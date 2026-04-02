<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\SignalementMotif;
use App\Enum\SignalementStatus;
use App\Repository\BusStopRepository;
use App\Repository\MotifGraviteRepository;
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
    public function index(
        Request $request,
        SignalementRepository $signalementRepository,
        BusStopRepository $busStopRepository,
    ): Response
    {
        $statusFilter = SignalementStatus::tryFrom((string) $request->query->get('status', ''));
        $motifFilter = SignalementMotif::tryFrom((string) $request->query->get('motif', ''));
        $stopFilter = trim((string) $request->query->get('stop', ''));

        $qb = $signalementRepository->createQueryBuilder('ticket')
            ->leftJoin('ticket.stop', 'stop')
            ->addSelect('stop')
            ->orderBy('ticket.prioriteScore', 'DESC')
            ->addOrderBy('ticket.submittedAt', 'DESC');

        if ($statusFilter !== null) {
            $qb->andWhere('ticket.status = :status')->setParameter('status', $statusFilter);
        }

        if ($motifFilter !== null) {
            $qb->andWhere('ticket.motif = :motif')->setParameter('motif', $motifFilter);
        }

        if ($stopFilter !== '') {
            $qb->andWhere('stop.id = :stopId')->setParameter('stopId', $stopFilter);
        }

        $tickets = $qb->getQuery()->getResult();

        $kpis = [
            'total' => count($tickets),
            'pending' => 0,
            'validated' => 0,
            'dismissed' => 0,
            'legal' => 0,
        ];

        foreach ($tickets as $ticket) {
            if ($ticket->getStatus() === SignalementStatus::EnAttenteValidation) {
                ++$kpis['pending'];
            }
            if ($ticket->getStatus() === SignalementStatus::Valide) {
                ++$kpis['validated'];
            }
            if ($ticket->getStatus() === SignalementStatus::SansSuite) {
                ++$kpis['dismissed'];
            }
            if ($ticket->getStatus() === SignalementStatus::EscaladeJuridique) {
                ++$kpis['legal'];
            }
        }

        return $this->render('moderation/tickets.html.twig', [
            'tickets' => $tickets,
            'statuses' => SignalementStatus::moderationCases(),
            'motifs' => SignalementMotif::cases(),
            'stops' => $busStopRepository->findBy([], ['label' => 'ASC']),
            'filters' => [
                'status' => $statusFilter?->value,
                'motif' => $motifFilter?->value,
                'stop' => $stopFilter,
            ],
            'kpis' => $kpis,
        ]);
    }

    #[Route('/dashboard', name: 'app_moderation_dashboard', methods: ['GET'])]
    public function dashboard(SignalementRepository $signalementRepository): Response
    {
        $tickets = $signalementRepository->createQueryBuilder('ticket')
            ->leftJoin('ticket.stop', 'stop')
            ->addSelect('stop')
            ->orderBy('ticket.submittedAt', 'DESC')
            ->getQuery()
            ->getResult();

        $total = count($tickets);
        $pending = 0;
        $validated = 0;
        $dismissed = 0;
        $legal = 0;
        $resolved = 0;

        $categoryCounts = [];
        $statusCounts = [
            'En attente' => 0,
            'En cours' => 0,
            'Valide' => 0,
            'Sans suite' => 0,
            'Escalade juridique' => 0,
            'Resolu' => 0,
        ];

        foreach ($tickets as $ticket) {
            $status = $ticket->getStatus();

            if ($status === SignalementStatus::EnAttenteValidation) {
                ++$pending;
                ++$statusCounts['En attente'];
            } elseif ($status === SignalementStatus::EnCours) {
                ++$statusCounts['En cours'];
            } elseif ($status === SignalementStatus::Valide) {
                ++$validated;
                ++$statusCounts['Valide'];
            } elseif ($status === SignalementStatus::SansSuite) {
                ++$dismissed;
                ++$statusCounts['Sans suite'];
            } elseif ($status === SignalementStatus::EscaladeJuridique) {
                ++$legal;
                ++$statusCounts['Escalade juridique'];
            } elseif ($status === SignalementStatus::Resolu) {
                ++$resolved;
                ++$statusCounts['Resolu'];
            }

            $motifLabel = $ticket->getMotif()?->label() ?? 'Non classe';
            $categoryCounts[$motifLabel] = ($categoryCounts[$motifLabel] ?? 0) + 1;
        }

        arsort($categoryCounts);
        $topCategories = array_slice($categoryCounts, 0, 6, true);
        $maxCategory = max(1, ...array_values($topCategories ?: ['default' => 1]));

        $categoryBars = [];
        foreach ($topCategories as $label => $count) {
            $categoryBars[] = [
                'label' => $label,
                // Normalized value to keep chart bars visually bounded.
                'value' => (int) max(1, ceil(($count / $maxCategory) * 6)),
            ];
        }

        $statusColors = [
            'En attente' => '#3b82f6',
            'En cours' => '#8b5cf6',
            'Valide' => '#ec4899',
            'Sans suite' => '#10b981',
            'Escalade juridique' => '#ef4444',
            'Resolu' => '#f59e0b',
        ];

        $statusDistribution = [];
        foreach ($statusCounts as $label => $count) {
            $percentage = $total > 0 ? (int) round(($count / $total) * 100) : 0;
            $statusDistribution[] = [
                'label' => $label,
                'value' => $percentage,
                'color' => $statusColors[$label],
            ];
        }

        $recentTickets = [];
        foreach (array_slice($tickets, 0, 6) as $ticket) {
            $priorityScore = $ticket->getPrioriteScore();
            $priorityLabel = $priorityScore >= 75 ? 'Critique' : ($priorityScore >= 50 ? 'Haute' : ($priorityScore >= 25 ? 'Moyenne' : 'Faible'));
            $priorityColor = $priorityScore >= 75 ? '#dc2626' : ($priorityScore >= 50 ? '#ea580c' : ($priorityScore >= 25 ? '#2563eb' : '#16a34a'));

            $recentTickets[] = [
                'id' => $ticket->getId(),
                'priority' => $priorityLabel,
                'priorityColor' => $priorityColor,
                'title' => $ticket->getMotif()?->label() ?? 'Incident',
                'tags' => array_values(array_filter([
                    $ticket->getMotif()?->value,
                    $ticket->getStatus()->label(),
                ])),
                'description' => mb_strimwidth((string) $ticket->getDetails(), 0, 180, '...'),
                'location' => $ticket->getStop()?->getLabel() ?? 'Arret inconnu',
                'source' => 'Formulaire signalement',
                'date' => $ticket->getSubmittedAt()->format('d/m/Y H:i'),
                'confidence' => $ticket->getConfianceScore(),
                'assigned' => $ticket->getReviewedBy()?->getEmail() ?? 'Non assigne',
            ];
        }

        $hotspotCounts = [];
        $thresholdDate = new \DateTimeImmutable('-30 days');
        foreach ($tickets as $ticket) {
            $submittedAt = $ticket->getSubmittedAt();
            if ($submittedAt < $thresholdDate) {
                continue;
            }

            $stopLabel = $ticket->getStop()?->getLabel() ?? 'Arret inconnu';
            $hotspotCounts[$stopLabel] = ($hotspotCounts[$stopLabel] ?? 0) + 1;
        }

        arsort($hotspotCounts);
        $hotspots = [];
        foreach (array_slice($hotspotCounts, 0, 3, true) as $place => $count) {
            $level = $count >= 8 ? 'Haute' : ($count >= 4 ? 'Moyenne' : 'Faible');
            $hotspots[] = [
                'place' => $place,
                'level' => $level,
                'window' => '30 derniers jours',
                'incidents' => $count,
            ];
        }

        $kpis = [
            ['label' => 'Total tickets', 'value' => $total, 'color' => '#2f4c99'],
            ['label' => 'En attente', 'value' => $pending, 'color' => '#3b82f6'],
            ['label' => 'En cours', 'value' => $statusCounts['En cours'], 'color' => '#8b5cf6'],
            ['label' => 'Valides', 'value' => $validated, 'color' => '#ec4899'],
            ['label' => 'Sans suite', 'value' => $dismissed, 'color' => '#10b981'],
            ['label' => 'Escalade juridique', 'value' => $legal, 'color' => '#ef4444'],
        ];

        return $this->render('moderation/dahsboard.html.twig', [
            'kpis' => $kpis,
            'categoryBars' => $categoryBars,
            'statusDistribution' => $statusDistribution,
            'recentTickets' => $recentTickets,
            'hotspots' => $hotspots,
            'resolved' => $resolved,
        ]);
    }

    #[Route('/{id}/status', name: 'app_moderation_ticket_status_update', methods: ['POST'], requirements: ['id' => '[0-9a-fA-F\-]{36}'])]
    public function updateStatus(
        string $id,
        Request $request,
        SignalementRepository $signalementRepository,
        MotifGraviteRepository $motifGraviteRepository,
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
        if ($status === null || !in_array($status, SignalementStatus::moderationCases(), true)) {
            $this->addFlash('error', 'Statut invalide.');

            return $this->redirectToRoute('app_moderation_tickets');
        }

        $reviewNote = trim((string) $request->request->get('review_note', ''));

        $ticket->setStatus($status);
        $ticket->setReviewedAt(new \DateTimeImmutable());
        $ticket->setReviewNote($reviewNote !== '' ? $reviewNote : null);

        $user = $this->getUser();
        if ($user instanceof User) {
            $ticket->setReviewedBy($user);
        }

        $confianceDelta = match ($status) {
            SignalementStatus::SansSuite => -15,
            SignalementStatus::Valide, SignalementStatus::EscaladeJuridique => 5,
            default => 0,
        };

        $confianceScore = max(0, min(100, $ticket->getConfianceScore() + $confianceDelta));
        $ticket->setConfianceScore($confianceScore);

        $gravite = $ticket->getMotif() !== null
            ? ($motifGraviteRepository->find($ticket->getMotif())?->getGravite() ?? 1)
            : 1;
        $ticket->setPrioriteScore($this->computePriorityScore($gravite, $ticket->getConfianceScore()));

        $entityManager->flush();

        $this->addFlash('success', 'Statut mis a jour.');

        return $this->redirectToRoute('app_moderation_tickets');
    }

    #[Route('/{id}/status-ajax', name: 'app_moderation_ticket_status_update_ajax', methods: ['POST'], requirements: ['id' => '[0-9a-fA-F\-]{36}'])]
    public function updateStatusAjax(
        string $id,
        Request $request,
        SignalementRepository $signalementRepository,
        MotifGraviteRepository $motifGraviteRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $ticket = $signalementRepository->find($id);

        if ($ticket === null) {
            return $this->json(['error' => 'Ticket not found'], 404);
        }

        $data = json_decode((string) $request->getContent(), true);
        $status = SignalementStatus::tryFrom((string) ($data['status'] ?? ''));

        if ($status === null || !in_array($status, SignalementStatus::moderationCases(), true)) {
            return $this->json(['error' => 'Invalid status'], 400);
        }

        $ticket->setStatus($status);
        $ticket->setReviewedAt(new \DateTimeImmutable());

        $user = $this->getUser();
        if ($user instanceof User) {
            $ticket->setReviewedBy($user);
        }

        $confianceDelta = match ($status) {
            SignalementStatus::SansSuite => -15,
            SignalementStatus::Valide, SignalementStatus::EscaladeJuridique => 5,
            default => 0,
        };

        $confianceScore = max(0, min(100, $ticket->getConfianceScore() + $confianceDelta));
        $ticket->setConfianceScore($confianceScore);

        $gravite = $ticket->getMotif() !== null
            ? ($motifGraviteRepository->find($ticket->getMotif())?->getGravite() ?? 1)
            : 1;
        $ticket->setPrioriteScore($this->computePriorityScore($gravite, $ticket->getConfianceScore()));

        $entityManager->flush();

        return $this->json(['success' => true, 'status' => $status->value]);
    }

    private function computePriorityScore(int $gravite, int $confianceScore): int
    {
        $score = ($gravite * 15) + intdiv($confianceScore, 4);

        return max(0, min(100, $score));
    }
}
