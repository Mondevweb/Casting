<?php

namespace App\Tests\Unit;

use App\Controller\StripeWebhookController;
use App\Entity\Order;
use App\Enum\OrderStatus;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Workflow\WorkflowInterface;
use Stripe\Webhook;

class StripeWebhookControllerTest extends TestCase
{
    // On ne peut pas facilement mocker Stripe\Webhook::constructEvent car c'est une méthode statique.
    // Cependant, on peut tester la logique interne si on extrait la méthode, ou on peut utiliser
    // une bibliothèque comme 'php-mock-phpunit' pour mocker les fonctions natives ou statiques.
    //
    // Pour simplifier, on va tester la méthode privée via Reflection ou refactoriser le controller 
    // pour que la validation de signature soit injectable (Strategy pattern).
    //
    // Mais plus pragmatique : On va tester une requête avec un stub de service si on avait injecté le validateur.
    // Ici le Validateur est statique.
    
    // Alternative : On passe la validation dans le test en créant une signature valide avec la clé de test ? 
    // Difficile sans la lib Stripe réelle.
    
    // Option B : On crée un test d'intégration qui mocke le service Webhook mais c'est hard-coded dans le controller.
    
    // Option C (Choisi): On va supposer que Stripe fonctionne et on teste la logique APRES la validation.
    // Pour ça, on va rendre la méthode `handlePaymentSucceeded` publique pour le test ou utiliser Reflection.
    
    public function testHandlePaymentSucceededLogique(): void
    {
        // 1. Mocks
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $workflow = $this->createMock(WorkflowInterface::class);
        $repo = $this->createMock(EntityRepository::class);
        
        $entityManager->method('getRepository')->willReturn($repo);
        
        $controller = new StripeWebhookController('whsec_test', $entityManager, $workflow);
        
        // 2. Data
        $orderId = 123;
        $order = new Order();
        $order->setStatus(OrderStatus::PENDING_PAYMENT);
        
        // Repo doit trouver l'ordre
        $repo->expects($this->once())
            ->method('find')
            ->with($orderId)
            ->willReturn($order);
            
        // Workflow doit checker et appliquer
        $workflow->expects($this->once())
            ->method('can')
            ->with($order, 'payment_validated')
            ->willReturn(true);
            
        $workflow->expects($this->once())
             ->method('apply')
             ->with($order, 'payment_validated');
             
        $entityManager->expects($this->once())->method('flush');

        // 3. Execution via Reflection pour accéder à la méthode privée
        $reflection = new \ReflectionClass(StripeWebhookController::class);
        $method = $reflection->getMethod('handlePaymentSucceeded');
        $method->setAccessible(true);
        
        // Mock de l'objet PaymentIntent de Stripe (qui est un simple object stdClass ou StripeObject)
        $paymentIntent = (object) [
            'metadata' => (object) ['order_id' => $orderId]
        ];
        
        $method->invoke($controller, $paymentIntent);
        
        // Assertions implicites via les expects des mocks
        $this->assertTrue(true); 
    }
}
