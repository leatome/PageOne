<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ProfileControllerTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $passwordHasher;
    private User $testUser;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        // supprime l'utilisateur qui a cet email s'il existe
        $existingUser = $this->em->getRepository(User::class)->findOneBy(['email' => 'test@example.com']);
        if ($existingUser) {
            $this->em->remove($existingUser);
            $this->em->flush();
        }

        // utilisateur de test
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));

        $this->em->persist($user);
        $this->em->flush();
        $this->testUser = $user;

        // import des données
        $this->importTestData();
    }

    protected function tearDown(): void
    {
        $this->em->remove($this->testUser);
        $this->em->flush();

        $category = $this->em->getRepository(Category::class)->findOneBy(['name' => 'Fiction']);
        if ($category) {
            $this->em->remove($category);
            $this->em->flush();
        }

        parent::tearDown();
    }

    private function importTestData(): void
    {
        $kernel = static::getContainer()->get('kernel');
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $command = $application->find('app:import-books');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['--env' => 'test']);
        $commandTester->assertCommandIsSuccessful();
    }

    public function testProfilePageLoadsCorrectly(): void
    {
        $this->client->loginUser($this->testUser);

        $crawler = $this->client->request('GET', '/profil');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3', 'test@example.com');
        $this->assertSelectorExists('option[value="Fiction"]');
    }

    public function testProfilePageWithoutCategories(): void
    {
        $user = new User();
        $user->setEmail('nocat@example.com');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
        $this->em->persist($user);
        $this->em->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/profil');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3', 'nocat@example.com');
        $this->assertSelectorTextContains('p', 'Aucun livre dans votre collection pour le moment.');

        $this->em->remove($user);
        $this->em->flush();
    }

    public function testRedirectIfNotLoggedIn(): void
    {
        $this->client->request('GET', '/profil');
        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects('/login');
    }
}
