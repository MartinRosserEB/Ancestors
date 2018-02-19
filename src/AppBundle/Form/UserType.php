<?php

namespace AppBundle\Form;

use AppBundle\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('enabled', CheckboxType::class, array(
                'label' => 'label.admin.enabled',
            ))
            ->add('username', TextType::class, array(
                'label' => 'label.admin.username',
            ))
            ->add('email', TextType::class, array(
                'label' => 'label.admin.email',
            ))
            ->add('roles')
            ->add('familyTrees', EntityType::class, array(
                'label' => 'label.admin.familyTrees',
                'multiple' => true,
                'class' => 'AppBundle:FamilyTree',
            ))
            ->add('save', SubmitType::class, array(
                'label' => 'label.submit',
                'attr' => array(
                    'class' => 'button',
                ),
            ))
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data-class' => User::class,
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'appbundle_user';
    }
}
