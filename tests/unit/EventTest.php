<?php
namespace App\tests\unit;

use PHPUnit\Framework\TestCase;
use App\Service\EventService;
use App\Entity\Event;

class EventTest extends TestCase
{
    public function testGetAvailableSpots(){

        $event = new Event();
        $event->setMaxParticipants(10);
        $eventService = new EventService();
        $this->assertEquals(10, $eventService->getAvailableSpots($event));

    }
}
