<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Entity\FamilyTreeRepository")
 * @ORM\Table(name="family_tree")
 */
class FamilyTree
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=250)
     */
    private $name;

    /**
     * @ORM\ManyToMany(targetEntity="Person", inversedBy="familyTrees", cascade="persist")
     * @ORM\JoinTable(name="persons_familyTrees")
     */
    private $persons;

    /**
     * @ORM\OneToMany(targetEntity="AccessRight", mappedBy="familyTree")
     */
    private $accessRights;

    public function __construct()
    {
        $this->persons = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function addPerson(Person $person)
    {
        $this->persons->add($person);
    }

    public function __toString()
    {
        return $this->name;
    }
}
