<?php

declare(strict_types=1);

namespace App\Teaching\Domain\Model\Session;

/**
 * An activity is the detail of a single session. It is never created in a
 * back office and never repeatable. When a session's time is over, activities
 * still {@see ActivityStatus::Planned} are carried over to the next occurrence
 * as fresh activities pointing back to their origin via {@see $carriedOverFrom}.
 *
 * @phpstan-type ActivityStateArray array{
 *     id: string,
 *     title: string,
 *     status: string,
 *     position: int,
 *     carriedOverFrom: string|null,
 * }
 */
final class Activity
{
    private function __construct(
        public readonly ActivityId $id,
        public readonly string $title,
        public private(set) ActivityStatus $status,
        public readonly int $position,
        public readonly ?ActivityId $carriedOverFrom,
    ) {
    }

    public static function plan(ActivityId $id, string $title, int $position): self
    {
        return new self($id, self::guardTitle($title), ActivityStatus::Planned, $position, null);
    }

    public static function carriedFrom(ActivityId $id, self $source, int $position): self
    {
        return new self($id, $source->title, ActivityStatus::Planned, $position, $source->id);
    }

    /**
     * Rehydrate an activity from its persisted state (Memento pattern). Bypasses
     * creation rules on purpose — the data was already valid when stored.
     *
     * @param ActivityStateArray $state
     */
    public static function fromState(array $state): self
    {
        return new self(
            ActivityId::fromString($state['id']),
            $state['title'],
            ActivityStatus::from($state['status']),
            $state['position'],
            $state['carriedOverFrom'] !== null ? ActivityId::fromString($state['carriedOverFrom']) : null,
        );
    }

    /**
     * Flat, serializable snapshot of the activity, tailored for persistence.
     *
     * @return ActivityStateArray
     */
    public function toState(): array
    {
        return [
            'id' => (string) $this->id,
            'title' => $this->title,
            'status' => $this->status->value,
            'position' => $this->position,
            'carriedOverFrom' => $this->carriedOverFrom !== null ? (string) $this->carriedOverFrom : null,
        ];
    }

    public function markDone(): void
    {
        $this->status = ActivityStatus::Done;
    }

    public function markNotDone(): void
    {
        $this->status = ActivityStatus::Planned;
    }

    public function isPlanned(): bool
    {
        return $this->status === ActivityStatus::Planned;
    }

    private static function guardTitle(string $title): string
    {
        $trimmed = trim($title);
        if ($trimmed === '') {
            throw new \InvalidArgumentException('Activity title cannot be empty.');
        }

        return $trimmed;
    }
}
