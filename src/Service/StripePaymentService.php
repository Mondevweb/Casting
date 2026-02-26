<?php

namespace App\Service;

use App\Entity\Order;
use Stripe\StripeClient;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class StripePaymentService
{
    private StripeClient $stripe;

    public function __construct(
        #[Autowire(env: 'STRIPE_SECRET_KEY')]
        private string $stripeSecretKey
    ) {
        $this->stripe = new StripeClient($this->stripeSecretKey);
    }

    public function createPaymentIntent(Order $order): string
    {
        // 1. Calculer le montant en centimes
        // On suppose que getTotalAmountTtc() retourne un float, ex 10.50
        $amount = (int) round($order->getTotalAmountTtc() * 100);

        if ($amount <= 0) {
            throw new \LogicException('Le montant de la commande doit être supérieur à 0.');
        }

        // 2. Créer l'intention de paiement
        $paymentIntent = $this->stripe->paymentIntents->create([
            'amount' => $amount,
            'currency' => 'eur',
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
            'metadata' => [
                'order_reference' => $order->getReference(),
                'order_id' => $order->getId(),
            ],
        ]);

        return $paymentIntent->client_secret;
    }
}
