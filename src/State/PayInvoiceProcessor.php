<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Command\Invoice\PayInvoiceCommand;
use App\Entity\Invoice;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

/**
 * @implements ProcessorInterface<Invoice, Invoice>
 */
final class PayInvoiceProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly MessageBusInterface $commandBus
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // Get the invoice ID from URL parameters
        $invoiceId = $uriVariables['id'] ?? null;
        if ($invoiceId === null) {
            throw new \InvalidArgumentException('Invoice ID is required');
        }

        // Extract optional paidAt date from request body
        $paidAt = null;
        if (isset($context['request'])) {
            $request = $context['request'];
            $requestData = json_decode($request->getContent(), true) ?? [];
            
            if (isset($requestData['paidAt'])) {
                $paidAt = new \DateTime($requestData['paidAt']);
            }
        }

        $command = new PayInvoiceCommand(
            id: (int) $invoiceId,
            paidAt: $paidAt
        );

        try {
            $envelope = $this->commandBus->dispatch($command);
            $handledStamp = $envelope->last(HandledStamp::class);

            return $handledStamp->getResult();
        } catch (HandlerFailedException $exception) {
            // Extract the original exception from the HandlerFailedException
            $originalException = $exception->getPrevious();
            
            if ($originalException instanceof NotFoundHttpException) {
                throw $originalException;
            }
            
            if ($originalException instanceof \InvalidArgumentException) {
                throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException($originalException->getMessage());
            }
            
            // Re-throw if we don't know how to handle it
            throw $exception;
        }
    }
}