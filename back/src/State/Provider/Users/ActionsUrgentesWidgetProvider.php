<?php

namespace App\State\Provider\Users;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiDto\Users\ActionsUrgentesWidgetDto;
use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use App\Entity\Structure\StructureDepartementPersonnel;

class ActionsUrgentesWidgetProvider implements ProviderInterface
{
    public function __construct(
        private CollectionProvider $collectionProvider,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $data = $this->collectionProvider->provide($operation, $uriVariables, $context);

        $dto = new ActionsUrgentesWidgetDto();

        if (!is_iterable($data)) {
            return $dto;
        }

        foreach ($data as $personnelDept) {
            if (!$personnelDept instanceof StructureDepartementPersonnel) {
                continue;
            }

            // ----------------------
            // Notes à saisir :
            // ----------------------
            // 1. Récupérer les évaluations assignées au personnel
            // 2. Pour chaque éval :
            // Calc le nombre de notes attendues->combien d'EtudiantNote.note !== null / all EtudiantNote liés à l'Evaluation
            // 3. Nombre notes attendus - Nombre notes saisies = X notes à saisir
            // todo: ajouter une date limite informative de saisie des notes sur les évaluations


            switch ($personnelDept->getRoles()['intranet']) {
                case 'ROLE_PERMANENT':
                    $dto->items = array_merge($dto->items, $this->getDatasPermanent($personnelDept));
                    break;
                case 'ROLE_VACATAIRE':
                    $dto->items = array_merge($dto->items, $this->getDatasVacataire($personnelDept));
                    break;
                case 'ROLE_ASSISTANT':
                    $dto->items = array_merge($dto->items, $this->getDatasAssistant($personnelDept));
                    break;
                case 'ROLE_CHEF_DEPARTEMENT':
                    $dto->items = array_merge($dto->items, $this->getDatasChefDeDept($personnelDept));
                    break;
                case 'ROLE_DIRECTEUR_ETUDES':
                    $dto->items = array_merge($dto->items, $this->getDatasDirecteurEtudes($personnelDept));
                    break;
                case 'ROLE_STAGE':
                    $dto->items = array_merge($dto->items, $this->getDatasStage($personnelDept));
                    break;
                case 'ROLE_RESP_PARCOURS':
                    $dto->items = array_merge($dto->items, $this->getDatasRP($personnelDept));
                    break;
                case 'ROLE_EDT':
                    $dto->items = array_merge($dto->items, $this->getDatasEDT($personnelDept));
                    break;
                case 'ROLE_REFERENT':
                    $dto->items = array_merge($dto->items, $this->getDatasReferent($personnelDept));
                    break;
                case 'ROLE_INFORMATIQUE':
                    $dto->items = array_merge($dto->items, $this->getDatasInformatique($personnelDept));
                    break;
                case 'ROLE_SCOLARITE':
                    $dto->items = array_merge($dto->items, $this->getDatasScolarite($personnelDept));
                    break;

                default:
                    $dto->items = array_merge($dto->items, [
                        ['icon' => 'pi pi-file', 'titre' => '1 plan de cours à saisir', 'detail' => 'Date limite : 22 mai 2025', 'cta' => 'Saisir', 'color' => 'red'],
                        ['icon' => 'pi pi-pencil', 'titre' => 'Notes à saisir', 'detail' => 'Avant jeudi 18h', 'cta' => 'Saisir les notes', 'color' => 'orange'],
                    ]);
                    break;
            }
        }



        return $dto;
    }

    private function getDatasPermanent($personnelDept): array
    {
        //TODO: 
        // types d'éléments à récupérer :
        // - plans de cours à saisir
        // - notes à saisir
        // - justificatifs d'absences à valider
        // - demandes de rattrapage
        $data = [
            ['icon' => 'pi pi-file', 'titre' => '1 plan de cours à saisir', 'detail' => 'Date limite : 22 mai 2025', 'cta' => 'Saisir', 'color' => 'red'],
        ];

        return $data;
    }

    private function getDatasVacataire($personnelDept): array
    {
        //TODO: 
        // types d'éléments à récupérer :
        // - plans de cours à saisir
        // - notes à saisir
        // - justificatifs d'absences à valider
        // - demandes de rattrapage
        $data = [
            ['icon' => 'pi pi-file', 'titre' => '1 plan de cours à saisir', 'detail' => 'Date limite : 22 mai 2025', 'cta' => 'Saisir', 'color' => 'red'],
            ['icon' => 'pi pi-pencil', 'titre' => 'Notes à saisir', 'detail' => 'Avant jeudi 18h', 'cta' => 'Saisir les notes', 'color' => 'orange'],
            ['icon' => 'pi pi-user', 'titre' => '2 absences à justifier', 'detail' => 'En attente de justificatif', 'cta' => 'Voir les absences', 'color' => 'yellow'],
        ];

        return $data;
    }

    private function getDatasAssistant($personnelDept): array
    {
        //TODO: 
        // types d'éléments à récupérer :
        // - plans de cours à saisir
        // - notes à saisir
        // - justificatifs d'absences à valider
        // - demandes de rattrapage
        $data = [
            ['icon' => 'pi pi-user', 'titre' => '2 absences à justifier', 'detail' => 'En attente de justificatif', 'cta' => 'Voir les absences', 'color' => 'yellow'],

        ];

        return $data;
    }

    private function getDatasChefDeDept($personnelDept): array
    {
        //TODO: 
        // types d'éléments à récupérer :
        // - plans de cours à saisir
        // - notes à saisir
        // - justificatifs d'absences à valider
        // - demandes de rattrapage
        $data = [
            ['icon' => 'pi pi-file', 'titre' => '1 plan de cours à saisir', 'detail' => 'Date limite : 22 mai 2025', 'cta' => 'Saisir', 'color' => 'red'],
            ['icon' => 'pi pi-pencil', 'titre' => 'Notes à saisir', 'detail' => 'Avant jeudi 18h', 'cta' => 'Saisir les notes', 'color' => 'orange'],
            ['icon' => 'pi pi-user', 'titre' => '2 absences à justifier', 'detail' => 'En attente de justificatif', 'cta' => 'Voir les absences', 'color' => 'yellow'],
        ];

        return $data;
    }

    private function getDatasDirecteurEtudes($personnelDept): array
    {
        //TODO: 
        // types d'éléments à récupérer :
        // - plans de cours à saisir
        // - notes à saisir
        // - justificatifs d'absences à valider
        // - demandes de rattrapage
        $data = [
            ['icon' => 'pi pi-file', 'titre' => '1 plan de cours à saisir', 'detail' => 'Date limite : 22 mai 2025', 'cta' => 'Saisir', 'color' => 'red'],
            ['icon' => 'pi pi-pencil', 'titre' => 'Notes à saisir', 'detail' => 'Avant jeudi 18h', 'cta' => 'Saisir les notes', 'color' => 'orange'],
            ['icon' => 'pi pi-user', 'titre' => '2 absences à justifier', 'detail' => 'En attente de justificatif', 'cta' => 'Voir les absences', 'color' => 'yellow'],
        ];

        return $data;
    }

    private function getDatasStage($personnelDept): array
    {
        //TODO: 
        // types d'éléments à récupérer :
        // - plans de cours à saisir
        // - notes à saisir
        // - justificatifs d'absences à valider
        // - demandes de rattrapage
        $data = [
            ['icon' => 'pi pi-file', 'titre' => '1 plan de cours à saisir', 'detail' => 'Date limite : 22 mai 2025', 'cta' => 'Saisir', 'color' => 'red'],
            ['icon' => 'pi pi-pencil', 'titre' => 'Notes à saisir', 'detail' => 'Avant jeudi 18h', 'cta' => 'Saisir les notes', 'color' => 'orange'],
            ['icon' => 'pi pi-user', 'titre' => '2 absences à justifier', 'detail' => 'En attente de justificatif', 'cta' => 'Voir les absences', 'color' => 'yellow'],
        ];

        return $data;
    }

    private function getDatasRP($personnelDept): array
    {
        //TODO: 
        // types d'éléments à récupérer :
        // - plans de cours à saisir
        // - notes à saisir
        // - justificatifs d'absences à valider
        // - demandes de rattrapage
        $data = [
            ['icon' => 'pi pi-file', 'titre' => '1 plan de cours à saisir', 'detail' => 'Date limite : 22 mai 2025', 'cta' => 'Saisir', 'color' => 'red'],
            ['icon' => 'pi pi-pencil', 'titre' => 'Notes à saisir', 'detail' => 'Avant jeudi 18h', 'cta' => 'Saisir les notes', 'color' => 'orange'],
            ['icon' => 'pi pi-user', 'titre' => '2 absences à justifier', 'detail' => 'En attente de justificatif', 'cta' => 'Voir les absences', 'color' => 'yellow'],
        ];

        return $data;
    }

    private function getDatasEDT($personnelDept): array
    {
        //TODO: 
        // types d'éléments à récupérer :
        // - plans de cours à saisir
        // - notes à saisir
        // - justificatifs d'absences à valider
        // - demandes de rattrapage
        $data = [
            ['icon' => 'pi pi-file', 'titre' => '1 plan de cours à saisir', 'detail' => 'Date limite : 22 mai 2025', 'cta' => 'Saisir', 'color' => 'red'],
            ['icon' => 'pi pi-pencil', 'titre' => 'Notes à saisir', 'detail' => 'Avant jeudi 18h', 'cta' => 'Saisir les notes', 'color' => 'orange'],
            ['icon' => 'pi pi-user', 'titre' => '2 absences à justifier', 'detail' => 'En attente de justificatif', 'cta' => 'Voir les absences', 'color' => 'yellow'],
        ];

        return $data;
    }

    private function getDatasReferent($personnelDept): array
    {
        //TODO: 
        // types d'éléments à récupérer :
        // - plans de cours à saisir
        // - notes à saisir
        // - justificatifs d'absences à valider
        // - demandes de rattrapage
        $data = [
            ['icon' => 'pi pi-file', 'titre' => '1 plan de cours à saisir', 'detail' => 'Date limite : 22 mai 2025', 'cta' => 'Saisir', 'color' => 'red'],
            ['icon' => 'pi pi-pencil', 'titre' => 'Notes à saisir', 'detail' => 'Avant jeudi 18h', 'cta' => 'Saisir les notes', 'color' => 'orange'],
            ['icon' => 'pi pi-user', 'titre' => '2 absences à justifier', 'detail' => 'En attente de justificatif', 'cta' => 'Voir les absences', 'color' => 'yellow'],
        ];

        return $data;
    }

    private function getDatasInformatique($personnelDept): array
    {
        //TODO: 
        // types d'éléments à récupérer :
        // - plans de cours à saisir
        // - notes à saisir
        // - justificatifs d'absences à valider
        // - demandes de rattrapage
        $data = [
            ['icon' => 'pi pi-file', 'titre' => '1 plan de cours à saisir', 'detail' => 'Date limite : 22 mai 2025', 'cta' => 'Saisir', 'color' => 'red'],
            ['icon' => 'pi pi-pencil', 'titre' => 'Notes à saisir', 'detail' => 'Avant jeudi 18h', 'cta' => 'Saisir les notes', 'color' => 'orange'],
            ['icon' => 'pi pi-user', 'titre' => '2 absences à justifier', 'detail' => 'En attente de justificatif', 'cta' => 'Voir les absences', 'color' => 'yellow'],
        ];

        return $data;
    }

    private function getDatasScolarite($personnelDept): array
    {
        //TODO: 
        // types d'éléments à récupérer :
        // - plans de cours à saisir
        // - notes à saisir
        // - justificatifs d'absences à valider
        // - demandes de rattrapage
        $data = [
            ['icon' => 'pi pi-file', 'titre' => '1 plan de cours à saisir', 'detail' => 'Date limite : 22 mai 2025', 'cta' => 'Saisir', 'color' => 'red'],
            ['icon' => 'pi pi-pencil', 'titre' => 'Notes à saisir', 'detail' => 'Avant jeudi 18h', 'cta' => 'Saisir les notes', 'color' => 'orange'],
            ['icon' => 'pi pi-user', 'titre' => '2 absences à justifier', 'detail' => 'En attente de justificatif', 'cta' => 'Voir les absences', 'color' => 'yellow'],
        ];

        return $data;
    }
}
