<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\OrderLine;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class OrderLineProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        #[Autowire(service: 'api_platform.doctrine.orm.state.remove_processor')]
        private ProcessorInterface $removeProcessor,
        private \App\Service\OrderPriceCalculator $calculator
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // 1. SUPPRESSION (DELETE)
        if ($operation instanceof DeleteOperationInterface) {
            $order = null;
            if ($data instanceof OrderLine && $data->getOrder()) {
                $order = $data->getOrder();
                $order->removeOrderLine($data); // Retirer la ligne en mémoire pour le calcul
            }

            // Exécuter la vraie requête de suppression en base pour la ligne
            $result = $this->removeProcessor->process($data, $operation, $uriVariables, $context);

            if ($order) {
                // Si la commande est désormais vide, on la supprime également
                if ($order->getOrderLines()->isEmpty()) {
                    // On supprime l'Order parent avec le même removeProcessor
                    // (Le contexte et l'opération importent peu pour Doctrine, l'essentiel est l'entité)
                    $this->removeProcessor->process($order, $operation, [], $context);
                } elseif ($this->calculator) {
                    // Sinon on recalcule simplement le nouveau total
                    $this->calculator->calculate($order);
                }
            }

            return $result;
        }

        // 2. CRÉATION / MODIFICATION (POST / PATCH)
        if ($data instanceof OrderLine) {
            
            // Si le ProService est renseigné, on assigne automatiquement le ServiceType
            if ($data->getService() && !$data->getServiceType()) {
                $data->setServiceType($data->getService()->getServiceType());
            }

            // Logique de calcul via le service partagé (inclut le snapshot des prix Unit/Base)
            if ($data->getService() && $this->calculator) {
                // S'assurer que la quantité facturée a une valeur de base
                if (($data->getQuantityBilled() ?? 0) === 0) {
                     $data->setQuantityBilled(1);
                }
                
                $lineTotal = $this->calculator->calculateLinePrice($data);
                $data->setLineTotalAmount($lineTotal);
            }
        }

        // On persiste la ligne
        $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        
        // Recalcul du parent après création
        // DEBUG : Dump des IDS médias reçus avant persistance
        if ($data instanceof OrderLine) {
            $mediaIds = [];
            foreach ($data->getMediaObjects() as $m) {
                $mediaIds[] = $m->getId();
            }
            file_put_contents(__DIR__.'/../../var/log/dump_media.txt', "Operation: " . get_class($operation) . " | Ligne ID: " . $data->getId() . " | Medias count: " . count($mediaIds) . " | IDs recues: " . implode(',', $mediaIds) . PHP_EOL, FILE_APPEND);
        }

        return $result;
    }
}
