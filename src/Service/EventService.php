<?php

namespace App\Service;

use App\Entity\Event;

class EventService
{
    public function getAvailableSpots(Event $event): int
    {
        return $event->getMaxParticipants() - $event->getParticipants()->count();
    }
}
