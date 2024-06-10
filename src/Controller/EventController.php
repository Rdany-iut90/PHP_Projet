<?php

namespace App\Controller;

use App\Entity\Event;
use App\Form\EventType;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class EventController extends AbstractController
{
    #[Route('/event/create', name: 'event_create')]
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

    #[Route('/events', name: 'event_list')]
    public function list(EventRepository $eventRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $queryBuilder = $eventRepository->createQueryBuilder('e');

        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_USER')) {
            // Les utilisateurs connectés peuvent voir tous les événements
            $queryBuilder->orderBy('e.dateHeure', 'ASC');
        } else {
            // Les utilisateurs non connectés ne peuvent voir que les événements publics
            $queryBuilder->where('e.publique = :publique')
                         ->setParameter('publique', true)
                         ->orderBy('e.dateHeure', 'ASC');
        }

        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('event/list.html.twig', ['pagination' => $pagination]);
    }

    #[Route('/event/{id}', name: 'event_detail')]
    public function detail(Event $event): Response
    {
        $this->denyAccessUnlessGranted('view', $event);

        return $this->render('event/detail.html.twig', ['event' => $event]);
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
                return $this->render('event/edit.html.twig', ['form' => $form->createView()]);
            }

            $entityManager->flush();
            return $this->redirectToRoute('event_detail', ['id' => $event->getId()]);
        }

        return $this->render('event/edit.html.twig', ['form' => $form->createView()]);
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
}
