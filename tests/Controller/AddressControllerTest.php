<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Tests\DatabasePrimer;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AddressControllerTest extends WebTestCase
{
    use DatabasePrimer;

    private function getToken(KernelBrowser $client, string $email): string
    {
        $client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => $email, 'password' => 'password123']));

        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => $email, 'password' => 'password123']));

        $data = json_decode($client->getResponse()->getContent(), true);

        return $data['token'];
    }

    public function testListAddressesEmpty(): void
    {
        $client = static::createClient();
        $this->resetDatabase($client);
        $token = $this->getToken($client, 'list@test.com');

        $client->request('GET', '/api/addresses', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertEmpty($data);
    }

    public function testCreateAddress(): void
    {
        $client = static::createClient();
        $this->resetDatabase($client);
        $token = $this->getToken($client, 'create@test.com');

        $client->request('POST', '/api/addresses', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ], json_encode([
            'address' => 'bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh',
            'label' => 'Test wallet',
        ]));

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertSame('bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh', $data['address']);
        $this->assertSame('Test wallet', $data['label']);
    }

    public function testCreateDuplicateAddress(): void
    {
        $client = static::createClient();
        $this->resetDatabase($client);
        $token = $this->getToken($client, 'duplicate-address@test.com');

        $payload = json_encode([
            'address' => 'bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh',
            'label' => 'Test wallet',
        ]);

        $client->request('POST', '/api/addresses', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ], $payload);
        $this->assertResponseStatusCodeSame(201);

        $client->request('POST', '/api/addresses', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ], $payload);
        $this->assertResponseStatusCodeSame(409);
    }

    public function testCreateAddressUnauthenticated(): void
    {
        $client = static::createClient();
        $this->resetDatabase($client);

        $client->request('POST', '/api/addresses', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['address' => 'bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh']));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testDeleteAddress(): void
    {
        $client = static::createClient();
        $this->resetDatabase($client);
        $token = $this->getToken($client, 'delete@test.com');

        $client->request('POST', '/api/addresses', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ], json_encode(['address' => 'bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh']));

        $data = json_decode($client->getResponse()->getContent(), true);
        $id = $data['id'];

        $client->request('DELETE', '/api/addresses/'.$id, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ]);

        $this->assertResponseStatusCodeSame(204);
    }
}
