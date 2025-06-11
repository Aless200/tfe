<?php

namespace App\Form;

use App\Entity\Team;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class TeamType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('teamName', TextType::class, [
                'label' => 'Nom de l\'équipe',
                'attr' => [
                    'class' => 'mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm',
                ]
            ]);

        if ($options['is_admin']) {
            // Version admin - saisie manuelle des noms des joueurs
            $builder->add('player1', TextType::class, [
                'label' => 'Joueur 1',
                'mapped' => false,
                'attr' => ['class' => '...'],
                'constraints' => [new NotBlank()]
            ])
                ->add('player2', TextType::class, [
                    'label' => 'Joueur 2',
                    'mapped' => false,
                    'attr' => ['class' => '...'],
                    'constraints' => [new NotBlank()]
                ]);

            if (!$options['isDoublette']) {
                $builder->add('player3', TextType::class, [
                    'label' => 'Joueur 3',
                    'mapped' => false,
                    'attr' => ['class' => '...'],
                    'required' => false // Optionnel pour les triplettes
                ]);
            }
        } else {
            // Version normale - invitations par email
            $builder->add('emailInvitation1', EmailType::class, [
                'label' => 'Email Invitation 1',
                'mapped' => false,
                'attr' => [
                    'class' => 'mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm',
                ]
            ]);

            if (!$options['isDoublette']) {
                $builder->add('emailInvitation2', EmailType::class, [
                    'label' => 'Email Invitation 2',
                    'mapped' => false,
                    'attr' => [
                        'class' => 'mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm',
                    ],
                ]);
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Team::class,
            'isDoublette' => false,
            'is_admin' => false, // Nouvelle option
        ]);
    }
}

