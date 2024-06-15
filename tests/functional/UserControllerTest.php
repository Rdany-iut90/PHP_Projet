<?php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\User;

class UserControllerTest extends WebTestCase
{
    private $entityManager;
    private $user;

    protected function setUp(): void
    {
        // Create and return a user object with necessary methods and properties
        $this->user = new User();
        $this->user->setNom('Test');
        $this->user->setPrenom('User');
        $this->user->setEmail('test@example.com');
        $this->user->setRoles(['ROLE_USER']);
        $hashedPassword = $client->getContainer()->get('security.password_hasher')->hashPassword($this->user, 'password');
        $this->user->setPassword($hashedPassword);

        $this->entityManager = $client->getContainer()->get('doctrine')->getManager();
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        $this->entityManager->remove($this->user);
        $this->entityManager->flush(); 
    }

    public function testProfile()
    {
        $client = static::createClient();
        
        $user = $this->createUser($client);
        // Simulate a logged-in user
        $client->loginUser($user);

        // Simulate a GET request to the profile page
        $client->request('GET', '/user/profile');
        $this->assertResponseIsSuccessful();

        // Simulate a POST request to update the password
        $crawler = $client->request('GET', '/user/profile');
        $form = $crawler->selectButton('Update Password')->form();
        $form['change_password[password]'] = 'newpassword123';
        $client->submit($form);

        $this->assertResponseRedirects('/user/profile');
        $client->followRedirect();
        $this->assertSelectorTextContains('.flash-success', 'Mot de passe mis à jour avec succès.');

        // Simulate a POST request to update the profile information
        $form = $crawler->selectButton('Save Changes')->form();
        $form['user_profile[email]'] = 'newemail@example.com';
        $client->submit($form);

        $this->assertResponseRedirects('/user/profile');
        $client->followRedirect();
        $this->assertSelectorTextContains('.flash-success', 'Informations mises à jour avec succès.');

        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }
}
