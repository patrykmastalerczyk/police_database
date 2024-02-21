<?php
namespace App\Controller;

use App\Entity\User;

use App\Repository\UserRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserController extends Controller
{
    /**
     * @Route("/users", name="users_list")
     * @Method({"GET"})
     */
    public function index(UserRepository $repository, Request $request, PaginatorInterface $paginator) {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $users = $this->getDoctrine()->getRepository(User::class)->findAll();

        $pagination = $paginator->paginate($users, $request->query->getInt('page', 1), 10);

        return $this->render('registry/users.html.twig', [
            'pagination' => $pagination,
            'error' => null
        ]);
    }

    /**
     * @Route("/users/add", name="users_add")
     * @Method({"GET"})
     */
    public function add(Request $request, UserPasswordEncoderInterface $encoder) {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = new User();

        $form = $this->createFormBuilder($user)
            ->add('username', TextType::class, [
                'label' => 'Login',
                'attr' => [
                  'class' => 'form-control',
                ],
            ])
            ->add('fullname', TextType::class, [
                'label' => 'Pełne dane osobowe',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Hasło',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('role', ChoiceType::class, [
                'label' => 'Rola',
                'choices' => [
                    'Użytkownik' => 0,
                    'Moderacja' => 1,
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('save', SubmitType::class, array(
                'label' => 'Zatwierdź',
                'attr' => array('class' => 'btn btn-success mt-3')
            ))
            ->getForm();

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();

            $user->setPassword($encoder->encodePassword($user, $user->getPassword()));

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('profile_view', [
                'id' => $user->getId()
            ]);
        }

        return $this->render('registry/new_user.html.twig', array(
            'form' => $form->createView()
        ));
    }
}