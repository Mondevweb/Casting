<?php

namespace App\Service;

use App\Entity\DurationServiceType;
use App\Entity\Order;
use App\Entity\OrderLine;
use App\Entity\ProService;
use App\Entity\UnitServiceType;
use App\Repository\ProServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class OrderPreparationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProServiceRepository $proServiceRepository
    ) {
    }

    /**
     * Hydrate manualmente l'ordre à partir du payload (pour contourner les limitations de désérialisation API Platform)
     * et valide les règles métier.
     *
     * @param Order $order
     * @param array $payload
     * @throws BadRequestHttpException
     */
    public function prepareOrder(Order $order, array $payload): void
    {
        // 1. Hydratation Candidate / Professional
        $this->hydrateActors($order, $payload);

        // 2. Hydratation & Validation des Lignes
        if ($order->getOrderLines()->isEmpty() && isset($payload['orderLines']) && is_array($payload['orderLines'])) {
            foreach ($payload['orderLines'] as $lineData) {
                $line = new OrderLine();
                $this->hydrateOrderLine($line, $lineData);
                
                // Lier le ProService et Valider
                if ($order->getProfessional()) {
                    $this->linkProServiceAndValidate($order, $line);
                }

                $order->addOrderLine($line);
            }
        } else {
             // Cas où les lignes sont déjà hydratées (ex: modification, ou hydratation standard réussie)
             // On doit quand même valider et lier le ProService si manquant
             if ($order->getProfessional()) {
                 foreach ($order->getOrderLines() as $line) {
                     $this->linkProServiceAndValidate($order, $line);
                 }
             }
        }
    }

    private function hydrateActors(Order $order, array $payload): void
    {
        if ($order->getCandidate() === null && isset($payload['candidate'])) {
            if (preg_match('/\/(\d+)$/', $payload['candidate'], $matches)) {
                $candidate = $this->entityManager->getReference(\App\Entity\Candidate::class, (int)$matches[1]);
                $order->setCandidate($candidate);
            }
        }

        if ($order->getProfessional() === null && isset($payload['professional'])) {
            if (preg_match('/\/(\d+)$/', $payload['professional'], $matches)) {
                $professional = $this->entityManager->getReference(\App\Entity\Professional::class, (int)$matches[1]);
                $order->setProfessional($professional);
            }
        }
    }

    private function hydrateOrderLine(OrderLine $line, array $lineData): void
    {
        // Service Type
        if (isset($lineData['serviceType'])) {
            // Détection du type de service via l'IRI
            if (str_contains($lineData['serviceType'], 'unit_service_types')) {
                $cls = \App\Entity\UnitServiceType::class;
            } elseif (str_contains($lineData['serviceType'], 'duration_service_types')) {
                $cls = \App\Entity\DurationServiceType::class;
            } else {
                $cls = \App\Entity\AbstractServiceType::class; 
            }

            if (preg_match('/\/(\d+)$/', $lineData['serviceType'], $matches)) {
                $st = $this->entityManager->getReference($cls, (int)$matches[1]);
                $line->setServiceType($st);
            }
        }

        // Quantity
        if (isset($lineData['quantityBilled'])) {
            $line->setQuantityBilled((int)$lineData['quantityBilled']);
        }

        // Media Objects
        if (isset($lineData['mediaObjects']) && is_array($lineData['mediaObjects'])) {
            foreach ($lineData['mediaObjects'] as $mediaUri) {
                if (preg_match('/\/(\d+)$/', $mediaUri, $matches)) {
                    $media = $this->entityManager->getReference(\App\Entity\MediaObject::class, (int)$matches[1]);
                    $line->addMediaObject($media);
                }
            }
        }
    }

    private function linkProServiceAndValidate(Order $order, OrderLine $line): void
    {
        $serviceType = $line->getServiceType();
        if (!$serviceType) {
             throw new BadRequestHttpException("Chaque ligne de commande doit avoir un ServiceType valide.");
        }

        // 1. Lier ProService
        if ($line->getService() === null) {
            $proService = $this->proServiceRepository->findOneBy([
                'professional' => $order->getProfessional(),
                'serviceType' => $serviceType
            ]);

            if (!$proService || !$proService->isActive()) {
                throw new BadRequestHttpException(sprintf(
                    "Le professionnel %s ne propose pas le service '%s' (ou il est inactif).",
                    $order->getProfessional()->getUser()->getEmail(), // Ou nom
                    $serviceType->getName()
                ));
            }
            $line->setService($proService);
        }

        // 2. Valider Règles Métier
        if ($serviceType instanceof UnitServiceType) {
            $qty = $line->getQuantityBilled();
            
            // Validation Quantité Min
            if ($qty < $serviceType->getOrderMinQty()) {
                 throw new BadRequestHttpException(sprintf(
                    "La quantité minimale pour '%s' est de %d (demandé: %d).",
                    $serviceType->getName(),
                    $serviceType->getOrderMinQty(),
                    $qty
                 ));
            }

            // Validation Quantité Max
            if ($qty > $serviceType->getOrderMaxQty()) {
                 throw new BadRequestHttpException(sprintf(
                    "La quantité maximale pour '%s' est de %d (demandé: %d).",
                    $serviceType->getName(),
                    $serviceType->getOrderMaxQty(),
                    $qty
                 ));
            }
        }
        elseif ($serviceType instanceof DurationServiceType) {
            $nbFiles = $line->getMediaObjects()->count();

            // Validation Nb Fichiers Min
            if ($nbFiles < $serviceType->getOrderMinFiles()) {
                 throw new BadRequestHttpException(sprintf(
                    "Vous devez fournir au moins %d fichier(s) pour '%s' (fourni: %d).",
                    $serviceType->getOrderMinFiles(),
                    $serviceType->getName(),
                    $nbFiles
                 ));
            }

            // Validation Nb Fichiers Max
            if ($nbFiles > $serviceType->getOrderMaxFiles()) {
                 throw new BadRequestHttpException(sprintf(
                    "Vous ne pouvez pas fournir plus de %d fichier(s) pour '%s' (fourni: %d).",
                    $serviceType->getOrderMaxFiles(),
                    $serviceType->getName(),
                    $nbFiles
                 ));
            }
        }
    }
}
