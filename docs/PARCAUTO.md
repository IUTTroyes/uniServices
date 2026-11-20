# Parc automobile

Le module est accessible depuis UniServices à l’adresse `/app/parcauto`. Il réutilise la connexion de l’application. Les données du parc et du planning sont indépendantes de l’année universitaire sélectionnée.

Le tableau de bord affiche les demandes personnelles, les départs confirmés et les missions en cours. Les gestionnaires voient également les demandes à valider, les restitutions en retard et les motifs de blocage des véhicules. Chaque réservation affichée ouvre le créneau correspondant. La barre latérale donne accès au planning, aux véhicules et équipements, ainsi qu’au pilotage pour les gestionnaires.

## Droits d’accès

| Profil | Opérations |
| --- | --- |
| Personnel avec accès au paquet ParcAuto | Consulter les véhicules, les créneaux occupés et les bornes. Demander ou annuler ses réservations puis relever les compteurs de départ et de retour. |
| Gestionnaire | Valider et annuler les demandes, gérer les fiches et les échéances, consulter le journal, identifier le conducteur d’une mission, accéder au pilotage et exporter. |
| Super administrateur | Mêmes droits de gestion. |

L’activation du paquet `parcauto` sur l’affectation active donne accès au profil demandeur. Le rôle `ROLE_PARCAUTO_GESTION` figure dans le registre de permissions du paquet `parcauto`. Il s’attribue sur l’affectation active via le système de droits UniServices. Les créneaux des autres conducteurs restent anonymes pour le personnel. Les informations des permis restent dans OMER.

## Paramétrage et exploitation

La migration `Version20261120120000` crée les tables du module. Avant d’ouvrir le planning, renseigner les véhicules et leurs échéances, dont le dernier contrôle des pneus et le contrôle technique. Le contrôle des pneus est à renouveler trois mois calendaires après la date saisie. Les autres opérations configurées peuvent cumuler une échéance à date et au compteur.

Un véhicule est bloqué si une échéance est atteinte, si les contrôles obligatoires manquent, s’il est à l’arrêt ou si sa date de réforme est atteinte. Les vérifications sont réalisées à la demande, à la validation et au départ. Deux gestionnaires ne peuvent pas valider en parallèle des créneaux qui se recouvrent pour le même véhicule. Une mission peut être clôturée après l’heure prévue, y compris si une échéance a été franchie pendant le trajet. Les missions déjà validées restent visibles quand un véhicule se bloque. Leur départ est refusé jusqu’à régularisation.

Un véhicule à l’arrêt peut être remis en service depuis le journal après un contrôle renseigné avec le prestataire et une référence. Renouveler les échéances obligatoires lors d’interventions documentées. La remise en service ne neutralise aucun contrôle expiré. Pour suivre un train de pneus, garder le même libellé pour ses mesures inscrire le retrait avec ce libellé et saisir les mesures du nouveau train avec un libellé distinct. Les cartes sans affectation sont volantes. Les factures se saisissent par référence, montant et prestataire, et les achats de carburant dans une entrée distincte. Pour éviter le double comptage, ventiler un achat entre ces deux types de saisie une seule fois.

La recherche d’infraction utilise l’intervalle réel à partir du départ, avec la restitution comme borne exclusive. Une mission dont le retour est en attente reste affichée comme telle. Les heures techniques sont stockées en UTC. Les dates seules sont interprétées dans le fuseau Europe/Paris.

## OMER

Le connecteur peut appeler une passerelle SI avec `PARCAUTO_OMER_URL` (URL HTTPS complète de vérification) et `PARCAUTO_OMER_TOKEN` (jeton serveur). Le contrat attendu par ParcAuto est un POST JSON portant `reference`, `conducteur` (identifiant UniServices), `debut` et `fin` (ISO 8601). La passerelle doit faire la correspondance d’identité et répondre `{"valide":true,"type":"permanent"}` ou `{"valide":true,"type":"ponctuel"}` si l’ordre couvre la mission. Une réponse invalide ou une panne empêche la validation quand le connecteur est activé. Sans URL configurée, le gestionnaire valide la réservation sur la base de la référence fournie. La réservation distingue ces deux cas.

Ce contrat décrit l’interface côté UniServices. La passerelle réelle, le contrat éditeur, la correspondance des identités et les essais avec OMER restent à valider avec le SI de l’URCA.

## Indicateurs et projections

| Indicateur | Calcul |
| --- | --- |
| Utilisation | Durée réelle d’utilisation dans la période, rapportée au temps calendaire de cette période. |
| Rotation et distance | Missions dont le retour est dans la période, avec distance calculée d’après les compteurs. |
| Consommation | Volume des pleins après le premier relevé de la période, rapporté à l’écart entre les premier et dernier compteurs. Cette estimation est pertinente pour des pleins complets. L’écart est signalé à partir de 20 % au-dessus de la référence du véhicule. |
| Coût de possession estimé | Dépenses saisies et, si les dates sont renseignées, part du prix d’achat amortie linéairement entre la mise en service et la date cible de réforme. |
| Coût au kilomètre | Coût estimé divisé par les kilomètres des missions clôturées pendant la période. |
| Réforme | Première échéance entre la date cible renseignée et la date projetée au rythme kilométrique de la période. |
| Usure | Extrapolation d’un seuil de suivi de 2 mm sur les deux derniers relevés comparables du train de pneus. Les échéances d’entretien proches sont aussi signalées. |
| Permutations | Suggestions de véhicules de même énergie sur des sites différents, à partir d’un écart d’utilisation de 20 points et d’au moins cinq missions pour le véhicule le plus sollicité. |

Pour suivre un incident mécanique récurrent, conserver le même libellé de symptôme. Deux incidents portant ce libellé sur 90 jours déclenchent une alerte dans le rapport.

Les projections reposent sur les données saisies. La prédiction de pannes mécaniques nécessite des historiques d’incidents et des données de diagnostic supplémentaires. L’export CSV reprend les indicateurs par véhicule sur la plage chargée dans le tableau de bord.
