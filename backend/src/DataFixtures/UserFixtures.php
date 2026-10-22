<?php

namespace App\DataFixtures;

use App\Entity\Security\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(
        protected UserPasswordHasherInterface $passwordHasher,
        #[Autowire(env: 'FIXTURE_ADMIN_PASSWORD')]
        protected string $adminPassword
    )
    {
    }

    public function load(ObjectManager $manager): void
    {
        if (count($manager->getRepository(User::class)->findAll()) === 0) {
            $user = new User();
            $user->setEmail("admin@localhost.cz");

            $hashedPassword = $this->passwordHasher->hashPassword($user, $this->adminPassword);
            $user->setPassword($hashedPassword);

            $manager->persist($user);
            $manager->flush();
        }
    }

    public static function getGroups(): array
    {
        return ['user'];
    }
}
