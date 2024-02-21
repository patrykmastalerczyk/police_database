<?php
namespace App\Controller;

use App\Entity\File;
use App\Entity\Person;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class FileController extends Controller
{
    /**
     * @Route("/persons/view/{id}/addfile", name="person_add_file")
     * @Method({"GET"})
     */
    public function add(Request $request, $id) {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $file = new File();

        $person = $this->getDoctrine()->getRepository(Person::class)->find($id);

        $form = $this->createFormBuilder($file)
            ->add('owner', TextType::class, [
                'label' => 'Osoba',
                'attr' => [
                    'class' => 'form-control',
                    'disabled' => true,
                    'value' => sprintf('%s %s (ID:%d)', $person->getName(), $person->getSurname(), $person->getId()),
                ],
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Drogowy' => 0,
                    'Karny' => 1,
                    'Inny' => 2,
                ],
                'attr' => [
                    'class' => 'form-control'
                ],
                'label' => 'Typ wpisu'
            ])
            ->add('content', TextareaType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '[412] [$1000] Ucieczka przed departamentem.'
                ],
                'label' => 'Treść wpisu'
            ])
            ->add('save', SubmitType::class, array(
                'label' => 'Zatwierdź',
                'attr' => array('class' => 'btn btn-success mt-3')
            ))
            ->getForm();

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $file = $form->getData();

            $file->setCreated(new \DateTime());
            $file->setCreator($this->getUser()->getId());
            $file->setHidden(false);
            $file->setOwner($id);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($file);
            $entityManager->flush();

            return $this->redirectToRoute('person_view', [
                'id' => $id
            ]);
        }

        return $this->render('registry/new_person_file.html.twig', array(
            'form' => $form->createView(),
            'id' => $id
        ));
    }

    /**
     * @Route("/files/hide/{id}", name="hide_file")
     * @Method({"GET"})
     */
    public function hide($id) {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $file = $this->getDoctrine()->getRepository(File::class)->find($id);

        if(!$file) {
            throw $this->createNotFoundException(
                'No file found for this id'
            );
        }
        $entityManager = $this->getDoctrine()->getManager();
        $file->setHidden(!$file->getHidden());
        $entityManager->flush();

        return $this->redirectToRoute('person_view', [
            'id' => $file->getOwner()
        ]);
    }
}