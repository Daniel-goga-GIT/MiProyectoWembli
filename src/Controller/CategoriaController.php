<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Categoria;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class CategoriaController extends AbstractController
{
    #[Route('/categorias', name: 'categorias')]
    public function mostrar_categorias(ManagerRegistry $doctrine): Response
    {
        $categorias = $doctrine->getRepository(Categoria::class)->findAll();

        return $this->render('categorias/mostrar_categorias.html.twig', [
            'categorias' => $categorias,
        ]);
    }
}
