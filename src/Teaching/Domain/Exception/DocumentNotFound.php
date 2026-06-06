<?php

declare(strict_types=1);

namespace App\Teaching\Domain\Exception;

use App\Teaching\Domain\Model\Session\DocumentId;
use App\Teaching\Domain\Model\Session\SessionId;
use DomainException;

final class DocumentNotFound extends DomainException
{
    public static function inSession(SessionId $session, DocumentId $document): self
    {
        return new self(sprintf('Document "%s" not found in session "%s".', $document, $session));
    }
}
