<?php

declare(strict_types=1);

namespace App\Teaching\Application\Port;

/**
 * Binary storage for attached documents, keyed by the (globally unique) document id.
 * Metadata (name, size, content type) lives in the Session aggregate.
 */
interface DocumentStorage
{
    public function store(string $documentId, string $sourcePath): void;

    public function delete(string $documentId): void;

    /**
     * Absolute path to the stored file, for streaming back to the client.
     */
    public function locate(string $documentId): string;
}
