<?php
/*
 * Copyright (c) 2024. | David Annebicque | IUT de Troyes  - All Rights Reserved
 * @file /Users/davidannebicque/Sites/intranetV3/src/Table/ColumnType/GroupeEtudiantColumnType.php
 * @author davidannebicque
 * @project intranetV3
 * @lastUpdate 16/02/2024 22:08
 */

namespace App\Table\ColumnType;

use Dannebicque\TableBundle\Column\PropertyColumnType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupeEtudiantColumnType extends PropertyColumnType
{
    public function renderProperty(mixed $value, array $options): string
    {
        if (null === $value) {
            return 'err';
        }
        $html = '';
        foreach ($value as $gr) {
            $html .= '<span class="badge bg-success me-1">'.$gr->getLibelle().'</span>';
        }

        return $html;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('order', false);
        $resolver->setDefault('is_safe_html', true);
    }
}
