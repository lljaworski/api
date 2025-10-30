<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\InvoiceItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: InvoiceItemRepository::class)]
#[ORM\Table(name: 'invoice_items')]
class InvoiceItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['invoice:read', 'invoice:details'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(groups: ['invoice:create', 'invoice:update'])]
    #[Assert\Length(max: 255, groups: ['invoice:create', 'invoice:update'])]
    #[Groups(['invoice:read', 'invoice:details', 'invoice:create', 'invoice:update'])]
    private string $description;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 3)]
    #[Assert\NotBlank(groups: ['invoice:create', 'invoice:update'])]
    #[Assert\Positive(groups: ['invoice:create', 'invoice:update'])]
    #[Groups(['invoice:read', 'invoice:details', 'invoice:create', 'invoice:update'])]
    private string $quantity;

    #[ORM\Column(length: 10)]
    #[Assert\NotBlank(groups: ['invoice:create', 'invoice:update'])]
    #[Assert\Length(max: 10, groups: ['invoice:create', 'invoice:update'])]
    #[Assert\Choice(
        choices: ['szt.', 'kg', 'm', 'm2', 'm3', 'godz.', 'dzień', 'l', 't', 'km', 'kWh', 'usł.', 'kpl.', 'op.', 'm.b.'],
        groups: ['invoice:create', 'invoice:update']
    )]
    #[Groups(['invoice:read', 'invoice:details', 'invoice:create', 'invoice:update'])]
    private string $unit;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(groups: ['invoice:create', 'invoice:update'])]
    #[Assert\PositiveOrZero(groups: ['invoice:create', 'invoice:update'])]
    #[Groups(['invoice:read', 'invoice:details', 'invoice:create', 'invoice:update'])]
    private string $unitPrice;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['invoice:read', 'invoice:details'])]
    private string $netAmount = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Assert\NotBlank(groups: ['invoice:create', 'invoice:update'])]
    #[Assert\Choice(
        choices: ['0.00', '5.00', '8.00', '23.00'],
        groups: ['invoice:create', 'invoice:update'],
        message: 'VAT rate must be one of: 0%, 5%, 8%, 23%'
    )]
    #[Groups(['invoice:read', 'invoice:details', 'invoice:create', 'invoice:update'])]
    private string $vatRate;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['invoice:read', 'invoice:details'])]
    private string $vatAmount = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['invoice:read', 'invoice:details'])]
    private string $grossAmount = '0.00';

    #[ORM\Column]
    #[Assert\PositiveOrZero(groups: ['invoice:create', 'invoice:update'])]
    #[Groups(['invoice:read', 'invoice:details', 'invoice:create', 'invoice:update'])]
    private int $sortOrder = 0;

    // Relationships
    #[ORM\ManyToOne(targetEntity: Invoice::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(groups: ['invoice:create'])]
    private ?Invoice $invoice = null;

    public function __construct()
    {
        // Constructor can be used for initialization if needed
    }

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        $this->recalculateAmounts();
        return $this;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function setQuantity(string $quantity): static
    {
        $this->quantity = $quantity;
        $this->recalculateAmounts();
        return $this;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function setUnit(string $unit): static
    {
        $this->unit = $unit;
        return $this;
    }

    public function getUnitPrice(): string
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(string $unitPrice): static
    {
        $this->unitPrice = $unitPrice;
        $this->recalculateAmounts();
        return $this;
    }

    public function getNetAmount(): string
    {
        return $this->netAmount;
    }

    public function setNetAmount(string $netAmount): static
    {
        $this->netAmount = $netAmount;
        return $this;
    }

    public function getVatRate(): string
    {
        return $this->vatRate;
    }

    public function setVatRate(string $vatRate): static
    {
        $this->vatRate = $vatRate;
        $this->recalculateAmounts();
        return $this;
    }

    public function getVatAmount(): string
    {
        return $this->vatAmount;
    }

    public function setVatAmount(string $vatAmount): static
    {
        $this->vatAmount = $vatAmount;
        return $this;
    }

    public function getGrossAmount(): string
    {
        return $this->grossAmount;
    }

    public function setGrossAmount(string $grossAmount): static
    {
        $this->grossAmount = $grossAmount;
        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(?Invoice $invoice): static
    {
        $this->invoice = $invoice;
        return $this;
    }

    // Business Logic Methods

    /**
     * Recalculates all monetary amounts based on quantity, unit price, and VAT rate
     */
    public function recalculateAmounts(): static
    {
        if (empty($this->quantity) || empty($this->unitPrice) || empty($this->vatRate)) {
            return $this;
        }

        // Calculate net amount (quantity * unit price)
        $this->netAmount = bcmul($this->quantity, $this->unitPrice, 2);

        // Calculate VAT amount (net amount * VAT rate / 100)
        $vatDecimal = bcdiv($this->vatRate, '100', 4);
        $this->vatAmount = bcmul($this->netAmount, $vatDecimal, 2);

        // Calculate gross amount (net amount + VAT amount)
        $this->grossAmount = bcadd($this->netAmount, $this->vatAmount, 2);

        // Trigger invoice totals recalculation if invoice is set
        if ($this->invoice !== null) {
            $this->invoice->recalculateTotals();
        }

        return $this;
    }

    /**
     * Returns the line total for KSeF P_11 field (net amount)
     */
    public function getKsefP11(): string
    {
        return $this->netAmount;
    }

    /**
     * Returns the VAT rate as integer for KSeF P_12 field
     */
    public function getKsefP12(): int
    {
        return (int) $this->vatRate;
    }

    /**
     * Returns description for KSeF P_7 field
     */
    public function getKsefP7(): string
    {
        return $this->description;
    }

    /**
     * Returns unit for KSeF P_8A field
     */
    public function getKsefP8A(): string
    {
        return $this->unit;
    }

    /**
     * Returns quantity for KSeF P_8B field
     */
    public function getKsefP8B(): string
    {
        return $this->quantity;
    }

    /**
     * Returns unit price for KSeF P_9A field
     */
    public function getKsefP9A(): string
    {
        return $this->unitPrice;
    }

    /**
     * Validates if the item has all required data for invoice processing
     */
    public function isComplete(): bool
    {
        return !empty($this->description) 
            && !empty($this->quantity) 
            && !empty($this->unit) 
            && !empty($this->unitPrice) 
            && !empty($this->vatRate);
    }

    /**
     * Returns a formatted display string for the item
     */
    public function getDisplayString(): string
    {
        return sprintf(
            '%s (%s %s × %s PLN, VAT %s%%)', 
            $this->description,
            $this->quantity,
            $this->unit,
            $this->unitPrice,
            $this->vatRate
        );
    }
}