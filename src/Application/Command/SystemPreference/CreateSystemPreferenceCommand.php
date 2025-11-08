<?php

declare(strict_types=1);

namespace App\Application\Command\SystemPreference;

use App\Application\Command\AbstractCommand;
use App\Enum\PreferenceKey;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Command to create a new system preference.
 */
final class CreateSystemPreferenceCommand extends AbstractCommand
{
    public function __construct(
        #[Assert\NotNull]
        public readonly PreferenceKey $preferenceKey,
        
        #[Assert\NotNull]
        public readonly mixed $value
    ) {
        parent::__construct();
    }
}
