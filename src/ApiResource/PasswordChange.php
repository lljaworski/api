<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\State\PasswordChangeProcessor;
use App\Validator\Constraints\PasswordMatch;
use App\Validator\Constraints\StrongPassword;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/password-changes',
            security: "is_granted('ROLE_USER')",
            denormalizationContext: ['groups' => ['password_change:write']],
            normalizationContext: ['groups' => ['password_change:read']],
            validationContext: ['groups' => ['password_change']]
        )
    ],
    processor: PasswordChangeProcessor::class
)]
#[PasswordMatch(groups: ['password_change'])]
class PasswordChange
{
    #[Assert\NotBlank(groups: ['password_change'], message: 'User ID is required.')]
    #[Assert\Positive(groups: ['password_change'], message: 'User ID must be a positive integer.')]
    #[Groups(['password_change:write'])]
    public ?int $userId = null;

    #[Assert\NotBlank(groups: ['password_change'], message: 'Current password is required.')]
    #[Groups(['password_change:write'])]
    public ?string $oldPassword = null;

    #[Assert\NotBlank(groups: ['password_change'], message: 'New password is required.')]
    #[StrongPassword(groups: ['password_change'])]
    #[Groups(['password_change:write'])]
    public ?string $newPassword = null;

    #[Assert\NotBlank(groups: ['password_change'], message: 'Password confirmation is required.')]
    #[Groups(['password_change:write'])]
    public ?string $passwordConfirmation = null;

    #[Groups(['password_change:read'])]
    public ?string $message = null;

    public function __construct(
        ?int $userId = null,
        ?string $oldPassword = null,
        ?string $newPassword = null,
        ?string $passwordConfirmation = null,
        ?string $message = null
    ) {
        $this->userId = $userId;
        $this->oldPassword = $oldPassword;
        $this->newPassword = $newPassword;
        $this->passwordConfirmation = $passwordConfirmation;
        $this->message = $message;
    }
}