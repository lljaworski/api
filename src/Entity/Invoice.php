<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Enum\InvoiceStatus;
use App\Enum\PaymentMethodEnum;
use App\Repository\InvoiceRepository;
use App\State\InvoiceProcessor;
use App\State\InvoiceProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
#[ORM\Table(name: 'invoices')]
#[ORM\UniqueConstraint(name: 'UNIQ_INVOICE_NUMBER', fields: ['number'])]
#[UniqueEntity(
    fields: ['number'],
    message: 'This invoice number is already taken.',
    groups: ['invoice:create']
)]
#[ApiFilter(SearchFilter::class, properties: [
    'number' => 'partial',
    'customer.name' => 'partial',
    'customer.taxId' => 'partial',
    'status' => 'exact',
    'isPaid' => 'exact'
])]
#[ApiFilter(DateFilter::class, properties: [
    'issueDate',
    'saleDate', 
    'dueDate',
    'paidAt',
    'createdAt'
])]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/invoices',
            security: "is_granted('ROLE_B2B')",
            normalizationContext: ['groups' => ['invoice:read', 'invoice:list']],
            paginationEnabled: true,
            paginationItemsPerPage: 30,
            paginationMaximumItemsPerPage: 100
        ),
        new Get(
            uriTemplate: '/invoices/{id}',
            security: "is_granted('ROLE_B2B')",
            normalizationContext: ['groups' => ['invoice:read', 'invoice:details']]
        ),
        new Post(
            uriTemplate: '/invoices',
            security: "is_granted('ROLE_B2B')",
            denormalizationContext: ['groups' => ['invoice:create']],
            normalizationContext: ['groups' => ['invoice:read', 'invoice:details']],
            validationContext: ['groups' => ['invoice:create']]
        ),
        new Put(
            uriTemplate: '/invoices/{id}',
            security: "is_granted('ROLE_B2B')",
            denormalizationContext: ['groups' => ['invoice:update']],
            normalizationContext: ['groups' => ['invoice:read', 'invoice:details']],
            validationContext: ['groups' => ['invoice:update']]
        ),
        new Patch(
            uriTemplate: '/invoices/{id}',
            security: "is_granted('ROLE_B2B')",
            denormalizationContext: ['groups' => ['invoice:update']],
            normalizationContext: ['groups' => ['invoice:read', 'invoice:details']],
            validationContext: ['groups' => ['invoice:update']]
        ),
        new Delete(
            uriTemplate: '/invoices/{id}',
            security: "is_granted('ROLE_B2B')"
        ),
    ],
    provider: InvoiceProvider::class,
    processor: InvoiceProcessor::class
)]
class Invoice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['invoice:read', 'invoice:list', 'invoice:details'])]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\Length(max: 50, groups: ['invoice:create', 'invoice:update'])]
    #[Groups(['invoice:read', 'invoice:list', 'invoice:details'])]
    private string $number;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(groups: ['invoice:create'])]
    #[Assert\Type(type: \DateTimeInterface::class, groups: ['invoice:create', 'invoice:update'])]
    #[Groups(['invoice:read', 'invoice:list', 'invoice:details', 'invoice:create', 'invoice:update'])]
    private \DateTimeInterface $issueDate;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(groups: ['invoice:create'])]
    #[Assert\Type(type: \DateTimeInterface::class, groups: ['invoice:create', 'invoice:update'])]
    #[Groups(['invoice:read', 'invoice:list', 'invoice:details', 'invoice:create', 'invoice:update'])]
    private \DateTimeInterface $saleDate;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\Type(type: \DateTimeInterface::class, groups: ['invoice:create', 'invoice:update'])]
    #[Groups(['invoice:read', 'invoice:details', 'invoice:create', 'invoice:update'])]
    private ?\DateTimeInterface $dueDate = null;

    #[ORM\Column(length: 3)]
    #[Assert\NotBlank(groups: ['invoice:create'])]
    #[Assert\Length(exactly: 3, groups: ['invoice:create', 'invoice:update'])]
    #[Assert\Choice(
        choices: ['PLN', 'EUR', 'USD', 'GBP', 'CHF', 'CZK', 'SEK', 'NOK', 'DKK'],
        groups: ['invoice:create', 'invoice:update']
    )]
    #[Groups(['invoice:read', 'invoice:list', 'invoice:details', 'invoice:create', 'invoice:update'])]
    private string $currency = 'PLN';

    #[ORM\Column(type: 'string', enumType: PaymentMethodEnum::class, nullable: true)]
    #[Groups(['invoice:read', 'invoice:details', 'invoice:create', 'invoice:update'])]
    private ?PaymentMethodEnum $paymentMethod = null;

    #[ORM\Column(type: 'string', enumType: InvoiceStatus::class)]
    #[Groups(['invoice:read', 'invoice:list', 'invoice:details'])]
    private InvoiceStatus $status = InvoiceStatus::ISSUED;

    #[ORM\Column]
    #[SerializedName('isPaid')]
    #[Groups(['invoice:read', 'invoice:list', 'invoice:details'])]
    private bool $isPaid = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['invoice:read', 'invoice:details'])]
    private ?\DateTimeInterface $paidAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 1000, groups: ['invoice:create', 'invoice:update'])]
    #[Groups(['invoice:read', 'invoice:details', 'invoice:create', 'invoice:update'])]
    private ?string $notes = null;

    // KSeF specific fields (for future integration)
    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['invoice:read', 'invoice:details'])]
    private ?string $ksefNumber = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['invoice:read', 'invoice:details'])]
    private ?\DateTimeInterface $ksefSubmittedAt = null;

    // Calculated totals (automatically computed from items)
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['invoice:read', 'invoice:list', 'invoice:details'])]
    private string $subtotal = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['invoice:read', 'invoice:list', 'invoice:details'])]
    private string $vatAmount = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['invoice:read', 'invoice:list', 'invoice:details'])]
    private string $total = '0.00';

    // Audit fields
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['invoice:read', 'invoice:details'])]
    private ?\DateTimeInterface $deletedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['invoice:read', 'invoice:details'])]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['invoice:read', 'invoice:details'])]
    private \DateTimeInterface $updatedAt;

    // Relationships
    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(groups: ['invoice:create'])]
    #[Groups(['invoice:read', 'invoice:list', 'invoice:details', 'invoice:create', 'invoice:update'])]
    private Company $customer;

    /** @var Collection<int, InvoiceItem> */
    #[ORM\OneToMany(targetEntity: InvoiceItem::class, mappedBy: 'invoice', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['sortOrder' => 'ASC'])]
    #[Assert\Valid(groups: ['invoice:create', 'invoice:update'])]
    #[Assert\Count(min: 1, groups: ['invoice:create'])]
    #[Groups(['invoice:read', 'invoice:details', 'invoice:create', 'invoice:update'])]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function setNumber(string $number): static
    {
        $this->number = $number;
        $this->touch();
        return $this;
    }

    public function getIssueDate(): \DateTimeInterface
    {
        return $this->issueDate;
    }

    public function setIssueDate(\DateTimeInterface $issueDate): static
    {
        $this->issueDate = $issueDate;
        $this->touch();
        return $this;
    }

    public function getSaleDate(): \DateTimeInterface
    {
        return $this->saleDate;
    }

    public function setSaleDate(\DateTimeInterface $saleDate): static
    {
        $this->saleDate = $saleDate;
        $this->touch();
        return $this;
    }

    public function getDueDate(): ?\DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTimeInterface $dueDate): static
    {
        $this->dueDate = $dueDate;
        $this->touch();
        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;
        $this->touch();
        return $this;
    }

    public function getPaymentMethod(): ?PaymentMethodEnum
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?PaymentMethodEnum $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;
        $this->touch();
        return $this;
    }

    public function getStatus(): InvoiceStatus
    {
        return $this->status;
    }

    public function setStatus(InvoiceStatus $status): static
    {
        if (!$this->status->canTransitionTo($status)) {
            throw new \InvalidArgumentException(
                sprintf('Cannot transition from %s to %s', $this->status->value, $status->value)
            );
        }
        
        $this->status = $status;
        $this->touch();
        return $this;
    }

    public function isPaid(): bool
    {
        return $this->isPaid;
    }
    
    // Alternative getter with 'get' prefix for serialization
    public function getIsPaid(): bool
    {
        return $this->isPaid;
    }
    


    public function setIsPaid(bool $isPaid): static
    {
        $this->isPaid = $isPaid;
        if ($isPaid && $this->paidAt === null) {
            $this->paidAt = new \DateTime();
        } elseif (!$isPaid) {
            $this->paidAt = null;
        }
        $this->touch();
        return $this;
    }

    public function getPaidAt(): ?\DateTimeInterface
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTimeInterface $paidAt): static
    {
        $this->paidAt = $paidAt;
        $this->touch();
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        $this->touch();
        return $this;
    }

    public function getKsefNumber(): ?string
    {
        return $this->ksefNumber;
    }

    public function setKsefNumber(?string $ksefNumber): static
    {
        $this->ksefNumber = $ksefNumber;
        $this->touch();
        return $this;
    }

    public function getKsefSubmittedAt(): ?\DateTimeInterface
    {
        return $this->ksefSubmittedAt;
    }

    public function setKsefSubmittedAt(?\DateTimeInterface $ksefSubmittedAt): static
    {
        $this->ksefSubmittedAt = $ksefSubmittedAt;
        $this->touch();
        return $this;
    }

    public function getSubtotal(): string
    {
        return $this->subtotal;
    }

    public function setSubtotal(string $subtotal): static
    {
        $this->subtotal = $subtotal;
        $this->touch();
        return $this;
    }

    public function getVatAmount(): string
    {
        return $this->vatAmount;
    }

    public function setVatAmount(string $vatAmount): static
    {
        $this->vatAmount = $vatAmount;
        $this->touch();
        return $this;
    }

    public function getTotal(): string
    {
        return $this->total;
    }

    public function setTotal(string $total): static
    {
        $this->total = $total;
        $this->touch();
        return $this;
    }

    public function getCustomer(): Company
    {
        return $this->customer;
    }

    public function setCustomer(Company $customer): static
    {
        $this->customer = $customer;
        $this->touch();
        return $this;
    }

    /**
     * @return Collection<int, InvoiceItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(InvoiceItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setInvoice($this);
        }
        $this->touch();
        return $this;
    }

    public function removeItem(InvoiceItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getInvoice() === $this) {
                $item->setInvoice(null);
            }
        }
        $this->touch();
        return $this;
    }

    // Soft Delete Methods

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): static
    {
        $this->deletedAt = $deletedAt;
        $this->touch();
        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function isActive(): bool
    {
        return $this->deletedAt === null;
    }

    public function softDelete(): static
    {
        if (!$this->status->isDeletable()) {
            throw new \InvalidArgumentException(
                sprintf('Cannot delete invoice with status %s', $this->status->value)
            );
        }
        
        $this->deletedAt = new \DateTime();
        $this->touch();
        return $this;
    }

    public function restore(): static
    {
        $this->deletedAt = null;
        $this->touch();
        return $this;
    }

    // Audit Methods

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTime();
    }

    // Business Logic Methods

    public function canBeEdited(): bool
    {
        return $this->status->isEditable() && !$this->isDeleted();
    }

    public function canBeDeleted(): bool
    {
        return $this->status->isDeletable() && !$this->isDeleted();
    }

    public function issue(): static
    {
        $this->setStatus(InvoiceStatus::ISSUED);
        return $this;
    }

    public function markAsPaid(): static
    {
        $this->setStatus(InvoiceStatus::PAID);
        $this->setIsPaid(true);
        return $this;
    }

    public function cancel(): static
    {
        $this->setStatus(InvoiceStatus::CANCELLED);
        return $this;
    }

    public function isOverdue(): bool
    {
        if ($this->status !== InvoiceStatus::ISSUED || $this->isPaid || $this->dueDate === null) {
            return false;
        }
        
        return $this->dueDate < new \DateTime('today');
    }

    public function recalculateTotals(): static
    {
        $subtotal = '0.00';
        $vatAmount = '0.00';
        
        foreach ($this->items as $item) {
            $subtotal = bcadd($subtotal, $item->getNetAmount(), 2);
            $vatAmount = bcadd($vatAmount, $item->getVatAmount(), 2);
        }
        
        $this->subtotal = $subtotal;
        $this->vatAmount = $vatAmount;
        $this->total = bcadd($subtotal, $vatAmount, 2);
        
        $this->touch();
        return $this;
    }
}