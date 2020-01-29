<?php

namespace App\Service;

use App\Entity\Person;
use App\Entity\AccessRight;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @author mrosser
 */
class CheckAccess {
    private $tokenStorage;
    private $em;

    public function __construct(ObjectManager $em, TokenStorageInterface $tokenStorage)
    {
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
    }

    public function checkReadAccess(Person $person)
    {
        return $this->checkAccessType($person, AccessRight::READ);
    }

    public function checkWriteAccess(Person $person)
    {
        return $this->checkAccessType($person, AccessRight::WRITE);
    }

    public function checkDeleteAccess(Person $person)
    {
        return $this->checkAccessType($person, AccessRight::DELETE);
    }

    private function checkAccessType(Person $person, $type)
    {
        $familyTrees = $person->getFamilyTrees();
        $user = $this->tokenStorage->getToken()->getUser();

        $accessGranted = false;
        foreach ($familyTrees as $familyTree) {
            if ($this->em->getRepository('App:AccessRight')->checkAccessRight($familyTree, $user, $type)) {
                $accessGranted = true;
            }
        }

        return $accessGranted;
    }
}
