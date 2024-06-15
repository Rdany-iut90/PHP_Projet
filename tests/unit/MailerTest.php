<?php
namespace App\tests\unit;

use PHPUnit\Framework\TestCase;
use App\Service\NotificationService;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Exception\TransportException;

class MailerTest extends TestCase
{
    private $mailer;
    private NotificationService $notificationService;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->notificationService = new NotificationService($this->mailer);
    }

    public function testSendEmailSuccess(): void
    {
        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(Email::class));

        $result = $this->notificationService->sendEmail('test@example.com', 'Test Subject', 'Test Content');

        $this->assertEquals(1, $result);
    }

    public function testSendEmailFailure(): void
    {
        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(Email::class))
            ->willThrowException(new TransportException());

        $result = $this->notificationService->sendEmail('test@example.com', 'Test Subject', 'Test Content');

        $this->assertEquals(-1, $result);
    }
}