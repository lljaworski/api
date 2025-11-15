<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Handler\Invoice;

use App\Application\Handler\Invoice\GetNextInvoiceNumberQueryHandler;
use App\Application\Query\Invoice\GetNextInvoiceNumberQuery;
use App\Service\InvoiceNumberGenerator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class GetNextInvoiceNumberQueryHandlerTest extends TestCase
{
    private InvoiceNumberGenerator&MockObject $invoiceNumberGenerator;
    private GetNextInvoiceNumberQueryHandler $handler;

    protected function setUp(): void
    {
        $this->invoiceNumberGenerator = $this->createMock(InvoiceNumberGenerator::class);
        $this->handler = new GetNextInvoiceNumberQueryHandler($this->invoiceNumberGenerator);
    }

    public function testInvokeCallsPreviewNextNumber(): void
    {
        $issueDate = new DateTimeImmutable('2025-11-15');
        $query = new GetNextInvoiceNumberQuery($issueDate);
        $expectedNumber = 'FV/2025/11/0001';

        $this->invoiceNumberGenerator
            ->expects($this->once())
            ->method('previewNextNumber')
            ->with($issueDate)
            ->willReturn($expectedNumber);

        $result = ($this->handler)($query);

        $this->assertEquals($expectedNumber, $result);
    }
}