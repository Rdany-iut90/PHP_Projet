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
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'test@gmail.com']);
        
        $client->loginUser($user);
        
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
    }
}