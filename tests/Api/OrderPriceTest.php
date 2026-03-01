<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\AbstractServiceType;
use App\Entity\Candidate;
use App\Entity\DurationServiceType;
use App\Entity\JobTitle;
use App\Entity\MediaObject;
use App\Entity\Professional;
use App\Entity\ProService;
use App\Entity\UnitServiceType;
use App\Entity\User;
use App\Enum\MediaCategory;

class OrderPriceTest extends ApiTestCase
{
    private $entityManager;
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        
        // Force schema update in test environment to avoid "no such table" issues
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function testOrderPriceCalculation(): void
    {
        try {
            // 1. SETUP DATA (fixtures)
            // ---------------------------------------------------
            
            // A. Create a Professional
            $proUser = new User();
            $proUser->setEmail('pro@test.com');
            $proUser->setPassword(
                self::getContainer()->get('security.user_password_hasher')->hashPassword($proUser, 'password')
            );
            $this->entityManager->persist($proUser);

            $jobTitle = new JobTitle();
            $jobTitle->setName('Casting Director');
            $this->entityManager->persist($jobTitle);

            $pro = new Professional();
            $pro->setUser($proUser)
                ->setFirstName('Jean')
                ->setLastName('Pro')
                ->setJobTitle($jobTitle)
                ->setCity('Paris')
                ->setIsExpressEnabled(true)
                ->setExpressPremiumPercent(20.0);
            $this->entityManager->persist($pro);

            // B. Create Services
            // Service 1: PHOTOS (Unit) - Base: 10 photos = 50€, Supp: 5€/photo
            $photoType = new UnitServiceType();
            $photoType->setName('Photos')->setSlug('photos')->setIsActive(true)
                      ->setBaseQuantity(10)
                      ->setOrderMinQty(1)
                      ->setOrderMaxQty(20)
                      ->setUnitName('photo');
            $this->entityManager->persist($photoType);

            $proServicePhoto = new ProService();
            $proServicePhoto->setProfessional($pro)
                            ->setServiceType($photoType)
                            ->setIsActive(true)
                            ->setBasePrice(5000) // 50.00€
                            ->setSupplementPrice(500); // 5.00€
            $this->entityManager->persist($proServicePhoto);

            // Service 2: VIDEO SCENES (Duration) - Base: 3 min = 30€, Supp: 10€/min
            $videoType = new DurationServiceType();
            $videoType->setName('Scènes')->setSlug('scenes')->setIsActive(true)
                      ->setBaseDurationMin(3)
                      ->setOrderMinFiles(1)
                      ->setOrderMaxFiles(5);
            $this->entityManager->persist($videoType);

            $proServiceVideo = new ProService();
            $proServiceVideo->setProfessional($pro)
                            ->setServiceType($videoType)
                            ->setIsActive(true)
                            ->setBasePrice(3000) // 30.00€
                            ->setSupplementPrice(1000); // 10.00€
            $this->entityManager->persist($proServiceVideo);

            // C. Create Candidate & Media
            $candidateUser = new User();
            $candidateUser->setEmail('candidate@test.com');
            $candidateUser->setRoles(['ROLE_CANDIDATE']);
            $candidateUser->setPassword(
                self::getContainer()->get('security.user_password_hasher')->hashPassword($candidateUser, 'password')
            );
            $this->entityManager->persist($candidateUser);

            $candidate = new Candidate();
            $candidate->setUser($candidateUser)
                      ->setFirstName('Alice')
                      ->setLastName('Actor');
            $this->entityManager->persist($candidate);

            // Media 1 to 14 (Photos)
            $photoMedias = [];
            for ($i = 0; $i < 14; $i++) {
                $m = new MediaObject();
                $m->setCandidate($candidate)->setFilePath("path/photo_$i.jpg")
                  ->setCategory(MediaCategory::PHOTO)
                  ->setOriginalName("photo_$i.jpg");
                $this->entityManager->persist($m);
                $photoMedias[] = $m;
            }

            // Media Video (5m20s = 320 seconds) 
            // Logic: 5m20s. Base 3m (180s). Diff = 140s = 2m20s. Wrapped to upper minute = 3 minutes suppl.
            $videoMedia = new MediaObject();
            $videoMedia->setCandidate($candidate)->setFilePath("path/video.mp4")
                       ->setCategory(MediaCategory::VIDEO_SCENE)
                       ->setDuration(320)
                       ->setOriginalName("video.mp4");
            $this->entityManager->persist($videoMedia);

            $this->entityManager->flush();

            // 1.5 Authenticate as Candidate
            $authResponse = $this->client->request('POST', '/api/login_check', [
                'json' => [
                    'email' => 'candidate@test.com',
                    'password' => 'password'
                ]
            ]);
            $this->assertResponseIsSuccessful();
            $token = $authResponse->toArray()['token'];

            // 2. EXECUTE (Create Order via API)
            // ---------------------------------------------------
            $jsonPayload = [
                'candidate' => '/api/candidates/' . $candidate->getId(),
                'professional' => '/api/professionals/' . $pro->getId(),
                'status' => 'CART',
                'orderLines' => [
                    // Line 1: 14 Photos
                    [
                        'service' => '/api/pro_services/' . $proServicePhoto->getId(), 
                        'quantityBilled' => 14,
                        'mediaObjects' => array_map(fn($m) => '/api/media_objects/' . $m->getId(), $photoMedias)
                    ],
                    // Line 2: 1 Video (5m20s)
                    [
                        'service' => '/api/pro_services/' . $proServiceVideo->getId(),
                        'quantityBilled' => 0, // Calculated automatically ideally, but let's pass dummy
                        'mediaObjects' => ['/api/media_objects/' . $videoMedia->getId()]
                    ]
                ]
            ];
            
            $response = $this->client->request('POST', '/api/orders', [
                'headers' => [
                    'Content-Type' => 'application/ld+json',
                    'Authorization' => 'Bearer ' . $token
                ],
                'json' => $jsonPayload
            ]);

            // 3. VERIFY
            // ---------------------------------------------------
            $this->assertResponseIsSuccessful();
            $data = $response->toArray();

            // Fetch the Order entity from the database
            $orderRef = $this->entityManager->getRepository(\App\Entity\Order::class)->find($data['id']);
            $this->assertNotNull($orderRef, 'Order should be found in the database');

            // Verify Total Price
            $this->assertNotNull($orderRef->getTotalAmountTtc(), 'DB Total should not be null');
            $this->assertEquals(13000, $orderRef->getTotalAmountTtc(), 'DB Total should match calculation');

            // 2. Check Financial Breakdown (Commission 15%)
            // Total: 13000
            // Commission: round(13000 * 0.15) = 1950
            // Pro: 13000 - 1950 = 11050
            $expectedCommission = 1950;
            $expectedProAmount = 11050;
            $expectedVat = 20.0;

            $this->assertEquals($expectedCommission, $orderRef->getCommissionAmount(), 'Commission Amount should be 15%');
            $this->assertEquals($expectedProAmount, $orderRef->getProAmount(), 'Pro Amount should be Total - Commission');
            $this->assertEquals($expectedVat, $orderRef->getAppliedVatPercent(), 'VAT should be 20%');

            // Expectation:
            // Line 1 (Photos): 14 items. Base (10) included in 50€. 4 extra * 5€ = 20€. Total = 70€.
            // Line 2 (Video): 5m20s (320s). Base 3m (180s). Diff 140s. Ceil(2.33m) = 3m extra.
            // 30€ + (3 * 10€) = 60€.
            // Grand Total = 70€ + 60€ = 130€.
            
            if (!array_key_exists('totalAmountTtc', $data)) {
                echo "\nKeys received: " . implode(', ', array_keys($data)) . "\n";
                // Fallback: Fetch the order via GET to see if it was saved correctly
                if (isset($data['@id'])) {
                    $getResp = $this->client->request('GET', $data['@id']);
                    $data = $getResp->toArray();
                    echo "\nFetched via GET. Keys: " . implode(', ', array_keys($data)) . "\n";
                }
            }

            $this->assertEquals(13000, $data['totalAmountTtc'], 'Total amount should be 130.00€ (70€ Photos + 60€ Video)');

        } catch (\Throwable $e) {
            throw $e;
        }
    }
}
