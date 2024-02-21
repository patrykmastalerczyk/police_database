<?php
namespace App\Twig;

use App\Entity\User;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;


class AppExtension extends AbstractExtension
{
    protected $doctrine;

    public function __construct(RegistryInterface $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('username', [$this, 'getUsername']),
            new TwigFilter('skin', [$this, 'getSkin']),
            new TwigFilter('sex', [$this, 'getSex']),
            new TwigFilter('role', [$this, 'getRoleString']),
        ];
    }

    public function getUsername($id)
    {
        $user = $this->doctrine->getRepository(User::class)->find($id);

        return $user->getFullname();
    }

    public function getSkin($id) {
        $str = null;

        switch($id) {
            case 0:
                $str = 'Biały';
                break;
            case 1:
                $str = 'Czarny';
                break;
            case 2:
                $str = 'Żółty';
                break;
            case 3:
                $str = 'Inny';
                break;
            default:
                $str = 'undefined';
                break;
        }

        return $str;
    }

    public function getRoleString($level) {
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

    public function getSex($id) {
        $str = null;

        switch($id) {
            case 0:
                $str = 'Mężczyzna';
                break;
            case 1:
                $str = 'Kobieta';
                break;
            case 2:
                $str = 'Inna';
                break;
            default:
                $str = 'undefined';
                break;
        }

        return $str;
    }
}