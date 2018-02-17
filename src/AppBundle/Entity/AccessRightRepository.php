<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;

class AccessRightRepository extends EntityRepository
{
    public function checkAccessRight($familyTree, $user, $type)
    {
        $qb = $this->createQueryBuilder('ar');
        $qb->where('ar.user = :user')
            ->andWhere('ar.familyTree = :familyTree')
            ->setParameter('user', $user)
            ->setParameter('familyTree', $familyTree);

        $accessRights = $qb->getQuery()->getResult();

        foreach ($accessRights as $accessRight) {
            if ($accessRight->getAccessType() & $type) {
                return true;
            }
        }

        return false;
    }
}