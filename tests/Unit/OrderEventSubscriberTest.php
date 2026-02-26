<?php

namespace App\Tests\Unit;

use App\EventSubscriber\OrderEventSubscriber;
use App\Entity\Order;
use App\Entity\Candidate;
use App\Entity\Professional;
use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Mime\Email;

class OrderEventSubscriberTest extends TestCase
{
    public function testOnPaymentValidatedSendsEmails(): void
    {
        // 1. Mocks
        $mailer = $this->createMock(MailerInterface::class);
        $subscriber = new OrderEventSubscriber($mailer);
        
        // 2. Data
        $candidateUser = new User(); $candidateUser->setEmail('candidate@test.com');
        $candidate = new Candidate(); $candidate->setFirstName('Jean')->setUser($candidateUser);
        
        $proUser = new User(); $proUser->setEmail('pro@test.com');
        $pro = new Professional(); $pro->setFirstName('Pierre')->setUser($proUser);
        
        $order = new Order();
        $order->setReference('ORD-123');
        $order->setCandidate($candidate);
        $order->setProfessional($pro);
        
        $event = new Event($order, new \Symfony\Component\Workflow\Marking(), new \Symfony\Component\Workflow\Transition('payment_validated', 'pending_payment', 'paid_pending_pro'));

        // 3. Expectations
        // On s'attend à 2 envois d'email (Candidat + Pro)
        $mailer->expects($this->exactly(2))
            ->method('send')
            ->with($this->isInstanceOf(Email::class));

        // 4. Execute
        $subscriber->onPaymentValidated($event);
    }
}
