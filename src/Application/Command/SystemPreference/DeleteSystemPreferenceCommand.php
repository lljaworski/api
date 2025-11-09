<?php

declare(strict_types=1);

namespace App\Application\Command\SystemPreference;

use App\Application\Command\AbstractCommand;

/**
 * Command to delete a system preference.
 */
final class DeleteSystemPreferenceCommand extends AbstractCommand
{
    public function __construct(
        public readonly int $id
    ) {
        parent::__construct();
    }
}
