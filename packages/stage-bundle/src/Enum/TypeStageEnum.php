<?php

namespace StageBundle\Enum;

enum TypeStageEnum: string
{
    case CLASSIQUE = 'TYPE_STAGE_CLASSIQUE';
    case ETRANGER = 'TYPE_STAGE_ETRANGER';
    case ERASMUS = 'TYPE_STAGE_ERASMUS';
    case APPRENTISSAGE = 'TYPE_STAGE_APPRENTISSAGE';
}
