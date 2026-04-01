<?php

namespace App\Controller;

use App\Repository\SignalementRepository;
use App\Security\TicketTokenHasher;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class TicketController extends AbstractController
{
    #[Route('/ticket/{id}-{token}', name: 'app_ticket_show', methods: ['GET'], requirements: ['id' => '[0-9a-fA-F\-]{36}', 'token' => '[0-9a-fA-F]{64}'])]
    public function show(string $id, string $token, SignalementRepository $signalementRepository, TicketTokenHasher $ticketTokenHasher): Response
    {
        $signalement = $signalementRepository->find($id);

        if ($signalement === null) {
            throw new NotFoundHttpException();
        }

        $tokenHash = $signalement->getTokenHash();
        $expiresAt = $signalement->getTokenExpiresAt();
        $isExpired = $expiresAt === null || $expiresAt < new \DateTimeImmutable();
        $isInvalidToken = $tokenHash === null || !hash_equals($tokenHash, $ticketTokenHasher->hashToken($token));

        if ($isInvalidToken || $isExpired) {
            throw new NotFoundHttpException();
        }

        return $this->render('ticket/show.html.twig', [
            'signalement' => $signalement,
        ]);
    }
}
