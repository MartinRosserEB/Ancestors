<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Entity\AccessRightRepository")
 */
class AccessRight
{
    const READ = 4;
    const WRITE = 6; // 4 + 2: Only combined access rights supported
    const DELETE = 7; // 4 + 2 + 1

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $accessType;

    /**
     * @ORM\ManyToOne(targetEntity="FamilyTree", inversedBy="accessRights")
     */
    private $familyTree;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="accessRights")
     */
    private $user;

    public function getId()
    {
        return $this->id;
    }

    public function setAccessType($type)
    {
        $this->accessType = $type;
    }

    public function getAccessType()
    {
        return $this->accessType;
    }

    public function setFamilyTree(FamilyTree $familyTree)
    {
        $this->familyTree = $familyTree;
    }

    public function getFamilyTree()
    {
        return $this->familyTree;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function __toString()
    {
        return (string) $this->familyTree;
    }
}
