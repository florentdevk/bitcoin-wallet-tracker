<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Tests\DatabasePrimer;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthControllerTest extends WebTestCase
{
    use DatabasePrimer;

    public function testRegisterSuccess(): void
    {
        $client = static::createClient();
        $this->resetDatabase($client);

        $client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => 'newuser@test.com', 'password' => 'password123']));

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $data);
    }

    public function testRegisterDuplicateEmail(): void
    {
        $client = static::createClient();
        $this->resetDatabase($client);

        $payload = json_encode(['email' => 'duplicate@test.com', 'password' => 'password123']);

        $client->request('POST', '/api/auth/register', [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $this->assertResponseStatusCodeSame(201);

        $client->request('POST', '/api/auth/register', [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $this->assertResponseStatusCodeSame(409);
    }

    public function testRegisterMissingFields(): void
    {
        $client = static::createClient();
        $this->resetDatabase($client);

        $client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => 'test@test.com']));

        $this->assertResponseStatusCodeSame(400);
    }

    public function testLoginSuccess(): void
    {
        $client = static::createClient();
        $this->resetDatabase($client);

        $client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => 'login@test.com', 'password' => 'password123']));

        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => 'login@test.com', 'password' => 'password123']));

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $data);
    }

    public function testLoginInvalidCredentials(): void
    {
        $client = static::createClient();
        $this->resetDatabase($client);

        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => 'wrong@test.com', 'password' => 'wrongpassword']));

        $this->assertResponseStatusCodeSame(401);
    }
}
