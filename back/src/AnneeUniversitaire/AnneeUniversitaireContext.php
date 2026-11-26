<?php

declare(strict_types=1);

namespace App\AnneeUniversitaire;

use App\Entity\Structure\StructureAnneeUniversitaire;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\{Request, RequestStack};
use Symfony\Component\HttpKernel\Exception\{BadRequestHttpException, ConflictHttpException};

/** Résolution limitée à la requête courante ; aucun état statique ni année en session serveur. */
final readonly class AnneeUniversitaireContext
{
    public const HEADER = 'X-Annee-Universitaire';
    public const MODE_HEADER = 'X-Contexte-Annuel';
    private const ALIASES = ['anneeUniversitaire', 'anneeUniversitaireId', 'anneeUnivId', 'annee_universitaire'];
    private const REFERENCES = [
        'semestreId' => ['App\\Entity\\Structure\\StructureSemestre', 'id'],
        'semestre' => ['App\\Entity\\Structure\\StructureSemestre', 'id'],
        'anneeId' => ['App\\Entity\\Structure\\StructureAnnee', 'id'],
        'annee' => ['App\\Entity\\Structure\\StructureAnnee', 'id'],
        'pn' => ['App\\Entity\\Structure\\StructurePn', 'id'],
        'scolariteSemestre' => ['App\\Entity\\Etudiant\\EtudiantScolariteSemestre', 'id'],
        'scolarite' => ['App\\Entity\\Etudiant\\EtudiantScolarite', 'id'],
        'evaluation' => ['App\\Entity\\Scolarite\\ScolEvaluation', 'id'],
        'ue' => ['App\\Entity\\Structure\\StructureUe', 'id'],
    ];

    public function __construct(private RequestStack $requests, private EntityManagerInterface $em, private PerimetreAnnuelRegistry $registry) {}
    public function request(): ?Request { return $this->requests->getCurrentRequest(); }

    public function id(mixed $value): int
    {
        if ($value === 'courante') {
            // Compatibilité explicite avec l’ancien contrat ; jamais un repli implicite.
            $annee = $this->em->getRepository(StructureAnneeUniversitaire::class)->findOneBy(['actif' => true]);
            if ($annee === null) throw new BadRequestHttpException('Aucune année universitaire active.');
            return $annee->getId();
        }
        if (is_string($value) && preg_match('~^/api/structure_annee_universitaires/(\d+)$~', $value, $matches)) $value = $matches[1];
        if ((!is_string($value) && !is_int($value)) || !ctype_digit((string) $value) || (int) $value < 1) throw new BadRequestHttpException('Identifiant d’année universitaire invalide.');
        return (int) $value;
    }

    public function body(Request $request): array
    {
        if ($request->getContentTypeFormat() === 'json' || str_contains($request->headers->get('Content-Type', ''), 'json')) {
            $data = json_decode($request->getContent(), true);
            return is_array($data) ? $data : [];
        }
        return $request->request->all();
    }

    public function explicit(Request $request): ?int
    {
        $ids = [];
        foreach ([$request->query->all(), $this->body($request)] as $data) {
            foreach (self::ALIASES as $alias) if (isset($data[$alias]) && $data[$alias] !== '') $ids[] = $this->id($data[$alias]);
        }
        $ids = array_values(array_unique($ids));
        if (count($ids) > 1) throw new ConflictHttpException('Des années universitaires contradictoires sont fournies.');
        return $ids[0] ?? null;
    }

    public function requireId(?Request $request = null): int
    {
        $request ??= $this->request();
        if ($request === null) throw new \LogicException('Hors HTTP, passer une année explicitement au service métier.');
        if ($request->attributes->has('_annee_universitaire_id')) return $request->attributes->getInt('_annee_universitaire_id');
        $mode = $request->headers->get(self::MODE_HEADER, 'selection');
        if (!in_array($mode, ['selection', 'explicite', 'ressource', 'toutes', 'aucun'], true)) throw new BadRequestHttpException('Mode de contexte annuel inconnu.');
        $explicit = $this->explicit($request);
        $reference = $this->fromReferences($request);
        $header = $request->headers->get(self::HEADER);
        if ($mode === 'explicite' && $header !== null && $explicit !== null && $this->id($header) !== $explicit) throw new ConflictHttpException('Le contexte explicite contredit l’année de la requête.');
        $id = $explicit ?? ($mode === 'explicite' && $header !== null ? $this->id($header) : null) ?? $reference ?? ($header !== null ? $this->id($header) : null);
        if ($id === null || in_array($mode, ['aucun', 'toutes'], true)) throw new BadRequestHttpException('Cette opération exige une année universitaire explicite ou sélectionnée.');
        if ($reference !== null && $id !== $reference) throw new ConflictHttpException('La ressource et le contexte annuel ne correspondent pas.');
        if ($this->em->find(StructureAnneeUniversitaire::class, $id) === null) throw new BadRequestHttpException('Année universitaire introuvable.');
        $request->attributes->set('_annee_universitaire_id', $id);
        return $id;
    }

    public function toutesAutorisees(string $class): bool
    {
        $request = $this->request();
        if ($request?->headers->get(self::MODE_HEADER) !== 'toutes') return false;
        if (!$request->isMethodSafe() || !$this->registry->autoriseToutes($class)) throw new BadRequestHttpException('La consultation de toutes les années n’est pas autorisée sur cette opération.');
        $request->attributes->set('_contexte_annuel_toutes', true);
        return true;
    }

    public function fromReferences(Request $request): ?int
    {
        $data = [...$request->query->all(), ...$this->body($request), ...$request->attributes->all()];
        $ids = [];
        foreach (self::REFERENCES as $key => [$class, $identifier]) {
            $value = $data[$key] ?? null;
            if ($value === null || $value === '' || is_array($value) || is_object($value)) continue;
            if (is_string($value) && str_starts_with($value, '/api/')) $value = basename($value);
            if (!ctype_digit((string) $value)) continue;
            $object = $this->em->getRepository($class)->findOneBy([$identifier => (int) $value]);
            if ($object === null) continue; // Le contrôleur ou provider conserve son erreur 404 habituelle.
            $years = $this->years($object);
            if (count($years) === 1) $ids[] = $years[0];
        }
        $ids = array_values(array_unique($ids));
        if (count($ids) > 1) throw new ConflictHttpException('Les ressources référencées appartiennent à des années différentes.');
        return $ids[0] ?? null;
    }

    public function years(object $object): array
    {
        if ($object instanceof StructureAnneeUniversitaire) return [$object->getId()];
        $class = $this->em->getClassMetadata($object::class)->getName();
        $path = $this->registry->path($class);
        if ($path === null) return [];
        $objects = [$object];
        foreach (explode('.', $path) as $property) {
            $next = [];
            foreach ($objects as $owner) {
                $this->em->initializeObject($owner);
                $metadata = $this->em->getClassMetadata($owner::class);
                $value = $metadata->getFieldValue($owner, $property);
                foreach (is_iterable($value) ? $value : [$value] as $item) if (is_object($item)) $next[] = $item;
            }
            $objects = $next;
        }
        return array_values(array_unique(array_filter(array_map(static fn ($year) => $year instanceof StructureAnneeUniversitaire ? $year->getId() : null, $objects))));
    }

    public function isModele(object $entity): bool
    {
        return $entity instanceof \App\Extension\HorsAnneeInterface && $entity->estHorsAnnee();
    }

    public function apply(\Doctrine\ORM\QueryBuilder $qb, string $class, string $alias, ?int $year = null): void
    {
        $path = $this->registry->path($class);
        if ($path === null) return;
        $year ??= $this->requireId();
        $names = new \ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator();
        foreach (explode('.', $path) as $field) {
            do { $join = $names->generateJoinAlias('annual_'.$field); } while (in_array($join, $qb->getAllAliases(), true));
            $qb->innerJoin($alias.'.'.$field, $join);
            $alias = $join;
        }
        $parameter = 'annual_context_'.count($qb->getParameters());
        $qb->distinct()->andWhere($alias.'.id = :'.$parameter)->setParameter($parameter, $year);
    }
}
