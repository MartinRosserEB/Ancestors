<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/admin")
 */
class AdminController extends Controller
{   
    /**
     * @Route("/show/users", name="show_users")
     */
    public function showUserAction(AuthorizationCheckerInterface $authChecker)
    {
        if (false === $authChecker->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException();
        }

        $userManager = $this->container->get('fos_user.user_manager');
        $allUsers = $userManager->findUsers();
        foreach ($allUsers as $user) {
            $user->populateFamilyTrees();
        }

        return $this->render('@ancestors/administration/show_users.html.twig', array(
            'users' => $allUsers,
        ));
    }

    /**
     * @Route("/edit/user/{id}", name="edit_user")
     */
    public function editUserAction(AuthorizationCheckerInterface $authChecker, $id)
    {
        if (false === $authChecker->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException();
        }

        $userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->findUserBy(array('id'=>$id));
        if (!$user) {
            throw $this->createNotFoundException('label.user.not.found');
        }
        $user->populateFamilyTrees();

        return $this->render('@ancestors/administration/edit_user.html.twig', array(
            'user' => $user,
        ));
    }
}
