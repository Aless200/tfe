<?php

namespace App\Form;

use App\Entity\Tournament;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class TournamentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'class' => 'mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 sm:text-sm' . ($options['attr']['class_error'] ?? ''),
                ]])
            ->add('dateTournament', DateTimeType::class, [
                'widget' => 'single_text',
                'html5' => true,
//                'format' => 'yyyy-MM-dd HH:mm',
                'attr' => [
                    'class' => 'mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm',
            ]])
            ->add('price', MoneyType::class, [
                'label' => 'Prix',
                'attr' => [
                    'class' => 'mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 sm:text-sm' . ($options['attr']['class_error'] ?? ''),
            ]])
            ->add('adresse', TextType::class, [
                'label' => 'Adresse',
                'attr' => [
                    'class' => 'mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm',
            ]])
            ->add('teamsMax', NumberType::class, [
                'label' => 'Nombre d\'équipe max',
                'attr' => [
                    'class' => 'mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm',
            ]])
//            ->add('isPublished')
            ->add('groundMax', NumberType::class, [
                'label' => 'Nombre de terrains max',
                'attr' => [
                    'class' => 'mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm',
            ]])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices'  => [
                    'Prochainement' => 'prochainement',
                    'En cours'     => 'en cours',
                    'Terminé'      => 'terminé',
                    'Annuler' => 'annuler',
                ],
                'placeholder' => 'Choisissez un statut',
                'multiple' => false,  // menu déroulant classique
                'expanded' => false,
                'attr' => [
                    'class' => 'mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm',
                ]])
            ->add('typeTournament', ChoiceType::class, [
                'label' => 'Type de tournois',
                'choices'  => [
                    'Doublette' => 'doublette',
                    'Triplette' => 'triplette',
                ],
                'placeholder' => 'Choisissez un type de tournois',
                'multiple' => false,
                'expanded' => false,
                'attr' => [
                    'class' => 'mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm',
                ]])
//            ->add('CreatedAt', null, [
//                'widget' => 'single_text',
//            ])
            ->add('imageFile', VichImageType::class, [
                'label' => 'Image',
                'required' => false,
                'allow_delete' => false,
                'download_uri' => false,
                'image_uri' => false,
                'asset_helper' => true,
                'attr' => [
                    'class' => 'mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm',
                    'data-turbo' => false,
                ],
            ]);
//            ->add('updatedAt', null, [
//                'widget' => 'single_text',
//            ])

        $builder->get('status')
            ->addModelTransformer(new CallbackTransformer(
                function ($statusAsArray) {
                    // Transformation de la base vers le formulaire
                    // Si la donnée est un tableau, on renvoie le premier élément, sinon on renvoie null
                    return is_array($statusAsArray) && count($statusAsArray) ? $statusAsArray[0] : null;
                },
                function ($statusAsString) {
                    // Transformation du formulaire vers la base de données
                    return [$statusAsString];
                }
            ));

        $builder->get('typeTournament')
            ->addModelTransformer(new CallbackTransformer(
                function ($typeAsArray) {
                    return is_array($typeAsArray) && count($typeAsArray) ? $typeAsArray[0] : null;
                },
                function ($typeAsString) {
                    return [$typeAsString];
                }
            ));


    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tournament::class,
        ]);
    }
}
