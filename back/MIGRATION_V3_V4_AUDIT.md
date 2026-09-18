# Audit de migration intranet V3 → UniServices V4

> **Statut : document de travail / recette de migration**  
> Branche auditée : `feat/intranet-v3-migration`  
> Objectif : identifier, pour **chaque entité actuellement gérée par un migrateur**, les données reprises, transformées, non reprises et les anomalies à traiter avant la bascule.

## Légende

| État | Signification |
|---|---|
| ✅ | repris avec la même sémantique |
| 🔄 | repris avec transformation de modèle ou calcul |
| ⚠️ | repris partiellement / hypothèse à valider |
| ❌ | non repris alors qu'une donnée V3 existe |
| ⏭️ | non repris volontairement / hors périmètre |
| 🆕 | champ V4 sans source V3, valeur par défaut ou calculée |

Priorités : **P0** perte/corruption possible, **P1** décision métier nécessaire, **P2** fidélité historique ou amélioration, **P3** perte volontaire/documentaire.

---

## 1. Périmètre réellement couvert

### Core — structure
`type-diplomes`, `departements`, `diplomes`, `annees-universitaires`, `pns`, `annees`, `semestres`, `groupes`.

### Core — utilisateurs / scolarité
`bacs`, `personnels`, `personnel-departements`, `etudiants`, `scolarites`, `scolarite-details`, `etudiant-groupes`.

### Core — maquette / APC
`ues`, `matieres`, référentiel APC, compétences, niveaux, apprentissages critiques, parcours, relations, ressources, SAE et liens diplôme/UE.

### Core — données pédagogiques opérationnelles
évaluations, notes, rattrapages, EDT, absences, justificatifs, prévisionnels, types HRS et HRS.

### Bundles
Stage : périodes, entreprises/contact référencés, stages étudiants et contexte.  
Document : catégories, documents et fichiers physiques.

---

# 2. Structure

## 2.1 Departement → StructureDepartement

Le migrateur lit explicitement : `id, libelle, logo_name, tel_contact, couleur, site_web, description, actif`.

| V3 | V4 / migration | État | Action |
|---|---|---:|---|
| id | oldId | ✅ | — |
| libelle | libelle | ✅ | — |
| logoName | logoName | ✅ métadonnée | vérifier copie physique du logo |
| telContact | telContact | ✅ | — |
| couleur | couleur | ✅ | — |
| siteWeb | siteWeb | ✅ | — |
| description | description | ✅ | — |
| actif | actif | ✅ | — |
| fax | aucun mapping | ❌ P2 | décider abandon ou champ V4 |
| ufr | aucun mapping | ❌ P1 | vérifier si établissement/composante V4 remplace cette relation |
| respri | aucun mapping direct | 🔄 P1 | doit devenir permission/responsabilité |
| respMateriel | aucun mapping direct | 🔄 P1 | doit relever d'un bundle/permission |
| anneeUniversitairePrepare | snapshots V4 | 🔄 | modèle remplacé |
| preparationAnnee | aucun mapping direct | ⏭️/P2 | confirmer obsolescence |
| optUpdateCelcat | aucun mapping | ❌ P1 | configuration intégration ? |
| optAgence | aucun mapping | ⏭️ P2 | bundle/configuration |
| optMateriel | aucun mapping | ⏭️ P2 | activation bundle |
| optEdt | aucun mapping | ⏭️ P2 | activation bundle |
| optStage | aucun mapping | ⏭️ P2 | activation bundle |
| optSynthese | aucun mapping | ⏭️ P2 | confirmer obsolescence |
| optMessagerie | aucun mapping | ⏭️ P2 | confirmer obsolescence |
| optAnneePrevisionnel | aucun mapping | ❌ P1 | vérifier impact prévisionnels |

**Point d'attention :** les options V3 ne doivent pas nécessairement devenir des colonnes V4 ; plusieurs correspondent mieux à l'activation/configuration des packages. Il faut néanmoins produire une règle explicite de conversion avant la bascule.

## 2.2 TypeDiplome → StructureTypeDiplome

Le migrateur ne lit que `id, libelle, sigle, apc` et résout la cible par **sigle**, pas par oldId.

| V3 | V4 | État |
|---|---|---:|
| libelle | libelle | ✅ |
| sigle | sigle | ✅ |
| apc | apc | ✅ |
| id | non conservé | ⚠️ P2 |
| nbSemestres | non migré | ❌ P1 |
| niveauEntree | non migré | ❌ P1 |
| niveauSortie | non migré | ❌ P1 |
| mccTypeEpreuves | hors migration actuelle | ❌ P1 |

**Action P1 :** comparer `StructureTypeDiplome` avec V3 et décider si `nbSemestres/niveauEntree/niveauSortie` sont encore nécessaires. Le rapprochement par sigle suppose également son unicité et sa stabilité.

## 2.3 Diplome → StructureDiplome

Le migrateur reprend `departement, parent, libelle, volumeHoraire, codeCelcatDepartement, sigle, logoPartenaire, keyEduSign`.

**Repris :** id→oldId, département, hiérarchie parent/enfant, libellé, volume horaire, code CELCAT département, sigle, logo partenaire, clé EduSign.

**Non repris / à décider :**
- `responsableDiplome`, `assistantDiplome` → **P1**, à convertir en permissions/responsabilités ;
- `typeDiplome` → **P0/P1** : le migrateur `DiplomeMigrator` ne le lit pas ; le lien peut être rétabli par `ApcDiplomeLinkMigrator` mais ce migrateur est orienté APC. Vérifier tous les diplômes non APC ;
- `actif` → **P1** : sélectionné mais actuellement non affecté dans le migrateur ;
- `anneeUniversitaire` → remplacé par les snapshots PN ;
- options `optNbJoursSaisie`, `optDiplomeDecale`, `optSupprAbsence`, `optMethodeCalcul`, `optAnonymat`, `optCommentairesReleve`, `optEspacePersoVisible`, `optSemainesVisibles`, `optCertifieQualite`, `optResponsableQualite`, `optUpdateCelcat`, `saisieCmAutorise` → **P1**, aucune reprise ;
- `referentiel`, `apcParcours` → repris dans la phase APC ;
- fichiers `logoFile` → objet fichier non migré, seul le nom est conservable.

## 2.4 PN annuel

V3 ne possède pas réellement le concept de snapshot annuel. V4 crée un `StructurePn` par **(diplôme, année universitaire observée dans les scolarités)**.

C'est une transformation volontaire :

`structure V3 courante × années observées → snapshots V4 historiques`.

⚠️ **Limitation fondamentale :** le contenu structurel historique n'est pas connu. Une structure V3 actuelle est clonée dans les années passées. Le snapshot donne donc le bon contexte annuel aux données historiques, mais **ne prouve pas que la maquette était identique cette année-là**.

## 2.5 Annee → StructureAnnee

Repris : id→oldId dans chaque snapshot, libellé, ordre, libellé long, actif, couleur, `codeVersion`, `codeEtape`.

Non repris :
- `optAlternance` → ❌ P1 ;
- relations alternances → hors Core / bundle concerné ;
- APC niveaux → reconstruits par la migration APC.

## 2.6 Semestre → StructureSemestre

Repris explicitement : id→oldId par snapshot, libellé, ordre année/LMD, actif, nombres de groupes CM/TD/TP, code élément.

**Nombreuses options V3 non reprises :**
- options de mails relevés/modification de note/absences/rattrapages ;
- visibilité/modifiabilité des évaluations ;
- pénalités d'absence ;
- délais de saisie absence ;
- bilan semestre ;
- préinitialisation évaluations ;
- `moisDebut`, `ppnActif`, `idEduSign` ;
- liens de semestre départ/arrivée ;
- diverses relations fonctionnelles.

**P1 :** ces champs sont majoritairement des réglages applicatifs. Ils doivent être classés un par un en « configuration V4 », « obsolète » ou « donnée à migrer ». Ils ne doivent pas disparaître implicitement.

## 2.7 Groupe → StructureGroupe

Repris : oldId, libellé, type, ordre, code Apogée, parent, rattachements aux semestres du snapshot.

Non repris :
- `parcours` / `apcParcours` → ❌ P1, vérifier si le nouveau modèle de groupe doit conserver cette restriction ;
- `idEduSign` → ❌ P1 si synchronisation EduSign ;
- autres collections opérationnelles → hors structure.

Le type inconnu est actuellement converti en `TYPE_GROUPE_AUTRE` : ⚠️ il faut compter et signaler ces conversions.

---

# 3. Utilisateurs

## 3.1 Etudiant

Le migrateur lit : `id, username, mail_univ, mail_perso, prenom, nom, photo_name, num_etudiant, num_ine, annee_bac, boursier, amenagements_particuliers, promotion, annee_sortie, bac_id`.

Repris : identité, mails, login, photoName, numéro étudiant, INE, année du bac, bac, boursier, aménagements, promotion, année sortie, rôle étudiant.

**Complété lors de la passe utilisateurs :**
- adresse étudiante V3 (`Utilisateur.adresse`) → `adresseEtudiante` JSON ;
- adresse parentale → `adresseParentale` JSON ;
- date/lieu de naissance, téléphones, sites personnel/universitaire ;
- `idEduSign`.

**Non repris / décision explicite :**
- UUID V3 → ⏭️ P3 si aucune URL/intégration ne l'utilise ;
- dates created/updated → ❌ P2 ;
- `semestre` courant → 🔄 scolarité ;
- `adresseParentale` → ❌ **P1** ;
- `demandeurEmploi` → ❌ **P1** ;
- `deleted` → ❌ **P0/P1** : risque de réactiver un ancien compte ;
- `intituleSecuriteSociale`, `adresseSecuriteSociale` → ❌ P2 / probablement abandon RGPD souhaitable ;
- `departement` → 🔄 déduit de la scolarité, mais vérifier les étudiants sans scolarité ;
- `loginSpecifique` → ❌ P1 ;
- `formationContinue` → ❌ **P1** ;
- `idEduSign` → ❌ **P1** ;
- mots de passe → ⏭️ volontaire ;
- favoris documents → audit Document ;
- commentaires/projets/événements → hors périmètre actuel.

**P0 :** traiter `deleted` avant import réel.

## 3.2 Personnel

Le migrateur actuel ne lit que : id, username, mails, prénom, nom, photo et année universitaire.

La passe utilisateurs complète maintenant les champs V4 ayant une correspondance directe.

**Désormais repris :** mails, identité/photo, année universitaire, statut lorsqu'il correspond à `StatutEnum`, poste interne, téléphone bureau, responsabilités, domaines (texte V3 normalisé en tableau), entreprise, bureaux 1+2 fusionnés, numéro Harpège, initiales, nombre d'heures de service, sites personnel/universitaire et id EduSign JSON valide.

**Toujours non repris / décision nécessaire :**
- `statut` → ❌ **P0/P1** ;
- `posteInterne`, `telBureau`, `responsabilites`, `domaines`, `entreprise`, `bureau1`, `bureau2`, `numeroHarpege`, `initiales` → ❌ P1/P2 ;
- `cvName` → ❌ P2 + fichier ;
- `nbHeuresService` → ❌ **P1** ;
- `deleted` → ❌ **P0** ;
- `couleur` → ❌ P2 ;
- `signatureElectronique` → ❌ P1 ;
- `configuration` → ⏭️ probablement ne pas migrer aveuglément ;
- `accessOriginaux` → ❌ P1, à rapprocher des permissions Document ;
- `idEduSign` → ❌ P1 ;
- created/updated → ❌ P2.

**Statuts :** les valeurs correspondant exactement à `StatutEnum` sont migrées. Les valeurs inconnues sont laissées à `null` et comptées dans le rapport, sans fallback silencieux. `permanent` est actuellement converti en `AUTRE` faute d'équivalent métier exact : à confirmer.

**Deleted :** comme pour les étudiants, la V4 ne possède pas de champ `deleted`. Les personnels restent importés pour préserver les références historiques et sont comptés dans le rapport.

## 3.3 PersonnelDepartement → StructureDepartementPersonnel

Repris : personnel, département, défaut, année/affectation selon le modèle cible, packages et conversion partielle des rôles.

Rôles explicitement convertis :
`ROLE_PERMANENT`, `ROLE_CDD`, `ROLE_DDE`, `ROLE_ASS`, `ROLE_RP`, `ROLE_NOTES`.

Non résolus observés : `ROLE_STA`, `ROLE_ABS`, `ROLE_EDT`, `ROLE_PRJ`.

**P0/P1 :** établir un mapping par bundle/permission. Ne pas convertir silencieusement.

---

# 4. Scolarité

## 4.1 Scolarite → EtudiantScolarite + EtudiantScolariteSemestre

Transformation V3 → V4 :
- plusieurs lignes V3 par semestre sont regroupées en une scolarité annuelle ;
- un détail `EtudiantScolariteSemestre` est créé par semestre.

Repris :
- étudiant, année universitaire, département dérivé ;
- ordre ;
- moyenne semestrielle ;
- nb absences semestriel + somme annuelle ;
- commentaire (premier non vide seulement) ;
- diffuse → public annuel par MAX ;
- actif dérivé de l'année universitaire.

Complément `scolarite-details` :
- décision → booléen ;
- moyennes matières ;
- moyennes UE.

**Non repris / transformation avec perte :**
- `proposition` → ❌ **P1**, explicitement signalée par le migrateur ;
- `rang` est sélectionné par `ScolariteDetailsMigrator` mais **jamais affecté** → ❌ **P1** ;
- `scolaritePromo` → ❌ P2 ;
- codes de décision : `V/VCJ/VCA → true`, `NV/DEF → false` → ⚠️ **perte de nuance P1** ;
- commentaire : plusieurs commentaires semestriels peuvent être condensés en un seul commentaire annuel → ⚠️ P2 ;
- scolarités sans année universitaire → skipped, anomalie source à corriger si possible.

**P0/P1 :** proposition, rang et sémantique détaillée de décision doivent être décidés avant bascule.

## 4.2 Affectations groupes étudiants

V3 `etudiant_groupe` n'est pas historisé par année. Le migrateur rattache chaque affectation au **semestre compatible le plus récent**.

C'est une heuristique nécessaire, mais non une restitution historique exacte.

Les affectations impossibles sont classées :
- semestre courant absent ;
- aucune scolarité sur le semestre courant ;
- groupe incompatible ;
- autre incohérence.

**P1 :** conserver le rapport détaillé des ~802 anomalies et décider si une correction source est possible.

---

# 5. Maquette classique

## 5.1 UE

Repris : oldId par snapshot, semestre, libellé, numéro UE, ECTS, actif, bonification, code élément. Compétence APC rattachée ensuite.

⚠️ **`coefficient` V3 est sélectionné mais n'est pas affecté directement sur `StructureUe`**. Vérifier si le nouveau modèle n'en a volontairement plus besoin. **P1.**

## 5.2 Matiere → ScolEnseignement + ScolEnseignementUe

Repris :
- oldId, libellés, description ;
- objectifs, prérequis, mots-clés ;
- code matière/code élément ;
- suspendu, mutualisée, nb notes ;
- heures CM/TD/TP PN et IUT ;
- coefficient et ECTS sur la liaison UE ;
- hiérarchie matière parent/enfant.

**V3 non repris :**
- `competencesVisees` → ❌ P1 ;
- `contenu` → ❌ **P1** ;
- `modalites` → ❌ **P1** ;
- `prolongements` → ❌ P1 ;
- `pac`, `ppn`, `parcours` → à comparer avec le modèle V4/APC ; potentielle perte P1.

---

# 6. APC

## Referentiel
Repris : libellé, description, année publication, département, type diplôme.  
⚠️ vérifier les autres propriétés éventuelles V3 non sélectionnées.

## Competence
Repris : oldId, libellé, nom court, couleur, référentiel.

## Niveau
Repris : compétence, libellé, ordre.  
⚠️ `ordre_annee` est sélectionné mais non affecté : **P1**.

## ApprentissageCritique
Repris : oldId, niveau, libellé, code.

## Parcours
Repris : oldId, libellé, code→sigle, actif, couleur. `formation_continue` est converti via `setOpt(...)` : vérifier la sémantique exacte.

## Relations ressources
La hiérarchie parent/enfant des ressources est reprise.

## Ressource
Repris : identité/libellés/description, prérequis, mots-clés, codes, suspendu/mutualisée, nb notes, volumes CM/TD/TP PN/IUT, coefficients de compétence/parcours.

À contrôler : autres champs V3 non sélectionnés et ECTS selon la relation cible.

## SAE
Repris : identité/libellés/description, objectif, codes, suspendu/mutualisée, nb notes, livrables, exemples, bonification, CM/TD/TP/projet PN/IUT, compétences/coefficient.

**Relation SAE ↔ ressources : ❌ P0/P1.** Elle n'est pas reconstruite car le modèle cible ne correspond pas. Décider si le modèle V4 doit évoluer.

## Liens diplôme / UE
- diplôme ↔ type diplôme / référentiel / parcours : migré par phase APC ;
- UE ↔ compétence : migré.

---

# 7. Évaluations et notes

## Evaluation → ScolEvaluation

Repris : UUID V3, auteur, année, semestre, parent, type/id matière résolus vers enseignement, date, visible, modifiable, coefficient, commentaire, libellé et état cible.

⚠️ Les évaluations autorisées à d'autres personnels ne figurent pas dans les champs du migrateur principal : **P1** si cette délégation doit être conservée.

## Note → EtudiantNote

Repris : étudiant via scolarité, évaluation, note, commentaire, absence justifiée transformée en statut de présence, publication.

**Non repris :**
- historique `ModificationNote` → ⏭️/P1 selon exigence d'audit ;
- id V3 de la note n'est pas nécessairement conservé → P3 ;
- nuances éventuelles autour des statuts d'absence → vérifier.

---

# 8. Données opérationnelles limitées à l'année active

Ce choix est volontaire et doit être considéré comme une règle de rétention, pas comme une anomalie.

## Rattrapage
Année active seulement. Repris : UUID, étudiant, personnel, enseignement/évaluation résolu, état, date, horaires/durée, salle.

Historique : ⏭️ non repris.

## EDT
Année active seulement. Le migrateur reprend un ensemble très riche : oldId, semaine, jour/date, début/fin, salle, personnel, enseignement, groupe, type, année, semestre, évaluation, ordre, libellés/codes, EduSign.

⚠️ champs V3 `texte`, `commentaire`, certains champs d'ordre/placement sont à vérifier : la requête les lit mais tous ne sont pas forcément transposés tels quels.

## Absence
Détail année active seulement. Repris : UUID, personnel, scolarité semestrielle, événement EDT, date de justification, EduSign.

Historique : seul `nbAbsences` agrégé dans les scolarités est conservé.

⚠️ vérifier que date/heure d'absence est bien portée par l'event cible ; sinon perte potentielle P0.

## Justificatif
Année active seulement. Repris : UUID, début/fin, motif, état, scolarité, nom de fichier, liens aux absences et date justification.

**Fichier physique du justificatif : P1**, contrôler/copie à prévoir si nécessaire.

## Prévisionnel
Année active seulement. Repris : personnel, année, enseignement, référent, heures CM/TD/TP et groupes CM/TD/TP.

Historique : ⏭️.

## TypeHrs
Repris : libellé, type, inclus service, maximum.  
⚠️ id V3 non conservé : résolution métier utilisée ; vérifier unicité.

## Hrs
Année active seulement. Repris : personnel, année, type HRS, libellé, nb heures TD, semestre, diplôme.

Historique : ⏭️.

---

# 9. Stage

## StagePeriode
Migré avec oldId, paramètres principaux et responsables. La M2M V3 des responsables est transformée en responsable principal + co-responsables.

## Entreprise / Contact
Seuls les enregistrements effectivement référencés par les stages sont migrés. L'annuaire historique complet est volontairement hors périmètre.

## StageEtudiant
Les stages historiques sont repris, y compris les stages sans période. Entreprise/contact sont rattachés.

**P0 :** diagnostiquer les **13 lignes Updated** observées lors du dry-run ; si la cible est vide, 4216 sources devraient normalement produire 4216 identités métier distinctes. Suspect principal : UUID V3 dupliqué / clé d'identification.

## StageContexte
Les contextes APPRENTISSAGE, ERASMUS et ETRANGER sont préservés.

## Non repris
Offres de stage, annuaire complet, et autres historiques périphériques sauf nécessité pour représenter le stage effectué : ⏭️ volontaire.

**P0 technique :** sécuriser le reset Stage : ne pas tronquer aveuglément Entreprise/Contact s'ils sont partagés avec d'autres bundles.

---

# 10. Document

## DocumentCategory
Repris : oldId, libellé, département, parent/enfants, `originaux → isOriginal`.

Non repris : dates historiques created/updated (P2). Champs V4 icon/color/packageKey/isSystem : 🆕.

## Document
Repris : oldId, titre, description, filename, MIME, taille, catégorie, département dérivé, visibilité, type calculé.

**À traiter :**
- created/updated → ❌ P2 ;
- UUID → ⏭️ P3 sauf usage externe ;
- document ↔ semestres → ❌ **P1** ;
- favoris utilisateurs → ❌ **P1** ; `Document.isFavorite` n'est pas sémantiquement équivalent ;
- fichiers physiques → migrateur disponible, recette réelle à effectuer ;
- fallback MIME `file` → **P1** : vérifier qu'il est accepté partout dans V4 ;
- typeDestinataire inconnu ne doit pas devenir PUBLIC silencieusement.

---

# 11. Liste d'actions consolidée

## P0 — avant toute migration réelle

- [ ] Définir la politique V4 pour les utilisateurs V3 `deleted=true` (ils sont désormais explicitement comptés par les migrateurs).
- [x] Compléter les correspondances directes du profil **Personnel** ; les statuts inconnus sont diagnostiqués.
- [x] Compléter les correspondances directes du profil **Etudiant** et ses deux adresses.
- [x] Rattacher **Diplome → TypeDiplome** dès la migration Structure, indépendamment de l'APC.
- [ ] Décider/réparer **SAE ↔ ressources**.
- [ ] Diagnostiquer les **13 StageEtudiant Updated**.
- [ ] Vérifier que les absences conservent réellement leur date/heure via l'event EDT.
- [ ] Sécuriser le reset Stage vis-à-vis des Entreprise/Contact partagés.

## P1 — décisions métier

- [ ] Département : convertir explicitement les `opt_*` utiles vers packages/configuration.
- [ ] TypeDiplome : nbSemestres, niveaux entrée/sortie, MCC.
- [ ] Diplome : responsables/assistants, actif et options métier.
- [ ] Annee : optAlternance.
- [ ] Semestre : classifier toutes les options historiques.
- [ ] Groupe : parcours/APC parcours et EduSign.
- [ ] Etudiant : adresse parentale, demandeurEmploi, formationContinue, loginSpecifique, EduSign.
- [ ] Personnel : profil professionnel, nbHeuresService, signature, accessOriginaux, EduSign.
- [ ] Mapper ROLE_STA / ROLE_ABS / ROLE_EDT / ROLE_PRJ vers les permissions des bundles.
- [ ] Scolarité : proposition, rang, nuances de décision.
- [ ] Traiter/valider les affectations groupes non résolues.
- [ ] UE : coefficient.
- [ ] Matière : contenu, compétences visées, modalités, prolongements.
- [ ] APC Niveau : ordreAnnee.
- [ ] Évaluations : personnels autorisés.
- [ ] Justificatifs : fichiers physiques.
- [ ] Document : semestres et favoris.
- [ ] Document : politique pour MIME inconnu et visibilité inconnue.

## P2 — fidélité / qualité

- [ ] Conserver created/updated lorsque V4 possède l'équivalent.
- [ ] Copier/valider photos, logos, CV et autres fichiers conservés par nom.
- [ ] Décider du fax et autres coordonnées secondaires.
- [ ] Vérifier les commentaires semestriels condensés au niveau annuel.
- [ ] Documenter précisément les snapshots historiques reconstruits.

## P3 — pertes volontaires à documenter

- [ ] Mots de passe V3.
- [ ] UUID sans usage externe lorsqu'un oldId suffit.
- [ ] Détail historique des absences/justificatifs/rattrapages/EDT.
- [ ] Prévisionnels et HRS historiques.
- [ ] Historique des modifications de notes si non requis.
- [ ] Offres de stage et annuaires Stage non référencés.

---

# 12. Recette recommandée

Pour chaque migrateur avant validation :

1. comparer le nombre de lignes source éligibles avec `created + updated + skipped + failed` ;
2. expliquer **chaque skipped** par une catégorie de diagnostic ;
3. interdire les fallbacks silencieux qui élargissent des droits/visibilités ;
4. comparer les relations, pas seulement les cardinalités ;
5. contrôler les champs V3 sélectionnés mais jamais affectés ;
6. contrôler les champs V3 importants qui ne sont même pas sélectionnés ;
7. vérifier les fichiers physiques lorsque la BDD ne contient que leur nom ;
8. exécuter une seconde migration sur la même base et vérifier l'idempotence ;
9. exécuter les integrity checkers après la migration complète ;
10. archiver le rapport final de dry-run et de migration réelle.

---

# 13. Prochaine passe technique

L'audit fait ressortir plusieurs corrections qui peuvent être réalisées sans décision métier : `deleted` utilisateurs, champs Personnel manifestement correspondants en V4, `Diplome.actif`, `rang` si V4 le supporte, dates historiques lorsque des setters existent, diagnostics de fallback et contrôles d'intégrité.

Les points qui changent le modèle V4 (favoris Document, semestres Document, SAE↔ressources, propositions de scolarité, options structurelles) doivent être décidés explicitement avant modification du schéma.


---

# 14. Passe utilisateurs — 2026-09-18

Corrections appliquées aux migrateurs :

- **EtudiantMigrator** : reprise des deux adresses V3, date et lieu de naissance, téléphones, sites web et identifiant EduSign. Les utilisateurs `deleted=true` sont comptés et signalés, sans inventer un état cible inexistant.
- **PersonnelMigrator** : reprise du statut, poste, téléphone bureau, responsabilités, domaines, entreprise, bureaux, Harpège, initiales, service statutaire, sites et EduSign. Les statuts non reconnus sont recensés au lieu d'être convertis silencieusement.
- **Choix volontaire** : aucun champ `deleted` n'a été ajouté artificiellement aux entités V4. Les comptes historiques restent nécessaires aux notes, scolarités, stages, EDT et autres relations. La désactivation fonctionnelle doit donc être modélisée explicitement si elle est encore nécessaire en V4.
- **Restent à décider côté utilisateurs** : demandeur d'emploi, formation continue, login spécifique, CV, signature électronique, accessOriginaux et quelques anciennes configurations V3 sans équivalent direct.


---

# 15. Passe Structure — corrections automatiques

Une nouvelle passe a été effectuée sur `Departement`, `TypeDiplome`, `Diplome`, `Annee`, `Semestre` et `Groupe`.

## Corrections appliquées

- **Diplome → TypeDiplome** est maintenant un lien de la migration structurelle elle-même. Il ne dépend plus du passage APC.
- Les **types diplôme non résolus** sont comptés et signalés.
- Les **diplômes V3 inactifs** sont comptés et signalés. Aucun champ `actif` n'a été ajouté artificiellement : `StructureDiplome` V4 n'en possède pas et ces diplômes peuvent être nécessaires à l'historique.
- Les **types de groupes inconnus** restent convertis en `TYPE_GROUPE_AUTRE`, mais la conversion n'est plus silencieuse : le rapport donne les valeurs et occurrences.
- Les anciens liens **Groupe.parcours / Groupe.apcParcours** et les **idEduSign de groupe** sont comptés lorsqu'ils existent et explicitement signalés comme non transposés.

## Confirmé comme différence de modèle V4

Les champs suivants n'ont actuellement **aucun emplacement direct** dans les entités Structure V4 et n'ont donc pas été ajoutés arbitrairement :

- `TypeDiplome.nbSemestres`, `niveauEntree`, `niveauSortie`, MCC ;
- options historiques de `Diplome` ;
- `Annee.optAlternance` ;
- nombreuses options applicatives de `Semestre` ;
- `Groupe.idEduSign`.

Ils restent des décisions métier/modèle P1.

## Point important sur Departement

Tous les champs présents à la fois dans V3 et `StructureDepartement` V4 sont déjà repris : libellé, logo, téléphone, couleur, site, description, actif. Les anciennes options départementales n'existent pas dans l'entité V4 et semblent davantage relever de l'activation/configuration des packages que de données structurelles. Aucune colonne V4 n'est donc ajoutée dans cette passe.

## Point important sur Annee / Semestre

Les champs structurels possédant un équivalent V4 sont déjà correctement migrés. Les champs restants sont essentiellement des options fonctionnelles V3 sans équivalent direct. Ils doivent être traités par décision de conception et non par simple copie.
