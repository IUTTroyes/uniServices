<?php

namespace FinanceBundle\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;
use FinanceBundle\Entity\FinanceBonCommande;
use FinanceBundle\Entity\FinanceFournisseur;
use FinanceBundle\Entity\FinanceTauxTva;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Référentiels du service financier.
 *  - Fournisseurs : pas de doublon (même numéro SIFAC, ou même raison sociale sans tenir compte de la casse ni des
 *    espaces) ; un fournisseur utilisé par une demande ne se supprime pas.
 *  - Taux de TVA : un seul taux proposé par défaut.
 *
 * @implements ProcessorInterface<mixed, mixed>
 */
final class FinanceReferentielProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persist,
        #[Autowire(service: 'api_platform.doctrine.orm.state.remove_processor')]
        private readonly ProcessorInterface $remove,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($operation instanceof DeleteOperationInterface) {
            if ($data instanceof FinanceFournisseur && $this->em->getRepository(FinanceBonCommande::class)->count(['fournisseur' => $data]) > 0) {
                throw new ConflictHttpException('Ce fournisseur est utilisé par des demandes d\'achat : il ne peut pas être supprimé.');
            }

            return $this->remove->process($data, $operation, $uriVariables, $context);
        }

        if ($data instanceof FinanceFournisseur) {
            $this->verifierDoublon($data);
        }
        if ($data instanceof FinanceTauxTva && $data->isParDefaut()) {
            foreach ($this->em->getRepository(FinanceTauxTva::class)->findBy(['parDefaut' => true]) as $autre) {
                if ($autre !== $data) {
                    $autre->setParDefaut(false);
                }
            }
        }

        return $this->persist->process($data, $operation, $uriVariables, $context);
    }

    private function verifierDoublon(FinanceFournisseur $fournisseur): void
    {
        $qb = $this->em->getRepository(FinanceFournisseur::class)->createQueryBuilder('f')
            ->select('f.id, f.nomFournisseur, f.numeroFournisseur')
            ->where('LOWER(TRIM(f.nomFournisseur)) = :nom')
            ->setParameter('nom', mb_strtolower(trim((string) $fournisseur->getNomFournisseur())));
        if ($fournisseur->getNumeroFournisseur() !== null && trim($fournisseur->getNumeroFournisseur()) !== '') {
            $qb->orWhere('f.numeroFournisseur = :numero')->setParameter('numero', trim($fournisseur->getNumeroFournisseur()));
        }
        foreach ($qb->getQuery()->getArrayResult() as $existant) {
            if ($existant['id'] !== $fournisseur->getId()) {
                throw new UnprocessableEntityHttpException(sprintf(
                    'Ce fournisseur existe déjà au référentiel : %s%s.',
                    $existant['numeroFournisseur'] ? $existant['numeroFournisseur'].' - ' : '',
                    $existant['nomFournisseur']
                ));
            }
        }
    }
}
