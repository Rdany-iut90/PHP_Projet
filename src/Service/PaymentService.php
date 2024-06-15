<?php

namespace App\Service;

use App\Entity\Payment;
use App\Entity\User;
use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;

class PaymentService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function createPayment(User $user, Event $event, string $status): Payment
    {
        $payment = new Payment();
        $payment->setUser($user);
        $payment->setEvent($event);
        $payment->setStatus($status);
        $payment->setCreatedAt(new \DateTime());

        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        return $payment;
    }
}