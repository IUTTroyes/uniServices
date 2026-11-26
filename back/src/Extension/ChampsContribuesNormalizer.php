<?php

declare(strict_types=1);

namespace App\Extension;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/** Complète la sortie normale d'un objet avec les champs apportés par les ChampsSerialisesInterface. */
final class ChampsContribuesNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const TRAITES = 'champs_contribues_traites';

    /** @param iterable<ChampsSerialisesInterface> $contributeurs */
    public function __construct(
        #[AutowireIterator('app.champs_serialises')]
        private readonly iterable $contributeurs,
    ) {
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        if (!\is_object($data) || isset($context[self::TRAITES][spl_object_id($data)])) {
            return false;
        }
        foreach ($this->contributeurs as $contributeur) {
            if ($contributeur->supporte($data)) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, bool> */
    public function getSupportedTypes(?string $format): array
    {
        return ['object' => false];
    }

    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        \assert(\is_object($data));
        $context[self::TRAITES][spl_object_id($data)] = true;
        $resultat = $this->normalizer->normalize($data, $format, $context);
        // Une ressource rendue par son seul identifiant (IRI) ne porte aucun champ.
        if (!\is_array($resultat)) {
            return $resultat;
        }

        $groupes = array_values((array) ($context['groups'] ?? []));
        // Les objets liés sont normalisés comme des sous-ressources : mêmes groupes, sans le contexte du parent.
        $enfant = array_intersect_key($context, array_flip(['groups', 'skip_null_values', self::TRAITES]));
        $enfant['jsonld_has_context'] = true;

        foreach ($this->contributeurs as $contributeur) {
            if (!$contributeur->supporte($data)) {
                continue;
            }
            foreach ($contributeur->champs($data, $groupes) as $nom => $valeur) {
                if ($valeur === null) {
                    continue;
                }
                $resultat[$nom] = \is_array($valeur)
                    ? array_map(fn (mixed $element) => $this->normalizer->normalize($element, $format, $enfant), $valeur)
                    : $this->normalizer->normalize($valeur, $format, $enfant);
            }
        }

        return $resultat;
    }
}
