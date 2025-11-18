<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use App\Entity\Producto;

class CestaCompra
{
    private $session;
    private array $cesta = [];

    public function __construct(private RequestStack $requestStack)
    {
        $this->session = $requestStack->getSession();
        $this->carga_cesta();
    }

    // Recupera la cesta de la sesión o crea una vacía
    public function carga_cesta(): void
    {
        $this->cesta = $this->session->get('cesta', []);
    }

    // Añade un producto con un número de unidades
    public function carga_articulo(Producto $producto, int $unidades = 1): void
    {
        $id = $producto->getId();
        if (isset($this->cesta[$id])) {
            $this->cesta[$id]['unidades'] += $unidades;
        } else {
            $this->cesta[$id] = [
                'producto' => $producto,
                'unidades' => $unidades
            ];
        }
        $this->guarda_cesta();
    }

    // Guarda la cesta en la sesión
    public function guarda_cesta(): void
    {
        $this->session->set('cesta', $this->cesta);
    }

    // Devuelve array de productos con sus unidades
    public function get_productos(): array
    {
        return $this->cesta;
    }

    // Calcula el coste total
    public function get_coste(): float
    {
        $total = 0;
        foreach ($this->cesta as $item) {
            $total += $item['producto']->getPrecio() * $item['unidades'];
        }
        return $total;
    }

    // Comprueba si la cesta está vacía
    public function is_vacia(): bool
    {
        return empty($this->cesta);
    }

    // Elimina un producto de la cesta
    public function eliminar_producto(int $productoId): void
    {
        if (isset($this->cesta[$productoId])) {
            unset($this->cesta[$productoId]);
            $this->guarda_cesta();
        }
    }
}
