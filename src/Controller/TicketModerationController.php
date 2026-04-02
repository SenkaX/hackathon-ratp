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
    #[Route('/projects', name: 'app_moderation_projects', methods: ['GET'])]
    public function projects(): Response
    {
        $projects = [
            [
                'name' => 'Station Chatelet',
                'subtitle' => 'Chatelet',
                'description' => 'Gestion des incidents pour la station Chatelet',
                'lines' => ['21', '38', '47', '58', '69', '70', '+4'],
                'total' => 4,
                'pending' => 1,
                'inProgress' => 1,
                'critical' => 1,
                'iconColor' => '#3b82f6',
            ],
            [
                'name' => 'Station Gare du Nord',
                'subtitle' => 'Gare du Nord',
                'description' => 'Gestion des incidents pour la station Gare du Nord',
                'lines' => ['26', '31', '35', '38', '39', '42', '+4'],
                'total' => 3,
                'pending' => 0,
                'inProgress' => 1,
                'critical' => 0,
                'iconColor' => '#3b82f6',
            ],
            [
                'name' => 'Station République',
                'subtitle' => 'Republique',
                'description' => 'Gestion des incidents pour la station Republique',
                'lines' => ['20', '56', '65', '75'],
                'total' => 3,
                'pending' => 1,
                'inProgress' => 1,
                'critical' => 0,
                'iconColor' => '#3b82f6',
            ],
        ];

        return $this->render('moderation/projects.html.twig', [
            'projects' => $projects,
            'overview' => [
                ['label' => 'Stations suivies', 'value' => 3],
                ['label' => 'Lignes couvertes', 'value' => 18],
                ['label' => 'Tickets ouverts', 'value' => 8],
            ],
        ]);
    }

    #[Route('/users', name: 'app_moderation_users', methods: ['GET'])]
    public function users(): Response
    {
        $users = [
            [
                'name' => 'Marie Dupont',
                'role' => 'Admin',
                'roleColor' => '#6366f1',
                'email' => 'marie.dupont@ratp.fr',
                'description' => 'Acces complet - Validation et decisions strategiques',
                'assigned' => 2,
                'created' => 0,
                'pending' => 0,
                'inProgress' => 0,
                'systemNote' => null,
            ],
            [
                'name' => 'Thomas Martin',
                'role' => 'RH',
                'roleColor' => '#6366f1',
                'email' => 'thomas.martin@ratp.fr',
                'description' => 'Gestion des incidents RH et comportements',
                'assigned' => 3,
                'created' => 0,
                'pending' => 1,
                'inProgress' => 1,
                'systemNote' => null,
            ],
            [
                'name' => 'Sophie Bernard',
                'role' => 'Manager',
                'roleColor' => '#6366f1',
                'email' => 'sophie.bernard@ratp.fr',
                'description' => 'Supervision des operations et coordination avec les equipes terrain',
                'assigned' => 3,
                'created' => 0,
                'pending' => 1,
                'inProgress' => 1,
                'systemNote' => null       
            ],
        ];

        return $this->render('moderation/users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/dashboard', name: 'app_moderation_dashboard', methods: ['GET'])]
    public function dashboard(
        SignalementRepository $signalementRepository,
    ): Response
    {
        $tickets = $signalementRepository->createQueryBuilder('ticket')
            ->leftJoin('ticket.stop', 'stop')
            ->addSelect('stop')
            ->orderBy('ticket.submittedAt', 'DESC')
            ->getQuery()
            ->getResult();

        $statusCounts = [
            'en_attente_validation' => 0,
            'valide' => 0,
            'en_cours' => 0,
            'sans_suite' => 0,
            'escalade_juridique' => 0,
            'resolu' => 0,
        ];

        $criticalTickets = 0;
        $confidenceTotal = 0;
        $recentTickets = [];

        foreach ($tickets as $ticket) {
            $statusValue = $ticket->getStatus()->value;
            if (isset($statusCounts[$statusValue])) {
                ++$statusCounts[$statusValue];
            }

            if ($ticket->getPrioriteScore() >= 75) {
                ++$criticalTickets;
            }

            $confidenceTotal += $ticket->getConfianceScore();

            if (count($recentTickets) < 5) {
                $recentTickets[] = [
                    'id' => 'TICK-' . strtoupper(substr((string) $ticket->getId(), 0, 8)),
                    'priority' => $ticket->getPrioriteScore() >= 75 ? 'Critique' : ($ticket->getPrioriteScore() >= 50 ? 'Haute' : ($ticket->getPrioriteScore() >= 25 ? 'Moyenne' : 'Faible')),
                    'priorityColor' => $ticket->getPrioriteScore() >= 75 ? '#ef4444' : ($ticket->getPrioriteScore() >= 50 ? '#f97316' : ($ticket->getPrioriteScore() >= 25 ? '#3b82f6' : '#22c55e')),
                    'title' => $ticket->getMotif()?->label() ?? 'Ticket',
                    'tags' => [$ticket->getMotif()?->value ?? 'unknown', $ticket->getStatus()->label()],
                    'description' => $ticket->getDetails(),
                    'location' => $ticket->getStop()?->getLabel() ?? 'Aucun arrêt',
                    'source' => $ticket->getEmail() !== '' ? 'Email' : 'Source inconnue',
                    'date' => $ticket->getSubmittedAt()->format('d/m/Y H:i'),
                    'confidence' => $ticket->getConfianceScore(),
                    'assigned' => 'Assigné',
                ];
            }
        }

        $totalTickets = count($tickets);
        $averageConfidence = $totalTickets > 0 ? (int) round($confidenceTotal / $totalTickets) : 0;

        $kpis = [
            ['label' => 'Total Tickets', 'value' => $totalTickets, 'color' => '#3b82f6', 'icon' => 'pulse'],
            ['label' => 'En attente validation', 'value' => $statusCounts['en_attente_validation'], 'color' => '#eab308', 'icon' => 'clock'],
            ['label' => 'En cours', 'value' => $statusCounts['en_cours'], 'color' => '#a855f7', 'icon' => 'trend'],
            ['label' => 'Resolus', 'value' => $statusCounts['resolu'], 'color' => '#22c55e', 'icon' => 'check'],
            ['label' => 'Tickets critiques', 'value' => $criticalTickets, 'color' => '#ef4444', 'icon' => 'alert'],
            ['label' => 'Indice confiance moy.', 'value' => $averageConfidence . '%', 'color' => '#6366f1', 'icon' => 'shield'],
        ];

        $categoryCounts = [];
        $stopStats = [];

        foreach ($tickets as $ticket) {
            $motif = $ticket->getMotif();
            if ($motif !== null) {
                $label = $motif->label();
                $categoryCounts[$label] = ($categoryCounts[$label] ?? 0) + 1;
            }

            $stopLabel = $ticket->getStop()?->getLabel() ?? 'Non renseigné';
            if (!isset($stopStats[$stopLabel])) {
                $stopStats[$stopLabel] = [
                    'incidents' => 0,
                    'slots' => ['matin' => 0, 'midi' => 0, 'soir' => 0, 'nuit' => 0],
                ];
            }

            ++$stopStats[$stopLabel]['incidents'];

            $hour = (int) $ticket->getSubmittedAt()->format('H');
            $slot = match (true) {
                $hour >= 6 && $hour < 12 => 'matin',
                $hour >= 12 && $hour < 17 => 'midi',
                $hour >= 17 && $hour < 22 => 'soir',
                default => 'nuit',
            };
            ++$stopStats[$stopLabel]['slots'][$slot];
        }

        arsort($categoryCounts);
        $categoryBars = [];
        foreach (array_slice($categoryCounts, 0, 8, true) as $label => $count) {
            $categoryBars[] = ['label' => $label, 'value' => $count];
        }

        if ($categoryBars === []) {
            $categoryBars[] = ['label' => 'Aucune donnée', 'value' => 1];
        }

        $maxCategoryValue = max(1, ...array_map(
            static fn (array $item): int => (int) $item['value'],
            $categoryBars,
        ));

        $statusMeta = [
            'en_attente_validation' => ['label' => 'En attente de validation', 'color' => '#3b82f6'],
            'valide' => ['label' => 'Valide', 'color' => '#8b5cf6'],
            'en_cours' => ['label' => 'En cours', 'color' => '#ec4899'],
            'sans_suite' => ['label' => 'Classe sans suite', 'color' => '#10b981'],
            'escalade_juridique' => ['label' => 'Juridique', 'color' => '#f59e0b'],
            'resolu' => ['label' => 'Resolu', 'color' => '#ef4444'],
        ];

        $statusDistribution = [];
        foreach ($statusMeta as $statusValue => $meta) {
            $count = $statusCounts[$statusValue];
            $statusDistribution[] = [
                'label' => $meta['label'],
                'count' => $count,
                'value' => $totalTickets > 0 ? round(($count / $totalTickets) * 100) : 0,
                'color' => $meta['color'],
            ];
        }

        $pieSegments = [];
        $accumulated = 0;
        foreach ($statusDistribution as $index => $item) {
            $start = $accumulated;
            $accumulated += (float) $item['value'];
            $end = $index === array_key_last($statusDistribution) ? 100 : $accumulated;
            $pieSegments[] = sprintf('%s %s%% %s%%', $item['color'], $start, $end);
        }

        $pieGradient = 'conic-gradient(' . implode(', ', $pieSegments) . ')';

        uasort($stopStats, static fn (array $a, array $b): int => $b['incidents'] <=> $a['incidents']);

        $slotLabels = [
            'matin' => 'Créneau dominant: matin',
            'midi' => 'Créneau dominant: midi',
            'soir' => 'Créneau dominant: soir',
            'nuit' => 'Créneau dominant: nuit',
        ];

        $hotspots = [];
        foreach (array_slice($stopStats, 0, 4, true) as $place => $stats) {
            $dominantSlot = array_key_first($stats['slots']);
            foreach ($stats['slots'] as $slot => $value) {
                if ($value > $stats['slots'][$dominantSlot]) {
                    $dominantSlot = $slot;
                }
            }

            $incidents = $stats['incidents'];
            $level = match (true) {
                $incidents >= 6 => 'Haute',
                $incidents >= 3 => 'Moyenne',
                default => 'Faible',
            };

            $hotspots[] = [
                'place' => $place,
                'window' => $slotLabels[$dominantSlot],
                'incidents' => $incidents,
                'level' => $level,
            ];
        }

        return $this->render('moderation/dashboard.html.twig', [
            'kpis' => $kpis,
            'categoryBars' => $categoryBars,
            'maxCategoryValue' => $maxCategoryValue,
            'statusDistribution' => $statusDistribution,
            'pieGradient' => $pieGradient,
            'recentTickets' => $recentTickets,
            'hotspots' => $hotspots,
        ]);
    }

    #[Route('/settings', name: 'app_moderation_settings', methods: ['GET'])]
    public function settings(): Response
    {
        return $this->render('moderation/settings.html.twig', [
            'notificationRules' => [
                ['label' => 'Tickets critiques', 'enabled' => true],
                ['label' => 'Nouveaux tickets IA', 'enabled' => true],
                ['label' => 'Tickets assignes', 'enabled' => true],
                ['label' => 'Points chauds detectes', 'enabled' => false],
            ],
            'securityRules' => [
                ['label' => 'Anonymisation automatique', 'enabled' => true],
                ['label' => 'Logs d\'audit', 'enabled' => true],
                ['label' => 'Validation humaine obligatoire', 'enabled' => true, 'disabled' => true],
            ],
            'sources' => [
                ['name' => 'Cameras bus', 'status' => 'Actif'],
                ['name' => 'QR Codes', 'status' => 'Actif'],
                ['name' => 'Reseaux sociaux (Scraping)', 'status' => 'Test'],
            ],
            'automationRules' => [
                ['label' => 'Creation auto de tickets', 'enabled' => true],
                ['label' => 'Resumes IA', 'enabled' => true],
                ['label' => 'Fusion automatique des doublons', 'enabled' => true],
                ['label' => 'Detection points chauds', 'enabled' => true],
                ['label' => 'Indice de confiance', 'enabled' => true],
            ],
            'confidence' => [
                'high' => 80,
                'medium' => 60,
                'penalty' => 10,
            ],
            'systemInfo' => [
                'version' => 'v1.0.0 (Prototype)',
                'environment' => 'Developpement',
                'processed' => 10,
                'uptime' => '99.9%',
            ],
        ]);
    }

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
