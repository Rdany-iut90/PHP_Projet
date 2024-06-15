<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Payment;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaymentController extends AbstractController
{
    private $notificationService;
    private $logger;

    public function __construct(NotificationService $notificationService, LoggerInterface $logger)
    {
        $this->notificationService = $notificationService;
        $this->logger = $logger;
    }

    #[Route('/payment/{id}', name: 'payment')]
    public function payment(Event $event): Response
    {
        $this->logger->info('Initiating payment for event', ['event_id' => $event->getId()]);

        if (!$event->getIsPaid()) {
            throw $this->createNotFoundException('This event is not paid.');
        }

        Stripe::setApiKey($this->getParameter('stripe_secret_key'));

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $event->getTitre(),
                    ],
                    'unit_amount' => $event->getCost() * 100,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $this->generateUrl('payment_success', ['id' => $event->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->generateUrl('payment_cancel', ['id' => $event->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        $this->logger->info('Stripe session created', ['session_id' => $session->id]);

        return $this->render('payment/checkout.html.twig', [
            'sessionId' => $session->id,
            'publicKey' => $this->getParameter('stripe_public_key'),
            'event' => $event,
        ]);
    }

    #[Route('/payment/success/{id}', name: 'payment_success')]
    public function success(Event $event, EntityManagerInterface $entityManager): Response
    {
        $this->logger->info('Payment succeeded for event', ['event_id' => $event->getId()]);

        $user = $this->getUser();
        if ($user && !$event->getParticipants()->contains($user)) {
            $event->addParticipant($user);

            // Ajouter l'enregistrement du paiement
            $payment = new Payment();
            $payment->setUser($user);
            $payment->setEvent($event);
            $payment->setStatus('succeeded');
            $payment->setCreatedAt(new \DateTime());

            $entityManager->persist($payment);
            $entityManager->flush();

            // Envoyer un e-mail de notification
            $this->notificationService->sendEmail(
                $user->getEmail(),
                'Inscription à l\'événement',
                'Votre paiement pour l\'événement ' . $event->getTitre() . ' a été réussi. Vous êtes maintenant inscrit à l\'événement qui aura lieu le ' . $event->getDateHeure()->format('d/m/Y H:i') . '.'
            );

            $this->logger->info('User registered to event and payment recorded', ['event_id' => $event->getId(), 'user_id' => $user->getId()]);
        }

        return $this->render('payment/success.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/payment/cancel/{id}', name: 'payment_cancel')]
    public function cancel(Event $event): Response
    {
        $this->logger->info('Payment cancelled for event', ['event_id' => $event->getId()]);

        return $this->render('payment/cancel.html.twig', [
            'event' => $event,
        ]);
    }
}
