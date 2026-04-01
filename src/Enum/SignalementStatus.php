<?php

namespace App\Enum;

enum SignalementStatus: string
{
    case Nouveau = 'nouveau';
    case EnAttenteValidation = 'en_attente_validation';
    case Valide = 'valide';
    case EnCours = 'en_cours';
    case SansSuite = 'sans_suite';
    case EscaladeJuridique = 'escalade_juridique';
    case Resolu = 'resolu';
    case Traite = 'traite';
    case Clos = 'clos';

    public function label(): string
    {
        return match ($this) {
            self::Nouveau => 'Nouveau',
            self::EnAttenteValidation => 'En attente de validation',
            self::Valide => 'Validé',
            self::EnCours => 'En cours',
            self::SansSuite => 'Classé sans suite',
            self::EscaladeJuridique => 'Transmis au juridique',
            self::Resolu => 'Résolu',
            self::Traite => 'Traite',
            self::Clos => 'Clos',
        };
    }

    /**
     * @return list<self>
     */
    public static function moderationCases(): array
    {
        return [
            self::EnAttenteValidation,
            self::EnCours,
            self::Valide,
            self::SansSuite,
            self::EscaladeJuridique,
            self::Resolu,
        ];
    }

    public function color(): string
    {
        return match ($this) {
            self::EnAttenteValidation => 'bg-yellow-500',
            self::Valide => 'bg-green-500',
            self::EnCours => 'bg-blue-500',
            self::SansSuite => 'bg-gray-500',
            self::EscaladeJuridique => 'bg-purple-500',
            self::Resolu => 'bg-emerald-500',
            default => 'bg-slate-500',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::EnAttenteValidation => 'hourglass_empty',
            self::Valide => 'check_circle',
            self::EnCours => 'warning',
            self::SansSuite => 'cancel',
            self::EscaladeJuridique => 'scale',
            self::Resolu => 'done_all',
            default => 'help',
        };
    }
}
