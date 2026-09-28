<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);

        $superadminUser = new User();
        $superadminUser->setEmail("super_admin@gmail.com");
        $superadminUser->setUsername("Super Admin");
        $superadminUser->setLastName("Super");
        $superadminUser->setFirstName("Admin");
        $superadminUser->setPlainPassword("super_adminpass");
        $superadminUser->setPassword($this->passwordHasher->hashPassword($superadminUser, $superadminUser->getPlainPassword()));
        $superadminUser->setRoles(["ROLE_SUPER_ADMIN"]);
        $manager->persist($superadminUser);

        $adminUser = new User();
        $adminUser->setEmail("admin@gmail.com");
        $adminUser->setUsername("Admin");
        $adminUser->setLastName("Simple");
        $adminUser->setFirstName("Admin");
        $adminUser->setPlainPassword("adminpass");
        $adminUser->setPassword($this->passwordHasher->hashPassword($adminUser, $adminUser->getPlainPassword()));
        $adminUser->setRoles(["ROLE_ADMIN"]);
        $manager->persist($adminUser);

        $manager->flush();
    }
}
