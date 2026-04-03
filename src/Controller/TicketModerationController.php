<?php

namespace App\Controller;

use App\Entity\BusStop;
use App\Entity\User;
use App\Enum\SignalementMotif;
use App\Enum\SignalementStatus;
use App\Repository\BusStopRepository;
use App\Repository\MotifGraviteRepository;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
    public function users(
        UserRepository $userRepository,
        SignalementRepository $signalementRepository,
    ): Response
    {
        $users = $this->buildUsersViewModels($userRepository, $signalementRepository);

        return $this->render('moderation/users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/users/new', name: 'app_moderation_user_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function newUser(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository,
    ): Response {
        $roleOptions = $this->userRoleOptions();
        $formData = [
            'email' => '',
            'password' => '',
            'password_confirm' => '',
            'role' => User::ROLE_MANAGER,
        ];
        $errors = [];

        if ($request->isMethod('POST')) {
            $formData['email'] = trim((string) $request->request->get('email', ''));
            $formData['password'] = (string) $request->request->get('password', '');
            $formData['password_confirm'] = (string) $request->request->get('password_confirm', '');
            $formData['role'] = (string) $request->request->get('role', User::ROLE_MANAGER);

            if ($formData['email'] === '' || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Adresse email invalide.';
            }

            if (strlen($formData['password']) < 8) {
                $errors[] = 'Le mot de passe doit contenir au moins 8 caracteres.';
            }

            if ($formData['password'] !== $formData['password_confirm']) {
                $errors[] = 'Les mots de passe ne correspondent pas.';
            }

            if (!array_key_exists($formData['role'], $roleOptions)) {
                $errors[] = 'Rôle invalide.';
            }

            if ($userRepository->findOneBy(['email' => $formData['email']]) !== null) {
                $errors[] = 'Un utilisateur avec cette adresse email existe deja.';
            }

            if ($errors === []) {
                $user = (new User())
                    ->setEmail($formData['email'])
                    ->setRoles([$formData['role']]);
                $user->setPassword($passwordHasher->hashPassword($user, $formData['password']));

                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', 'Utilisateur cree avec succes.');

                return $this->redirectToRoute('app_moderation_users');
            }
        }

        return $this->render('moderation/user_new.html.twig', [
            'formData' => $formData,
            'errors' => $errors,
            'roleOptions' => $roleOptions,
        ]);
    }

    #[Route('/dashboard', name: 'app_moderation_dashboard', methods: ['GET'])]
    public function dashboard(
        Request $request,
        SignalementRepository $signalementRepository,
    ): Response
    {
        $periodMap = [
            '30d' => ['days' => 30, 'label' => '30 derniers jours'],
            '3m' => ['days' => 90, 'label' => '3 derniers mois'],
            '6m' => ['days' => 180, 'label' => '6 derniers mois'],
            '1y' => ['days' => 365, 'label' => '12 derniers mois'],
        ];

        $selectedPeriod = (string) $request->query->get('period', '30d');
        if (!isset($periodMap[$selectedPeriod])) {
            $selectedPeriod = '30d';
        }

        $periodDays = $periodMap[$selectedPeriod]['days'];
        $periodLabel = $periodMap[$selectedPeriod]['label'];

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
        $recentTickets = [];

        $today = new \DateTimeImmutable('today');
        $evolutionStart = $today->modify(sprintf('-%d days', $periodDays - 1));
        $evolutionDailyCounts = [];
        for ($i = 0; $i < $periodDays; ++$i) {
            $day = $evolutionStart->modify(sprintf('+%d days', $i));
            $evolutionDailyCounts[$day->format('Y-m-d')] = 0;
        }

        foreach ($tickets as $ticket) {
            $statusValue = $ticket->getStatus()->value;
            if (isset($statusCounts[$statusValue])) {
                ++$statusCounts[$statusValue];
            }

            if ($ticket->getPrioriteScore() >= 75) {
                ++$criticalTickets;
            }

            $submittedAt = $ticket->getSubmittedAt();
            if ($submittedAt >= $evolutionStart) {
                $key = $submittedAt->format('Y-m-d');
                if (array_key_exists($key, $evolutionDailyCounts)) {
                    ++$evolutionDailyCounts[$key];
                }
            }

            if (count($recentTickets) < 5) {
                $recentTickets[] = [
                    'id' => 'TICK-' . strtoupper(substr((string) $ticket->getId(), 0, 8)),
                    'priority' => $ticket->getPrioriteScore() >= 75 ? 'Critique' : ($ticket->getPrioriteScore() >= 50 ? 'Haute' : ($ticket->getPrioriteScore() >= 25 ? 'Moyenne' : 'Faible')),
                    'priorityColor' => $ticket->getPrioriteScore() >= 75 ? '#ef4444' : ($ticket->getPrioriteScore() >= 50 ? '#f97316' : ($ticket->getPrioriteScore() >= 25 ? '#00c4b3' : '#22c55e')),
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

        $evolutionLabels = [];
        $evolutionValues = [];
        foreach ($evolutionDailyCounts as $dateKey => $count) {
            $evolutionLabels[] = (new \DateTimeImmutable($dateKey))->format('d/m');
            $evolutionValues[] = $count;
        }

        $displayLabels = $evolutionLabels;
        $displayValues = $evolutionValues;
        $firstNonZeroIndex = null;
        foreach ($displayValues as $index => $value) {
            if ($value > 0) {
                $firstNonZeroIndex = $index;
                break;
            }
        }

        if ($selectedPeriod === '30d' && $firstNonZeroIndex !== null && $firstNonZeroIndex > 0) {
            $displayLabels = array_slice($displayLabels, $firstNonZeroIndex);
            $displayValues = array_slice($displayValues, $firstNonZeroIndex);
        }

        $evolutionAxisLabels = [];
        $labelSlots = 10;
        $labelCount = count($displayLabels);
        $step = max(1, (int) floor($labelCount / $labelSlots));
        for ($i = 0; $i < $labelCount; $i += $step) {
            $evolutionAxisLabels[] = $displayLabels[$i];
            if (count($evolutionAxisLabels) >= $labelSlots) {
                break;
            }
        }
        if ($evolutionAxisLabels !== [] && end($evolutionAxisLabels) !== end($displayLabels)) {
            $evolutionAxisLabels[count($evolutionAxisLabels) - 1] = end($displayLabels);
        }

        $rawEvolutionMax = max(0, ...$displayValues);
        $evolutionMax = $rawEvolutionMax > 0
            ? (int) ceil($rawEvolutionMax * 1.25)
            : 1;
        $evolutionPoints = [];
        $evolutionCount = count($displayValues);
        foreach ($displayValues as $index => $value) {
            $x = $evolutionCount > 1 ? round(($index / ($evolutionCount - 1)) * 1000, 2) : 0;
            $y = round(260 - (($value / $evolutionMax) * 260), 2);
            $evolutionPoints[] = sprintf('%s,%s', $x, $y);
        }

        $trendWindow = max(7, (int) floor($periodDays / 4));
        $recentWindow = array_slice($evolutionValues, -$trendWindow);
        $previousWindow = array_slice($evolutionValues, -($trendWindow * 2), $trendWindow);
        $recent7Total = array_sum($recentWindow);
        $previous7Total = array_sum($previousWindow);
        $evolutionTrend = $previous7Total > 0
            ? (int) round((($recent7Total - $previous7Total) / $previous7Total) * 100)
            : ($recent7Total > 0 ? 100 : 0);

        $kpis = [
            ['label' => 'Total Tickets', 'value' => $totalTickets, 'color' => '#00c4b3', 'icon' => 'pulse'],
            ['label' => 'En attente validation', 'value' => $statusCounts['en_attente_validation'], 'color' => '#eab308', 'icon' => 'clock'],
            ['label' => 'En cours', 'value' => $statusCounts['en_cours'], 'color' => '#a855f7', 'icon' => 'trend'],
            ['label' => 'Resolus', 'value' => $statusCounts['resolu'], 'color' => '#22c55e', 'icon' => 'check'],
            ['label' => 'Tickets critiques', 'value' => $criticalTickets, 'color' => '#ef4444', 'icon' => 'alert'],
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
            'en_attente_validation' => ['label' => 'En attente de validation', 'color' => '#00c4b3'],
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
            'evolutionLabels' => $evolutionLabels,
            'evolutionDisplayLabels' => $displayLabels,
            'evolutionAxisLabels' => $evolutionAxisLabels,
            'evolutionAxisLabelCount' => max(1, count($evolutionAxisLabels)),
            'evolutionValues' => $displayValues,
            'evolutionMax' => $evolutionMax,
            'evolutionPoints' => implode(' ', $evolutionPoints),
            'evolutionTrend' => $evolutionTrend,
            'selectedPeriod' => $selectedPeriod,
            'periodLabel' => $periodLabel,
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

    #[Route('/map', name: 'app_moderation_tickets_map', methods: ['GET'])]
    #[IsGranted('ROLE_MANAGER')]
    public function map(Request $request): Response
    {
        $selectedPeriod = in_array((string) $request->query->get('period', 'all'), ['7', '30', 'all'], true)
            ? (string) $request->query->get('period', 'all')
            : 'all';
        $selectedMotif = SignalementMotif::tryFrom((string) $request->query->get('motif', ''));

        return $this->render('moderation/map.html.twig', [
            'motifs' => SignalementMotif::cases(),
            'motifLabels' => array_reduce(
                SignalementMotif::cases(),
                static function (array $labels, SignalementMotif $motif): array {
                    $labels[$motif->value] = $motif->label();

                    return $labels;
                },
                []
            ),
            'filters' => [
                'period' => $selectedPeriod,
                'motif' => $selectedMotif?->value,
            ],
        ]);
    }

    #[Route('/map/data', name: 'app_moderation_tickets_map_data', methods: ['GET'])]
    #[IsGranted('ROLE_MANAGER')]
    public function mapData(
        Request $request,
        BusStopRepository $busStopRepository,
        SignalementRepository $signalementRepository,
    ): Response {
        $period = in_array((string) $request->query->get('period', 'all'), ['7', '30', 'all'], true)
            ? (string) $request->query->get('period', 'all')
            : 'all';
        $motifFilter = SignalementMotif::tryFrom((string) $request->query->get('motif', ''));

        $hotspotRows = $signalementRepository->getHotspotData([
            'period' => $period,
            'motif' => $motifFilter?->value,
        ]);

        $byStopId = [];
        foreach ($hotspotRows as $row) {
            $byStopId[$row['stop_id']] = $row;
        }

        $payload = [];
        foreach ($busStopRepository->findBy([], ['label' => 'ASC']) as $stop) {
            $data = $byStopId[$stop->getId()] ?? null;

            $payload[] = [
                'stop_id' => $stop->getId(),
                'label' => $stop->getLabel(),
                'latitude' => $stop->getLatitude(),
                'longitude' => $stop->getLongitude(),
                'score' => $data['score'] ?? 0,
                'count' => $data['count'] ?? 0,
                'signalements' => $data['signalements'] ?? [],
            ];
        }

        return $this->json($payload);
    }

    #[Route('/qrcodes', name: 'app_moderation_qrcodes', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function qrcodes(BusStopRepository $busStopRepository): Response
    {
        $writer = new PngWriter();
        $appUrl = rtrim((string) $this->getParameter('app.url'), '/');

        $items = [];
        foreach ($busStopRepository->findBy([], ['label' => 'ASC']) as $stop) {
            $targetUrl = sprintf('%s/signalement?stop_id=%s', $appUrl, $stop->getId());
            $result = $writer->write(new QrCode(data: $targetUrl, size: 300, margin: 10));

            $items[] = [
                'stop' => $stop,
                'target_url' => $targetUrl,
                'image_base64' => base64_encode($result->getString()),
            ];
        }

        return $this->render('moderation/qrcodes.html.twig', [
            'items' => $items,
        ]);
    }

    #[Route('/qrcodes/{id}/download', name: 'app_moderation_qrcode_download', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function qrcodeDownload(BusStop $stop): Response
    {
        $targetUrl = sprintf('%s/signalement?stop_id=%s', rtrim((string) $this->getParameter('app.url'), '/'), $stop->getId());
        $result = (new PngWriter())->write(new QrCode(data: $targetUrl, size: 1000, margin: 20));

        $response = new Response($result->getString());
        $response->headers->set('Content-Type', 'image/png');
        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, sprintf('qrcode-%s.png', $stop->getId())));

        return $response;
    }

    #[Route('', name: 'app_moderation_tickets', methods: ['GET'])]
    public function index(
        Request $request,
        SignalementRepository $signalementRepository,
        BusStopRepository $busStopRepository,
        UserRepository $userRepository,
    ): Response
    {
        return $this->renderTicketsBoard(
            $request,
            $signalementRepository,
            $busStopRepository,
            $userRepository,
            null,
            'kanban',
            'Tableau de bord Moderation',
            'Vue Kanban - Gestion centralisee des incidents',
            'app_moderation_tickets',
            true,
        );
    }

    private function renderTicketsBoard(
        Request $request,
        SignalementRepository $signalementRepository,
        BusStopRepository $busStopRepository,
        UserRepository $userRepository,
        ?string $assignedRole,
        string $activeTab,
        string $pageTitle,
        string $pageSubtitle,
        string $resetRoute,
        bool $defaultToCurrentUser,
    ): Response
    {
        $statusFilter = SignalementStatus::tryFrom((string) $request->query->get('status', ''));
        $motifFilter = SignalementMotif::tryFrom((string) $request->query->get('motif', ''));
        $stopFilter = trim((string) $request->query->get('stop', ''));
        $hasUserFilter = $request->query->has('user');
        $userFilter = trim((string) $request->query->get('user', ''));
        $currentUser = $this->getUser();
        $defaultUserFilter = $defaultToCurrentUser && !$hasUserFilter && $currentUser instanceof User ? (string) $currentUser->getId() : '';
        $resolvedUserFilter = $userFilter !== '' ? $userFilter : $defaultUserFilter;

        $qb = $signalementRepository->createQueryBuilder('ticket')
            ->leftJoin('ticket.stop', 'stop')
            ->leftJoin('ticket.reviewedBy', 'reviewedBy')
            ->addSelect('reviewedBy')
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

        if ($resolvedUserFilter !== '') {
            $qb->andWhere('reviewedBy.id = :reviewedById')->setParameter('reviewedById', (int) $resolvedUserFilter);
        }

        if ($assignedRole !== null) {
            $qb->andWhere('ticket.assignedRole = :assignedRole')->setParameter('assignedRole', $assignedRole);
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
            'users' => $userRepository->findBy([], ['email' => 'ASC']),
            'activeTab' => $activeTab,
            'pageTitle' => $pageTitle,
            'pageSubtitle' => $pageSubtitle,
            'resetRoute' => $resetRoute,
            'assignedRole' => $assignedRole,
            'filters' => [
                'status' => $statusFilter?->value,
                'motif' => $motifFilter?->value,
                'stop' => $stopFilter,
                'user' => $resolvedUserFilter,
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

            return $this->redirectToRoute($this->resolveRedirectRoute($request));
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

        return $this->redirectToRoute($this->resolveRedirectRoute($request));
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

    #[Route('/{id}/ai-review', name: 'app_moderation_ticket_ai_review_update', methods: ['POST'], requirements: ['id' => '[0-9a-fA-F\-]{36}'])]
    public function updateAiReview(
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
        if (!$this->isCsrfTokenValid('ticket-ai-review-'.$ticket->getId(), $token)) {
            throw $this->createAccessDeniedException('CSRF token invalid.');
        }

        $suggestion = trim((string) $request->request->get('suggestion', ''));
        $humanResponse = trim((string) $request->request->get('human_response', ''));
        $decision = (string) $request->request->get('suggestion_decision', 'pending');

        $ticket->setSuggestion($suggestion !== '' ? $suggestion : null);
        $ticket->setSuggestionHumanResponse($humanResponse !== '' ? $humanResponse : null);

        $suggestionValidated = match ($decision) {
            'approved' => true,
            'rejected' => false,
            default => null,
        };
        $ticket->setSuggestionValidated($suggestionValidated);

        $ticket->setReviewedAt(new \DateTimeImmutable());
        $user = $this->getUser();
        if ($user instanceof User) {
            $ticket->setReviewedBy($user);
        }

        $entityManager->flush();

        $this->addFlash('success', 'Analyse IA et reponse humaine mises a jour.');

        return $this->redirectToRoute($this->resolveRedirectRoute($request));
    }

    private function computePriorityScore(int $gravite, int $confianceScore): int
    {
        $score = ($gravite * 15) + intdiv($confianceScore, 4);

        return max(0, min(100, $score));
    }

    private function resolveAssignedRoleForCurrentUser(): string
    {
        if ($this->isGranted(User::ROLE_RH)) {
            return User::ROLE_RH;
        }

        return User::ROLE_MANAGER;
    }

    private function resolveRedirectRoute(Request $request): string
    {
        $route = (string) $request->request->get('_redirect_route', 'app_moderation_tickets');

        return in_array($route, ['app_moderation_tickets'], true)
            ? $route
            : 'app_moderation_tickets';
    }

    /**
     * @return array<string, string>
     */
    private function userRoleOptions(): array
    {
        return [
            User::ROLE_ADMIN => 'Admin',
            User::ROLE_RH => 'RH',
            User::ROLE_MANAGER => 'Manager',
        ];
    }

    /**
     * @return array<int, array{name: string, role: string, roleColor: string, email: string, description: string, assigned: int, created: int, pending: int, inProgress: int, systemNote: null}>
     */
    private function buildUsersViewModels(UserRepository $userRepository, SignalementRepository $signalementRepository): array
    {
        $ticketCountsByRole = [
            User::ROLE_ADMIN => ['assigned' => 0, 'pending' => 0, 'inProgress' => 0],
            User::ROLE_RH => ['assigned' => 0, 'pending' => 0, 'inProgress' => 0],
            User::ROLE_MANAGER => ['assigned' => 0, 'pending' => 0, 'inProgress' => 0],
            User::ROLE_MODERATOR => ['assigned' => 0, 'pending' => 0, 'inProgress' => 0],
        ];

        foreach ($signalementRepository->findAll() as $ticket) {
            $assignedRole = $ticket->getAssignedRole();
            if ($assignedRole === null || !isset($ticketCountsByRole[$assignedRole])) {
                continue;
            }

            ++$ticketCountsByRole[$assignedRole]['assigned'];

            if ($ticket->getStatus() === SignalementStatus::EnAttenteValidation) {
                ++$ticketCountsByRole[$assignedRole]['pending'];
            }

            if ($ticket->getStatus() === SignalementStatus::EnCours) {
                ++$ticketCountsByRole[$assignedRole]['inProgress'];
            }
        }

        $roleCountByValue = [];
        foreach ($userRepository->findBy([], ['email' => 'ASC']) as $user) {
            $primaryRole = $this->primaryRoleForUser($user->getRoles());
            $roleCountByValue[$primaryRole] = ($roleCountByValue[$primaryRole] ?? 0) + 1;
        }

        $result = [];
        foreach ($userRepository->findBy([], ['email' => 'ASC']) as $user) {
            $primaryRole = $this->primaryRoleForUser($user->getRoles());
            $result[] = [
                'name' => $this->displayNameFromEmail((string) $user->getEmail()),
                'role' => $this->userRoleOptions()[$primaryRole] ?? 'Utilisateur',
                'roleColor' => $this->roleColorFor($primaryRole),
                'email' => (string) $user->getEmail(),
                'description' => $this->roleDescriptionFor($primaryRole),
                'assigned' => $ticketCountsByRole[$primaryRole]['assigned'] ?? 0,
                'created' => $roleCountByValue[$primaryRole] ?? 0,
                'pending' => $ticketCountsByRole[$primaryRole]['pending'] ?? 0,
                'inProgress' => $ticketCountsByRole[$primaryRole]['inProgress'] ?? 0,
                'systemNote' => null,
            ];
        }

        return $result;
    }

    /**
     * @param list<string> $roles
     */
    private function primaryRoleForUser(array $roles): string
    {
        foreach ([User::ROLE_ADMIN, User::ROLE_RH, User::ROLE_MANAGER, User::ROLE_MODERATOR] as $role) {
            if (in_array($role, $roles, true)) {
                return $role;
            }
        }

        return User::ROLE_MODERATOR;
    }

    private function displayNameFromEmail(string $email): string
    {
        if (strtolower($email) === 'admin@ratp.local') {
            return 'Marie Dupont';
        }

        $localPart = strstr($email, '@', true) ?: $email;
        $parts = preg_split('/[._-]+/', $localPart) ?: [$localPart];
        $parts = array_filter(array_map(static fn (string $part): string => trim($part), $parts));

        if ($parts === []) {
            return $email;
        }

        return implode(' ', array_map(static fn (string $part): string => ucfirst($part), $parts));
    }

    private function roleColorFor(string $role): string
    {
        return match ($role) {
            User::ROLE_ADMIN => '#dc2626',
            User::ROLE_RH => '#4f46e5',
            User::ROLE_MANAGER => '#0f766e',
            User::ROLE_MODERATOR => '#7c3aed',
            default => '#6366f1',
        };
    }

    private function roleDescriptionFor(string $role): string
    {
        return match ($role) {
            User::ROLE_ADMIN => 'Acces complet - Validation et decisions strategiques',
            User::ROLE_RH => 'Gestion des incidents RH et comportements',
            User::ROLE_MANAGER => 'Supervision des operations et coordination avec les equipes terrain',
            User::ROLE_MODERATOR => 'Tri initial, qualification et suivi des signalements',
            default => 'Utilisateur de la plateforme',
        };
    }
}
