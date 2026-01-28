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
use App\Entity\Pedido;
use App\Entity\PedidoProducto;
use App\Services\CestaCompra;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

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
    
    #[Route('/eliminar/{id}', name: 'eliminar', methods: ['POST'])]
    public function eliminar_producto(int $id, Request $request): Response
    {
        $sesion = $request->getSession();
        $cantidad = (int) $request->request->get('cantidad', 1);

        // Obtener productos y unidades de la sesión
        $productos = $sesion->get('productos', []);
        $unidades = $sesion->get('unidades', []);

        // Si el producto existe en la cesta
        if (isset($productos[$id])) {
            // Restar la cantidad especificada
            $unidades[$id] -= $cantidad;
            
            // Si quedan 0 o menos unidades, eliminar el producto completamente
            if ($unidades[$id] <= 0) {
                unset($productos[$id]);
                unset($unidades[$id]);
            }

            // Guardar de vuelta en la sesión
            $sesion->set('productos', $productos);
            $sesion->set('unidades', $unidades);
        }

        return $this->redirectToRoute('cesta');
    }
    
    //Cambiamos el Manager por el Entity ya que no nos dejaría utilizar el persist
    #[Route('/pedido', name: 'pedido')]
    public function pedido(CestaCompra $cesta, EntityManagerInterface $em, MailerInterface $mailer)
    {   
        // Iniciamos las variables
        $error = 0;
        $pedido = null;
        $productos = $cesta->get_productos();
        $unidades  = $cesta->get_unidades();
        
        if(count($productos) == 0){
            //Valor 1 cuando no hay productos en la cesta
            $error = 1;
        } else {
            //Generamos un nuevo objeto Pedido con sus Setters
            $pedido = new Pedido();
            $pedido->setCoste($cesta->calcular_coste());
            //Hacemos un objeto nuevo para poder conseguir la hora actual
            $pedido->setFecha(new \DateTime());
            $pedido->setUsuario($this->getUser());
            //Permance en espera con ese pedido
            $em->persist($pedido);
            

            // Hacemos un for para asignar los productos
            foreach ($productos as $codigo_producto => $productoCesta) {
                $pedidoProducto = new PedidoProducto();
                $pedidoProducto->setPedido($pedido);
                
                $producto = $em->getRepository(Producto::class)->findBy(['id' => $productoCesta -> getId()])[0];
                        
                $pedidoProducto->setProducto($producto);
                // Asignamos el codigo producto a las unidades
                $pedidoProducto->setUnidades($unidades[$codigo_producto]);
                // Generamos el persist
                $em->persist($pedidoProducto);
            }
            try{
                // El flush hace que se guarde en la base de datos
                $em->flush();
            } catch (\Exception $ex) {
                // Este error será porque falla el acceso a la BD
                $error = 2;
            }
            
            if (!$error) {
                $email = (new TemplatedEmail())
                    ->from('noreply@example.com')
                    ->to(new Address($this->getUser()->getEmail()))
                    ->subject('Confirmación de pedido #' . $pedido->getId())
                    ->htmlTemplate('correo/correo.html.twig')
                    ->locale('es')
                    ->context([
                        'pedido_id' => $pedido->getId(),
                        'productos' => $productos,
                        'unidades' => $unidades,
                        'coste' => $cesta->calcular_coste(),
                    ]);
                
                try {
                    $mailer->send($email);
                } catch (\Exception $e) {
                    // Si falla el envío de email, solo lo registramos pero no bloqueamos el pedido
                    // En producción, podrías usar un logger: $this->logger->error('Error enviando email: ' . $e->getMessage());
                }
                
                // Vaciar la cesta después de un pedido exitoso
                $request = $this->container->get('request_stack')->getCurrentRequest();
                $sesion = $request->getSession();
                $sesion->remove('productos');
                $sesion->remove('unidades');
            }
        }
        
        return $this->render('pedidos/pedido.html.twig', [
            'pedido_id' => $pedido ? $pedido->getId() : null,
            'error' => $error
        ]);
    }
    
    #[Route('/pedidos', name: 'pedidos')]
    public function mostrar_pedidos(ManagerRegistry $doctrine): Response
    {
        // Obtener los pedidos del usuario autenticado
        $pedidos = $doctrine->getRepository(Pedido::class)->findBy(
            ['Usuario' => $this->getUser()],
            ['fecha' => 'DESC']
        );
        
        return $this->render('pedidos/pedidos.html.twig', [
            'pedidos' => $pedidos,
        ]);
    }
}
