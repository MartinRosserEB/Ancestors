<?php

namespace App\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PersonMarryPersonType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $user = $options['user'];

        $builder
            ->add('husband', EntityType::class, array(
                'label' => 'label.generic.husband',
                'class' => 'App:Person',
                'query_builder' => function(EntityRepository $er) use ($user) {
                    return $er->queryMalePersonsWithReadAccessFor($user);
                },
                'choice_label' => function($person) {
                    return $person->getFullName();
                }
            ))
            ->add('wife', EntityType::class, array(
                'label' => 'label.generic.wife',
                'class' => 'App:Person',
                'query_builder' => function(EntityRepository $er) use ($user) {
                    return $er->queryFemalePersonsWithReadAccessFor($user);
                },
                'choice_label' => function($person) {
                    return $person->getFullName();
                }
            ))
            ->add('marriage', DateType::class, array(
                'label' => 'label.generic.marriageDate',
                'widget' => 'single_text',
                'required' => false,
            ))
            ->add('divorce', DateType::class, array(
                'label' => 'label.generic.divorceDate',
                'widget' => 'single_text',
                'required' => false,
            ))
            ->add('save', SubmitType::class, array(
                'label' => 'label.person.marry',
                'attr' => array(
                    'class' => 'button',
                ),
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('user');
    }
}
