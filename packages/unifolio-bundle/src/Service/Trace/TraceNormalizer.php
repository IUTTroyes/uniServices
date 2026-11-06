<?php

namespace UnifolioBundle\Service\Trace;

use App\Entity\Etudiant\EtudiantScolarite;
use UnifolioBundle\Entity\Trace;
use UnifolioBundle\Entity\TraceMedia;
use UnifolioBundle\Entity\TraceRattachement;
use UnifolioBundle\Repository\AutoEvaluationRepository;
use UnifolioBundle\Service\Perimetre\UnifolioPerimetre;

/** Représentation JSON des traces pour l'API. */
class TraceNormalizer
{
    public function __construct(
        private readonly UnifolioPerimetre $perimetre,
        private readonly AutoEvaluationRepository $autoEvaluations,
    ) {
    }

    /** @return array<string, mixed> */
    public function resume(Trace $trace): array
    {
        $premiereImage = null;
        foreach ($trace->getMedias() as $media) {
            if ($media->getType()->value === 'image' && $media->hasFichier()) {
                $premiereImage = $this->urlFichier($trace, $media);
                break;
            }
        }

        $competences = [];
        foreach ($trace->getRattachements() as $r) {
            $c = $r->getCompetenceParente();
            if ($c !== null) {
                $competences[$c->getId()] = ['id' => $c->getId(), 'nomCourt' => $c->getNomCourt() ?? $c->getLibelle(), 'couleur' => $c->getCouleur()];
            }
        }

        return [
            'uuid' => $trace->getUuidString(),
            'titre' => $trace->getTitre(),
            'dateRealisation' => $trace->getDateRealisation()?->format('Y-m-d'),
            'typeActivite' => $this->enum($trace->getTypeActivite()),
            'statut' => ['value' => $trace->getStatut()->value, 'libelle' => $trace->getStatut()->getLibelle(), 'badge' => $trace->getStatut()->getBadge()],
            'scolarite' => $this->scolarite($trace->getScolarite()),
            'vignette' => $premiereImage,
            'nbMedias' => $trace->getMedias()->count(),
            'nbRattachements' => $trace->getRattachements()->count(),
            'competences' => array_values($competences),
            'tags' => $trace->getTags(),
            'modifiable' => $trace->isModifiable(),
            'misAJourLe' => $trace->getUpdatedAt()?->format(\DATE_ATOM),
        ];
    }

    /** @return array<string, mixed> */
    public function detail(Trace $trace): array
    {
        $autos = $this->autoEvaluations->findParRattachements($trace->getRattachements()->toArray());

        return $this->resume($trace) + [
            'contexte' => $trace->getContexte(),
            'conceptionIa' => ['value' => $trace->getConceptionIa()->value, 'libelle' => $trace->getConceptionIa()->getLibelle()],
            'realisationIa' => ['value' => $trace->getRealisationIa()->value, 'libelle' => $trace->getRealisationIa()->getLibelle()],
            'usageIaDetail' => $trace->getUsageIaDetail(),
            'deposeeLe' => $trace->getDeposeeLe()?->format(\DATE_ATOM),
            'erreursDepot' => $trace->getErreursDepot(),
            'medias' => array_map(fn (TraceMedia $m) => $this->media($trace, $m), $trace->getMedias()->toArray()),
            'rattachements' => array_map(function (TraceRattachement $r) use ($autos) {
                $auto = $autos[$r->getId()] ?? null;

                return $this->rattachement($r) + ['autoEvaluation' => $auto !== null ? [
                    'uuid' => $auto->getUuidString(),
                    'statut' => ['value' => $auto->getStatut()->value, 'libelle' => $auto->getStatut()->getLibelle(), 'badge' => $auto->getStatut()->getBadge()],
                    'score' => $auto->getScore(),
                ] : null];
            }, $trace->getRattachements()->toArray()),
        ];
    }

    /** @return array<string, mixed> */
    public function media(Trace $trace, TraceMedia $media): array
    {
        return [
            'id' => $media->getId(),
            'type' => $media->getType()->value,
            'libelleType' => $media->getType()->getLibelle(),
            'icone' => $media->getType()->getIcone(),
            'ordre' => $media->getOrdre(),
            'legende' => $media->getLegende(),
            'fichier' => $media->hasFichier() ? [
                'url' => $this->urlFichier($trace, $media),
                'nom' => $media->getNomOriginal(),
                'mimeType' => $media->getMimeType(),
                'taille' => $media->getTaille(),
            ] : null,
            'url' => $media->getUrl(),
            'contenu' => $media->getContenu(),
            'metadata' => $media->getMetadata(),
            'typesPossibles' => array_map(
                static fn ($t) => ['value' => $t->value, 'libelle' => $t->getLibelle(), 'icone' => $t->getIcone()],
                $media->getTypesCompatibles()
            ),
        ];
    }

    /** @return array<string, mixed> */
    public function rattachement(TraceRattachement $r): array
    {
        $cible = $r->getCible();
        $competence = $r->getCompetenceParente();

        return [
            'id' => $r->getId(),
            'type' => $r->getGranularite()->value,
            'cibleId' => $cible->getId(),
            'code' => $r->getApprentissageCritique()?->getCode(),
            'libelle' => $cible->getLibelle(),
            'competence' => $competence !== null ? ['id' => $competence->getId(), 'nomCourt' => $competence->getNomCourt() ?? $competence->getLibelle(), 'couleur' => $competence->getCouleur()] : null,
            'argumentaire' => $r->getArgumentaire(),
        ];
    }

    /** @return array<string, mixed>|null */
    public function scolarite(?EtudiantScolarite $scolarite): ?array
    {
        if ($scolarite === null) {
            return null;
        }
        $annee = $this->perimetre->getAnneeFormation($scolarite);

        return [
            'id' => $scolarite->getId(),
            'annee' => $annee?->getLibelle(),
            'anneeUniversitaire' => $scolarite->getAnneeUniversitaire()?->getLibelle(),
            'active' => $scolarite->isActif(),
        ];
    }

    private function urlFichier(Trace $trace, TraceMedia $media): string
    {
        return sprintf('/api/unifolio/traces/%s/medias/%d/fichier', $trace->getUuidString(), (int) $media->getId());
    }

    /** @return array{value: string, libelle: string}|null */
    private function enum(?\BackedEnum $enum): ?array
    {
        return $enum !== null && method_exists($enum, 'getLibelle')
            ? ['value' => (string) $enum->value, 'libelle' => $enum->getLibelle()]
            : null;
    }
}
