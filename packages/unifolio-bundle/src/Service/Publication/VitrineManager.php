<?php

namespace UnifolioBundle\Service\Publication;

use App\Entity\Users\Etudiant;
use Doctrine\ORM\EntityManagerInterface;
use UnifolioBundle\Entity\Portfolio;
use UnifolioBundle\Enum\TypePortfolioEnum;
use UnifolioBundle\Repository\PortfolioRepository;
use UnifolioBundle\Repository\TraceRepository;
use UnifolioBundle\Service\Portfolio\BlocValidator;
use UnifolioBundle\Service\Portfolio\ContexteEtudiant;
use UnifolioBundle\Service\Portfolio\ModeleParDefaut;
use UnifolioBundle\Service\Trace\UnifolioValidationException;

/** Portfolio vitrine (un par étudiant, libre et durable) et passerelle « Copier dans ma vitrine ». */
class VitrineManager
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PortfolioRepository $portfolios,
        private readonly TraceRepository $traces,
        private readonly BlocValidator $validateur,
    ) {
    }

    public function trouver(Etudiant $etudiant): ?Portfolio
    {
        return $this->portfolios->findOneBy(['etudiant' => $etudiant, 'type' => TypePortfolioEnum::VITRINE]);
    }

    public function creer(Etudiant $etudiant, ?string $titre = null): Portfolio
    {
        if ($this->trouver($etudiant) !== null) {
            throw UnifolioValidationException::un('Vous avez déjà un portfolio vitrine.');
        }
        $titre = trim((string) $titre) !== '' ? trim((string) $titre) : trim($etudiant->getPrenom().' '.$etudiant->getNom());
        $portfolio = new Portfolio($etudiant, TypePortfolioEnum::VITRINE, $titre);

        $definitions = [
            ['Présentation', [
                ModeleParDefaut::bloc('heading', ['niveau' => 1, 'texte' => $titre]),
                ModeleParDefaut::bloc('paragraph', ['texte' => 'Présentez votre parcours, vos centres d\'intérêt et ce que vous recherchez.']),
            ]],
            ['Projets', [
                ModeleParDefaut::bloc('heading', ['niveau' => 2, 'texte' => 'Mes projets']),
                ModeleParDefaut::bloc('traces', ['maximum' => 6, 'affichage' => 'cartes']),
            ]],
            ['CV', [ModeleParDefaut::bloc('cv', [])]],
        ];
        foreach ($definitions as [$nom, $blocs]) {
            $portfolio->ajouterPage($nom)->initialiser($this->valider($etudiant, $blocs), []);
        }
        $this->em->persist($portfolio);
        $this->em->flush();

        return $portfolio;
    }

    /**
     * Copie des pages d'un portfolio (sans lien vivant) vers la vitrine. Les pages copiées ne sont jamais imposées.
     *
     * @param list<string> $uuidsPages pages à copier ; vide = toutes
     *
     * @return int nombre de pages copiées
     */
    public function copier(Portfolio $source, Portfolio $vitrine, array $uuidsPages): int
    {
        if ($vitrine->getType() !== TypePortfolioEnum::VITRINE || $source->getEtudiant()->getId() !== $vitrine->getEtudiant()->getId()) {
            throw UnifolioValidationException::un('La copie se fait vers votre portfolio vitrine.');
        }
        $uuids = array_map('strtolower', $uuidsPages);
        $nb = 0;
        try {
            foreach ($source->getPages() as $page) {
                if ($uuids !== [] && !\in_array($page->getUuidString(), $uuids, true)) {
                    continue;
                }
                $copie = $vitrine->ajouterPage($page->getTitre());
                $copie->initialiser($this->valider($vitrine->getEtudiant(), $page->getBlocs()), []);
                ++$nb;
            }
        } catch (\DomainException $e) {
            throw UnifolioValidationException::un($e->getMessage());
        }
        if ($nb === 0) {
            throw UnifolioValidationException::un('Aucune page à copier.');
        }
        $this->em->flush();

        return $nb;
    }

    /** @param list<array<string, mixed>> $blocs */
    private function valider(Etudiant $etudiant, array $blocs): array
    {
        return $this->validateur->valider($blocs, new ContexteEtudiant($etudiant, $this->traces));
    }
}
