<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\Checkout\CheckoutSessionRequest;
use App\Http\Resources\Api\v1\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected StripeService $stripeService
    ) {}

    /**
     * Initialize a checkout session and Stripe Checkout redirect.
     */
    public function createSession(CheckoutSessionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user('sanctum');

        $cartIdentifier = $validated['cart_token'] ?? null;
        if (! $cartIdentifier) {
            $cartIdentifier = $user ? 'user_'.$user->id : ($request->header('X-Cart-Token') ?? 'guest_default');
        }

        $cart = $this->cartService->getCart($cartIdentifier);

        if (empty($cart['items'])) {
            throw ValidationException::withMessages([
                'cart' => 'Your shopping cart is empty.',
            ]);
        }

        // Validate stock availability
        foreach ($cart['items'] as $item) {
            $product = Product::find($item['product_id']);
            if (! $product) {
                throw ValidationException::withMessages([
                    'stock' => "Product '{$item['name']}' is no longer available.",
                ]);
            }

            if (! empty($item['variant_id'])) {
                $variant = ProductVariant::find($item['variant_id']);
                if (! $variant) {
                    throw ValidationException::withMessages([
                        'stock' => "Product variant '{$item['name']}' is no longer available.",
                    ]);
                }

                if ($variant->stock_quantity < $item['quantity']) {
                    $variantLabel = ($item['size'] ?? '').' / '.($item['color'] ?? '');
                    throw ValidationException::withMessages([
                        'stock' => "Product '{$item['name']}' ({$variantLabel}) only has {$variant->stock_quantity} units left.",
                    ]);
                }
            } elseif ($product->stock < $item['quantity']) {
                throw ValidationException::withMessages([
                    'stock' => "Product '{$item['name']}' only has {$product->stock} units left.",
                ]);
            }
        }

        $frontendUrl = rtrim(config('app.url', 'http://localhost:8000'), '/');
        $successUrl = $validated['success_url'] ?? "{$frontendUrl}/order/success?session_id={CHECKOUT_SESSION_ID}";
        $cancelUrl = $validated['cancel_url'] ?? "{$frontendUrl}/order/cancel";

        $order = DB::transaction(function () use ($validated, $user, $cart): Order {
            $orderNumber = 'ORD-'.strtoupper(Str::random(10));
            $subtotal = $cart['subtotal'];
            $discount = $cart['discount'];
            $totalAmount = max(0, $subtotal - $discount);

            $order = Order::create([
                'user_id' => $user?->id,
                'order_number' => $orderNumber,
                'status' => Order::STATUS_PENDING,
                'total_amount' => $totalAmount,
                'currency' => config('services.stripe.currency', 'usd'),
                'customer_email' => $validated['customer_email'],
                'customer_name' => $validated['customer_name'] ?? $user?->name,
                'shipping_address' => $validated['shipping_address'] ?? null,
                'metadata' => [
                    'coupon_code' => $cart['coupon']['code'] ?? null,
                    'discount_amount' => $discount,
                    'subtotal' => $subtotal,
                ],
            ]);

            foreach ($cart['items'] as $item) {
                $product = Product::find($item['product_id']);
                $variantDetails = $item['variant_details'] ?? null;
                $productDisplayName = $item['name'];
                if (! empty($item['size']) || ! empty($item['color'])) {
                    $productDisplayName .= ' ('.implode(' / ', array_filter([$item['size'] ?? '', $item['color'] ?? ''])).')';
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'seller_id' => $product?->seller_id,
                    'variant_id' => $item['variant_id'] ?? null,
                    'variant_details' => $variantDetails,
                    'product_name' => $productDisplayName,
                    'unit_price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'total_price' => $item['total'],
                    'fulfillment_status' => 'pending',
                ]);
            }

            return $order;
        });

        $session = $this->stripeService->createCheckoutSession(
            $order->load('items'),
            $successUrl,
            $cancelUrl
        );

        $order->update([
            'stripe_session_id' => $session->id,
        ]);

        // Clear cart now that order is generated
        $this->cartService->clearCart($cartIdentifier);

        return response()->json([
            'message' => 'Checkout session created successfully.',
            'order_number' => $order->order_number,
            'session_id' => $session->id,
            'checkout_url' => $session->url,
        ]);
    }

    /**
     * Retrieve status and details of an order.
     */
    public function getOrderStatus(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)
            ->orWhere('stripe_session_id', $orderNumber)
            ->with('items')
            ->firstOrFail();

        return response()->json([
            'data' => new OrderResource($order),
        ]);
    }
}
