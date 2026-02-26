<?php

namespace App\Form;

use App\Entity\Game;
use App\Entity\Tournament;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TournamentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du tournoi',
                'required' => false,
                'attr' => [
                    'class' => 'am-input',
                    'placeholder' => 'Ex: ArenaMind Cup — Valorant',
                ],
            ])
            ->add('game', EntityType::class, [
                'class' => Game::class,
                'choice_label' => 'name',
                'label' => 'Jeu',
                'required' => false,
                'placeholder' => '— Sélectionner —',
                'attr' => ['class' => 'am-input'],
            ])
            ->add('startAt', DateTimeType::class, [
                'label' => 'Début',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'am-input'],
            ])
            ->add('checkInAt', DateTimeType::class, [
                'label' => 'Check-in (optionnel)',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'am-input'],
            ])
            ->add('slots', IntegerType::class, [
                'label' => 'Slots',
                'required' => false,
                'attr' => [
                    'class' => 'am-input',
                    'min' => 1,
                    'max' => 128,
                    'placeholder' => 'Ex: 16',
                ],
            ])
            ->add('format', TextType::class, [
                'label' => 'Format',
                'required' => false,
                'attr' => [
                    'class' => 'am-input',
                    'placeholder' => 'Ex: 5v5, 1v1',
                ],
            ])
            ->add('prize', TextType::class, [
                'label' => 'Prix',
                'required' => false,
                'attr' => [
                    'class' => 'am-input',
                    'placeholder' => 'Ex: 500DT, points + badges',
                ],
            ])
            ->add('rules', TextType::class, [
                'label' => 'Règles',
                'required' => false,
                'attr' => [
                    'class' => 'am-input',
                    'placeholder' => 'Ex: Standard, Clash',
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'required' => false,
                'placeholder' => '— Choisir —',
                'choices' => [
                    'Scheduled' => 'Scheduled',
                    'Upcoming'  => 'Upcoming',
                    'Open'      => 'Open',
                    'Closed'    => 'Closed',
                    'Finished'  => 'Finished',
                ],
                'attr' => [
                    'class' => 'am-input',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tournament::class,
        ]);
    }
}