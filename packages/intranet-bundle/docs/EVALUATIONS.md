# Processus de gestion des evaluations

## Avant toute chose

Les évaluations sont liées aux enseignements. L'import de la maquette pédagogique engendre automatiquement la création des évaluations associées en prenant en compte l'information : nombre de notes d'une matière.
Ces évaluations sont ensuite à initialiser afin de permettre la saisie des notes et la publication des résultats.

## États des évaluations

Une évaluation va passer par differents états :

- non initialisée : l'évaluation n'a pas encore été initialisée. On ne peut rien faire d'autre que l'initialiser.
- initialisée : l'évaluation a été initialisée. Un ou plusieurs enseignants lui ont été attribués. On peut modifier l'évaluation ou l'annuler
- planifiee : l'évaluation est planifiée. On peut saisir les notes, modifier l'évaluation, l'annuler.
- completee : les notes ont été saisies. On peut modifier les notes, modifier l'évaluation, l'annuler, voir les statistiques et planifier la publication des résultats aux étudiants."
- publiee : les résultats ont été publiés. On ne peut plus modifier les notes ni l'évaluation.
- annulee : l'évaluation est annulée. On ne peut plus la modifier, ni saisir des notes, ni publier les résultats. On peut juste supprimer l'évaluation ou la réactiver.

## Étapes pour l'enseignant responsable

1. L'initialisation
   Ici plusieurs possibilités : l'enseignant responsable de l'enseignement ou bien un enseignant habilité à gérer les évaluations peut initialiser l'évaluation. Il va devoir assigner les personnels autorisés à saisir les notes, le type de l'évaluation, le coefficient, le type de groupes (CM/TD/TP).
   Il n'est pas obligé de saisir une date au moment de l'initialisation, cela peut être fait à posteriori.

2. La planification (si pas déjà faite à l'initialisation)
   On fixe la date de l'évaluation.

3. La saisie
   On saisit les notes.

4. La publication
   On publie les résultats aux étudiants.

## Gestion des évaluations

Dans la page qui liste les évaluations le responsable des évals peut indiquer qu'une évaluation est masquée aux étudiants ou bien qu'elle est non-modifiable (dans ce cas, seul l'admin peut modifier) ce qui empêche la modification des notes et de l'évaluation.
Une page de statistiques permet de voir la répartition des résultats.

## L'enseignant intervenant

Un enseignant qui n'a pas de responsabilité particulière dispose d'une page où sont listées uniquement les évaluations auxquelles il a été assigné. Il peut alors saisir les notes et voir les statistiques.

## L'étudiant

Un étudiant dispose d'une page où sont listées uniquement les évaluations qui le concernent. Il peut alors consulter les évaluations passées et à venir, ses notes et les commentaires associés, et les statistiques qui le concernent.

## Évaluation Annulée

Lorsqu'une évaluation est annulée, on désactive la saisie des notes et la publication des résultats. Cependant, on garde un historique des notes qui avaient été saisies. Les étudiants ne voient plus les notes. On désactive aussi la possibilité de modifier l'évaluation. On permet juste de réactiver l'évaluation et donc on la re-initialise (elle passe à l'état initialisée).

## Prise en compte des notes dans le calcul des moyennes

Seules les notes publiées sont prises en compte dans le calcul des moyennes. Si une évaluation est annulée, ses notes ne sont plus prises en compte dans le calcul des moyennes. Si une évaluation est réactivée, ses notes sont de nouveau prises en compte dans le calcul des moyennes. Si les notes d'une évaluation ne sont pas publiées, elles ne seront pas prises en compte dans le calcul des moyennes. Dans ce cas, on met la mention "Absence" à la place.
