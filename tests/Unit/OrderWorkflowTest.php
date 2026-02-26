<?php

namespace App\Tests\Unit;

use App\Entity\Order;
use App\Enum\OrderStatus;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Workflow\WorkflowInterface;

class OrderWorkflowTest extends KernelTestCase
{
    private WorkflowInterface $workflow;

    protected function setUp(): void
    {
        self::bootKernel();
        // Le nom du workflow est 'order_lifecycle' mais le registre l'appelle souvent par son nom
        // Si autowiring, on peut l'injecter, sinon on le récupère via le module Registry
        $registry = static::getContainer()->get('workflow.registry');
        $order = new Order();
        $this->workflow = $registry->get($order, 'order_lifecycle');
    }

    public function testHappyPath(): void
    {
        $order = new Order();
        // Initial state is CART (by constructor)
        $this->assertEquals(OrderStatus::CART, $order->getStatus());
        $this->assertTrue($this->workflow->can($order, 'checkout'));

        // CART -> PENDING_PAYMENT
        $this->workflow->apply($order, 'checkout');
        $this->assertEquals(OrderStatus::PENDING_PAYMENT, $order->getStatus());

        // PENDING_PAYMENT -> PAID_PENDING_PRO
        $this->assertTrue($this->workflow->can($order, 'payment_validated'));
        $this->workflow->apply($order, 'payment_validated');
        $this->assertEquals(OrderStatus::PAID_PENDING_PRO, $order->getStatus());

        // PAID_PENDING_PRO -> IN_PROGRESS
        $this->assertTrue($this->workflow->can($order, 'pro_validate'));
        $this->workflow->apply($order, 'pro_validate');
        $this->assertEquals(OrderStatus::IN_PROGRESS, $order->getStatus());

        // IN_PROGRESS -> DELIVERED
        $this->assertTrue($this->workflow->can($order, 'pro_deliver'));
        $this->workflow->apply($order, 'pro_deliver');
        $this->assertEquals(OrderStatus::DELIVERED, $order->getStatus());

        // DELIVERED -> COMPLETED
        $this->assertTrue($this->workflow->can($order, 'customer_accept'));
        $this->workflow->apply($order, 'customer_accept');
        $this->assertEquals(OrderStatus::COMPLETED, $order->getStatus());
    }

    public function testCannotSkipSteps(): void
    {
        $order = new Order();
        $this->assertEquals(OrderStatus::CART, $order->getStatus());

        // Cannot go directly to COMPLETED
        $this->assertFalse($this->workflow->can($order, 'customer_accept'));
        
        $this->expectException(\Symfony\Component\Workflow\Exception\LogicException::class);
        $this->workflow->apply($order, 'customer_accept');
    }

    public function testCancellation(): void
    {
        // CART -> CANCELLED
        $order = new Order();
        $this->assertTrue($this->workflow->can($order, 'cancel'));
        $this->workflow->apply($order, 'cancel');
        $this->assertEquals(OrderStatus::CANCELLED, $order->getStatus());

        // PENDING -> CANCELLED
        $order2 = new Order();
        $this->workflow->apply($order2, 'checkout');
        $this->assertTrue($this->workflow->can($order2, 'cancel'));
        $this->workflow->apply($order2, 'cancel');
        $this->assertEquals(OrderStatus::CANCELLED, $order2->getStatus());
    }
}
