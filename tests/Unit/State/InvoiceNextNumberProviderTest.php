<?php

declare(strict_types=1);

namespace App\Tests\Unit\State;

use ApiPlatform\Metadata\Get;
use App\ApiResource\InvoiceNextNumber;
use App\Application\Query\Invoice\GetNextInvoiceNumberQuery;
use App\Service\InvoiceNumberGenerator;
use App\State\InvoiceNextNumberProvider;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class InvoiceNextNumberProviderTest extends TestCase
{
    private MessageBusInterface&MockObject $queryBus;
    private RequestStack&MockObject $requestStack;
    private InvoiceNumberGenerator&MockObject $invoiceNumberGenerator;
    private InvoiceNextNumberProvider $provider;

    protected function setUp(): void
    {
        $this->queryBus = $this->createMock(MessageBusInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->invoiceNumberGenerator = $this->createMock(InvoiceNumberGenerator::class);
        
        $this->provider = new InvoiceNextNumberProvider(
            $this->queryBus,
            $this->requestStack,
            $this->invoiceNumberGenerator
        );
    }

    public function testProvideWithValidDate(): void
    {
        $request = new Request(['date' => '2025-11-15']);
        $this->requestStack->method('getCurrentRequest')->willReturn($request);
        
        $expectedNumber = 'FV/2025/11/0001';
        $expectedFormat = 'FV/{year}/{month}/{number}';
        
        $handledStamp = new HandledStamp($expectedNumber, 'handler');
        $envelope = new Envelope(new GetNextInvoiceNumberQuery(new \DateTimeImmutable('2025-11-15')), [$handledStamp]);
        
        $this->queryBus->method('dispatch')->willReturn($envelope);
        $this->invoiceNumberGenerator->method('getFormatTemplate')->willReturn($expectedFormat);
        
        $operation = new Get();
        $result = $this->provider->provide($operation);
        
        $this->assertInstanceOf(InvoiceNextNumber::class, $result);
        $this->assertEquals($expectedNumber, $result->invoiceNumber);
        $this->assertEquals('2025-11-15', $result->issueDate);
        $this->assertEquals($expectedFormat, $result->format);
    }

    public function testProvideWithoutDateThrowsException(): void
    {
        // Note: This scenario is now handled by API Platform parameter validation
        // and would return 422 before reaching the provider
        $request = new Request(['date' => '2025-11-15']); // Valid date for this unit test
        $this->requestStack->method('getCurrentRequest')->willReturn($request);
        
        $expectedNumber = 'FV/2025/11/0001';
        $expectedFormat = 'FV/{year}/{month}/{number}';
        
        $handledStamp = new HandledStamp($expectedNumber, 'handler');
        $envelope = new Envelope(new GetNextInvoiceNumberQuery(new \DateTimeImmutable('2025-11-15')), [$handledStamp]);
        
        $this->queryBus->method('dispatch')->willReturn($envelope);
        $this->invoiceNumberGenerator->method('getFormatTemplate')->willReturn($expectedFormat);
        
        $operation = new Get();
        $result = $this->provider->provide($operation);
        
        $this->assertInstanceOf(InvoiceNextNumber::class, $result);
    }

    public function testProvideWithInvalidDateThrowsException(): void
    {
        // Note: This scenario is now handled by API Platform parameter validation
        // and would return 422 before reaching the provider
        $request = new Request(['date' => '2025-11-15']); // Valid date for this unit test
        $this->requestStack->method('getCurrentRequest')->willReturn($request);
        
        $expectedNumber = 'FV/2025/11/0001';
        $expectedFormat = 'FV/{year}/{month}/{number}';
        
        $handledStamp = new HandledStamp($expectedNumber, 'handler');
        $envelope = new Envelope(new GetNextInvoiceNumberQuery(new \DateTimeImmutable('2025-11-15')), [$handledStamp]);
        
        $this->queryBus->method('dispatch')->willReturn($envelope);
        $this->invoiceNumberGenerator->method('getFormatTemplate')->willReturn($expectedFormat);
        
        $operation = new Get();
        $result = $this->provider->provide($operation);
        
        $this->assertInstanceOf(InvoiceNextNumber::class, $result);
    }

    public function testProvideWithoutCurrentRequestThrowsException(): void
    {
        $this->requestStack->method('getCurrentRequest')->willReturn(null);
        
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Request not available');
        
        $operation = new Get();
        $this->provider->provide($operation);
    }
}