<?php

namespace AppBundle\Service;

use AppBundle\Helper\DisplayNode;

/**
 * Description of FindLink
 *
 * @author mrosser
 */
class FindLink {
    private $em;

    public function __construct ($em)
    {
        $this->em = $em;
    }

    public function getLinkBetweenTwoPersons($p1ID, $p2ID)
    {
        $person1 = $this->em->getRepository('AppBundle:Person')->findOneById($p1ID);
        $person2 = $this->em->getRepository('AppBundle:Person')->findOneById($p2ID);
        if (!$person1 || !$person2) {
            return false;
        }
        $hierarchyPerson1 = array();
        $this->recursivelyGetFatherAndMother($hierarchyPerson1, $person1);
        $hierarchyPerson2 = array();
        $this->recursivelyGetFatherAndMother($hierarchyPerson2, $person2);

        $resultArray = $this->findShortestPath($person1, $person2, $hierarchyPerson1, $hierarchyPerson2);

        $curNode = null;
        foreach ($resultArray['res'][$resultArray['minD']['id']][1] as $tmpPersonP1) {
            $curNode = $this->checkIfExistsOrCreateDisplayNode($curNode, $tmpPersonP1['person']);
        }
        $curNode = $curNode->traverseParentToTop();
        foreach (array_slice($resultArray['res'][$resultArray['minD']['id']][3], 1) as $tmpPersonP2) {
            $curNode = $this->checkIfExistsOrCreateDisplayNode($curNode, $tmpPersonP2['person']);
        }

        return $curNode->traverseParentToTop();
    }

    private function findShortestPath($person1, $person2, $hierarchyPerson1, $hierarchyPerson2)
    {
        $samePersonIDs = array_intersect(array_keys($hierarchyPerson1), array_keys($hierarchyPerson2));
        if (count($samePersonIDs) === 0) {
            return false;
        }
        $minDistance = array(
            'id' => 0,
            'distance' => 9000,
        );
        $resultArray = array();
        foreach ($samePersonIDs as $samePersonID) {
            $p1LinkArray = array();
            $p2LinkArray = array();
            $p1Dist = $this->getMinDistance($hierarchyPerson1, $person1->getID(), $samePersonID, $p1LinkArray) - 1;
            $p2Dist = $this->getMinDistance($hierarchyPerson2, $person2->getID(), $samePersonID, $p2LinkArray) - 1;
            if ($p1Dist + $p2Dist < $minDistance['distance']) {
                $minDistance['distance'] = $p1Dist + $p2Dist;
                $minDistance['id'] = $samePersonID;
            }
            $resultArray[$samePersonID] = array(
                $p1Dist,
                $p1LinkArray,
                $p2Dist,
                $p2LinkArray,
            );
        }
        return array(
            'res' => $resultArray,
            'minD' => $minDistance
        );
    }

    private function checkIfExistsOrCreateDisplayNode($parentNode, $curPerson)
    {
        $childNode = false;
        if ($parentNode) {
            $childNode = $parentNode->getChildBasedOnPerson($curPerson);
        } else {
            return new DisplayNode($curPerson);
        }
        if ($childNode) {
            return $childNode;
        } else {
            return $parentNode->addChildPersonCreateDisplayNode($curPerson);
        }
    }

    /**
     * Function computes distance from a person (ID) to a destination person
     * (ID). Persons and relations are looked up in the ancestorArray.
     * The dependency is written in the link array.
     *
     * @param type $ancestorArray
     * @param type $curPersonID
     * @param type $pIntersectID
     * @param type $linkArray
     * @return int
     */
    private function getMinDistance($ancestorArray, $curPersonID, $pIntersectID, &$linkArray)
    {
        $distance = 0;

        $curPersonRef = $ancestorArray[$curPersonID];
        if ($curPersonRef['person']->getID() !== $pIntersectID) {
            if (array_key_exists('mother', $curPersonRef)) {
                $distance = $this->getMinDistance($ancestorArray, $curPersonRef['mother'], $pIntersectID, $linkArray);
                if ($distance > 0) {
                    $linkArray[] = $curPersonRef;
                    $distance++;
                    return $distance;
                }
            }
            if (array_key_exists('father', $curPersonRef)) {
                $distance += $this->getMinDistance($ancestorArray, $curPersonRef['father'], $pIntersectID, $linkArray);
                if ($distance > 0) {
                    $linkArray[] = $curPersonRef;
                    $distance++;
                    return $distance;
                }
            }
            return 0;
        } else {
            $linkArray[] = $curPersonRef;
            return 1;
        }
    }

    /**
     * Function builds up an array with all ancestors for a person and
     * corresponding relationships.
     *
     * Structure:
     * - person Person
     * - father Person->ID
     * - mother Person->ID
     * 
     * @param type $em
     * @param type $person
     * @return type
     */
    private function recursivelyGetFatherAndMother(&$mainArray, $person) {
        $resArray = array();
        $resArray['person'] = $person;
        $father = $person->getFather();
        $mother = $person->getMother();
        if ($father) {
            $resArray['father'] = $father->getId();
            $this->recursivelyGetFatherAndMother($mainArray, $father);
        }
        if ($mother) {
            $resArray['mother'] = $mother->getId();
            $this->recursivelyGetFatherAndMother($mainArray, $mother);
        }
        $mainArray[$person->getId()] = $resArray;
    }
}
