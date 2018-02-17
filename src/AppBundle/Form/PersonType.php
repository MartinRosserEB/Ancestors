<?php

namespace AppBundle\Form;

use AppBundle\Entity\FamilyTreeRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class PersonType extends AbstractType
{
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
            ->add('female', CheckboxType::class, array(
                'label' => 'label.generic.female',
                'required' => false,
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
                'class' => 'AppBundle:FamilyTree',
                'required' => true,
                'query_builder' => function(FamilyTreeRepository $er) use ($user) {
                    return $er->queryFamilyTreesWithReadAccessFor($user);
                }
            ))
            ->add('father', EntityType::class, array(
                'label' => 'label.generic.father',
                'class' => 'AppBundle:Person',
                'query_builder' => function(EntityRepository $repository) {
                    $qb = $repository->createQueryBuilder('u');
                    return $qb
                        ->where($qb->expr()->eq('u.female', ':female'))
                        ->setParameter('female', 'true')
                        ->orderBy('u.firstname', 'ASC')
                    ;
                },
                'choice_label' => function($person) {
                    return $person->getFullName();
                },
                'required' => false
            ))
            ->add('mother', EntityType::class, array(
                'label' => 'label.generic.mother',
                'class' => 'AppBundle:Person',
                'query_builder' => function(EntityRepository $repository) {
                    $qb = $repository->createQueryBuilder('u');
                    return $qb
                        ->where($qb->expr()->neq('u.female', ':female'))
                        ->setParameter('female', 'true')
                        ->orderBy('u.firstname', 'ASC')
                    ;
                },
                'choice_label' => function($person) {
                    return $person->getFullName();
                },
                'required' => false
            ))
            ->add('twin', EntityType::class, array(
                'label' => 'label.generic.twin',
                'class' => 'AppBundle:Person',
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
