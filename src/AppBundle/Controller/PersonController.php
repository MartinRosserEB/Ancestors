<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Person;
use AppBundle\Entity\PersonMarryPerson;
use AppBundle\Form\PersonType;
use AppBundle\Form\FindPersonType;
use AppBundle\Form\LinkTwoPersonsType;
use AppBundle\Form\PersonMarryPersonType;
use AppBundle\Service\CheckAccess;
use AppBundle\Service\FindLink;
use AppBundle\Service\PreFill;
use AppBundle\Service\PrepareRelations;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @Route("/person")
 */
class PersonController extends Controller
{
    /**
     * @Route("/", name="person_index")
     */
    public function indexAction()
    {
        return $this->render('@ancestors/person/index.html.twig');
    }

    /**
     * @Route("/{id}/show/", name="show_person")
     */
    public function showAction(Person $person, PrepareRelations $prepareRelations, CheckAccess $checkAccess)
    {
        if (!$checkAccess->checkReadAccess($person)) {
            throw new AccessDeniedHttpException();
        }

        $marriagesWithKids = $prepareRelations->getMarriagesWithKidsFor($person);

        return $this->render('@ancestors/person/show.html.twig', array(
            'person' => $person,
            'marriagesWithKids' => $marriagesWithKids,
        ));
    }

    /**
     * @Route("/show/oldest", name="show_oldest_person")
     */
    public function showOldestAction(CheckAccess $checkAccess)
    {
        $oldestPerson = $this->getDoctrine()->getManager()->getRepository('AppBundle:Person')->findOldestPerson();

        if ($oldestPerson) {
            if (!$checkAccess->checkReadAccess($oldestPerson)) {
                throw new AccessDeniedHttpException();
            }
            return $this->redirectToRoute('show_person', array('id' => $oldestPerson->getId()));
        } else {
            throw new AccessDeniedHttpException();
        }
    }

    /**
     * @Route("/{id}/edit/", name="edit_person")
     */
    public function editAction(Request $request, Person $person, CheckAccess $checkAccess)
    {
        if (!$checkAccess->checkWriteAccess($person)) {
            throw new AccessDeniedHttpException();
        }

        $prevFamilyTrees = new ArrayCollection();
        foreach ($person->getFamilyTrees() as $prevFamTree) {
            $prevFamilyTrees->add($prevFamTree);
        }
        $form = $this->createForm(PersonType::class, $person, array(
            'action' => $this->generateUrl('edit_person', array('id' => $person->getId())),
            'method' => 'POST',
            'user' => $this->getUser(),
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            foreach ($person->getFamilyTrees() as $familyTree) {
                if (!$prevFamilyTrees->contains($familyTree)) {
                    $familyTree->addPerson($person);
                }
            }
            foreach ($prevFamilyTrees as $familyTree) {
                if (!$person->getFamilyTrees()->contains($familyTree)) {
                    $familyTree->removePerson($person);
                }
            }
            $em->persist($person);
            $em->flush();
            return $this->redirect($this->generateUrl('show_person', array('id' => $person->getId())));
        }

        return $this->render('@ancestors/person/create.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/show/tree", name="show_family_tree")
     */
    public function showTreeAction(PrepareRelations $prepareRelations)
    {
        $em = $this->getDoctrine()->getManager();
        $persons = $em->getRepository('AppBundle:Person')->findAll();

        if ($person) {
            return $this->render('@ancestors/person/show.html.twig', array(
                'person' => $person,
                'marriedTo' => $prepareRelations->getPersonsMarriedTo($person),
                'kids' => $prepareRelations->getKids($person),
            ));
        } else {
            throw new AccessDeniedHttpException();
        }
    }

    /**
     * @Route("/link/{person1}/{person2}", name="link_persons")
     */
    public function link(Person $person1, Person $person2, FindLink $linkFinder, CheckAccess $checkAccess)
    {
        if (!$checkAccess->checkReadAccess($person1) || !$checkAccess->checkReadAccess($person2)) {
            throw new AccessDeniedHttpException();
        }

        $curNode = $linkFinder->getLinkBetweenTwoPersons($person1, $person2);
        if (!$curNode) {
            throw $this->createNotFoundException('label.link.between.persons.not.found');
        }
        $curNode->recursivelySetSpouse($this->getDoctrine()->getManager());
        $curNode->removeSpouseFromLastChild();

        return $this->render('@ancestors/person/find_connection.html.twig', array(
            'data' => json_encode(array($curNode->outputDTree())),
        ));
    }

    /**
     * @Route("/linkForm/", name="link_persons_form")
     */
    public function linkForm(Request $request)
    {
        // abusing PersonMarryPerson. Requiring entity with two Person links
        $entity = new PersonMarryPerson;
        $form = $this->createForm(LinkTwoPersonsType::class, $entity, array(
            'action' => $this->generateUrl('link_persons_form'),
            'method' => 'POST',
            'user' => $this->getUser(),
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $person1 = $em->getRepository('AppBundle:Person')->findOneById($entity->getHusband());
            $person2 = $em->getRepository('AppBundle:Person')->findOneById($entity->getWife());
            if (!$person1 || !$person2) {
                throw $this->createNotFoundException('label.persons.not.found');
            }
            return $this->redirectToRoute('link_persons', array(
                'person1' => $person1->getId(),
                'person2' => $person2->getId(),
            ));
        }

        return $this->render('@ancestors/person/find_link.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/create/{personToMarry}", defaults = {"personToMarry" = null}, name="create_person")
     */
    public function createAction(Request $request, Person $personToMarry = null)
    {
        $entity = new Person;

        $em = $this->getDoctrine()->getManager();

        $actionUrl = $this->generateUrl('create_person');
        if ($personToMarry) {
            $actionUrl = $this->generateUrl('create_person', array(
                'pToMarry' => $personToMarry->getId(),
            ));
        }

        $form = $this->createForm(PersonType::class, $entity, array(
            'action' => $actionUrl,
            'method' => 'POST',
            'user' => $this->getUser(),
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($entity);
            foreach ($entity->getFamilyTrees() as $familyTree) {
                $familyTree->addPerson($entity);
            }
            $em->flush();
            if ($personToMarry) {
                $url = $this->generateUrl('marry_both', array(
                    'p1' => $personToMarry->getId(),
                    'p2' => $entity->getId(),
                ));
            } else {
                $url = $this->generateUrl('show_person', array(
                    'id' => $entity->getId(),
                ));
            }
            return $this->redirect($url);
        }

        return $this->render('@ancestors/person/create.html.twig', array(
            'form' => $form->createView(),
            'personToMarry' => isset($personToMarry) ? $personToMarry : null,
        ));
    }

    /**
     * @Route("/createFrom/{person1}/{person2}", name="create_person_from_parents")
     */
    public function createFromParentsAction(Request $request, Person $person1, Person $person2, PreFill $preFill)
    {
        $entity = $preFill->returnPreFilledPerson($person1, $person2);

        $form = $this->createForm(PersonType::class, $entity, array(
            'action' => $this->generateUrl('create_person'),
            'method' => 'POST',
            'user' => $this->getUser(),
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();
            return $this->redirect($this->generateUrl('create_person_from_parents', array('person1' => $person1->getId(), 'person2' => $person2->getId())));
        }

        return $this->render('@ancestors/person/create.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/find", name="find_person")
     */
    public function findAction(Request $request)
    {
        $entity = new Person;
        $form = $this->createForm(FindPersonType::class, $entity, array(
            'action' => $this->generateUrl('find_person'),
            'method' => 'POST',
        ));

        $result = '';
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $result = $this->getDoctrine()->getManager()->getRepository('AppBundle:Person')->findPersonFromPartialInfoWithReadAccessCheck($entity, $this->getUser());
        }

        return $this->render('@ancestors/person/find.html.twig', array(
            'form' => $form->createView(),
            'result' => $result,
        ));
    }

    /**
     * @Route("/marry", name="marry")
     */
    public function marryAction(Request $request)
    {
        $entity = new PersonMarryPerson;

        $form = $this->createForm(PersonMarryPersonType::class, $entity, array(
            'action' => $this->generateUrl('marry'),
            'method' => 'POST',
            'user' => $this->getUser(),
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();
            return $this->redirect($this->generateUrl('show_person', array('id' => $entity->getHusband()->getId())));
        }

        return $this->render('@ancestors/person/marry.html.twig', array(
            'form' => $form->createView(),
            'nextActionMarryBoth' => false,
        ));
    }

    /**
     * @Route("/marryWith/{person}", name="marry_with")
     */
    public function marryWithAction(Request $request, Person $person, PreFill $preFill)
    {
        $entity = $preFill->setWifeOrHusband($person);

        $form = $this->createForm(PersonMarryPersonType::class, $entity, array(
            'action' => $this->generateUrl('marry'),
            'method' => 'POST',
            'user' => $this->getUser(),
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();
            return $this->redirect($this->generateUrl('show_person', array('id' => $entity->getHusband()->getId())));
        }

        return $this->render('@ancestors/person/marry.html.twig', array(
            'form' => $form->createView(),
            'nextActionMarryBoth' => true,
            'person' => $person,
        ));
    }

    /**
     * @Route("/marryBoth/{person1}/{person2}", name="marry_both")
     */
    public function marryBothAction(Request $request, $person1, $person2, PreFill $preFill, CheckAccess $checkAccess)
    {
        if (!$checkAccess->checkWriteAccess($person1) || !$checkAccess->checkWriteAccess($person2)) {
            throw new AccessDeniedHttpException();
        }

        $entity = $preFill->setWifeAndHusband($person1, $person2);

        $form = $this->createForm(PersonMarryPersonType::class, $entity, array(
            'action' => $this->generateUrl('marry'),
            'method' => 'POST',
            'user' => $this->getUser(),
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($entity);
            $em->flush();
            return $this->redirect($this->generateUrl('show_person', array('id' => $entity->getHusband()->getId())));
        }

        return $this->render('@ancestors/person/marry.html.twig', array(
            'form' => $form->createView(),
            'nextActionMarryBoth' => false,
        ));
    }

    /**
     * @Route("/editMarriage/{person1}/{person2}", name="edit_marriage")
     */
    public function editMarriageAction(Request $request, Person $person1, Person $person2, CheckAccess $checkAccess)
    {
        if (!$checkAccess->checkWriteAccess($person1) || !$checkAccess->checkWriteAccess($person2)) {
            throw new AccessDeniedHttpException();
        }

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('AppBundle:PersonMarryPerson')
            ->findMarriageBetween($person1, $person2);
        if (!$entity) {
            throw $this->createNotFoundException('label.marriage.not.found');
        }

        $form = $this->createForm(PersonMarryPersonType::class, $entity, array(
            'action' => $this->generateUrl('edit_marriage', array(
                'person1' => $person1->getId(),
                'person2' => $person2->getId(),
            )),
            'method' => 'POST',
            'user' => $this->getUser(),
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($entity);
            $em->flush();
            return $this->redirect($this->generateUrl('show_person', array(
                'id' => $entity->getHusband()->getId(),
            )));
        }

        return $this->render('@ancestors/person/marry.html.twig', array(
            'form' => $form->createView(),
            'nextActionMarryBoth' => false,
        ));
    }

    /**
     * @Route("/deleteMarriage/{p1}/{p2}", name="delete_marriage")
     */
    public function deleteMarriageAction(Person $person1, Person $person2, CheckAccess $checkAccess)
    {
        if (!$checkAccess->checkDeleteAccess($person1) || !$checkAccess->checkDeleteAccess($person2)) {
            throw new AccessDeniedHttpException();
        }

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:PersonMarryPerson')
            ->findMarriageBetween($person1, $person2);
        if ($entity) {
            $em->remove($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('show_person', array(
                'id' => $person1->getId(),
            )));
        } else {
            throw $this->createNotFoundException('label.marriage.not.found');
        }
    }
}
