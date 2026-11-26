<?php

declare(strict_types=1);

namespace IntranetBundle\State\Provider\Previsionnel;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\AnneeUniversitaire\AnneeUniversitaireContext;
use App\Entity\Scolarite\ScolEnseignement;
use App\Repository\Structure\StructureSemestreRepository;
use IntranetBundle\Dto\Previsionnel\PrevisionnelSemestreDto;
use IntranetBundle\Entity\Previsionnel\Previsionnel;
use IntranetBundle\Service\Previsionnel\VolumeMaquetteResolver;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final readonly class PrevisionnelSemestreProvider implements ProviderInterface
{
    private const TYPES = ['CM', 'TD', 'TP'];
    private const ZERO = ['CM' => 0, 'TD' => 0, 'TP' => 0];

    public function __construct(
        private CollectionProvider $collectionProvider,
        private StructureSemestreRepository $semestreRepository,
        private AnneeUniversitaireContext $anneeUniversitaireContext,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $semestreId = $operation->getParameters()?->get('semestre')?->getValue(null) ?? $context['filters']['semestre'] ?? null;
        $enseignementId = $operation->getParameters()?->get('enseignement')?->getValue(null) ?? $context['filters']['enseignement'] ?? null;
        $semestre = is_numeric($semestreId) ? $this->semestreRepository->find((int) $semestreId) : null;
        if ($semestre && $this->anneeUniversitaireContext->request()
            && $semestre->getAnnee()?->getPn()?->getAnneeUniversitaire()?->getId() !== $this->anneeUniversitaireContext->requireId()) {
            throw new ConflictHttpException('Le semestre ne correspond pas à l’année universitaire de la requête.');
        }
        $nbGroupesSemestre = [
            'CM' => $semestre?->getNbGroupesCm() ?? 0,
            'TD' => $semestre?->getNbGroupesTd() ?? 0,
            'TP' => $semestre?->getNbGroupesTp() ?? 0,
        ];

        $enseignements = [];
        foreach ($semestre?->getUes() ?? [] as $ue) {
            foreach ($ue->getEnseignementUes() as $lien) {
                $enseignement = $lien->getEnseignement();
                if ($enseignement && (!is_numeric($enseignementId) || (int) $enseignementId === $enseignement->getId())) {
                    $enseignements[$enseignement->getId()] = $this->initialiserEnseignement($enseignement);
                }
            }
        }

        $lignes = [];
        foreach ($this->collectionProvider->provide($operation, $uriVariables, $context) as $item) {
            $enseignement = $item->getEnseignement();
            $personnel = $item->getPersonnel();
            if (!$enseignement || !$personnel) {
                continue;
            }

            $id = $enseignement->getId();
            $enseignements[$id] ??= $this->initialiserEnseignement($enseignement);
            $ligne = $this->formToDto($item);
            $lignes[] = $ligne;
            $enseignements[$id]['personnels'][$personnel->getId()] = $personnel;
            foreach (self::TYPES as $type) {
                $heures = $item->getHeures()[$type] ?? 0;
                $groupes = $item->getGroupes()[$type] ?? 0;
                $enseignements[$id]['heures'][$type] += $heures;
                $enseignements[$id]['groupes'][$type] += $groupes;
                $diviseur = $nbGroupesSemestre[$type] ?: $groupes;
                $enseignements[$id]['parEtudiant'][$type] += $diviseur > 0 ? $heures / $diviseur : 0;
            }
        }

        $maquette = $service = $attendu = $saisi = self::ZERO;
        $synthese = [];
        foreach ($enseignements as $id => $groupe) {
            $enseignement = $groupe['enseignement'];
            $ligne = $this->enseignementToDto($enseignement);
            $ligne->setId($id);
            $ligne->setPersonnels(array_values($groupe['personnels']));
            $ligne->setGroupes($groupe['groupes']);
            $heures = [];
            foreach (self::TYPES as $type) {
                $volumes = $enseignement->getHeures()[$type] ?? [];
                $heuresMaquette = VolumeMaquetteResolver::resolve($volumes);
                $heuresService = $heuresMaquette * max(1, $nbGroupesSemestre[$type]);
                $heures[$type] = $this->ecart($heuresService, $groupe['heures'][$type]);
                $maquette[$type] += $heuresService;
                $service[$type] += $groupe['heures'][$type];
                $attendu[$type] += $heuresMaquette;
                $saisi[$type] += $groupe['parEtudiant'][$type];
            }
            $heures['Total'] = $this->ecart(array_sum(array_column($heures, 'Maquette')), array_sum($groupe['heures']));
            $ligne->setHeures($heures);
            $synthese[] = $ligne;
        }

        $totaux = $verification = [];
        foreach (self::TYPES as $type) {
            $totaux[$type] = $this->ecart($maquette[$type], $service[$type]);
            $verification[$type] = [
                'NbHrAttendu' => round($attendu[$type], 1),
                'NbHrSaisi' => round($saisi[$type], 1),
                'Diff' => round($saisi[$type] - $attendu[$type], 1),
            ];
        }
        $totaux['Total'] = $this->ecart(array_sum($maquette), array_sum($service));
        $service['Total'] = array_sum($service);

        return [
            $lignes,
            $synthese,
            $totaux,
            $verification,
            array_map(static fn ($heures) => round($heures, 1), $service),
            [
                'TotalClassique' => round($service['Total'], 1),
                'TotalTd' => round($service['CM'] * ScolEnseignement::MAJORATION_CM + $service['TD'] + $service['TP'], 1),
            ],
            $nbGroupesSemestre,
        ];
    }

    private function initialiserEnseignement(ScolEnseignement $enseignement): array
    {
        return [
            'enseignement' => $enseignement,
            'personnels' => [],
            'heures' => self::ZERO,
            'groupes' => self::ZERO,
            'parEtudiant' => self::ZERO,
        ];
    }

    private function ecart(float $attendu, float $saisi): array
    {
        return ['Maquette' => round($attendu, 1), 'Previsionnel' => round($saisi, 1), 'Diff' => round($saisi - $attendu, 1)];
    }

    private function enseignementToDto(ScolEnseignement $enseignement): PrevisionnelSemestreDto
    {
        $dto = new PrevisionnelSemestreDto();
        $dto->setIdEnseignement($enseignement->getId());
        $dto->setCodeEnseignement($enseignement->getCodeEnseignement() ?? '');
        $dto->setLibelleEnseignement($enseignement->getDisplay());
        $dto->setTypeEnseignement($enseignement->getType());

        return $dto;
    }

    public function formToDto(Previsionnel $item): PrevisionnelSemestreDto
    {
        $personnel = $item->getPersonnel();
        $dto = $this->enseignementToDto($item->getEnseignement());
        $dto->setId($item->getId());
        $dto->setIdPersonnel($personnel->getId());
        $dto->setPersonnels([$personnel]);
        $dto->setIntervenant($personnel->getDisplay());
        $dto->setHeures($item->getRepartitionHeures());
        $dto->setGroupes($item->getGroupes());
        $dto->setAnneeUniversitaire($item->getAnneeUniversitaire());

        return $dto;
    }
}
