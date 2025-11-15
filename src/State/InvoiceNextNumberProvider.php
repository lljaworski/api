<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\InvoiceNextNumber;
use App\Application\Query\Invoice\GetNextInvoiceNumberQuery;
use App\Service\InvoiceNumberGenerator;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

readonly class InvoiceNextNumberProvider implements ProviderInterface
{
    public function __construct(
        private MessageBusInterface $queryBus,
        private RequestStack $requestStack,
        private InvoiceNumberGenerator $invoiceNumberGenerator,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): InvoiceNextNumber
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            throw new BadRequestHttpException('Request not available');
        }

        $dateString = $request->query->get('date');
        if (!$dateString) {
            throw new BadRequestHttpException('Date parameter is required (format: YYYY-MM-DD)');
        }

        try {
            $issueDate = new DateTimeImmutable($dateString);
        } catch (\Exception) {
            throw new BadRequestHttpException('Invalid date format. Use YYYY-MM-DD');
        }

        // Use CQRS to get the next invoice number
        $query = new GetNextInvoiceNumberQuery($issueDate);
        $envelope = $this->queryBus->dispatch($query);
        $invoiceNumber = $envelope->last(HandledStamp::class)?->getResult();

        if (!$invoiceNumber) {
            throw new BadRequestHttpException('Unable to generate invoice number');
        }

        return new InvoiceNextNumber(
            invoiceNumber: $invoiceNumber,
            issueDate: $issueDate->format('Y-m-d'),
            format: $this->invoiceNumberGenerator->getFormatTemplate(),
        );
    }
}