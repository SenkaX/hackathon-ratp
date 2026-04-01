<?php

namespace App\DataFixtures;

use App\Entity\BusStop;
use App\Entity\MotifGravite;
use App\Entity\User;
use App\Enum\SignalementMotif;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        foreach ($this->busStops() as $id => $label) {
            $stop = (new BusStop())
                ->setId($id)
                ->setLabel($label);

            $manager->persist($stop);
        }

        foreach ($this->motifGravites() as $row) {
            $entity = (new MotifGravite())
            ->setMotif($row['motif'])
            ->setGravite($row['gravite']);

            $manager->persist($entity);
        }

        $admin = (new User())
            ->setEmail('admin@ratp.local')
            ->setRoles([User::ROLE_ADMIN]);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'Admin123!'));
        $manager->persist($admin);

        $moderator = (new User())
            ->setEmail('moderateur@ratp.local')
            ->setRoles([User::ROLE_MODERATOR]);
        $moderator->setPassword($this->passwordHasher->hashPassword($moderator, 'Moderator123!'));
        $manager->persist($moderator);

        $manager->flush();
    }

    /**
     * @return array<string, string>
     */
    private function busStops(): array
    {
        return [
            'gabriel-peri-metro' => 'Gabriel Peri - Metro',
            'place-voltaire' => 'Place Voltaire',
            'marche-de-gennevilliers' => 'Marche de Gennevilliers',
            'cite-jardins' => 'Cite Jardins',
            'gresilons-felix-eboue' => 'Gresillons - Felix Eboue',
            'tour-dasnieres-laurent-cely' => 'Tour d\'Asnieres - Laurent Cely',
            'pierre-curie' => 'Pierre Curie',
            'les-gresilons' => 'Les Gresillons',
            'caboeufs' => 'Caboeufs',
            'quatre-chemins' => 'Quatre Chemins',
            'bongarde' => 'Bongarde',
            'lycee-petiet-cc' => 'Lycee Petiet - Centre Commercial',
            'maurice-ravel' => 'Maurice Ravel',
            'pointet' => 'Pointet',
            'la-noue' => 'La Noue',
            'gerard-philipe' => 'Gerard Philipe',
            'stade' => 'Stade',
            'place-paul-herbe' => 'Place Paul Herbe',
            'saint-exupery' => 'Saint-Exupery',
            'parc-departemental' => 'Parc departemental',
            'zone-industrielle-nord' => 'Zone Industrielle Nord',
        ];
    }

    /**
     * @return list<array{motif: SignalementMotif, gravite: int}>
     */
    private function motifGravites(): array
    {
        return [
            ['motif' => SignalementMotif::AgressionPhysique, 'gravite' => 5],
            ['motif' => SignalementMotif::ConduiteDangereuse, 'gravite' => 4],
            ['motif' => SignalementMotif::NonArretStation, 'gravite' => 2],
            ['motif' => SignalementMotif::AgressionVerbale, 'gravite' => 3],
            ['motif' => SignalementMotif::RefusOuverturePorte, 'gravite' => 2],
        ];
    }
}
