<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use App\Repository\InvoiceSettingsRepository;
use App\Validator\Constraints\InvoiceNumberFormat;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: InvoiceSettingsRepository::class)]
#[ORM\Table(name: 'invoice_settings')]
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/invoice-settings',
            security: "is_granted('ROLE_B2B')",
            normalizationContext: ['groups' => ['invoice_settings:read']]
        ),
        new Patch(
            uriTemplate: '/invoice-settings',
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['invoice_settings:update']],
            normalizationContext: ['groups' => ['invoice_settings:read']]
        ),
    ]
)]
class InvoiceSettings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['invoice_settings:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(groups: ['invoice_settings:update'])]
    #[Assert\Length(max: 255, groups: ['invoice_settings:update'])]
    #[InvoiceNumberFormat(groups: ['invoice_settings:update'])]
    #[Groups(['invoice_settings:read', 'invoice_settings:update'])]
    private string $numberFormat = 'FV/{year}/{month}/{number:4}';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['invoice_settings:read'])]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['invoice_settings:read'])]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumberFormat(): string
    {
        return $this->numberFormat;
    }

    public function setNumberFormat(string $numberFormat): static
    {
        $this->numberFormat = $numberFormat;
        $this->touch();
        return $this;
    }

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
