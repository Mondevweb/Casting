<?php

namespace App\Controller;

use App\Entity\Order;
use App\Enum\OrderStatus;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

#[Route('/webhook/stripe', name: 'stripe_webhook', methods: ['POST'])]
class StripeWebhookController extends AbstractController
{
    public function __construct(
        #[Autowire(env: 'STRIPE_WEBHOOK_SECRET')]
        private string $stripeWebhookSecret,
        private EntityManagerInterface $entityManager,
        private WorkflowInterface $orderLifecycleStateMachine
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');

        try {
            $event = Webhook::constructEvent(
                $payload, $sigHeader, $this->stripeWebhookSecret
            );
        } catch(\UnexpectedValueException $e) {
            // Invalid payload
            return new Response('Invalid payload', 400);
        } catch(SignatureVerificationException $e) {
            // Invalid signature
            return new Response('Invalid signature', 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;
                $this->handlePaymentSucceeded($paymentIntent);
                break;
            // ... handle other event types
            default:
                // echo 'Received unknown event type ' . $event->type;
        }

        return new Response('Received', 200);
    }

    private function handlePaymentSucceeded(object $paymentIntent): void
    {
        // En vrai mode, on utiliserait $paymentIntent->metadata->order_id
        // Mais $paymentIntent est un objet Stripe, on accède aux propriétés
        if (!isset($paymentIntent->metadata->order_id)) {
            // Log warning ?
            return;
        }

        $orderId = $paymentIntent->metadata->order_id;
        $order = $this->entityManager->getRepository(Order::class)->find($orderId);

        if (!$order) {
            // Log error ?
            return;
        }

        // Appliquer la transition 'payment_validated'
        if ($this->orderLifecycleStateMachine->can($order, 'payment_validated')) {
             $this->orderLifecycleStateMachine->apply($order, 'payment_validated');
             
             // Enregistrer la date de paiement
             $order->setPaidAt(new \DateTimeImmutable());
             
             // Ici on pourrait aussi stocker l'ID de transaction Stripe, etc.
             
             $this->entityManager->flush();
        }
    }
}
