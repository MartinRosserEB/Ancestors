<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;

class PersonMarryPersonRepository extends EntityRepository
{
    public function findMarriageBetween(Person $person1, Person $person2)
    {
        if ($person1->getFemale() && !$person2->getFemale()) {
            $wife = $person1;
            $husband = $person2;
        } else if (!$person1->getFemale() && $person2->getFemale()) {
            $wife = $person2;
            $husband = $person1;
        } else {
            return null;
            // Should do handling of same sex marriage
        }

        $qb = $this->createQueryBuilder('pmp');
        $qb->where('pmp.husband = :husband')
           ->setParameter('husband', $husband)
           ->andWhere('pmp.wife = :wife')
           ->setParameter('wife', $wife);

        return $qb->getQuery()->getOneOrNullResult();
    }
}