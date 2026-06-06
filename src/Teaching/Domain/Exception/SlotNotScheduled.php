<?php

declare(strict_types=1);

namespace App\Teaching\Domain\Exception;

use DomainException;

final class SlotNotScheduled extends DomainException
{
    public static function for(string $slotId, string $date): self
    {
        return new self(sprintf('No scheduled occurrence for slot "%s" on %s.', $slotId, $date));
    }
}
