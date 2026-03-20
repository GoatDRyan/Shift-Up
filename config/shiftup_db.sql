-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mer. 11 mars 2026 à 14:05
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
(1, 'Jardinier Zen', 'Zen Gardener', 'A pris soin d\'une plante pendant 14 jours.', 'Took care of a plant for 14 days.', '', 0, 26);

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
  `company_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `challenges`
--

INSERT INTO `challenges` (`id`, `titre_fr`, `titre_en`, `descr_fr`, `descr_en`, `image_url`, `xp_gain`, `co2_kg`, `difficulty`, `domaine`, `domaine_2`, `categorie`, `duration_days`, `max_actions_day`, `company_id`) VALUES
(1, 'venir à velo', 'commute in vélo', 'description en fr', 'description in english', NULL, 200, 2, 'difficile', 'ecologique', NULL, 'Mobilité', 7, 1, NULL),
(2, 'Le Roi du Pédalier', 'King of the Road', 'Je suis venu au travail en vélo (électrique ou mécanique) aujourd\'hui.', 'I commuted to work by bike (electric or mechanical) today.', NULL, 50, 2.5, 'difficile', 'ecologique', NULL, 'Mobilité', 1, 1, NULL),
(3, 'Covoiturage Convivial', 'Carpool Buddy', 'J\'ai partagé mon trajet avec au moins un collègue (conducteur ou passager).', 'I shared my ride with at least one colleague (driver or passenger).', 'img/carte/carte-covoiturage2.svg', 30, 1.8, 'moyen', 'ecologique', 'social', 'Mobilité', 1, 10, NULL),
(4, 'Transports en Commun', 'Public Transport Rider', 'J\'ai laissé la voiture au garage pour prendre le bus, le train ou le métro.', 'I left the car at home and took the bus, train, or subway.', NULL, 30, 1.2, 'moyen', 'ecologique', NULL, 'Mobilité', 1, 1, NULL),
(5, 'Marche à Pied', 'Walk to Work', 'Je suis venu à pied (ou j\'ai descendu 2 arrêts plus tôt pour finir en marchant).', 'I walked to work (or got off 2 stops early to walk the rest of the way).', NULL, 40, 0.5, 'moyen', 'ecologique', NULL, 'Mobilité', 1, 1, NULL),
(6, 'Déjeuner Végétarien', 'Veggie Lunch', 'J\'ai pris un repas 100% végétarien ce midi (sans viande ni poisson).', 'I ate a 100% vegetarian meal for lunch (no meat or fish).', NULL, 20, 1.5, 'moyen', 'ecologique', NULL, 'Autre', 1, 1, NULL),
(7, 'Zéro Déchet', 'Zero Waste Lunch', 'J\'ai apporté mon propre repas dans une boîte réutilisable (Tupperware/Bento).', 'I brought my own lunch in a reusable container.', NULL, 15, 0.1, 'facile', 'ecologique', NULL, 'Recyclage', 1, 1, NULL),
(8, 'Gourde Attitude', 'Reusable Bottle', 'J\'ai utilisé ma gourde ou un verre toute la journée au lieu de gobelets jetables.', 'I used my reusable bottle or a glass all day instead of disposable cups.', NULL, 10, 0.05, 'facile', 'ecologique', NULL, 'Bureau', 1, 1, NULL),
(9, 'Café Responsable', 'Responsible Coffee', 'J\'ai utilisé mon mug personnel à la machine à café (sans touillette en plastique).', 'I used my personal mug at the coffee machine (no plastic stirrer).', NULL, 5, 0.02, 'facile', 'ecologique', NULL, 'Bureau', 1, 3, NULL),
(10, 'Nettoyage de Printemps', 'Mailbox Cleanup', 'J\'ai supprimé au moins 50 vieux emails ou désabonné de 3 newsletters inutiles.', 'I deleted at least 50 old emails or unsubscribed from 3 useless newsletters.', NULL, 15, 0.3, 'facile', 'ecologique', NULL, 'Numérique', 1, 1, NULL),
(11, 'Réunion sans Caméra', 'Audio-Only Meeting', 'J\'ai coupé ma caméra pendant une réunion en visio pour économiser la bande passante.', 'I turned off my camera during a video meeting to save bandwidth.', NULL, 10, 0.1, 'facile', 'ecologique', NULL, 'Numérique', 1, 3, NULL),
(12, 'Déconnexion Totale', 'Full Unplug', 'J\'ai complètement éteint mon ordinateur et mon écran en partant (pas juste en veille).', 'I completely turned off my computer and monitor before leaving (not just sleep mode).', NULL, 20, 0.2, 'moyen', 'ecologique', NULL, 'Numérique', 1, 1, NULL),
(13, 'Favoris Locaux', 'Bookmark Shortcut', 'J\'ai utilisé mes favoris pour accéder aux sites web au lieu de passer par une recherche Google.', 'I used bookmarks to access websites instead of searching on Google.', NULL, 5, 0.01, 'facile', 'ecologique', NULL, 'Numérique', 1, 5, NULL),
(14, 'Cloud Detox', 'Cloud Detox', 'J\'ai supprimé des fichiers lourds et inutiles de mon stockage en ligne (Drive, OneDrive, iCloud).', 'I deleted large and useless files from my online storage (Drive, OneDrive, iCloud).', NULL, 25, 0.5, 'moyen', 'ecologique', NULL, 'Numérique', 1, 1, NULL),
(15, 'Typographie Éco', 'Eco-Font Warrior', 'J\'ai utilisé une police économe en encre (Century Gothic, Ecofont) ou le \"Mode Sombre\" pour travailler.', 'I used an ink-saving font (Century Gothic, Ecofont) or \"Dark Mode\" to work.', NULL, 10, 0.01, 'facile', 'ecologique', NULL, 'Numérique', 1, 1, NULL),
(16, 'L\'Escalier Sportif', 'Take the Stairs', 'J\'ai pris les escaliers au lieu de l\'ascenseur (montée ou descente).', 'I took the stairs instead of the elevator (up or down).', NULL, 10, 0.05, 'facile', 'ecologique', NULL, 'Bureau', 1, 4, NULL),
(17, 'Lumière Naturelle', 'Natural Light', 'J\'ai éteint la lumière dans mon bureau ou une salle de réunion inoccupée.', 'I turned off the lights in my office or an empty meeting room.', NULL, 10, 0.1, 'facile', 'ecologique', NULL, 'Bureau', 1, 2, NULL),
(18, 'Zéro Papier', 'Paperless Day', 'Je n\'ai rien imprimé de la journée (ou j\'ai imprimé en R/V et Noir & Blanc strict).', 'I didn\'t print anything today (or used double-sided Black & White only).', NULL, 25, 0.2, 'moyen', 'ecologique', NULL, 'Bureau', 1, 1, NULL),
(19, 'Pull Over Chauffage', 'Sweater Weather', 'J\'ai mis un pull au lieu d\'augmenter le chauffage (ou j\'ai baissé le thermostat de 1°C).', 'I put on a sweater instead of turning up the heat (or lowered the thermostat by 1°C).', NULL, 30, 1, 'moyen', 'ecologique', NULL, 'Bureau', 1, 1, NULL),
(20, 'Chasse aux Courants d\'Air', 'Climate Control', 'J\'ai fermé les fenêtres et les portes car le chauffage ou la climatisation était allumé.', 'I closed windows and doors because the heating or air conditioning was on.', NULL, 15, 0.8, 'facile', 'ecologique', NULL, 'Bureau', 1, 2, NULL),
(21, 'Maître du Brouillon', 'Draft Paper Master', 'J\'ai réutilisé le verso d\'une feuille imprimée pour prendre des notes au lieu d\'une feuille neuve.', 'I reused the back of a printed sheet for notes instead of a new one.', NULL, 10, 0.05, 'facile', 'ecologique', NULL, 'Recyclage', 1, 3, NULL),
(22, 'Parrainage Écologique', 'Eco-Sponsor', 'J\'ai convaincu un collègue de s\'inscrire sur Shift\'Up aujourd\'hui.', 'I convinced a colleague to sign up for Shift\'Up today.', NULL, 100, 0, 'difficile', 'ecologique', 'social', 'Autre', 1, 1, NULL),
(23, 'Partage de Réussite', 'Success Sharing', 'J\'ai posté une astuce ou une réussite sur le mur social de l\'entreprise.', 'I posted a tip or a success story on the company social wall.', NULL, 15, 0, 'facile', 'ecologique', 'social', 'Autre', 1, 1, NULL),
(24, 'Tri Sélectif', 'Recycling Pro', 'J\'ai correctement trié mes déchets (papier, plastique, verre) dans les bacs de l\'entreprise.', 'I correctly sorted my waste (paper, plastic, glass) in the company bins.', NULL, 10, 0.1, 'facile', 'ecologique', NULL, 'Recyclage', 1, 2, NULL),
(25, 'Seconde Main', 'Second Hand', 'J\'ai acheté ou apporté un vêtement/objet de seconde main au lieu du neuf.', 'I bought or brought a second-hand item instead of a new one.', NULL, 40, 2, 'moyen', 'ecologique', NULL, 'Autre', 1, 1, NULL),
(26, 'Main Verte : Sauver l\'Orchidée', 'Green Thumb: Save the Orchid', 'Arrosez la plante du bureau tous les jours pendant 2 semaines.', 'Water the office plant every day for 2 weeks.', NULL, 500, 0.5, 'moyen', 'ecologique', NULL, 'Bureau', 14, 1, NULL);

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
(2, 'Roi du management', 1, 0, 0),
(3, 'Ressources Humaines', 2, 0, 0),
(4, 'Développement IT', 2, 0, 0),
(5, 'Marketing', 3, 0, 0);

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
  `stock` int(11) DEFAULT 0,
  `company_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `rewards`
--

INSERT INTO `rewards` (`id`, `nom`, `type`, `description`, `boost_value`, `duration_hours`, `image_url`, `cost`, `stock`, `company_id`) VALUES
(1, 'Gourde en Inox', 'goodie', 'Une superbe gourde réutilisable aux couleurs de l\'entreprise.', NULL, NULL, NULL, 5000, 10, 1),
(2, 'Gel de Série', 'streak_freeze', 'Protège ta série (streak) de flammes si tu oublies de te connecter une journée !', NULL, NULL, NULL, 500, 999, 1),
(3, 'Boost XP 50%', 'xp_boost', 'Gagne 1.5x plus d\'XP sur tous tes défis pendant 24 heures !', 1.50, 24, NULL, 1000, 999, 1);

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
  `pseudo` varchar(30) DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `role` enum('shifter','admin','super_admin') DEFAULT 'shifter',
  `points_wallet` int(11) DEFAULT 0,
  `xp_boost_multiplier` decimal(3,2) DEFAULT 1.00,
  `xp_boost_ends_at` datetime DEFAULT NULL,
  `points_rank` int(11) DEFAULT 0,
  `initial_footprint_kg` decimal(5,2) DEFAULT 32.60,
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

INSERT INTO `users` (`id`, `email`, `password_hash`, `pseudo`, `avatar_url`, `role`, `points_wallet`, `xp_boost_multiplier`, `xp_boost_ends_at`, `points_rank`, `initial_footprint_kg`, `total_carbon_saved`, `current_streak`, `max_streak`, `streak_freezes`, `last_activity`, `est_actif`, `language_pref`, `company_id`, `department_id`) VALUES
(1, 'ryan@gmail.com', '$2y$10$mU1GqaJJ4cJbFtMP0u86geUAVCgFNFXxsWYcQFokcFJe0Z6n6hKea', 'Ryanthebiggoat', NULL, 'shifter', 10010789, 1.00, NULL, 20035, 999.99, 124.94, 1, 0, 0, '2026-03-11', 1, 'fr', 1, 1),
(2, 'sophie@shiftup.com', '$2y$10$HHaKIT3bmlJ8VZdS8U7xbe4a5Ym5zDqQ.c.MS0f4HQhhtBpN/BR.O', 'Sophie RSE', NULL, 'shifter', 1280, 1.00, NULL, 1280, 28.50, 48.9, 5, 0, 0, '2026-02-12', 1, 'fr', 1, NULL),
(3, 'marc@shiftup.com', '$2y$10$HHaKIT3bmlJ8VZdS8U7xbe4a5Ym5zDqQ.c.MS0f4HQhhtBpN/BR.O', 'Marc Vitesse', NULL, 'shifter', 4800, 1.00, NULL, 4800, 35.00, 150.5, 21, 0, 0, '2026-02-12', 1, 'fr', 1, NULL),
(4, 'julie@shiftup.com', '$2y$10$HHaKIT3bmlJ8VZdS8U7xbe4a5Ym5zDqQ.c.MS0f4HQhhtBpN/BR.O', 'Julie Green', NULL, 'shifter', 15505, 1.00, NULL, 15505, 18.20, 524.7, 1, 0, 0, '2026-02-21', 1, 'en', 1, NULL),
(5, 'thomas@shiftup.com', '$2y$10$HHaKIT3bmlJ8VZdS8U7xbe4a5Ym5zDqQ.c.MS0f4HQhhtBpN/BR.O', 'Thomas Novice', NULL, 'shifter', 50, 1.00, NULL, 50, 32.60, 2.5, 0, 0, 0, '2026-02-07', 1, 'fr', 1, NULL),
(6, 'alice@ecocorp.com', '$2y$10$HHaKIT3bmlJ8VZdS8U7xbe4a5Ym5zDqQ.c.MS0f4HQhhtBpN/BR.O', 'Alice Eco', NULL, 'shifter', 1500, 1.00, NULL, 1500, 32.60, 0, 12, 12, 0, NULL, 1, 'fr', 2, 3),
(7, 'bob@ecocorp.com', '$2y$10$HHaKIT3bmlJ8VZdS8U7xbe4a5Ym5zDqQ.c.MS0f4HQhhtBpN/BR.O', 'Bob Tech', NULL, 'shifter', 3200, 1.00, NULL, 3200, 32.60, 0, 5, 14, 0, NULL, 1, 'fr', 2, 4),
(8, 'charlie@greentech.com', '$2y$10$HHaKIT3bmlJ8VZdS8U7xbe4a5Ym5zDqQ.c.MS0f4HQhhtBpN/BR.O', 'Charlie Green', NULL, 'shifter', 8500, 1.00, NULL, 8500, 32.60, 0, 45, 50, 0, NULL, 1, 'fr', 3, 5),
(9, 'diana@greentech.com', '$2y$10$HHaKIT3bmlJ8VZdS8U7xbe4a5Ym5zDqQ.c.MS0f4HQhhtBpN/BR.O', 'Diana Bio', NULL, 'shifter', 450, 1.00, NULL, 450, 32.60, 0, 2, 5, 0, NULL, 1, 'fr', 3, 5),
(10, 'lucas@shiftup.com', '$2y$10$HHaKIT3bmlJ8VZdS8U7xbe4a5Ym5zDqQ.c.MS0f4HQhhtBpN/BR.O', 'Lucas Speed', NULL, 'shifter', 12400, 1.00, NULL, 12400, 32.60, 0, 30, 30, 0, NULL, 1, 'fr', 1, 1);

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
(108, 1, 1, '2026-03-11 10:34:03');

-- --------------------------------------------------------

--
-- Structure de la table `user_badges`
--

CREATE TABLE `user_badges` (
  `user_id` int(11) NOT NULL,
  `badge_id` int(11) NOT NULL,
  `obtained_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `carbon_logs`
--
ALTER TABLE `carbon_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `challenges`
--
ALTER TABLE `challenges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `user_actions`
--
ALTER TABLE `user_actions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
