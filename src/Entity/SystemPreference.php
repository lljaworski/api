<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Enum\PreferenceKey;
use App\Repository\SystemPreferenceRepository;
use App\State\SystemPreferenceProcessor;
use App\State\SystemPreferenceProvider;
use App\Validator\Constraints\ValidSystemPreferenceValue;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SystemPreferenceRepository::class)]
#[ORM\Table(name: 'system_preferences')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_KEY', fields: ['preferenceKey'])]
#[UniqueEntity(
    fields: ['preferenceKey'],
    message: 'This preference key already exists.',
    groups: ['preference:create']
)]
#[ValidSystemPreferenceValue(groups: ['preference:create', 'preference:update'])]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/system-preferences',
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['preference:read', 'preference:list']],
            paginationEnabled: true,
            paginationItemsPerPage: 30,
            paginationMaximumItemsPerPage: 100
        ),
        new Get(
            uriTemplate: '/system-preferences/{id}',
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['preference:read', 'preference:details']]
        ),
        new Post(
            uriTemplate: '/system-preferences',
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['preference:create']],
            normalizationContext: ['groups' => ['preference:read', 'preference:details']],
            validationContext: ['groups' => ['preference:create']]
        ),
        new Put(
            uriTemplate: '/system-preferences/{id}',
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['preference:update']],
            normalizationContext: ['groups' => ['preference:read', 'preference:details']],
            validationContext: ['groups' => ['preference:update']]
        ),
        new Patch(
            uriTemplate: '/system-preferences/{id}',
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['preference:update']],
            normalizationContext: ['groups' => ['preference:read', 'preference:details']],
            validationContext: ['groups' => ['preference:update']]
        ),
        new Delete(
            uriTemplate: '/system-preferences/{id}',
            security: "is_granted('ROLE_ADMIN')"
        ),
    ],
    provider: SystemPreferenceProvider::class,
    processor: SystemPreferenceProcessor::class
)]
class SystemPreference
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['preference:read', 'preference:list', 'preference:details'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100, unique: true, enumType: PreferenceKey::class)]
    #[Assert\NotBlank(groups: ['preference:create'])]
    #[Groups(['preference:read', 'preference:list', 'preference:details', 'preference:create', 'preference:update'])]
    private PreferenceKey $preferenceKey;

    #[ORM\Column(type: Types::JSON)]
    #[Assert\NotNull(groups: ['preference:create', 'preference:update'])]
    #[Groups(['preference:read', 'preference:list', 'preference:details', 'preference:create', 'preference:update'])]
    private mixed $value = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['preference:read', 'preference:details'])]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['preference:read', 'preference:details'])]
    private \DateTimeInterface $updatedAt;

    public function __construct(PreferenceKey $preferenceKey, mixed $value)
    {
        $this->preferenceKey = $preferenceKey;
        $this->value = $value;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPreferenceKey(): PreferenceKey
    {
        return $this->preferenceKey;
    }

    public function setPreferenceKey(PreferenceKey $preferenceKey): static
    {
        $this->preferenceKey = $preferenceKey;
        $this->touch();

        return $this;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): static
    {
        $this->value = $value;
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
