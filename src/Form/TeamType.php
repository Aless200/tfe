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
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
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
                'attr' => ['class' => 'mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm'], // Ajoutez vos classes CSS ici
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez remplir le nom du joueur 1.'
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'Le nom doit contenir au minimum {{ limit }} caractères.',
                        'maxMessage' => 'Le nom ne doit pas dépasser {{ limit }} caractères.'
                    ])
                ]
            ])
                ->add('player2', TextType::class, [
                    'label' => 'Joueur 2',
                    'mapped' => false,
                    'attr' => ['class' => 'mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm'
                    ],
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Veuillez remplir le nom du joueur 2.'
                        ]),
                        new Length([
                            'min' => 2,
                            'max' => 255,
                            'minMessage' => 'Le nom doit contenir au minimum {{ limit }} caractères.',
                            'maxMessage' => 'Le nom ne doit pas dépasser {{ limit }} caractères.'
                        ])
                    ]
                ]);

            if (!$options['isDoublette']) {
                $builder->add('player3', TextType::class, [
                    'label' => 'Joueur 3',
                    'mapped' => false,
                    'attr' => ['class' => 'mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm'],
                    'required' => false, // Optionnel pour les triplettes
                    'constraints' => [
                        new Length([
                            'min' => 2,
                            'max' => 255,
                            'minMessage' => 'Le nom doit contenir au minimum {{ limit }} caractères.',
                            'maxMessage' => 'Le nom ne doit pas dépasser {{ limit }} caractères.'
                        ])
                    ]
                ]);
            }
        } else {
            // Version normale - invitations par email
            $builder->add('emailInvitation1', EmailType::class, [
                'label' => 'Email Invitation 1',
                'mapped' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer une adresse email pour l\'invitation 1.',
                    ]),
                    new Email([
                        'message' => 'L\'adresse email pour l\'invitation 1 n\'est pas valide.',
                    ]),
                ],
                'attr' => [
                    'class' => 'mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm',
                ]
            ]);

            if (!$options['isDoublette']) {
                $builder->add('emailInvitation2', EmailType::class, [
                    'label' => 'Email Invitation 2',
                    'mapped' => false,
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Veuillez entrer une adresse email pour l\'invitation 2.',
                        ]),
                        new Email([
                            'message' => 'L\'adresse email pour l\'invitation 2 n\'est pas valide.',
                        ]),
                    ],
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

