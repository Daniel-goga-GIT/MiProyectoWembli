<?php

namespace App\Services;

use App\Entity\Producto;
use Symfony\Component\HttpFoundation\RequestStack;

class CestaCompra
{
    protected $productos;
    protected $unidades;
    protected $requestStack;

    public function __construct(RequestStack $requestStack){
        $this->requestStack = $requestStack;
    }

    //Recibe como parámetros los productos y las unidades del formulario
    public function cargar_articulos($productos, $unidades){
        for($i = 0; $i < count($productos); $i++){
            if($unidades[$i] != 0){
                $this->cargar_producto($productos[$i], $unidades[$i]);
            }
        }
    }

    //Recibe como parámetro el objecto producto con su unidad y la carga a la cesta
    public function cargar_producto($producto, $unidad){
        $this->cargar_cesta();
        $codigo = $producto->getId();  // ← Usar getId() en vez de getCode()

        //Cojo el código y miro si está en la cesta
        if(array_key_exists($codigo, $this->productos)){
            $this->unidades[$codigo] += $unidad;  // ← Usar mismo código como clave
        } else if ($unidad != 0) {
            $this->productos[$codigo] = $producto;  // ← Guardar con código como clave
            $this->unidades[$codigo] = $unidad;     // ← Guardar con código como clave
        }

        $this->guardar_cesta();
    }

    //recupera el array de productos y unidades de la sesion
    protected function cargar_cesta() {
        $sesion = $this->requestStack->getSession();

        if($sesion->has('productos') && $sesion->has('unidades')){
            $this->productos = $sesion->get("productos");
            $this->unidades = $sesion->get("unidades");
        } else {
            $this->productos = [];
            $this->unidades = [];
        }
    }
    
    protected function guardar_cesta(){
        $sesion = $this->requestStack->getSession();
        $sesion->set('productos', $this->productos);
        $sesion->set('unidades', $this->unidades);
    }
    
    public function get_productos(){
        $this->cargar_cesta();
        return $this->productos;
    }
    
    public function get_unidades(){
        $this->cargar_cesta();
        return $this->unidades;
    }
    
    public function eliminar_producto($codigo_producto, $unidades){
        if (array_key_exists($codigo_producto, $this->productos)){
            $this->unidades[$codigo_producto] = $this->unidades[$codigo_producto] - $unidades;
            if ($this->unidades[$codigo_producto] <= 0){
                unset($this->unidades[$codigo_producto]);
                unset($this->productos[$codigo_producto]);
            }
            $this->guardar_cesta();
        }
    }
    
    public function calcular_coste()
    {
        $resultado = 0;
        foreach ($this->productos as $codigo_producto => $producto) {
            $resultado += $producto->getPrecio() * $this->unidades[$codigo_producto];
        }
        return $resultado;
    }
    
    public function is_vacia()
    {
        $this->cargar_cesta();
        return count($this->productos) === 0;
    }
}