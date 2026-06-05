<?php

declare(strict_types=1);

namespace App\Teaching\Domain\Port;

use App\Shared\Domain\Identifier\ClassroomId;
use App\Shared\Domain\Identifier\SlotId;
use App\Shared\Domain\Occurrence;

/**
 * Anti-corruption port over the Scheduling context: Teaching asks "what is
 * scheduled?" without knowing about timetables or school breaks.
 * Implemented in Infrastructure by an adapter delegating to Scheduling.
 */
interface OccurrenceProvider
{
    /**
     * Virtual occurrences for a given day (school breaks already excluded).
     *
     * @return list<Occurrence>
     */
    public function forDay(\DateTimeImmutable $date): array;

    /**
     * Resolve a single occurrence by its identity, used when materialising a
     * session on first write. Null if nothing is scheduled there.
     */
    public function resolve(SlotId $slotId, \DateTimeImmutable $date): ?Occurrence;

    /**
     * The next occurrence of the same classroom strictly after the given
     * instant — the target of a carry-over. Null if none remains.
     */
    public function nextAfter(ClassroomId $classroomId, \DateTimeImmutable $after): ?Occurrence;
}
