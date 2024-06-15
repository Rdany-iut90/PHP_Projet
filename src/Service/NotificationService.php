<?php
namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class NotificationService
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function sendEmail(string $to, string $subject, string $content): int
    {
        try {
            $email = (new Email())
                ->from('raphaeldany03@gmail.com')
                ->to($to)
                ->subject($subject)
                ->text($content)
                ->html('<p>' . $content . '</p>');

                $this->mailer->send($email);
                return 1;
            } catch (TransportExceptionInterface $e) {
                return -1;
            }
    }
}
