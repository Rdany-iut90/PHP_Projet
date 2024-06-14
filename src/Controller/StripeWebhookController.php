<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class StripeWebhookController extends AbstractController
{
    #[Route('/stripe/webhook', name: 'stripe_webhook')]
    public function handleStripeWebhook(Request $request)
    {
        $payload = @file_get_contents('php://input');
        $event = null;

        try {
            $event = \Stripe\Event::constructFrom(json_decode($payload, true));
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            return new Response('', 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                // Update payment status in your database
                $this->updatePaymentStatus($session);
                break;
            // ... handle other event types
            default:
                return new Response('', 400);
        }

        return new Response('', 200);
    }

    private function updatePaymentStatus($session)
    {
        // Logic to update payment status in the database
    }
}