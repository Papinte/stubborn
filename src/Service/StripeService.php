<?php

namespace App\Service;

use Stripe\Stripe;
use Stripe\PaymentIntent;

class StripeService
{
    private $secretKey;

    public function __construct(string $secretKey)
    {
        $this->secretKey = $secretKey;
        Stripe::setApiKey($this->secretKey);
    }

    public function createPaymentIntent(array $cart): PaymentIntent
{
    // Calculer le montant total en centimes
    $totalAmount = 0;
    foreach ($cart as $item) {
        if (!isset($item['price']) || !is_numeric($item['price'])) {
            throw new \LogicException('Prix invalide pour l\'article : ' . json_encode($item));
        }
        $totalAmount += (float) $item['price'] * 100; // Convertir en centimes
    }

    // Vérifier que le montant est un entier
    if (!is_int($totalAmount)) {
        $totalAmount = (int) round($totalAmount);
    }

    if ($totalAmount <= 0) {
        throw new \LogicException('Le montant total doit être supérieur à 0.');
    }

    // Créer un PaymentIntent
    return PaymentIntent::create([
        'amount' => $totalAmount,
        'currency' => 'eur',
        'payment_method_types' => ['card'],
        'description' => 'Commande Stubborn',
        'metadata' => [
            'cart_items' => json_encode($cart),
        ],
    ]);
}

    public function confirmPaymentIntent(string $paymentIntentId, string $paymentMethodId = 'pm_card_visa'): void
    {
        $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
        $paymentIntent->confirm([
            'payment_method' => $paymentMethodId, // pm_card_visa est une méthode de paiement de test
        ]);
    }
}