<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Producto;

final class ProductoController extends AbstractController
{
    #[Route('/productos/{categoria?}', name: 'productos')]
    public function mostrar_productos(ManagerRegistry $doctrine, ?int $categoria = null): Response
    {
        $repo = $doctrine->getRepository(Producto::class);

        $productos = $categoria
            ? $repo->findBy(['categoria' => $categoria])
            : $repo->findAll();

        return $this->render('productos/mostrar_productos.html.twig', [
            'productos' => $productos,
        ]);
    }
}
