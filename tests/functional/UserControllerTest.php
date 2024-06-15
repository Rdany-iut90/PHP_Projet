<?php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use App\Entity\User;

class UserControllerTest extends WebTestCase
{
    private $entityManager;
    private User $user;
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();
        $this->user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'test@gmail.com']);
    }

    public function testProfile()
    {
        // Simulate a logged-in user
        $this->client->loginUser($this->user);

        // Simulate a GET request to the profile page
        $crawler = $this->client->request('GET', '/user/profile');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Profil Utilisateur');

        // Check for the presence of buttons
        $this->assertSelectorExists('#editInfoBtn');
        $this->assertSelectorExists('#changePasswordBtn');

        // Simulate filling and submitting the profile information form
        $form = $crawler->filter('#editInfoForm form')->form([
            'user_profile[nom]' => 'test',
            'user_profile[prenom]' => 'user',
            'user_profile[email]' => 'test@gmail.com'
        ]);
        $this->client->submit($form);

        $this->assertResponseRedirects('/user/profile');
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'Informations mises à jour avec succès.');

        // Simulate filling and submitting the password change form
        $crawler = $this->client->request('GET', '/user/profile'); // Refresh the page to simulate new form submission
        $form = $crawler->filter('#changePasswordForm form')->form([
            'change_password[password][first]' => 'password1234',
            'change_password[password][second]' => 'password1234'
        ]);
        $this->client->submit($form);

        $this->assertResponseRedirects('/user/profile');
        $crawler = $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'Mot de passe mis à jour avec succès.');
    }
}
