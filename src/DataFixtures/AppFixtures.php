<?php

namespace App\DataFixtures;

use App\Entity\Candidate;
use App\Entity\JobTitle;
use App\Entity\Order;
use App\Entity\OrderLine;
use App\Entity\Professional;
use App\Entity\ProService;
use App\Entity\UnitServiceType;
use App\Entity\DurationServiceType;
use App\Entity\AbstractServiceType;
use App\Entity\Specialty;
use App\Entity\User;
use App\Enum\OrderStatus;
use App\Enum\ProfessionalStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private const JOBS = ['Photographe', 'Vidéaste', 'Ingénieur Son', 'Monteur', 'Etalonneur', 'Cadreur', 'Maquilleur', 'Styliste'];
    private const SPECS = ['Portrait', 'Mariage', 'Corporate', 'Mode', 'Pub', 'Clip', 'Documentaire'];
    private const CITIES = ['Paris', 'Lyon', 'Marseille', 'Bordeaux', 'Lille'];

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // =====================================================================
        // 1. ADMIN
        // =====================================================================
        $admin = new User();
        $admin->setEmail('admin@casting.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $manager->persist($admin);

        // =====================================================================
        // Create Job Titles
        $jobTitles = [];
        $titleNames = [
            'Directeur de Casting',
            'Assistant Directeur de Casting',
            'Agent Artistique',
            'Assistant Agent Artistique'
        ];
        
        foreach ($titleNames as $name) {
            $jobTitle = new JobTitle();
            $jobTitle->setName($name);
            $manager->persist($jobTitle);
            $jobTitles[] = $jobTitle;
        }

        // Create Specialties (Assuming Actors, so specialties might be relevant or not, keep for now)
        $specialties = [];
        $specNames = ['Cinéma', 'Théâtre', 'Publicité', 'Télévision', 'Doublage', 'Danse', 'Chant'];
        foreach ($specNames as $name) {
            $spec = new Specialty();
            $spec->setName($name);
            $manager->persist($spec);
            $specialties[] = $spec;
        }

        // =====================================================================
        // 3. SERVICE TYPES (Reference Data)
        // =====================================================================
        $serviceTypes = [];

        // 1. Analyse de Photos (Unit)
        $srv1 = new UnitServiceType();
        $srv1->setName('Analyse de Photos');
        $srv1->setSlug('analyse-de-photos');
        $srv1->setUnitName('photo');
        $srv1->setLibraryQuota(50);
        $srv1->setOrderMinQty(1);
        $srv1->setOrderMaxQty(10);
        $srv1->setIsActive(true);
        $manager->persist($srv1);
        $serviceTypes[] = $srv1;

        // 2. Analyse de Bande Démo (Unit)
        $srv2 = new UnitServiceType();
        $srv2->setName('Analyse de Bande Démo');
        $srv2->setSlug('analyse-de-bande-demo');
        $srv2->setUnitName('bande-demo');
        $srv2->setLibraryQuota(10);
        $srv2->setOrderMinQty(1);
        $srv2->setOrderMaxQty(1);
        $srv2->setIsActive(true);
        $manager->persist($srv2);
        $serviceTypes[] = $srv2;

        // 3. Analyse de Vidéo de Présentation (Unit)
        $srv3 = new UnitServiceType();
        $srv3->setName('Analyse de Vidéo de Présentation');
        $srv3->setSlug('analyse-de-video-presentation');
        $srv3->setUnitName('video-presentation');
        $srv3->setLibraryQuota(10);
        $srv3->setOrderMinQty(1);
        $srv3->setOrderMaxQty(1);
        $srv3->setIsActive(true);
        $manager->persist($srv3);
        $serviceTypes[] = $srv3;

        // 4. Analyse de CV (Unit)
        $srv4 = new UnitServiceType();
        $srv4->setName('Analyse de CV');
        $srv4->setSlug('analyse-de-cv');
        $srv4->setUnitName('cv');
        $srv4->setLibraryQuota(10);
        $srv4->setOrderMinQty(1);
        $srv4->setOrderMaxQty(1);
        $srv4->setIsActive(true);
        $manager->persist($srv4);
        $serviceTypes[] = $srv4;

        // 5. Composition de Bande Démo (Unit - multiple scenes input logic implied)
        $srv5 = new UnitServiceType(); // Or DurationServiceType if per minute? User said "uploading multiple scenes". Assuming Unit "1 Demo Reel".
        $srv5->setName('Composition de Bande Démo');
        $srv5->setSlug('composition-de-bande-demo');
        $srv5->setUnitName('bande-demo-montee');
        $srv5->setLibraryQuota(20); // More space for scenes
        $srv5->setOrderMinQty(1);
        $srv5->setOrderMaxQty(1);
        $srv5->setIsActive(true);
        $manager->persist($srv5);
        $serviceTypes[] = $srv5;
        
        $manager->flush();

        // =====================================================================
        // 4. PROFESSIONALS (20)
        // =====================================================================
        $pros = [];
        for ($i = 0; $i < 20; $i++) {
            $user = new User();
            $user->setEmail('pro' . ($i+1) . '@example.com');
            $user->setRoles(['ROLE_PRO']);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
            
            $manager->persist($user);

            $pro = new Professional();
            $pro->setUser($user);
            $pro->setFirstName($faker->firstName());
            $pro->setLastName($faker->lastName());
            $pro->setJobTitle($faker->randomElement($jobTitles));
            $pro->setCity($faker->randomElement(self::CITIES));
            $pro->setZipCode($faker->postcode());
            $pro->setDepartmentCode(substr($pro->getZipCode(), 0, 2));
            $pro->setStatus(ProfessionalStatus::ACTIVE);
            $pro->setIsStripeVerified($faker->boolean(80));
            $pro->setIsExpressEnabled($faker->boolean(30));
            $pro->setStandardDelayDays($faker->numberBetween(2, 14));
            $pro->setBiography($faker->paragraph());
            
            // Add Specialties
            $s = $faker->randomElements($specialties, $faker->numberBetween(1, 3));
            foreach ($s as $spec) {
                $pro->addSpecialty($spec);
            }

            // Add Services
            foreach ($serviceTypes as $st) {
                if ($faker->boolean(70)) {
                    $ps = new ProService();
                    $ps->setProfessional($pro);
                    $ps->setServiceType($st);
                    $ps->setBasePrice($faker->numberBetween(5000, 50000)); // 50€ - 500€
                    $ps->setIsActive(true);
                    $manager->persist($ps);
                }
            }

            $manager->persist($pro);
            $pros[] = $pro;
        }

        // =====================================================================
        // 5. CANDIDATES (30)
        // =====================================================================
        $candidates = [];
        for ($i = 0; $i < 30; $i++) {
            $user = new User();
            $user->setEmail("candidate$i@casting.com");
            $user->setRoles(['ROLE_CANDIDATE']);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
            $manager->persist($user);

            $candidate = new Candidate();
            $candidate->setUser($user);
            $candidate->setFirstName($faker->firstName());
            $candidate->setLastName($faker->lastName());
            $manager->persist($candidate);
            $candidates[] = $candidate;
        }

        $manager->flush(); // Save Users before Orders

        // =====================================================================
        // 6. ORDERS (50)
        // =====================================================================
        foreach ($candidates as $candidate) {
            // Each candidate makes 1-3 orders
            for ($k = 0; $k < $faker->numberBetween(1, 3); $k++) {
                $pro = $faker->randomElement($pros);
                
                $order = new Order();
                $order->setReference('ORD-' . strtoupper($faker->bothify('????-####')));
                $order->setCandidate($candidate);
                $order->setProfessional($pro);
                
                // Status random
                $status = $faker->randomElement([
                    OrderStatus::CART, 
                    OrderStatus::PENDING_PAYMENT, 
                    OrderStatus::PAID_PENDING_PRO, 
                    OrderStatus::IN_PROGRESS, 
                    OrderStatus::COMPLETED
                ]);
                $order->setStatus($status);
                
                // Lines
                $total = 0;
                // Add 1 or 2 lines
                $proServices = $pro->getProServices();
                if ($proServices->count() > 0) {
                     $ps = $faker->randomElement($proServices->toArray());
                     
                     $line = new OrderLine();
                     $line->setOrder($order);
                     $line->setServiceType($ps->getServiceType()); // Abstract mapping
                     $line->setQuantity($faker->numberBetween(1, 5));
                     $line->setUnitPrice($ps->getBasePrice());
                     $line->setTotal($line->getQuantity() * $line->getUnitPrice());
                     
                     $manager->persist($line);
                     $total += $line->getTotal();
                }

                $order->setTotalAmountTtc($total);
                $order->setCommissionAmount((int)($total * 0.1));
                $order->setProAmount($total - $order->getCommissionAmount());
                $order->setAppliedVatPercent(20.0);

                $manager->persist($order);
            }
        }

        $manager->flush();
    }
}