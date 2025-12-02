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

    public function cargar_articulos($productos, $unidades){
        for($i = 0; $i < count($productos); $i++){
            if($unidades[$i] != 0){
                $this->cargar_producto($productos[$i], $unidades[$i]);
            }
        }
    }

    public function cargar_producto($producto, $unidad){
        $this->cargar_cesta();
        $codigo = $producto->getId();  // ← Usar getId() en vez de getCode()

        if(array_key_exists($codigo, $this->productos)){
            $this->unidades[$codigo] += $unidad;  // ← Usar mismo código como clave
        } else {
            $this->productos[$codigo] = $producto;  // ← Guardar con código como clave
            $this->unidades[$codigo] = $unidad;     // ← Guardar con código como clave
        }

        $this->guardar_cesta();
    }

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
}