-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : lun. 30 mars 2026 à 13:35
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `shiftup_db`
--

-- --------------------------------------------------------

--
-- Structure de la table `badges`
--

CREATE TABLE `badges` (
  `id` int(11) NOT NULL,
  `nom_fr` varchar(50) NOT NULL,
  `nom_en` varchar(50) NOT NULL,
  `descr_fr` varchar(255) DEFAULT NULL,
  `descr_en` varchar(255) DEFAULT NULL,
  `icon_url` varchar(255) NOT NULL,
  `xp_threshold` int(11) NOT NULL,
  `challenge_required_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `badges`
--

INSERT INTO `badges` (`id`, `nom_fr`, `nom_en`, `descr_fr`, `descr_en`, `icon_url`, `xp_threshold`, `challenge_required_id`) VALUES
(2, 'Covoitureur', 'Carpooler', 'Tu as participé à ton premier covoiturage !', 'You participated in your first carpool!', '../../img/badge/Badges-covoit.jpg', 0, 3),
(3, 'Super Covoitureur', 'Super Carpooler', 'Le covoiturage n\'a plus de secret pour toi.', 'Carpooling holds no secrets for you.', '../../img/badge/Badges-covoit+.jpg', 8000, 3),
(4, 'Économe d\'Énergie', 'Energy Saver', 'Tu as le bon réflexe d\'éteindre en partant.', 'You have the good habit of turning off the lights.', '../../img/badge/Badges-lightoff.jpg', 0, 17),
(5, 'Le Médiateur', 'The Mediator', 'Tu as prouvé tes compétences sociales et de cohésion.', 'You proved your social and cohesion skills.', '../../img/badge/Badges-mediteur.jpg', 0, 43),
(6, 'Pro du Recyclage', 'Recycling Pro', 'Le tri sélectif est une seconde nature pour toi.', 'Recycling is second nature to you.', '../../img/badge/Badges-recy.jpg', 0, 24),
(7, 'Ambassadeur RSE', 'CSR Ambassador', 'Ton implication écologique globale est remarquable.', 'Your overall ecological involvement is remarkable.', '../../img/badge/Badges-rse.jpg', 0, 44);

-- --------------------------------------------------------

--
-- Structure de la table `carbon_logs`
--

CREATE TABLE `carbon_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date_log` date NOT NULL,
  `amount_co2` float DEFAULT 0,
  `source_type` varchar(50) DEFAULT 'challenge',
  `source_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `challenges`
--

CREATE TABLE `challenges` (
  `id` int(11) NOT NULL,
  `titre_fr` varchar(100) NOT NULL,
  `titre_en` varchar(100) NOT NULL,
  `descr_fr` text NOT NULL,
  `descr_en` text NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `xp_gain` int(11) NOT NULL DEFAULT 10,
  `co2_kg` float NOT NULL DEFAULT 0,
  `difficulty` enum('facile','moyen','difficile') DEFAULT 'facile',
  `domaine` enum('ecologique','social') NOT NULL DEFAULT 'ecologique',
  `domaine_2` enum('ecologique','social') DEFAULT NULL,
  `categorie` varchar(50) NOT NULL DEFAULT 'Général',
  `duration_days` int(11) DEFAULT 1,
  `max_actions_day` tinyint(4) DEFAULT 1,
  `company_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `challenges`
--

INSERT INTO `challenges` (`id`, `titre_fr`, `titre_en`, `descr_fr`, `descr_en`, `image_url`, `xp_gain`, `co2_kg`, `difficulty`, `domaine`, `domaine_2`, `categorie`, `duration_days`, `max_actions_day`, `company_id`, `is_active`) VALUES
(1, 'venir à velo', 'commute in vélo', 'description en fr', 'description in english', NULL, 200, 2, 'difficile', 'ecologique', NULL, 'Mobilité', 7, 1, NULL, 1),
(2, 'Le Roi du Pédalier', 'King of the Road', 'Je suis venu au travail en vélo (électrique ou mécanique) aujourd\'hui.', 'I commuted to work by bike (electric or mechanical) today.', NULL, 50, 2.5, 'difficile', 'ecologique', NULL, 'Mobilité', 1, 1, NULL, 1),
(3, 'Covoiturage Convivial', 'Carpool Buddy', 'J\'ai partagé mon trajet avec au moins un collègue (conducteur ou passager).', 'I shared my ride with at least one colleague (driver or passenger).', 'img/carte/carte-covoiturage2.svg', 30, 1.8, 'moyen', 'ecologique', 'social', 'Mobilité', 1, 10, NULL, 1),
(4, 'Transports en Commun', 'Public Transport Rider', 'J\'ai laissé la voiture au garage pour prendre le bus, le train ou le métro.', 'I left the car at home and took the bus, train, or subway.', NULL, 30, 1.2, 'moyen', 'ecologique', NULL, 'Mobilité', 1, 1, NULL, 1),
(5, 'Marche à Pied', 'Walk to Work', 'Je suis venu à pied (ou j\'ai descendu 2 arrêts plus tôt pour finir en marchant).', 'I walked to work (or got off 2 stops early to walk the rest of the way).', NULL, 40, 0.5, 'moyen', 'ecologique', NULL, 'Mobilité', 1, 1, NULL, 1),
(6, 'Déjeuner Végétarien', 'Veggie Lunch', 'J\'ai pris un repas 100% végétarien ce midi (sans viande ni poisson).', 'I ate a 100% vegetarian meal for lunch (no meat or fish).', NULL, 20, 1.5, 'moyen', 'ecologique', NULL, 'Autre', 1, 1, NULL, 1),
(7, 'Zéro Déchet', 'Zero Waste Lunch', 'J\'ai apporté mon propre repas dans une boîte réutilisable (Tupperware/Bento).', 'I brought my own lunch in a reusable container.', NULL, 15, 0.1, 'facile', 'ecologique', NULL, 'Recyclage', 1, 1, NULL, 1),
(8, 'Gourde Attitude', 'Reusable Bottle', 'J\'ai utilisé ma gourde ou un verre toute la journée au lieu de gobelets jetables.', 'I used my reusable bottle or a glass all day instead of disposable cups.', NULL, 10, 0.05, 'facile', 'ecologique', NULL, 'Bureau', 1, 1, NULL, 1),
(9, 'Café Responsable', 'Responsible Coffee', 'J\'ai utilisé mon mug personnel à la machine à café (sans touillette en plastique).', 'I used my personal mug at the coffee machine (no plastic stirrer).', NULL, 5, 0.02, 'facile', 'ecologique', NULL, 'Bureau', 1, 3, NULL, 1),
(10, 'Nettoyage de Printemps', 'Mailbox Cleanup', 'J\'ai supprimé au moins 50 vieux emails ou désabonné de 3 newsletters inutiles.', 'I deleted at least 50 old emails or unsubscribed from 3 useless newsletters.', NULL, 15, 0.3, 'facile', 'ecologique', NULL, 'Numérique', 1, 1, NULL, 1),
(11, 'Réunion sans Caméra', 'Audio-Only Meeting', 'J\'ai coupé ma caméra pendant une réunion en visio pour économiser la bande passante.', 'I turned off my camera during a video meeting to save bandwidth.', NULL, 10, 0.1, 'facile', 'ecologique', NULL, 'Numérique', 1, 3, NULL, 1),
(12, 'Déconnexion Totale', 'Full Unplug', 'J\'ai complètement éteint mon ordinateur et mon écran en partant (pas juste en veille).', 'I completely turned off my computer and monitor before leaving (not just sleep mode).', NULL, 20, 0.2, 'moyen', 'ecologique', NULL, 'Numérique', 1, 1, NULL, 1),
(13, 'Favoris Locaux', 'Bookmark Shortcut', 'J\'ai utilisé mes favoris pour accéder aux sites web au lieu de passer par une recherche Google.', 'I used bookmarks to access websites instead of searching on Google.', NULL, 5, 0.01, 'facile', 'ecologique', NULL, 'Numérique', 1, 5, NULL, 1),
(14, 'Cloud Detox', 'Cloud Detox', 'J\'ai supprimé des fichiers lourds et inutiles de mon stockage en ligne (Drive, OneDrive, iCloud).', 'I deleted large and useless files from my online storage (Drive, OneDrive, iCloud).', NULL, 25, 0.5, 'moyen', 'ecologique', NULL, 'Numérique', 1, 1, NULL, 1),
(15, 'Typographie Éco', 'Eco-Font Warrior', 'J\'ai utilisé une police économe en encre (Century Gothic, Ecofont) ou le \"Mode Sombre\" pour travailler.', 'I used an ink-saving font (Century Gothic, Ecofont) or \"Dark Mode\" to work.', NULL, 10, 0.01, 'facile', 'ecologique', NULL, 'Numérique', 1, 1, NULL, 1),
(16, 'L\'Escalier Sportif', 'Take the Stairs', 'J\'ai pris les escaliers au lieu de l\'ascenseur (montée ou descente).', 'I took the stairs instead of the elevator (up or down).', NULL, 10, 0.05, 'facile', 'ecologique', NULL, 'Bureau', 1, 4, NULL, 1),
(17, 'Lumière Naturelle', 'Natural Light', 'J\'ai éteint la lumière dans mon bureau ou une salle de réunion inoccupée.', 'I turned off the lights in my office or an empty meeting room.', NULL, 10, 0.1, 'facile', 'ecologique', NULL, 'Bureau', 1, 2, NULL, 1),
(18, 'Zéro Papier', 'Paperless Day', 'Je n\'ai rien imprimé de la journée (ou j\'ai imprimé en R/V et Noir & Blanc strict).', 'I didn\'t print anything today (or used double-sided Black & White only).', NULL, 25, 0.2, 'moyen', 'ecologique', NULL, 'Bureau', 1, 1, NULL, 1),
(19, 'Pull Over Chauffage', 'Sweater Weather', 'J\'ai mis un pull au lieu d\'augmenter le chauffage (ou j\'ai baissé le thermostat de 1°C).', 'I put on a sweater instead of turning up the heat (or lowered the thermostat by 1°C).', NULL, 30, 1, 'moyen', 'ecologique', NULL, 'Bureau', 1, 1, NULL, 1),
(20, 'Chasse aux Courants d\'Air', 'Climate Control', 'J\'ai fermé les fenêtres et les portes car le chauffage ou la climatisation était allumé.', 'I closed windows and doors because the heating or air conditioning was on.', NULL, 15, 0.8, 'facile', 'ecologique', NULL, 'Bureau', 1, 2, NULL, 1),
(21, 'Maître du Brouillon', 'Draft Paper Master', 'J\'ai réutilisé le verso d\'une feuille imprimée pour prendre des notes au lieu d\'une feuille neuve.', 'I reused the back of a printed sheet for notes instead of a new one.', NULL, 10, 0.05, 'facile', 'ecologique', NULL, 'Recyclage', 1, 3, NULL, 1),
(22, 'Parrainage Écologique', 'Eco-Sponsor', 'J\'ai convaincu un collègue de s\'inscrire sur Shift\'Up aujourd\'hui.', 'I convinced a colleague to sign up for Shift\'Up today.', NULL, 100, 0, 'difficile', 'ecologique', 'social', 'Autre', 1, 1, NULL, 1),
(23, 'Partage de Réussite', 'Success Sharing', 'J\'ai posté une astuce ou une réussite sur le mur social de l\'entreprise.', 'I posted a tip or a success story on the company social wall.', NULL, 15, 0, 'facile', 'ecologique', 'social', 'Autre', 1, 1, NULL, 1),
(24, 'Tri Sélectif', 'Recycling Pro', 'J\'ai correctement trié mes déchets (papier, plastique, verre) dans les bacs de l\'entreprise.', 'I correctly sorted my waste (paper, plastic, glass) in the company bins.', NULL, 10, 0.1, 'facile', 'ecologique', NULL, 'Recyclage', 1, 2, NULL, 1),
(25, 'Seconde Main', 'Second Hand', 'J\'ai acheté ou apporté un vêtement/objet de seconde main au lieu du neuf.', 'I bought or brought a second-hand item instead of a new one.', NULL, 40, 2, 'moyen', 'ecologique', NULL, 'Autre', 1, 1, NULL, 1),
(26, 'Main Verte : Sauver l\'Orchidée', 'Green Thumb: Save the Orchid', 'Arrosez la plante du bureau tous les jours pendant 2 semaines.', 'Water the office plant every day for 2 weeks.', NULL, 500, 0.5, 'moyen', 'ecologique', NULL, 'Bureau', 14, 1, NULL, 0),
(27, 'Points de tri et sensibilisation', 'Recycling stations and awareness', 'Installer et organiser des points de tri accessibles et visibles dans les locaux. Sensibiliser toute l\'équipe par une affiche et un mini-brief', 'Install and organize recycling stations easily accessible and visible in the workplace. Raise team awareness with posters and a short briefing', NULL, 15, 1.5, 'moyen', 'social', NULL, 'Recyclage', 7, 1, NULL, 1),
(28, 'Remplacer le plastique à usage unique', 'Replace single-use plastic', 'Remplacer gobelets, couverts et bouteilles plastiques par des solutions réutilisables (gobelets mug, gourdes), et mettre en place une politique interne', 'Replace single-use cups, cutlery and plastic bottles with reusable alternatives (mugs, reusable bottles), and implement an internal policy', NULL, 10, 0.5, 'facile', 'social', NULL, 'Recyclage', 30, 1, NULL, 1),
(29, 'Atelier réparation et upcycling convivial', 'Repair & upcycling social workshop', 'Organiser un atelier convivial où les employés apportent des objets à réparer ou à upcycler. Favoriser l\'entraide', 'Organise a friendly workshop where employees bring items to repair or upcycle. Encourage mutual assistance.', NULL, 20, 3, 'moyen', 'social', NULL, 'Recyclage', 1, 1, NULL, 1),
(30, 'Compost en entreprise', 'Office composting', 'Mettre en place une benne de compost pour déchets organiques de cuisine et café, avec responsabilisation d\'un petit groupe qui gère le processus', 'Set up a compost bin for organic kitchen and coffee waste, with a small group taking responsibility for managing the process.', NULL, 12, 2, 'moyen', 'social', NULL, 'Recyclage', 30, 1, NULL, 1),
(31, 'Journée nettoyage numérique', 'Digital declutter day', 'Organiser une journée pour supprimer fichiers inutiles, archiver anciens dossiers, et réduire le stockage cloud superflu pour économiser énergie numérique', 'Organize a day to delete useless files, archive old folders, and reduce unnecessary cloud storage to save digital energy', NULL, 10, 0.2, 'facile', 'social', NULL, 'Numérique', 1, 1, NULL, 1),
(32, 'Réduction et bonne pratique des mails', 'Email reduction challenge', 'Mettre en place des bonnes pratiques: limiter les CC, utiliser des sujets clairs, compresser pièces jointes et privilegier liens partagés pour réduire le trafic mail', 'Implement best practices: limit CCs, use clear subjects, compress attachments and prefer shared links to reduce email traffic', NULL, 8, 0.05, 'facile', 'social', NULL, 'Numérique', 7, 2, NULL, 1),
(33, 'Optimisation des instances et cloud', 'Efficient cloud instance optimization', 'Auditer et migrer vers des instances cloud optimisées pour réduire la consommation énergétique serveur', 'Audit and migrate to optimized cloud instances to reduce server energy consumption', NULL, 30, 10, 'difficile', 'social', NULL, 'Numérique', 30, 1, NULL, 1),
(34, 'Atelier convivial d\'économie d\'énergie numérique', 'Low-energy tech workshop', 'Atelier pratique pour montrer comment configurer modes économies d\'énergie sur ordinateurs, téléphones et expliquer l\'impact du streaming et stockage', 'Practical workshop showing how to set energy-saving modes on computers and phones and explaining the impact of streaming and storage', NULL, 12, 0.3, 'facile', 'social', NULL, 'Numérique', 1, 1, NULL, 1),
(35, 'Semaine mobilité douce', 'Soft mobility week', 'Encourager marche, vélo, transports en commun pendant une semaine: comptabiliser trajets, offrir petits gestes conviviaux (petit déjeuner collectif) pour motiver', 'Encourage walking, cycling and public transport for one week: track trips and offer convivial incentives (group breakfast) to motivate', NULL, 15, 5, 'moyen', 'social', NULL, 'Mobilité', 7, 1, NULL, 1),
(36, 'Plateforme de covoiturage interne', 'Internal carpooling platform', 'Lancer un outil ou un canal pour partager trajets domicile-travail et covoiturage, mettre en place un petit incitatif (place prioritaire, café offert)', 'Launch a tool or channel to share home-work trips and carpooling, with small incentives', NULL, 12, 4, 'moyen', 'social', NULL, 'Mobilité', 30, 1, NULL, 1),
(37, 'Prioriser réunions à distance et limiter déplacements pro', 'Prioritize remote meetings and reduce business travel', 'Mettre une politique encourageant visioconférences et outils collaboratifs pour limiter les voyages professionnels et leurs emissions CO2', 'Implement a policy encouraging videoconferences and collaboration tools to limit business travel and related CO2 emissions', NULL, 20, 15, 'difficile', 'social', NULL, 'Mobilité', 90, 1, NULL, 1),
(38, 'Matinée vélo conviviale', 'Social bike-to-work morning', 'Organiser une matinée ou groupe vient à vélo puis petit dejeuner collectif sur place pour renforcer lien social et promouvoir le vélo', 'Organize a morning where a group cycles to work followed by a shared breakfast to strengthen social bonds and promote cycling', NULL, 18, 2, 'facile', 'social', NULL, 'Mobilité', 1, 1, NULL, 1),
(39, 'Achats responsables pour le bureau', 'Responsible office supplies procurement', 'Mettre à jour la politique d\'achat,papier recyclé, fournitures durables, produits de nettoyage non toxiques et emballages réduits', 'Update procurement policy,recycled paper, durable supplies, non-toxic cleaning products and reduced packaging', NULL, 10, 1, 'facile', 'social', NULL, 'Bureau', 30, 1, NULL, 1),
(40, 'Plantes et qualité de l\'air', 'Plants and air quality', 'Installer des plantes dans les espaces communs, favoriser aération naturelle et organiser une petite action collective d\'entretien pour impliquer l\'équipe', 'Install plants in common areas, favor natural ventilation and organize a small collective maintenance action to involve the team', NULL, 8, 0.1, 'facile', 'social', NULL, 'Bureau', 7, 1, NULL, 1),
(41, 'Optimisation chauffage et climatisation', 'Thermostat and HVAC optimization', 'Réviser consignes de thermostat (saisonnier), sensibiliser sur gestes simples (veste plutot que surchauffage) et programmer plages économiques', 'Review thermostat rules (seasonal), raise awareness on simple habits (wear a sweater instead of over-heating) and schedule energy-saving periods', NULL, 15, 8, 'moyen', 'social', NULL, 'Bureau', 180, 1, NULL, 1),
(42, 'Pause-café zéro-déchet conviviale', 'Zero-waste coffee break', 'Promouvoir tasses réutilisables, café en vrac, coupelles mutualisées et organiser chaque semaine un moment convivial sans emballages individuels', 'Promote reusable cups, bulk coffee, shared plates and organize a weekly convivial moment without single-use packaging', NULL, 6, 0.2, 'facile', 'social', NULL, 'Bureau', 1, 1, NULL, 1),
(43, 'Cohésion d\'équipe', 'Team Cohesion', 'Organise ou participe activement à un moment de médiation ou d\'intégration avec l\'équipe.', 'Organize or actively participate in a mediation or integration moment with the team.', NULL, 50, 0, 'moyen', 'social', NULL, 'Autre', 1, 1, NULL, 1),
(44, 'Réunion RSE', 'CSR Meeting', 'Participe activement à une réunion ou à un groupe de travail sur la RSE (Responsabilité Sociétale des Entreprises).', 'Actively participate in a meeting or working group on CSR (Corporate Social Responsibility).', NULL, 40, 0, 'moyen', 'ecologique', NULL, 'Autre', 1, 1, NULL, 1);

-- --------------------------------------------------------

--
-- Structure de la table `companies`
--

CREATE TABLE `companies` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `secteur` varchar(50) NOT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `code_invite` varchar(20) NOT NULL,
  `total_xp` int(11) DEFAULT 0,
  `total_carbon_saved` float DEFAULT 0,
  `theme_color` varchar(7) DEFAULT '#22c55e',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `companies`
--

INSERT INTO `companies` (`id`, `nom`, `secteur`, `logo_url`, `code_invite`, `total_xp`, `total_carbon_saved`, `theme_color`, `created_at`) VALUES
(1, 'test1', '1', '120', '1234', 0, 0, '#22c55e', '2026-02-03 19:08:56'),
(2, 'EcoCorp', 'Technologie', NULL, 'ECO2026', 15000, 0, '#22c55e', '2026-03-11 14:04:34'),
(3, 'GreenTech', 'Energie', NULL, 'GREEN26', 42000, 0, '#22c55e', '2026-03-11 14:04:34');

-- --------------------------------------------------------

--
-- Structure de la table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `company_id` int(11) NOT NULL,
  `total_xp` int(11) DEFAULT 0,
  `total_carbon_saved` float DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `departments`
--

INSERT INTO `departments` (`id`, `nom`, `company_id`, `total_xp`, `total_carbon_saved`) VALUES
(1, 'Roi du management', 1, 0, 0),
(3, 'Ressources Humaines', 2, 0, 0),
(4, 'Développement IT', 2, 0, 0),
(5, 'Marketing', 3, 0, 0);

-- --------------------------------------------------------

--
-- Structure de la table `disabled_challenges`
--

CREATE TABLE `disabled_challenges` (
  `id` int(11) NOT NULL,
  `challenge_id` int(11) NOT NULL,
  `disabled_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `learning_modules`
--

CREATE TABLE `learning_modules` (
  `id` int(11) NOT NULL,
  `titre` varchar(100) NOT NULL,
  `contenu_texte` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `xp_reward` int(11) DEFAULT 50,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `quiz_questions`
--

CREATE TABLE `quiz_questions` (
  `id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `question` varchar(255) NOT NULL,
  `choix_a` varchar(255) NOT NULL,
  `choix_b` varchar(255) NOT NULL,
  `choix_c` varchar(255) NOT NULL,
  `bonne_reponse` char(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `rewards`
--

CREATE TABLE `rewards` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `type` enum('goodie','carte_cadeau','xp_boost','streak_freeze','avatar_cadre') DEFAULT 'goodie',
  `description` text DEFAULT NULL,
  `boost_value` decimal(3,2) DEFAULT NULL COMMENT 'Ex: 1.5 pour +50% XP',
  `duration_hours` int(11) DEFAULT NULL COMMENT 'Durée du boost en heures',
  `image_url` varchar(255) DEFAULT NULL,
  `cost` int(11) NOT NULL,
  `company_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `rewards`
--

INSERT INTO `rewards` (`id`, `nom`, `type`, `description`, `boost_value`, `duration_hours`, `image_url`, `cost`, `company_id`) VALUES
(1, 'Gourde en Inox', 'goodie', 'Une superbe gourde réutilisable aux couleurs de l\'entreprise.', NULL, NULL, NULL, 5000, NULL),
(2, 'Gel de Série', 'streak_freeze', 'Protège ta série (streak) de flammes si tu oublies de te connecter une journée !', NULL, NULL, '../../img/shop/streak-freeze.webp', 500, NULL),
(3, 'Boost XP 50%', 'xp_boost', 'Gagne 1.5x plus d\'XP sur tous tes défis pendant 24 heures !', 1.50, 24, NULL, 1000, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `social_posts`
--

CREATE TABLE `social_posts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires_at` datetime DEFAULT NULL,
  `prenom` varchar(50) DEFAULT NULL,
  `nom` varchar(50) DEFAULT NULL,
  `pseudo` varchar(30) DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `role` enum('shifter','admin','super_admin') DEFAULT 'shifter',
  `points_wallet` int(11) DEFAULT 0,
  `xp_boost_multiplier` decimal(3,2) DEFAULT 1.00,
  `xp_boost_ends_at` datetime DEFAULT NULL,
  `points_rank` int(11) DEFAULT 0,
  `initial_footprint_kg` decimal(10,2) DEFAULT 0.00,
  `total_carbon_saved` float DEFAULT 0,
  `current_streak` int(11) DEFAULT 0,
  `max_streak` int(11) DEFAULT 0,
  `streak_freezes` int(11) DEFAULT 0,
  `last_activity` date DEFAULT NULL,
  `est_actif` tinyint(1) DEFAULT 1,
  `language_pref` varchar(5) DEFAULT 'fr',
  `company_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `reset_token`, `reset_expires_at`, `prenom`, `nom`, `pseudo`, `avatar_url`, `role`, `points_wallet`, `xp_boost_multiplier`, `xp_boost_ends_at`, `points_rank`, `initial_footprint_kg`, `total_carbon_saved`, `current_streak`, `max_streak`, `streak_freezes`, `last_activity`, `est_actif`, `language_pref`, `company_id`, `department_id`) VALUES
(1, 'ryan@gmail.com', '$2y$10$GyxOBlhJY5gR4sRGAax.w.ZsGLFB7sSRRhTiyMRrHnJSL7KSVbDzm', NULL, NULL, NULL, NULL, 'Ryanthebiggoat', NULL, 'shifter', 10008664, 1.50, '2026-03-30 22:25:59', 20410, 9300.00, 132.55, 2, 0, 1, '2026-03-30', 1, 'fr', 1, 1),
(2, 'sophie@shiftup.com', '$2y$10$HHaKIT3bmlJ8VZdS8U7xbe4a5Ym5zDqQ.c.MS0f4HQhhtBpN/BR.O', NULL, NULL, NULL, NULL, 'Sophie RSE', NULL, 'shifter', 1280, 1.00, NULL, 1280, 28.50, 48.9, 5, 0, 0, '2026-02-12', 1, 'fr', 1, NULL),
(3, 'marc@shiftup.com', '$2y$10$HHaKIT3bmlJ8VZdS8U7xbe4a5Ym5zDqQ.c.MS0f4HQhhtBpN/BR.O', NULL, NULL, NULL, NULL, 'Marc Vitesse', NULL, 'shifter', 4800, 1.00, NULL, 4800, 35.00, 150.5, 21, 0, 0, '2026-02-12', 1, 'fr', 1, NULL),
(4, 'julie@shiftup.com', '$2y$10$HHaKIT3bmlJ8VZdS8U7xbe4a5Ym5zDqQ.c.MS0f4HQhhtBpN/BR.O', NULL, NULL, NULL, NULL, 'Julie Green', NULL, 'shifter', 15505, 1.00, NULL, 15505, 18.20, 524.7, 1, 0, 0, '2026-02-21', 1, 'en', 1, NULL),
(5, 'thomas@shiftup.com', '$2y$10$HHaKIT3bmlJ8VZdS8U7xbe4a5Ym5zDqQ.c.MS0f4HQhhtBpN/BR.O', NULL, NULL, NULL, NULL, 'Thomas Novice', NULL, 'shifter', 50, 1.00, NULL, 50, 32.60, 2.5, 0, 0, 0, '2026-02-07', 1, 'fr', 1, NULL),
(6, 'alice@ecocorp.com', '$2y$10$HHaKIT3bmlJ8VZdS8U7xbe4a5Ym5zDqQ.c.MS0f4HQhhtBpN/BR.O', NULL, NULL, NULL, NULL, 'Alice Eco', NULL, 'shifter', 1500, 1.00, NULL, 1500, 32.60, 0, 12, 12, 0, NULL, 1, 'fr', 2, 3),
(7, 'bob@ecocorp.com', '$2y$10$HHaKIT3bmlJ8VZdS8U7xbe4a5Ym5zDqQ.c.MS0f4HQhhtBpN/BR.O', NULL, NULL, NULL, NULL, 'Bob Tech', NULL, 'shifter', 3200, 1.00, NULL, 3200, 32.60, 0, 5, 14, 0, NULL, 1, 'fr', 2, 4),
(8, 'charlie@greentech.com', '$2y$10$HHaKIT3bmlJ8VZdS8U7xbe4a5Ym5zDqQ.c.MS0f4HQhhtBpN/BR.O', NULL, NULL, NULL, NULL, 'Charlie Green', NULL, 'shifter', 8500, 1.00, NULL, 8500, 32.60, 0, 45, 50, 0, NULL, 1, 'fr', 3, 5),
(9, 'diana@greentech.com', '$2y$10$HHaKIT3bmlJ8VZdS8U7xbe4a5Ym5zDqQ.c.MS0f4HQhhtBpN/BR.O', NULL, NULL, NULL, NULL, 'Diana Bio', NULL, 'shifter', 450, 1.00, NULL, 450, 32.60, 0, 2, 5, 0, NULL, 1, 'fr', 3, 5),
(10, 'lucas@shiftup.com', '$2y$10$HHaKIT3bmlJ8VZdS8U7xbe4a5Ym5zDqQ.c.MS0f4HQhhtBpN/BR.O', NULL, NULL, NULL, NULL, 'Lucas Speed', NULL, 'shifter', 12400, 1.00, NULL, 12400, 32.60, 0, 30, 30, 0, NULL, 1, 'fr', 1, 1),
(11, 'ryan2@gmail.com', '$2y$10$LeWZGPRNqX.RqAUxZZfzYuwZdXh1M1NzS4Hl2R9ssuVJ2ic/fjbae', NULL, NULL, 'Ryan', 'Mumbata', 'Legoat2', NULL, 'admin', 10, 1.00, NULL, 10, 32.60, 0.1, 1, 0, 0, '2026-03-29', 1, 'fr', 2, 4);

-- --------------------------------------------------------

--
-- Structure de la table `user_actions`
--

CREATE TABLE `user_actions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `challenge_id` int(11) NOT NULL,
  `date_action` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `user_actions`
--

INSERT INTO `user_actions` (`id`, `user_id`, `challenge_id`, `date_action`) VALUES
(1, 1, 1, '2026-02-02 10:09:45'),
(2, 1, 1, '2026-02-02 10:09:56'),
(3, 1, 1, '2026-02-02 10:15:05'),
(4, 1, 1, '2026-02-02 10:15:07'),
(5, 1, 1, '2026-02-02 10:15:07'),
(6, 1, 1, '2026-02-02 10:15:07'),
(7, 1, 1, '2026-02-02 10:15:07'),
(8, 1, 1, '2026-02-02 10:15:08'),
(9, 1, 1, '2026-02-02 10:15:08'),
(10, 1, 1, '2026-02-02 10:15:08'),
(11, 1, 1, '2026-02-02 10:17:45'),
(12, 1, 1, '2026-02-02 10:17:52'),
(13, 1, 1, '2026-02-02 10:17:52'),
(14, 1, 1, '2026-02-02 12:11:43'),
(15, 1, 1, '2026-02-02 12:11:45'),
(16, 1, 1, '2026-02-02 12:11:45'),
(17, 1, 1, '2026-02-02 12:11:46'),
(18, 1, 1, '2026-02-02 12:11:46'),
(19, 1, 1, '2026-02-02 12:11:46'),
(20, 1, 1, '2026-02-02 12:11:47'),
(21, 1, 1, '2026-02-02 12:11:47'),
(22, 1, 1, '2026-02-02 12:11:47'),
(23, 1, 1, '2026-02-02 12:11:47'),
(24, 1, 1, '2026-02-02 12:11:48'),
(25, 1, 1, '2026-02-02 12:11:48'),
(26, 1, 1, '2026-02-02 12:11:48'),
(27, 1, 1, '2026-02-02 12:11:49'),
(28, 1, 1, '2026-02-02 12:11:49'),
(29, 1, 1, '2026-02-02 12:11:49'),
(30, 1, 1, '2026-02-02 12:11:50'),
(31, 1, 1, '2026-02-02 12:11:50'),
(32, 1, 1, '2026-02-02 12:11:50'),
(33, 1, 1, '2026-02-02 12:11:51'),
(34, 1, 1, '2026-02-02 12:11:51'),
(35, 1, 1, '2026-02-02 12:11:51'),
(36, 1, 1, '2026-02-02 12:11:52'),
(37, 1, 1, '2026-02-02 12:11:52'),
(38, 1, 1, '2026-02-02 12:11:52'),
(39, 1, 1, '2026-02-02 12:11:53'),
(40, 1, 1, '2026-02-02 12:11:53'),
(41, 1, 1, '2026-02-02 12:11:53'),
(42, 1, 1, '2026-02-02 12:11:54'),
(43, 1, 1, '2026-02-02 12:11:54'),
(44, 1, 1, '2026-02-02 12:11:54'),
(45, 1, 1, '2026-02-02 12:11:55'),
(46, 1, 1, '2026-02-02 12:11:55'),
(47, 1, 1, '2026-02-02 12:11:56'),
(48, 1, 1, '2026-02-02 12:11:56'),
(49, 1, 1, '2026-02-02 12:11:56'),
(50, 1, 1, '2026-02-02 12:11:57'),
(51, 1, 26, '2026-02-02 12:52:30'),
(52, 1, 2, '2026-02-02 12:53:24'),
(53, 1, 3, '2026-02-02 12:53:29'),
(54, 1, 4, '2026-02-02 12:53:31'),
(55, 1, 5, '2026-02-02 12:53:34'),
(56, 1, 6, '2026-02-02 12:53:36'),
(57, 1, 8, '2026-02-02 12:53:39'),
(58, 1, 7, '2026-02-02 12:53:41'),
(59, 1, 9, '2026-02-02 12:53:44'),
(60, 1, 10, '2026-02-02 12:53:47'),
(61, 1, 12, '2026-02-02 12:53:50'),
(62, 1, 15, '2026-02-02 18:41:10'),
(63, 1, 13, '2026-02-02 18:41:14'),
(64, 1, 11, '2026-02-02 18:41:17'),
(65, 1, 14, '2026-02-02 18:41:21'),
(66, 1, 17, '2026-02-02 18:41:25'),
(67, 1, 22, '2026-02-02 18:41:34'),
(68, 1, 16, '2026-02-02 19:01:07'),
(69, 1, 24, '2026-02-02 19:01:18'),
(70, 1, 3, '2026-02-02 19:13:06'),
(71, 1, 3, '2026-02-02 19:13:09'),
(72, 1, 3, '2026-02-02 19:20:12'),
(73, 1, 3, '2026-02-02 19:20:15'),
(74, 1, 3, '2026-02-02 19:20:16'),
(75, 1, 3, '2026-02-02 19:20:19'),
(76, 1, 3, '2026-02-02 19:20:21'),
(77, 1, 3, '2026-02-02 19:20:22'),
(78, 1, 3, '2026-02-02 19:20:24'),
(79, 1, 9, '2026-02-02 19:20:27'),
(80, 1, 9, '2026-02-02 19:20:31'),
(81, 1, 13, '2026-02-02 19:45:27'),
(82, 1, 1, '2026-02-12 08:49:48'),
(83, 2, 4, '2026-02-12 10:52:37'),
(84, 2, 1, '2026-02-12 10:52:45'),
(85, 2, 2, '2026-02-12 10:52:47'),
(86, 4, 2, '2026-02-12 10:53:37'),
(87, 4, 1, '2026-02-13 08:46:00'),
(88, 1, 1, '2026-02-13 14:22:33'),
(89, 4, 10, '2026-02-21 21:42:10'),
(90, 4, 24, '2026-02-21 21:43:17'),
(91, 4, 3, '2026-02-21 22:36:35'),
(92, 1, 26, '2026-02-24 19:09:09'),
(93, 1, 1, '2026-02-24 19:09:15'),
(94, 1, 1, '2026-02-24 19:09:19'),
(95, 1, 1, '2026-02-24 19:09:24'),
(96, 1, 1, '2026-02-24 19:09:52'),
(97, 1, 21, '2026-02-24 20:32:42'),
(98, 1, 11, '2026-02-24 21:20:28'),
(99, 1, 21, '2026-02-24 21:21:30'),
(100, 1, 14, '2026-02-24 21:30:34'),
(101, 1, 11, '2026-02-24 21:38:05'),
(102, 1, 21, '2026-02-24 21:39:32'),
(103, 1, 1, '2026-03-02 09:12:38'),
(104, 1, 26, '2026-03-02 09:12:51'),
(105, 1, 21, '2026-03-03 14:39:23'),
(106, 1, 14, '2026-03-09 12:07:18'),
(107, 1, 21, '2026-03-11 10:33:58'),
(108, 1, 1, '2026-03-11 10:34:03'),
(109, 1, 21, '2026-03-20 13:40:44'),
(110, 1, 21, '2026-03-27 08:51:07'),
(111, 1, 1, '2026-03-27 15:52:48'),
(112, 1, 21, '2026-03-29 19:56:31'),
(113, 1, 24, '2026-03-29 19:56:48'),
(114, 1, 21, '2026-03-29 20:08:20'),
(115, 1, 21, '2026-03-29 20:22:54'),
(116, 1, 11, '2026-03-29 20:25:17'),
(117, 1, 7, '2026-03-29 20:32:28'),
(118, 1, 24, '2026-03-29 20:39:15'),
(119, 1, 11, '2026-03-29 20:39:20'),
(120, 1, 15, '2026-03-29 20:42:55'),
(121, 11, 24, '2026-03-29 21:42:44'),
(122, 11, 28, '2026-03-29 22:06:20'),
(123, 1, 3, '2026-03-29 22:16:55'),
(124, 1, 30, '2026-03-29 22:26:10'),
(125, 1, 29, '2026-03-29 22:26:35'),
(126, 1, 21, '2026-03-30 01:08:16');

-- --------------------------------------------------------

--
-- Structure de la table `user_badges`
--

CREATE TABLE `user_badges` (
  `user_id` int(11) NOT NULL,
  `badge_id` int(11) NOT NULL,
  `obtained_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `user_badges`
--

INSERT INTO `user_badges` (`user_id`, `badge_id`, `obtained_at`) VALUES
(1, 2, '2026-03-29 22:16:55'),
(11, 6, '2026-03-29 21:42:44');

-- --------------------------------------------------------

--
-- Structure de la table `user_inventory`
--

CREATE TABLE `user_inventory` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reward_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `is_used` tinyint(1) DEFAULT 0,
  `obtained_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `user_inventory`
--

INSERT INTO `user_inventory` (`id`, `user_id`, `reward_id`, `quantity`, `is_used`, `obtained_at`) VALUES
(1, 1, 2, 1, 1, '2026-03-29 22:20:50'),
(2, 1, 3, 1, 1, '2026-03-29 22:21:07'),
(3, 1, 3, 1, 0, '2026-03-29 22:25:56');

-- --------------------------------------------------------

--
-- Structure de la table `user_quiz_results`
--

CREATE TABLE `user_quiz_results` (
  `user_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `date_done` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `badges`
--
ALTER TABLE `badges`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_badge_challenge` (`challenge_required_id`);

--
-- Index pour la table `carbon_logs`
--
ALTER TABLE `carbon_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `challenges`
--
ALTER TABLE `challenges`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Index pour la table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code_invite` (`code_invite`);

--
-- Index pour la table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Index pour la table `disabled_challenges`
--
ALTER TABLE `disabled_challenges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `challenge_id` (`challenge_id`);

--
-- Index pour la table `learning_modules`
--
ALTER TABLE `learning_modules`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `module_id` (`module_id`);

--
-- Index pour la table `rewards`
--
ALTER TABLE `rewards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Index pour la table `social_posts`
--
ALTER TABLE `social_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `company_id` (`company_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Index pour la table `user_actions`
--
ALTER TABLE `user_actions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `challenge_id` (`challenge_id`);

--
-- Index pour la table `user_badges`
--
ALTER TABLE `user_badges`
  ADD PRIMARY KEY (`user_id`,`badge_id`),
  ADD KEY `badge_id` (`badge_id`);

--
-- Index pour la table `user_inventory`
--
ALTER TABLE `user_inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_inv_user` (`user_id`),
  ADD KEY `fk_inv_reward` (`reward_id`);

--
-- Index pour la table `user_quiz_results`
--
ALTER TABLE `user_quiz_results`
  ADD PRIMARY KEY (`user_id`,`module_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `badges`
--
ALTER TABLE `badges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `carbon_logs`
--
ALTER TABLE `carbon_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `challenges`
--
ALTER TABLE `challenges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT pour la table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `disabled_challenges`
--
ALTER TABLE `disabled_challenges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `learning_modules`
--
ALTER TABLE `learning_modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `rewards`
--
ALTER TABLE `rewards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `social_posts`
--
ALTER TABLE `social_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `user_actions`
--
ALTER TABLE `user_actions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=127;

--
-- AUTO_INCREMENT pour la table `user_inventory`
--
ALTER TABLE `user_inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `badges`
--
ALTER TABLE `badges`
  ADD CONSTRAINT `fk_badge_challenge` FOREIGN KEY (`challenge_required_id`) REFERENCES `challenges` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `carbon_logs`
--
ALTER TABLE `carbon_logs`
  ADD CONSTRAINT `carbon_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `challenges`
--
ALTER TABLE `challenges`
  ADD CONSTRAINT `challenges_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD CONSTRAINT `quiz_questions_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `learning_modules` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `rewards`
--
ALTER TABLE `rewards`
  ADD CONSTRAINT `rewards_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `social_posts`
--
ALTER TABLE `social_posts`
  ADD CONSTRAINT `social_posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `social_posts_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `user_actions`
--
ALTER TABLE `user_actions`
  ADD CONSTRAINT `user_actions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_actions_ibfk_2` FOREIGN KEY (`challenge_id`) REFERENCES `challenges` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `user_badges`
--
ALTER TABLE `user_badges`
  ADD CONSTRAINT `user_badges_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_badges_ibfk_2` FOREIGN KEY (`badge_id`) REFERENCES `badges` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `user_inventory`
--
ALTER TABLE `user_inventory`
  ADD CONSTRAINT `fk_inv_reward` FOREIGN KEY (`reward_id`) REFERENCES `rewards` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_inv_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
