<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\InvoiceSettings;
use App\Tests\Trait\DatabaseTestTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class InvoiceNextNumberTest extends WebTestCase
{
    use DatabaseTestTrait;

    private $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        
        // Ensure admin exists
        $this->ensureTestAdmin();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestData();
        parent::tearDown();
    }

    public function testGetNextNumberRequiresAuthentication(): void
    {
        $this->client->request(Request::METHOD_GET, '/api/invoices/next-number');
        
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testGetNextNumberRequiresB2BRole(): void
    {
        $regularUsername = $this->generateUniqueUsername('regularuser');
        $this->createTestUser($regularUsername, 'password123', ['ROLE_USER']);
        
        $regularUserToken = $this->getAuthToken($regularUsername, 'password123');
        
        $this->client->request(Request::METHOD_GET, '/api/invoices/next-number', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $regularUserToken,
        ]);
        
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testGetNextNumberWithDefaultDate(): void
    {
        $adminToken = $this->getAuthToken();
        
        $this->client->request(Request::METHOD_GET, '/api/invoices/next-number', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
            'HTTP_ACCEPT' => 'application/json'
        ]);
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertJson($this->client->getResponse()->getContent());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        // Verify response structure
        $this->assertArrayHasKey('nextNumber', $responseData);
        $this->assertArrayHasKey('format', $responseData);
        $this->assertArrayHasKey('issueDate', $responseData);
        $this->assertArrayHasKey('sequenceNumber', $responseData);
        
        // Verify data types
        $this->assertIsString($responseData['nextNumber']);
        $this->assertIsString($responseData['format']);
        $this->assertIsString($responseData['issueDate']);
        $this->assertIsInt($responseData['sequenceNumber']);
        
        // Verify default format
        $this->assertEquals('FV/{year}/{month}/{number:4}', $responseData['format']);
        
        // Verify sequence number is positive
        $this->assertGreaterThanOrEqual(1, $responseData['sequenceNumber']);
        
        // Verify issue date is today
        $today = (new \DateTime())->format('Y-m-d');
        $this->assertEquals($today, $responseData['issueDate']);
        
        // Verify next number matches expected format
        $year = date('Y');
        $month = date('m');
        $this->assertMatchesRegularExpression('/^FV\/\d{4}\/\d{2}\/\d{4}$/', $responseData['nextNumber']);
        $this->assertStringContainsString("FV/$year/$month/", $responseData['nextNumber']);
    }

    public function testGetNextNumberWithSpecificDate(): void
    {
        $adminToken = $this->getAuthToken();
        $testDate = '2025-12-25';
        
        $this->client->request(
            Request::METHOD_GET,
            '/api/invoices/next-number?date=' . $testDate,
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
                'HTTP_ACCEPT' => 'application/json'
            ]
        );
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        // Verify issue date matches requested date
        $this->assertEquals($testDate, $responseData['issueDate']);
        
        // Verify next number contains the correct year and month
        $this->assertStringContainsString('FV/2025/12/', $responseData['nextNumber']);
    }

    public function testGetNextNumberWithDifferentMonths(): void
    {
        $adminToken = $this->getAuthToken();
        
        // Test January
        $this->client->request(
            Request::METHOD_GET,
            '/api/invoices/next-number?date=2025-01-15',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
                'HTTP_ACCEPT' => 'application/json'
            ]
        );
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseData1 = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('FV/2025/01/', $responseData1['nextNumber']);
        
        // Test December
        $this->client->request(
            Request::METHOD_GET,
            '/api/invoices/next-number?date=2025-12-15',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
                'HTTP_ACCEPT' => 'application/json'
            ]
        );
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseData2 = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('FV/2025/12/', $responseData2['nextNumber']);
        
        // Both should have sequence number 1 since they're in different months
        $this->assertEquals(1, $responseData1['sequenceNumber']);
        $this->assertEquals(1, $responseData2['sequenceNumber']);
    }

    public function testGetNextNumberWithInvalidDate(): void
    {
        $adminToken = $this->getAuthToken();
        
        $this->client->request(
            Request::METHOD_GET,
            '/api/invoices/next-number?date=invalid-date',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
                'HTTP_ACCEPT' => 'application/json'
            ]
        );
        
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('detail', $responseData);
        $this->assertStringContainsString('Invalid date format', $responseData['detail']);
    }

    public function testGetNextNumberWithInvalidDateFormat(): void
    {
        $adminToken = $this->getAuthToken();
        
        // Test various invalid formats
        $invalidDates = [
            '2025/12/25',  // Wrong separator
            '25-12-2025',  // Wrong order
            '2025-13-01',  // Invalid month
            '2025-12-32',  // Invalid day
            '12-25-2025',  // Wrong format
        ];
        
        foreach ($invalidDates as $invalidDate) {
            $this->client->request(
                Request::METHOD_GET,
                '/api/invoices/next-number?date=' . $invalidDate,
                [],
                [],
                [
                    'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
                    'HTTP_ACCEPT' => 'application/json'
                ]
            );
            
            $this->assertEquals(
                Response::HTTP_BAD_REQUEST,
                $this->client->getResponse()->getStatusCode(),
                "Failed for date: $invalidDate"
            );
        }
    }

    public function testGetNextNumberSequenceIncreases(): void
    {
        $adminToken = $this->getAuthToken();
        $testDate = '2025-06-15';
        
        // Get first number
        $this->client->request(
            Request::METHOD_GET,
            '/api/invoices/next-number?date=' . $testDate,
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
                'HTTP_ACCEPT' => 'application/json'
            ]
        );
        
        $response1 = json_decode($this->client->getResponse()->getContent(), true);
        $firstSequence = $response1['sequenceNumber'];
        
        // Note: The sequence number is based on existing invoices in the database for that month
        // Since we're in a clean test environment, the sequence should be 1
        $this->assertEquals(1, $firstSequence);
        
        // The next number endpoint only previews the next number
        // It doesn't actually increment anything until an invoice is created
        // So calling it again should return the same number
        $this->client->request(
            Request::METHOD_GET,
            '/api/invoices/next-number?date=' . $testDate,
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
                'HTTP_ACCEPT' => 'application/json'
            ]
        );
        
        $response2 = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($firstSequence, $response2['sequenceNumber']);
        $this->assertEquals($response1['nextNumber'], $response2['nextNumber']);
    }

    public function testGetNextNumberWithPaddingFormat(): void
    {
        $adminToken = $this->getAuthToken();
        
        $this->client->request(
            Request::METHOD_GET,
            '/api/invoices/next-number?date=2025-03-15',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
                'HTTP_ACCEPT' => 'application/json'
            ]
        );
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        // Default format has 4-digit padding for number
        // With sequence 1, it should be 0001
        $this->assertMatchesRegularExpression('/0001$/', $responseData['nextNumber']);
    }

    private function getAuthToken(string $username = 'admin', string $password = 'admin123!'): string
    {
        $this->client->request(Request::METHOD_POST, '/api/login_check', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'username' => $username,
            'password' => $password
        ]));
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('token', $response);
        
        return $response['token'];
    }
}
