<?php

namespace App\Tests\Architecture;

use App\Extension\ChampsContribuesNormalizer;
use App\Extension\ChampsSerialisesInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/** Un package ajoute des champs à un objet du cœur sans que celui-ci le connaisse. */
class ChampsContribuesTest extends TestCase
{
    public function testAjouteLesChampsAvecLesMemesGroupesSansBoucler(): void
    {
        $contributeur = new class implements ChampsSerialisesInterface {
            public function supporte(object $objet): bool
            {
                return $objet instanceof Porteur;
            }

            public function champs(object $objet, array $groupes): array
            {
                return \in_array('detail', $groupes, true) ? ['lies' => [new Lie('a'), new Lie('b')], 'absent' => null] : [];
            }
        };
        $serializer = new Serializer([new ChampsContribuesNormalizer([$contributeur]), new ObjectNormalizer(defaultContext: ['groups' => []])]);

        self::assertSame(
            ['nom' => 'service', 'lies' => [['libelle' => 'a'], ['libelle' => 'b']]],
            $serializer->normalize(new Porteur('service'), null, ['groups' => ['detail']]),
        );
    }
}

final class Porteur
{
    public function __construct(public string $nom)
    {
    }
}

final class Lie
{
    public function __construct(public string $libelle)
    {
    }
}
