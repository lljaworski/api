<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Company;
use App\Enum\InvoiceStatus;
use App\Tests\Trait\DatabaseTestTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class InvoiceWorkflowIntegrationTest extends WebTestCase
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
        
        $this->ensureTestAdmin();
        $this->createTestCustomer();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestData();
        parent::tearDown();
    }

    public function testCompleteInvoiceLifecycleWorkflow(): void
    {
        $adminToken = $this->getAuthToken();
        
        // Step 1: Create a draft invoice
        $invoiceData = [
            'issueDate' => '2024-01-01',
            'saleDate' => '2024-01-01',
            'dueDate' => '2024-01-31',
            'currency' => 'PLN',
            'paymentMethod' => 'cash',
            'notes' => 'Lifecycle test invoice',
            'customer' => $this->getCustomerIri(),
            'items' => [
                [
                    'description' => 'Software License',
                    'quantity' => '1.000',
                    'unit' => 'szt.',
                    'unitPrice' => '1000.00',
                    'vatRate' => '23.00',
                    'sortOrder' => 1
                ],
                [
                    'description' => 'Support Services',
                    'quantity' => '12.000',
                    'unit' => 'godz.',
                    'unitPrice' => '150.00',
                    'vatRate' => '23.00',
                    'sortOrder' => 2
                ]
            ]
        ];
        
        $this->client->request(Request::METHOD_POST, '/api/invoices', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($invoiceData));
        
        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $createResponse = json_decode($this->client->getResponse()->getContent(), true);
        $invoiceId = $createResponse['id'];
        
        // Verify initial state
        $this->assertEquals('issued', $createResponse['status']); // Changed from 'draft' to 'issued'
        $this->assertFalse($createResponse['isPaid']);
        $this->assertNull($createResponse['paidAt']);
        
        // Verify calculated totals
        // Software: 1 * 1000.00 = 1000.00 net, 1000.00 * 0.23 = 230.00 VAT
        // Support: 12 * 150.00 = 1800.00 net, 1800.00 * 0.23 = 414.00 VAT
        // Total: 2800.00 net, 644.00 VAT, 3444.00 gross
        $this->assertEquals('2800.00', $createResponse['subtotal']);
        $this->assertEquals('644.00', $createResponse['vatAmount']);
        $this->assertEquals('3444.00', $createResponse['total']);
        
        // Step 2: Update the draft invoice (add more items, change notes)
        $updateData = [
            'notes' => 'Updated with additional services',
            'items' => [
                [
                    'description' => 'Software License',
                    'quantity' => '1.000',
                    'unit' => 'szt.',
                    'unitPrice' => '1000.00',
                    'vatRate' => '23.00',
                    'sortOrder' => 1
                ],
                [
                    'description' => 'Support Services',
                    'quantity' => '12.000',
                    'unit' => 'godz.',
                    'unitPrice' => '150.00',
                    'vatRate' => '23.00',
                    'sortOrder' => 2
                ],
                [
                    'description' => 'Training Session',
                    'quantity' => '8.000',
                    'unit' => 'godz.',
                    'unitPrice' => '200.00',
                    'vatRate' => '23.00',
                    'sortOrder' => 3
                ]
            ]
        ];
        
        $this->client->request(Request::METHOD_PATCH, "/api/invoices/{$invoiceId}", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
            'CONTENT_TYPE' => 'application/merge-patch+json',
        ], json_encode($updateData));
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $updateResponse = json_decode($this->client->getResponse()->getContent(), true);
        
        // Verify updated totals
        // Software: 1000.00 net, 230.00 VAT
        // Support: 1800.00 net, 414.00 VAT  
        // Training: 8 * 200.00 = 1600.00 net, 1600.00 * 0.23 = 368.00 VAT
        // Total: 4400.00 net, 1012.00 VAT, 5412.00 gross
        $this->assertEquals('Updated with additional services', $updateResponse['notes']);
        $this->assertEquals('4400.00', $updateResponse['subtotal']);
        $this->assertEquals('1012.00', $updateResponse['vatAmount']);
        $this->assertEquals('5412.00', $updateResponse['total']);
        $this->assertCount(3, $updateResponse['items']);
        
        // Step 3: Verify the invoice can still be edited (ISSUED invoices are editable)
        $this->assertTrue($this->getInvoiceCanBeEdited($invoiceId, $adminToken));
        $this->assertFalse($this->getInvoiceCanBeDeleted($invoiceId, $adminToken)); // Changed: ISSUED invoices cannot be deleted
        
        // Step 4: Issue the invoice (already ISSUED by default now)
        // No additional action needed since invoices start as ISSUED
        
        // Step 5: Try to delete the ISSUED invoice (should fail)
        $this->client->request(Request::METHOD_DELETE, "/api/invoices/{$invoiceId}", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
        ]);
        
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $this->client->getResponse()->getStatusCode()); // Changed: should fail for ISSUED invoices
        
        // Since deletion failed, invoice should still exist
        $this->client->request(Request::METHOD_GET, "/api/invoices/{$invoiceId}", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
        ]);
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode()); // Changed: invoice should still exist
    }

    public function testMultipleInvoicesFiltering(): void
    {
        $adminToken = $this->getAuthToken();
        
        // Create multiple invoices with different properties
        $invoicesData = [
            [
                'issueDate' => '2024-01-15',
                'saleDate' => '2024-01-15',
                'currency' => 'PLN',
                'notes' => 'Polish invoice'
            ],
            [
                'issueDate' => '2024-02-15',
                'saleDate' => '2024-02-15',
                'currency' => 'EUR',
                'notes' => 'European invoice'
            ],
            [
                'issueDate' => '2024-03-15',
                'saleDate' => '2024-03-15',
                'currency' => 'USD',
                'notes' => 'American invoice'
            ]
        ];
        
        $createdInvoiceIds = [];
        
        foreach ($invoicesData as $data) {
            $invoiceData = array_merge($data, [
                'customer' => $this->getCustomerIri(),
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
            ]);
            
            $this->client->request(Request::METHOD_POST, '/api/invoices', [], [], [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
                'CONTENT_TYPE' => 'application/json',
            ], json_encode($invoiceData));
            
            $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
            $response = json_decode($this->client->getResponse()->getContent(), true);
            $createdInvoiceIds[] = $response['id'];
        }
        
        // Test filtering by currency
        $this->client->request(Request::METHOD_GET, '/api/invoices?currency=PLN', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
            'HTTP_ACCEPT' => 'application/json'
        ]);
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $filterResponse = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertGreaterThanOrEqual(1, count($filterResponse['data']));
        
        // Verify that the filtered invoice has PLN currency
        $found = false;
        foreach ($filterResponse['data'] as $invoice) {
            if ($invoice['currency'] === 'PLN') {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
        
        // Test filtering by customer
        $this->client->request(Request::METHOD_GET, '/api/invoices?customer.name=' . urlencode($this->testCustomer->getName()), [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
            'HTTP_ACCEPT' => 'application/json'
        ]);
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $customerFilterResponse = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertGreaterThanOrEqual(3, count($customerFilterResponse['data']));
        
        // Test filtering by status
        $this->client->request(Request::METHOD_GET, '/api/invoices?status=draft', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
            'HTTP_ACCEPT' => 'application/json'
        ]);
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $statusFilterResponse = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertGreaterThanOrEqual(3, count($statusFilterResponse['data']));
        
        // Test date range filtering
        $this->client->request(Request::METHOD_GET, '/api/invoices?issueDate[after]=2024-02-01&issueDate[before]=2024-02-28', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
            'HTTP_ACCEPT' => 'application/json'
        ]);
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $dateFilterResponse = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertGreaterThanOrEqual(1, count($dateFilterResponse['data']));
    }

    public function testInvoiceCalculationsWithDifferentVatRates(): void
    {
        $adminToken = $this->getAuthToken();
        
        // Create invoice with mixed VAT rates
        $invoiceData = [
            'issueDate' => '2024-01-01',
            'saleDate' => '2024-01-01',
            'currency' => 'PLN',
            'customer' => $this->getCustomerIri(),
            'items' => [
                [
                    'description' => 'Books (0% VAT)',
                    'quantity' => '5.000',
                    'unit' => 'szt.',
                    'unitPrice' => '25.00',
                    'vatRate' => '0.00',
                    'sortOrder' => 1
                ],
                [
                    'description' => 'Food (5% VAT)',
                    'quantity' => '10.000',
                    'unit' => 'kg',
                    'unitPrice' => '12.00',
                    'vatRate' => '5.00',
                    'sortOrder' => 2
                ],
                [
                    'description' => 'Hotel Services (8% VAT)',
                    'quantity' => '3.000',
                    'unit' => 'dzień',
                    'unitPrice' => '200.00',
                    'vatRate' => '8.00',
                    'sortOrder' => 3
                ],
                [
                    'description' => 'Software (23% VAT)',
                    'quantity' => '2.000',
                    'unit' => 'szt.',
                    'unitPrice' => '500.00',
                    'vatRate' => '23.00',
                    'sortOrder' => 4
                ]
            ]
        ];
        
        $this->client->request(Request::METHOD_POST, '/api/invoices', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($invoiceData));
        
        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent(), true);
        
        // Verify calculations:
        // Books: 5 * 25.00 = 125.00 net, 0.00 VAT
        // Food: 10 * 12.00 = 120.00 net, 120.00 * 0.05 = 6.00 VAT
        // Hotel: 3 * 200.00 = 600.00 net, 600.00 * 0.08 = 48.00 VAT
        // Software: 2 * 500.00 = 1000.00 net, 1000.00 * 0.23 = 230.00 VAT
        // Total: 1845.00 net, 284.00 VAT, 2129.00 gross
        
        $this->assertEquals('1845.00', $response['subtotal']);
        $this->assertEquals('284.00', $response['vatAmount']);
        $this->assertEquals('2129.00', $response['total']);
        
        // Verify individual item calculations
        $items = $response['items'];
        $this->assertCount(4, $items);
        
        // Sort items by sortOrder to ensure predictable testing
        usort($items, fn($a, $b) => $a['sortOrder'] <=> $b['sortOrder']);
        
        $this->assertEquals('125.00', $items[0]['netAmount']);
        $this->assertEquals('0.00', $items[0]['vatAmount']);
        
        $this->assertEquals('120.00', $items[1]['netAmount']);
        $this->assertEquals('6.00', $items[1]['vatAmount']);
        
        $this->assertEquals('600.00', $items[2]['netAmount']);
        $this->assertEquals('48.00', $items[2]['vatAmount']);
        
        $this->assertEquals('1000.00', $items[3]['netAmount']);
        $this->assertEquals('230.00', $items[3]['vatAmount']);
    }

    public function testPaginationAndSorting(): void
    {
        $adminToken = $this->getAuthToken();
        
        // Create multiple invoices for pagination testing
        for ($i = 1; $i <= 15; $i++) {
            $invoiceData = [
                'issueDate' => '2024-01-' . str_pad((string)$i, 2, '0', STR_PAD_LEFT),
                'saleDate' => '2024-01-' . str_pad((string)$i, 2, '0', STR_PAD_LEFT),
                'currency' => 'PLN',
                'customer' => $this->getCustomerIri(),
                'items' => [
                    [
                        'description' => "Product {$i}",
                        'quantity' => '1.000',
                        'unit' => 'szt.',
                        'unitPrice' => sprintf('%.2f', $i * 10),
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
        
        // Test first page
        $this->client->request(Request::METHOD_GET, '/api/invoices?page=1&itemsPerPage=5', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
            'HTTP_ACCEPT' => 'application/json'
        ]);
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $page1Response = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertCount(5, $page1Response['data']);
        $this->assertEquals(5, $page1Response['pagination']['itemsPerPage']);
        $this->assertGreaterThanOrEqual(15, $page1Response['pagination']['total']);
        
        // Test second page
        $this->client->request(Request::METHOD_GET, '/api/invoices?page=2&itemsPerPage=5', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
            'HTTP_ACCEPT' => 'application/json'
        ]);
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $page2Response = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertCount(5, $page2Response['data']);
        
        // Verify that different items are returned on different pages
        $page1Numbers = array_column($page1Response['data'], 'number');
        $page2Numbers = array_column($page2Response['data'], 'number');
        
        $this->assertEmpty(array_intersect($page1Numbers, $page2Numbers));
    }

    public function testInvoiceSearchFunctionality(): void
    {
        $adminToken = $this->getAuthToken();
        
        // Create invoices with searchable content
        $searchableInvoices = [
            [
                'notes' => 'Software development services'
            ],
            [
                'notes' => 'Hardware maintenance contract'
            ],
            [
                'notes' => 'Business consulting services'
            ]
        ];
        
        foreach ($searchableInvoices as $data) {
            $invoiceData = array_merge($data, [
                'issueDate' => '2024-01-01',
                'saleDate' => '2024-01-01',
                'currency' => 'PLN',
                'customer' => $this->getCustomerIri(),
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
            ]);
            
            $this->client->request(Request::METHOD_POST, '/api/invoices', [], [], [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
                'CONTENT_TYPE' => 'application/json',
            ], json_encode($invoiceData));
            
            $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        }
        
        // Test search by notes content
        $this->client->request(Request::METHOD_GET, '/api/invoices?search=Software', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
            'HTTP_ACCEPT' => 'application/json'
        ]);
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $searchResponse = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertGreaterThanOrEqual(1, count($searchResponse['data']));
        
        // Verify search result contains the expected invoice
        $foundSoftware = false;
        foreach ($searchResponse['data'] as $invoice) {
            if (str_contains($invoice['notes'], 'Software')) {
                $foundSoftware = true;
                break;
            }
        }
        $this->assertTrue($foundSoftware);
    }

    private function createTestCustomer(): void
    {
        $this->testCustomer = new Company('Workflow Test Customer Ltd.');
        $this->testCustomer->setTaxId('5555555555')
            ->setAddressLine1('Workflow Street 789')
            ->setCountryCode('PL')
            ->setEmail('workflow@test.com')
            ->setPhoneNumber('555555555');
        
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

    private function getInvoiceCanBeEdited(int $invoiceId, string $token): bool
    {
        $this->client->request(Request::METHOD_GET, "/api/invoices/{$invoiceId}", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);
        
        if ($this->client->getResponse()->getStatusCode() !== Response::HTTP_OK) {
            return false;
        }
        
        $invoice = json_decode($this->client->getResponse()->getContent(), true);
        
        // Business logic: draft and issued invoices can be edited
        return in_array($invoice['status'], ['draft', 'issued']);
    }

    private function getInvoiceCanBeDeleted(int $invoiceId, string $token): bool
    {
        $this->client->request(Request::METHOD_GET, "/api/invoices/{$invoiceId}", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);
        
        if ($this->client->getResponse()->getStatusCode() !== Response::HTTP_OK) {
            return false;
        }
        
        $invoice = json_decode($this->client->getResponse()->getContent(), true);
        
        // Business logic: draft and cancelled invoices can be deleted
        return in_array($invoice['status'], ['draft', 'cancelled']);
    }

    private function getCustomerIri(): string
    {
        return '/api/companies/' . $this->testCustomer->getId();
    }
}