<?php

namespace App\Form;

use App\Entity\Ticket;
use App\Entity\TicketCategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TicketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('subject', TextType::class , [
            'label' => 'Subject',
            'attr' => [
                'placeholder' => 'Brief summary of your issue...',
                'class' => 'form-input',
            ],
        ])
            ->add('category', EntityType::class , [
            'class' => TicketCategory::class ,
            'choice_label' => 'name',
            'label' => 'Category',
            'placeholder' => 'Select a category',
            'attr' => ['class' => 'form-input'],
        ])
            ->add('description', TextareaType::class , [
            'label' => 'Description',
            'attr' => [
                'placeholder' => 'Describe your issue in detail...',
                'rows' => 6,
                'class' => 'form-input',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ticket::class ,
        ]);
    }
}