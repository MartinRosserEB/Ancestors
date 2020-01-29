<?php

namespace App\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LinkTwoPersonsType extends AbstractType
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
                'label' => 'label.generic.person',
                'class' => 'App:Person',
                'required' => false,
                'query_builder' => function(EntityRepository $er) use ($user) {
                    return $er->queryPersonsWithReadAccessFor($user);
                },
                'choice_label' => function($person) {
                    return $person->getFullName();
                }
            ))
            ->add('wife', EntityType::class, array(
                'label' => 'label.generic.person',
                'class' => 'App:Person',
                'required' => false,
                'query_builder' => function(EntityRepository $er) use ($user) {
                    return $er->queryPersonsWithReadAccessFor($user);
                },
                'choice_label' => function($person) {
                    return $person->getFullName();
                }
            ))
            ->add('save', SubmitType::class, array(
                'label' => 'label.person.findLink',
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
