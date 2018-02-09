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

        return $qb->getQuery()->getResult();
    }

    public function findOldestPerson()
    {
        $qb = $this->createQueryBuilder('p');

        return $qb->select('p')
            ->where('p.birthdate is not null')
            ->orderBy('p.birthdate', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findPersonFromPartialInfo(Person $entity)
    {
        $qb = $this->createQueryBuilder('p');
        if ($entity->getFamilyname()) {
            $qb->andWhere('p.familyname LIKE :familyname')
               ->setParameter('familyname', '%' . $entity->getFamilyname() . '%');
        }
        if ($entity->getFirstname()) {
            $qb->andWhere('p.firstname LIKE :firstname')
               ->setParameter('firstname', '%' . $entity->getFirstname() . '%');
        }
        if ($entity->getBirthdate()) {
            $qb->andWhere('p.birthdate = :birthdate')
               ->setParameter('birthdate', $entity->getBirthdate());
        }
        $qb->orderBy('p.birthdate', 'ASC');

        $result = $qb->getQuery()->getResult();
    }
}