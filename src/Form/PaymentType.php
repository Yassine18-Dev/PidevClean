<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints as Assert;

class PaymentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cardNumber', TextType::class, [
                'label' => 'Numéro de carte',
                'attr' => [
                    'placeholder' => '1234 5678 9012 3456',
                    'maxlength' => 19,
                    'class' => 'form-control',
                    'inputmode' => 'numeric'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le numéro de carte est obligatoire']),
                    new Assert\Length(['min' => 13, 'max' => 19, 'exactMessage' => 'Le numéro de carte doit contenir entre 13 et 19 chiffres']),
                    new Assert\Regex([
                        'pattern' => '/^[0-9]{13,19}$/',
                        'message' => 'Le numéro de carte ne doit contenir que des chiffres'
                    ])
                ]
            ])
            ->add('cardHolder', TextType::class, [
                'label' => 'Nom du titulaire',
                'attr' => [
                    'placeholder' => 'MOATEZ BEN SALEM',
                    'maxlength' => 50,
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom du titulaire est obligatoire']),
                    new Assert\Length(['min' => 3, 'max' => 50, 'exactMessage' => 'Le nom doit contenir entre 3 et 50 caractères']),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-Z\s\-]+$/',
                        'message' => 'Le nom ne peut contenir que des lettres, espaces et tirets'
                    ])
                ]
            ])
            ->add('expiryDate', TextType::class, [
                'label' => 'Date d\'expiration',
                'attr' => [
                    'placeholder' => 'MM/AA',
                    'maxlength' => 5,
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date d\'expiration est obligatoire']),
                    new Assert\Regex([
                        'pattern' => '/^(0[1-9]|1[0-2])\/([0-9]{2})$/',
                        'message' => 'Format invalide. Utilisez MM/AA (ex: 08/25)'
                    ])
                ]
            ])
            ->add('cvv', TextType::class, [
                'label' => 'CVC',
                'attr' => [
                    'placeholder' => '123',
                    'maxlength' => 4,
                    'class' => 'form-control',
                    'inputmode' => 'numeric'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le CVC est obligatoire']),
                    new Assert\Length(['min' => 3, 'max' => 4, 'exactMessage' => 'Le CVC doit contenir entre 3 et 4 chiffres']),
                    new Assert\Regex([
                        'pattern' => '/^[0-9]{3,4}$/',
                        'message' => 'Le CVC ne doit contenir que des chiffres'
                    ])
                ]
            ])
            ->add('amount', HiddenType::class, [
                'label' => 'Montant',
                'attr' => ['class' => 'payment-amount']
            ])
            ->add('saveCard', CheckboxType::class, [
                'label' => 'Enregistrer cette carte pour les prochains paiements',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('terms', CheckboxType::class, [
                'label' => 'J\'accepte les conditions générales de vente',
                'attr' => ['class' => 'form-check-input'],
                'constraints' => [
                    new Assert\IsTrue([
                        'message' => 'Vous devez accepter les conditions générales de vente'
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => ['class' => 'payment-form'],
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'payment_form',
        ]);
    }
}
