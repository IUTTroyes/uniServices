<?php
/*
 * Copyright (c) 2024. | David Annebicque | IUT de Troyes  - All Rights Reserved
 * @file /Users/davidannebicque/Sites/intranetV3/src/Table/PlanCoursTableType.php
 * @author davidannebicque
 * @project intranetV3
 * @lastUpdate 29/02/2024 12:11
 */

namespace App\Table;

use App\Classes\Previsionnel\PrevisionnelManager;
use App\Entity\Diplome;
use App\Entity\Semestre;
use App\Enums\PlanCoursEnum;
use App\Repository\SemestreRepository;
use App\Table\ColumnType\StatusPCBadgeColumnType;
use BackedEnum;
use Dannebicque\TableBundle\Column\PropertyColumnType;
use Dannebicque\TableBundle\Column\WidgetColumnType;
use Dannebicque\TableBundle\DTO\TableResult;
use Dannebicque\TableBundle\DTO\TableState;
use Dannebicque\TableBundle\TableBuilder;
use Dannebicque\TableBundle\TableType;
use Dannebicque\TableBundle\Widget\Type\RowShowLinkType;
use Dannebicque\TableBundle\Widget\WidgetBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PlanCoursTableType extends TableType
{
    protected Diplome $diplome;
    protected int $annee;

    public function __construct(
        private readonly PrevisionnelManager $previsionnelManager
    ) {
    }

    public function buildTable(TableBuilder $builder, array $options): void
    {
        $this->diplome = $options['diplome'];
        $this->annee = $options['annee'];

        $builder->addFilter('semestre', EntityType::class, [
            'class' => Semestre::class,
            'choice_label' => 'display',
            'required' => false,
            'placeholder' => 'Filtrer par semestre',
            'query_builder' => fn (SemestreRepository $semestreRepository) => $semestreRepository->findByDiplomeBuilder($this->diplome),
        ]);
        $builder->addFilter('etat_plan_cours', EnumType::class, [
            'class' => PlanCoursEnum::class,
            'required' => false,
            'choice_label' => static fn (BackedEnum $choice): string => 'plan_cours.'.$choice->value,
            'placeholder' => 'Etat du plan de cours',
        ]);

//        $builder->addWidget('export', ExportDropdownType::class, [
//            'route' => 'administration_plan_cours_export',
//            'route_params' => [
//                'diplome' => $this->diplome->getId(),
//            ],
//        ]);

        $builder->addColumn('matiere_code_element', PropertyColumnType::class, [
            'order' => 'ASC',
            'label' => 'table.matiere_code_element',
            'translation_domain' => 'table',
        ]);
        $builder->addColumn('matiere_code', PropertyColumnType::class, [
            'order' => 'ASC',
            'label' => 'table.matiere_code',
            'translation_domain' => 'table',
        ]);
        $builder->addColumn('matiere_libelle', PropertyColumnType::class,
            ['label' => 'table.matiere_libelle', 'translation_domain' => 'table']);

        $builder->addColumn('personnel_display', PropertyColumnType::class,
            ['label' => 'table.personnel', 'translation_domain' => 'table']);

        $builder->addColumn('etatPlanCours', StatusPCBadgeColumnType::class,
            [
                'label' => 'table.etat_plan_cours',
                'translation_domain' => 'table',
            ]);

        $builder->addColumn('links', WidgetColumnType::class, [
            'build' => function (WidgetBuilder $builder, $s) {
                if ($s->etatPlanCours !== null) {
                    $builder->add('show', RowShowLinkType::class, [
                        'route' => 'app_plan_cours_apc_show',
                        'route_params' => [
                            'previsionnel' => $s->id,
                        ],
                        'xhr' => false,
                    ]);
                }
            },
        ]);

        $builder->setLoadUrl('administration_plan_cours_diplome', ['diplome' => $this->diplome->getId()]);

        $builder->useAdapter(function (TableState $state) {
            $formData = $state->getFormData();
            $semestres = $this->diplome->getSemestres();
            $t = [];
            foreach ($semestres as $semestre) {
                $previsionnels = $this->previsionnelManager->getPrevisionnelSemestre($semestre, $this->annee);
                foreach ($previsionnels as $previsionnel) {
                    $t[$semestre->getId()][$previsionnel->getTypeIdMatiere()] = $previsionnel;
                }
            }
            if (isset($formData['semestre']) && array_key_exists($formData['semestre'], $t)) {
                // filtre par semestre
                $t = $t[$formData['semestre']];
            } else {
                $t = array_merge(...$t);
            }

            if (isset($formData['etat_plan_cours'])) {
                // filtre par etat
                $tab = [];
                foreach ($t as $elt) {
                    if ($elt->etatPlanCours === $formData['etat_plan_cours']) {
                        $tab[] = $elt;
                    }
                }
                $t = $tab;
            }

            return new TableResult($t, count($t));
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'orderable' => true,
            'diplome' => null,
            'annee' => null,
            'exportable' => true,
        ]);
    }
}
