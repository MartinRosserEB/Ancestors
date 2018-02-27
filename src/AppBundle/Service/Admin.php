<?php

namespace AppBundle\Service;

use AppBundle\Form\UserType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
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

    public function __construct(UserManagerInterface $userManager, FormFactoryInterface $formFactory, UrlGeneratorInterface $urlGenerator, ObjectManager $em, $roles)
    {
        $this->userManager = $userManager;
        $this->formFactory = $formFactory;
        $this->urlGenerator = $urlGenerator;
        $this->em = $em;
        $this->roles = array_keys($roles);
    }

    public function prepareForm($id)
    {
        $user = $this->userManager->findUserBy(array('id'=>$id));
        if (!$user) {
            throw $this->createNotFoundException('label.user.not.found');
        }
        $originalAccessRights = new ArrayCollection();
        foreach ($user->getAccessRights() as $accessRight) {
            $originalAccessRights->add($accessRight);
        }

        $form = $this->formFactory->create(UserType::class, $user, array(
            'action' => $this->urlGenerator->generate('edit_user', array(
                'id' => $user->getId()
            )),
            'method' => 'POST',
            'roles' => array_combine($this->roles, $this->roles),
        ));

        return array(
            'user' => $user,
            'form' => $form,
            'originalAccessRights' => $originalAccessRights,
        );
    }

    public function persistForm($data)
    {
        $user = $data['user'];
        $originalAccessRights = $data['originalAccessRights'];

        foreach ($originalAccessRights as $originalAccessRight) {
            if (false === $user->getAccessRights()->contains($originalAccessRight)) {
                $this->em->remove($originalAccessRight);
            }
        }

        $this->userManager->updateUser($user);
    }
}
