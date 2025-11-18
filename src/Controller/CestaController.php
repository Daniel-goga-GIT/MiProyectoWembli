<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Persistence\ManagerRegistry;
use App\Service\CestaCompra;
use App\Entity\Producto;

#[IsGranted('ROLE_USER')]
#[Route('/cesta')]
class CestaController extends AbstractController
{
    #[Route('/', name: 'cesta')]
    public function mostrar(CestaCompra $cesta): Response
    {
        return $this->render('cesta/mostrar.html.twig', [
            'productos' => $cesta->get_productos(),
            'total' => $cesta->get_coste(),
        ]);
    }

    #[Route('/anadir/{id}', name: 'anadir')]
    public function anadir(int $id, ManagerRegistry $doctrine, CestaCompra $cesta): Response
    {
        $producto = $doctrine->getRepository(Producto::class)->find($id);
        if (!$producto) {
            throw $this->createNotFoundException('Producto no encontrado');
        }

        $cesta->carga_articulo($producto, 1);
        return $this->redirectToRoute('cesta');
    }

    #[Route('/eliminar/{id}', name: 'eliminar')]
    public function eliminar(int $id, CestaCompra $cesta): Response
    {
        $cesta->eliminar_producto($id);
        return $this->redirectToRoute('cesta');
    }

    #[Route('/vaciar', name: 'vaciar_cesta')]
    public function vaciar(CestaCompra $cesta): Response
    {
        foreach ($cesta->get_productos() as $id => $item) {
            $cesta->eliminar_producto($id);
        }
        return $this->redirectToRoute('cesta');
    }
}
