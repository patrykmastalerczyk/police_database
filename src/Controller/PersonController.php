<?php
namespace App\Controller;

use App\Entity\User;
use App\Entity\Person;
use App\Entity\File;

use App\Repository\PersonRepository;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Knp\Component\Pager\PaginatorInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;


class PersonController extends Controller
{
    /**
     * @Route("/persons", name="persons_list")
     * @Method({"GET"})
     */
    public function index(PersonRepository $repository, Request $request, PaginatorInterface $paginator) {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->showPersons($repository, $request, $paginator);
    }

    /**
     * @param PersonRepository $repository
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function showPersons(PersonRepository $repository, Request $request, PaginatorInterface $paginator) {
        $search = $request->get('name');

        if($search == null) {
            $persons = $this->getDoctrine()->getRepository(Person::class)->findAll();
        } else {
            if(!preg_match("/^[a-zA-Z]{1,20} [a-zA-Z]{1,20}$/", $search)) {
                return $this->render('registry/persons.html.twig', [
                    'error' => 'Fraza musi być w formacie Imię Nazwisko, np. John Doe. Inne formaty, np. John_Doe nie są akceptowalne.'
                ]);
            }

            $name = explode( " ", $search);
            $persons = $this->getDoctrine()->getRepository(Person::class)->findBy(['name' => $name[0], 'surname' => $name[1]]);
        }

        $pagination = $paginator->paginate($persons, $request->query->getInt('page', 1), 10);

        return $this->render('registry/persons.html.twig', [
            'pagination' => $pagination,
            'error' => null
        ]);
    }

    /**
     * @Route("/persons/view/{id}", name="person_view")
     * @Method({"GET"})
     * @param $id
     * @return Response
     */
    public function view($id) {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $person = $this->getDoctrine()->getRepository(Person::class)->find($id);

        if(!$person) {
            throw $this->createNotFoundException(
                'Not found'
            );
        }

        $creator = $this->getDoctrine()->getRepository(User::class)->find($person->addedby);
        $files = $this->getDoctrine()->getRepository(File::class)->findBy(
            ['owner' => $id]
        );

        return $this->render('registry/person.html.twig', [
            'person' => $person,
            'creator_name' => $creator->fullname,
            'files' => $files
        ]);
    }

    /**
     * @Route("/persons/remove/{id}", name="person_remove")
     * @Method({"GET"})
     */
    public function remove(PersonRepository $repository, Request $request, PaginatorInterface $paginator, $id) {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $person = $this->getDoctrine()->getRepository(Person::class)->find($id);
        $files = $this->getDoctrine()->getRepository(File::class)->findBy(
            ['owner' => $id]
        );

        if($person == null) {
            return $this->showPersons($repository, $request, $paginator);
        }

        $entityManager = $this->getDoctrine()->getManager();

        $entityManager->remove($person);
        foreach($files as $file) {
            $entityManager->remove($file);
        }

        $entityManager->flush();

        return $this->showPersons($repository, $request, $paginator);
    }

    /**
     * @Route("/persons/add", name="add_person")
     * Method({"GET", "POST"})
     */
    public function new(Request $request) {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $person = new Person();

        $form = $this->createFormBuilder($person)
            ->add('name', TextType::class, array(
                'label' => 'Imię',
                'attr' => array('class' => 'form-control')
            ))
            ->add('surname', TextType::class, array(
                'label' => 'Nazwisko',
                'attr' => array('class' => 'form-control')
            ))
            ->add('sex', ChoiceType::class, array(
                'choices' => [
                    'Mężczyzna' => 0,
                    'Kobieta' => 1,
                    'Inna' => 2
                ],
                'attr' => [
                    'class' => 'form-control'
                ],
                'label' => 'Płeć',
                'placeholder' => false,
            ))
            ->add('height',  NumberType::class, array(
                'label' => 'Wzrost (cm)',
                'html5' => true,
                'attr' => array('min' => 60, 'max' => 250, 'class' => 'form-control'),
                'required' => false,
            ))
            ->add('weight',  NumberType::class, array(
                'label' => 'Waga (kg)',
                'html5' => true,
                'attr' => array('min' => 20, 'max' => 250, 'class' => 'form-control'),
                'required' => false,
            ))
            ->add('birth', DateType::class, array(
                'label' => 'Data urodzenia',
                'required' => false,
                'widget' => 'choice',
                'years' => range(date('Y'), date('Y')-100),
                'placeholder' => array(
                    'year' => 'Rok', 'month' => 'Miesiąc', 'day' => 'Dzień',
                )
            ))
            ->add('skin', ChoiceType::class, array(
                'choices' => [
                    'Biały' => 0,
                    'Czarny' => 1,
                    'Żółty' => 2,
                    'Inny' => 3
                ],
                'attr' => [
                    'class' => 'form-control'
                ],
                'placeholder' => false,
                'label' => 'Kolor skóry',
                'required' => false
            ))
            ->add('save', SubmitType::class, array(
                'label' => 'Zatwierdź',
                'attr' => array('class' => 'btn btn-success mt-3')
            ))
            ->getForm();

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $person = $form->getData();

            $person->addedby = $this->getUser()->getId();
            $person->added =  new \DateTime();
            $person->edited =  new \DateTime();

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($person);
            $entityManager->flush();

            return $this->redirectToRoute('persons_list');
        }

        return $this->render('registry/new_person.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/persons/edit/{id}", name="edit_person")
     * Method({"GET", "POST"})
     */
    public function edit(Request $request, $id) {
        $this->denyAccessUnlessGranted('ROLE_MOD');

        $person = $this->getDoctrine()->getRepository(Person::class)->find($id);

        $form = $this->createFormBuilder($person)
            ->add('name', TextType::class, array(
                'label' => 'Imię',
                'attr' => array('class' => 'form-control')
            ))
            ->add('surname', TextType::class, array(
                'label' => 'Nazwisko',
                'attr' => array('class' => 'form-control')
            ))
            ->add('sex', ChoiceType::class, array(
                'choices' => [
                    'Mężczyzna' => 0,
                    'Kobieta' => 1,
                    'Inna' => 2
                ],
                'attr' => [
                    'class' => 'form-control'
                ],
                'label' => 'Płeć',
                'placeholder' => false,
            ))
            ->add('height',  NumberType::class, array(
                'label' => 'Wzrost (cm)',
                'html5' => true,
                'attr' => array('min' => 60, 'max' => 250, 'class' => 'form-control'),
                'required' => false,
            ))
            ->add('weight',  NumberType::class, array(
                'label' => 'Waga (kg)',
                'html5' => true,
                'attr' => array('min' => 20, 'max' => 250, 'class' => 'form-control'),
                'required' => false,
            ))
            ->add('birth', DateType::class, array(
                'label' => 'Data urodzenia',
                'required' => false,
                'widget' => 'choice',
                'years' => range(date('Y'), date('Y')-100),
                'placeholder' => array(
                    'year' => 'Rok', 'month' => 'Miesiąc', 'day' => 'Dzień',
                )
            ))
            ->add('skin', ChoiceType::class, array(
                'choices' => [
                    'Biały' => 0,
                    'Czarny' => 1,
                    'Żółty' => 2,
                    'Inny' => 3
                ],
                'attr' => [
                    'class' => 'form-control'
                ],
                'placeholder' => false,
                'label' => 'Kolor skóry',
                'required' => false
            ))
            ->add('save', SubmitType::class, array(
                'label' => 'Zatwierdź',
                'attr' => array('class' => 'btn btn-success mt-3')
            ))
            ->getForm();

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $person->edited =  new \DateTime();

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();

            return $this->redirectToRoute('persons_list');
        }

        return $this->render('registry/new_person.html.twig', array(
            'form' => $form->createView()
        ));
    }
}