<?php

namespace StageBundle\Controller;

use App\Entity\Etablissement;
use App\Service\Pdf\PdfExportOptions;
use App\Service\Pdf\PdfExportService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use App\Repository\EtablissementRepository;
use StageBundle\Adapter\StageConventionAdapter;
use StageBundle\Entity\Stages\StageEtudiant;
use StageBundle\Entity\Stages\StageAvenant;
use StageBundle\Entity\Stages\StageConventionTemplate;
use StageBundle\Security\StageVoter;
use StageBundle\Service\Notification\StageNotifier;
use StageBundle\Services\GotenbergService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

//todo: a déplacer dans le bundle stage
class StagePdfController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly GotenbergService $gotenbergService,
        private readonly PdfExportService $pdfExportService,
        private readonly StageNotifier $notifier,
        private readonly LoggerInterface $logger,
        private readonly EtablissementRepository $etablissements,
    ) {
    }

    #[Route('/api/stage_etudiants/{id}/pdf', name: 'api_stage_etudiants_pdf', methods: ['GET'])]
    public function getStagePdf(int $id): Response
    {
        $stage = $this->em->getRepository(StageEtudiant::class)->find($id);
        if (!$stage) {
            return new Response('Stage étudiant non trouvé', Response::HTTP_NOT_FOUND);
        }
        $this->denyAccessUnlessGranted(StageVoter::STAGE_ETUDIANT_VIEW, $stage);

        // Get main establishment
        $etab = $this->etablissements->findPrincipal();

        $periode = $stage->getStagePeriode();
        $semestre = $periode?->getSemestreProgramme();
        $annee = $semestre?->getAnnee();
        $diplome = $annee?->getDiplome();
        $dept = $diplome?->getDepartement() ?? $periode?->getDepartement();

        // Année universitaire
        $anneeUnivStr = $periode && $periode->getAnneeUniversitaire()
            ? $periode->getAnneeUniversitaire()->getLibelle()
            : (date('Y') . '/' . (date('Y') + 1));

        // Coordonnées et signataire : table établissement (configuration de l'établissement principal).
        $universite = $etab?->getUniversite();
        $deptTel = $dept?->getTelContact() ?? $etab?->getTelephone() ?? '';

        $userData = [
            'displayAnneeUniversitaire' => $anneeUnivStr,
            // Établissement = la composante (l'IUT) ; l'université de rattachement signe la convention.
            'etablissement' => [
                'libelle' => $etab?->getLibelle() ?? '',
                'adresse' => $etab?->getAdresse()?->getInlineAdresse() ?? '',
                'telephone' => $etab?->getTelephone() ?? '',
                'directeur' => $etab?->getDirecteurNom() ?? '',
                'signataireNom' => $etab?->getSignataireNom() ?? '',
                'signataireQualite' => $etab?->getSignataireQualite() ?? '',
            ],
            'universite' => [
                'libelle' => $universite?->getLibelle() ?? $etab?->getLibelle() ?? '',
                'adresse' => $universite?->getAdresse()?->getInlineAdresse() ?? '',
                'telephone' => $universite?->getTelephone() ?? '',
                'president' => $universite?->getPresident() ?? '',
            ],
            'departement' => [
                'telcontact' => $deptTel,
                'ufr' => [
                    'sitePrincipal' => [
                        'adresse' => $etab?->getAdresse(),
                    ],
                    'responsable' => [
                        'display' => $etab?->getSignataireNom() ?? '',
                    ],
                ],
            ],
        ];

        $adaptedStage = new StageConventionAdapter($stage, $semestre, $diplome);

        $nomFichier = 'convention_stage_' . ($stage->getEtudiant()?->getNom() ? mb_strtolower($stage->getEtudiant()->getNom()) : 'etudiant') . '.pdf';

        $options = PdfExportOptions::create()
            ->setWithHeader(false)
            ->setWithFooter(false)
            ->setMargins(0.8, 0.8, 0.8, 0.8)
            ->setInline(true)
            ->setTitle('Convention de stage - ' . ($stage->getEtudiant()?->getDisplay() ?? 'Étudiant'));

        // 1. Modèle en base (édité par le super administrateur), s'il existe et n'est pas vide.
        $modele = $this->em->getRepository(StageConventionTemplate::class)->findOneBy(['code' => StageConventionTemplate::CODE_CONVENTION]);
        if ($modele !== null && trim((string) $modele->getTexte()) !== '') {
            try {
                $html = $this->renderConventionHtml($this->replacePlaceholders((string) $modele->getTexte(), $stage), $stage);

                return new Response($this->gotenbergService->convertHtmlToPdf($html), Response::HTTP_OK, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="'.$nomFichier.'"',
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Convention : échec du rendu du modèle en base, repli sur le gabarit intégré.', ['stage' => $stage->getId(), 'erreur' => $e->getMessage()]);
            }
        }

        // 2. Secours : gabarit Twig intégré au package.
        try {
            return $this->pdfExportService->generateStreamedResponse(
                '@Stage/pdf/conventionStagePDF.html.twig',
                [
                    'proposition' => $adaptedStage,
                    'user_data' => $userData,
                ],
                $nomFichier,
                $options
            );
        } catch (\Throwable $e) {
            $this->logger->error('Convention : échec de la génération PDF.', ['stage' => $stage->getId(), 'erreur' => $e->getMessage()]);

            return new Response('La convention n\'a pas pu être générée. Réessayez plus tard ou contactez le service des stages.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ---------- Éditeur du modèle de convention (super administrateur) ----------

    /** Modèle de convention livré avec le package, point de départ de l'édition. */
    #[Route('/api/stage/modele-convention/defaut', name: 'api_stage_modele_convention_defaut', methods: ['GET'])]
    public function modeleConventionDefaut(): JsonResponse
    {
        $this->denyAccessUnlessGranted('SUPER_ADMIN');

        return $this->json([
            'code' => StageConventionTemplate::CODE_CONVENTION,
            'libelle' => 'Convention de stage',
            'texte' => (string) file_get_contents(\dirname(__DIR__).'/Resources/templates/default_convention.html'),
        ]);
    }

    /** Balises disponibles dans les modèles. */
    #[Route('/api/stage/modele-convention/balises', name: 'api_stage_modele_convention_balises', methods: ['GET'])]
    public function balisesModele(): JsonResponse
    {
        $this->denyAccessUnlessGranted('SUPER_ADMIN');

        return $this->json(array_keys($this->valeursBalises(new StageEtudiant(), new StageAvenant())));
    }

    /** Aperçu HTML d'un modèle avec les données d'un vrai dossier (le dossier indiqué, sinon le plus récent). */
    #[Route('/api/stage/modele-convention/apercu', name: 'api_stage_modele_convention_apercu', methods: ['POST'])]
    public function apercuModele(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('SUPER_ADMIN');
        $data = json_decode($request->getContent(), true) ?? [];
        $texte = (string) ($data['texte'] ?? '');
        $repo = $this->em->getRepository(StageEtudiant::class);
        $stage = isset($data['stageEtudiantId']) && is_numeric($data['stageEtudiantId'])
            ? $repo->find((int) $data['stageEtudiantId'])
            : $repo->createQueryBuilder('s')->where('s.etudiant IS NOT NULL')->orderBy('s.id', 'DESC')->setMaxResults(1)->getQuery()->getOneOrNullResult();
        if (!$stage instanceof StageEtudiant) {
            return $this->json(['error' => 'Aucun dossier de stage pour construire l\'aperçu.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'html' => $this->rendreModeleConvention($texte, $stage),
            'dossier' => $stage->getEtudiant()?->getDisplay() ?? 'dossier #'.$stage->getId(),
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
        $this->denyAccessUnlessGranted(StageVoter::STAGE_ETUDIANT_VIEW, $stage);

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

        $stage = $avenant->getStageEtudiant();
        if (!$stage) {
            return new Response('Stage associé à l\'avenant non trouvé', Response::HTTP_NOT_FOUND);
        }
        $this->denyAccessUnlessGranted(StageVoter::STAGE_ETUDIANT_MANAGE, $stage);

        // Idempotent : un avenant déjà validé n'est ni ré-appliqué ni re-notifié.
        if ($avenant->isValide()) {
            return $this->json(['success' => true, 'dejaValide' => true]);
        }

        $avenant->setValide(true);
        $avenant->setDateValidation(new \DateTime());

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
            // Le contact de l'avenant reste attaché à l'avenant (historique) : on recopie ses informations dans le
            // tuteur du dossier, ou dans une copie, pour ne jamais partager un contact entre deux lignes
            // (contrainte d'unicité du tuteur, suppression en cascade avec le dossier).
            if ($stage->getTuteur() !== null) {
                $stage->getTuteur()->copierDepuis($avenant->getNewTuteur());
            } else {
                $stage->setTuteur($avenant->getNewTuteur()->dupliquer());
            }
        }

        $this->em->flush();

        $user = $this->getUser();
        $this->notifier->avenantValide($avenant, $user instanceof \App\Entity\Users\Personnel ? $user : null);

        return new Response(json_encode(['success' => true]), Response::HTTP_OK, [
            'Content-Type' => 'application/json'
        ]);
    }

    private function replacePlaceholders(string $templateText, StageEtudiant $stage, ?StageAvenant $avenant = null): string
    {
        $replaces = $this->valeursBalises($stage, $avenant);

        // Modèle HTML : les valeurs (saisies par l'étudiant, l'entreprise…) sont échappées, pour qu'aucune balise ne soit
        // interprétée lors du rendu (ni mise en page détournée, ni chargement de ressource externe par Gotenberg).
        // Modèle texte : c'est le texte complet qui est échappé ensuite (renderConventionHtml / renderAvenantHtml).
        if (self::estHtml($templateText)) {
            $replaces = array_map(static fn ($v) => nl2br(htmlspecialchars((string) $v, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8')), $replaces);
        }

        return strtr($templateText, array_map('strval', $replaces));
    }

    /**
     * Valeurs des balises « {objet.champ} » des modèles de convention et d'avenant.
     *
     * @return array<string, string|int|float|null>
     */
    public function valeursBalises(StageEtudiant $stage, ?StageAvenant $avenant = null): array
    {
        $etu = $stage->getEtudiant();
        $ent = $stage->getEntreprise();
        $tut = $stage->getTuteur();
        $tutUniv = $stage->getTuteurUniversitaire();
        $periode = $stage->getStagePeriode();

        // Get main establishment
        $etab = $this->etablissements->findPrincipal();

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
        $anneeUnivStr = $periode && $periode->getAnneeUniversitaire() ? $periode->getAnneeUniversitaire()->getLibelle() : (date('Y').'/'.((int) date('Y') + 1));

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
            '{etudiant.secu}' => $stage->getAssuranceCompagnie() ?: ($etu && method_exists($etu, 'getOpt') && !empty($etu->getOpt()['intitule_securite_sociale']) ? $etu->getOpt()['intitule_securite_sociale'] : ($etu && method_exists($etu, 'getIntituleSecuriteSociale') && $etu->getIntituleSecuriteSociale() ? $etu->getIntituleSecuriteSociale() : '')),
            '{etudiant.secu_adresse}' => $stage->getAssuranceNumero() ?: ($etu && method_exists($etu, 'getOpt') && !empty($etu->getOpt()['adresse_securite_sociale']) ? $etu->getOpt()['adresse_securite_sociale'] : ($etu && method_exists($etu, 'getAdresseSecuriteSociale') && $etu->getAdresseSecuriteSociale() ? $etu->getAdresseSecuriteSociale() : '')),

            '{entreprise.nom}' => $ent ? $ent->getRaisonSociale() : '',
            '{entreprise.adresse}' => $stage->getAdresseStage() ? (string) $stage->getAdresseStage() : ($ent && $ent->getAdresse() ? (string) $ent->getAdresse() : ''),
            '{entreprise.signataire}' => $ent && $ent->getResponsable() ? $ent->getResponsable()->getDisplay() : '',
            '{entreprise.signataire_fonction}' => $ent && $ent->getResponsable() ? $ent->getResponsable()->getFonction() : '',
            '{entreprise.telephone}' => $ent && $ent->getResponsable() ? ($ent->getResponsable()->getTelephone() ?? $ent->getResponsable()->getPortable() ?? '') : '',
            '{entreprise.email}' => $ent && $ent->getResponsable() ? $ent->getResponsable()->getEmail() : '',
            '{stage.service}' => $stage->getServiceStageEntreprise() ?? '',
            '{stage.adresse_different}' => $stage->getAdresseStage() ? (string) $stage->getAdresseStage() : '',
            '{stage.interruptions}' => $stage->getPeriodesInterruptions() ?? '',

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

            '{universite.nom}' => $etab?->getUniversite()->getLibelle() ?? $etab?->getLibelle() ?? '',
            '{universite.adresse}' => $etab?->getUniversite()->getAdresse()?->getInlineAdresse() ?? '',
            '{universite.telephone}' => $etab?->getUniversite()->getTelephone() ?? '',
            '{universite.president}' => $etab?->getUniversite()->getPresident() ?? '',
            '{etablissement.nom}' => $etab?->getLibelle() ?? '',
            '{etablissement.directeur}' => $etab?->getDirecteurNom() ?? '',
            '{etablissement.adresse}' => $etab?->getAdresse()?->getInlineAdresse() ?? '',
            '{etablissement.telephone}' => $etab?->getTelephone() ?? '',
            '{etablissement.signataire}' => $etab?->getSignataireNom() ?? '',
            '{etablissement.signataire_qualite}' => $etab?->getSignataireQualite() ?? '',
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

        return $replaces;
    }

    /** Rendu HTML d'un modèle de convention pour un dossier (aperçu de l'éditeur). */
    public function rendreModeleConvention(string $texte, StageEtudiant $stage): string
    {
        return $this->renderConventionHtml($this->replacePlaceholders($texte, $stage), $stage);
    }

    /** @return array{string, string} noms (échappés) de l'établissement et de son université */
    private function nomsEtablissement(): array
    {
        $etab = $this->etablissements->findPrincipal();

        return [
            htmlspecialchars($etab?->getLibelle() ?? ''),
            htmlspecialchars($etab?->getUniversite()->getLibelle() ?? ''),
        ];
    }

    /** Modèle HTML complet (document) plutôt que texte brut. */
    public static function estHtml(string $texte): bool
    {
        return stripos($texte, '<html') !== false;
    }

    private function renderConventionHtml(string $text, StageEtudiant $stage): string
    {
        if (self::estHtml($text)) {
            return $text;
        }

        $paragraphs = nl2br(htmlspecialchars($text));
        [$nomEtab, $nomUniv] = $this->nomsEtablissement();

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
                <div class="logo">{$nomEtab}</div>
                <div class="subheader">{$nomUniv}</div>
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
                    <strong>Pour l'établissement : {$nomEtab}</strong><br>
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
        [$nomEtab] = $this->nomsEtablissement();

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
                <div class="logo">{$nomEtab}</div>
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
                    <strong>Pour l'établissement : {$nomEtab}</strong><br>
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
