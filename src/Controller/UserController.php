<?php
namespace App\Controller;

use App\Form\UserProfileType;
use App\Form\ChangePasswordType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class UserController extends AbstractController
{
    #[Route('/user/profile', name: 'user_profile')]
    public function profile(
        Request $request, 
        UserPasswordHasherInterface $passwordHasher, 
        EntityManagerInterface $entityManager, 
        ValidatorInterface $validator, 
        UserRepository $userRepository
    ): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour voir cette page.');
        }

        $passwordForm = $this->createForm(ChangePasswordType::class);
        $profileForm = $this->createForm(UserProfileType::class, $user);

        $passwordForm->handleRequest($request);
        $profileForm->handleRequest($request);

        $passwordErrors = $passwordForm->isSubmitted() && !$passwordForm->isValid();
        $profileErrors = $profileForm->isSubmitted() && !$profileForm->isValid();

        try {
            if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
                $newPassword = $passwordForm->get('password')->getData();
                $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
                $entityManager->flush();
                $this->addFlash('success', 'Mot de passe mis à jour avec succès.');
                return $this->redirectToRoute('user_profile');
            }

            if ($profileForm->isSubmitted()) {
                $errors = $validator->validate($user);
                if (count($errors) === 0) {
                    $newEmail = $profileForm->get('email')->getData();
                    if ($userRepository->isEmailTaken($newEmail, $user->getId())) {
                        $this->addFlash('error', 'Cette adresse email est déjà utilisée.');
                        $entityManager->refresh($user);
                    } else {
                        $entityManager->flush();
                        $this->addFlash('success', 'Informations mises à jour avec succès.');
                        return $this->redirectToRoute('user_profile');
                    }
                } else {
                    $entityManager->refresh($user);
                }
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la mise à jour de votre profil.');
        }

        return $this->render('user/profile.html.twig', [
            'passwordForm' => $passwordForm->createView(),
            'profileForm' => $profileForm->createView(),
            'passwordErrors' => $passwordErrors,
            'profileErrors' => $profileErrors,
        ]);
    }
}
