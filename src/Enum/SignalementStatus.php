<?php

namespace App\Enum;

enum SignalementStatus: string
{
    case EnAttenteValidation = 'en_attente_validation';
    case EnCours = 'en_cours';
    case Traite = 'traite';
    case Clos = 'clos';

    public function label(): string
    {
        return match ($this) {
            self::EnAttenteValidation => 'En attente validation',
            self::EnCours => 'En cours',
            self::Traite => 'Traite',
            self::Clos => 'Clos',
        };
    }
}
