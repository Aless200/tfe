<?php

namespace App\Form;

use App\Entity\Comment;
use App\Entity\Gallery;
use App\Entity\News;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content',TextareaType::class, [
                'label' => 'Votre Commentaire',
                'attr' => ['class' => 'mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm',
                 'placeholder' => 'Partager votre avis...'
                ],
                'label_attr' => [
                    'class' => 'block mb-2 text-sm font-medium text-gray-900'
                ],
            ])
//            ->add('createdAt', null, [
//                'widget' => 'single_text',
//            ])
//            ->add('isPublished')
//            ->add('user', EntityType::class, [
//                'class' => User::class,
//                'choice_label' => 'id',
//            ])
//            ->add('news', EntityType::class, [
//                'class' => News::class,
//                'choice_label' => 'id',
//            ])
//            ->add('gallery', EntityType::class, [
//                'class' => Gallery::class,
//                'choice_label' => 'id',
//            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Comment::class,
        ]);
    }
}
