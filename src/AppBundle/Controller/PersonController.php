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
use AppBundle\Helper\DisplayNode;
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
    public function showAction(Person $person)
    {
        $marriagesWithKids = $this->getMarriagesWithKidsFor($person);

        return $this->render('@ancestors/person/show.html.twig', array(
            'person' => $person,
            'marriagesWithKids' => $marriagesWithKids,
        ));
    }

    /**
     * @Route("/show/oldest", name="show_oldest_person")
     */
    public function showOldestAction()
    {
        $oldestPerson = $this->getDoctrine()->getManager()->getRepository('AppBundle:Person')->findOldestPerson();

        if ($oldestPerson) {
            return $this->redirectToRoute('show_person', array('id' => $oldestPerson->getId()));
        } else {
            throw new AccessDeniedHttpException();
        }
    }

    /**
     * @Route("/{id}/edit/", name="edit_person")
     */
    public function editAction(Request $request, Person $person)
    {
        $form = $this->createForm(PersonType::class, $person, array(
            'action' => $this->generateUrl('edit_person', array('id' => $person->getId())),
            'method' => 'POST',
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
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
    public function showTreeAction()
    {
        $em = $this->getDoctrine()->getManager();
        $persons = $em->getRepository('AppBundle:Person')->findAll();

        if ($person) {
            $personsMarriedTo = $this->getPersonsMarriedTo($person);
            $kids = $this->getKids($person);
            return $this->render('@ancestors/person/show.html.twig', array(
                'person' => $person,
                'marriedTo' => $personsMarriedTo,
                'kids' => $kids,
            ));
        } else {
            throw new AccessDeniedHttpException();
        }
    }

    private function getMarriagesWithKidsFor(Person $person)
    {
        $em = $this->getDoctrine()->getManager();

        $marriagesWithKids = [];
        if ($person->getFemale()) {
            $marriedTo = $em->getRepository('AppBundle:PersonMarryPerson')->findByWife($person->getId());
            if ($marriedTo) {
                foreach ($marriedTo as $marriedPerson) {
                    $husband = $marriedPerson->getHusband();
                    $marriagesWithKids[] = array(
                        'person' => $husband,
                        'kids' => $em->getRepository('AppBundle:Person')->findKidsByMotherAndFather($person, $husband),
                    );
                }
            }
        } else {
            $marriedTo = $em->getRepository('AppBundle:PersonMarryPerson')->findByHusband($person->getId());
            if ($marriedTo) {
                foreach ($marriedTo as $marriedPerson) {
                    $wife = $marriedPerson->getWife();
                    $marriagesWithKids[] = array(
                        'person' => $wife,
                        'kids' => $em->getRepository('AppBundle:Person')->findKidsByMotherAndFather($wife, $person),
                    );
                }
            }
        }

        return $marriagesWithKids;
    }

    private function getPersonsMarriedTo($person)
    {
        $em = $this->getDoctrine()->getManager();

        $personsMarriedTo = [];
        if ($person->getFemale()) {
            $marriedTo = $em->getRepository('AppBundle:PersonMarryPerson')->findByWife($person->getId());
            if ($marriedTo) {
                foreach ($marriedTo as $marriedPerson) {
                    $personsMarriedTo[] = $marriedPerson->getHusband();
                }
            }
        } else {
            $marriedTo = $em->getRepository('AppBundle:PersonMarryPerson')->findByHusband($person->getId());
            if ($marriedTo) {
                foreach ($marriedTo as $marriedPerson) {
                    $personsMarriedTo[] = $marriedPerson->getWife();
                }
            }
        }

        return $personsMarriedTo;
    }

    private function getKids($person)
    {
        $em = $this->getDoctrine()->getManager();

        $kids = [];
        if ($person->getFemale()) {
            $kids = $em->getRepository('AppBundle:Person')->findByMother($person->getId());
        } else {
            $kids = $em->getRepository('AppBundle:Person')->findByFather($person->getId());
        }

        return $kids;
    }

    /**
     * @Route("/link/{p1}/{p2}", name="link_persons")
     */
    public function link($p1, $p2)
    {
        $curNode = $this->get('person_link_finder')->getLinkBetweenTwoPersons($p1, $p2);
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
                'p1' => $person1->getId(),
                'p2' => $person2->getId(),
            ));
        }

        return $this->render('@ancestors/person/find_link.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/create/{personToMarry}", defaults = {"personToMarry" = null}, name="create_person")
     */
    public function createAction(Request $request, Person $personToMarry)
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
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($entity);
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
     * @Route("/createFrom/{p1}/{p2}", name="create_person_from_parents")
     */
    public function createFromParentsAction(Request $request, Person $p1, Person $p2)
    {
        $entity = new Person;
        if (!$person1 || !$person2) {
            throw $this->createNotFoundException('label.parents.not.found');
        }
        if ($person1->getFemale() && !$person2->getFemale()) {
            $entity->setMother($person1);
            $entity->setFather($person2);
            $entity->setFamilyname($person2->getFamilyname());
        } else if (!$person1->getFemale() && $person2->getFemale()) {
            $entity->setMother($person2);
            $entity->setFather($person1);
            $entity->setFamilyname($person1->getFamilyname());
        }

        $form = $this->createForm(PersonType::class, $entity, array(
            'action' => $this->generateUrl('create_person'),
            'method' => 'POST',
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();
            return $this->redirect($this->generateUrl('create_person_from_parents', array('p1' => $person1->getId(), 'p2' => $person2->getId())));
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
            $result = $this->getDoctrine()->getManager()->getRepository('AppBundle:Person')->findPersonFromPartialInfo($entity);
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
     * @Route("/marryWith/{p1}", name="marry_with")
     */
    public function marryWithAction(Request $request, $p1)
    {
        $entity = new PersonMarryPerson;

        $em = $this->getDoctrine()->getManager();
        $person = $em->getRepository('AppBundle:Person')->findOneById($p1);
        if ($person) {
            if ($person->getFemale()) {
                $entity->setWife($person);
            } else {
                $entity->setHusband($person);
            }
        } else {
            // Should do error handling
        }

        $form = $this->createForm(PersonMarryPersonType::class, $entity, array(
            'action' => $this->generateUrl('marry'),
            'method' => 'POST',
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
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
     * @Route("/marryBoth/{p1}/{p2}", name="marry_both")
     */
    public function marryBothAction(Request $request, $p1, $p2)
    {
        $entity = new PersonMarryPerson;

        $em = $this->getDoctrine()->getManager();
        $person1 = $em->getRepository('AppBundle:Person')->findOneById($p1);
        $person2 = $em->getRepository('AppBundle:Person')->findOneById($p2);
        if ($person1 && $person2) {
            if ($person1->getFemale() && !$person2->getFemale()) {
                $entity->setWife($person1);
                $entity->setHusband($person2);
            } elseif ($person2->getFemale() && !$person1->getFemale()) {
                $entity->setWife($person2);
                $entity->setHusband($person1);
            }
        }

        $form = $this->createForm(PersonMarryPersonType::class, $entity, array(
            'action' => $this->generateUrl('marry'),
            'method' => 'POST',
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
     * @Route("/editMarriage/{p1}/{p2}", name="edit_marriage")
     */
    public function editMarriageAction(Request $request, Person $person1, Person $person2)
    {
        $em = $this->getDoctrine()->getManager();
        if ($person1 && $person2) {
            $entity = $em->getRepository('AppBundle:PersonMarryPerson')
                ->findMarriageBetween($person1, $person2);
        } else {
            throw $this->createNotFoundException('label.marriage.not.found');
        }

        $form = $this->createForm(PersonMarryPersonType::class, $entity, array(
            'action' => $this->generateUrl('edit_marriage', array(
                'p1' => $person1->getId(),
                'p2' => $person2->getId(),
            )),
            'method' => 'POST',
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
    public function deleteMarriageAction(Person $person1, Person $person2)
    {
        $em = $this->getDoctrine()->getManager();

        if ($person1 && $person2) {
            $entity = $em->getRepository('AppBundle:PersonMarryPerson')
                ->findMarriageBetween($person1, $person2);
            $em->remove($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('show_person', array(
                'id' => $p1,
            )));
        } else {
            throw $this->createNotFoundException('label.marriage.not.found');
        }
    }
}
