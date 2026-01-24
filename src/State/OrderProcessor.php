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
        // On injecte le processeur par défaut (celui qui sait sauvegarder en BDD)
        // pour pouvoir l'appeler à la fin de notre traitement.
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // On vérifie qu'on traite bien une Commande
        if ($data instanceof Order) {
            
            // --- LOGIQUE À LA CRÉATION (Si pas d'ID, c'est une nouvelle commande) ---
            if ($data->getId() === null) {
                // Générer la référence unique (Ex: ORD-2026-ABC12)
                if ($data->getReference() === null) {
                    $data->setReference($this->generateReference());
                }
            }

            // --- LOGIQUE À LA MISE À JOUR (Transition de statut) ---
            // Ici, vous pourrez ajouter plus tard la logique de "Snapshot" des prix
            // quand le statut passera à "PENDING_PAYMENT" ou "PAID".
        }

        // Une fois notre logique terminée, on laisse API Platform faire le travail standard (sauvegarde SQL)
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