<?php

namespace App\Form;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use App\Entity\ShopProduct;
use App\Entity\Game;
use App\Entity\Size;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\All;

class ShopProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('price', NumberType::class)
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Merchandising' => 'merch',
                    'Skin (jeu)' => 'skin',
                ],
            ])
            ->add('game', EntityType::class, [
                'class' => Game::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Aucun (Merch)',
            ])
            ->add('sizes', EntityType::class, [
    'class' => Size::class,
    'choice_label' => 'name',
    'multiple' => true,
    'expanded' => true,
    'required' => false,
])
            ->add('image', FileType::class, [   // ✅ image principale
        'label' => 'Image principale du produit',
        'mapped' => false,
        'required' => true,
        'constraints' => [
            new File([
                'maxSize' => '2M',
                'mimeTypes' => ['image/jpeg','image/png','image/webp'],
                'mimeTypesMessage' => 'Téléchargez une image valide (JPEG, PNG, WEBP)',
            ])
        ],
    ])
            ->add('imagesFiles', FileType::class, [
    'label' => 'Autres images du produit (PNG, JPG)',
    'mapped' => false,
    'multiple' => true,
    'required' => false,
    'constraints' => [
        new All([
            'constraints' => [
                new File([
                    'maxSize' => '2M',
                    'mimeTypes' => [
                        'image/jpeg',
                        'image/png',
                        'image/webp',
                    ],
                    'mimeTypesMessage' => 'Veuillez télécharger des images valides (JPEG, PNG, WEBP)',
                ])
            ]
        ])
    ],
]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ShopProduct::class,
        ]);
    }
}
