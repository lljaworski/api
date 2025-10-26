<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\CompanyRepository;
use App\State\CompanyProcessor;
use App\State\CompanyProvider;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CompanyRepository::class)]
#[ORM\Table(name: 'companies')]
#[ApiFilter(SearchFilter::class, properties: ['name' => 'partial', 'taxId' => 'partial', 'email' => 'partial', 'phoneNumber' => 'partial'])]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/companies',
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['company:read', 'company:list']],
            paginationEnabled: true,
            paginationItemsPerPage: 30,
            paginationMaximumItemsPerPage: 100
        ),
        new Get(
            uriTemplate: '/companies/{id}',
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['company:read', 'company:details']]
        ),
        new Post(
            uriTemplate: '/companies',
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['company:create']],
            normalizationContext: ['groups' => ['company:read', 'company:details']],
            validationContext: ['groups' => ['company:create']]
        ),
        new Put(
            uriTemplate: '/companies/{id}',
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['company:update']],
            normalizationContext: ['groups' => ['company:read', 'company:details']],
            validationContext: ['groups' => ['company:update']]
        ),
        new Patch(
            uriTemplate: '/companies/{id}',
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['company:update']],
            normalizationContext: ['groups' => ['company:read', 'company:details']],
            validationContext: ['groups' => ['company:update']]
        ),
        new Delete(
            uriTemplate: '/companies/{id}',
            security: "is_granted('ROLE_ADMIN')"
        ),
    ],
    provider: CompanyProvider::class,
    processor: CompanyProcessor::class
)]
class Company
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['company:read', 'company:list', 'company:details'])]
    private ?int $id = null;

    // Identification Data Fields
    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Length(max: 20, groups: ['company:create', 'company:update'])]
    #[Groups(['company:read', 'company:list', 'company:details', 'company:create', 'company:update'])]
    private ?string $taxId = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(groups: ['company:create'])]
    #[Assert\Length(max: 255, groups: ['company:create', 'company:update'])]
    #[Groups(['company:read', 'company:list', 'company:details', 'company:create', 'company:update'])]
    private string $name;

    #[ORM\Column(length: 4, nullable: true)]
    #[Assert\Length(max: 4, groups: ['company:create', 'company:update'])]
    #[Groups(['company:read', 'company:details', 'company:create', 'company:update'])]
    private ?string $taxpayerPrefix = null;

    #[ORM\Column(length: 17, nullable: true)]
    #[Assert\Length(max: 17, groups: ['company:create', 'company:update'])]
    #[Groups(['company:read', 'company:details', 'company:create', 'company:update'])]
    private ?string $eoriNumber = null;

    #[ORM\Column(length: 4, nullable: true)]
    #[Assert\Length(max: 4, groups: ['company:create', 'company:update'])]
    #[Groups(['company:read', 'company:details', 'company:create', 'company:update'])]
    private ?string $euCountryCode = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Length(max: 20, groups: ['company:create', 'company:update'])]
    #[Groups(['company:read', 'company:details', 'company:create', 'company:update'])]
    private ?string $vatRegNumberEu = null;

    #[ORM\Column(length: 4, nullable: true)]
    #[Assert\Length(max: 4, groups: ['company:create', 'company:update'])]
    #[Groups(['company:read', 'company:details', 'company:create', 'company:update'])]
    private ?string $otherIdCountryCode = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Length(max: 50, groups: ['company:create', 'company:update'])]
    #[Groups(['company:read', 'company:details', 'company:create', 'company:update'])]
    private ?string $otherIdNumber = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['company:read', 'company:details', 'company:create', 'company:update'])]
    private ?bool $noIdMarker = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Length(max: 50, groups: ['company:create', 'company:update'])]
    #[Groups(['company:read', 'company:details', 'company:create', 'company:update'])]
    private ?string $clientNumber = null;

    // Address Fields
    #[ORM\Column(length: 4, nullable: true)]
    #[Assert\Length(max: 4, groups: ['company:create', 'company:update'])]
    #[Groups(['company:read', 'company:details', 'company:create', 'company:update'])]
    private ?string $countryCode = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255, groups: ['company:create', 'company:update'])]
    #[Groups(['company:read', 'company:details', 'company:create', 'company:update'])]
    private ?string $addressLine1 = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255, groups: ['company:create', 'company:update'])]
    #[Groups(['company:read', 'company:details', 'company:create', 'company:update'])]
    private ?string $addressLine2 = null;

    #[ORM\Column(length: 13, nullable: true)]
    #[Assert\Length(max: 13, groups: ['company:create', 'company:update'])]
    #[Groups(['company:read', 'company:details', 'company:create', 'company:update'])]
    private ?string $gln = null;

    // Correspondence Address Fields
    #[ORM\Column(length: 4, nullable: true)]
    #[Assert\Length(max: 4, groups: ['company:create', 'company:update'])]
    #[Groups(['company:read', 'company:details', 'company:create', 'company:update'])]
    private ?string $correspondenceCountryCode = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255, groups: ['company:create', 'company:update'])]
    #[Groups(['company:read', 'company:details', 'company:create', 'company:update'])]
    private ?string $correspondenceAddressLine1 = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255, groups: ['company:create', 'company:update'])]
    #[Groups(['company:read', 'company:details', 'company:create', 'company:update'])]
    private ?string $correspondenceAddressLine2 = null;

    #[ORM\Column(length: 13, nullable: true)]
    #[Assert\Length(max: 13, groups: ['company:create', 'company:update'])]
    #[Groups(['company:read', 'company:details', 'company:create', 'company:update'])]
    private ?string $correspondenceGln = null;

    // Contact Details
    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255, groups: ['company:create', 'company:update'])]
    #[Assert\Email(groups: ['company:create', 'company:update'])]
    #[Groups(['company:read', 'company:list', 'company:details', 'company:create', 'company:update'])]
    private ?string $email = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Length(max: 20, groups: ['company:create', 'company:update'])]
    #[Groups(['company:read', 'company:list', 'company:details', 'company:create', 'company:update'])]
    private ?string $phoneNumber = null;

    // Additional Information Fields
    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 1, max: 4, groups: ['company:create', 'company:update'])]
    #[Groups(['company:read', 'company:details', 'company:create', 'company:update'])]
    private ?int $taxpayerStatus = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 1, max: 2, groups: ['company:create', 'company:update'])]
    #[Groups(['company:read', 'company:details', 'company:create', 'company:update'])]
    private ?int $jstMarker = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 1, max: 2, groups: ['company:create', 'company:update'])]
    #[Groups(['company:read', 'company:details', 'company:create', 'company:update'])]
    private ?int $gvMarker = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Choice(choices: [1, 2, 4, 6, 11], groups: ['company:create', 'company:update'])]
    #[Groups(['company:read', 'company:details', 'company:create', 'company:update'])]
    private ?int $role = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['company:read', 'company:details', 'company:create', 'company:update'])]
    private ?bool $otherRoleMarker = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255, groups: ['company:create', 'company:update'])]
    #[Groups(['company:read', 'company:details', 'company:create', 'company:update'])]
    private ?string $roleDescription = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    #[Assert\Range(min: 0, max: 100, groups: ['company:create', 'company:update'])]
    #[Groups(['company:read', 'company:details', 'company:create', 'company:update'])]
    private ?float $sharePercentage = null;

    // Audit Fields
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['company:read', 'company:details'])]
    private ?\DateTimeInterface $deletedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['company:read', 'company:details'])]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['company:read', 'company:details'])]
    private \DateTimeInterface $updatedAt;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTaxId(): ?string
    {
        return $this->taxId;
    }

    public function setTaxId(?string $taxId): static
    {
        $this->taxId = $taxId;
        $this->touch();
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        $this->touch();
        return $this;
    }

    public function getTaxpayerPrefix(): ?string
    {
        return $this->taxpayerPrefix;
    }

    public function setTaxpayerPrefix(?string $taxpayerPrefix): static
    {
        $this->taxpayerPrefix = $taxpayerPrefix;
        $this->touch();
        return $this;
    }

    public function getEoriNumber(): ?string
    {
        return $this->eoriNumber;
    }

    public function setEoriNumber(?string $eoriNumber): static
    {
        $this->eoriNumber = $eoriNumber;
        $this->touch();
        return $this;
    }

    public function getEuCountryCode(): ?string
    {
        return $this->euCountryCode;
    }

    public function setEuCountryCode(?string $euCountryCode): static
    {
        $this->euCountryCode = $euCountryCode;
        $this->touch();
        return $this;
    }

    public function getVatRegNumberEu(): ?string
    {
        return $this->vatRegNumberEu;
    }

    public function setVatRegNumberEu(?string $vatRegNumberEu): static
    {
        $this->vatRegNumberEu = $vatRegNumberEu;
        $this->touch();
        return $this;
    }

    public function getOtherIdCountryCode(): ?string
    {
        return $this->otherIdCountryCode;
    }

    public function setOtherIdCountryCode(?string $otherIdCountryCode): static
    {
        $this->otherIdCountryCode = $otherIdCountryCode;
        $this->touch();
        return $this;
    }

    public function getOtherIdNumber(): ?string
    {
        return $this->otherIdNumber;
    }

    public function setOtherIdNumber(?string $otherIdNumber): static
    {
        $this->otherIdNumber = $otherIdNumber;
        $this->touch();
        return $this;
    }

    public function getNoIdMarker(): ?bool
    {
        return $this->noIdMarker;
    }

    public function setNoIdMarker(?bool $noIdMarker): static
    {
        $this->noIdMarker = $noIdMarker;
        $this->touch();
        return $this;
    }

    public function getClientNumber(): ?string
    {
        return $this->clientNumber;
    }

    public function setClientNumber(?string $clientNumber): static
    {
        $this->clientNumber = $clientNumber;
        $this->touch();
        return $this;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function setCountryCode(?string $countryCode): static
    {
        $this->countryCode = $countryCode;
        $this->touch();
        return $this;
    }

    public function getAddressLine1(): ?string
    {
        return $this->addressLine1;
    }

    public function setAddressLine1(?string $addressLine1): static
    {
        $this->addressLine1 = $addressLine1;
        $this->touch();
        return $this;
    }

    public function getAddressLine2(): ?string
    {
        return $this->addressLine2;
    }

    public function setAddressLine2(?string $addressLine2): static
    {
        $this->addressLine2 = $addressLine2;
        $this->touch();
        return $this;
    }

    public function getGln(): ?string
    {
        return $this->gln;
    }

    public function setGln(?string $gln): static
    {
        $this->gln = $gln;
        $this->touch();
        return $this;
    }

    public function getCorrespondenceCountryCode(): ?string
    {
        return $this->correspondenceCountryCode;
    }

    public function setCorrespondenceCountryCode(?string $correspondenceCountryCode): static
    {
        $this->correspondenceCountryCode = $correspondenceCountryCode;
        $this->touch();
        return $this;
    }

    public function getCorrespondenceAddressLine1(): ?string
    {
        return $this->correspondenceAddressLine1;
    }

    public function setCorrespondenceAddressLine1(?string $correspondenceAddressLine1): static
    {
        $this->correspondenceAddressLine1 = $correspondenceAddressLine1;
        $this->touch();
        return $this;
    }

    public function getCorrespondenceAddressLine2(): ?string
    {
        return $this->correspondenceAddressLine2;
    }

    public function setCorrespondenceAddressLine2(?string $correspondenceAddressLine2): static
    {
        $this->correspondenceAddressLine2 = $correspondenceAddressLine2;
        $this->touch();
        return $this;
    }

    public function getCorrespondenceGln(): ?string
    {
        return $this->correspondenceGln;
    }

    public function setCorrespondenceGln(?string $correspondenceGln): static
    {
        $this->correspondenceGln = $correspondenceGln;
        $this->touch();
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        $this->touch();
        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;
        $this->touch();
        return $this;
    }

    public function getTaxpayerStatus(): ?int
    {
        return $this->taxpayerStatus;
    }

    public function setTaxpayerStatus(?int $taxpayerStatus): static
    {
        $this->taxpayerStatus = $taxpayerStatus;
        $this->touch();
        return $this;
    }

    public function getJstMarker(): ?int
    {
        return $this->jstMarker;
    }

    public function setJstMarker(?int $jstMarker): static
    {
        $this->jstMarker = $jstMarker;
        $this->touch();
        return $this;
    }

    public function getGvMarker(): ?int
    {
        return $this->gvMarker;
    }

    public function setGvMarker(?int $gvMarker): static
    {
        $this->gvMarker = $gvMarker;
        $this->touch();
        return $this;
    }

    public function getRole(): ?int
    {
        return $this->role;
    }

    public function setRole(?int $role): static
    {
        $this->role = $role;
        $this->touch();
        return $this;
    }

    public function getOtherRoleMarker(): ?bool
    {
        return $this->otherRoleMarker;
    }

    public function setOtherRoleMarker(?bool $otherRoleMarker): static
    {
        $this->otherRoleMarker = $otherRoleMarker;
        $this->touch();
        return $this;
    }

    public function getRoleDescription(): ?string
    {
        return $this->roleDescription;
    }

    public function setRoleDescription(?string $roleDescription): static
    {
        $this->roleDescription = $roleDescription;
        $this->touch();
        return $this;
    }

    public function getSharePercentage(): ?float
    {
        return $this->sharePercentage;
    }

    public function setSharePercentage(?float $sharePercentage): static
    {
        $this->sharePercentage = $sharePercentage;
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
}