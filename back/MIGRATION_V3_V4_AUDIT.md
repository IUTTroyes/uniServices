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
| fax | aucun mapping | ❌ P2 | abandon V4 |
| ufr | aucun mapping | ❌ P1 | vérifier si établissement/composante V4 remplace cette relation |
| respri | aucun mapping direct | 🔄 P1 | doit devenir permission/responsabilité |
| respMateriel | aucun mapping direct | 🔄 P1 | doit relever d'un bundle/permission |
| anneeUniversitairePrepare | snapshots V4 | 🔄 | modèle remplacé |
| preparationAnnee | aucun mapping direct | ⏭️/P2 | confirmer obsolescence |
| optUpdateCelcat | aucun mapping | ❌ P1 | configuration intégration ? |
| optAgence | aucun mapping | ⏭️ P2 | bundle/configuration |
| optMateriel | aucun mapping | ⏭️ P2 | abandon V4  |
| optEdt | aucun mapping | ⏭️ P2 | abandon V4  |
| optStage | aucun mapping | ⏭️ P2 | abandon V4  |
| optSynthese | aucun mapping | ⏭️ P2 | abandon V4  |
| optMessagerie | aucun mapping | ⏭️ P2 | abandon V4  |
| optAnneePrevisionnel | aucun mapping | ❌ P1 | abandon V4  |

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
- [x] Matière : contenu, compétences visées, modalités, prolongements conservés via options d'enseignement.
- [x] APC Niveau : `ordreAnnee` ajouté et migré.
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

Après prise en compte de `OptionTrait`, les champs suivants n'ont toujours **aucun emplacement direct / aucune clé de resolver** dans les entités Structure V4 et n'ont donc pas été ajoutés arbitrairement :

- `TypeDiplome.nbSemestres`, `niveauEntree`, `niveauSortie`, MCC ;
- options historiques de `Diplome` ;
- `Annee.optAlternance` ;
- nombreuses options applicatives de `Semestre` ;
- `Groupe.idEduSign`.

Ils restent des décisions métier/modèle P1.

## Point important sur Departement

Tous les champs présents à la fois dans V3 et `StructureDepartement` V4 sont déjà repris : libellé, logo, téléphone, couleur, site, description, actif. `StructureDepartement` utilise `OptionTrait`. Les options compatibles sont désormais reprises : `optMateriel → materiel`, `optEdt → edt`, `optStage → stage`, et `respri_id → resp_ri` (ancienne référence conservée sous forme de chaîne, conformément au resolver actuel). Les autres options V3 sans clé dans le resolver restent à décider.

## Point important sur Annee / Semestre

Les champs structurels possédant un équivalent V4 sont déjà correctement migrés. Les champs restants sont essentiellement des options fonctionnelles V3 sans équivalent direct. Ils doivent être traités par décision de conception et non par simple copie.


## 15.1 Correction importante — OptionTrait

La règle d'audit est corrigée : un ancien champ V3 `opt*` n'est **pas considéré perdu** lorsque l'entité V4 utilise `OptionTrait` et que son `configureOptions()` expose une clé sémantiquement équivalente.

Mappings désormais appliqués :

**StructureDepartement**
- `opt_materiel → opt[materiel]`
- `opt_edt → opt[edt]`
- `opt_stage → opt[stage]`
- `respri_id → opt[resp_ri]` (référence V3 sous forme de chaîne ; resolver V4 à faire évoluer si l'on veut une vraie relation/IRI)

**StructureDiplome**
- `opt_nb_jours_saisie → nb_jours_saisie_absence`
- `opt_suppr_absence → supp_absence`
- `opt_anonymat → anonymat`
- `opt_commentaires_releve → commentaire_releve`
- `opt_espace_perso_visible → espace_perso_visible`
- `opt_semaines_visibles → semaine_visible`
- `opt_certifie_qualite → certif_qualite`
- `opt_responsable_qualite_id → resp_qualite`
- `opt_update_celcat → update_celcat`
- `saisie_cm_autorise → saisie_cm_autorisee`

**StructureSemestre**
- reprise de toutes les clés V3 disposant d'un équivalent dans le resolver : mails relevé/modification note, destinataires, visibilité/modification évaluation, pénalité absence, notifications absence, justificatifs, bilan, rattrapage ;
- `idEduSign` est également repris via `EduSignTrait`.

### Règle pour la suite de l'audit

Pour toutes les autres entités migrées, il faut désormais systématiquement contrôler :
1. les colonnes/propriétés V4 ;
2. les traits V4 (`OptionTrait`, `EduSignTrait`, timestamp, etc.) ;
3. le contenu de `configureOptions()` ;
4. les éventuels resolvers/services qui interprètent ces options.

Une option V3 n'est classée P1/perdue qu'après cette vérification.


---

# 16. Passe Maquette / APC

La vérification a été poursuivie en tenant compte des champs directs, traits, structures JSON et resolvers.

## ScolEnseignement

Le modèle V4 regroupant Matière, Ressource et SAÉ dans `ScolEnseignement`, un stockage `opt` validé par `OptionsResolver` a été ajouté pour les métadonnées V3 qui restent utiles sans justifier des colonnes dédiées.

Clés ajoutées :
- `competences_visees`
- `contenu`
- `modalites`
- `prolongements`
- `pac`
- `ppn_old_id`
- `parcours_old_id`
- `ressource_parent`
- `has_coefficient_different`

`MatiereMigrator` alimente les six premières familles pertinentes. Les anciens IDs PPN/parcours sont conservés comme références historiques plutôt que de recréer une relation incompatible avec les snapshots V4.

`ApcRessourceMigrator` conserve désormais `ressourceParent` et `hasCoefficientDifferent`.

## ApcNiveau

`ordreAnnee` a une sémantique métier propre et existe encore dans le modèle conceptuel V4 : un champ nullable a été ajouté à `ApcNiveau` et le migrateur le renseigne. Migration Doctrine associée.

## SAE ↔ Ressource

Le point reste ouvert et prioritaire. La V3 possède une relation explicite `ApcSaeRessource`. Le modèle V4 `ScolEnseignement` possède déjà un champ `sae` (ManyToOne self), ce qui suggère qu'une ressource peut être rattachée à une SAÉ, mais la cardinalité V3 doit être mesurée avant conversion : si une ressource V3 peut être reliée à plusieurs SAÉ, le ManyToOne V4 est insuffisant. **Ne pas migrer cette relation avant ce contrôle de cardinalité.**

## Autres constats

- coefficient et ECTS des matières sont déjà conservés sur `ScolEnseignementUe` ;
- coefficients Ressource/Compétence et SAÉ/Compétence sont transposés sur les liaisons enseignement/UE ;
- apprentissages critiques Ressource/SAÉ sont déjà liés ;
- volumes horaires utilisent un resolver JSON dédié et sont déjà correctement transposés ;
- `ApcParcours.formation_continue` est déjà conservé via `OptionTrait`.


---

# 17. Passe données opérationnelles

## Evaluations

La comparaison V3/V4 confirme que les données principales sont présentes : UUID, libellé, commentaire, coefficient, date, visibilité, modification, année, semestre, enseignement, auteur/personnels autorisés et parent.

Correction appliquée :
- `Evaluation.typeGroupe` était disponible dans V4 mais oublié par le migrateur. Il est maintenant migré vers `TypeGroupeEnum`. Les anciennes valeurs non reconnues sont conservées sémantiquement comme `AUTRE`.

À surveiller :
- V4 possède aussi `type`, `etat` et `stats`. `etat` est reconstruit à partir de `visible`; `stats` est une donnée V4 et n'a pas à provenir de V3. Le champ `type` V4 n'a pas d'équivalent évident dans l'entité Evaluation V3 auditée.

## Notes

Les champs métier V3 sont repris :
- évaluation ;
- étudiant via sa scolarité ;
- note ;
- commentaire ;
- absence justifiée, transformée en `presenceStatut`.

La collection V3 `modificationNotes` n'est volontairement pas migrée conformément au périmètre historique décidé. V4 possède un champ `historique`, mais le périmètre actuel ne reconstruit pas cet historique.

Attention : l'identification V4 utilise actuellement le couple évaluation/scolarité. Cela suppose une note unique par étudiant et évaluation, ce qui correspond au modèle attendu.

## Absences

Périmètre volontaire : détails de l'année active uniquement.

Les informations structurelles sont transposées vers :
- scolarité semestrielle ;
- événement EDT ;
- personnel ;
- UUID ;
- date de justification ;
- EduSign lorsque le trait/mutateur est disponible.

Les anciennes données `dateHeure`, durée et contexte matière ne sont pas recopiées en colonnes : elles sont représentées par l'événement EDT lié. C'est une transformation de modèle et non une perte si le rattachement EDT réussit.

Le champ booléen V3 `justifie` n'est pas copié directement : V4 matérialise la justification par la relation vers `EtudiantAbsenceJustificatif` et sa date. La passe justificatifs reconstruit cette relation.

## Justificatifs

Périmètre volontaire : année active uniquement.

Repris :
- UUID ;
- début/fin ;
- motif ;
- état ;
- étudiant via scolarité semestrielle ;
- nom/référence de fichier ;
- couverture des absences recalculée à partir de la période.

V4 possède `motif_refus` et `nom_fichier`, qui n'ont pas d'équivalent dans l'entité V3 auditée.

⚠️ Le fichier est actuellement migré comme **métadonnée/nom uniquement**. La copie physique des justificatifs n'est pas couverte par ce migrateur et doit rester un point explicite de l'audit.

## Rattrapages

Périmètre volontaire : année active uniquement.

Repris ou reconstruits :
- UUID ;
- étudiant ;
- personnel ;
- évaluation cible ;
- état ;
- date de rattrapage ;
- heure début ;
- heure fin calculée à partir de la durée.

⚠️ `salle` V3 est une chaîne libre alors que V4 attend une entité `Salle` : aucune correspondance implicite n'est faite. C'est un point P1 si la salle historique doit être conservée.

Les données de l'évaluation initiale (date/heure, matière, semestre) servent à retrouver `ScolEvaluation` plutôt qu'à être dupliquées dans l'entité V4.

## EDT

Périmètre volontaire : année active uniquement.

La majorité des champs utiles sont transposés : semaine, jour, date, horaires, salle texte, personnel, enseignement, groupe, type, année, semestre, indicateur évaluation, ordre de séance et EduSign.

Les anciennes valeurs texte `texte` servent de fallback au libellé module lorsque l'enseignement n'est pas résolu.

Champs V4 sans source directe : couleur, celcatId, codeSalle, departementCodeCelcat, updatedEvent. Ils ne sont pas inventés.

## Prévisionnels

Périmètre volontaire : année active.

Les champs V3 sont couverts : personnel, année, référent, heures CM/TD/TP et nombres de groupes CM/TD/TP. Les tableaux JSON V4 sont validés par leur resolver.

`Projet` n'existe pas dans le modèle V3 audité et reste donc à zéro.

## Types HRS

Tous les champs V3 audités ont un équivalent et sont migrés : libellé, type, inclus dans le service, maximum.

## HRS

Correction appliquée :
- `commentaire` V3 n'avait aucun emplacement V4 alors qu'il s'agit d'une donnée métier. Un champ nullable a été ajouté à `PersonnelEnseignantHrs` et le migrateur le renseigne.

Les autres données sont reprises : volume TD, libellé, semestre, diplôme, personnel, type HRS, année.

Le département V3 n'est pas dupliqué dans HRS V4. Il peut être déduit du contexte diplôme/semestre/personnel selon le cas ; il reste à vérifier si des HRS V3 possèdent un département sans diplôme/semestre permettant de conserver ce contexte.

## Priorités issues de cette passe

- 🔴 **SAÉ ↔ Ressource** : cardinalité V3 à mesurer avant choix du modèle V4.
- 🟠 **fichiers physiques des justificatifs** : métadonnée migrée, fichier non copié.
- 🟠 **Rattrapage.salle** : chaîne V3 non transposée vers l'entité Salle.
- 🟠 **HRS.departement** : vérifier les occurrences où ce contexte ne peut pas être déduit.
- 🟢 **Evaluation.typeGroupe** : corrigé.
- 🟢 **HRS.commentaire** : corrigé.


---

# 18. Passe utilisateurs, affectations et scolarités

## Etudiant

La passe complète inclut maintenant les champs hérités de `Utilisateur` et les champs propres à `Etudiant`.

Déjà repris avant cette passe : identifiant V3, username, mails, nom/prénom, photo, numéros étudiant/INE, année de bac, boursier, aménagements, promotion, année de sortie, bac, EduSign, naissance, téléphones, lieu de naissance, sites et adresses étudiante/parentale.

Correction appliquée : cinq champs administratifs V3 avaient encore une valeur fonctionnelle et aucun emplacement V4 :
- `demandeurEmploi`
- `loginSpecifique`
- `formationContinue`
- `intituleSecuriteSociale`
- `adresseSecuriteSociale`

Ils ont été ajoutés à l'entité V4 et au migrateur.

Relations V3 non recopiées directement mais reconstruites ailleurs : semestre courant, groupes, scolarités, notes, absences, rattrapages, stages.

Le `departement` direct V3 n'est pas recopié dans Etudiant : en V4 le contexte départemental est porté par la scolarité. C'est cohérent avec l'historisation multi-année.

⚠️ Champs hérités de `Utilisateur` non présents en V4 : `civilite`, `remarque`, `signature`, `visible`, `slug`, `typeUser`. Ils doivent être arbitrés. `slug/typeUser` paraissent techniques V3 ; `civilite/remarque/visible` peuvent encore être fonctionnels. Le mot de passe/resetToken ne doivent pas être repris sans besoin explicite puisque l'authentification V4 est différente.

Les étudiants `deleted=true` restent importés pour l'intégrité historique ; aucune désactivation V4 n'existe encore.

## Personnel

Déjà repris : oldId, username, mails, nom/prénom, photo, année universitaire, statut, poste, téléphone bureau, responsabilités, domaines, entreprise, bureaux fusionnés, Harpège, initiales, service, sites et EduSign.

Relations historiques ou métier gérées ailleurs : départements/droits, évaluations, prévisionnels, HRS, stages.

⚠️ Données V3 propres au personnel non encore reprises :
- `couleur` ;
- `configuration` ;
- `signatureElectronique` ;
- `accessOriginaux`.

Elles nécessitent un arbitrage fonctionnel : `configuration` est probablement une ancienne préférence UI et ne doit pas être migrée aveuglément ; `accessOriginaux` peut désormais relever des permissions Document ; `signatureElectronique` doit être comparée au mécanisme V4 ; `couleur` peut être utile à l'EDT.

Comme pour Etudiant, les champs hérités `civilite/remarque/signature/visible` ne sont pas présents en V4 et restent à arbitrer.

Les personnels `deleted=true` sont conservés pour les références historiques.

## PersonnelDepartement

La relation V3 est correctement transformée en `StructureDepartementPersonnel` :
- personnel ;
- département ;
- défaut ;
- rôles → permissions ;
- affectation active ;
- package `intranet`.

⚠️ `annee` existe dans l'entité V3 mais n'est pas sélectionné/migré. Le modèle V4 d'affectation n'est pas historisé par année. Il faut vérifier la distribution réelle de ce champ avant de décider s'il s'agit d'une perte fonctionnelle ou d'un reliquat V3.

Les rôles sans équivalent restent volontairement diagnostiqués. En particulier les rôles propres à des bundles ne doivent pas être transformés en permissions Core arbitraires.

## Bac

`libelle`, `libelleLong`, `codeApogee` et oldId sont migrés.

🔴 `typeBac` V3 n'est actuellement ni sélectionné ni représenté dans `ScolBac`. C'est un vrai point à traiter si cette classification est encore utilisée.

## Scolarite

La V3 stocke une ligne par semestre. La V4 sépare :
- `EtudiantScolarite` : contexte annuel ;
- `EtudiantScolariteSemestre` : contexte semestriel.

Transformations correctes :
- étudiant/année/département → scolarité annuelle ;
- semestre → scolarité semestrielle ;
- moyenne et nombre d'absences du semestre → détail semestriel ;
- total des absences → annuel ;
- `diffuse` → visibilité annuelle ;
- commentaire → annuel ;
- décision, moyennes matières et UE → détail semestriel via `ScolariteDetailsMigrator`.

⚠️ `proposition` est encore non migrée : V3 utilise un champ libre alors que V4 attend une relation `StructureSemestre`. Le migrateur diagnostique ces occurrences sans inventer de correspondance.

⚠️ `rang` est sélectionné dans `ScolariteDetailsMigrator` mais n'est pas affecté. V4 ne possède pas de champ rang dans `EtudiantScolariteSemestre`. **P1 à arbitrer**.

⚠️ `scolaritePromo` n'est pas migré et n'a pas été identifié comme équivalent V4 dans cette passe.

## Groupes étudiants

La relation V3 `etudiant_groupe` n'étant pas historisée par année, la migration la rattache au snapshot/sc1olarité semestrielle compatible le plus récent. C'est une reconstruction explicite, avec diagnostics détaillés pour les incohérences V3.

Les 802 anomalies déjà observées doivent donc être considérées comme anomalies de données source/résolution, et non comme champs oubliés silencieusement.

## Points prioritaires de cette passe

- 🔴 `Bac.typeBac` : équivalent V4 absent.
- 🟠 `Scolarite.rang` : lu mais non affecté.
- 🟠 `Scolarite.proposition` : nécessite un resolver explicite vers StructureSemestre.
- 🟠 `PersonnelDepartement.annee` : vérifier si l'historisation des affectations est nécessaire.
- 🟠 champs communs Utilisateur `civilite/remarque/visible` : arbitrage fonctionnel.
- 🟠 Personnel `couleur/configuration/signatureElectronique/accessOriginaux` : arbitrage par fonctionnalité.
- 🟢 champs administratifs Etudiant manquants : ajoutés et migrés.


---

# 19. Passe transversale des migrateurs

Une passe supplémentaire a été effectuée en recherchant en priorité :
- colonnes V3 sélectionnées mais non affectées ;
- propriétés V3 fonctionnelles jamais sélectionnées ;
- options V3 alors que la cible utilise `OptionTrait` ;
- valeurs de fallback silencieuses ;
- relations V3 volontairement ou accidentellement ignorées.

## Corrections issues directement de cette passe

### StructureAnnee.optAlternance
`Annee.optAlternance` avait un équivalent exact dans `StructureAnnee::configureOptions()` mais n'était pas sélectionné par le migrateur.

✅ Corrigé : `opt_alternance → opt[alternance]`.

### StructureTypeDiplome
Trois propriétés structurelles V3 étaient absentes de V4 :
- `nbSemestres`
- `niveauEntree`
- `niveauSortie`

Elles décrivent le type de diplôme lui-même et ne sont pas de simples données de migration. Elles ont donc été réintroduites comme colonnes V4 et sont maintenant migrées.

La relation `mccTypeEpreuves` reste séparée : elle relève du modèle MCC et ne doit pas être recréée comme simple champ de StructureTypeDiplome.

### Bac.typeBac
La classification V3 `g/t/p/e/a` était perdue.

✅ Ajout de `ScolBac.typeBac` nullable et migration de la valeur historique. La valeur est conservée telle quelle afin de ne pas introduire une nouvelle enum uniquement pour la migration ; une enum V4 pourra être ajoutée ensuite si le domaine l'utilise encore.

### Scolarite.rang
`rang` était le cas typique d'un champ présent dans le SELECT mais jamais consommé.

✅ Ajout de `EtudiantScolariteSemestre.rang` nullable et migration directe.

## Champs SELECT volontairement non affectés

La présence d'une colonne dans un SELECT n'implique pas nécessairement une perte :

- `Diplome.actif` : V4 n'a pas d'état actif sur StructureDiplome ; les diplômes inactifs sont nécessaires aux snapshots historiques et sont comptabilisés explicitement.
- `Groupe.parcours/apc_parcours_id` : détectés et comptabilisés, mais aucune relation n'est créée tant que la correspondance snapshot/APC n'est pas sûre.
- `Groupe.id_edu_sign` : détecté, pas d'équivalent V4 dans StructureGroupe.
- `UE.coefficient` : reste un point ouvert ; StructureUe contient d'ailleurs un TODO historique `add coeff. ?`. La donnée doit être arbitrée avant ajout.
- `UE.apc_competence_id` : volontairement traité dans `ApcUeLinkMigrator`.
- `Scolarite.proposition` : volontairement diagnostiquée sans conversion arbitraire.

## Fallbacks à surveiller

Les conversions suivantes sont explicites mais méritent un contrôle des valeurs réelles en dry-run :
- type de groupe inconnu → `TYPE_GROUPE_AUTRE` avec compteur ;
- décisions scolarité `V/VCJ/VCA → true`, `NV/DEF → false`, autres → null ;
- certains enums historiques Stage/EDT/APC disposent également de conversions de compatibilité documentées dans leurs migrateurs.

La règle finale est qu'un fallback ne doit jamais être silencieux : toute valeur source inconnue doit soit être comptée/reportée, soit provoquer un skip contrôlé lorsque le fallback changerait la sémantique métier.

## Points encore ouverts après cette passe

### P0/P1
- SAÉ ↔ Ressource : cardinalité réelle V3.
- UE.coefficient : déterminer si la donnée doit revenir dans StructureUe.
- Scolarite.proposition : résolution explicite.
- PersonnelDepartement.annee : utilité réelle de l'historisation.
- fichiers physiques justificatifs.
- StageEtudiant : diagnostic des 13 updates/UUID.
- reset Stage sur Entreprise/Contact partagés.

### P1/P2
- Groupe parcours/APC et EduSign.
- Personnel accessOriginaux → permission Document.
- Personnel couleur/signatureElectronique.
- champs communs Utilisateur civilité/remarque/visible.
- MCC TypeDiplome : migration à traiter avec le futur modèle MCC plutôt que dans la structure.
- lifecycle dates et fichiers physiques secondaires.

## État de la couverture

À ce stade, les pertes restantes identifiées sont désormais majoritairement :
1. des **choix de modèle V4 à arbitrer** ;
2. des **relations dont la cardinalité ou la sémantique diffère** ;
3. des **fichiers physiques** ;
4. des **données historiques volontairement hors périmètre**.

Les oublis directs simples détectés lors des passes précédentes ont été corrigés au fil de l'audit.


---

# 20. Résolution des pertes structurelles UE / propositions

## UE.coefficient

Le doute de la passe précédente est levé : `UE.coefficient` est une donnée portée directement par l'UE V3 et elle est distincte du coefficient d'un enseignement dans une UE (`ScolEnseignementUe.coefficient`).

Le modèle V4 avait d'ailleurs conservé un TODO explicite sur ce manque.

✅ Correction :
- ajout de `StructureUe.coefficient: float` ;
- exposition dans les groupes API UE/maquette ;
- migration directe de `ue.coefficient` ;
- migration Doctrine associée.

Il n'y a donc plus de perte identifiée sur ce champ.

## Scolarite.proposition

L'analyse des usages V3 confirme que `Scolarite.proposition` est une chaîne libre utilisée comme proposition d'orientation. Dans l'interface de fin de semestre V3, la valeur est comparée au **libellé du semestre d'arrivée**.

Le modèle V4 `EtudiantScolariteSemestre.proposition` est donc cohérent : relation vers `StructureSemestre`.

✅ Un resolver explicite a été ajouté au migrateur :
- recherche uniquement dans le **même snapshot PN annuel** ;
- comparaison normalisée du libellé V3 avec `StructureSemestre.libelle` ;
- `?`, `E.C.` et `EC` sont considérés comme absence de proposition exploitable ;
- aucune correspondance approximative ou inter-snapshot n'est autorisée ;
- les propositions résolues et non résolues sont comptabilisées dans le rapport.

Cette stratégie permet de migrer les propositions sans fabriquer de relation ambiguë.

⚠️ Le dry-run final devra fournir la distribution des valeurs non résolues. Si certaines correspondent à des variantes historiques connues des libellés de semestre, elles pourront être ajoutées à un resolver/config explicite plutôt que via du fuzzy matching.

## Distinction proposition semestrielle / annuelle

V4 contient également `EtudiantScolarite.proposition → StructureAnnee`. Ce champ représente une proposition au niveau annuel et ne doit pas recevoir automatiquement la chaîne `Scolarite.proposition` V3 : la donnée source auditée est attachée à une ligne semestrielle et les usages V3 observés ciblent un semestre d'arrivée.

Aucune duplication artificielle n'est donc faite vers la proposition annuelle.

## État après correction

Les deux pertes structurelles identifiées lors de la passe transversale sont maintenant traitées :
- 🟢 `UE.coefficient` : ajouté et migré ;
- 🟢 `Scolarite.rang` : ajouté et migré lors de la passe précédente ;
- 🟢 `Scolarite.proposition` : resolver snapshot-aware ajouté ;
- 🟢 `Bac.typeBac` : ajouté et migré ;
- 🟢 `Annee.optAlternance` : migré vers OptionTrait.

Les prochains points réellement structurants restent principalement :
- `PersonnelDepartement.annee` et la question de l'historisation des affectations ;
- `Personnel.accessOriginaux` vers les permissions du bundle Document ;
- relations Groupe ↔ parcours/APC ;
- SAÉ ↔ Ressource ;
- fichiers physiques et sécurisation des resets Stage/Document.


---

# 21. Initialisation des données propres à UniServices V4

Les données nécessaires à une installation V4 mais qui ne proviennent pas réellement d'une entité V3 sont désormais séparées des migrateurs.

## Architecture

Un mécanisme générique d'initialisation a été ajouté hors du namespace `Migration/IntranetV3` :

- `App\Initialization\InitializerInterface`
- `App\Initialization\InitializationRunner`
- `App\Initialization\EtablissementInitializer`

Les initializers sont enregistrés automatiquement avec le tag `app.initializer`.

Lors d'une migration V3 complète, l'ordre devient :

1. reset éventuel ;
2. initialisation des données V4 ;
3. migration V3 → V4 ;
4. contrôles d'intégrité.

Une migration nommée ne rejoue pas les initializers : ceux-ci sont destinés au bootstrap global.

## Etablissement

`EtablissementInitializer` crée ou met à jour l'établissement principal de façon idempotente.

La recherche se fait d'abord sur `isMain=true`, puis sur le libellé configuré. L'initializer ne crée donc pas un établissement supplémentaire à chaque exécution.

Les données sont définies par la configuration `app.establishment`, elle-même alimentée par des variables d'environnement :

- `ETABLISSEMENT_LIBELLE`
- `ETABLISSEMENT_SITE_WEB`
- `ETABLISSEMENT_TELEPHONE`
- `ETABLISSEMENT_ADRESSE`
- `ETABLISSEMENT_ADRESSE_COMPLEMENT1`
- `ETABLISSEMENT_ADRESSE_COMPLEMENT2`
- `ETABLISSEMENT_VILLE`
- `ETABLISSEMENT_CODE_POSTAL`
- `ETABLISSEMENT_PAYS`

Les valeurs par défaut du projet initialisent actuellement l'Université de Reims Champagne-Ardenne. Les données spécifiques à une autre installation peuvent être remplacées par l'environnement sans modifier le code.

## Dry-run

L'initializer respecte `MigrationContext::dryRun` via une transaction dédiée lorsqu'il est exécuté seul avant le runner de migration. Les données sont flushées afin de détecter les contraintes Doctrine/SQL, puis rollbackées.

## Principe retenu

Une donnée doit aller dans un **migrateur** si sa source de vérité est la V3 et qu'elle doit être transformée.

Une donnée doit aller dans un **initializer** si elle est requise par UniServices V4 mais relève de la configuration/du bootstrap de l'installation.

Cette séparation doit être conservée pour les futures données obligatoires V4 absentes de la V3.


## Reset de la base cible

Le comportement de `--reset-db` a été clarifié : cette option effectue désormais **uniquement le reset puis termine la commande**. Elle ne lance plus automatiquement l'initialisation ni la migration V3.

Séquence recommandée :

```bash
php bin/console app:migrate-intranet-v3 --reset-db
php bin/console app:migrate-intranet-v3
```

Le reset MySQL/MariaDB utilise `TRUNCATE`, qui nécessite un metadata lock. Une connexion ou transaction encore active (application, worker, outil SQL, etc.) pouvait donc faire paraître la console figée sans produire d'erreur.

Le reset configure maintenant un `SESSION lock_wait_timeout` court (5 secondes). En cas de verrou concurrent, la commande échoue donc avec une erreur exploitable au lieu d'attendre indéfiniment.

Avec `-v`, la commande affiche également chaque table juste avant son `TRUNCATE`, ce qui permet d'identifier immédiatement la table sur laquelle un verrou est rencontré.


---

# 22. Recette migration complète — premier run réel

Bilan reçu après reset + migration complète :
- initialisation établissement : OK ;
- types diplômes, départements, APC, diplômes, années universitaires, PN et années : créations cohérentes ;
- `semestres` : **672 échecs / 672** ;
- `etudiants` : **5974 créés / 674 échecs** ;
- de nombreuses migrations aval sont ensuite à 0 création ou entièrement skipped.

## Cause racine identifiée — semestres

Le SELECT du `SemestreMigrator` expose les colonnes :
- `opt_dest_mail_releve_id`
- `opt_dest_mail_modif_note_id`
- `opt_dest_mail_absence_resp_id`

mais le mapping lisait respectivement les clés sans suffixe `_id`.

Cela provoquait une exception sur **chaque semestre** avant persist.

✅ Corrigé.

Conséquence importante : une grande partie des résultats suivants n'est **pas interprétable comme une anomalie de données** sur ce run. Sans semestre cible :
- aucune UE ;
- aucune ressource/SAÉ/matière ;
- aucune scolarité semestrielle ;
- aucun rattachement étudiant-groupe ;
- aucune évaluation ;
- aucune note ;
- prévisionnels et autres données dépendantes sont skipped/non créés.

Ces compteurs devront être réévalués après un nouveau reset + run complet.

## Ordonnancement APC ↔ Diplômes

`apc-diplome-liens` s'exécutait avant `diplomes`. Les 63 lignes étaient donc skipped puisque le `StructureDiplome` cible n'existait pas encore.

✅ `ApcDiplomeLinkMigrator` dépend maintenant explicitement de `DiplomeMigrator`.

Les 63 skips observés sur ce run ne constituent donc pas une perte de données source.

## Etudiants

Les **674 échecs étudiants** sont indépendants de la panne des semestres et restent à diagnostiquer.

Le migrateur capture déjà le message exact par étudiant. Au prochain run, exécuter la commande avec `-v` permettra de récupérer ces messages dans le bilan détaillé.

Ce point est désormais **P0 recette** : il faut identifier si les 674 lignes partagent une contrainte cible (username/mail, valeur de date, longueur, enum, champ obligatoire, doublon, etc.) avant de considérer la migration utilisateurs comme validée.

## Lecture du prochain run

Le prochain run complet doit d'abord vérifier les invariants suivants :

1. `semestres` : créations > 0 et failed = 0 ;
2. `ues`, `matieres`, `apc-ressources`, `apc-sae` : ne doivent plus rester artificiellement à zéro ;
3. `apc-diplome-liens` : les 63 liens doivent être traités après les diplômes ;
4. `scolarites`, `evaluations`, `notes` : les énormes volumes de skips doivent fortement diminuer ;
5. `etudiants` : analyser séparément les 674 échecs persistants.

Le bilan de ce premier run est donc utile principalement comme **test d'intégration de l'ordre des migrateurs et des dépendances structurelles**, mais ne doit pas encore servir de bilan de couverture final.


## Optimisation mémoire après correction des semestres

Le premier run avec les semestres réellement créés augmente fortement le graphe Doctrine chargé : les snapshots PN clonent années puis semestres et les associations bidirectionnelles peuvent conserver les objets dans l'UnitOfWork jusqu'à la fin d'un migrateur.

Une passe mémoire a donc été ajoutée :

- `AnneeMigrator` et `SemestreMigrator` ne chargent plus toutes les entités parentes avec `findAll()` ; ils parcourent d'abord uniquement leurs IDs ;
- flush + `EntityManager::clear()` tous les `BATCH_SIZE=200` descendants créés ;
- rechargement du parent après un clear lorsque nécessaire ;
- clear explicite après chaque parent snapshot ;
- `MigrationRunner` libère systématiquement l'EntityManager et lance `gc_collect_cycles()` entre deux migrateurs.

Objectif : rendre la consommation mémoire approximativement bornée par la taille d'un batch plutôt que par la totalité du graphe structurel déjà migré.

Cette optimisation est préférable à une simple augmentation du `memory_limit`, qui masquerait la rétention Doctrine sans corriger sa cause.
