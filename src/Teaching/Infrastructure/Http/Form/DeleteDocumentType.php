<?php

declare(strict_types=1);

namespace App\Teaching\Infrastructure\Http\Form;

use Symfony\Component\Form\AbstractType;

/**
 * Fieldless form: it exists only to carry (and validate) a CSRF token for the
 * document removal POST.
 *
 * @extends AbstractType<mixed>
 */
final class DeleteDocumentType extends AbstractType
{
}
