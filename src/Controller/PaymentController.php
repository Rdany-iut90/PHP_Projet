<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Payment;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PaymentController extends AbstractController
{
    #[Route('/create-checkout-session/{eventId}', name: 'create_checkout_session')]
    public function createCheckoutSession(int $eventId, EntityManagerInterface $entityManager)
    {
        $event = $entityManager->getRepository(Event::class)->find($eventId);
        Stripe::setApiKey($this->getParameter('stripe.secret_key'));
    
        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $event->getTitre(),
                    ],
                    'unit_amount' => $event->getCost() * 100, // Assuming cost is in dollars
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $this->generateUrl('payment_success', ['eventId' => $event->getId(), 'userId' => $this->getUser()->getId()], true),
            'cancel_url' => $this->generateUrl('payment_cancel', [], true),
        ]);
    
        return new JsonResponse(['id' => $session->id]);
    }

    #[Route('/payment/success', name: 'payment_success')]
    public function paymentSuccess(Request $request, EntityManagerInterface $entityManager): Response
    {
        $eventId = $request->query->get('eventId');
        $userId = $request->query->get('userId');

        $event = $entityManager->getRepository(Event::class)->find($eventId);
        $user = $entityManager->getRepository(User::class)->find($userId);

        if ($event && $user) {
            $event->addParticipant($user);

            $payment = new Payment();
            $payment->setUser($user);
            $payment->setEvent($event);
            $payment->setStatus('paid');
            $entityManager->persist($payment);
            $entityManager->flush();

            $this->addFlash('success', 'Paiement réussi. Vous êtes inscrit à l\'événement.');

            $this->notificationService->sendEmail(
                $user->getEmail(),
                'Inscription à l\'événement',
                'Vous êtes inscrit à l\'événement ' . $event->getTitre() . ' qui aura lieu le ' . $event->getDateHeure()->format('d/m/Y H:i') . '.'
            );

            return $this->render('payment/success.html.twig');
        }

        $this->addFlash('error', 'Erreur lors de l\'inscription à l\'événement.');
        return $this->redirectToRoute('event_list');
    }

    #[Route('/payment/cancel', name: 'payment_cancel')]
    public function paymentCancel(): Response
    {
        return $this->render('payment/cancel.html.twig');
    }
}