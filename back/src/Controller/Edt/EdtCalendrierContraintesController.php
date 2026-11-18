<?php

declare(strict_types=1);

namespace App\Controller\Edt;

use App\Entity\Edt\EdtContraintesSemestre;
use App\Repository\Structure\StructureSemestreRepository;
use App\Service\Calendrier\CalendrierUniversitaireService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[\App\AnneeUniversitaire\ContexteAnnuel('ressource', 'Opération rattachée à une ressource identifiée et contrôlée par le métier.')]
final class EdtCalendrierContraintesController extends AbstractController
{
    #[Route('/api/edt-calendrier-contraintes', name: 'edt_calendrier_contraintes', methods: ['POST'])]
    public function __invoke(EntityManagerInterface $em, StructureSemestreRepository $semestres, CalendrierUniversitaireService $calendrier, Request $request): JsonResponse
    {
        $data = $request->toArray();
        $semestreId = $data['semestreId'] ?? null;
        if (!is_int($semestreId) || $semestreId < 1 || !is_string($data['dateLundi'] ?? null) || !is_string($data['contrainte'] ?? null)) {
            return $this->json(['message' => 'semestreId, dateLundi et contrainte sont requis.'], 400);
        }
        $semestre = $semestres->find($semestreId);
        if ($semestre === null) throw $this->createNotFoundException('Semestre introuvable.');
        $pn = $semestre->getAnnee()?->getPn();
        $departement = $pn?->getDiplome()?->getDepartement();
        $this->denyAccessUnlessGranted($departement === null ? 'SUPER_ADMIN' : 'ROLE_EDT', $departement);
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $data['dateLundi']);
        if ($date === false || $date->format('Y-m-d') !== $data['dateLundi'] || $date->format('N') !== '1') {
            return $this->json(['message' => 'Une date de lundi au format AAAA-MM-JJ est requise.'], 400);
        }
        $annee = $pn?->getAnneeUniversitaire();
        if ($annee === null) return $this->json(['message' => 'Semestre sans année universitaire.'], 422);
        try {
            if ($calendrier->getSemaineFormation($annee, $date) === null) {
                return $this->json(['message' => 'Semaine hors de l’année universitaire du semestre.'], 422);
            }
        } catch (\DomainException $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }
        $contraintes = $em->getRepository(EdtContraintesSemestre::class)->findOneBy(['semestre' => $semestre, 'anneeUniversitaire' => $annee]);
        $contraintes ??= (new EdtContraintesSemestre())->setSemestre($semestre)->setAnneeUniversitaire($annee);
        $valeurs = $contraintes->getContraintes();
        $valeurs[$data['dateLundi']] = ['type' => $data['contrainte']];
        $contraintes->setContraintes($valeurs);
        $em->persist($contraintes);
        $em->flush();
        return $this->json(['message' => 'Contrainte enregistrée.', 'contraintes' => $valeurs]);
    }
}
