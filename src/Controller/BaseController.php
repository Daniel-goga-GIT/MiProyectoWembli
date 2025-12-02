<?php
namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;  // ⬅️ AÑADE ESTA LÍNEA
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Categoria;
use App\Entity\Producto;
use App\Services\CestaCompra;

#[IsGranted('ROLE_USER')]
final class BaseController extends AbstractController
{
    #[Route('/categorias', name: 'categorias')]
    public function mostrar_categorias(ManagerRegistry $doctrine): Response
    {
        $categorias = $doctrine->getRepository(Categoria::class)->findAll();
        return $this->render('categorias/mostrar_categorias.html.twig', [
            'categorias' => $categorias,
        ]);
    }
   
    #[Route('/productos/{categoria}', name: 'productos')]
    public function mostrar_productos(ManagerRegistry $em, int $categoria): Response
    {
        $categoriaObjeto = $em->getRepository(Categoria::class)->find($categoria);

        // Si no existe la categor�a \u2192 error controlado
        if (!$categoriaObjeto) {
            throw $this->createNotFoundException("La categor�a no existe");
        }

        $productos = $categoriaObjeto->getProductos();

        return $this->render('productos/mostrar_productos.html.twig', [
            'productos' => $productos,
        ]);
    }
    
    #[Route('/anadir', name: 'anadir', methods: ['POST'])]
    public function anadir_productos(EntityManagerInterface $em, Request $request, CestaCompra $cesta): Response
    {
        //Recogemos los datos de entrada de la peticion POST
        $productos_id = $request->request->all('productos_id');
        $unidades = $request->request->all('unidades');

        //Vamos a obtener un array de objetos productos a partir de sus IDS
        $productos = $em->getRepository(Producto::class)->findBy(['id' => $productos_id]);

        if (empty($productos)) {
            throw $this->createNotFoundException("No se encontraron productos");
        }

        //Llamamos a la carga de productos para cargarlos en la sesión
        $cesta->cargar_articulos($productos, $unidades);
        $objetos_producto = array_values($productos);

        return $this->redirectToRoute("productos", ['categoria'=>$objetos_producto[0]->getCategoria()->getId()]);
    }
        
    
    #[Route('/cesta', name:'cesta')]
    public function cesta(CestaCompra $cesta){
        $cesta->get_productos();
        $cesta->get_unidades();
        return $this->render('cesta/mostrar_cesta.html.twig', [
            'productos' => $cesta->get_productos(),
            'unidades' => $cesta->get_unidades()
        ]);
    }
    
    #[Route('/eliminar/{id}', name: 'eliminar')]
    public function eliminar_producto(int $id, Request $request): Response
    {
        $sesion = $request->getSession();

        // Obtener productos y unidades de la sesión
        $productos = $sesion->get('productos', []);
        $unidades = $sesion->get('unidades', []);

        // Eliminar el producto
        if (isset($productos[$id])) {
            unset($productos[$id]);
            unset($unidades[$id]);

            // Guardar de vuelta en la sesión
            $sesion->set('productos', $productos);
            $sesion->set('unidades', $unidades);
        }

        return $this->redirectToRoute('cesta');
    }
    
    
}
