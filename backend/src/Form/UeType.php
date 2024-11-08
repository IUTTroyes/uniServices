<?php
/*
 * Copyright (c) 2022. | David Annebicque | IUT de Troyes  - All Rights Reserved
 * @file /Users/davidannebicque/Sites/intranetV3/src/Form/UeType.php
 * @author davidannebicque
 * @project intranetV3
 * @lastUpdate 20/08/2022 17:26
 */

namespace App\Form;

use App\Entity\ApcCompetence;
use App\Entity\Diplome;
use App\Entity\Semestre;
use App\Entity\Ue;
use App\Form\Type\YesNoType;
use App\Repository\ApcComptenceRepository;
use App\Repository\SemestreRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class UeType.
 */
class UeType extends AbstractType
{
    protected ?Diplome $diplome = null;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->diplome = $options['diplome'];

        $builder
            ->add('libelle', TextType::class, ['label' => 'label.libelle'])
            ->add('codeElement', TextType::class, ['label' => 'label.code_element'])
            ->add('semestre', EntityType::class, [
                'class' => Semestre::class,
                'required' => true,
                'choice_label' => 'display',
                'query_builder' => fn (SemestreRepository $semestreRepository) => $semestreRepository->findByDiplomeBuilder($this->diplome),
                'label' => 'label.semestre',
                'expanded' => true,
            ])
            ->add('numero_ue', ChoiceType::class, ['choices' => range(0, 20), 'label' => 'label.numero_ue'])
            ->add('bonification', YesNoType::class, ['label' => 'label.bonification', 'help' => 'help.bonification'])
            ->add('coefficient', TextType::class, ['label' => 'label.coefficient'])
            ->add('nbEcts', TextType::class, ['label' => 'label.nb_ects']);

        if (true === $this->diplome->isApc()) {
            $builder->add('apcCompetence', EntityType::class, [
                'class' => ApcCompetence::class,
                'required' => false,
                'choice_label' => 'nomCourt',
                'query_builder' => fn (ApcComptenceRepository $apcComptenceRepository) => $apcComptenceRepository->findByReferentielBuilder($this->diplome->getReferentiel()),
                'label' => 'label.apc.competence',
                'expanded' => true,
                'help' => 'Le diplôme étant au format APC, vous pouvez attacher une compétence à cette UE',
            ]);
        }
    }

    /**
     * @throws AccessException
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ue::class,
            'diplome' => null,
            'translation_domain' => 'form',
        ]);
    }
}
