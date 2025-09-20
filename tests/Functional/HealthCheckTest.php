<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class HealthCheckTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testHealthEndpointReturnsCorrectResponse(): void
    {
        $this->client->request('GET', '/api/health', [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $response = $this->client->getResponse();
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertResponseHeaderSame('Content-Type', 'application/json; charset=utf-8');

        $data = json_decode($response->getContent(), true);
        
        // Test required fields
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('timestamp', $data);
        $this->assertArrayHasKey('api', $data);
        $this->assertArrayHasKey('system', $data);
        
        // Test field values
        $this->assertEquals('ok', $data['status']);
        $this->assertNotEmpty($data['timestamp']);
        $this->assertIsValidTimestamp($data['timestamp']);
        
        // Test API information
        $this->assertArrayHasKey('name', $data['api']);
        $this->assertArrayHasKey('version', $data['api']);
        $this->assertArrayHasKey('environment', $data['api']);
        $this->assertEquals('Hello API Platform', $data['api']['name']);
        $this->assertEquals('1.0.0', $data['api']['version']);
        
        // Test system information
        $this->assertArrayHasKey('php_version', $data['system']);
        $this->assertArrayHasKey('symfony_version', $data['system']);
        $this->assertArrayHasKey('api_platform_version', $data['system']);
        $this->assertNotEmpty($data['system']['php_version']);
        $this->assertNotEmpty($data['system']['symfony_version']);
        
        // Should not have message field
        $this->assertArrayNotHasKey('message', $data);
    }

    public function testPingEndpointReturnsCorrectResponse(): void
    {
        $this->client->request('GET', '/api/ping', [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $response = $this->client->getResponse();
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertResponseHeaderSame('Content-Type', 'application/json; charset=utf-8');

        $data = json_decode($response->getContent(), true);
        
        // Test required fields
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('timestamp', $data);
        $this->assertArrayHasKey('message', $data);
        
        // Test field values
        $this->assertEquals('ok', $data['status']);
        $this->assertEquals('pong', $data['message']);
        $this->assertNotEmpty($data['timestamp']);
        $this->assertIsValidTimestamp($data['timestamp']);
        
        // Should not have api or system fields
        $this->assertArrayNotHasKey('api', $data);
        $this->assertArrayNotHasKey('system', $data);
    }

    public function testHealthEndpointWithJsonLdFormat(): void
    {
        $this->client->request('GET', '/api/health', [], [], [
            'HTTP_ACCEPT' => 'application/ld+json',
        ]);

        $response = $this->client->getResponse();
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');

        $data = json_decode($response->getContent(), true);
        
        // Test JSON-LD specific fields
        $this->assertArrayHasKey('@context', $data);
        $this->assertArrayHasKey('@id', $data);
        $this->assertArrayHasKey('@type', $data);
        
        $this->assertEquals('/api/contexts/Health', $data['@context']);
        $this->assertEquals('/api/health', $data['@id']);
        $this->assertEquals('Health', $data['@type']);
        
        // Test that regular fields are still present
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('timestamp', $data);
        $this->assertEquals('ok', $data['status']);
    }

    public function testPingEndpointWithJsonLdFormat(): void
    {
        $this->client->request('GET', '/api/ping', [], [], [
            'HTTP_ACCEPT' => 'application/ld+json',
        ]);

        $response = $this->client->getResponse();
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');

        $data = json_decode($response->getContent(), true);
        
        // Test JSON-LD specific fields
        $this->assertArrayHasKey('@context', $data);
        $this->assertArrayHasKey('@id', $data);
        $this->assertArrayHasKey('@type', $data);
        
        $this->assertEquals('/api/contexts/Health', $data['@context']);
        $this->assertEquals('/api/ping', $data['@id']);
        $this->assertEquals('Health', $data['@type']);
        
        // Test that regular fields are still present
        $this->assertEquals('ok', $data['status']);
        $this->assertEquals('pong', $data['message']);
    }

    public function testHealthEndpointCacheHeaders(): void
    {
        $this->client->request('GET', '/api/health');

        $response = $this->client->getResponse();
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Cache-Control', 'no-cache, private');
        $this->assertResponseHasHeader('Vary');
        $this->assertStringContainsString('Accept', $response->headers->get('Vary'));
    }

    public function testPingEndpointCacheHeaders(): void
    {
        $this->client->request('GET', '/api/ping');

        $response = $this->client->getResponse();
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Cache-Control', 'no-cache, private');
        $this->assertResponseHasHeader('Vary');
        $this->assertStringContainsString('Accept', $response->headers->get('Vary'));
    }

    public function testHealthEndpointResponseTime(): void
    {
        $startTime = microtime(true);
        
        $this->client->request('GET', '/api/health');
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        $this->assertResponseIsSuccessful();
        $this->assertLessThan(1000, $responseTime, 'Health check should respond within 1 second');
    }

    public function testPingEndpointResponseTime(): void
    {
        $startTime = microtime(true);
        
        $this->client->request('GET', '/api/ping');
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        $this->assertResponseIsSuccessful();
        $this->assertLessThan(500, $responseTime, 'Ping should respond within 500ms');
    }

    private function assertIsValidTimestamp(string $timestamp): void
    {
        $dateTime = \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $timestamp);
        $this->assertInstanceOf(\DateTimeImmutable::class, $dateTime, 'Timestamp should be a valid ISO 8601 format');
        
        // Check that timestamp is recent (within last 10 seconds)
        $now = new \DateTimeImmutable();
        $diff = $now->getTimestamp() - $dateTime->getTimestamp();
        $this->assertLessThan(10, abs($diff), 'Timestamp should be current (within 10 seconds)');
    }
}