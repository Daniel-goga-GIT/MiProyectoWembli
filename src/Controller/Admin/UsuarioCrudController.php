<?php

namespace App\Controller\Admin;

use App\Entity\Usuario;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UsuarioCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Usuario::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('login', 'Usuario'),
            TextField::new('plainPassword', 'Contraseña')
                ->setFormType(PasswordType::class)
                ->onlyOnForms(),
            EmailField::new('email', 'Correo'),
            ArrayField::new('roles', 'Roles'),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('login')
            ->add('email')
            ->add('roles');
    }

    public function persistEntity(
        \Doctrine\ORM\EntityManagerInterface $entityManager,
        $entityInstance
    ): void {
        $this->hashPassword($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(
        \Doctrine\ORM\EntityManagerInterface $entityManager,
        $entityInstance
    ): void {
        $this->hashPassword($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    private function hashPassword($entityInstance): void
    {
        if (!$entityInstance instanceof Usuario) {
            return;
        }

        $plainPassword = $entityInstance->getPlainPassword();
        if ($plainPassword) {
            $hashed = $this->passwordHasher->hashPassword($entityInstance, $plainPassword);
            $entityInstance->setPassword($hashed);
            $entityInstance->setPlainPassword(null);
        }
    }
}
