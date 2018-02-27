<?php

namespace AppBundle\Form;

use AppBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
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
        $roles = $options['roles'];

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
            ->add('roles', ChoiceType::class, array(
                'choices' => $roles,
                'label' => 'label.admin.roles',
                'expanded' => false,
                'required' => false,
                'multiple' => true,
                'mapped' => true,
                'attr' => array('class' => 'select2'),
            ))
            ->add('accessRights', CollectionType::class, array(
                'entry_type' => AccessRightType::class,
                'entry_options' => array(
                    'label' => 'label.admin.accessRights',
                ),
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
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
            'data_class' => User::class,
            'roles' => array(),
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
