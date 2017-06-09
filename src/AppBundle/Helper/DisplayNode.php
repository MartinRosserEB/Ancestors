<?php

namespace AppBundle\Helper;

/**
 * Helper class to allow display of family tree in various output formats
 */
class DisplayNode {
    private $person;

    private $parent;
    private $spouse;
    private $children;

    public function __construct($person)
    {
        $this->person = $person;
        $this->children = array();
    }

    public function setSpouse($spouse)
    {
        $this->spouse = $spouse;
    }

    public function getPerson()
    {
        return $this->person;
    }

    public function addChildPersonCreateDisplayNode($person)
    {
        $newChildNode = new DisplayNode($person);
        $this->children[] = $newChildNode;
        $newChildNode->setParent($this);
        return $newChildNode;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    public function traverseParentToTop()
    {
        if ($this->parent) {
            return $this->parent->traverseParentToTop();
        } else {
            return $this;
        }
    }

    public function getChildBasedOnPerson($person) {
        foreach ($this->children as $child) {
            if ($child->getPerson() === $person) {
                return $child;
            }
        }
        return false;
    }

    public function recursivelySetSpouse($em)
    {
        foreach ($this->children as $child) {
            $child->recursivelySetSpouse($em);
        }
        if ($this->person->getFemale()) {
            $marriage = $em->getRepository('AppBundle:PersonMarryPerson')->findOneByWife($this->person->getId());
            if ($marriage) {
                $personId = $marriage->getHusband();
            }
        } else {
            $marriage = $em->getRepository('AppBundle:PersonMarryPerson')->findOneByHusband($this->person->getId());
            if ($marriage) {
                $personId = $marriage->getWife();
            }
        }
        if (isset($personId)) {
            $spouse = $em->getRepository('AppBundle:Person')->findOneById($personId);
            if ($spouse) {
                $this->spouse = $spouse;
            }
        }
    }

    public function removeSpouseFromLastChild()
    {
        if (isset($this->parent) && count($this->children) === 0) {
            $this->spouse = null;
            return;
        }
        foreach ($this->children as $child) {
            $child->removeSpouseFromLastChild();
        }
    }

    public function outputDTree()
    {
        $ancestryArray = array(
            'name' => $this->person->getFullname(),
            'class' => $this->person->getFemale() ? 'woman' : 'man',
        );
        $marriageArray = array();
        if ($this->spouse) {
            $marriageArray = array(
                'spouse' => array(
                    'name' => $this->spouse->getFullname(),
                    'class' => $this->spouse->getFemale() ? 'woman' : 'man',
                ),
            );
        }
        if ($this->children) {
            // not sure if children are shown if there is no spouse
            $marriageArray['children'] = array();
            foreach ($this->children as $child) {
                $marriageArray['children'][] = $child->outputDTree();
            }
        }
        if (count($marriageArray) > 0) {
            $ancestryArray['marriages'][] = $marriageArray;
        }
        return $ancestryArray;
    }
}
