<?php

namespace Tests\Feature;

use App\Jobs\ProcessStripeWebhookJob;
use App\Mail\OrderConfirmationMail;
use App\Mail\SellerOrderNotificationMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StripeConnectTransferAndEmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_webhook_queues_buyer_and_seller_emails_and_disburses_transfers(): void
    {
        Mail::fake();

        $sellerUser = User::factory()->create([
            'role' => 'seller',
            'email' => 'atelier@fold.test',
        ]);

        $seller = SellerProfile::create([
            'user_id' => $sellerUser->id,
            'store_name' => 'Apex Heavyweight Club',
            'slug' => 'apex-heavyweight-club',
            'stripe_account_id' => 'acct_apex_connected_123',
            'verification_status' => 'approved',
            'total_sales' => 0,
        ]);

        $buyer = User::factory()->create([
            'role' => 'buyer',
            'email' => 'shopper@test.com',
            'name' => 'Julian Collector',
        ]);

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'name' => '400GSM Vintage Washed Boxy Tee',
            'price' => 10000, // $100.00
            'stock' => 20,
        ]);

        $order = Order::create([
            'user_id' => $buyer->id,
            'order_number' => 'ORD-TRANSFER-999',
            'status' => Order::STATUS_PENDING,
            'total_amount' => 10000,
            'currency' => 'usd',
            'customer_email' => $buyer->email,
            'customer_name' => $buyer->name,
            'stripe_session_id' => 'cs_test_transfer_session_999',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'seller_id' => $seller->id,
            'product_name' => $product->name,
            'unit_price' => 10000,
            'quantity' => 1,
            'total_price' => 10000,
            'fulfillment_status' => 'pending',
        ]);

        $eventPayload = [
            'id' => 'evt_test_checkout_complete_999',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_transfer_session_999',
                    'payment_intent' => 'pi_test_999',
                    'metadata' => [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                    ],
                ],
            ],
        ];

        // Execute webhook job synchronously
        $job = new ProcessStripeWebhookJob($eventPayload);
        $job->handle();

        // 1. Order marked as paid
        $order->refresh();
        $this->assertEquals(Order::STATUS_PAID, $order->status);

        // 2. Buyer received order confirmation mail
        Mail::assertQueued(OrderConfirmationMail::class, function ($mail) use ($buyer) {
            return $mail->hasTo($buyer->email);
        });

        // 3. Seller received seller order notification mail
        Mail::assertQueued(SellerOrderNotificationMail::class, function ($mail) use ($sellerUser) {
            return $mail->hasTo($sellerUser->email);
        });

        // 4. Seller total sales incremented by 90% ($90.00 = 9000 cents after 10% platform fee)
        $seller->refresh();
        $this->assertEquals(9000, $seller->total_sales);
    }
}
