<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Repository\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NotificationController extends AbstractController
{
    #[Route('/notifications', name: 'notification_index')]
    public function index(PaymentRepository $paymentRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $query = $entityManager->createQuery(
            'SELECT p FROM App\Entity\Payment p JOIN p.event e WHERE p.user = :user'
        )->setParameter('user', $user);
        
        $payments = $query->getResult();

        return $this->render('notification/index.html.twig', [
            'payments' => $payments,
        ]);
    }
}
