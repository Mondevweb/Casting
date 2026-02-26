<?php

namespace App\EventSubscriber;

use App\Entity\Order;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Workflow\Event\Event;

class OrderEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private MailerInterface $mailer
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.order_lifecycle.transition.payment_validated' => 'onPaymentValidated',
            'workflow.order_lifecycle.transition.pro_deliver' => 'onProDeliver',
        ];
    }

    public function onPaymentValidated(Event $event): void
    {
        $order = $event->getSubject();

        if (!$order instanceof Order) {
            return;
        }

        $candidate = $order->getCandidate();
        $pro = $order->getProfessional();

        if (!$candidate || !$pro) {
            // Should not happen if validation is correct
            return;
        }

        // Email Candidate
        $emailCandidate = (new TemplatedEmail())
            ->from('no-reply@casting-app.com')
            ->to($candidate->getEmail() ?? 'candidate@test.com') // Fallback si email non stocké direct (mais il est dans User)
            // En vrai : $candidate->getUser()->getEmail()
            ->subject('Confirmation de votre commande ' . $order->getReference())
            ->htmlTemplate('emails/order_confirmation.html.twig')
            ->context([
                'candidateName' => $candidate->getFirstName(),
                'orderReference' => $order->getReference(),
                'orderLines' => $this->formatOrderLines($order)
            ]);

        $this->mailer->send($emailCandidate);

        // Email Professional
        $emailPro = (new TemplatedEmail())
            ->from('no-reply@casting-app.com')
            ->to($pro->getUser()->getEmail() ?? 'pro@test.com')
            ->subject('Nouvelle commande reçue !')
            ->htmlTemplate('emails/new_order_notification.html.twig')
            ->context([
                'proName' => $pro->getFirstName(),
                'candidateName' => $candidate->getFirstName() . ' ' . $candidate->getLastName(),
                'orderReference' => $order->getReference(),
            ]);

        $this->mailer->send($emailPro);
    }

    public function onProDeliver(Event $event): void
    {
        $order = $event->getSubject();
        if (!$order instanceof Order) {
            return;
        }

        $candidate = $order->getCandidate();
        
        $email = (new TemplatedEmail())
            ->from('no-reply@casting-app.com')
            ->to($candidate->getUser()->getEmail())
            ->subject('Votre commande est prête !')
            ->htmlTemplate('emails/order_delivered.html.twig')
            ->context([
                'candidateName' => $candidate->getFirstName(),
                'proName' => $order->getProfessional()->getFirstName(),
                'orderReference' => $order->getReference(),
            ]);

        $this->mailer->send($email);
    }

    private function formatOrderLines(Order $order): array
    {
        $lines = [];
        foreach ($order->getOrderLines() as $line) {
            $lines[] = [
                'serviceName' => $line->getServiceType() ? $line->getServiceType()->getName() : 'Service',
                'quantity' => $line->getQuantityBilled()
            ];
        }
        return $lines;
    }
}
