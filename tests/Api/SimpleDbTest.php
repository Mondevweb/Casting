<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User;

class SimpleDbTest extends ApiTestCase
{
    public function testDbInsert(): void
    {
        $kernel = self::bootKernel();
        $em = $kernel->getContainer()->get('doctrine')->getManager();

        $user = new User();
        $user->setEmail('simpletest@test.com')->setPassword('password');
        
        $em->persist($user);
        $em->flush();

        $this->assertNotNull($user->getId());
    }
}
