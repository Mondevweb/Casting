<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

class ProfessionalTest extends ApiTestCase
{
    public function testGetCollection(): void
    {
        // Request the collection of professionals
        // If the API requires authentication, this might return 401, which is still a valid response to assert for now.
        // It confirms the API is reachable and routing works.
        $response = static::createClient()->request('GET', '/api/professionals');

        // Expecting 200 OK assuming public read access or 401 if secured.
        // Let's assert successful for now, and see what happens.
        $this->assertResponseIsSuccessful();
        
        // Use JSON-LD assertion style
        $this->assertJsonContains(['@context' => '/api/contexts/Professional']);
    }
}
