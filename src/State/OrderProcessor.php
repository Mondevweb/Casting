<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Order;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Ce Processor intercepte toutes les écritures (POST, PATCH, PUT) sur l'entité Order.
 * Il permet d'ajouter notre logique métier avant ou après la sauvegarde en base.
 */
class OrderProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private \App\Service\OrderPriceCalculator $calculator,
        private \Symfony\Component\HttpFoundation\RequestStack $requestStack,
        private \Doctrine\ORM\EntityManagerInterface $entityManager
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // On vérifie qu'on traite bien une Commande
        if ($data instanceof Order) {
            
            // WORKAROUND: Force hydration of Candidate/Professional if null (Deserializer issues)
            $request = $this->requestStack->getCurrentRequest();
            if ($request) {
                $payload = $request->toArray(); // Works for JSON/LD-JSON typically
                
                if ($data->getCandidate() === null && isset($payload['candidate'])) {
                    // Extract ID from IRI: /api/candidates/123 -> 123
                    if (preg_match('/\/(\d+)$/', $payload['candidate'], $matches)) {
                        $candidate = $this->entityManager->getReference(\App\Entity\Candidate::class, (int)$matches[1]);
                        $data->setCandidate($candidate);
                    }
                }

                if ($data->getProfessional() === null && isset($payload['professional'])) {
                    if (preg_match('/\/(\d+)$/', $payload['professional'], $matches)) {
                        $professional = $this->entityManager->getReference(\App\Entity\Professional::class, (int)$matches[1]);
                        $data->setProfessional($professional);
                    }
                }

                if ($data->getOrderLines()->isEmpty() && isset($payload['orderLines']) && is_array($payload['orderLines'])) {
                    foreach ($payload['orderLines'] as $lineData) {
                        $line = new \App\Entity\OrderLine();
                        
                        // Service Type (Unit or Duration)
                        if (isset($lineData['serviceType'])) {
                            // Try both types or abstract? Abstract is abstract. We know IDs are unique across tables usually? 
                            // Actually, getReference needs specific class.
                            // The URI tells us the class! /api/unit_service_types/1 or /api/duration_service_types/2
                            if (str_contains($lineData['serviceType'], 'unit_service_types')) {
                                $cls = \App\Entity\UnitServiceType::class;
                            } elseif (str_contains($lineData['serviceType'], 'duration_service_types')) {
                                $cls = \App\Entity\DurationServiceType::class;
                            } else {
                                $cls = \App\Entity\AbstractServiceType::class; // Fallback? Might fail if mapped superclass
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
                        
                        // Pro Service?
                        // Wait, OrderLine has `private ?ProService $service`.
                        // But payload sends serviceType.
                        // Ideally we should find the matching ProService for this Pro + ServiceType.
                        // BUT for this test (Pricing), if logic uses $service->getBasePrice(), we NEED it.
                        // The test creates a ProService.
                        // How does `OrderPriceCalculator` find the price?
                        // `calculateLinePrice` does `$service = $line->getService()`.
                        // If `$service` is null, returns 0.
                        // So we MUST link to `ProService`.
                        
                        // How do we find it?
                        // Use repository to find ProService where professional = order.professional AND serviceType = line.serviceType.
                        if ($data->getProfessional() && $line->getServiceType()) {
                             $psRepo = $this->entityManager->getRepository(\App\Entity\ProService::class);
                             $proService = $psRepo->findOneBy([
                                 'professional' => $data->getProfessional(),
                                 'serviceType' => $line->getServiceType()
                             ]);
                             if ($proService) {
                                 $line->setService($proService);
                             }
                        }

                        $data->addOrderLine($line);
                    }
                }
            }

            // CORRECTION: Assurer le lien ProService pour TOUTES les lignes (hydratées manuellement OU par API Platform)
            if ($data->getProfessional()) {
                $psRepo = $this->entityManager->getRepository(\App\Entity\ProService::class);
                foreach ($data->getOrderLines() as $line) {
                    if ($line->getService() === null && $line->getServiceType() !== null) {
                        $proService = $psRepo->findOneBy([
                             'professional' => $data->getProfessional(),
                             'serviceType' => $line->getServiceType()
                        ]);
                        if ($proService) {
                             $line->setService($proService);
                        } else {
                            // DEBUG
                            throw new \Exception("ProService NOT FOUND for ProID " . $data->getProfessional()->getId() . " ServiceType " . $line->getServiceType()->getId());
                        }
                    } elseif ($line->getServiceType() === null) {
                        throw new \Exception("ServiceType is NULL on OrderLine!");
                    }
                }
            }

            // --- LOGIQUE À LA CRÉATION (Si pas d'ID, c'est une nouvelle commande) ---
            if ($data->getId() === null) {
                // Générer la référence unique (Ex: ORD-2026-ABC12)
                if ($data->getReference() === null) {
                    $data->setReference($this->generateReference());
                }
                
                // Calcul du prix
                $this->calculator->calculate($data);
            }


            // --- LOGIQUE À LA MISE À JOUR (Transition de statut) ---
            // Ici, vous pourrez ajouter plus tard la logique de "Snapshot" des prix
            // quand le statut passera à "PENDING_PAYMENT" ou "PAID".
            if ($data->getId() !== null) {
                 // Recalcul au cas où (si status CART)
                 if ($data->getStatus() === \App\Enum\OrderStatus::CART) {
                     $this->calculator->calculate($data);
                 }
            }
        }

        // Une fois notre logique terminée, on laisse API Platform faire le travail standard (sauvegarde SQL)
        // echo "PRE-PERSIST Total: " . ($data->getTotalAmountTtc() ?? 'NULL') . "\n";
        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }

    /**
     * Génère une référence aléatoire unique et lisible.
     */
    private function generateReference(): string
    {
        // Dictionnaire de caractères (Chiffres + Lettres majuscules, sans confusions comme O/0 ou I/1 si on veut, mais ici on garde tout pour l'entropie)
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomPart = '';
        
        // On tire 8 caractères totalement aléatoires
        for ($i = 0; $i < 8; $i++) {
            $randomPart .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return 'ORD-' . date('Y') . '-' . $randomPart;
    }
}