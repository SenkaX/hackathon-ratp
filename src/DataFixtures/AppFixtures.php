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
        foreach ($this->busStops() as $id => $row) {
            $stop = (new BusStop())
                ->setId($id)
                ->setLabel($row['label'])
                ->setLatitude($row['latitude'])
                ->setLongitude($row['longitude']);

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
            ->setRoles([User::ROLE_MANAGER]);
        $moderator->setPassword($this->passwordHasher->hashPassword($moderator, 'Moderator123!'));
        $manager->persist($moderator);

        $rh = (new User())
            ->setEmail('rh@ratp.local')
            ->setRoles([User::ROLE_RH]);
        $rh->setPassword($this->passwordHasher->hashPassword($rh, 'Rh123456!'));
        $manager->persist($rh);

        $manager->flush();
    }

    /**
     * @return array<string, array{label: string, latitude: float, longitude: float}>
     */
    private function busStops(): array
    {
        return [
            'gabriel-peri-metro' => ['label' => 'Gabriel Peri - Metro', 'latitude' => 48.9006, 'longitude' => 2.2933],
            'place-voltaire' => ['label' => 'Place Voltaire', 'latitude' => 48.8934, 'longitude' => 2.2978],
            'marche-de-gennevilliers' => ['label' => 'Marche de Gennevilliers', 'latitude' => 48.9213, 'longitude' => 2.2966],
            'cite-jardins' => ['label' => 'Cite Jardins', 'latitude' => 48.9178, 'longitude' => 2.2944],
            'gresilons-felix-eboue' => ['label' => 'Gresillons - Felix Eboue', 'latitude' => 48.9241, 'longitude' => 2.2989],
            'tour-dasnieres-laurent-cely' => ['label' => 'Tour d\'Asnieres - Laurent Cely', 'latitude' => 48.9112, 'longitude' => 2.2855],
            'pierre-curie' => ['label' => 'Pierre Curie', 'latitude' => 48.9156, 'longitude' => 2.2901],
            'les-gresilons' => ['label' => 'Les Gresillons', 'latitude' => 48.9248, 'longitude' => 2.3012],
            'caboeufs' => ['label' => 'Caboeufs', 'latitude' => 48.9267, 'longitude' => 2.3045],
            'quatre-chemins' => ['label' => 'Quatre Chemins', 'latitude' => 48.9289, 'longitude' => 2.3089],
            'bongarde' => ['label' => 'Bongarde', 'latitude' => 48.9301, 'longitude' => 2.3123],
            'lycee-petiet-cc' => ['label' => 'Lycee Petiet - Centre Commercial', 'latitude' => 48.9134, 'longitude' => 2.3067],
            'maurice-ravel' => ['label' => 'Maurice Ravel', 'latitude' => 48.9078, 'longitude' => 2.3034],
            'pointet' => ['label' => 'Pointet', 'latitude' => 48.9056, 'longitude' => 2.3056],
            'la-noue' => ['label' => 'La Noue', 'latitude' => 48.9034, 'longitude' => 2.3078],
            'gerard-philipe' => ['label' => 'Gerard Philipe', 'latitude' => 48.9012, 'longitude' => 2.3101],
            'stade' => ['label' => 'Stade', 'latitude' => 48.8989, 'longitude' => 2.3123],
            'place-paul-herbe' => ['label' => 'Place Paul Herbe', 'latitude' => 48.8967, 'longitude' => 2.3145],
            'saint-exupery' => ['label' => 'Saint-Exupery', 'latitude' => 48.8945, 'longitude' => 2.3167],
            'parc-departemental' => ['label' => 'Parc departemental', 'latitude' => 48.8923, 'longitude' => 2.3189],
            'zone-industrielle-nord' => ['label' => 'Zone Industrielle Nord', 'latitude' => 48.9312, 'longitude' => 2.3212],
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
