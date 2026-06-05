<?php

declare(strict_types=1);

namespace App\Teaching\Domain\Model\Session;

enum ActivityStatus: string
{
    case Planned = 'planned';
    case Done = 'done';
}
