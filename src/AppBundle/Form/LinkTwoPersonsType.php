<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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
