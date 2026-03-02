<?php

namespace App\Form\Admin;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class AdminUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'] === true;

        $builder
            ->add('username', TextType::class, [
                'required' => false, // pas de validation HTML
            ])
            ->add('email', EmailType::class, [
                'required' => false,
            ])
            ->add('roleType', ChoiceType::class, [
                'choices' => [
                    'PLAYER' => 'PLAYER',
                    'CAPTAIN' => 'CAPTAIN',
                    'FAN' => 'FAN',
                ],
                'required' => false,
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'ACTIVE' => User::STATUS_ACTIVE,
                    'SUSPENDED' => User::STATUS_SUSPENDED,
                    'BANNED' => User::STATUS_BANNED,
                ],
                'required' => false,
            ])
            // champ non mappé: pour créer/modifier le password
            ->add('newPassword', PasswordType::class, [
                'mapped' => false,
                'required' => !$isEdit, // obligatoire seulement en création
                'constraints' => $isEdit ? [] : [
                    new Length(min: 6, minMessage: 'Password must be at least 6 characters.')
                ],
                'attr' => ['autocomplete' => 'new-password'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}
