<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Entity\User;
use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class WebhookController extends AbstractController
{
    #[Route('/webhook', name: 'stripe_webhook')]
    public function stripeWebhook(Request $request, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        $logger->info('Webhook endpoint hit');

        $payload = @file_get_contents('php://input');
        $sigHeader = $request->headers->get('stripe-signature');
        $event = null;

        $logger->info('Received webhook', ['payload' => $payload, 'sigHeader' => $sigHeader]);

        $secret = $this->getParameter('stripe_webhook_secret');
        if (!$secret) {
            $logger->error('Stripe webhook secret is not configured.');
            return new Response('Webhook secret not configured', 500);
        }

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sigHeader, $secret
            );
            $logger->info('Webhook constructed successfully', ['event' => $event]);
        } catch (\UnexpectedValueException $e) {
            $logger->error('Invalid payload', ['exception' => $e]);
            return new Response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            $logger->error('Invalid signature', ['exception' => $e]);
            return new Response('Invalid signature', 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'checkout.session.completed':
                $this->handleCheckoutSessionCompleted($event, $entityManager, $logger);
                break;

            case 'charge.succeeded':
                $this->handleChargeSucceeded($event, $entityManager, $logger);
                break;

            case 'payment_intent.succeeded':
                $this->handlePaymentIntentSucceeded($event, $entityManager, $logger);
                break;

            case 'charge.updated':
                $this->handleChargeUpdated($event, $entityManager, $logger);
                break;

            // Add other event types as needed
            default:
                $logger->info('Unhandled event type', ['type' => $event->type]);
        }

        return new Response('Received', 200);
    }

    private function handleCheckoutSessionCompleted($event, EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $session = $event->data->object;

        $logger->info('Handling checkout.session.completed', ['session' => $session]);

        // Extract metadata
        $userId = $session->metadata->user_id ?? null;
        $eventId = $session->metadata->event_id ?? null;

        $logger->info('Extracted metadata', ['user_id' => $userId, 'event_id' => $eventId]);

        if ($userId && $eventId) {
            // Find the user and event
            $user = $entityManager->getRepository(User::class)->find($userId);
            $event = $entityManager->getRepository(Event::class)->find($eventId);

            if ($user && $event) {
                // Create a new Payment entity and save it to the database
                $payment = new Payment();
                $payment->setUser($user);
                $payment->setEvent($event);
                $payment->setStatus('succeeded');
                $payment->setCreatedAt(new \DateTime());

                $entityManager->persist($payment);
                $entityManager->flush();

                $logger->info('Payment recorded', ['payment' => $payment]);
            } else {
                $logger->error('User or Event not found', ['user' => $user, 'event' => $event]);
            }
        } else {
            $logger->error('Missing user_id or event_id in metadata', ['metadata' => $session->metadata]);
        }
    }

    private function handleChargeSucceeded($event, EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $charge = $event->data->object;

        $logger->info('Handling charge.succeeded', ['charge' => $charge]);

        // Find the payment entity
        $payment = $entityManager->getRepository(Payment::class)->findOneBy([
            'stripeChargeId' => $charge->id
        ]);

        if ($payment) {
            $payment->setStatus('succeeded');
            $entityManager->flush();
            $logger->info('Payment status updated to succeeded', ['payment' => $payment]);
        } else {
            $logger->error('Payment not found for charge id', ['charge_id' => $charge->id]);
        }
    }

    private function handlePaymentIntentSucceeded($event, EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $paymentIntent = $event->data->object;

        $logger->info('Handling payment_intent.succeeded', ['paymentIntent' => $paymentIntent]);

        // Find the payment entity
        $payment = $entityManager->getRepository(Payment::class)->findOneBy([
            'stripePaymentIntentId' => $paymentIntent->id
        ]);

        if ($payment) {
            $payment->setStatus('succeeded');
            $entityManager->flush();
            $logger->info('Payment status updated to succeeded', ['payment' => $payment]);
        } else {
            $logger->error('Payment not found for payment intent id', ['payment_intent_id' => $paymentIntent->id]);
        }
    }

    private function handleChargeUpdated($event, EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $charge = $event->data->object;

        $logger->info('Handling charge.updated', ['charge' => $charge]);

        // Find the payment entity
        $payment = $entityManager->getRepository(Payment::class)->findOneBy([
            'stripeChargeId' => $charge->id
        ]);

        if ($payment) {
            $payment->setStatus($charge->status);
            $entityManager->flush();
            $logger->info('Payment status updated', ['payment' => $payment, 'status' => $charge->status]);
        } else {
            $logger->error('Payment not found for charge id', ['charge_id' => $charge->id]);
        }
    }
}
