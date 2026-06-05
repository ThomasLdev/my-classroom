<?php

declare(strict_types=1);

namespace App\Teaching\Application\Command\CloseElapsedSessions;

/**
 * Triggered by the scheduler every few minutes. Time decides: a session whose
 * slot is over is necessarily finished, and its unfinished activities must be
 * carried to the next occurrence.
 */
final readonly class CloseElapsedSessions
{
}
