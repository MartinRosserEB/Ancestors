<?php

namespace AppBundle\Controller;

use AppBundle\Service\Admin;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
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
    public function editUserAction(Request $request, AuthorizationCheckerInterface $authChecker, Admin $adminSrv, $id)
    {
        if (false === $authChecker->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException();
        }

        $data = $adminSrv->prepareForm($id);
        $form = $data['form'];

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $adminSrv->persistForm($data);
        }

        return $this->render('@ancestors/administration/edit_user.html.twig', array(
            'form' => $form->createView(),
        ));
    }
}
