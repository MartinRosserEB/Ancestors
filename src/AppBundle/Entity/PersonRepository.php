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

    public function findKidsWithSingleParent(Person $person)
    {
        $qb = $this->createQueryBuilder('p');
        if ($person->getFemale()) {
            $qb->where('p.mother = :person')
                ->andWhere($qb->expr()->isNull('p.father'));
        } else {
            $qb->where('p.father = :person')
                ->andWhere($qb->expr()->isNull('p.mother'));
        }
        $qb->orderBy('p.birthdate', 'ASC')
            ->setParameter('person', $person);

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

        return $qb->getQuery()->getResult();
    }

    public function queryMalePersonsWithReadAccessFor(User $user)
    {
        $qb = $this->createQueryBuilder('u');

        $this->addAccessCheck($qb, $user);

        $qb->andWhere('u.female = 0')
            ->orderBy('u.firstname', 'ASC');

        return $qb;
    }

    public function queryFemalePersonsWithReadAccessFor(User $user)
    {
        $qb = $this->createQueryBuilder('u');

        $this->addAccessCheck($qb, $user);

        $qb->andWhere('u.female = 1')
            ->orderBy('u.firstname', 'ASC');

        return $qb;
    }

    public function queryPersonsWithReadAccessFor(User $user)
    {
        $qb = $this->createQueryBuilder('u');

        $this->addAccessCheck($qb, $user);

        $qb->orderBy('u.firstname', 'ASC');

        return $qb;
    }

    private function addAccessCheck($qb, User $user)
    {
        $qb->leftJoin('u.familyTrees', 'uft')
            ->leftJoin('uft.accessRights', 'uftar')
            ->andWhere('uftar.user = :user')
            ->andWhere('BIT_AND(uftar.accessType, '.AccessRight::READ.') > 0')
            ->setParameter('user', $user);
    }
}