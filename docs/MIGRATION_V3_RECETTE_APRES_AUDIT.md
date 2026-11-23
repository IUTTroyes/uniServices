# Recette du transfert Intranet V3 → UniServices V4 (audit du 20/11/2026)

Référence : [scriptTransfertOverview.md](scriptTransfertOverview.md). Branche de correction :
`fix/migration-v3-integrite-2026` ; PR #280.

## Invariants

- Source : V3 en lecture seule (`doctrine.dbal.copy_connection`) ; la seule année `active = 1` doit être **2026–2027**.
- V4 ne fabrique pas de maquettes historiques ; les PN et les données opérationnelles détaillées commencent en 2026–2027.
- Une seule `etudiant_scolarite` par couple (`etudiant_id`, `annee_universitaire_id`).
- Pour les quatre clés source répétées étudiant–année–semestre du dump audité, la ligne V3 d'identifiant le plus récent est retenue **avant** l'agrégation des absences et des moyennes.
- Les résultats consolidés des étudiants sortis restent migrés ; les notes détaillées sont réservées aux étudiants V3 `annee_sortie = 0`.
- Toutes les évaluations de 2026–2027 sont conservées, même sans note, pour préserver la saisie en cours.
- Les absences actives sans EDT sont conservées avec `event_id = NULL` et `legacy_context` (date, matière, semestre), sans faux cours planifié.
- Prévisionnel V3 : répétitions strictement identiques neutralisées ; données contradictoires pour une même clé non fusionnées, avec diagnostic.
- La migration reste explicite pour chaque bundle ; les commandes Stage et Document ne sont pas exécutées par la commande Core.

## Préparation sur une copie dev/test

1. Sauvegarder les bases V3 et V4 ainsi que les fichiers ; vérifier la connexion `DATABASE_URL_ORIGINAL`.
2. Sur une base V4 déjà contaminée par une ancienne recette, **réinitialiser avant** de lancer la nouvelle migration Doctrine ajoutant l'index unique :
   ```bash
   APP_ENV=dev php bin/console app:migrate-intranet-v3 --reset-db --force
   ```
   Cette commande efface la base applicative cible **et s'arrête**. Jamais en production.
3. Appliquer les migrations et vérifier le mapping :
   ```bash
   php bin/console doctrine:migrations:migrate
   php bin/console doctrine:schema:validate --skip-sync
   vendor/bin/phpunit --testsuite Core
   ```
4. Effectuer l'import en plusieurs étapes avec journal :
   ```bash
   php bin/console app:migrate-intranet-v3 --list
   php bin/console app:migrate-intranet-v3 --dry-run --no-progress -v
   php bin/console app:migrate-intranet-v3 --no-progress -v
   php bin/console app:migrate-intranet-v3 --check
   ```
   Ne pas lancer `--reset-db` en pensant qu'il réalise aussi l'import.
5. Migrer et contrôler les bundles séparément :
   ```bash
   php bin/console app:migrate-intranet-v3-stage --dry-run -v
   php bin/console app:migrate-intranet-v3-stage
   php bin/console app:migrate-intranet-v3-stage --check

   php bin/console app:migrate-intranet-v3-document --dry-run -v
   php bin/console app:migrate-intranet-v3-document
   php bin/console app:migrate-intranet-v3-document --check
   ```
   Les fichiers Document se copient séparément avec `--files-source` et `--files-target`.

## Contrôles prioritaires

| Objet | Requête / critère |
| --- | --- |
| Dossiers annuels en double | `SELECT etudiant_id, annee_universitaire_id, COUNT(*) FROM etudiant_scolarite GROUP BY etudiant_id, annee_universitaire_id HAVING COUNT(*) > 1` → aucun |
| Notes | Comparer le nombre de notes éligibles V3 (étudiant non sorti) avec `etudiant_note` et les diagnostics techniques ; une différence non expliquée est un échec |
| Scolarités semestrielles | Moyennes, rangs, décisions, `legacy_context.resultatsV3`, unicité par semestre |
| Groupes | Vérifier les groupes sans semestre, puis le TD S4 GEMA FI : pas de GCFF, GPRH ni GEMA ALT injustifiés |
| Prévisionnels | Vérifier les sommes d'heures et groupes, les conflits V3 et la présence des enseignants |
| Calendrier | En 2026–2027, semaine n°1 : lundi 24 août 2026 dans le dump audité |
| Absences | Une absence V3 active sans EDT ne doit plus être ignorée ; le contexte et l'heure source doivent être consultables |
| Justificatifs | Vérifier les dates de validité et les pièces ; la date de fin du justificatif ne doit pas écraser `date_justifie` de l'absence |
| Stage et Document | Comparer séparément les lignes actives/catégories, fichiers physiques et accès effectifs |

## Arbitrages ou développements à poursuivre après cette passe

1. **Prévisionnels contradictoires** : la V3 ne permet pas d'identifier un semestre directement. Ne pas distribuer automatiquement les heures à tous les snapshots ; résoudre par correspondance métier ou données additionnelles.
2. **Droits et applications** : les rôles V3 sans équivalent (`ROLE_STA`, `ROLE_ABS`, `ROLE_EDT`, `ROLE_PRJ`) requièrent une validation de matrice de permissions. Tester réellement une connexion étudiante désactivée.
3. **Stage** : offres toujours hors périmètre ; vérifier quels avenants, rapports et fichiers concernent les seules périodes actives avant d'ajouter leur migration.
4. **Document** : les liaisons V3 document–semestre et favoris individuels ne peuvent pas être imputés à un simple booléen global. Auditer les restrictions d'accès avant publication.
5. **SAÉ–ressources / HRS** : rapprochement à contrôler après synchronisation OréOF ; vérifier que `types-hrs` est bien exécuté et si des HRS 2026–2027 existent dans la source.
6. **Base déjà migrée** : la migration de contrainte unique refuse les anciennes scolarités en double. Ne pas supprimer automatiquement les doublons en production ; un plan de consolidation transactionnel et sauvegardé reste nécessaire.

Les volumes du rapport du 20/11/2026 sont des instantanés. Un transfert réel et un second passage idempotent doivent être exécutés sur une copie complète avant fusion.
