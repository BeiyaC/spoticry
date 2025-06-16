<?php

namespace App\Form;

use App\Entity\Article;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Titre de l\'article',
                ],
            ])
            ->add('artistName', TextType::class, [
                'label' => 'Nom de l\'artiste',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Nom de l\'artiste',
                    'data-spotify-search' => 'true',
                ],
            ])
            ->add('excerpt', TextareaType::class, [
                'label' => 'Extrait',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Court résumé de l\'article (optionnel)',
                ],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Contenu',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 15,
                    'data-editor' => 'true',
                ],
            ])
            ->add('tags', CollectionType::class, [
                'entry_type' => TextType::class,
                'label' => 'Tags',
                'allow_add' => true,
                'allow_delete' => true,
                'required' => false,
                'attr' => [
                    'class' => 'tags-collection',
                ],
                'entry_options' => [
                    'attr' => ['class' => 'form-control mb-2'],
                ],
            ])
            ->add('isPublished', CheckboxType::class, [
                'label' => 'Publier l\'article',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ])
            ->add('updateSpotifyData', CheckboxType::class, [
                'label' => 'Mettre à jour les données Spotify',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
        ]);
    }
}
