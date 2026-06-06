<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Teaching\Application\Port\DocumentStorage;

final class InMemoryDocumentStorage implements DocumentStorage
{
    /** @var array<string, string> documentId => source path */
    private array $stored = [];

    public function store(string $documentId, string $sourcePath): void
    {
        $this->stored[$documentId] = $sourcePath;
    }

    public function delete(string $documentId): void
    {
        unset($this->stored[$documentId]);
    }

    public function locate(string $documentId): string
    {
        return '/tmp/'.$documentId;
    }

    public function has(string $documentId): bool
    {
        return isset($this->stored[$documentId]);
    }

    public function count(): int
    {
        return \count($this->stored);
    }
}
