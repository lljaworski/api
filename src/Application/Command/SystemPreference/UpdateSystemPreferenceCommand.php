<?php

declare(strict_types=1);

namespace App\Application\Command\SystemPreference;

use App\Application\Command\AbstractCommand;
use App\Enum\PreferenceKey;

/**
 * Command to update an existing system preference.
 */
final class UpdateSystemPreferenceCommand extends AbstractCommand
{
    public function __construct(
        public readonly int $id,
        public readonly ?PreferenceKey $preferenceKey = null,
        public readonly mixed $value = null
    ) {
        parent::__construct();
    }
}
