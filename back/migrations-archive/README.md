# Migrations antérieures à la migration initiale

Elles ne sont plus exécutées : le schéma qu'elles produisaient est inclus dans les migrations initiales
(`back/migrations/` pour le cœur, `packages/*/migrations/` pour les packages).

Une base qui ne les a pas toutes reçues doit les appliquer **avant** de passer à cette version
(depuis le commit précédent). La migration initiale du cœur s'arrête si elle détecte un schéma en retard,
mais ce contrôle ne porte que sur `structure_semestre.resultats_version`.
