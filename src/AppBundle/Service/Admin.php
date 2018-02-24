<?php

namespace AppBundle\Service;

use AppBundle\Form\UserType;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @author mrosser
 */
class Admin {
    private $userManager;
    private $formFactory;
    private $urlGenerator;
    private $roles;

    public function __construct(UserManagerInterface $userManager, FormFactoryInterface $formFactory, UrlGeneratorInterface $urlGenerator, $roles)
    {
        $this->userManager = $userManager;
        $this->formFactory = $formFactory;
        $this->urlGenerator = $urlGenerator;
        $this->roles = $roles;
    }

    public function prepareForm($id)
    {
        $user = $this->userManager->findUserBy(array('id'=>$id));
        if (!$user) {
            throw $this->createNotFoundException('label.user.not.found');
        }
        $user->populateFamilyTrees();

        $form = $this->formFactory->create(UserType::class, $user, array(
            'action' => $this->urlGenerator->generate('edit_user', array(
                'id' => $user->getId()
            )),
            'method' => 'POST',
            'roles' => array_flip(array_keys($this->roles)),
        ));

        return array(
            'user' => $user,
            'form' => $form,
        );
    }

    public function persistForm($data)
    {
        $user = $data['user'];
        $accessRights = $user->getAccessRights();
        $storedFamilyTrees = array();
        foreach ($accessRights as $accessRight) {
            $storedFamilyTrees[] = $accessRight->getFamilyTree();
        }
    }
}
