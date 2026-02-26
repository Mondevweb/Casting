<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User;

class RegistrationTest extends ApiTestCase
{
    private function cleanupUser(string $email): void
    {
        // Nettoyage manuel avant/après si besoin, 
        // mais le kernel reboot entre les tests (sauf si static::$booted)
        // On utilisera des emails uniques pour éviter les conflits
    }

    public function testRegisterCandidate(): void
    {
        $client = static::createClient();
        $email = 'new_candidate_' . uniqid() . '@test.com';

        $response = $client->request('POST', '/api/candidates', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'firstName' => 'Jean',
                'lastName' => 'Dupont',
                'email' => $email,
                'plainPassword' => 'password123',
                // Optional fields
                'gender' => 'Homme'
            ]
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'firstName' => 'Jean',
            'lastName' => 'Dupont'
            // email is write-only in API definition? No,mapped to User but virtual property might not be returned.
            // Let's check User creation
        ]);

        // Verify User existence and Role
        // On doit récupérer le conteneur pour accéder à la DB
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

        $this->assertNotNull($user, 'Le User devrait être créé.');
        $this->assertContains('ROLE_CANDIDATE', $user->getRoles());
        $this->assertTrue($user->getCandidate() !== null, 'Le User doit être lié à un Candidate.');
    }

    public function testRegisterProfessional(): void
    {
        $client = static::createClient();
        $email = 'new_pro_' . uniqid() . '@test.com';

        // Pour un Pro, il faut souvent un JobTitle valide. 
        // On va en créer un ou en chercher un via l'API, ou assumer qu'il y en a en base de test.
        // Créons-en un "mock" si possible, ou utilisons une IRI.
        // => Mieux : utiliser les fixtures ou créer via l'EM.
        
        // On boot le kernel pour accéder à l'EM
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        
        // Créer un JobTitle
        $job = new \App\Entity\JobTitle();
        $job->setName('Photographe Test ' . uniqid());
        $em->persist($job);
        $em->flush();

        $response = $client->request('POST', '/api/professionals', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'firstName' => 'Pierre',
                'lastName' => 'Pro',
                'companyName' => 'Studio Photo',
                'email' => $email,
                'plainPassword' => 'pro12345',
                'jobTitle' => '/api/job_titles/' . $job->getId(),
                'city' => 'Paris'
            ]
        ]);

        $this->assertResponseStatusCodeSame(201);
        
        // Verify User
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        $this->assertNotNull($user, 'Le User Pro devrait être créé.');
        $this->assertContains('ROLE_PRO', $user->getRoles());
        $this->assertTrue($user->getProfessional() !== null, 'Le User doit être lié à un Professional.');
    }
}
