<?php
/*
 * Copyright (c) 2022. | David Annebicque | IUT de Troyes  - All Rights Reserved
 * @file /Users/davidannebicque/Sites/intranetV3/src/Form/SemestreType.php
 * @author davidannebicque
 * @project intranetV3
 * @lastUpdate 26/08/2022 14:15
 */

namespace App\Form;

use App\Entity\Annee;
use App\Entity\Diplome;
use App\Entity\Personnel;
use App\Entity\Ppn;
use App\Entity\Semestre;
use App\Form\Type\CollectionStimulusType;
use App\Form\Type\EntityCompleteType;
use App\Form\Type\YesNoType;
use App\Repository\AnneeRepository;
use App\Repository\PersonnelRepository;
use App\Repository\PpnRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class SemestreType.
 */
class SemestreType extends AbstractType
{
    protected ?Diplome $diplome = null;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->diplome = $options['diplome'];

        $builder
            ->add('libelle', TextType::class, [
                'label' => 'label.libelle',
            ])
            ->add('codeElement', TextType::class, [
                'label' => 'label.code_element',
            ])
            ->add('annee', EntityType::class, [
                'class' => Annee::class,
                'required' => true,
                'choice_label' => 'libelle',
                'expanded' => true,
                'query_builder' => fn (AnneeRepository $anneeRepository) => $anneeRepository->findByDiplomeBuilder($this->diplome),
                'label' => 'label.annee',
            ])

            ->add('ordreAnnee', ChoiceType::class, [
                'label' => 'label.ordre_annee',
                'choices' => [1 => 1, 2 => 2],
                'expanded' => true,
                'translation_domain' => 'form',
            ])
            ->add('moisDebut', ChoiceType::class, [
                'label' => 'label.mois_debut',
                'choices' => [
                    'Janvier' => 1,
                    'Février' => 2,
                    'Mars' => 3,
                    'Avril' => 4,
                    'Mai' => 5,
                    'Juin' => 6,
                    'Juillet' => 7,
                    'Août' => 8,
                    'Septembre' => 9,
                    'Octobre' => 10,
                    'Novembre' => 11,
                    'Décembre' => 12,
                ],
                'expanded' => false,
                'translation_domain' => 'form',
            ])
            ->add('ordreLmd', ChoiceType::class, [
                'label' => 'label.ordre_lmd',
                'choices' => range(0, 16),
            ])
            ->add(
                'actif',
                YesNoType::class,
                [
                    'label' => 'label.actif',
                ]
            )
            ->add('nbGroupesCm', ChoiceType::class, [
                'label' => 'label.nbGroupesCm',
                'choices' => range(0, 10),
            ])
            ->add('nbGroupesTd', ChoiceType::class, [
                'label' => 'label.nbGroupesTd',
                'choices' => range(0, 10),
            ])
            ->add('nbGroupesTP', ChoiceType::class, [
                'label' => 'label.nbGroupesTP',
                'choices' => range(0, 20),
            ])
            ->add(
                'optMailReleve',
                YesNoType::class,
                [
                    'label' => 'label.opt_mail_releve',
                ]
            )
            ->add('optDestMailReleve', EntityCompleteType::class, [
                'class' => Personnel::class,
                'choice_label' => 'display',
                'label' => 'label.opt_destinataire_mail_releve',
                'required' => false,
                'query_builder' => static fn (PersonnelRepository $personnelRepository) => $personnelRepository->findAllOrder(),
            ])
            ->add(
                'optEvaluationModifiable',
                YesNoType::class,
                [
                    'label' => 'label.opt_evaluation_modifiable',
                ]
            )
            ->add(
                'optMailModificationNote',
                YesNoType::class,
                [
                    'label' => 'label.opt_mail_modification_note',
                ]
            )
            ->add('optDestMailModifNote', EntityCompleteType::class, [
                'class' => Personnel::class,
                'choice_label' => 'display',
                'label' => 'label.opt_destinataire_mail_modification_note',
                'required' => false,
                'query_builder' => static fn (PersonnelRepository $personnelRepository) => $personnelRepository->findAllOrder(),
            ])
            ->add(
                'optEvaluationVisible',
                YesNoType::class,
                [
                    'label' => 'label.opt_evaluation_visible',
                ]
            )
            ->add(
                'optPenaliteAbsence',
                YesNoType::class,
                [
                    'label' => 'label.opt_penalite_absence',
                ]
            )
            ->add(
                'optPointPenaliteAbsence',
                TextType::class,
                [
                    'label' => 'label.opt_point_penalite_absence',
                    'required' => false,
                ]
            )
            ->add(
                'optMailAbsenceResp',
                YesNoType::class,
                [
                    'label' => 'label.opt_mail_absence_responsable',
                ]
            )
            ->add('optDestMailAbsenceResp', EntityCompleteType::class, [
                'class' => Personnel::class,
                'choice_label' => 'display',
                'label' => 'label.opt_destinataire_mail_absence_responsable',
                'required' => false,
                'query_builder' => static fn (PersonnelRepository $personnelRepository) => $personnelRepository->findAllOrder(),
            ])
            ->add(
                'optMailAbsenceEtudiant',
                YesNoType::class,
                [
                    'label' => 'label.opt_mail_absence_etudiant',
                ]
            )
            ->add(
                'optPenaliteAbsence',
                YesNoType::class,
                [
                    'label' => 'label.opt_point_penalite_absence',
                ]
            )
            ->add('ppn_actif', EntityType::class, [
                'class' => Ppn::class,
                'required' => false,
                'choice_label' => 'libelle',
                'query_builder' => fn (PpnRepository $ppnRepository) => $ppnRepository->findByDiplomeBuilder($this->diplome),
                'label' => 'label.ppn_actif',
            ])
            ->add('semestreLienDepart', CollectionStimulusType::class, [
                'entry_type' => SemestreLienType::class,
                'entry_options' => [
                    'label' => false,
                    'diplome' => $this->diplome, ],
                'allow_add' => true,
                'prototype' => true,
                'allow_delete' => true,
                'label' => 'Questions pour la question',
                'by_reference' => false,
                'max_items' => 0,
                'help' => 'Il est possible d\'ajouter les questions plus tard', ]
            );
    }

    /**
     * @throws AccessException
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Semestre::class,
            'diplome' => null,
            'translation_domain' => 'form',
        ]);
    }
}
