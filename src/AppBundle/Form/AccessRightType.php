<?php

namespace AppBundle\Form;

use AppBundle\Entity\AccessRight;
use AppBundle\Entity\FamilyTree;
use AppBundle\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class AccessRightType extends AbstractType
{
    private $trans;

    public function __construct(TranslatorInterface $trans)
    {
        $this->trans = $trans;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $accessTypes = array(
            $this->trans->trans('label.admin.read') => AccessRight::READ,
            $this->trans->trans('label.admin.write') => AccessRight::WRITE,
            $this->trans->trans('label.admin.delete') => AccessRight::DELETE,
        );

        $builder
            ->add('user', EntityType::class, array(
                'class' => User::class,
            ))
            ->add('familyTree', EntityType::class, array(
                'class' => FamilyTree::class,
            ))
            ->add('accessType', ChoiceType::class, array(
                'choices' => $accessTypes,
                'expanded' => false,
                'required' => true,
                'multiple' => false,
                'mapped' => true,
                'attr' => array('class' => 'select2'),
            ))
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => AccessRight::class,
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'appbundle_accessright';
    }
}
