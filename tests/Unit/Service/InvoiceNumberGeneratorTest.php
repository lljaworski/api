<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\InvoiceNumberGenerator;
use App\Repository\InvoiceRepository;
use App\Repository\SystemPreferenceRepository;
use App\Entity\Invoice;
use App\Entity\SystemPreference;
use App\Enum\PreferenceKey;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class InvoiceNumberGeneratorTest extends TestCase
{
    private InvoiceNumberGenerator $generator;
    private InvoiceRepository&MockObject $invoiceRepository;
    private SystemPreferenceRepository&MockObject $systemPreferenceRepository;

    protected function setUp(): void
    {
        $this->invoiceRepository = $this->createMock(InvoiceRepository::class);
        $this->systemPreferenceRepository = $this->createMock(SystemPreferenceRepository::class);
        
        // Mock default preference for invoice number format
        $this->systemPreferenceRepository
            ->method('findByKey')
            ->with(PreferenceKey::INVOICE_NUMBER_FORMAT)
            ->willReturn(null); // Uses default format
            
        $this->generator = new InvoiceNumberGenerator(
            $this->invoiceRepository,
            $this->systemPreferenceRepository
        );
    }

    public function testGenerateCurrentMonth(): void
    {
        $issueDate = new \DateTime('2024-10-15');
        
        $this->invoiceRepository
            ->expects($this->once())
            ->method('getNextSequenceNumber')
            ->with($issueDate)
            ->willReturn(1);

        $number = $this->generator->generate($issueDate);
        
        $this->assertEquals('FV/2024/10/0001', $number);
    }

    public function testGenerateJanuary(): void
    {
        $issueDate = new \DateTime('2024-01-01');
        
        $this->invoiceRepository
            ->expects($this->once())
            ->method('getNextSequenceNumber')
            ->with($issueDate)
            ->willReturn(1);

        $number = $this->generator->generate($issueDate);
        
        $this->assertEquals('FV/2024/01/0001', $number);
    }

    public function testGenerateDecember(): void
    {
        $issueDate = new \DateTime('2024-12-31');
        
        $this->invoiceRepository
            ->expects($this->once())
            ->method('getNextSequenceNumber')
            ->with($issueDate)
            ->willReturn(1);

        $number = $this->generator->generate($issueDate);
        
        $this->assertEquals('FV/2024/12/0001', $number);
    }

    public function testGenerateWithHigherSequenceNumber(): void
    {
        $issueDate = new \DateTime('2024-10-15');
        
        $this->invoiceRepository
            ->expects($this->once())
            ->method('getNextSequenceNumber')
            ->with($issueDate)
            ->willReturn(123);

        $number = $this->generator->generate($issueDate);
        
        $this->assertEquals('FV/2024/10/0123', $number);
    }

    public function testGenerateWithMaxSequenceNumber(): void
    {
        $issueDate = new \DateTime('2024-10-15');
        
        $this->invoiceRepository
            ->expects($this->once())
            ->method('getNextSequenceNumber')
            ->with($issueDate)
            ->willReturn(9999);

        $number = $this->generator->generate($issueDate);
        
        $this->assertEquals('FV/2024/10/9999', $number);
    }

    public function testGenerateForToday(): void
    {
        $this->invoiceRepository
            ->expects($this->once())
            ->method('getNextSequenceNumber')
            ->willReturn(1);

        $number = $this->generator->generateForToday();
        
        $expectedFormat = '/^FV\/\d{4}\/\d{2}\/0001$/';
        $this->assertMatchesRegularExpression($expectedFormat, $number);
    }

    public function testGenerateWithRetry(): void
    {
        $issueDate = new \DateTime('2024-10-15');
        
        // generateWithRetry will call generate() multiple times, so getNextSequenceNumber will be called multiple times
        $this->invoiceRepository
            ->expects($this->exactly(2))
            ->method('getNextSequenceNumber')
            ->with($issueDate)
            ->willReturn(1);

        // First call returns true (collision), second call returns false (no collision)
        $mockInvoice = $this->createMock(Invoice::class);
        $this->invoiceRepository
            ->expects($this->exactly(2))
            ->method('findByNumber')
            ->with('FV/2024/10/0001')
            ->willReturnOnConsecutiveCalls(
                $mockInvoice, // Mock invoice (collision)
                null // No collision
            );

        $number = $this->generator->generateWithRetry($issueDate);
        
        $this->assertEquals('FV/2024/10/0001', $number);
    }

    public function testIsValidFormat(): void
    {
        $validNumbers = [
            'FV/2024/01/0001',
            'FV/2024/12/9999',
            'FV/2025/06/0123',
        ];

        foreach ($validNumbers as $number) {
            $this->assertTrue($this->generator->isValidFormat($number));
        }
    }

    public function testIsInvalidFormat(): void
    {
        $invalidNumbers = [
            'INV/2024/01/0001', // Wrong prefix
            'FV/24/01/0001',    // Wrong year format
            'FV/2024/1/0001',   // Wrong month format
            'FV/2024/01/001',   // Wrong sequence format
            'FV/2024/01/00001', // Too long sequence
            '',                 // Empty
            'INVALID',          // Completely wrong format
        ];

        foreach ($invalidNumbers as $number) {
            $this->assertFalse($this->generator->isValidFormat($number), "Number '$number' should be invalid");
        }
    }

    public function testValidMonthNumbers(): void
    {
        // Note: The validation pattern only checks format, not actual month validity
        // FV/2024/13/0001 and FV/2024/00/0001 are format-valid but logically invalid
        $formatValidNumbers = [
            'FV/2024/13/0001',  // Format valid but month 13 doesn't exist
            'FV/2024/00/0001',  // Format valid but month 00 doesn't exist
        ];

        foreach ($formatValidNumbers as $number) {
            $this->assertTrue($this->generator->isValidFormat($number), "Number '$number' should be format-valid");
        }
    }

    public function testParseInvoiceNumber(): void
    {
        $number = 'FV/2024/10/0123';
        $parsed = $this->generator->parseInvoiceNumber($number);
        
        $this->assertIsArray($parsed);

        $this->assertEquals(2024, $parsed['year']);
        $this->assertEquals(10, $parsed['month']);
        $this->assertEquals(123, $parsed['sequence']);
    }

    public function testParseInvalidInvoiceNumber(): void
    {
        $invalidNumber = 'INVALID/FORMAT';
        $parsed = $this->generator->parseInvoiceNumber($invalidNumber);
        
        $this->assertNull($parsed);
    }

    public function testIsNumberTaken(): void
    {
        $number = 'FV/2024/10/0001';
        
        $mockInvoice = $this->createMock(Invoice::class);
        $this->invoiceRepository
            ->expects($this->once())
            ->method('findByNumber')
            ->with($number)
            ->willReturn($mockInvoice);

        $this->assertTrue($this->generator->isNumberTaken($number));
    }

    public function testIsNumberNotTaken(): void
    {
        $number = 'FV/2024/10/0001';
        
        $this->invoiceRepository
            ->expects($this->once())
            ->method('findByNumber')
            ->with($number)
            ->willReturn(null);

        $this->assertFalse($this->generator->isNumberTaken($number));
    }

    public function testGetFormatTemplate(): void
    {
        $template = $this->generator->getFormatTemplate();
        $this->assertEquals('FV/{year}/{month}/{number}', $template);
    }

    public function testPreviewNextNumber(): void
    {
        $issueDate = new \DateTime('2024-10-15');
        
        $this->invoiceRepository
            ->expects($this->once())
            ->method('getNextSequenceNumber')
            ->with($issueDate)
            ->willReturn(42);

        $preview = $this->generator->previewNextNumber($issueDate);
        $this->assertEquals('FV/2024/10/0042', $preview);
    }

    public function testGetNumberingStats(): void
    {
        $from = new \DateTime('2024-01-01');
        $to = new \DateTime('2024-12-31');
        
        $this->invoiceRepository
            ->expects($this->once())
            ->method('getNextSequenceNumber')
            ->willReturn(1);

        $stats = $this->generator->getNumberingStats($from, $to);
        
        $this->assertIsArray($stats);
        $this->assertEquals('FV/{year}/{month}/{number}', $stats['format']);
        $this->assertEquals('2024-01-01', $stats['period_from']);
        $this->assertEquals('2024-12-31', $stats['period_to']);
        $this->assertArrayHasKey('next_number_today', $stats);
    }
}