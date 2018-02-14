<?php

namespace AppBundle\Service;

use AppBundle\Entity\Person;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * @author mrosser
 */
class PrepareRelations {
    private $em;

    public function __construct(ObjectManager $em)
    {
        $this->em = $em;
    }

    public function getMarriagesWithKidsFor(Person $person)
    {
        $marriagesWithKids = [];
        if ($person->getFemale()) {
            $marriedTo = $this->em->getRepository('AppBundle:PersonMarryPerson')
                ->findByWife($person->getId());
            if ($marriedTo) {
                foreach ($marriedTo as $marriedPerson) {
                    $husband = $marriedPerson->getHusband();
                    $marriagesWithKids[] = array(
                        'person' => $husband,
                        'kids' => $this->em->getRepository('AppBundle:Person')
                            ->findKidsByMotherAndFather($person, $husband),
                    );
                }
            }
        } else {
            $marriedTo = $this->em->getRepository('AppBundle:PersonMarryPerson')
                ->findByHusband($person->getId());
            if ($marriedTo) {
                foreach ($marriedTo as $marriedPerson) {
                    $wife = $marriedPerson->getWife();
                    $marriagesWithKids[] = array(
                        'person' => $wife,
                        'kids' => $this->em->getRepository('AppBundle:Person')
                            ->findKidsByMotherAndFather($wife, $person),
                    );
                }
            }
        }
        $kidsWithSingleParent = $this->em->getRepository('AppBundle:Person')->findKidsWithSingleParent($person);
        if (count($kidsWithSingleParent) > 0) {
            $marriagesWithKids[] = array(
                'person' => null,
                'kids' => $kidsWithSingleParent,
            );
        }

        return $marriagesWithKids;
    }

    public function getPersonsMarriedTo(Person $person)
    {
        $personsMarriedTo = [];
        if ($person->getFemale()) {
            $marriedTo = $this->em->getRepository('AppBundle:PersonMarryPerson')->findByWife($person->getId());
            if ($marriedTo) {
                foreach ($marriedTo as $marriedPerson) {
                    $personsMarriedTo[] = $marriedPerson->getHusband();
                }
            }
        } else {
            $marriedTo = $this->em->getRepository('AppBundle:PersonMarryPerson')->findByHusband($person->getId());
            if ($marriedTo) {
                foreach ($marriedTo as $marriedPerson) {
                    $personsMarriedTo[] = $marriedPerson->getWife();
                }
            }
        }

        return $personsMarriedTo;
    }

    public function getKids(Person $person)
    {
        $kids = [];
        if ($person->getFemale()) {
            $kids = $this->em->getRepository('AppBundle:Person')->findByMother($person->getId());
        } else {
            $kids = $this->em->getRepository('AppBundle:Person')->findByFather($person->getId());
        }

        return $kids;
    }
}
