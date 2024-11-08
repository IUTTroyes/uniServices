<?php
/*
 * Copyright (c) 2024. | David Annebicque | IUT de Troyes  - All Rights Reserved
 * @file /Users/davidannebicque/Sites/intranetV3/src/Table/MccTableType.php
 * @author davidannebicque
 * @project intranetV3
 * @lastUpdate 16/02/2024 21:58
 */

namespace App\Table;

use App\Classes\Matieres\TypeMatiereManager;
use App\DTO\Matiere;
use App\Entity\AnneeUniversitaire;
use App\Entity\Semestre;
use App\Table\ColumnType\MccNbNotesColumnType;
use App\Table\ColumnType\MccPourcentageColumnType;
use Dannebicque\TableBundle\Column\PropertyColumnType;
use Dannebicque\TableBundle\Column\WidgetColumnType;
use Dannebicque\TableBundle\DTO\TableResult;
use Dannebicque\TableBundle\DTO\TableState;
use Dannebicque\TableBundle\TableBuilder;
use Dannebicque\TableBundle\TableType;
use Dannebicque\TableBundle\Widget\Type\RowShowLinkType;
use Dannebicque\TableBundle\Widget\WidgetBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MccTableType extends TableType
{
    protected Semestre $semestre;
    protected AnneeUniversitaire $anneeUniversitaire;
    protected array $mccs;

    public function __construct(
        private readonly TypeMatiereManager $typeMatiereManager
    ) {
    }

    public function buildTable(TableBuilder $builder, array $options): void
    {
        $this->semestre = $options['semestre'];
        $this->mccs = $options['mccs'];
        $this->anneeUniversitaire = $options['anneeUniversitaire'];

        $builder->addColumn('codeElement', PropertyColumnType::class, [
            'order' => 'ASC',
            'label' => 'table.code_element',
            'translation_domain' => 'messages',
        ]);
        $builder->addColumn('codeMatiere', PropertyColumnType::class, [
            'order' => 'ASC',
            'label' => 'table.code_element',
            'translation_domain' => 'messages',
        ]);
        $builder->addColumn('libelle', PropertyColumnType::class,
            ['label' => 'table.matiere_libelle', 'translation_domain' => 'messages']);
        $builder->addColumn('typeIdMatiereMcc', MccNbNotesColumnType::class,
            [
                'label' => 'table.nbNotes',
                'translation_domain' => 'messages',
                'mccs' => $this->mccs
            ]); // todo: à gérer dans table avec un column non mappé ?
        $builder->addColumn('typeIdMatiere', MccPourcentageColumnType::class,
            ['label' => 'table.pourcentage', 'translation_domain' => 'messages', 'mccs' => $this->mccs]);

        $builder->addColumn('links', WidgetColumnType::class, [
            'build' => function(WidgetBuilder $builder, Matiere $s) {
                $builder->add('show', RowShowLinkType::class, [
                    'route' => 'administration_mcc_show_matiere',
                    'route_params' => [
                        'typeIdMatiere' => $s->getTypeIdMatiere(),
                        'semestre' => $this->semestre->getId(),
                    ],
                    'xhr' => false,
                ]);
            },
        ]);
        $builder->setLoadUrl('administration_mcc_index', ['semestre' => $this->semestre->getId()]);

        $builder->useAdapter(function (TableState $state) {
            $matieres = $this->typeMatiereManager->findBySemestreAndReferentiel($this->semestre, $this->semestre->getDiplome()->getReferentiel());

            return new TableResult($matieres, count($matieres));
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'orderable' => true,
            'semestre' => null,
            'anneeUniversitaire' => null,
            'mccs' => null,
            'exportable' => true,
        ]);
    }
}
