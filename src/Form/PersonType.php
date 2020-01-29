<?php

namespace App\Form;

use App\Entity\FamilyTreeRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Translation\TranslatorInterface;

class PersonType extends AbstractType
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
        $user = $options['user'];

        $builder
            ->add('familyname', TextType::class, array(
                'label' => 'label.generic.familyname',
            ))
            ->add('firstname', TextType::class, array(
                'label' => 'label.generic.firstname',
            ))
            ->add('female', ChoiceType::class, array(
                'label' => 'label.generic.sex',
                'required' => true,
                'choices' => array(
                    $this->trans->trans('label.generic.male') => false,
                    $this->trans->trans('label.generic.female') => true,
                ),
                'placeholder' => 'label.generic.choose.option',
            ))
            ->add('birthdate', DateType::class, array(
                'widget' => 'single_text',
                'required' => false,
                'label' => 'label.generic.birthdate',
            ))
            ->add('deathdate', DateType::class, array(
                'label' => 'label.generic.deathdate',
                'widget' => 'single_text',
                'required' => false,
            ))
            ->add('familyTrees', EntityType::class, array(
                'label' => 'label.familyTree',
                'multiple' => true,
                'class' => 'App:FamilyTree',
                'required' => true,
                'query_builder' => function(FamilyTreeRepository $er) use ($user) {
                    return $er->queryFamilyTreesWithReadAccessFor($user);
                }
            ))
            ->add('father', EntityType::class, array(
                'label' => 'label.generic.father',
                'class' => 'App:Person',
                'query_builder' => function(EntityRepository $er) use ($user) {
                    return $er->queryMalePersonsWithReadAccessFor($user);
                },
                'choice_label' => function($person) {
                    return $person->getFullName();
                },
                'required' => false
            ))
            ->add('mother', EntityType::class, array(
                'label' => 'label.generic.mother',
                'class' => 'App:Person',
                'query_builder' => function(EntityRepository $er) use ($user) {
                    return $er->queryFemalePersonsWithReadAccessFor($user);
                },
                'choice_label' => function($person) {
                    return $person->getFullName();
                },
                'required' => false
            ))
            ->add('twin', EntityType::class, array(
                'label' => 'label.generic.twin',
                'class' => 'App:Person',
                'query_builder' => function(EntityRepository $er) use ($user) {
                    return $er->queryPersonsWithReadAccessFor($user);
                },
                'choice_label' => function($person) {
                    return $person->getFullName();
                },
                'required' => false
            ))
            ->add('comment', TextareaType::class, array(
                'label' => 'label.generic.comment',
                'required' => false,
            ))
            ->add('save', SubmitType::class, array(
                'label' => 'label.person.create',
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
