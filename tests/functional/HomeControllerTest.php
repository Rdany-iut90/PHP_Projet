<?php
namespace App\tests\functionnal;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Repository\EventRepository;
use App\Entity\User;

class HomeControllerTest extends WebTestCase
{
    private $entityManager;

    public function testIndexAsAnonymousUser()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
    }

    public function testIndexAsAuthenticatedUser()
    {
        $client = static::createClient();
        $this->entityManager = $client->getContainer()->get('doctrine')->getManager();
        
        // Create a user and persist it
        $user = new User();
        $user->setNom('Test');
        $user->setPrenom('User');
        $user->setEmail('test@example.com');
        $user->setRoles(['ROLE_USER']);
        $hashedPassword = $client->getContainer()->get('security.password_hasher')->hashPassword($user, 'password');
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        $client->loginUser($user);
        
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }
}