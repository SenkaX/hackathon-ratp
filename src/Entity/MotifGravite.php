<?php

namespace App\Entity;

use App\Enum\SignalementMotif;
use App\Repository\MotifGraviteRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MotifGraviteRepository::class)]
class MotifGravite
{
    #[ORM\Id]
    #[ORM\Column(length: 64, enumType: SignalementMotif::class)]
    private SignalementMotif $motif;

    #[ORM\Column]
    #[Assert\Range(min: 1, max: 5)]
    private int $gravite;

    public function getMotif(): SignalementMotif
    {
        return $this->motif;
    }

    public function setMotif(SignalementMotif $motif): static
    {
        $this->motif = $motif;

        return $this;
    }

    public function getGravite(): int
    {
        return $this->gravite;
    }

    public function setGravite(int $gravite): static
    {
        $this->gravite = $gravite;

        return $this;
    }
}
