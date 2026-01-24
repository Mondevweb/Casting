<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Professional;
use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Enum\ProfessionalStatus;

class ProfessionnalProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof Professional && $data->getUser() === null) {
            // C'est une nouvelle inscription !
            
            // 1. On récupère les infos virtuelles envoyées dans le JSON
            $email = $data->getEmail();
            $plainPassword = $data->getPlainPassword();

            if ($email && $plainPassword) {
                // 2. On crée le User associé
                $user = new User();
                $user->setEmail($email);
                // On hashe le mot de passe
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
                $user->setRoles(['ROLE_PRO']); // On force le rôle
                
                // FORCE LE STATUT À "PENDING" (Même si le JSON dit autre chose)
                $data->setStatus(ProfessionalStatus::PENDING);

                // 3. On lie les deux !
                // (Cascade: ['persist'] sur la relation $user dans Candidate fera le reste)
                $data->setUser($user);
            }
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}