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
        $marriagesWithKids = $this->getMarriagesWithKidsFor($person);dump($marriagesWithKids );
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
        $repo = $this->getDoctrine()->getManager()->getRepository('AppBundle:Person');
        $qb = $repo->createQueryBuilder('p');
        $persons = $qb->select('p')
            ->where('p.birthdate is not null')
            ->orderBy('p.birthdate', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        if ($persons[0] && $persons[0]->getId()) {
            return $this->redirectToRoute('show_person', array('id' => $persons[0]->getId()));
        } else {
            throw new AccessDeniedHttpException();
        }
    }

    /**
     * @Route("/{id}/edit/", name="edit_person")
     */
    public function editAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $person = $em->getRepository('AppBundle:Person')->findOneById($id);

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
            trigger_error('Could not find linke between persons!');
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
                trigger_error('Could not find both persons in database!');
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
     * @Route("/create/{pToMarry}", defaults = {"pToMarry" = null}, name="create_person")
     */
    public function createAction(Request $request, $pToMarry)
    {
        $entity = new Person;

        $em = $this->getDoctrine()->getManager();

        $actionUrl = $this->generateUrl('create_person');
        if ($pToMarry) {
            $personToMarry = $em->getRepository('AppBundle:Person')->findOneById($pToMarry);
            if ($personToMarry) {
                $actionUrl = $this->generateUrl('create_person', array(
                    'pToMarry' => $personToMarry->getId(),
                ));
            } else {
                // Should fail with error message that person was not found
            }
        }

        $form = $this->createForm(PersonType::class, $entity, array(
            'action' => $actionUrl,
            'method' => 'POST',
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($entity);
            $em->flush();
            if ($pToMarry) {
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
            'pToMarry' => isset($personToMarry) ? $personToMarry : null,
        ));
    }

    /**
     * @Route("/createFrom/{p1}/{p2}", name="create_person_from_parents")
     */
    public function createFromParentsAction(Request $request, $p1, $p2)
    {
        $entity = new Person;
        $em = $this->getDoctrine()->getManager();
        $person1 = $em->getRepository('AppBundle:Person')->findOneById($p1);
        $person2 = $em->getRepository('AppBundle:Person')->findOneById($p2);
        if (!$person1 || !$person2) {
            // Should do error handling
            //exit(dump($person1, $person2));
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
            $em = $this->getDoctrine()->getManager();
            $qb = $em->createQueryBuilder();
            $qb->select('p')
               ->from('AppBundle:Person', 'p');
            if ($entity->getFamilyname()) {
                $qb->andWhere('p.familyname LIKE :familyname')
                   ->setParameter('familyname', '%' . $entity->getFamilyname() . '%');
            }
            if ($entity->getFirstname()) {
                $qb->andWhere('p.firstname LIKE :firstname')
                   ->setParameter('firstname', '%' . $entity->getFirstname() . '%');
            }
            if ($entity->getBirthdate()) {
                $qb->andWhere('p.birthdate = :birthdate')
                   ->setParameter('birthdate', $entity->getBirthdate());
            }
            $qb->orderBy('p.birthdate', 'ASC');
            
            $result = $qb->getQuery()->getResult();
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
    public function editMarriageAction(Request $request, $p1, $p2)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $this->selectMarriage($p1, $p2);

        $form = $this->createForm(PersonMarryPersonType::class, $entity, array(
            'action' => $this->generateUrl('edit_marriage', array(
                'p1' => $p1,
                'p2' => $p2,
            )),
            'method' => 'POST',
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
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
    public function deleteMarriageAction($p1, $p2)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $this->selectMarriage($p1, $p2);

        $em->remove($entity);
        $em->flush();

        return $this->redirect($this->generateUrl('show_person', array(
            'id' => $p1,
        )));
    }

    private function selectMarriage($p1, $p2)
    {
        $em = $this->getDoctrine()->getManager();

        $person1 = $em->getRepository('AppBundle:Person')->findOneById($p1);
        $person2 = $em->getRepository('AppBundle:Person')->findOneById($p2);
        if (!$person1 || !$person2) {
            // Should do error handling
            //exit(dump($person1, $person2));
        }
        if ($person1->getFemale() && !$person2->getFemale()) {
            $wife = $person1;
            $husband = $person2;
        } else if (!$person1->getFemale() && $person2->getFemale()) {
            $wife = $person2;
            $husband = $person1;
        } else {
            // Should do handling of same sex marriage
        }

        $qb = $em->createQueryBuilder();
        $qb->select('p')
           ->from('AppBundle:PersonMarryPerson', 'p')
           ->where('p.husband = :husband')
           ->setParameter('husband', $husband)
           ->andWhere('p.wife = :wife')
           ->setParameter('wife', $wife)
           ->setMaxResults(1);

        return $qb->getQuery()->getResult()[0];
    }
}
