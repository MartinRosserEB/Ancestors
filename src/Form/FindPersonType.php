<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class FindPersonType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('familyname', TextType::class, array(
                'label' => 'label.generic.familyname',
                'required' => false,
            ))
            ->add('firstname', TextType::class, array(
                'label' => 'label.generic.firstname',
                'required' => false,
            ))
            ->add('birthdate', DateType::class, array(
                'label' => 'label.generic.birthdate',
                'widget' => 'single_text',
                'required' => false,
            ))
            ->add('save', SubmitType::class, array(
                'label' => 'label.nav.person.find',
                'attr' => array(
                    'class' => 'button',
                ),
            ))
        ;
    }
}
