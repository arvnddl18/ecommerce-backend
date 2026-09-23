<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\Cart\AddToCartRequest;
use App\Http\Requests\Api\v1\Cart\UpdateCartRequest;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService
    ) {}

    /**
     * Display current cart contents and subtotal.
     */
    public function index(Request $request): JsonResponse
    {
        $identifier = $this->resolveCartIdentifier($request);
        $cart = $this->cartService->getCart($identifier);

        return response()->json([
            'cart_token' => $identifier,
            'data' => $cart,
        ]);
    }

    /**
     * Add an item to the shopping cart.
     */
    public function store(AddToCartRequest $request): JsonResponse
    {
        $identifier = $this->resolveCartIdentifier($request);
        $validated = $request->validated();
        $quantity = $validated['quantity'] ?? 1;

        $variantId = isset($validated['variant_id']) && $validated['variant_id'] ? (int) $validated['variant_id'] : null;
        $cart = $this->cartService->addItem($identifier, (int) $validated['product_id'], (int) $quantity, $variantId);

        return response()->json([
            'message' => 'Item added to cart.',
            'cart_token' => $identifier,
            'data' => $cart,
        ]);
    }

    /**
     * Update item quantity in the cart.
     */
    public function update(UpdateCartRequest $request, string $productId): JsonResponse
    {
        $identifier = $this->resolveCartIdentifier($request);
        $validated = $request->validated();

        $cart = $this->cartService->updateQuantity($identifier, $productId, (int) $validated['quantity']);

        return response()->json([
            'message' => 'Cart updated.',
            'cart_token' => $identifier,
            'data' => $cart,
        ]);
    }

    /**
     * Remove an item from the cart.
     */
    public function destroy(Request $request, string $productId): JsonResponse
    {
        $identifier = $this->resolveCartIdentifier($request);
        $cart = $this->cartService->removeItem($identifier, $productId);

        return response()->json([
            'message' => 'Item removed from cart.',
            'cart_token' => $identifier,
            'data' => $cart,
        ]);
    }

    /**
     * Clear all items from the cart.
     */
    public function clear(Request $request): JsonResponse
    {
        $identifier = $this->resolveCartIdentifier($request);
        $this->cartService->clearCart($identifier);

        return response()->json([
            'message' => 'Cart cleared.',
            'cart_token' => $identifier,
            'data' => $this->cartService->getCart($identifier),
        ]);
    }

    /**
     * Apply a discount coupon to the cart.
     */
    public function applyCoupon(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ]);

        $identifier = $this->resolveCartIdentifier($request);
        $cart = $this->cartService->applyCoupon($identifier, $validated['code']);

        return response()->json([
            'message' => 'Coupon applied successfully.',
            'cart_token' => $identifier,
            'data' => $cart,
        ]);
    }

    /**
     * Remove applied coupon from the cart.
     */
    public function removeCoupon(Request $request): JsonResponse
    {
        $identifier = $this->resolveCartIdentifier($request);
        $cart = $this->cartService->removeCoupon($identifier);

        return response()->json([
            'message' => 'Coupon removed.',
            'cart_token' => $identifier,
            'data' => $cart,
        ]);
    }

    /**
     * Resolve unique cart identifier for authenticated user or guest session token.
     */
    protected function resolveCartIdentifier(Request $request): string
    {
        if ($request->user('sanctum')) {
            return 'user_'.$request->user('sanctum')->id;
        }

        $headerToken = $request->header('X-Cart-Token') ?? $request->query('cart_token');

        if ($headerToken && is_string($headerToken) && strlen($headerToken) >= 16) {
            $cleaned = preg_replace('/[^a-zA-Z0-9_\-]/', '', $headerToken);

            return str_starts_with($cleaned, 'guest_') ? $cleaned : 'guest_'.$cleaned;
        }

        return 'guest_'.Str::random(32);
    }
}
