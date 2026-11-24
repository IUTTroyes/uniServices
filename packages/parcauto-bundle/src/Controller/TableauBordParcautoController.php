<?php

declare(strict_types=1);

namespace ParcautoBundle\Controller;

use App\AnneeUniversitaire\ContexteAnnuel;
use ParcautoBundle\Security\ParcautoVoter;
use ParcautoBundle\Service\SaisieParcauto;
use ParcautoBundle\Service\TableauBordParcauto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[ContexteAnnuel(ContexteAnnuel::INDEPENDANT, 'Pilotage du parc par période civile.')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[IsGranted(ParcautoVoter::GESTION)]
final class TableauBordParcautoController extends AbstractController
{
    #[Route('/api/parcauto/tableau-bord', name: 'parcauto_tableau_bord', methods: ['GET'])]
    #[Route('/api/parcauto/export', name: 'parcauto_export', methods: ['GET'])]
    public function __invoke(Request $request, TableauBordParcauto $tableau): Response
    {
        $saisie = new SaisieParcauto($request->query->all());
        try {
            $debut = $saisie->date('debut');
            $fin = $saisie->date('fin');
            if ($fin <= $debut || $fin > $debut->modify('+2 years')) {
                throw new \InvalidArgumentException('Sélectionnez une période de deux ans maximum.');
            }
        } catch (\InvalidArgumentException $erreur) {
            return $this->json(['message' => $erreur->getMessage()], 422);
        }

        $donnees = $tableau->calculer($debut, $fin);
        if ($request->attributes->get('_route') !== 'parcauto_export') {
            return $this->json($donnees);
        }

        return new StreamedResponse(static function () use ($donnees): void {
            $flux = fopen('php://output', 'wb');
            fwrite($flux, "\xEF\xBB\xBF");
            fputcsv($flux, [
                'Véhicule', 'Immatriculation', 'Site', 'Énergie', 'Disponible', 'Utilisation (%)', 'Missions',
                'Distance (km)', 'Carburant (L)', 'Consommation (L/100 km)', 'Dépenses (€)',
                'Coût total estimé (€)', 'Coût au km (€)', 'Réforme estimée', 'Alertes', 'Dernier départ',
            ], ';', '"', '');
            foreach ($donnees['vehicules'] as $ligne) {
                $valeurs = array_map(static fn (mixed $valeur) =>
                    is_string($valeur) && preg_match('/^[\s\p{C}]*[=+@\-]/u', $valeur) ? "'".$valeur : $valeur, $ligne);
                fputcsv($flux, $valeurs, ';', '"', '');
            }
            fclose($flux);
        }, headers: [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="parcauto.csv"',
        ]);
    }
}
