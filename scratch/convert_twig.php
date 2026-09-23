<?php

$twig = file_get_contents('/Users/davidannebicque/Sites/IUTTroyes/uniServices/conventionStagePDF.html.twig');

// Extract the content block between {% block content %} and {% endblock %} at the end
$startPos = strpos($twig, '{% block content %}');
$endPos = strrpos($twig, '{% endblock %}');

if ($startPos !== false && $endPos !== false) {
    $content = substr($twig, $startPos + strlen('{% block content %}'), $endPos - ($startPos + strlen('{% block content %}')));
} else {
    $content = $twig;
}

// Clean up tuteur blocks before regex replacements
$tuteurUnivBlockPattern = '/<div class="center"><strong><u>TUTEUR DE L’ETABLISSEMENT D’ENSEIGNEMENT SUPERIEUR<\/u><\/strong><\/div>.*?<\/td>/s';
$tuteurUnivBlockReplacement = '<div class="center"><strong><u>TUTEUR DE L’ETABLISSEMENT D’ENSEIGNEMENT SUPERIEUR</u></strong></div>
                <br>
                <p>Nom et prénom du tuteur universitaire : {tuteur.nom}</p>
                <p>Tél. : {tuteur.telephone} </p>
                <p>Email : {tuteur.email}</p>
            </td>';
$content = preg_replace($tuteurUnivBlockPattern, $tuteurUnivBlockReplacement, $content);

$tuteurEntBlockPattern = '/<div class="center"><strong><u>TUTEUR DE L’ORGANISME D’ACCUEIL<\/u><\/strong><\/div>.*?<\/td>/s';
$tuteurEntBlockReplacement = '<div class="center"><strong><u>TUTEUR DE L’ORGANISME D’ACCUEIL</u></strong></div>
                <br>
                <p>Nom et prénom : {tuteur_entreprise.nom}</p>
                <p>Fonction : {tuteur_entreprise.fonction}</p>
                <p>Tél. : {tuteur_entreprise.telephone}</p>
                <p>Email : {tuteur_entreprise.email}</p>
            </td>';
$content = preg_replace($tuteurEntBlockPattern, $tuteurEntBlockReplacement, $content);

// Replacements
$replacements = [
    // Complex expressions first
    '/\{\{\s*user_data\.displayAnneeUniversitaire\s*\}\}/' => '{annee_universitaire}',
    '/\{\{\s*proposition\.etudiant\.semestre\.annee\.diplome\.typediplome\s*\.sigle\s*\}\}\s*\{\{\s*proposition\.etudiant\.semestre\.annee\.diplome\.sigle\s*\}\}/' => '{etudiant.parcours}',
    '/\{\{\s*user_data\.departement\.telcontact\|tel_format\s*\}\}/' => '{dept.tel}',
    '/\{\{\s*proposition\.etudiant\.semestre\.annee\.diplome\.assistantDiplome\.mailUniv\s*\}\}/' => '{dept.mail}',
    '/\{%\s*if\s*user_data\.departement\.ufr\.sitePrincipal\.adresse\s*!=\s*null\s*%\}\{\{\s*user_data\.departement\.ufr\.sitePrincipal\.adresse\.display\|striptags\s*\}\}\{%\s*endif\s*%\}/' => '{dept.adresse}',
    '/\{\{\s*proposition\.entreprise\.raisonSociale\s*\}\}/' => '{entreprise.nom}',
    '/\{%\s*if\s*proposition\.entreprise\.adresse\s*!=\s*null\s*%\}\{\{\s*proposition\.entreprise\.adresse\.display\|striptags\s*\}\}\{%\s*endif\s*%\}/' => '{entreprise.adresse}',
    '/\{\{\s*proposition\.entreprise\.responsable\.display\s*\}\}/' => '{entreprise.signataire}',
    '/\{\{\s*proposition\.entreprise\.responsable\.fonction\s*\}\}/' => '{entreprise.signataire_fonction}',
    '/\{\{\s*proposition\.serviceStageEntreprise\s*\}\}/' => '{stage.service}',
    '/\{\{\s*proposition\.entreprise\.responsable\.telephone\s*!=\s*null\s*\?\s*proposition\.entreprise\.responsable\.telephone\|tel_format\s*:\s*proposition\.entreprise\.responsable\.portable\|tel_format\s*\}\}/' => '{entreprise.telephone}',
    '/\{\{\s*proposition\.entreprise\.responsable\.email\s*\}\}/' => '{entreprise.email}',
    '/\{%\s*if\s*proposition\.adresseStage\s*!=\s*null\s*%\}\s*\{\{\s*proposition\.adresseStage\.display\|striptags\s*\}\}\s*\{%\s*endif\s*%\}/' => '{stage.adresse_different}',
    '/\{\{\s*proposition\.etudiant\.nom\s*\}\}/' => '{etudiant.nom}',
    '/\{\{\s*proposition\.etudiant\.prenom\|title\s*\}\}/' => '{etudiant.prenom}',
    '/\{%\s*if\s*proposition\.etudiant\.civilite\s*==\s*"Mme"\s*%\}Femme\{%\s*else\s*%\}Homme\{%\s*endif\s*%\}/' => '{etudiant.sexe}',
    '/\{\{\s*proposition\.etudiant\.datenaissance\|date\(\'d\/m\/Y\'\)\s*\}\}/' => '{etudiant.date_naissance}',
    '/\{%\s*if\s*proposition\.etudiant\.adresse\s*!=\s*null\s*%\}\{\{\s*proposition\.etudiant\.adresse\.display\|striptags\s*\}\}\{%\s*endif\s*%\}/' => '{etudiant.adresse}',
    '/\{\{\s*proposition\.etudiant\.tel1\|tel_format\s*\}\}\s*\/\s*\{\{\s*proposition\.etudiant\.tel2\|tel_format\s*\}\}/' => '{etudiant.telephones}',
    '/\{\{\s*proposition\.etudiant\.mailUniv\s*\}\}/' => '{etudiant.email}',
    '/\{\{\s*proposition\.etudiant\.semestre\.annee\.diplome\.typediplome\.libelle\s*\}\}\s*\{\{\s*proposition\s*\.etudiant\.semestre\.annee\.diplome\.libelle\s*\}\}\s*\({\{\s*proposition\.etudiant\.semestre\.annee\.diplome\s*\.volumehoraire\s*\}\}\s*heures\s*pour\s*le\s*diplôme\)/' => '{etudiant.formation} ({formation.volume_horaire} heures pour le diplôme)',
    '/\{\{\s*proposition\.etudiant\.semestre\.annee\.diplome\.typediplome\.libelle\s*\}\}\s*\{\{\s*proposition\s*\.etudiant\.semestre\.annee\.diplome\.libelle\s*\}\}/' => '{etudiant.formation}',
    '/\{\{\s*proposition\.sujetStage\s*\}\}/' => '{stage.sujet}',
    '/\{\{\s*proposition\.dateDebutStage\|date\(\'d\/m\/Y\'\)\s*\}\}/' => '{stage.date_debut}',
    '/\{\{\s*proposition\.dateFinStage\|date\(\'d\/m\/Y\'\)\s*\}\}/' => '{stage.date_fin}',
    '/\{\{\s*\(proposition\.dureeJoursStage\/5\)\s*\|round\(0,\s*\'ceil\'\)\s*\}\}/' => '{stage.semaines}',
    '/\{\{\s*proposition\.dureeJoursStage\s*\}\}/' => '{stage.jours}',
    '/\{\{\s*proposition\.commentaireDureeHebdomadaire\s*\}\}/' => '{stage.commentaire_heures}',
    '/\{%\s*if\s*proposition\.stagePeriode\.stagePeriodeInterruptions\|length\s*>\s*0\s*%\}.*?\{%\s*endif\s*%\}\.\s*\{\{\s*proposition\.periodesInterruptions\s*\}\}/s' => '{stage.interruptions}',
    '/\{\{\s*proposition\.tuteurUniversitaire\.display\s*\}\}/' => '{tuteur.nom}',
    '/\{\{\s*proposition\.tuteurUniversitaire\.telbureau\|tel_format\s*\}\}/' => '{tuteur.telephone}',
    '/\{\{\s*proposition\.tuteurUniversitaire\.mailUniv\s*\}\}/' => '{tuteur.email}',
    '/\{\{\s*proposition\.tuteur\.display\s*\}\}/' => '{tuteur_entreprise.nom}',
    '/\{\{\s*proposition\.tuteur\.fonction\s*\}\}/' => '{tuteur_entreprise.fonction}',
    '/\{\{\s*proposition\.tuteur\.telephone\s*!=\s*null\s*\?\s*proposition\.tuteur\.telephone\|tel_format\s*:\s*proposition\.tuteur\.portable\|tel_format\s*\}\}/' => '{tuteur_entreprise.telephone}',
    '/\{\{\s*proposition\.tuteur\.email\s*\}\}/' => '{tuteur_entreprise.email}',
    '/\{\{\s*proposition\.etudiant\.intituleSecuriteSociale\s*\}\}/' => '{etudiant.secu}',
    '/\{\{\s*proposition\.etudiant\.adresseSecuriteSociale\s*\}\}/' => '{etudiant.secu_adresse}',
    '/\{\{\s*proposition\.activites\s*\}\}/' => '{stage.activites}',
    '/\{\{\s*proposition\.stagePeriode\.competencesVisees\s*\}\}/' => '{stage.competences}',
    '/\{\{\s*proposition\.dureeHebdomadaire\s*\}\}/' => '{stage.heures_hebdo}',
    '/\{\{\s*proposition\s*\.amenagementStage\s*\}\}/' => '{stage.amenagements}',
    '/\{\{\s*proposition\.stagePeriode\.modaliteEncadrement\s*\}\}/' => '{stage.modalites_encadrement}',
    '/\{\{\s*proposition\.avantages\s*\}\}/' => '{stage.avantages}',
    '/\{\{\s*proposition\.stagePeriode\.documentRendre\s*\}\}/' => '{stage.documents_a_rendre}',
    '/\{\{\s*proposition\.stagePeriode\.nbEcts\s*==\s*0\s*\?\s*\'Le stage ne donne pas\s*lieu à des ECTS mais une note comptant dans différentes UE\'\s*:\s*proposition\.stagePeriode\.nbEcts\s*\}\}/' => '{stage.ects}',
    '/\{\{\s*\'now\'\|date\(\'d\/m\/Y\'\)\s*\}\}/' => '{date_creation}',
    '/\{\{\s*user_data\.departement\.ufr\.responsable\s*\.display\s*\}\}/' => '{etablissement.signataire}',
    '/\{\{\s*proposition\.entreprise\.responsable\.prenom\|capitalize\s*\}\}\s*\{\{\s*proposition\s*\.entreprise\.responsable\.nom\|upper\s*\}\}/' => '{entreprise.signataire}',
    '/\{\{\s*proposition\.etudiant\.displayPr\s*\}\}/' => '{etudiant.prenom} {etudiant.nom}',
    '/\{%\s*if\s*proposition\.tuteurUniversitaire\s*!=\s*null\s*%\}\{\{\s*proposition\.tuteurUniversitaire\.displayPr\s*\}\}\{%\s*endif\s*%\}/' => '{tuteur.nom}',
    '/\{\{\s*proposition\.tuteur\.prenom\|capitalize\s*\}\}\s*\{\{\s*proposition\.tuteur\.nom\|upper\s*\}\}/' => '{tuteur_entreprise.nom}',
    '/\|\s*\{\{\s*proposition\.etudiant\.mailPerso\s*\}\}/' => '', // remove secondary email reference

    // Accès tuteur fallback
    '/\{\{\s*proposition\.tuteurUniversitaire\.displayPr\s*\}\}/' => '{tuteur.nom}',
];

foreach ($replacements as $pattern => $replacement) {
    $content = preg_replace($pattern, $replacement, $content);
}

// Clean up structural Twig tags
// 1. Tuteur universitaire blocks
$content = preg_replace('/\{%\s*if\s*proposition\.tuteurUniversitaire\s*!=\s*null\s*%\}(.*?)\{%\s*else\s*%\}.*?\{%\s*endif\s*%\}/s', '$1', $content);

// 2. Tuteur entreprise blocks
$content = preg_replace('/\{%\s*if\s*proposition\.tuteur\s*!=\s*null\s*%\}(.*?)\{%\s*else\s*%\}.*?\{%\s*endif\s*%\}/s', '$1', $content);

// 3. Gratification blocks
$content = preg_replace('/\{%\s*if\s*proposition\.gratification\s*==\s*true\s*%\}\s*\{stage\.gratification\}\s*\{%\s*else\s*%\}\s*-\s*\{%\s*endif\s*%\}/', '{stage.gratification}', $content);

// 4. Gratification period select block
$content = preg_replace('/\{%\s*if\s*proposition\.gratificationPeriode\s*==\s*\'M\'\s*%\}.*?\{%\s*else\s*%\}.*?\{%\s*endif\s*%\}/s', 'par {stage.gratification_periode}', $content);

// 5. Clean up signature electrónico block
$content = preg_replace('/\{%\s*if\s*proposition\.tuteurUniversitaire\.signatureElectronique\s*==\s*null\s*%\}.*?\{%\s*endif\s*%\}/s', '', $content);
$content = preg_replace('/\{%\s*if\s*proposition\.tuteurUniversitaire\.signatureElectronique\s*!=\s*null\s*%\}.*?\{%\s*endif\s*%\}/s', '', $content);

// 6. Strip any other leftover Twig control tags
$content = preg_replace('/\{%.*?%\}/', '', $content);
$content = preg_replace('/\{\{\s*settings\(\'BASE_PATH\'\)\s*\}\}/', '', $content);

// Wrap in full HTML document with optimized styles
$html = '<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body {
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        font-size: 9.5px;
        line-height: 1.35;
        color: #2D3748;
        margin: 15px;
    }
    .blocconvention {
        margin: 0;
        padding: 0;
        font-size: 9.5px;
        text-align: justify;
    }
    .blocconvention_colonne {
        width: 100%;
        margin: 0;
        padding: 5px;
        font-size: 9.5px;
        text-align: justify;
        border-spacing: 0;
    }
    .blocconvention_colonne td {
        vertical-align: top;
    }
    .blocconvention_preambule {
        font-size: 11px;
    }
    .blocconvention_preambule_encadre {
        font-size: 10.5px;
        padding: 10px;
        border: 1px solid #4A5568;
        margin-bottom: 10px;
        background-color: #F8FAFC;
        border-radius: 6px;
    }
    p {
        margin: 0;
        padding: 0;
        padding-bottom: 4px;
    }
    ul {
        margin: 0;
        margin-left: 20px;
        padding: 0;
    }
    .center {
        text-align: center;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
    }
    td {
        border-color: #A0AEC0;
    }
    strong {
        color: #1A202C;
    }
</style>
</head>
<body>' . trim($content) . '</body>
</html>';

file_put_contents('/Users/davidannebicque/Sites/IUTTroyes/uniServices/scratch/default_convention.html', $html);
echo "Conversion complete!\n";
