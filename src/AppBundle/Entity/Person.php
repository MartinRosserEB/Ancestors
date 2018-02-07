<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="person")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\PersonRepository")
 */
class Person
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
    private $familyname;

    /**
     * @ORM\Column(type="string", length=250)
     */
    private $firstname;

    /**
     * @ORM\Column(type="boolean")
     */
    private $female = false;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $birthdate;

    /**
     * @ORM\ManyToOne(targetEntity="Person")
     */
    private $twin;

    /**
     * @ORM\ManyToOne(targetEntity="Person")
     */
    private $father;

    /**
     * @ORM\ManyToOne(targetEntity="Person")
     */
    private $mother;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $deathdate;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $comment;

    public function __construct($familyname=false, $firstname=false, $birthdate=false)
    {
        if ($familyname)
        {
            $this->familyname = $familyname;
        }
        if ($firstname)
        {
            $this->firstname = $firstname;
        }
        if ($birthdate)
        {
            $this->birthdate = $birthdate;
        }
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set familyname
     *
     * @param string $familyname
     *
     * @return Person
     */
    public function setFamilyname($familyname)
    {
        $this->familyname = $familyname;

        return $this;
    }

    /**
     * Get familyname
     *
     * @return string
     */
    public function getFamilyname()
    {
        return $this->familyname;
    }

    /**
     * Set firstname
     *
     * @param string $firstname
     *
     * @return Person
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Get surname
     *
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    public function getFullName()
    {
        if ($this->birthdate) {
            return $this->familyname . " " . $this->firstname . ", " . $this->birthdate->format('d.m.Y');
        } else {
            return $this->familyname . " " . $this->firstname;
        }
    }

    /**
     * Set birthdate
     *
     * @param \DateTime $birthdate
     *
     * @return Person
     */
    public function setBirthdate($birthdate)
    {
        $this->birthdate = $birthdate;

        return $this;
    }

    /**
     * Get birthdate
     *
     * @return \DateTime
     */
    public function getBirthdate()
    {
        return $this->birthdate;
    }

    /**
     * Set deathdate
     *
     * @param \DateTime $deathdate
     *
     * @return Person
     */
    public function setDeathdate($deathdate)
    {
        $this->deathdate = $deathdate;

        return $this;
    }

    /**
     * Get deathdate
     *
     * @return \DateTime
     */
    public function getDeathdate()
    {
        return $this->deathdate;
    }

    /**
     * Set comment
     *
     * @param string $comment
     *
     * @return Person
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set father
     *
     * @param \AppBundle\Entity\Person $father
     *
     * @return Person
     */
    public function setFather(\AppBundle\Entity\Person $father = null)
    {
        $this->father = $father;

        return $this;
    }

    /**
     * Get father
     *
     * @return \AppBundle\Entity\Person
     */
    public function getFather()
    {
        return $this->father;
    }

    /**
     * Set twin
     *
     * @param \AppBundle\Entity\Person $twin
     *
     * @return Person
     */
    public function setTwin(\AppBundle\Entity\Person $twin = null)
    {
        $this->twin = $twin;

        return $this;
    }

    /**
     * Get twin
     *
     * @return \AppBundle\Entity\Person
     */
    public function getTwin()
    {
        return $this->twin;
    }

    /**
     * Set mother
     *
     * @param \AppBundle\Entity\Person $mother
     *
     * @return Person
     */
    public function setMother(\AppBundle\Entity\Person $mother = null)
    {
        $this->mother = $mother;

        return $this;
    }

    /**
     * Get mother
     *
     * @return \AppBundle\Entity\Person
     */
    public function getMother()
    {
        return $this->mother;
    }

    /**
     * Set female
     *
     * @param boolean $female
     *
     * @return Person
     */
    public function setFemale($female)
    {
        $this->female = $female;

        return $this;
    }

    /**
     * Get female
     *
     * @return boolean
     */
    public function getFemale()
    {
        return $this->female;
    }
}
