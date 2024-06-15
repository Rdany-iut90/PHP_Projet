<?php

namespace App\Controller;

use App\Repository\PaymentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NotificationController extends AbstractController
{
    #[Route('/notification', name: 'notification_index')]
    public function index(PaymentRepository $paymentRepository): Response
    {
        $user = $this->getUser();
        $payments = $paymentRepository->findBy(['user' => $user]);
        return $this->render('notification/index.html.twig', [
            'payments' => $payments,
        ]);
    }
}
