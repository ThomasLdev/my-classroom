<?php

declare(strict_types=1);

namespace App\Teaching\Application\Query\GetSessionDetail;

use App\Teaching\Domain\Model\Session\AttachedDocument;

final class DocumentViewFactory
{
    public function fromDocument(AttachedDocument $document): DocumentView
    {
        return new DocumentView(
            id: (string) $document->id,
            name: $document->name,
            displayName: $this->truncateName($document->name),
            sizeLabel: $this->prettySize($document->size),
            contentType: $document->contentType,
        );
    }

    private function truncateName(string $name, int $max = 34): string
    {
        if (mb_strlen($name) <= $max) {
            return $name;
        }

        $dot = mb_strrpos($name, '.');
        if ($dot !== false) {
            $extension = mb_substr($name, $dot + 1);
            $stemLength = $max - mb_strlen($extension) - 2;
            if ($stemLength > 4) {
                return mb_substr($name, 0, $stemLength) . '….' . $extension;
            }
        }

        return mb_substr($name, 0, $max - 1) . '…';
    }

    private function prettySize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' o';
        }

        if ($bytes < 1_048_576) {
            return round($bytes / 1024) . ' Ko';
        }

        return number_format($bytes / 1_048_576, 1, ',', ' ') . ' Mo';
    }
}
