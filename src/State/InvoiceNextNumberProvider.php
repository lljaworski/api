<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\InvoiceNextNumber;
use App\Repository\InvoiceRepository;
use App\Repository\InvoiceSettingsRepository;
use App\Service\InvoiceNumberGenerator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProviderInterface<InvoiceNextNumber>
 */
class InvoiceNextNumberProvider implements ProviderInterface
{
    public function __construct(
        private readonly InvoiceNumberGenerator $numberGenerator,
        private readonly InvoiceRepository $invoiceRepository,
        private readonly InvoiceSettingsRepository $settingsRepository,
        private readonly RequestStack $requestStack
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?InvoiceNextNumber
    {
        $request = $this->requestStack->getCurrentRequest();
        
        if ($request === null) {
            throw new BadRequestHttpException('No request available');
        }

        // Get date parameter or default to today
        $dateString = $request->query->get('date');
        
        if ($dateString === null || $dateString === '') {
            $issueDate = new \DateTime();
        } else {
            // Use strict date validation
            $issueDate = \DateTime::createFromFormat('Y-m-d', $dateString);
            if ($issueDate === false || $issueDate->format('Y-m-d') !== $dateString) {
                throw new BadRequestHttpException(
                    sprintf('Invalid date format: %s. Expected format: YYYY-MM-DD', $dateString)
                );
            }
        }

        // Generate the next invoice number
        $nextNumber = $this->numberGenerator->generate($issueDate);
        
        // Get format and sequence number for response
        $settings = $this->settingsRepository->getOrCreateSettings();
        $format = $settings->getNumberFormat();
        $sequenceNumber = $this->invoiceRepository->getNextSequenceNumber($issueDate);

        return new InvoiceNextNumber(
            nextNumber: $nextNumber,
            format: $format,
            issueDate: $issueDate->format('Y-m-d'),
            sequenceNumber: $sequenceNumber
        );
    }
}
