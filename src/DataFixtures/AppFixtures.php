<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    // On injecte le hasher pour crypter le mot de passe de l'admin
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // Création de l'Administrateur
        $admin = new User();
        $admin->setEmail('admin@casting.com');
        $admin->setRoles(['ROLE_ADMIN']); // <--- C'est ici que la magie opère
        
        // Hachage du mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword(
            $admin,
            'admin123'
        );
        $admin->setPassword($hashedPassword);

        $manager->persist($admin);

        // On valide l'enregistrement
        $manager->flush();
    }
}