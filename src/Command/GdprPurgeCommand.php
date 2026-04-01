<?php

namespace App\Command;

use App\Repository\SignalementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(name: 'app:gdpr:purge', description: 'Anonymise les signalements anciens selon la retention RGPD.')]
final class GdprPurgeCommand extends Command
{
    public function __construct(
        private readonly SignalementRepository $signalementRepository,
        private readonly EntityManagerInterface $entityManager,
        #[Autowire('%gdpr_retention_days%')] private readonly int $retentionDays,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche le nombre de lignes ciblees sans ecrire en base.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $cutoff = (new \DateTimeImmutable())->modify(sprintf('-%d days', max(1, $this->retentionDays)));
        $dryRun = (bool) $input->getOption('dry-run');

        $targets = $this->signalementRepository->createQueryBuilder('s')
            ->andWhere('s.submittedAt < :cutoff')
            ->andWhere('s.anonymizedAt IS NULL')
            ->setParameter('cutoff', $cutoff)
            ->getQuery()
            ->getResult();

        $count = \count($targets);
        if ($count === 0) {
            $io->success('Aucun signalement a anonymiser.');

            return Command::SUCCESS;
        }

        if ($dryRun) {
            $io->warning(sprintf('Dry-run: %d signalement(s) seraient anonymises (cutoff=%s).', $count, $cutoff->format(\DateTimeInterface::ATOM)));

            return Command::SUCCESS;
        }

        $now = new \DateTimeImmutable();
        foreach ($targets as $signalement) {
            $signalement->setEmail(sprintf('anonymized+%s@example.invalid', $signalement->getId()));
            $signalement->setDetails('[DONNEES ANONYMISEES]');
            $signalement->setTokenHash(null);
            $signalement->setTokenExpiresAt(null);
            $signalement->setAnonymizedAt($now);
        }

        $this->entityManager->flush();

        $io->success(sprintf('%d signalement(s) anonymises (cutoff=%s).', $count, $cutoff->format(\DateTimeInterface::ATOM)));

        return Command::SUCCESS;
    }
}
