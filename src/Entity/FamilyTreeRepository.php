<?php

namespace App\Entity;

use Doctrine\ORM\EntityRepository;

class FamilyTreeRepository extends EntityRepository
{
    public function queryFamilyTreesWithReadAccessFor(User $user)
    {
        $qb = $this->createQueryBuilder('ft');

        $qb->leftJoin('ft.accessRights', 'ftar')
            ->where('ftar.user = :user')
            ->andWhere('BIT_AND(ftar.accessType, '.AccessRight::READ.') > 0')
            ->setParameter('user', $user);

        return $qb;
    }
}
