<?php

namespace App\DataFixtures;

use App\Entity\Usuario;
use App\Entity\Categoria;
use App\Entity\Producto;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // -------------------- Usuario de prueba --------------------
        $usuario = new Usuario();
        $usuario->setLogin('admin');
        $usuario->setEmail('admin@wembli.com');
        $usuario->setPassword($this->passwordHasher->hashPassword($usuario, '123456'));
        $usuario->setRoles(['ROLE_ADMIN']);

        $manager->persist($usuario);

        // -------------------- Categorías de prueba --------------------
        $categorias = [];

        $nombres = ['Bebidas', 'Comida', 'Postres', 'Snacks'];
        foreach ($nombres as $nombre) {
            $categoria = new Categoria();
            $categoria->setNombre($nombre);
            $categoria->setDescripcion("Categoría de $nombre");
            $manager->persist($categoria);
            $categorias[] = $categoria;
        }

        // -------------------- Productos de prueba --------------------
        $productos = [
            ['Coca-Cola', 1.50, 0], 
            ['Hamburguesa', 5.00, 1],
            ['Tarta de Chocolate', 3.50, 2],
            ['Patatas Fritas', 2.00, 3]
        ];

        foreach ($productos as $p) {
            $producto = new Producto();
            $producto->setNombre($p[0]);
            $producto->setPrecio($p[1]);
            $producto->setCategoria($categorias[$p[2]]);
            $producto->setCreador($usuario);
            $manager->persist($producto);
        }

        $manager->flush();
    }
}
