<?php

namespace AppBundle\Service;

use AppBundle\Entity\Person;
use AppBundle\Entity\PersonMarryPerson;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

/**
 * @author mrosser
 */
class PreFill {
    public function returnPreFilledPerson(Person $person1, Person $person2)
    {
        $entity = new Person;
        if ($person1->getFemale() && !$person2->getFemale()) {
            $entity->setMother($person1);
            $entity->setFather($person2);
            $entity->setFamilyname($person2->getFamilyname());
        } else if (!$person1->getFemale() && $person2->getFemale()) {
            $entity->setMother($person2);
            $entity->setFather($person1);
            $entity->setFamilyname($person1->getFamilyname());
        }
        return $entity;
    }

    /**
     * @return PersonMarryPerson
     */
    public function setWifeOrHusband(Person $person)
    {
        $entity = new PersonMarryPerson;

        if ($person->getFemale()) {
            $entity->setWife($person);
        } else {
            $entity->setHusband($person);
        }

        return $entity;
    }

    /**
     * @return PersonMarryPerson
     */
    public function setWifeAndHusband(Person $person1, Person $person2)
    {
        $entity = new PersonMarryPerson;

        if ($person1->getFemale() && !$person2->getFemale()) {
            $entity->setWife($person1);
            $entity->setHusband($person2);
        } elseif ($person2->getFemale() && !$person1->getFemale()) {
            $entity->setWife($person2);
            $entity->setHusband($person1);
        } else {
            throw new PreconditionFailedHttpException('label.persons.not.male.and.female');
        }

        return $entity;
    }
}
