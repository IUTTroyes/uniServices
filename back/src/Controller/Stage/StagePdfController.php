<?php

namespace App\Controller\Stage;

use App\Entity\Etablissement;
use Doctrine\ORM\EntityManagerInterface;
use StageBundle\Entity\Stages\StageEtudiant;
use StageBundle\Entity\Stages\StageAvenant;
use StageBundle\Entity\Stages\StageConventionTemplate;
use StageBundle\Services\GotenbergService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

//todo: a déplacer dans le bundle stage
class StagePdfController extends AbstractController
{
    private EntityManagerInterface $em;
    private GotenbergService $gotenbergService;

    public function __construct(EntityManagerInterface $em, GotenbergService $gotenbergService)
    {
        $this->em = $em;
        $this->gotenbergService = $gotenbergService;
    }

    #[Route('/api/stage_etudiants/{id}/pdf', name: 'api_stage_etudiants_pdf', methods: ['GET'])]
    public function getStagePdf(int $id): Response
    {
        $stage = $this->em->getRepository(StageEtudiant::class)->find($id);
        if (!$stage) {
            return new Response('Stage étudiant non trouvé', Response::HTTP_NOT_FOUND);
        }

        $template = $this->em->getRepository(StageConventionTemplate::class)->findOneBy(['code' => 'default_convention']);
        $templateText = $template ? $template->getTexte() : "CONVENTION DE STAGE\n(Modèle par défaut non configuré en base)";

        // Replace placeholders
        $text = $this->replacePlaceholders($templateText, $stage);

        // Convert to nice HTML page
        $html = $this->renderConventionHtml($text, $stage);

        // Call Gotenberg
        try {
            $pdfBytes = $this->gotenbergService->convertHtmlToPdf($html);
        } catch (\Exception $e) {
            return new Response('Erreur lors de la génération PDF : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new Response($pdfBytes, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="convention_stage_' . ($stage->getEtudiant()?->getNom() ?? 'etudiant') . '.pdf"',
        ]);
    }

    #[Route('/api/stage_avenants/{id}/pdf', name: 'api_stage_avenants_pdf', methods: ['GET'])]
    public function getAvenantPdf(int $id): Response
    {
        $avenant = $this->em->getRepository(StageAvenant::class)->find($id);
        if (!$avenant) {
            return new Response('Avenant non trouvé', Response::HTTP_NOT_FOUND);
        }

        $stage = $avenant->getStageEtudiant();
        if (!$stage) {
            return new Response('Stage associé à l\'avenant non trouvé', Response::HTTP_NOT_FOUND);
        }

        $template = $this->em->getRepository(StageConventionTemplate::class)->findOneBy(['code' => 'default_avenant']);
        $templateText = $template ? $template->getTexte() : "AVENANT À LA CONVENTION DE STAGE\n(Modèle par défaut non configuré en base)";

        // Replace placeholders
        $text = $this->replacePlaceholders($templateText, $stage, $avenant);

        // Convert to HTML
        $html = $this->renderAvenantHtml($text, $stage, $avenant);

        // Call Gotenberg
        try {
            $pdfBytes = $this->gotenbergService->convertHtmlToPdf($html);
        } catch (\Exception $e) {
            return new Response('Erreur lors de la génération PDF de l\'avenant : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new Response($pdfBytes, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="avenant_stage_' . ($stage->getEtudiant()?->getNom() ?? 'etudiant') . '.pdf"',
        ]);
    }

    #[Route('/api/stage_avenants/{id}/validate', name: 'api_stage_avenants_validate', methods: ['POST'])]
    public function validateAvenant(int $id): Response
    {
        $avenant = $this->em->getRepository(StageAvenant::class)->find($id);
        if (!$avenant) {
            return new Response('Avenant non trouvé', Response::HTTP_NOT_FOUND);
        }

        $avenant->setValide(true);
        $avenant->setDateValidation(new \DateTime());

        $stage = $avenant->getStageEtudiant();
        if ($stage) {
            if ($avenant->getNewDateDebut()) {
                $stage->setDateDebutStage($avenant->getNewDateDebut());
            }
            if ($avenant->getNewDateFin()) {
                $stage->setDateFinStage($avenant->getNewDateFin());
            }
            if (null !== $avenant->getNewDureeHebdomadaire()) {
                $stage->setDureeHebdomadaire($avenant->getNewDureeHebdomadaire());
            }
            if (null !== $avenant->getNewGratificationMontant()) {
                $stage->setGratificationMontant($avenant->getNewGratificationMontant());
            }
            if ($avenant->getNewTuteur()) {
                $stage->setTuteur($avenant->getNewTuteur());
            }
        }

        $this->em->flush();

        return new Response(json_encode(['success' => true]), Response::HTTP_OK, [
            'Content-Type' => 'application/json'
        ]);
    }

    private function replacePlaceholders(string $templateText, StageEtudiant $stage, ?StageAvenant $avenant = null): string
    {
        $etu = $stage->getEtudiant();
        $ent = $stage->getEntreprise();
        $tut = $stage->getTuteur();
        $tutUniv = $stage->getTuteurUniversitaire();
        $periode = $stage->getStagePeriode();

        // Get main establishment
        $etab = $this->em->getRepository(Etablissement::class)->findOneBy(['isMain' => true]);
        if (!$etab) {
            $etab = $this->em->getRepository(Etablissement::class)->findOneBy([]);
        }

        // Safe format helper for dates
        $fmtDate = function(?\DateTimeInterface $d) {
            return $d ? $d->format('d/m/Y') : '';
        };

        // Telephones
        $tels = [];
        if ($etu) {
            if ($etu->getTel1()) $tels[] = $etu->getTel1();
            if ($etu->getTel2()) $tels[] = $etu->getTel2();
        }
        $telephones = implode(' / ', $tels);

        // Semestre & Diplome libelle
        $formationLibelle = 'BUT';
        $volumeHoraire = '600';
        $sem = $periode ? $periode->getSemestreProgramme() : null;
        if ($sem) {
            $formationLibelle = $sem->getLibelle();
            if ($sem->getAnnee() && $sem->getAnnee()->getDiplome()) {
                $diplome = $sem->getAnnee()->getDiplome();
                $formationLibelle = ($diplome->getTypeDiplome() ? $diplome->getTypeDiplome()->getLibelle() : '') . ' ' . $diplome->getLibelle();
                $volumeHoraire = (string)($diplome->getVolumeHoraire() ?? '600');
            }
        }

        // Departement details
        $deptTel = '';
        $deptMail = '';
        $deptAdresse = '';
        $dept = ($sem && $sem->getAnnee()) ? $sem->getAnnee()->getDepartement() : null;
        if ($dept) {
            $deptTel = $dept->getTelContact() ?? '';
            if ($sem && $sem->getAnnee() && $sem->getAnnee()->getDiplome() && $sem->getAnnee()->getDiplome()->getAssistantDiplome()) {
                $deptMail = $sem->getAnnee()->getDiplome()->getAssistantDiplome()->getMailUniv() ?? '';
            }
        }
        if ($etab && $etab->getAdresse()) {
            $deptAdresse = (string) $etab->getAdresse();
        }

        // Annee universitaire display
        $anneeUnivStr = $periode && $periode->getAnneeUniversitaire() ? $periode->getAnneeUniversitaire()->getLibelle() : '2025/2026';

        // Calcule semaines
        $nbSemaines = 0;
        if ($stage->getDureeJoursStage()) {
            $nbSemaines = ceil($stage->getDureeJoursStage() / 5);
        }

        $replaces = [
            '{annee_universitaire}' => $anneeUnivStr,
            '{etudiant.nom}' => $etu ? mb_strtoupper($etu->getNom()) : '',
            '{etudiant.prenom}' => $etu ? $etu->getPrenom() : '',
            '{etudiant.sexe}' => $etu && method_exists($etu, 'getCivilite') && $etu->getCivilite() === 'Mme' ? 'Femme' : 'Homme',
            '{etudiant.date_naissance}' => $etu && $etu->getDateNaissance() ? $fmtDate($etu->getDateNaissance()) : '',
            '{etudiant.adresse}' => $etu && method_exists($etu, 'getAdresseEtudiante') && $etu->getAdresseEtudiante() ? (string) $etu->getAdresseEtudiante() : '',
            '{etudiant.telephones}' => $telephones,
            '{etudiant.email}' => $etu ? $etu->getMailUniv() : '',
            '{etudiant.formation}' => $formationLibelle,
            '{formation.volume_horaire}' => $volumeHoraire,
            '{etudiant.secu}' => $etu && method_exists($etu, 'getIntituleSecuriteSociale') && $etu->getIntituleSecuriteSociale() ? $etu->getIntituleSecuriteSociale() : 'CPAM de l\'Aube',
            '{etudiant.secu_adresse}' => $etu && method_exists($etu, 'getAdresseSecuriteSociale') && $etu->getAdresseSecuriteSociale() ? $etu->getAdresseSecuriteSociale() : '',

            '{entreprise.nom}' => $ent ? $ent->getRaisonSociale() : '',
            '{entreprise.adresse}' => $stage->getAdresseStage() ? (string) $stage->getAdresseStage() : ($ent && $ent->getAdresse() ? (string) $ent->getAdresse() : ''),
            '{entreprise.signataire}' => $ent && $ent->getResponsable() ? $ent->getResponsable()->getDisplay() : '',
            '{entreprise.signataire_fonction}' => $ent && $ent->getResponsable() ? $ent->getResponsable()->getFonction() : '',
            '{entreprise.telephone}' => $ent && $ent->getResponsable() ? ($ent->getResponsable()->getTelephone() ?? $ent->getResponsable()->getPortable() ?? '') : '',
            '{entreprise.email}' => $ent && $ent->getResponsable() ? $ent->getResponsable()->getEmail() : '',
            '{stage.service}' => $stage->getServiceStageEntreprise() ?? 'Service Technique',

            '{stage.sujet}' => $stage->getSujetStage() ?? '',
            '{stage.date_debut}' => $fmtDate($stage->getDateDebutStage()),
            '{stage.date_fin}' => $fmtDate($stage->getDateFinStage()),
            '{stage.semaines}' => (string)$nbSemaines,
            '{stage.jours}' => (string)($stage->getDureeJoursStage() ?? '0'),
            '{stage.commentaire_heures}' => $stage->getCommentaireDureeHebdomadaire() ?? '',
            '{stage.activites}' => $stage->getActivites() ?? '',
            '{stage.competences}' => $periode ? ($periode->getCompetencesVisees() ?? '') : '',
            '{stage.heures_hebdo}' => (string)($stage->getDureeHebdomadaire() ?? '35'),
            '{stage.amenagements}' => $stage->getAmenagementStage() ?? 'Aucun',
            '{stage.modalites_encadrement}' => $periode ? ($periode->getModalitesEncadrement() ?? '') : '',
            '{stage.gratification}' => $stage->getGratificationMontant() ? number_format($stage->getGratificationMontant(), 2, ',', ' ') : '0,00',
            '{stage.gratification_periode}' => $stage->getGratificationPeriode() === 'M' ? 'mois' : ($stage->getGratificationPeriode() === 'H' ? 'heure' : 'jour'),
            '{stage.avantages}' => $stage->getAvantages() ?? 'Aucun',
            '{stage.documents_a_rendre}' => $periode ? ($periode->getDocumentsRendre() ?? '') : '',
            '{stage.ects}' => $periode ? ($periode->getNbEcts() === 0 ? 'Le stage ne donne pas lieu à des ECTS mais une note comptant dans différentes UE' : (string)$periode->getNbEcts()) : '',

            '{tuteur.nom}' => $tutUniv ? $tutUniv->getDisplay() : ($periode && $periode->getResponsablePrincipal() ? $periode->getResponsablePrincipal()->getDisplay() : 'Non attribué'),
            '{tuteur.telephone}' => $tutUniv && method_exists($tutUniv, 'getTelBureau') ? $tutUniv->getTelBureau() : '',
            '{tuteur.email}' => $tutUniv ? $tutUniv->getMailUniv() : '',

            '{tuteur_entreprise.nom}' => $tut ? $tut->getDisplay() : ($ent && $ent->getResponsable() ? $ent->getResponsable()->getDisplay() : ''),
            '{tuteur_entreprise.fonction}' => $tut ? $tut->getFonction() : ($ent && $ent->getResponsable() ? $ent->getResponsable()->getFonction() : ''),
            '{tuteur_entreprise.telephone}' => $tut ? ($tut->getTelephone() ?? $tut->getPortable() ?? '') : ($ent && $ent->getResponsable() ? ($ent->getResponsable()->getTelephone() ?? $ent->getResponsable()->getPortable() ?? '') : ''),
            '{tuteur_entreprise.email}' => $tut ? $tut->getEmail() : ($ent && $ent->getResponsable() ? $ent->getResponsable()->getEmail() : ''),

            '{etablissement.signataire}' => 'Martial Martin',
            '{date_creation}' => date('d/m/Y'),
            '{dept.tel}' => $deptTel,
            '{dept.mail}' => $deptMail,
            '{dept.adresse}' => $deptAdresse,
            '{etudiant.parcours}' => ($stage->getStagePeriode() && $stage->getStagePeriode()->getSemestreProgramme()) ? $stage->getStagePeriode()->getSemestreProgramme()->getLibelle() : 'BUT',
        ];

        if ($avenant) {
            $replaces['{avenant.texte}'] = $avenant->getTexte() ?? '';
            $replaces['{avenant.date_creation}'] = $avenant->getDateCreation() ? $avenant->getDateCreation()->format('d/m/Y') : '';
        }

        return strtr($templateText, $replaces);
    }

    private function renderConventionHtml(string $text, StageEtudiant $stage): string
    {
        if (str_contains($text, '<html') || str_contains($text, '<HTML')) {
            return $text;
        }

        $paragraphs = nl2br(htmlspecialchars($text));
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #333;
            padding: 10px;
        }
        @page {
            size: A4;
            margin: 20mm;
        }
        .header {
            border-bottom: 2px solid #b3002d;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 18pt;
            font-weight: bold;
            color: #b3002d;
        }
        .subheader {
            font-size: 8pt;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .content {
            margin-bottom: 40px;
            text-align: justify;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .signatures {
            margin-top: 50px;
            width: 100%;
            border-collapse: collapse;
        }
        .signatures td {
            width: 33.33%;
            vertical-align: top;
            text-align: center;
            font-size: 9pt;
            padding-top: 15px;
        }
        .sig-box {
            border-top: 1px dashed #999;
            margin: 0 10px;
            padding-top: 10px;
            height: 100px;
        }
    </style>
</head>
<body>
    <table style="width: 100%; margin-bottom: 20px;">
        <tr>
            <td>
                <div class="logo">IUT de Troyes</div>
                <div class="subheader">Université de Reims Champagne-Ardenne</div>
            </td>
            <td style="text-align: right; vertical-align: top; font-size: 9pt; color: #999; font-family: monospace;">
                Réf: CONV-STAGE-{$stage->getId()}
            </td>
        </tr>
    </table>
    
    <div style="border-bottom: 2px solid #b3002d; margin-bottom: 30px;"></div>

    <div class="content">{$text}</div>

    <table class="signatures">
        <tr>
            <td>
                <div class="sig-box">
                    <strong>L'Étudiant(e)</strong><br>
                    <span style="font-size: 8pt; color: #888;">Lu et approuvé</span>
                </div>
            </td>
            <td>
                <div class="sig-box">
                    <strong>Pour l'Entreprise</strong><br>
                    <span style="font-size: 8pt; color: #888;">Le représentant légal</span>
                </div>
            </td>
            <td>
                <div class="sig-box">
                    <strong>Pour l'IUT de Troyes</strong><br>
                    <span style="font-size: 8pt; color: #888;">Le Directeur</span>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    private function renderAvenantHtml(string $text, StageEtudiant $stage, StageAvenant $avenant): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #333;
            padding: 10px;
        }
        @page {
            size: A4;
            margin: 20mm;
        }
        .header {
            border-bottom: 2px solid #3c8dbc;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 18pt;
            font-weight: bold;
            color: #3c8dbc;
        }
        .subheader {
            font-size: 8pt;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .content {
            margin-bottom: 40px;
            text-align: justify;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .signatures {
            margin-top: 50px;
            width: 100%;
            border-collapse: collapse;
        }
        .signatures td {
            width: 33.33%;
            vertical-align: top;
            text-align: center;
            font-size: 9pt;
            padding-top: 15px;
        }
        .sig-box {
            border-top: 1px dashed #999;
            margin: 0 10px;
            padding-top: 10px;
            height: 100px;
        }
    </style>
</head>
<body>
    <table style="width: 100%; margin-bottom: 20px;">
        <tr>
            <td>
                <div class="logo">IUT de Troyes</div>
                <div class="subheader">Avenant à la convention CONV-STAGE-{$stage->getId()}</div>
            </td>
            <td style="text-align: right; vertical-align: top; font-size: 9pt; color: #999; font-family: monospace;">
                Réf: AVENANT-{$avenant->getId()}
            </td>
        </tr>
    </table>
    
    <div style="border-bottom: 2px solid #3c8dbc; margin-bottom: 30px;"></div>

    <div class="content">{$text}</div>

    <table class="signatures">
        <tr>
            <td>
                <div class="sig-box">
                    <strong>L'Étudiant(e)</strong><br>
                    <span style="font-size: 8pt; color: #888;">Lu et approuvé</span>
                </div>
            </td>
            <td>
                <div class="sig-box">
                    <strong>Pour l'Entreprise</strong><br>
                    <span style="font-size: 8pt; color: #888;">Le représentant légal</span>
                </div>
            </td>
            <td>
                <div class="sig-box">
                    <strong>Pour l'IUT de Troyes</strong><br>
                    <span style="font-size: 8pt; color: #888;">Le Directeur</span>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
}
