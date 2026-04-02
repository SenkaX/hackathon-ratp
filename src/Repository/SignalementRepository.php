<?php

namespace App\Repository;

use App\Entity\MotifGravite;
use App\Entity\Signalement;
use App\Enum\SignalementMotif;
use App\Enum\SignalementStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Signalement>
 */
class SignalementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Signalement::class);
    }

    /**
     * @param array{period?: string, motif?: ?string} $filters
     *
     * @return list<array{
     *     stop_id: string,
     *     label: string,
     *     latitude: ?float,
     *     longitude: ?float,
     *     score: int,
     *     count: int,
     *     signalements: list<array{
     *         id: string,
     *         motif: string,
     *         gravite: int,
     *         status: string,
     *         submitted_at: string
     *     }>
     * }>
     */
    public function getHotspotData(array $filters = []): array
    {
        $period = in_array($filters['period'] ?? 'all', ['7', '30', 'all'], true)
            ? (string) ($filters['period'] ?? 'all')
            : 'all';

        $motifFilter = SignalementMotif::tryFrom((string) ($filters['motif'] ?? ''));

        $since = match ($period) {
            '7' => new \DateTimeImmutable('-7 days'),
            '30' => new \DateTimeImmutable('-30 days'),
            default => null,
        };

        $aggregateQb = $this->createQueryBuilder('signalement')
            ->select('stop.id AS stop_id')
            ->addSelect('stop.label AS label')
            ->addSelect('stop.latitude AS latitude')
            ->addSelect('stop.longitude AS longitude')
            ->addSelect('COALESCE(SUM(COALESCE(motifGravite.gravite, 1)), 0) AS score')
            ->addSelect('COUNT(signalement.id) AS count')
            ->innerJoin('signalement.stop', 'stop')
            ->leftJoin(MotifGravite::class, 'motifGravite', Join::WITH, 'motifGravite.motif = signalement.motif')
            ->groupBy('stop.id, stop.label, stop.latitude, stop.longitude')
            ->orderBy('score', 'DESC')
            ->addOrderBy('count', 'DESC');

        if ($since !== null) {
            $aggregateQb
                ->andWhere('signalement.submittedAt >= :since')
                ->setParameter('since', $since);
        }

        if ($motifFilter !== null) {
            $aggregateQb
                ->andWhere('signalement.motif = :motif')
                ->setParameter('motif', $motifFilter);
        }

        /** @var list<array{stop_id:string,label:string,latitude:mixed,longitude:mixed,score:numeric-string,count:numeric-string}> $aggregateRows */
        $aggregateRows = $aggregateQb->getQuery()->getArrayResult();

        if ($aggregateRows === []) {
            return [];
        }

        $detailsQb = $this->createQueryBuilder('signalement')
            ->select('signalement.id AS id')
            ->addSelect('stop.id AS stop_id')
            ->addSelect('signalement.motif AS motif')
            ->addSelect('COALESCE(motifGravite.gravite, 1) AS gravite')
            ->addSelect('signalement.status AS status')
            ->addSelect('signalement.submittedAt AS submittedAt')
            ->innerJoin('signalement.stop', 'stop')
            ->leftJoin(MotifGravite::class, 'motifGravite', Join::WITH, 'motifGravite.motif = signalement.motif')
            ->orderBy('signalement.submittedAt', 'DESC');

        if ($since !== null) {
            $detailsQb
                ->andWhere('signalement.submittedAt >= :since')
                ->setParameter('since', $since);
        }

        if ($motifFilter !== null) {
            $detailsQb
                ->andWhere('signalement.motif = :motif')
                ->setParameter('motif', $motifFilter);
        }

        /** @var list<array{id:string,stop_id:string,motif:mixed,gravite:numeric-string,status:mixed,submittedAt:\DateTimeInterface|string}> $detailsRows */
        $detailsRows = $detailsQb->getQuery()->getArrayResult();

        /** @var array<string, array{
     *     stop_id: string,
     *     label: string,
     *     latitude: ?float,
     *     longitude: ?float,
     *     score: int,
     *     count: int,
     *     signalements: list<array{id:string,motif:string,gravite:int,status:string,submitted_at:string}>
     * }> $byStop */
        $byStop = [];

        foreach ($aggregateRows as $row) {
            $byStop[$row['stop_id']] = [
                'stop_id' => $row['stop_id'],
                'label' => $row['label'],
                'latitude' => $row['latitude'] !== null ? (float) $row['latitude'] : null,
                'longitude' => $row['longitude'] !== null ? (float) $row['longitude'] : null,
                'score' => (int) $row['score'],
                'count' => (int) $row['count'],
                'signalements' => [],
            ];
        }

        foreach ($detailsRows as $row) {
            $stopId = $row['stop_id'];
            if (!isset($byStop[$stopId])) {
                continue;
            }

            $motif = $row['motif'] instanceof SignalementMotif
                ? $row['motif']->value
                : (string) $row['motif'];

            $status = $row['status'] instanceof SignalementStatus
                ? $row['status']->value
                : (string) $row['status'];

            $submittedAt = $row['submittedAt'] instanceof \DateTimeInterface
                ? $row['submittedAt']->format(\DateTimeInterface::ATOM)
                : (string) $row['submittedAt'];

            $byStop[$stopId]['signalements'][] = [
                'id' => $row['id'],
                'motif' => $motif,
                'gravite' => (int) $row['gravite'],
                'status' => $status,
                'submitted_at' => $submittedAt,
            ];
        }

        return array_values($byStop);
    }
}
