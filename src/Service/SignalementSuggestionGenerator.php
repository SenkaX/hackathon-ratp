<?php

namespace App\Service;

use App\Entity\BusStop;
use App\Entity\Signalement;
use App\Enum\SignalementMotif;
use App\Enum\SignalementStatus;
use App\Repository\MotifGraviteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class SignalementSuggestionGenerator
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private EntityManagerInterface $entityManager,
        private MotifGraviteRepository $motifGraviteRepository,
        private LoggerInterface $logger,
        private string $openaiApiKey,
        private string $openaiModel,
    ) {
    }

    public function generate(Signalement $signalement): string
    {
        $context = $this->buildContext($signalement);

        if ($this->openaiApiKey === '') {
            return $this->buildFallbackSuggestion($context);
        }

        try {
            $suggestion = $this->callOpenAi($context);
            if ($suggestion !== null) {
                return $suggestion;
            }
        } catch (\Throwable $exception) {
            $this->logger->error('OpenAI suggestion generation failed.', [
                'signalement_id' => $signalement->getId(),
                'exception' => $exception,
            ]);
        }

        return $this->buildFallbackSuggestion($context);
    }

    /**
     * @return array{
     *     signalement: array<string, mixed>,
     *     stop_history: array<string, mixed>,
     *     roles_context: array<string, string>
     * }
     */
    private function buildContext(Signalement $signalement): array
    {
        $gravite = $signalement->getMotif() !== null
            ? ($this->motifGraviteRepository->find($signalement->getMotif())?->getGravite() ?? 1)
            : 1;

        $stopHistory = $this->buildStopHistory($signalement->getStop(), $signalement->getId());

        return [
            'signalement' => [
                'id' => $signalement->getId(),
                'motif' => $signalement->getMotif()?->value,
                'motif_label' => $signalement->getMotif()?->label(),
                'details' => $signalement->getDetails(),
                'priorite_score' => $signalement->getPrioriteScore(),
                'confiance_score' => $signalement->getConfianceScore(),
                'gravite' => $gravite,
                'stop_id' => $signalement->getStop()?->getId(),
                'stop_label' => $signalement->getStop()?->getLabel(),
                'submitted_at' => $signalement->getSubmittedAt()->format(\DateTimeInterface::ATOM),
            ],
            'stop_history' => $stopHistory,
            'roles_context' => [
                'ROLE_MANAGER' => 'qualification initiale et suivi operationnel terrain',
                'ROLE_RH' => 'mesures disciplinaires et suivi conducteur',
                'ROLE_ADMIN' => 'arbitrage, validation finale, escalade transverse et pilotage QR codes',
                'CHEF_EQUIPE' => 'actions operationnelles terrain et accompagnement',
                'JURIDIQUE' => 'dossiers litigieux et faits graves',
            ],
        ];
    }

    /**
     * @return array{total:int,recent_30d:int,high_priority:int,pending:int,risk_level:string}
     */
    private function buildStopHistory(?BusStop $stop, string $signalementId): array
    {
        if ($stop === null) {
            return [
                'total' => 0,
                'recent_30d' => 0,
                'high_priority' => 0,
                'pending' => 0,
                'risk_level' => 'inconnu',
            ];
        }

        $since30 = new \DateTimeImmutable('-30 days');

        $baseQb = $this->entityManager->createQueryBuilder()
            ->from(Signalement::class, 's')
            ->where('s.stop = :stop')
            ->andWhere('s.id != :currentId')
            ->setParameter('stop', $stop)
            ->setParameter('currentId', $signalementId);

        $total = (int) (clone $baseQb)
            ->select('COUNT(s.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $recent30 = (int) (clone $baseQb)
            ->select('COUNT(s.id)')
            ->andWhere('s.submittedAt >= :since30')
            ->setParameter('since30', $since30)
            ->getQuery()
            ->getSingleScalarResult();

        $highPriority = (int) (clone $baseQb)
            ->select('COUNT(s.id)')
            ->andWhere('s.prioriteScore >= 60')
            ->getQuery()
            ->getSingleScalarResult();

        $pending = (int) (clone $baseQb)
            ->select('COUNT(s.id)')
            ->andWhere('s.status IN (:statuses)')
            ->setParameter('statuses', [
                SignalementStatus::EnAttenteValidation->value,
                SignalementStatus::EnCours->value,
                SignalementStatus::Valide->value,
            ])
            ->getQuery()
            ->getSingleScalarResult();

        $riskLevel = 'faible';
        if ($recent30 >= 6 || $highPriority >= 4) {
            $riskLevel = 'eleve';
        } elseif ($recent30 >= 3 || $highPriority >= 2) {
            $riskLevel = 'modere';
        }

        return [
            'total' => $total,
            'recent_30d' => $recent30,
            'high_priority' => $highPriority,
            'pending' => $pending,
            'risk_level' => $riskLevel,
        ];
    }

    /**
     * @param array<string, mixed> $context
     */
    private function callOpenAi(array $context): ?string
    {
        $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer '.$this->openaiApiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => $this->openaiModel,
                'temperature' => 0.2,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Tu assistes la moderation d\'un outil de reporting RATP. Reponds UNIQUEMENT en JSON avec les cles: resume, action, destinataire, urgence. Adapte la recommandation selon la gravite et l\'historique de l\'arret. Pour agression_physique, orienter en priorite RH + juridique. Pour non_arret_station, orienter en priorite chef d\'equipe.',
                    ],
                    [
                        'role' => 'user',
                        'content' => json_encode($context, JSON_THROW_ON_ERROR),
                    ],
                ],
            ],
            'timeout' => 20,
        ]);

        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode >= 300) {
            $this->logger->warning('OpenAI returned non-success status for suggestion.', [
                'status_code' => $statusCode,
            ]);

            return null;
        }

        $payload = $response->toArray(false);
        $content = $payload['choices'][0]['message']['content'] ?? null;

        if (!is_string($content) || $content === '') {
            return null;
        }

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        $resume = trim((string) ($decoded['resume'] ?? ''));
        $action = trim((string) ($decoded['action'] ?? ''));
        $destinataire = trim((string) ($decoded['destinataire'] ?? ''));
        $urgence = trim((string) ($decoded['urgence'] ?? ''));

        if ($resume === '' || $action === '') {
            return null;
        }

        return implode("\n", array_filter([
            'Resume: '.$resume,
            'Action recommandee: '.$action,
            $destinataire !== '' ? 'Destinataire: '.$destinataire : null,
            $urgence !== '' ? 'Niveau d\'urgence: '.$urgence : null,
            'Rappel: validation humaine obligatoire avant toute sanction.',
        ]));
    }

    /**
     * @param array<string, mixed> $context
     */
    private function buildFallbackSuggestion(array $context): string
    {
        $motif = SignalementMotif::tryFrom((string) ($context['signalement']['motif'] ?? ''));
        $riskLevel = (string) ($context['stop_history']['risk_level'] ?? 'inconnu');
        $recent30 = (int) ($context['stop_history']['recent_30d'] ?? 0);

        $destinataire = 'Moderation';
        $action = 'Analyser les elements et demander des preuves complementaires si necessaire.';

        if ($motif === SignalementMotif::AgressionPhysique) {
            $destinataire = 'RH + Juridique';
            $action = 'Escalade immediate. Prioriser la securisation des personnes, ouvrir un dossier RH et transmettre les faits au juridique.';
        } elseif ($motif === SignalementMotif::NonArretStation || $motif === SignalementMotif::RefusOuverturePorte) {
            $destinataire = 'Chef d\'equipe exploitation';
            $action = 'Verifier les traces GPS et planning, puis organiser un point operationnel avec le chef d\'equipe.';
        } elseif ($motif === SignalementMotif::ConduiteDangereuse) {
            $destinataire = 'Chef d\'equipe + RH';
            $action = 'Demander un controle prioritaire et evaluer une mesure conservatoire selon les elements disponibles.';
        }

        if ($riskLevel === 'eleve') {
            $action .= ' L\'historique de l\'arret est critique, traiter en priorite haute et planifier une action preventive locale.';
        } elseif ($riskLevel === 'modere') {
            $action .= ' L\'arret presente des recurrences, suivre les prochains signalements avec une vigilance renforcee.';
        }

        return implode("\n", [
            'Resume: Signalement '.$context['signalement']['motif_label'].' avec gravite estimee '.$context['signalement']['gravite'].'/5.',
            'Contexte arret: '.$recent30.' incident(s) sur 30 jours, niveau de risque '.$riskLevel.'.',
            'Action recommandee: '.$action,
            'Destinataire: '.$destinataire,
            'Rappel: suggestion generee automatiquement, validation humaine obligatoire avant decision.',
        ]);
    }
}
