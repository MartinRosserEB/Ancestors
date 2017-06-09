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

class PersonMarryPersonType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('husband', EntityType::class, array(
                'label' => 'label.generic.husband',
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
                }
            ))
            ->add('wife', EntityType::class, array(
                'label' => 'label.generic.wife',
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
                'label' => 'label.person.marry'
            ))
        ;
    }
}
