<?php
namespace App\Controller;
use App\Repository\UserRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\User;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class DefaultController extends Controller {

    /**
     * @Route("/", name="index")
     */
    public function index()
    {
        return $this->render('index.html.twig', [
            'role' => $this->getRangString($this->getUser()->getRole())
        ]);
    }

    /**
     * @Route("/profile/{id}", name="profile_view")
     */
    public function show($id) {
        if($id == 'you') {
            return $this->render('user/profile.html.twig', array('user' => $this->getUser()));
        } else {
            if($this->getDoctrine()->getRepository(User::class)->find($id) != null) {
                return $this->render('user/profile.html.twig', array('user' => $this->getDoctrine()->getRepository(User::class)->find($id)));
            } else {
                throw $this->createNotFoundException(
                    'Nie znaleziono użytkownika z ID ' . $id
                );
            }
        }
    }

    /**
     * @Route("/profile/changepassword/{id}")
     * @Method("POST")
     */
    public function changePassword(Request $request, UserPasswordEncoderInterface $encoder, $id) {
        $user = $this->getDoctrine()->getRepository(User::class)->find($id);
        $currentUser = $this->getUser();
        $password = $request->request->get('password');

        if(strlen($password) >= 8) {
            if($user == $currentUser || $currentUser->getRole() >= 2) {
                $entityManager = $this->getDoctrine()->getManager();
                $user->setPassword($encoder->encodePassword($user, $password));

                $entityManager->flush();

                $response = new Response('Success');
                $response->send();
            } else {
                throw $this->createAccessDeniedException('Brak uprawnień');
            }
        } else {
            throw $this->createAccessDeniedException('Hasło jest za krótkie');
        }
    }

    public function getRangString($level) {
        $string = null;

        switch($level) {
            case 0:
                $string = 'Użytkownik';
                break;
            case 1:
                $string = 'Moderacja';
                break;
            case 2:
                $string = 'Administrator';
                break;
            default:
                $string = 'Undefined';
                break;
        }

        return $string;
    }
}