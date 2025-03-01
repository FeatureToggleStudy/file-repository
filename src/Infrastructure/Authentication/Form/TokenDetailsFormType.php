<?php declare(strict_types=1);

namespace App\Infrastructure\Authentication\Form;

use App\Domain\Authentication\Form\TokenDetailsForm;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TokenDetailsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('tags', CollectionType::class, [
                'allow_add'    => true,
                'allow_delete' => true
            ])
            ->add('allowedMimeTypes', CollectionType::class, [
                'allow_add'    => true,
                'allow_delete' => true
            ])
            ->add('maxAllowedFileSize', IntegerType::class)
            ->add('allowedIpAddresses', CollectionType::class, [
                'allow_add'    => true,
                'allow_delete' => true
            ])
            ->add('allowedUserAgents', CollectionType::class, [
                'allow_add'    => true,
                'allow_delete' => true
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        return $resolver->setDefaults([
            'data_class'           => TokenDetailsForm::class,
            'csrf_protection'      => false,
            'extra_fields_message' => 'This request does not support extra parameters such as {{ extra_fields }}'
        ]);
    }
}
