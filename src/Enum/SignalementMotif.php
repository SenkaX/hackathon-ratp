<?php

namespace App\Enum;

enum SignalementMotif: string
{
    case AgressionPhysique = 'agression_physique';
    case ConduiteDangereuse = 'conduite_dangereuse';
    case NonArretStation = 'non_arret_station';
    case AgressionVerbale = 'agression_verbale';
    case RefusOuverturePorte = 'refus_ouverture_porte';

    public function label(): string
    {
        return match ($this) {
            self::AgressionPhysique => 'Agression physique',
            self::ConduiteDangereuse => 'Conduite dangereuse',
            self::NonArretStation => 'Non-arret en station',
            self::AgressionVerbale => 'Agression verbale',
            self::RefusOuverturePorte => 'Refus d\'ouverture des portes',
        };
    }
}
