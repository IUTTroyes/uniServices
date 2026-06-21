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

            //todo:
            // types d'éléments à récupérer :
            // - plans de cours à saisir
            // - notes à saisir
            // - justificatifs d'absences à valider
            // - ??

            // ----------------------
            // Notes à saisir :
            // ----------------------
            // 1. Récupérer les évaluations assignées au personnel
            // 2. Pour chaque éval :
                // Calc le nombre de notes attendues->combien d'EtudiantNote.note !== null / all EtudiantNote liés à l'Evaluation
            // 3. Nombre notes attendus - Nombre notes saisies = X notes à saisir
            // todo: ajouter une date limite informative de saisie des notes sur les évaluations


//            $dto->items[] = [
//                'icon' => 'pi pi-file',
//                'titre' => '1 plan de cours à saisir',
//                'detail' => 'Date limite : 22 mai 2025',
//                'cta' => 'Saisir',
//                'color' => 'red'
//            ];
        }


        $dto->items = [
            ['icon' => 'pi pi-file', 'titre' => '1 plan de cours à saisir', 'detail' => 'Date limite : 22 mai 2025', 'cta' => 'Saisir', 'color' => 'red'],
            ['icon' => 'pi pi-pencil', 'titre' => 'Notes à saisir', 'detail' => 'Avant jeudi 18h', 'cta' => 'Saisir les notes', 'color' => 'orange'],
            ['icon' => 'pi pi-user', 'titre' => '2 absences à justifier', 'detail' => 'En attente de justificatif', 'cta' => 'Voir les absences', 'color' => 'yellow'],
        ];

        return $dto;
    }
}
