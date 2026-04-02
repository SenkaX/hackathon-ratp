<?php

namespace App\MessageHandler;

use App\Message\GenerateSignalementSuggestionMessage;
use App\Repository\SignalementRepository;
use App\Service\SignalementSuggestionGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class GenerateSignalementSuggestionMessageHandler
{
    public function __construct(
        private SignalementRepository $signalementRepository,
        private SignalementSuggestionGenerator $suggestionGenerator,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(GenerateSignalementSuggestionMessage $message): void
    {
        $signalement = $this->signalementRepository->find($message->signalementId);

        if ($signalement === null) {
            $this->logger->warning('Suggestion generation skipped: signalement not found.', [
                'signalement_id' => $message->signalementId,
            ]);

            return;
        }

        $suggestion = $this->suggestionGenerator->generate($signalement);
        $signalement->setSuggestion($suggestion);

        $this->entityManager->flush();
    }
}
