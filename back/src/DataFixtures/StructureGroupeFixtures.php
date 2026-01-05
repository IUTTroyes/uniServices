<?php

namespace App\DataFixtures;

use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Etudiant\EtudiantScolariteSemestre;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureGroupe;
use App\Entity\Users\Etudiant;
use App\Enum\TypeGroupeEnum;
use App\Repository\Structure\StructureAnneeUniversitaireRepository;
use App\Repository\Structure\StructureDepartementRepository;
use App\Repository\Structure\StructureSemestreRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\UuidV4;

class StructureGroupeFixtures extends Fixture implements OrderedFixtureInterface
{
    /**
     * @inheritDoc
     */
    public function getOrder(): int
    {
        return 9;
    }

    public function __construct(
        private readonly StructureSemestreRepository $structureSemestreRepository,
        private readonly StructureAnneeUniversitaireRepository $structureAnneeUniversitaireRepository,
        private readonly StructureDepartementRepository $structureDepartementRepository,
    ){}

    public function load(ObjectManager $manager): void
    {
        $s1 = $this->structureSemestreRepository->findOneBy(['libelle' => 'S1']);
        $anneeU = $this->structureAnneeUniversitaireRepository->findOneBy(['libelle' => '2024/2025']);
        $departement = $this->structureDepartementRepository->findOneBy(['libelle' => 'MMI']);

        if ($s1 === null) {
            throw new \Exception('S1 semestre not found');
        }

        $groupe1 = new StructureGroupe();
        $groupe1->setLibelle('CM')
            ->setCodeApogee('MMICM')
            ->setType(TypeGroupeEnum::TYPE_GROUPE_CM)
            ->setOrdre(1)
            ;
        $groupe1->addSemestre($s1);
        $s1->addGroupe($groupe1);

        $groupe2 = new StructureGroupe();
        $groupe2->setLibelle('AB')
            ->setCodeApogee('MMITDAB')
            ->setType(TypeGroupeEnum::TYPE_GROUPE_TD)
            ->setOrdre(2)
            ->setParent($groupe1)
            ;

        $groupe2->addSemestre($s1);
        $s1->addGroupe($groupe2);

        $groupe3 = new StructureGroupe();
        $groupe3->setLibelle('CD')
            ->setCodeApogee('MMITDCD')
            ->setType(TypeGroupeEnum::TYPE_GROUPE_TD)
            ->setOrdre(3)
            ->setParent($groupe1)
            ;

        $groupe3->addSemestre($s1);
        $s1->addGroupe($groupe3);

        $groupe4 = new StructureGroupe();
        $groupe4->setLibelle('A')
            ->setCodeApogee('MMITPA')
            ->setType(TypeGroupeEnum::TYPE_GROUPE_TP)
            ->setOrdre(4)
            ->setParent($groupe2)
            ;

        $groupe4->addSemestre($s1);
        $s1->addGroupe($groupe4);

        $groupe5 = new StructureGroupe();
        $groupe5->setLibelle('B')
            ->setCodeApogee('MMITPB')
            ->setType(TypeGroupeEnum::TYPE_GROUPE_TP)
            ->setOrdre(5)
            ->setParent($groupe2)
            ;

        $groupe5->addSemestre($s1);
        $s1->addGroupe($groupe5);

        $groupe6 = new StructureGroupe();
        $groupe6->setLibelle('C')
            ->setCodeApogee('MMITPC')
            ->setType(TypeGroupeEnum::TYPE_GROUPE_TP)
            ->setOrdre(6)
            ->setParent($groupe2)
            ;

        $groupe6->addSemestre($s1);
        $s1->addGroupe($groupe6);

        $groupe7 = new StructureGroupe();
        $groupe7->setLibelle('D')
            ->setCodeApogee('MMITPD')
            ->setType(TypeGroupeEnum::TYPE_GROUPE_TP)
            ->setOrdre(7)
            ->setParent($groupe2)
            ;

        $groupe7->addSemestre($s1);
        $s1->addGroupe($groupe7);

        $groupe1->addEnfant($groupe2);
        $groupe1->addEnfant($groupe3);

        $groupe2->addEnfant($groupe4);
        $groupe2->addEnfant($groupe5);

        $groupe3->addEnfant($groupe6);
        $groupe3->addEnfant($groupe7);

        $manager->persist($groupe1);
        $manager->persist($groupe2);
        $manager->persist($groupe3);
        $manager->persist($groupe4);
        $manager->persist($groupe5);
        $manager->persist($groupe6);
        $manager->persist($groupe7);

        // boucle pour ajouter des étudiants



        for ($i = 1; $i <= 10; $i++) {
            $etudiant = new Etudiant();
            $etudiant->setNom('Etudiant ' . $i);
            $etudiant->setPrenom('Prenom ' . $i);
            $etudiant->setMailUniv('etudiant'.$i.'@mail.com');
            $etudiant->setUsername('etudiant'.$i);
            $etudiant->setPassword('test');
            $etudiant->setRoles(['ROLE_ETUDIANT']);
            $etudiant->setPhotoName('noimage.png');
            $etudiant->setApplications(['UniTranet']);
            $etudiant->setNumEtudiant('123456789'.$i);
            $etudiant->setNumIne('123456789'.$i);
            $etudiant->setAnneeBac(2020);
            $etudiant->setBoursier(false);
            $etudiant->setAnneeSortie(0);
            $etudiant->setDateNaissance(new \DateTime('2020-01-01'));
            $etudiant->setTel1('0612345678');
            $etudiant->setTel2('0612345678');
            $etudiant->addGroupe($groupe1);
            $etudiant->addGroupe($groupe2);
            if ($i % 2 === 0) {
                $etudiant->addGroupe($groupe4);
            } else {
                $etudiant->addGroupe($groupe5);
            }

            $scol = new EtudiantScolarite();
            $scol->setEtudiant($etudiant);
            $scol->setOrdre(1);
            $scol->setAnneeUniversitaire($anneeU);
            $scol->setUuid(UuidV4::v4());
            $scol->setActif(true);
            $scol->setDepartement($departement);
            $scol->addAnnee($s1->getAnnee());
            $scol->addScolariteSemestre($s1->getScolariteSemestre()[0]);
            $etudiant->addScolarite($scol);

            $scolS1 = new EtudiantScolariteSemestre();
            $scolS1->setScolarite($scol);
            $scolS1->setSemestre($s1);

            $manager->persist($etudiant);
            $manager->persist($scolS1);
            $manager->persist($scol);
        }



        $manager->flush();
    }
}
