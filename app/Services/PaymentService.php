<?php

namespace App\Services;

use App\Models\Order;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class PaymentService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createCheckoutSession(float $amount, string $currency, Order $order)
    {
        $baseUrl = config('app.url');
        $checkoutSession = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => [
                        'name' => 'Order #' . $order->id,
                    ],
                    'unit_amount' => (int)round($amount * 100), // Convert to smallest unit
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $baseUrl . '/order-success?orderId=' . $order->id,
            'cancel_url' => $baseUrl . '/order-cancel?orderId=' . $order->id,
            'payment_intent_data' => [
                'capture_method' => 'manual', // Hold payment for 7 days
                'description' => 'Order #' . $order->id,
                'metadata' => ['order_id' => $order->id],
            ],
        ]);

        $order->update(['checkout_session_id' => $checkoutSession->id]);

        return $checkoutSession;
    }

    public function capturePayment(string $checkoutSessionId)
    {
        $session = Session::retrieve($checkoutSessionId);
        $paymentIntent = \Stripe\PaymentIntent::retrieve($session->payment_intent);
        return $paymentIntent->capture();
    }

    public function cancelPayment(string $checkoutSessionId)
    {
        $session = Session::retrieve($checkoutSessionId);
        $paymentIntent = \Stripe\PaymentIntent::retrieve($session->payment_intent);
        return $paymentIntent->cancel();
    }
}
