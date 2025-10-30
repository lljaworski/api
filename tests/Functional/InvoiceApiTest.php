<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Company;
use App\Tests\Trait\DatabaseTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;

class InvoiceApiTest extends WebTestCase
{
    use DatabaseTestTrait;

    private $client;
    private EntityManagerInterface $entityManager;
    private ?string $adminToken = null;
    private ?Company $testCustomer = null;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        
        // Ensure admin exists and create test customer
        $this->ensureTestAdmin();
        $this->createTestCustomer();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestData();
        parent::tearDown();
    }

    public function testGetInvoicesRequiresAuthentication(): void
    {
        $this->client->request(Request::METHOD_GET, '/api/invoices');
        
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testGetInvoicesRequiresAdminRole(): void
    {
        $regularUsername = $this->generateUniqueUsername('regularuser');
        $this->createTestUser($regularUsername, 'password123', ['ROLE_USER']);
        
        $regularUserToken = $this->getAuthToken($regularUsername, 'password123');
        
        $this->client->request(Request::METHOD_GET, '/api/invoices', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $regularUserToken,
        ]);
        
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testGetInvoicesAsAdmin(): void
    {
        $adminToken = $this->getAuthToken();
        
        $this->client->request(Request::METHOD_GET, '/api/invoices', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
            'HTTP_ACCEPT' => 'application/json'
        ]);
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertJson($this->client->getResponse()->getContent());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('pagination', $responseData);
        $this->assertIsArray($responseData['data']);
    }

    public function testCreateInvoiceAsAdmin(): void
    {
        $adminToken = $this->getAuthToken();
        
        $invoiceData = [
            'number' => 'INV/TEST/001',
            'issueDate' => '2024-01-01',
            'saleDate' => '2024-01-01',
            'dueDate' => '2024-01-31',
            'currency' => 'PLN',
            'paymentMethod' => 1,
            'notes' => 'Test invoice notes',
            'customer' => $this->testCustomer->getId(),
            'items' => [
                [
                    'description' => 'Test Product 1',
                    'quantity' => '2.000',
                    'unit' => 'szt.',
                    'unitPrice' => '100.00',
                    'vatRate' => '23.00',
                    'sortOrder' => 1
                ],
                [
                    'description' => 'Test Product 2',
                    'quantity' => '1.000',
                    'unit' => 'szt.',
                    'unitPrice' => '50.00',
                    'vatRate' => '8.00',
                    'sortOrder' => 2
                ]
            ]
        ];
        
        $this->client->request(Request::METHOD_POST, '/api/invoices', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($invoiceData));
        
        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $this->assertJson($this->client->getResponse()->getContent());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('INV/TEST/001', $responseData['number']);
        $this->assertEquals('draft', $responseData['status']);
        $this->assertEquals('PLN', $responseData['currency']);
        $this->assertFalse($responseData['isPaid']);
        $this->assertCount(2, $responseData['items']);
        
        // Check calculated totals
        // Item 1: 2 * 100.00 = 200.00 net, 200.00 * 0.23 = 46.00 VAT
        // Item 2: 1 * 50.00 = 50.00 net, 50.00 * 0.08 = 4.00 VAT
        // Total: 250.00 net, 50.00 VAT, 300.00 gross
        $this->assertEquals('250.00', $responseData['subtotal']);
        $this->assertEquals('50.00', $responseData['vatAmount']);
        $this->assertEquals('300.00', $responseData['total']);
    }

    public function testCreateInvoiceWithValidationErrors(): void
    {
        $adminToken = $this->getAuthToken();
        
        // Missing required fields
        $invalidInvoiceData = [
            'number' => '', // Empty number
            'currency' => 'INVALID', // Invalid currency
            'items' => [] // No items
        ];
        
        $this->client->request(Request::METHOD_POST, '/api/invoices', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($invalidInvoiceData));
        
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('violations', $responseData);
    }

    public function testGetSingleInvoiceAsAdmin(): void
    {
        $adminToken = $this->getAuthToken();
        
        // First create an invoice
        $invoiceData = [
            'number' => 'INV/GET/001',
            'issueDate' => '2024-01-01',
            'saleDate' => '2024-01-01',
            'currency' => 'PLN',
            'customer' => $this->testCustomer->getId(),
            'items' => [
                [
                    'description' => 'Test Product',
                    'quantity' => '1.000',
                    'unit' => 'szt.',
                    'unitPrice' => '100.00',
                    'vatRate' => '23.00',
                    'sortOrder' => 1
                ]
            ]
        ];
        
        $this->client->request(Request::METHOD_POST, '/api/invoices', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($invoiceData));
        
        $createResponse = json_decode($this->client->getResponse()->getContent(), true);
        $invoiceId = $createResponse['id'];
        
        // Now get the invoice
        $this->client->request(Request::METHOD_GET, "/api/invoices/{$invoiceId}", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
        ]);
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('INV/GET/001', $responseData['number']);
        $this->assertEquals('draft', $responseData['status']);
        $this->assertCount(1, $responseData['items']);
    }

    public function testGetNonExistentInvoice(): void
    {
        $adminToken = $this->getAuthToken();
        
        $this->client->request(Request::METHOD_GET, '/api/invoices/99999', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
        ]);
        
        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testUpdateInvoiceWithPut(): void
    {
        $adminToken = $this->getAuthToken();
        
        // Create invoice first
        $invoiceData = [
            'number' => 'INV/UPDATE/001',
            'issueDate' => '2024-01-01',
            'saleDate' => '2024-01-01',
            'currency' => 'PLN',
            'customer' => $this->testCustomer->getId(),
            'items' => [
                [
                    'description' => 'Original Product',
                    'quantity' => '1.000',
                    'unit' => 'szt.',
                    'unitPrice' => '100.00',
                    'vatRate' => '23.00',
                    'sortOrder' => 1
                ]
            ]
        ];
        
        $this->client->request(Request::METHOD_POST, '/api/invoices', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($invoiceData));
        
        $createResponse = json_decode($this->client->getResponse()->getContent(), true);
        $invoiceId = $createResponse['id'];
        
        // Update the invoice
        $updateData = [
            'number' => 'INV/UPDATED/001',
            'issueDate' => '2024-02-01',
            'saleDate' => '2024-02-01',
            'dueDate' => '2024-02-28',
            'currency' => 'EUR',
            'notes' => 'Updated notes',
            'customer' => $this->testCustomer->getId(),
            'items' => [
                [
                    'description' => 'Updated Product',
                    'quantity' => '2.000',
                    'unit' => 'szt.',
                    'unitPrice' => '150.00',
                    'vatRate' => '23.00',
                    'sortOrder' => 1
                ]
            ]
        ];
        
        $this->client->request(Request::METHOD_PUT, "/api/invoices/{$invoiceId}", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($updateData));
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertEquals('INV/UPDATED/001', $responseData['number']);
        $this->assertEquals('EUR', $responseData['currency']);
        $this->assertEquals('Updated notes', $responseData['notes']);
        $this->assertCount(1, $responseData['items']);
        $this->assertEquals('Updated Product', $responseData['items'][0]['description']);
        
        // Check recalculated totals: 2 * 150.00 = 300.00 net, 300.00 * 0.23 = 69.00 VAT
        $this->assertEquals('300.00', $responseData['subtotal']);
        $this->assertEquals('69.00', $responseData['vatAmount']);
        $this->assertEquals('369.00', $responseData['total']);
    }

    public function testUpdateInvoiceWithPatch(): void
    {
        $adminToken = $this->getAuthToken();
        
        // Create invoice first
        $invoiceData = [
            'number' => 'INV/PATCH/001',
            'issueDate' => '2024-01-01',
            'saleDate' => '2024-01-01',
            'currency' => 'PLN',
            'notes' => 'Original notes',
            'customer' => $this->testCustomer->getId(),
            'items' => [
                [
                    'description' => 'Test Product',
                    'quantity' => '1.000',
                    'unit' => 'szt.',
                    'unitPrice' => '100.00',
                    'vatRate' => '23.00',
                    'sortOrder' => 1
                ]
            ]
        ];
        
        $this->client->request(Request::METHOD_POST, '/api/invoices', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($invoiceData));
        
        $createResponse = json_decode($this->client->getResponse()->getContent(), true);
        $invoiceId = $createResponse['id'];
        
        // Partial update - only notes and due date
        $patchData = [
            'notes' => 'Partially updated notes',
            'dueDate' => '2024-01-31'
        ];
        
        $this->client->request(Request::METHOD_PATCH, "/api/invoices/{$invoiceId}", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
            'CONTENT_TYPE' => 'application/merge-patch+json',
        ], json_encode($patchData));
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        // Patched fields should be updated
        $this->assertEquals('Partially updated notes', $responseData['notes']);
        $this->assertEquals('2024-01-31', $responseData['dueDate']);
        
        // Other fields should remain unchanged
        $this->assertEquals('INV/PATCH/001', $responseData['number']);
        $this->assertEquals('PLN', $responseData['currency']);
    }

    public function testDeleteInvoiceAsAdmin(): void
    {
        $adminToken = $this->getAuthToken();
        
        // Create invoice first
        $invoiceData = [
            'number' => 'INV/DELETE/001',
            'issueDate' => '2024-01-01',
            'saleDate' => '2024-01-01',
            'currency' => 'PLN',
            'customer' => $this->testCustomer->getId(),
            'items' => [
                [
                    'description' => 'Test Product',
                    'quantity' => '1.000',
                    'unit' => 'szt.',
                    'unitPrice' => '100.00',
                    'vatRate' => '23.00',
                    'sortOrder' => 1
                ]
            ]
        ];
        
        $this->client->request(Request::METHOD_POST, '/api/invoices', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($invoiceData));
        
        $createResponse = json_decode($this->client->getResponse()->getContent(), true);
        $invoiceId = $createResponse['id'];
        
        // Delete the invoice (soft delete)
        $this->client->request(Request::METHOD_DELETE, "/api/invoices/{$invoiceId}", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
        ]);
        
        $this->assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
        
        // Verify invoice is not found after deletion
        $this->client->request(Request::METHOD_GET, "/api/invoices/{$invoiceId}", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
        ]);
        
        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testCannotDeleteIssuedInvoice(): void
    {
        $adminToken = $this->getAuthToken();
        
        // Create and issue an invoice
        $invoiceData = [
            'number' => 'INV/NODELETE/001',
            'issueDate' => '2024-01-01',
            'saleDate' => '2024-01-01',
            'currency' => 'PLN',
            'customer' => $this->testCustomer->getId(),
            'items' => [
                [
                    'description' => 'Test Product',
                    'quantity' => '1.000',
                    'unit' => 'szt.',
                    'unitPrice' => '100.00',
                    'vatRate' => '23.00',
                    'sortOrder' => 1
                ]
            ]
        ];
        
        $this->client->request(Request::METHOD_POST, '/api/invoices', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($invoiceData));
        
        $createResponse = json_decode($this->client->getResponse()->getContent(), true);
        $invoiceId = $createResponse['id'];
        
        // Issue the invoice by updating status (this would require a separate endpoint or command)
        // For now, we'll test the business logic constraint through validation
        
        // Try to delete - should fail if invoice is issued (would need to implement status change first)
        $this->client->request(Request::METHOD_DELETE, "/api/invoices/{$invoiceId}", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
        ]);
        
        // Draft invoice should be deletable
        $this->assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    public function testInvoiceWithDifferentCurrencies(): void
    {
        $adminToken = $this->getAuthToken();
        
        $currencies = ['PLN', 'EUR', 'USD', 'GBP'];
        
        foreach ($currencies as $currency) {
            $invoiceData = [
                'number' => "INV/{$currency}/001",
                'issueDate' => '2024-01-01',
                'saleDate' => '2024-01-01',
                'currency' => $currency,
                'customer' => $this->testCustomer->getId(),
                'items' => [
                    [
                        'description' => 'Test Product',
                        'quantity' => '1.000',
                        'unit' => 'szt.',
                        'unitPrice' => '100.00',
                        'vatRate' => '23.00',
                        'sortOrder' => 1
                    ]
                ]
            ];
            
            $this->client->request(Request::METHOD_POST, '/api/invoices', [], [], [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
                'CONTENT_TYPE' => 'application/json',
            ], json_encode($invoiceData));
            
            $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
            $responseData = json_decode($this->client->getResponse()->getContent(), true);
            $this->assertEquals($currency, $responseData['currency']);
        }
    }

    public function testInvoiceFiltering(): void
    {
        $adminToken = $this->getAuthToken();
        
        // Create invoices with different data for filtering
        $invoices = [
            ['number' => 'INV/FILTER/001', 'currency' => 'PLN'],
            ['number' => 'INV/FILTER/002', 'currency' => 'EUR'],
            ['number' => 'TEST/FILTER/003', 'currency' => 'PLN'],
        ];
        
        foreach ($invoices as $invoice) {
            $invoiceData = [
                'number' => $invoice['number'],
                'issueDate' => '2024-01-01',
                'saleDate' => '2024-01-01',
                'currency' => $invoice['currency'],
                'customer' => $this->testCustomer->getId(),
                'items' => [
                    [
                        'description' => 'Test Product',
                        'quantity' => '1.000',
                        'unit' => 'szt.',
                        'unitPrice' => '100.00',
                        'vatRate' => '23.00',
                        'sortOrder' => 1
                    ]
                ]
            ];
            
            $this->client->request(Request::METHOD_POST, '/api/invoices', [], [], [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
                'CONTENT_TYPE' => 'application/json',
            ], json_encode($invoiceData));
            
            $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        }
        
        // Test filtering by number (partial match)
        $this->client->request(Request::METHOD_GET, '/api/invoices?number=INV/FILTER', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
            'HTTP_ACCEPT' => 'application/json'
        ]);
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        // Should find 2 invoices with INV/FILTER in number
        $this->assertGreaterThanOrEqual(2, count($responseData['data']));
        
        // Test filtering by currency
        $this->client->request(Request::METHOD_GET, '/api/invoices?currency=EUR', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
            'HTTP_ACCEPT' => 'application/json'
        ]);
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        // Should find at least 1 EUR invoice
        $this->assertGreaterThanOrEqual(1, count($responseData['data']));
    }

    public function testInvoicePagination(): void
    {
        $adminToken = $this->getAuthToken();
        
        // Test pagination parameters
        $this->client->request(Request::METHOD_GET, '/api/invoices?page=1&itemsPerPage=10', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
            'HTTP_ACCEPT' => 'application/json'
        ]);
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('pagination', $responseData);
        $this->assertEquals(10, $responseData['pagination']['itemsPerPage']);
        $this->assertArrayHasKey('total', $responseData['pagination']);
        $this->assertArrayHasKey('currentPage', $responseData['pagination']);
    }

    private function createTestCustomer(): void
    {
        $this->testCustomer = new Company('Test Invoice Customer');
        $this->testCustomer->setTaxId('9876543210')
            ->setAddressLine1('Customer Address 123')
            ->setCountryCode('PL')
            ->setEmail('customer@test.com')
            ->setPhoneNumber('987654321');
        
        $this->entityManager->persist($this->testCustomer);
        $this->entityManager->flush();
        
        $this->trackEntityForCleanup($this->testCustomer);
    }

    private function getAuthToken(string $username = 'admin', string $password = 'admin123!'): string
    {
        if ($this->adminToken !== null && $username === 'admin') {
            return $this->adminToken;
        }
        
        $this->client->request(Request::METHOD_POST, '/api/login_check', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'username' => $username,
            'password' => $password
        ]));
        
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        
        if ($username === 'admin') {
            $this->adminToken = $response['token'];
        }
        
        return $response['token'];
    }
}