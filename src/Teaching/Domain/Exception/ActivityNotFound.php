<?php

declare(strict_types=1);

namespace App\Teaching\Domain\Exception;

use App\Teaching\Domain\Model\Session\ActivityId;
use App\Teaching\Domain\Model\Session\SessionId;
use DomainException;

final class ActivityNotFound extends DomainException
{
    public static function inSession(SessionId $session, ActivityId $activity): self
    {
        return new self(sprintf('Activity "%s" not found in session "%s".', $activity, $session));
    }
}
