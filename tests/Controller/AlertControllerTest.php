<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Tests\DatabasePrimer;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlertControllerTest extends WebTestCase
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

    private function createAddress(KernelBrowser $client, string $token): int
    {
        $client->request('POST', '/api/addresses', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ], json_encode([
            'address' => 'bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh',
            'label' => 'Test wallet',
        ]));

        $data = json_decode($client->getResponse()->getContent(), true);

        return $data['id'];
    }

    public function testCreateAlert(): void
    {
        $client = static::createClient();
        $this->resetDatabase($client);
        $token = $this->getToken($client, 'alert-create@test.com');
        $addressId = $this->createAddress($client, $token);

        $client->request('POST', '/api/alerts', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ], json_encode([
            'watched_address_id' => $addressId,
            'type' => 'balance_above',
            'threshold_value' => 1.0,
        ]));

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertSame('balance_above', $data['type']);
        $this->assertTrue($data['isActive']);
    }

    public function testCreateAlertInvalidType(): void
    {
        $client = static::createClient();
        $this->resetDatabase($client);
        $token = $this->getToken($client, 'alert-invalid@test.com');
        $addressId = $this->createAddress($client, $token);

        $client->request('POST', '/api/alerts', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ], json_encode([
            'watched_address_id' => $addressId,
            'type' => 'invalid_type',
            'threshold_value' => 1.0,
        ]));

        $this->assertResponseStatusCodeSame(400);
    }

    public function testListAlerts(): void
    {
        $client = static::createClient();
        $this->resetDatabase($client);
        $token = $this->getToken($client, 'alert-list@test.com');
        $addressId = $this->createAddress($client, $token);

        $client->request('POST', '/api/alerts', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ], json_encode([
            'watched_address_id' => $addressId,
            'type' => 'balance_below',
            'threshold_value' => 0.5,
        ]));

        $client->request('GET', '/api/alerts', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
    }

    public function testDeleteAlert(): void
    {
        $client = static::createClient();
        $this->resetDatabase($client);
        $token = $this->getToken($client, 'alert-delete@test.com');
        $addressId = $this->createAddress($client, $token);

        $client->request('POST', '/api/alerts', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ], json_encode([
            'watched_address_id' => $addressId,
            'type' => 'balance_above',
            'threshold_value' => 1.0,
        ]));

        $data = json_decode($client->getResponse()->getContent(), true);
        $id = $data['id'];

        $client->request('DELETE', '/api/alerts/'.$id, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ]);

        $this->assertResponseStatusCodeSame(204);
    }

    public function testCreateAlertUnauthenticated(): void
    {
        $client = static::createClient();
        $this->resetDatabase($client);

        $client->request('POST', '/api/alerts', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'watched_address_id' => 1,
            'type' => 'balance_above',
            'threshold_value' => 1.0,
        ]));

        $this->assertResponseStatusCodeSame(401);
    }
}
