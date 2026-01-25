<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Professional;
use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Enum\ProfessionalStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ProfessionalProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private UserPasswordHasherInterface $passwordHasher,
        private Security $security, // <--- Injection Security
        private EntityManagerInterface $entityManager // <--- Injection EntityManager
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // 1. Cas de l'inscription (Création)
        if ($data instanceof Professional && $data->getUser() === null) {
            $this->handleRegistration($data);
        }
        
        // 2. Cas de la mise à jour (Edition)
        if ($data instanceof Professional && $data->getId() !== null) {
            $this->handleSecurityChecks($data);
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }

    private function handleRegistration(Professional $professional): void
    {
        $email = $professional->getEmail();
        $plainPassword = $professional->getPlainPassword();

        if ($email && $plainPassword) {
            $user = new User();
            $user->setEmail($email);
            $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
            $user->setRoles(['ROLE_PRO']);
            
            $professional->setStatus(ProfessionalStatus::PENDING);
            $professional->setUser($user);
        }
    }

    private function handleSecurityChecks(Professional $professional): void
    {
        // On récupère les changements
        $uow = $this->entityManager->getUnitOfWork();
        $uow->computeChangeSets();
        $changes = $uow->getEntityChangeSet($professional);

        // Règle 1 : Protection du statut
        if (isset($changes['status']) && !$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Seul un administrateur peut modifier le statut.');
        }

        // Règle 2 : (Exemple futur) Protection du Siret
        if (isset($changes['siretNumber']) && $professional->isStripeVerified()) {
            throw new AccessDeniedException('Impossible de changer le SIRET une fois vérifié.');
        }
    }
}