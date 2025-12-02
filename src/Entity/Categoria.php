<?php
namespace App\Entity;

use App\Repository\CategoriaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoriaRepository::class)]
#[ORM\Table(name: 'categoria')]
class Categoria
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nombre = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $descripcion = null;

    // ⬇️ ESTO ES LO QUE FALTA ⬇️
    #[ORM\OneToMany(targetEntity: Producto::class, mappedBy: 'categoria')]
    private Collection $productos;

    // ⬇️ Y ESTO EN EL CONSTRUCTOR ⬇️
    public function __construct()
    {
        $this->productos = new ArrayCollection();
    }

    // -------------------- Getters y Setters --------------------
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): self
    {
        $this->nombre = $nombre;
        return $this;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(?string $descripcion): self
    {
        $this->descripcion = $descripcion;
        return $this;
    }

    // ⬇️ Y ESTE MÉTODO ⬇️
    /**
     * @return Collection<int, Producto>
     */
    public function getProductos(): Collection
    {
        return $this->productos;
    }

    public function addProducto(Producto $producto): self
    {
        if (!$this->productos->contains($producto)) {
            $this->productos->add($producto);
            $producto->setCategoria($this);
        }
        return $this;
    }

    public function removeProducto(Producto $producto): self
    {
        if ($this->productos->removeElement($producto)) {
            if ($producto->getCategoria() === $this) {
                $producto->setCategoria(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->nombre ?? '';
    }
}