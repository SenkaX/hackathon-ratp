<?php

namespace App\Controller;

use App\Enum\SignalementStatus;
use App\Repository\SignalementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/moderation/tickets')]
final class TicketModerationController extends AbstractController
{
    #[Route('', name: 'app_moderation_tickets', methods: ['GET'])]
    public function index(SignalementRepository $signalementRepository): Response
    {
        return $this->render('moderation/tickets.html.twig', [
            'tickets' => $signalementRepository->findBy([], ['submittedAt' => 'DESC']),
            'statuses' => SignalementStatus::cases(),
        ]);
    }

    #[Route('/{id}/status', name: 'app_moderation_ticket_status_update', methods: ['POST'], requirements: ['id' => '[0-9a-fA-F\-]{36}'])]
    public function updateStatus(
        string $id,
        Request $request,
        SignalementRepository $signalementRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $ticket = $signalementRepository->find($id);

        if ($ticket === null) {
            throw new NotFoundHttpException();
        }

        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('ticket-status-'.$ticket->getId(), $token)) {
            throw $this->createAccessDeniedException('CSRF token invalid.');
        }

        $status = SignalementStatus::tryFrom((string) $request->request->get('status'));
        if ($status === null) {
            $this->addFlash('error', 'Statut invalide.');

            return $this->redirectToRoute('app_moderation_tickets');
        }

        $ticket->setStatus($status);
        $entityManager->flush();

        $this->addFlash('success', 'Statut mis a jour.');

        return $this->redirectToRoute('app_moderation_tickets');
    }
}
