<?php

namespace App\Entity;

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

    public function findPersonFromPartialInfoWithReadAccessCheck(Person $entity, User $user)
    {
        $qb = $this->createQueryBuilder('p');
        $this->addAccessCheck($qb, $user);
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
        $qb = $this->createQueryBuilder('p');

        $this->addAccessCheck($qb, $user);

        $qb->andWhere('p.female = 0')
            ->orderBy('p.firstname', 'ASC');

        return $qb;
    }

    public function queryFemalePersonsWithReadAccessFor(User $user)
    {
        $qb = $this->createQueryBuilder('p');

        $this->addAccessCheck($qb, $user);

        $qb->andWhere('p.female = 1')
            ->orderBy('p.firstname', 'ASC');

        return $qb;
    }

    public function queryPersonsWithReadAccessFor(User $user)
    {
        $qb = $this->createQueryBuilder('p');

        $this->addAccessCheck($qb, $user);

        $qb->orderBy('p.firstname', 'ASC');

        return $qb;
    }

    private function addAccessCheck($qb, User $user)
    {
        $qb->leftJoin('p.familyTrees', 'pft')
            ->leftJoin('pft.accessRights', 'pftar')
            ->andWhere('pftar.user = :user')
            ->andWhere('BIT_AND(pftar.accessType, '.AccessRight::READ.') > 0')
            ->setParameter('user', $user);
    }
}