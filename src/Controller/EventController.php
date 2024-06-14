<?php

namespace App\Controller;

use App\Entity\Event;
use App\Form\EventType;
use App\Repository\EventRepository;
use App\Service\EventService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class EventController extends AbstractController
{
    private $eventService;
    private $notificationService;

    public function __construct(EventService $eventService, NotificationService $notificationService)
    {
        $this->eventService = $eventService;
        $this->notificationService = $notificationService;
    }

    #[Route('/events', name: 'event_list')]
    public function list(EventRepository $eventRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $queryBuilder = $eventRepository->createQueryBuilder('e')
                                         ->orderBy('e.dateHeure', 'ASC');

        if (!$this->isGranted('ROLE_USER')) {
            $queryBuilder->where('e.publique = :publique')
                         ->setParameter('publique', true);
        }

        if ($title = $request->query->get('title')) {
            $queryBuilder->andWhere('e.titre LIKE :title')
                         ->setParameter('title', '%' . $title . '%');
        }

        if (($publique = $request->query->get('publique')) !== null && $publique !== '') {
            $queryBuilder->andWhere('e.publique = :publique')
                         ->setParameter('publique', (bool)$publique);
        }

        if ($date = $request->query->get('date')) {
            $queryBuilder->andWhere('e.dateHeure >= :date_start AND e.dateHeure <= :date_end')
                         ->setParameter('date_start', (new \DateTime($date))->setTime(0, 0, 0))
                         ->setParameter('date_end', (new \DateTime($date))->setTime(23, 59, 59));
        }

        if ($participantsMin = $request->query->get('participants_min')) {
            $queryBuilder->andWhere('e.maxParticipants >= :participants_min')
                         ->setParameter('participants_min', (int)$participantsMin);
        }

        if ($participantsMax = $request->query->get('participants_max')) {
            $queryBuilder->andWhere('e.maxParticipants <= :participants_max')
                         ->setParameter('participants_max', (int)$participantsMax);
        }

        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('event/list.html.twig', ['pagination' => $pagination]);
    }

    #[Route('/event/create', name: 'event_create')]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $event = new Event();
        $form = $this->createForm(EventType::class, $event);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $now = new \DateTime();
            if ($event->getDateHeure() <= $now) {
                $this->addFlash('error', 'La date de l\'événement doit être après la date du jour (' . $now->format('d/m/Y') . ').');
                return $this->render('event/create.html.twig', ['form' => $form->createView()]);
            }

            $event->setUser($this->getUser());
            $entityManager->persist($event);
            $entityManager->flush();
            return $this->redirectToRoute('event_list');
        }

        return $this->render('event/create.html.twig', ['form' => $form->createView()]);
    }

     #[Route('/event/{id}', name: 'event_show')]
    public function show(Event $event): Response
    {
        return $this->render('event/show.html.twig', ['event' => $event]);
    }

    #[Route('/event/{id}/edit', name: 'event_edit')]
    #[IsGranted('edit', subject: 'event')]
    public function edit(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $now = new \DateTime();
            if ($event->getDateHeure() <= $now) {
                $this->addFlash('error', 'La date de l\'événement doit être après la date du jour (' . $now->format('d/m/Y') . ').');
                return $this->render('event/edit.html.twig', ['form' => $form->createView(), 'event' => $event]);
            }

            $entityManager->flush();
            return $this->redirectToRoute('event_list');
        }

        return $this->render('event/edit.html.twig', ['form' => $form->createView(), 'event' => $event]);
    }

    #[Route('/event/{id}/delete', name: 'event_delete')]
    #[IsGranted('delete', subject: 'event')]
    public function delete(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$event->getId(), $request->request->get('_token'))) {
            $entityManager->remove($event);
            $entityManager->flush();
            $this->addFlash('success', 'Événement supprimé avec succès.');
        }

        return $this->redirectToRoute('event_list');
    }

    #[Route('/event/{id}/register', name: 'event_register')]
    #[IsGranted('ROLE_USER')]
    public function register(Event $event, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $availableSpots = $this->eventService->getAvailableSpots($event);

        if ($availableSpots <= 0) {
            $this->addFlash('error', 'L\'événement est complet vous ne pouvez plus vous inscrire.');
        } elseif ($event->getParticipants()->contains($user)) {
            $this->addFlash('error', 'Vous êtes déjà inscrit à cet événement.');
        } else {
            $event->addParticipant($user);
            $entityManager->flush();

            $this->addFlash('success', 'Vous êtes inscrit à l\'événement.');

            // Envoi de l'email de notification
            $this->notificationService->sendEmail(
                $user->getEmail(),
                'Inscription à l\'événement',
                'Vous êtes inscrit à l\'événement ' . $event->getTitre() . ' qui aura lieu le ' . $event->getDateHeure()->format('d/m/Y H:i') . '.'
            );
        }

        return $this->redirectToRoute('event_list');
    }

    #[Route('/event/{id}/unregister', name: 'event_unregister')]
    #[IsGranted('ROLE_USER')]
    public function unregister(Event $event, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$event->getParticipants()->contains($user)) {
            $this->addFlash('error', 'Vous n\'êtes pas inscrit à cet événement.');
        } else {
            $event->removeParticipant($user);
            $entityManager->flush();

            $this->addFlash('success', 'Votre inscription a été annulée.');

            // Envoi de l'email de notification
            $this->notificationService->sendEmail(
                $user->getEmail(),
                'Annulation de l\'inscription à l\'événement',
                'Votre inscription à l\'événement ' . $event->getTitre() . ' qui aura lieu le ' . $event->getDateHeure()->format('d/m/Y H:i') . ' a été annulée.'
            );
        }

        return $this->redirectToRoute('event_list');
    }

    #[Route('/my-events', name: 'my_event_list')]
    #[IsGranted('ROLE_USER')]
    public function myEvents(EventRepository $eventRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $user = $this->getUser();
        $queryBuilder = $eventRepository->createQueryBuilder('e')
                                         ->join('e.participants', 'p')
                                         ->where('p.id = :userId')
                                         ->setParameter('userId', $user->getId())
                                         ->orderBy('e.dateHeure', 'ASC');

        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('event/my_events.html.twig', ['pagination' => $pagination]);
    }
}
