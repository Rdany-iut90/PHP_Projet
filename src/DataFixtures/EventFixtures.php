<?php

namespace App\DataFixtures;

use App\Entity\Event;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class EventFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        
        $user = $this->getReference('user_test');

        for ($i = 1; $i <= 5; $i++) {
            $event = new Event();
            $event->setTitre('Evenement ' . $i);
            $event->setDescription('Description de l`evenement ' . $i);
            $event->setDateHeure((new \DateTime())->modify('+'.($i+1).' jours'));
            $event->setMaxParticipants(10);
            $event->setPublique(true);
            $event->setUser($user);
            $manager->persist($event);
        }

        for ($i = 6; $i <= 10; $i++) {
            $event = new Event();
            $event->setTitre('Event Connected ' . $i);
            $event->setDescription('Description for event Connected ' . $i);
            $event->setDateHeure((new \DateTime())->modify('+'.($i+1).' jours'));
            $event->setMaxParticipants(1);
            $event->setPublique(false);
            $event->setUser($user);
            $manager->persist($event);
        }

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            UserFixtures::class,
        ];
    }
}
