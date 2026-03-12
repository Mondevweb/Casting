<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\MediaObject;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class MediaObjectRemoveProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.remove_processor')]
        private ProcessorInterface $removeProcessor,
        private EntityManagerInterface $em
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof MediaObject) {
            // Check if the media is attached to any OrderLine
            $query = $this->em->createQuery('SELECT COUNT(ol.id) FROM App\Entity\OrderLine ol JOIN ol.mediaObjects m WHERE m = :media');
            $query->setParameter('media', $data);
            $count = $query->getSingleScalarResult();

            if ($count == 0) {
                // HARD DELETE: Since it's not linked to any orders, we can completely delete it.
                // We must temporarily disable the SoftDeleteableListener to ensure data is deleted from DB.
                // (VichUploader uses preRemove/postRemove, which will still fire and delete files)
                $evm = $this->em->getEventManager();
                $softDeleteListener = null;
                
                foreach ($evm->getListeners('onFlush') as $listener) {
                    if ($listener instanceof \Gedmo\SoftDeleteable\SoftDeleteableListener) {
                        $softDeleteListener = $listener;
                        $evm->removeEventListener(['onFlush'], $listener);
                        break;
                    }
                }

                $result = $this->removeProcessor->process($data, $operation, $uriVariables, $context);

                // Restore listener for subsequent operations in same request
                if ($softDeleteListener) {
                    $evm->addEventListener(['onFlush'], $softDeleteListener);
                }

                return $result;
            } else {
                // SOFT DELETE: It's linked to an OrderLine, just let Doctrine/Gedmo intercept it and update deletedAt
                $result = $this->removeProcessor->process($data, $operation, $uriVariables, $context);
                return $result;
            }
        }

        return $this->removeProcessor->process($data, $operation, $uriVariables, $context);
    }
}
