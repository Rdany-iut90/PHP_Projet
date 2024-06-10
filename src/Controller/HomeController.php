<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\EventRepository;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(EventRepository $eventRepository)
    {
        // affiche seulement les événements publiques si l'utilisateur n'est pas connecté
        if (!$this->getUser()) {
            $events = $eventRepository->findBy(['publique' => true]);
        } else {
            $events = $eventRepository->findAll();
        }

        return $this->render('home/index.html.twig', [
            'events' => $events,
        ]);
    }
}
