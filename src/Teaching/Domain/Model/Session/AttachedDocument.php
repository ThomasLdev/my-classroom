<?php

declare(strict_types=1);

namespace App\Teaching\Domain\Model\Session;

/**
 * @phpstan-type DocumentStateArray array{
 *     id: string,
 *     name: string,
 *     size: int,
 *     contentType: string,
 * }
 */
final readonly class AttachedDocument
{
    private function __construct(
        public DocumentId $id,
        public string $name,
        public int $size,
        public string $contentType,
    ) {
    }

    public static function attach(DocumentId $id, string $name, int $size, string $contentType): self
    {
        return new self($id, $name, $size, $contentType);
    }

    /**
     * @param DocumentStateArray $state
     */
    public static function fromState(array $state): self
    {
        return new self(
            DocumentId::fromString($state['id']),
            $state['name'],
            $state['size'],
            $state['contentType'],
        );
    }

    /**
     * @return DocumentStateArray
     */
    public function toState(): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'size' => $this->size,
            'contentType' => $this->contentType,
        ];
    }
}
