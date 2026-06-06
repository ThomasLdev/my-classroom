<?php

declare(strict_types=1);

namespace App\Teaching\Infrastructure\Storage;

use App\Teaching\Application\Port\DocumentStorage;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

#[AsAlias(DocumentStorage::class)]
final readonly class LocalDocumentStorage implements DocumentStorage
{
    private Filesystem $filesystem;

    public function __construct(
        #[Autowire('%kernel.project_dir%/var/storage/documents')]
        private string $directory,
    ) {
        $this->filesystem = new Filesystem();
    }

    public function store(string $documentId, string $sourcePath): void
    {
        $this->filesystem->copy($sourcePath, $this->locate($documentId), true);
    }

    public function delete(string $documentId): void
    {
        $this->filesystem->remove($this->locate($documentId));
    }

    public function locate(string $documentId): string
    {
        return $this->directory . '/' . $documentId;
    }
}
