<?php

declare(strict_types=1);

namespace LukaszJaworski\CeidgBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class CeidgBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
