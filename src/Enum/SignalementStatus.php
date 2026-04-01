<?php

namespace App\Enum;

enum SignalementStatus: string
{
    case EnCours = 'en_cours';
    case Traite = 'traite';
    case Clos = 'clos';

    public function label(): string
    {
        return match ($this) {
            self::EnCours => 'En cours',
            self::Traite => 'Traite',
            self::Clos => 'Clos',
        };
    }
}
