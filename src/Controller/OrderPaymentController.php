<?php

namespace App\Controller;

use App\Entity\Order;
use App\Service\StripePaymentService;
use App\Enum\OrderStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Workflow\WorkflowInterface;

#[AsController]
class OrderPaymentController extends AbstractController
{
    public function __construct(
        private StripePaymentService $stripePaymentService,
        private WorkflowInterface $orderLifecycleStateMachine
    ) {
    }

    public function __invoke(Order $order): JsonResponse
    {
        // 1. Vérifier si on peut payer (Transition 'checkout')
        if ($this->orderLifecycleStateMachine->can($order, 'checkout')) {
            $this->orderLifecycleStateMachine->apply($order, 'checkout');
            // TODO: Persister le changement de statut ? 
            // API Platform le fera peut-être si on retourne l'objet, mais ici on retourne un JSON custom.
            // Mieux vaut flush ici.
             // $this->entityManager->flush(); (Nécessite l'injection de EM)
        }

        // Si déjà en PENDING_PAYMENT, on continue (retry)
        if ($order->getStatus() !== OrderStatus::PENDING_PAYMENT && $order->getStatus() !== OrderStatus::CART) {
             return new JsonResponse(['error' => 'Commande non payable.'], 400);
        }

        try {
            $clientSecret = $this->stripePaymentService->createPaymentIntent($order);
            
            return new JsonResponse([
                'clientSecret' => $clientSecret,
                'orderReference' => $order->getReference()
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
