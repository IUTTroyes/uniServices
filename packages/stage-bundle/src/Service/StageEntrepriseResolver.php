<?php

namespace StageBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use StageBundle\Entity\Stages\StageContact;
use StageBundle\Entity\Stages\StageEntreprise;
use StageBundle\Entity\Stages\StageEtudiant;

/**
 * Évite les doublons créés par les formulaires, qui envoient l'entreprise et le tuteur entreprise en objets imbriqués
 * (API Platform crée alors de nouvelles lignes à chaque enregistrement) :
 *  - entreprise : rapprochée par SIRET, sinon l'entreprise déjà liée au dossier (si elle ne sert qu'à lui) est mise à
 *    jour en place ;
 *  - tuteur entreprise : le contact déjà lié au dossier est mis à jour en place.
 *
 * Limite connue : le signataire est porté par l'entreprise ; le modifier pour un dossier le modifie pour tous les
 * dossiers rattachés à cette entreprise.
 */
class StageEntrepriseResolver
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function resoudre(StageEtudiant $stage, ?StageEntreprise $entrepriseAvant, ?StageContact $tuteurAvant): void
    {
        $saisie = $stage->getEntreprise();
        if ($saisie !== null && $saisie->getId() === null) {
            $cible = $this->parSiret($saisie->getSiret());
            if ($cible === null && $entrepriseAvant?->getId() !== null && !$this->partagee($entrepriseAvant, $stage)) {
                $cible = $entrepriseAvant;
            }
            if ($cible !== null) {
                $cible->copierDepuis($saisie);
                $stage->setEntreprise($cible);
            }
        }

        $tuteur = $stage->getTuteur();
        if ($tuteur !== null && $tuteur->getId() === null && $tuteurAvant?->getId() !== null && $tuteurAvant !== $tuteur) {
            $tuteurAvant->copierDepuis($tuteur);
            $stage->setTuteur($tuteurAvant);
        }
    }

    private function parSiret(?string $siret): ?StageEntreprise
    {
        $siret = StageEntreprise::normaliserSiret($siret);

        return $siret === null ? null : $this->em->getRepository(StageEntreprise::class)->findOneBy(['siret' => $siret], ['id' => 'ASC']);
    }

    /** L'entreprise est-elle aussi utilisée par un autre dossier ? */
    private function partagee(StageEntreprise $entreprise, StageEtudiant $stage): bool
    {
        $qb = $this->em->createQueryBuilder()
            ->select('COUNT(s.id)')
            ->from(StageEtudiant::class, 's')
            ->where('s.entreprise = :e')
            ->setParameter('e', $entreprise);
        if ($stage->getId() !== null) {
            $qb->andWhere('s.id <> :id')->setParameter('id', $stage->getId());
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
