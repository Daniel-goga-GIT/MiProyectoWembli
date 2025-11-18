<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class TestController extends AbstractController
{
    #[Route('/hola', name: 'hola')]
    public function home(): Response
    {
        return new Response('<html><body>Hola</body></html>');
    }

    #[Route('/producto/{num1}/{num2}', name: 'producto')]
    public function producto(int $num1, int $num2): Response
    {
        return new Response('<html><body>' . ($num1 * $num2) . '</body></html>');
    }

    #[Route('/defecto1/{num}', name: 'defecto1')]
    public function defecto1(int $num = 3): Response
    {
        return new Response('<html><body>' . $num . '</body></html>');
    }

    #[Route('/defecto2/{num?4}', name: 'defecto2')]
    public function defecto2(int $num): Response
    {
        return new Response('<html><body>' . $num . '</body></html>');
    }

    #[Route('/cuadrado/{num}', name: 'cuadrado')]
    public function cuadrado(int $num): Response
    {
        return $this->redirectToRoute('producto', ['num1' => $num, 'num2' => $num]);
    }

    #[Route('/testRequest', name: 'testRequest')]
    public function testRequest(Request $request): Response
    {
        return new Response('<html><body>IP: ' . $request->getClientIp() . '</body></html>');
    }

    #[Route('/sesion1', name: 'sesion1')]
    public function sesion1(SessionInterface $session): Response
    {
        $session->set("variable", 100);
        return $this->redirectToRoute('sesion2');
    }

    #[Route('/sesion2', name: 'sesion2')]
    public function sesion2(SessionInterface $session): Response
    {
        $var = $session->get("variable");
        return new Response('<html><body>' . $var . '</body></html>');
    }
}
