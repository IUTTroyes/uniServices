export const tickets = [
    {
        id: 1,
        subject: 'Problème de vidéoprojecteur - Salle B204',
        author: 'Jean Dupont',
        category: 'Matériel informatique',
        statut: 'Nouveau',
        priority: 'Haute',
        desc: 'Bonjour,\n\nJe me permets de vous solliciter car le vidéoprojecteur de la salle B204 est totalement inutilisable ce matin. \n\nLors de la mise sous tension, l\'appareil émet un sifflement léger et le voyant "Status" clignote rapidement en rouge. J\'ai tenté les manipulations suivantes :\n1. Débranchement et rebranchement du câble d\'alimentation.\n2. Vérification de la source HDMI sur l\'ordinateur de la salle.\n3. Remplacement des piles de la télécommande.\n\nMalgré cela, aucune image n\'est projetée. Plusieurs cours de TD sont prévus dans cette salle aujourd\'hui, ce qui rend la situation critique pour les enseignants concernés.\n\nMerci par avance pour votre intervention rapide.\n\nCordialement,\nM. Dupont.',
        attachment: 'photo_erreur.jpg'
    },
    {
        id: 2,
        subject: 'Demande de badges pour les nouveaux arrivants',
        author: 'Marie Lemoine',
        category: 'Scolarité',
        statut: 'En cours',
        priority: null,
        desc: 'Bonjour l\'équipe technique,\n\nDans le cadre de l\'accueil de trois nouveaux intervenants extérieurs lundi prochain, nous aurions besoin de la création de trois badges d\'accès temporaires.\n\nLes accès requis sont les suivants :\n- Entrée principale du bâtiment A\n- Salle des professeurs\n- Parking souterrain\n\nLes noms des intervenants vous ont été envoyés par mail hier via le secrétariat. Pourriez-vous nous confirmer dès que les badges sont encodés et disponibles à l\'accueil ?\n\nMerci pour votre aide habituelle.\n\nBonne journée,\nLe service Scolarité.',
        attachment: 'liste_intervenants.pdf'
    },
    {
        id: 3,
        subject: 'Fuite d\'eau importante - Sanitaires 2ème étage',
        author: 'Pierre Durand',
        category: 'Maintenance',
        statut: 'Urgent',
        priority: 'Critique',
        desc: 'ATTENTION : URGENCE SIGNALÉE.\n\nUne fuite d\'eau majeure vient d\'être détectée dans les sanitaires du personnel situés au deuxième étage du bâtiment principal, juste à côté de l\'ascenseur.\n\nL\'eau commence à s\'écouler dans le couloir et risque d\'atteindre les bureaux administratifs si rien n\'est fait rapidement. Nous avons placé des seaux en attendant, mais le débit semble augmenter. Il est probablement nécessaire de couper l\'arrivée d\'eau générale de l\'aile Est pour éviter des dégâts des eaux plus importants sur le faux plafond du premier étage.\n\nMerci de dépêcher un technicien sur place immédiatement.',
        attachment: 'photo_degat_des_eaux.png'
    },
    {
        id: 4,
        subject: 'Dysfonctionnement Wi-Fi - Parc de tablettes (Classe Mobile)',
        author: 'Sophie Martin',
        category: 'Réseau',
        statut: 'En attente',
        priority: '',
        desc: 'Bonjour,\n\nDepuis la mise à jour des certificats de sécurité effectuée mercredi dernier, les 15 tablettes de la classe mobile n\'arrivent plus à se connecter au réseau Wi-Fi "IUT-STUDENT".\n\nLe message d\'erreur suivant s\'affiche sur chaque appareil : "Erreur d\'authentification : certificat invalide". \n\nNous avons essayé de "réinitialiser les paramètres réseau" sur l\'une des tablettes mais le problème persiste. Il semblerait que le nouveau protocole ne soit pas correctement poussé par le serveur de gestion de flotte (MDM).\n\nSerait-il possible de passer au bureau 102 pour vérifier la configuration du serveur ? Sans Wi-Fi, les étudiants ne peuvent pas accéder aux ressources pédagogiques en ligne.\n\nMerci d\'avance.',
        attachment: 'logs_connexion.txt'
    },
    {
        id: 5,
        subject: 'Clavier défectueux - Poste secrétariat',
        author: 'Lucie Bernard',
        category: 'Matériel informatique',
        statut: 'Traité',
        priority: 'Basse',
        desc: 'Le clavier du poste principal du secrétariat ne répond plus correctement. Plusieurs touches (Entrée, Espace) sont bloquées. Serait-il possible de le remplacer par un modèle identique ?',
        attachment: null
    },
    {
        id: 6,
        subject: 'Demande d\'installation Logiciel spécifique (Adobe Suite)',
        author: 'Marc Lefebvre',
        category: 'Logiciel',
        statut: 'En cours',
        priority: 'Moyenne',
        desc: 'Bonjour, j\'ai besoin de la suite Adobe Creative Cloud pour les cours de communication visuelle qui débutent le mois prochain en salle multimédia. Pourriez-vous vérifier les licences disponibles ?',
        attachment: 'liste_etudiants.xlsx'
    },
    {
        id: 7,
        subject: 'Panne d\'ascenseur Bâtiment C',
        author: 'Jean-Claude Van',
        category: 'Maintenance',
        statut: 'Nouveau',
        priority: 'Critique',
        desc: 'L\'ascenseur du bâtiment C est bloqué au 3ème étage. Personne n\'est à l\'intérieur, mais cela pose un problème majeur pour l\'accessibilité PMR des étudiants ce matin.',
        attachment: null
    },
    {
        id: 8,
        subject: 'Oubli de mot de passe ENT',
        author: 'Thomas Roche',
        category: 'Réseau',
        statut: 'Traité',
        priority: null,
        desc: 'Je n\'arrive plus à me connecter à mon espace numérique de travail depuis ce matin. Le message "identifiants invalides" s\'affiche systématiquement. Pouvez-vous réinitialiser mon compte ?',
        attachment: null
    },
    {
        id: 9,
        subject: 'Remplacement de néons - Amphi 1',
        author: 'Claire Petit',
        category: 'Maintenance',
        statut: 'En attente',
        priority: 'Basse',
        desc: 'Trois néons clignotent de manière très gênante au-dessus du pupitre de l\'Amphi 1. Cela fatigue énormément les intervenants durant les conférences.',
        attachment: 'photo_plafond.jpg'
    },
    {
        id: 10,
        subject: 'Problème de son - Salle de visioconférence',
        author: 'Robert Durand',
        category: 'Matériel informatique',
        statut: 'Nouveau',
        priority: 'Haute',
        desc: 'Le micro pieuvre de la salle de visio ne transmet plus de son vers les interlocuteurs distants. Nous avons une réunion importante avec l\'étranger à 14h.',
        attachment: null
    },
    {
        id: 11,
        subject: 'Mise à jour de la liste d\'émergement numérique',
        author: 'Alice Vasseur',
        category: 'Scolarité',
        statut: 'En cours',
        priority: null,
        desc: 'Les nouveaux inscrits en licence pro n\'apparaissent pas sur les tablettes d\'émargement. Pouvez-vous synchroniser la base de données Apogée avec le logiciel de présence ?',
        attachment: 'nouveaux_inscrits.csv'
    },
    {
        id: 12,
        subject: 'Tentative de phishing signalée',
        author: 'Securite Info',
        category: 'Réseau',
        statut: 'Urgent',
        priority: 'Critique',
        desc: 'Plusieurs personnels ont reçu un mail frauduleux demandant leurs coordonnées bancaires sous couvert d\'une mise à jour de salaire. Une communication globale est nécessaire.',
        attachment: 'capture_mail_frauduleux.png'
    },
    {
        id: 13,
        subject: 'Commande de toner pour imprimante Bureau 201',
        author: 'Hélène Joly',
        category: 'Matériel informatique',
        statut: 'En attente',
        priority: 'Moyenne',
        desc: 'L\'imprimante laser du bureau 201 indique "Toner bas". Il reste environ 50 pages selon l\'interface. Pourriez-vous nous livrer une cartouche de remplacement ?',
        attachment: null
    },
    {
        id: 14,
        subject: 'Chauffage trop élevé - Bibliothèque',
        author: 'Gérard Dubois',
        category: 'Maintenance',
        statut: 'Nouveau',
        priority: '',
        desc: 'Il fait plus de 26 degrés dans la zone d\'étude de la bibliothèque. Les étudiants se plaignent de la chaleur. Le thermostat semble ne plus répondre.',
        attachment: null
    }
]