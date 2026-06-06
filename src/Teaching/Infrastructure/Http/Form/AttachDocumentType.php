<?php

declare(strict_types=1);

namespace App\Teaching\Infrastructure\Http\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<mixed>
 */
final class AttachDocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('document', FileType::class, [
            'label' => false,
            'multiple' => true,
            'required' => false,
            'attr' => [
                'hidden' => 'hidden',
                'data-dropzone-target' => 'input',
                'data-action' => 'change->dropzone#selected',
            ],
        ]);
    }
}
