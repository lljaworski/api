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
        $request = new Request();
        $this->requestStack->method('getCurrentRequest')->willReturn($request);
        
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Date parameter is required (format: YYYY-MM-DD)');
        
        $operation = new Get();
        $this->provider->provide($operation);
    }

    public function testProvideWithInvalidDateThrowsException(): void
    {
        $request = new Request(['date' => 'invalid-date']);
        $this->requestStack->method('getCurrentRequest')->willReturn($request);
        
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid date format. Use YYYY-MM-DD');
        
        $operation = new Get();
        $this->provider->provide($operation);
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