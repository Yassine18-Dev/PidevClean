<?php

namespace App\Form;

use App\Entity\Promotion;
use App\Entity\ShopProduct;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class PromotionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la promotion',
                'attr' => [
                    'placeholder' => 'Ex: Promotion d\'été, Soldes hiver...',
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le nom de la promotion est obligatoire']),
                    new \Symfony\Component\Validator\Constraints\Length([
                        'min' => 3,
                        'max' => 100,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('code', TextType::class, [
                'label' => 'Code de promotion',
                'attr' => [
                    'placeholder' => 'Ex: ETE2024, HIVER25...',
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le code de promotion est obligatoire']),
                    new \Symfony\Component\Validator\Constraints\Length([
                        'min' => 2,
                        'max' => 20,
                        'minMessage' => 'Le code doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le code ne peut pas dépasser {{ limit }} caractères'
                    ]),
                    new \Symfony\Component\Validator\Constraints\Regex([
                        'pattern' => '/^[A-Z0-9_]+$/',
                        'message' => 'Le code ne peut contenir que des lettres majuscules, des chiffres et des underscores'
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description (optionnel)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Décrivez les conditions de cette promotion...',
                    'rows' => 3,
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\Length([
                        'max' => 500,
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de réduction',
                'choices' => [
                    'Pourcentage (%)' => 'percentage',
                    'Montant fixe (€)' => 'fixed_amount',
                    'Achat X, obtient Y' => 'buy_x_get_y'
                ],
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le type de promotion est obligatoire'])
                ]
            ])
            ->add('value', NumberType::class, [
                'label' => 'Valeur de la réduction',
                'attr' => [
                    'placeholder' => 'Ex: 15 pour 15% ou 10 pour 10€',
                    'class' => 'form-control',
                    'step' => '0.01'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'La valeur de la réduction est obligatoire']),
                    new Positive(['message' => 'La valeur doit être positive']),
                    new Range([
                        'min' => 0.01,
                        'max' => 100,
                        'notInRangeMessage' => 'La valeur doit être entre {{ min }} et {{ max }}'
                    ])
                ]
            ])
            ->add('minAmount', NumberType::class, [
                'label' => 'Montant minimum d\'achat (€)',
                'required' => false,
                'attr' => [
                    'placeholder' => '0 si pas de minimum',
                    'class' => 'form-control',
                    'step' => '0.01'
                ],
                'constraints' => [
                    new Positive(['message' => 'Le montant minimum doit être positif ou nul'])
                ]
            ])
            ->add('startDate', DateTimeType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'La date de début est obligatoire']),
                    new GreaterThanOrEqual([
                        'value' => 'today',
                        'message' => 'La date de début ne peut pas être dans le passé'
                    ])
                ]
            ])
            ->add('endDate', DateTimeType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'La date de fin est obligatoire']),
                    new GreaterThanOrEqual([
                        'propertyPath' => 'parent.all[startDate].data',
                        'message' => 'La date de fin doit être après la date de début'
                    ])
                ]
            ])
            ->add('isActive', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Active' => true,
                    'Inactive' => false
                ],
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('isPublic', ChoiceType::class, [
                'label' => 'Visibilité',
                'choices' => [
                    'Publique (visible par tous)' => true,
                    'Privée (admin seulement)' => false
                ],
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('applyToAllProducts', ChoiceType::class, [
                'label' => 'Application',
                'choices' => [
                    'Tous les produits' => true,
                    'Produits sélectionnés' => false
                ],
                'attr' => [
                    'class' => 'form-control',
                    'onchange' => 'toggleProductSelection()'
                ]
            ])
            ->add('products', EntityType::class, [
                'label' => 'Produits concernés',
                'class' => ShopProduct::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'size' => 8
                ],
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\Count([
                        'min' => 1,
                        'minMessage' => 'Vous devez sélectionner au moins un produit si vous n\'appliquez pas la promotion à tous les produits'
                    ])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Promotion::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'promotion_form',
        ]);
    }
}
