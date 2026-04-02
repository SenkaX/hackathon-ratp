<?php

namespace App\Message;

final readonly class GenerateSignalementSuggestionMessage
{
    public function __construct(
        public string $signalementId,
    ) {
    }
}
