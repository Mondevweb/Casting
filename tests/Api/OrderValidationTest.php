<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Candidate;
use App\Entity\DurationServiceType;
use App\Entity\JobTitle;
use App\Entity\MediaObject;
use App\Entity\Professional;
use App\Entity\ProService;
use App\Entity\UnitServiceType;
use App\Entity\User;
use App\Enum\MediaCategory;

class OrderValidationTest extends ApiTestCase
{
    private $entityManager;
    private $client;
    private string $token;
    private Professional $pro;
    private Candidate $candidate;
    private UnitServiceType $photoType;
    private DurationServiceType $videoType;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        
        // Force schema update
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        $this->setupFixtures();
        $this->authenticateCandidate();
    }

    private function setupFixtures(): void
    {
        // 1. Pro
        $proUser = new User();
        $proUser->setEmail('pro@test.com')->setPassword('password'); // Hashing handled by listener/processor usually? No, manual in test
        $proUser->setPassword(
            self::getContainer()->get('security.user_password_hasher')->hashPassword($proUser, 'password')
        );
        $this->entityManager->persist($proUser);

        $jobTitle = new JobTitle();
        $jobTitle->setName('Casting Director');
        $this->entityManager->persist($jobTitle);

        $this->pro = new Professional();
        $this->pro->setUser($proUser)
            ->setFirstName('Jean')->setLastName('Pro')
            ->setJobTitle($jobTitle)->setCity('Paris');
        $this->entityManager->persist($this->pro);

        // 2. Services
        // Photo: Min 10, Max 20
        $this->photoType = new UnitServiceType();
        $this->photoType->setName('Photos')->setSlug('photos')->setIsActive(true)
            ->setOrderMinQty(10)->setOrderMaxQty(20);
        $this->entityManager->persist($this->photoType);

        $psPhoto = new ProService();
        $psPhoto->setProfessional($this->pro)->setServiceType($this->photoType)->setIsActive(true)
                ->setBasePrice(5000)->setSupplementPrice(500);
        $this->entityManager->persist($psPhoto);

        // Video: Min 1 file
        $this->videoType = new DurationServiceType();
        $this->videoType->setName('Scènes')->setSlug('scenes')->setIsActive(true)
            ->setOrderMinFiles(1)->setOrderMaxFiles(5);
        $this->entityManager->persist($this->videoType);

        $psVideo = new ProService();
        $psVideo->setProfessional($this->pro)->setServiceType($this->videoType)->setIsActive(true)
                ->setBasePrice(3000)->setSupplementPrice(1000);
        $this->entityManager->persist($psVideo);

        // 3. Candidate
        $candidateUser = new User();
        $candidateUser->setEmail('candidate@test.com');
        $candidateUser->setPassword(
            self::getContainer()->get('security.user_password_hasher')->hashPassword($candidateUser, 'password')
        );
        $this->entityManager->persist($candidateUser);

        $this->candidate = new Candidate();
        $this->candidate->setUser($candidateUser)->setFirstName('Alice')->setLastName('Actor');
        $this->entityManager->persist($this->candidate);

        $this->entityManager->flush();
    }

    private function authenticateCandidate(): void
    {
        $response = $this->client->request('POST', '/api/login_check', [
            'json' => ['email' => 'candidate@test.com', 'password' => 'password']
        ]);
        $this->token = $response->toArray()['token'];
    }

    public function testOrderBelowMinQuantity(): void
    {
        // Try to order 5 photos when min is 10
        $response = $this->client->request('POST', '/api/orders', [
            'headers' => ['Authorization' => 'Bearer ' . $this->token, 'Content-Type' => 'application/ld+json'],
            'json' => [
                'candidate' => '/api/candidates/' . $this->candidate->getId(),
                'professional' => '/api/professionals/' . $this->pro->getId(),
                'orderLines' => [
                    [
                        'serviceType' => '/api/unit_service_types/' . $this->photoType->getId(),
                        'quantityBilled' => 5 // < 10
                    ]
                ]
            ]
        ]);

        $this->assertResponseStatusCodeSame(400);
        $content = $response->toArray(false);
        $this->assertArrayHasKey('detail', $content);
        $this->assertStringContainsString('minimale pour \'Photos\' est de 10', $content['detail']);
    }

    public function testOrderAboveMaxQuantity(): void
    {
        // Try to order 25 photos when max is 20
        $response = $this->client->request('POST', '/api/orders', [
            'headers' => ['Authorization' => 'Bearer ' . $this->token, 'Content-Type' => 'application/ld+json'],
            'json' => [
                'candidate' => '/api/candidates/' . $this->candidate->getId(),
                'professional' => '/api/professionals/' . $this->pro->getId(),
                'orderLines' => [
                    [
                        'serviceType' => '/api/unit_service_types/' . $this->photoType->getId(),
                        'quantityBilled' => 25 // > 20
                    ]
                ]
            ]
        ]);

        $this->assertResponseStatusCodeSame(400);
        $content = $response->toArray(false);
        $this->assertArrayHasKey('detail', $content);
        $this->assertStringContainsString('maximale pour \'Photos\' est de 20', $content['detail']);
    }

    public function testOrderWithoutFilesForDurationService(): void
    {
        // Try to order Video without files (min 1)
        $response = $this->client->request('POST', '/api/orders', [
            'headers' => ['Authorization' => 'Bearer ' . $this->token, 'Content-Type' => 'application/ld+json'],
            'json' => [
                'candidate' => '/api/candidates/' . $this->candidate->getId(),
                'professional' => '/api/professionals/' . $this->pro->getId(),
                'orderLines' => [
                    [
                        'serviceType' => '/api/duration_service_types/' . $this->videoType->getId(),
                        'quantityBilled' => 0,
                        'mediaObjects' => [] // 0 files
                    ]
                ]
            ]
        ]);

        $this->assertResponseStatusCodeSame(400);
        $content = $response->toArray(false);
        $this->assertArrayHasKey('detail', $content);
        $this->assertStringContainsString('fournir au moins 1 fichier(s)', $content['detail']);
    }

    public function testOrderServiceNotProvidedByPro(): void
    {
        // Create a new ServiceType NOT linked to the Pro
        $otherService = new UnitServiceType();
        $otherService->setName('Autre')->setSlug('autre')->setIsActive(true);
        $this->entityManager->persist($otherService);
        $this->entityManager->flush();

        $response = $this->client->request('POST', '/api/orders', [
            'headers' => ['Authorization' => 'Bearer ' . $this->token, 'Content-Type' => 'application/ld+json'],
            'json' => [
                'candidate' => '/api/candidates/' . $this->candidate->getId(),
                'professional' => '/api/professionals/' . $this->pro->getId(),
                'orderLines' => [
                    [
                        'serviceType' => '/api/unit_service_types/' . $otherService->getId(),
                        'quantityBilled' => 1
                    ]
                ]
            ]
        ]);

        $this->assertResponseStatusCodeSame(400);
        $content = $response->toArray(false);
        $this->assertArrayHasKey('detail', $content);
        $this->assertStringContainsString('ne propose pas le service', $content['detail']);
    }

    public function testValidOrder(): void
    {
        $response = $this->client->request('POST', '/api/orders', [
            'headers' => ['Authorization' => 'Bearer ' . $this->token, 'Content-Type' => 'application/ld+json'],
            'json' => [
                'candidate' => '/api/candidates/' . $this->candidate->getId(),
                'professional' => '/api/professionals/' . $this->pro->getId(),
                'orderLines' => [
                    [
                        'serviceType' => '/api/unit_service_types/' . $this->photoType->getId(),
                        'quantityBilled' => 12 // OK (10 < 12 < 20)
                    ]
                ]
            ]
        ]);
        
        // DEBUG if fails
        if ($response->getStatusCode() !== 201) {
             var_dump($response->toArray(false));
        }

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
    }
}
