<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;

class PersonRepository extends EntityRepository
{
    public function findKidsByMotherAndFather(Person $mother, Person $father)
    {
        $qb = $this->createQueryBuilder('p');
        $qb->where('p.mother = :mother')
            ->andWhere('p.father = :father')
            ->orderBy('p.birthdate', 'ASC')
            ->setParameter('father', $father)
            ->setParameter('mother', $mother);
dump($qb->getQuery()    , $qb, $qb->getQuery()->getResult());
        return $qb->getQuery()->getResult();
    }
}