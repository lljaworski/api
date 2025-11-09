<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Application\Command\Invoice\CreateInvoiceCommand;
use App\Application\Command\Invoice\DeleteInvoiceCommand;
use App\Application\Command\Invoice\UpdateInvoiceCommand;
use App\Application\Query\Invoice\GetInvoiceQuery;
use App\Application\Query\Invoice\GetInvoicesQuery;
use App\Entity\Company;
use App\Enum\InvoiceStatus;
use App\Enum\PaymentMethodEnum;
use App\Tests\Trait\DatabaseTestTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class InvoiceCqrsIntegrationTest extends KernelTestCase
{
    use DatabaseTestTrait;

    private MessageBusInterface $commandBus;
    private MessageBusInterface $queryBus;
    private EntityManagerInterface $entityManager;
    private Company $testCustomer;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        
        $container = static::getContainer();
        $this->commandBus = $container->get('command.bus');
        $this->queryBus = $container->get('query.bus');
        $this->entityManager = $container->get(EntityManagerInterface::class);
        
        $this->ensureTestAdmin();
        $this->createTestCustomer();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestData();
        parent::tearDown();
    }

    public function testInvoiceCqrsWorkflowCreateReadUpdateDelete(): void
    {
        // Test Create Command
        $createCommand = new CreateInvoiceCommand(
            customerId: $this->testCustomer->getId(),
            issueDate: new \DateTime('2024-01-01'),
            saleDate: new \DateTime('2024-01-01'),
            dueDate: new \DateTime('2024-01-31'),
            currency: 'PLN',
            paymentMethod: PaymentMethodEnum::WIRE_TRANSFERS,
            notes: 'Test invoice notes',
            items: [
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
        );

        $envelope = $this->commandBus->dispatch($createCommand);
        $createdInvoice = $envelope->last(HandledStamp::class)->getResult();

        $this->assertNotNull($createdInvoice);
        $this->assertEquals('PLN', $createdInvoice->getCurrency());
        $this->assertEquals(InvoiceStatus::DRAFT, $createdInvoice->getStatus());
        $this->assertFalse($createdInvoice->isPaid());
        $this->assertEquals('Test invoice notes', $createdInvoice->getNotes());
        $this->assertCount(2, $createdInvoice->getItems());
        
        // Check calculated totals
        // Item 1: 2 * 100.00 = 200.00 net, 200.00 * 0.23 = 46.00 VAT
        // Item 2: 1 * 50.00 = 50.00 net, 50.00 * 0.08 = 4.00 VAT
        // Total: 250.00 net, 50.00 VAT, 300.00 gross
        $this->assertEquals('250.00', $createdInvoice->getSubtotal());
        $this->assertEquals('50.00', $createdInvoice->getVatAmount());
        $this->assertEquals('300.00', $createdInvoice->getTotal());

        $invoiceId = $createdInvoice->getId();

        // Test Get Invoice Query
        $getInvoiceQuery = new GetInvoiceQuery($invoiceId);
        $envelope = $this->queryBus->dispatch($getInvoiceQuery);
        $invoiceDto = $envelope->last(HandledStamp::class)->getResult();

        $this->assertNotNull($invoiceDto);
        $this->assertEquals($invoiceId, $invoiceDto->id);
        $this->assertEquals('PLN', $invoiceDto->currency);
        $this->assertEquals(InvoiceStatus::DRAFT, $invoiceDto->status);
        $this->assertFalse($invoiceDto->isPaid);
        $this->assertEquals('Test invoice notes', $invoiceDto->notes);
        $this->assertCount(2, $invoiceDto->items);
        $this->assertEquals('250.00', $invoiceDto->subtotal);
        $this->assertEquals('50.00', $invoiceDto->vatAmount);
        $this->assertEquals('300.00', $invoiceDto->total);

        // Test Get Invoices Query
        $getInvoicesQuery = new GetInvoicesQuery(page: 1, itemsPerPage: 10);
        $envelope = $this->queryBus->dispatch($getInvoicesQuery);
        $invoicesCollection = $envelope->last(HandledStamp::class)->getResult();

        $this->assertNotNull($invoicesCollection);
        $this->assertGreaterThanOrEqual(1, $invoicesCollection->total);
        $this->assertNotEmpty($invoicesCollection->invoices);

        // Test Update Command
        $updateCommand = new UpdateInvoiceCommand(
            id: $invoiceId,
            customerId: $this->testCustomer->getId(),
            currency: 'EUR',
            notes: 'Updated invoice notes',
            items: [
                [
                    'description' => 'Updated Product',
                    'quantity' => '3.000',
                    'unit' => 'szt.',
                    'unitPrice' => '75.00',
                    'vatRate' => '23.00',
                    'sortOrder' => 1
                ]
            ]
        );

        $envelope = $this->commandBus->dispatch($updateCommand);
        $updatedInvoice = $envelope->last(HandledStamp::class)->getResult();

        $this->assertNotNull($updatedInvoice);
        $this->assertEquals($invoiceId, $updatedInvoice->getId());
        $this->assertEquals('EUR', $updatedInvoice->getCurrency());
        $this->assertEquals('Updated invoice notes', $updatedInvoice->getNotes());
        $this->assertCount(1, $updatedInvoice->getItems());
        
        // Check recalculated totals: 3 * 75.00 = 225.00 net, 225.00 * 0.23 = 51.75 VAT
        $this->assertEquals('225.00', $updatedInvoice->getSubtotal());
        $this->assertEquals('51.75', $updatedInvoice->getVatAmount());
        $this->assertEquals('276.75', $updatedInvoice->getTotal());

        // Verify update with query
        $getInvoiceQuery = new GetInvoiceQuery($invoiceId);
        $envelope = $this->queryBus->dispatch($getInvoiceQuery);
        $updatedInvoiceDto = $envelope->last(HandledStamp::class)->getResult();

        $this->assertEquals('EUR', $updatedInvoiceDto->currency);
        $this->assertEquals('Updated invoice notes', $updatedInvoiceDto->notes);
        $this->assertCount(1, $updatedInvoiceDto->items);

        // Test Delete Command (soft delete)
        $deleteCommand = new DeleteInvoiceCommand($invoiceId);
        $this->commandBus->dispatch($deleteCommand);

        // Verify invoice is soft deleted
        $getInvoiceQuery = new GetInvoiceQuery($invoiceId);
        $envelope = $this->queryBus->dispatch($getInvoiceQuery);
        $deletedInvoiceDto = $envelope->last(HandledStamp::class)->getResult();

        $this->assertNull($deletedInvoiceDto); // Should return null for soft-deleted invoices
    }

    public function testGetInvoicesQueryWithFiltering(): void
    {
        // Create test invoices with different properties
        $createCommand1 = new CreateInvoiceCommand(
            customerId: $this->testCustomer->getId(),
            issueDate: new \DateTime('2024-01-01'),
            saleDate: new \DateTime('2024-01-01'),
            currency: 'PLN',
            items: [
                [
                    'description' => 'PLN Product',
                    'quantity' => '1.000',
                    'unit' => 'szt.',
                    'unitPrice' => '100.00',
                    'vatRate' => '23.00',
                    'sortOrder' => 1
                ]
            ]
        );

        $createCommand2 = new CreateInvoiceCommand(
            customerId: $this->testCustomer->getId(),
            issueDate: new \DateTime('2024-02-01'),
            saleDate: new \DateTime('2024-02-01'),
            currency: 'EUR',
            items: [
                [
                    'description' => 'EUR Product',
                    'quantity' => '1.000',
                    'unit' => 'szt.',
                    'unitPrice' => '100.00',
                    'vatRate' => '23.00',
                    'sortOrder' => 1
                ]
            ]
        );

        $envelope1 = $this->commandBus->dispatch($createCommand1);
        $invoice1 = $envelope1->last(HandledStamp::class)->getResult();
        
        $envelope2 = $this->commandBus->dispatch($createCommand2);
        $invoice2 = $envelope2->last(HandledStamp::class)->getResult();

        // Test filtering by customer
        $customerFilterQuery = new GetInvoicesQuery(
            page: 1,
            itemsPerPage: 10,
            customerId: $this->testCustomer->getId()
        );
        $envelope = $this->queryBus->dispatch($customerFilterQuery);
        $customerResults = $envelope->last(HandledStamp::class)->getResult();

        $this->assertNotNull($customerResults);
        $this->assertGreaterThanOrEqual(2, $customerResults->total);

        // Test filtering by status
        $statusFilterQuery = new GetInvoicesQuery(
            page: 1,
            itemsPerPage: 10,
            status: InvoiceStatus::DRAFT
        );
        $envelope = $this->queryBus->dispatch($statusFilterQuery);
        $statusResults = $envelope->last(HandledStamp::class)->getResult();

        $this->assertNotNull($statusResults);
        $this->assertGreaterThanOrEqual(2, $statusResults->total);

        // Test filtering by date range
        $dateFilterQuery = new GetInvoicesQuery(
            page: 1,
            itemsPerPage: 10,
            issueDateFrom: new \DateTime('2024-01-01'),
            issueDateTo: new \DateTime('2024-01-31')
        );
        $envelope = $this->queryBus->dispatch($dateFilterQuery);
        $dateResults = $envelope->last(HandledStamp::class)->getResult();

        $this->assertNotNull($dateResults);
        $this->assertGreaterThanOrEqual(1, $dateResults->total);

        // Test search functionality
        $searchQuery = new GetInvoicesQuery(
            page: 1,
            itemsPerPage: 10,
            search: 'PLN Product'
        );
        $envelope = $this->queryBus->dispatch($searchQuery);
        $searchResults = $envelope->last(HandledStamp::class)->getResult();

        $this->assertNotNull($searchResults);
        $this->assertGreaterThanOrEqual(1, $searchResults->total);
    }

    public function testPartialUpdateCommand(): void
    {
        // Create invoice first
        $createCommand = new CreateInvoiceCommand(
            customerId: $this->testCustomer->getId(),
            issueDate: new \DateTime('2024-01-01'),
            saleDate: new \DateTime('2024-01-01'),
            currency: 'PLN',
            notes: 'Original notes',
            items: [
                [
                    'description' => 'Original Product',
                    'quantity' => '1.000',
                    'unit' => 'szt.',
                    'unitPrice' => '100.00',
                    'vatRate' => '23.00',
                    'sortOrder' => 1
                ]
            ]
        );

        $envelope = $this->commandBus->dispatch($createCommand);
        $createdInvoice = $envelope->last(HandledStamp::class)->getResult();
        $invoiceId = $createdInvoice->getId();

        // Test partial update - only notes and due date
        $partialUpdateCommand = new UpdateInvoiceCommand(
            id: $invoiceId,
            notes: 'Partially updated notes',
            dueDate: new \DateTime('2024-01-31')
            // Other fields are null, so they shouldn't be updated
        );

        $envelope = $this->commandBus->dispatch($partialUpdateCommand);
        $updatedInvoice = $envelope->last(HandledStamp::class)->getResult();

        $this->assertNotNull($updatedInvoice);
        $this->assertEquals('Partially updated notes', $updatedInvoice->getNotes());
        $this->assertEquals('2024-01-31', $updatedInvoice->getDueDate()->format('Y-m-d'));
        
        // Original values should remain unchanged
        $this->assertEquals('PLN', $updatedInvoice->getCurrency());
        $this->assertEquals('2024-01-01', $updatedInvoice->getIssueDate()->format('Y-m-d'));
        $this->assertCount(1, $updatedInvoice->getItems());
    }

    public function testCreateInvoiceWithMinimalData(): void
    {
        $createCommand = new CreateInvoiceCommand(
            customerId: $this->testCustomer->getId(),
            issueDate: new \DateTime('2024-01-01'),
            saleDate: new \DateTime('2024-01-01'),
            items: [
                [
                    'description' => 'Minimal Product',
                    'quantity' => '1.000',
                    'unit' => 'szt.',
                    'unitPrice' => '100.00',
                    'vatRate' => '23.00',
                    'sortOrder' => 1
                ]
            ]
        );

        $envelope = $this->commandBus->dispatch($createCommand);
        $createdInvoice = $envelope->last(HandledStamp::class)->getResult();

        $this->assertNotNull($createdInvoice);
        $this->assertEquals('PLN', $createdInvoice->getCurrency()); // Default currency
        $this->assertEquals(InvoiceStatus::DRAFT, $createdInvoice->getStatus());
        $this->assertNull($createdInvoice->getDueDate());
        $this->assertNull($createdInvoice->getPaymentMethod());
        $this->assertNull($createdInvoice->getNotes());
    }

    public function testQueryNonExistentInvoice(): void
    {
        $getInvoiceQuery = new GetInvoiceQuery(99999); // Non-existent ID
        $envelope = $this->queryBus->dispatch($getInvoiceQuery);
        $invoiceDto = $envelope->last(HandledStamp::class)->getResult();

        $this->assertNull($invoiceDto);
    }

    public function testInvoiceItemsHandling(): void
    {
        // Create invoice with multiple items
        $createCommand = new CreateInvoiceCommand(
            customerId: $this->testCustomer->getId(),
            issueDate: new \DateTime('2024-01-01'),
            saleDate: new \DateTime('2024-01-01'),
            items: [
                [
                    'description' => 'Product A',
                    'quantity' => '2.000',
                    'unit' => 'szt.',
                    'unitPrice' => '50.00',
                    'vatRate' => '23.00',
                    'sortOrder' => 1
                ],
                [
                    'description' => 'Product B',
                    'quantity' => '1.500',
                    'unit' => 'kg',
                    'unitPrice' => '30.00',
                    'vatRate' => '8.00',
                    'sortOrder' => 2
                ],
                [
                    'description' => 'Product C',
                    'quantity' => '3.000',
                    'unit' => 'm',
                    'unitPrice' => '25.00',
                    'vatRate' => '0.00',
                    'sortOrder' => 3
                ]
            ]
        );

        $envelope = $this->commandBus->dispatch($createCommand);
        $createdInvoice = $envelope->last(HandledStamp::class)->getResult();

        $this->assertCount(3, $createdInvoice->getItems());
        
        // Check item details and calculations
        $items = $createdInvoice->getItems()->toArray();
        
        // Sort by sort order to ensure predictable testing
        usort($items, fn($a, $b) => $a->getSortOrder() <=> $b->getSortOrder());
        
        // Product A: 2 * 50.00 = 100.00 net, 100.00 * 0.23 = 23.00 VAT
        $this->assertEquals('Product A', $items[0]->getDescription());
        $this->assertEquals('100.00', $items[0]->getNetAmount());
        $this->assertEquals('23.00', $items[0]->getVatAmount());
        
        // Product B: 1.5 * 30.00 = 45.00 net, 45.00 * 0.08 = 3.60 VAT
        $this->assertEquals('Product B', $items[1]->getDescription());
        $this->assertEquals('45.00', $items[1]->getNetAmount());
        $this->assertEquals('3.60', $items[1]->getVatAmount());
        
        // Product C: 3 * 25.00 = 75.00 net, 75.00 * 0.00 = 0.00 VAT
        $this->assertEquals('Product C', $items[2]->getDescription());
        $this->assertEquals('75.00', $items[2]->getNetAmount());
        $this->assertEquals('0.00', $items[2]->getVatAmount());
        
        // Total: 220.00 net, 26.60 VAT, 246.60 gross
        $this->assertEquals('220.00', $createdInvoice->getSubtotal());
        $this->assertEquals('26.60', $createdInvoice->getVatAmount());
        $this->assertEquals('246.60', $createdInvoice->getTotal());
    }

    private function createTestCustomer(): void
    {
        $this->testCustomer = new Company('Test CQRS Customer');
        $this->testCustomer->setTaxId('1111111111')
            ->setAddressLine1('CQRS Address 456')
            ->setCountryCode('PL')
            ->setEmail('cqrs@test.com')
            ->setPhoneNumber('111111111');
        
        $this->entityManager->persist($this->testCustomer);
        $this->entityManager->flush();
        
        $this->trackEntityForCleanup($this->testCustomer);
    }
}