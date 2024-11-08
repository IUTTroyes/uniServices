<?php
/*
 * Copyright (c) 2023. | David Annebicque | IUT de Troyes  - All Rights Reserved
 * @file /Users/davidannebicque/Sites/intranetV3/src/Components/Questionnaire/Form/QuestionnaireSsQuestionType.php
 * @author davidannebicque
 * @project intranetV3
 * @lastUpdate 02/08/2023 13:59
 */

namespace App\Components\Questionnaire\Form;

use App\Entity\QuestQuestion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuestionnaireSsQuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('libelle', TextareaType::class, [
                'translation_domain' => 'form',
                'label' => 'label.libelle',
                'attr' => ['rows' => 3, 'maxlength' => 255],
            ])
            ->add('help', TextType::class, [
                'label' => 'label.question.help',
                'required' => false,
                'translation_domain' => 'form',
                'help' => 'Texte d\'aide qui sera indiqué avec la question. Exemple signification des valeurs, ...',
                'attr' => ['class' => 'form-control-sm', 'size' => 5],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => QuestQuestion::class,
            'translation_domain' => 'form',
            'listeTypeQuestion' => [],
        ]);
    }
}
