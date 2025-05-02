<?php

namespace App\Service;

use Stripe\PaymentIntent;
use Stripe\Stripe;

class StripeService
{
    private $secretKey;

    public function __construct(string $secretKey)
    {
        $this->secretKey = $secretKey;
        Stripe::setApiKey($secretKey);
    }

    public function createPaymentIntent(array $cart): PaymentIntent
    {
        $totalPrice = 0;
        foreach ($cart as $item) {
            $totalPrice += (float) $item['price'];
        }

        return PaymentIntent::create([
            'amount' => $totalPrice * 100, // Montant en centimes
            'currency' => 'eur',
            'payment_method_types' => ['card'],
        ]);
    }

    public function confirmPaymentIntent(string $paymentIntentId): void
    {
        $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
        $paymentIntent->confirm();
    }

    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return PaymentIntent::retrieve($paymentIntentId);
    }
}