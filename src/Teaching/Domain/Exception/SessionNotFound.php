<?php

declare(strict_types=1);

namespace App\Teaching\Domain\Exception;

use DomainException;

final class SessionNotFound extends DomainException
{
    public static function forOccurrence(string $slotId, string $date): self
    {
        return new self(sprintf('No materialised session for slot "%s" on %s.', $slotId, $date));
    }
}
