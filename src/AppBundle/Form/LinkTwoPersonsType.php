<?php

namespace AppBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class LinkTwoPersonsType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('husband', EntityType::class, array(
                'label' => 'label.generic.person',
                'class' => 'AppBundle:Person',
                'required' => false,
                'choice_label' => function($person) {
                    return $person->getFullName();
                }
            ))
            ->add('wife', EntityType::class, array(
                'label' => 'label.generic.person',
                'class' => 'AppBundle:Person',
                'required' => false,
                'choice_label' => function($person) {
                    return $person->getFullName();
                }
            ))
            ->add('save', SubmitType::class, array(
                'label' => 'label.person.findLink'
            ))
        ;
    }
}
