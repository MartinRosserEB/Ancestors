<?php
// src/AppBundle/Entity/User.php

namespace AppBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="fos_user")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToMany(targetEntity="AccessRight", mappedBy="user")
     */
    private $accessRights;

    /**
     * familyTrees is for display only. It's content is defined through
     * accessRights->familyTrees
     */
    private $familyTrees;

    public function __construct()
    {
        parent::__construct();
        // your own logic
    }

    public function getAccessRights()
    {
        return $this->accessRights;
    }

    public function setAccessRights($accessRights)
    {
        $this->accessRights = $accessRights;
    }

    public function addAccessRight($accessRight)
    {
        $this->accessRights->add($accessRight);
    }

    public function populateFamilyTrees()
    {
        $this->familyTrees = array();
        foreach ($this->accessRights as $accessRight) {
            $this->familyTrees[] = $accessRight->getFamilyTree();
        }
    }

    public function getFamilyTrees()
    {
        return $this->familyTrees;
    }
}