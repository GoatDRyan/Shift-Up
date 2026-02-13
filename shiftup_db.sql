-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : ven. 13 fév. 2026 à 11:02
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
(1, 'Jardinier Zen', 'Zen Gardener', 'A pris soin d\'une plante pendant 14 jours.', 'Took care of a plant for 14 days.', 'assets/img/badges/flower.png', 0, 26);

-- --------------------------------------------------------

--
-- Structure de la table `carbon_logs`
--

CREATE TABLE `carbon_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date_log` date NOT NULL,
  `amount_co2` float DEFAULT 0
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
  `xp_gain` int(11) NOT NULL DEFAULT 10,
  `co2_kg` float NOT NULL DEFAULT 0,
  `difficulty` enum('facile','moyen','difficile') DEFAULT 'facile',
  `domaine` enum('ecologique','social') NOT NULL DEFAULT 'ecologique',
  `categorie` varchar(50) NOT NULL DEFAULT 'Général',
  `duration_days` int(11) DEFAULT 1,
  `max_actions_day` tinyint(4) DEFAULT 1,
  `company_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `challenges`
--

INSERT INTO `challenges` (`id`, `titre_fr`, `titre_en`, `descr_fr`, `descr_en`, `xp_gain`, `co2_kg`, `difficulty`, `domaine`, `categorie`, `duration_days`, `max_actions_day`, `company_id`) VALUES
(1, 'venir à velo', 'commute in vélo', 'description en fr', 'description in english', 200, 2, 'difficile', 'ecologique', 'Général', 100, 50, NULL),
(2, 'Le Roi du Pédalier', 'King of the Road', 'Je suis venu au travail en vélo (électrique ou mécanique) aujourd\'hui.', 'I commuted to work by bike (electric or mechanical) today.', 50, 2.5, 'difficile', 'ecologique', 'Général', 1, 1, NULL),
(3, 'Covoiturage Convivial', 'Carpool Buddy', 'J\'ai partagé mon trajet avec au moins un collègue (conducteur ou passager).', 'I shared my ride with at least one colleague (driver or passenger).', 30, 1.8, 'moyen', 'ecologique', 'Général', 1, 10, NULL),
(4, 'Transports en Commun', 'Public Transport Rider', 'J\'ai laissé la voiture au garage pour prendre le bus, le train ou le métro.', 'I left the car at home and took the bus, train, or subway.', 30, 1.2, 'moyen', 'ecologique', 'Général', 1, 1, NULL),
(5, 'Marche à Pied', 'Walk to Work', 'Je suis venu à pied (ou j\'ai descendu 2 arrêts plus tôt pour finir en marchant).', 'I walked to work (or got off 2 stops early to walk the rest of the way).', 40, 0.5, 'moyen', 'ecologique', 'Général', 1, 1, NULL),
(6, 'Déjeuner Végétarien', 'Veggie Lunch', 'J\'ai pris un repas 100% végétarien ce midi (sans viande ni poisson).', 'I ate a 100% vegetarian meal for lunch (no meat or fish).', 20, 1.5, 'moyen', 'ecologique', 'Général', 1, 1, NULL),
(7, 'Zéro Déchet', 'Zero Waste Lunch', 'J\'ai apporté mon propre repas dans une boîte réutilisable (Tupperware/Bento).', 'I brought my own lunch in a reusable container.', 15, 0.1, 'facile', 'ecologique', 'Général', 1, 1, NULL),
(8, 'Gourde Attitude', 'Reusable Bottle', 'J\'ai utilisé ma gourde ou un verre toute la journée au lieu de gobelets jetables.', 'I used my reusable bottle or a glass all day instead of disposable cups.', 10, 0.05, 'facile', 'ecologique', 'Général', 1, 1, NULL),
(9, 'Café Responsable', 'Responsible Coffee', 'J\'ai utilisé mon mug personnel à la machine à café (sans touillette en plastique).', 'I used my personal mug at the coffee machine (no plastic stirrer).', 5, 0.02, 'facile', 'ecologique', 'Général', 1, 3, NULL),
(10, 'Nettoyage de Printemps', 'Mailbox Cleanup', 'J\'ai supprimé au moins 50 vieux emails ou désabonné de 3 newsletters inutiles.', 'I deleted at least 50 old emails or unsubscribed from 3 useless newsletters.', 15, 0.3, 'facile', 'ecologique', 'Général', 1, 1, NULL),
(11, 'Réunion sans Caméra', 'Audio-Only Meeting', 'J\'ai coupé ma caméra pendant une réunion en visio pour économiser la bande passante.', 'I turned off my camera during a video meeting to save bandwidth.', 10, 0.1, 'facile', 'ecologique', 'Général', 1, 3, NULL),
(12, 'Déconnexion Totale', 'Full Unplug', 'J\'ai complètement éteint mon ordinateur et mon écran en partant (pas juste en veille).', 'I completely turned off my computer and monitor before leaving (not just sleep mode).', 20, 0.2, 'moyen', 'ecologique', 'Général', 1, 1, NULL),
(13, 'Favoris Locaux', 'Bookmark Shortcut', 'J\'ai utilisé mes favoris pour accéder aux sites web au lieu de passer par une recherche Google.', 'I used bookmarks to access websites instead of searching on Google.', 5, 0.01, 'facile', 'ecologique', 'Général', 1, 5, NULL),
(14, 'Cloud Detox', 'Cloud Detox', 'J\'ai supprimé des fichiers lourds et inutiles de mon stockage en ligne (Drive, OneDrive, iCloud).', 'I deleted large and useless files from my online storage (Drive, OneDrive, iCloud).', 25, 0.5, 'moyen', 'ecologique', 'Général', 1, 1, NULL),
(15, 'Typographie Éco', 'Eco-Font Warrior', 'J\'ai utilisé une police économe en encre (Century Gothic, Ecofont) ou le \"Mode Sombre\" pour travailler.', 'I used an ink-saving font (Century Gothic, Ecofont) or \"Dark Mode\" to work.', 10, 0.01, 'facile', 'ecologique', 'Général', 1, 1, NULL),
(16, 'L\'Escalier Sportif', 'Take the Stairs', 'J\'ai pris les escaliers au lieu de l\'ascenseur (montée ou descente).', 'I took the stairs instead of the elevator (up or down).', 10, 0.05, 'facile', 'ecologique', 'Général', 1, 4, NULL),
(17, 'Lumière Naturelle', 'Natural Light', 'J\'ai éteint la lumière dans mon bureau ou une salle de réunion inoccupée.', 'I turned off the lights in my office or an empty meeting room.', 10, 0.1, 'facile', 'ecologique', 'Général', 1, 2, NULL),
(18, 'Zéro Papier', 'Paperless Day', 'Je n\'ai rien imprimé de la journée (ou j\'ai imprimé en R/V et Noir & Blanc strict).', 'I didn\'t print anything today (or used double-sided Black & White only).', 25, 0.2, 'moyen', 'ecologique', 'Général', 1, 1, NULL),
(19, 'Pull Over Chauffage', 'Sweater Weather', 'J\'ai mis un pull au lieu d\'augmenter le chauffage (ou j\'ai baissé le thermostat de 1°C).', 'I put on a sweater instead of turning up the heat (or lowered the thermostat by 1°C).', 30, 1, 'moyen', 'ecologique', 'Général', 1, 1, NULL),
(20, 'Chasse aux Courants d\'Air', 'Climate Control', 'J\'ai fermé les fenêtres et les portes car le chauffage ou la climatisation était allumé.', 'I closed windows and doors because the heating or air conditioning was on.', 15, 0.8, 'facile', 'ecologique', 'Général', 1, 2, NULL),
(21, 'Maître du Brouillon', 'Draft Paper Master', 'J\'ai réutilisé le verso d\'une feuille imprimée pour prendre des notes au lieu d\'une feuille neuve.', 'I reused the back of a printed sheet for notes instead of a new one.', 10, 0.05, 'facile', 'ecologique', 'Général', 1, 3, NULL),
(22, 'Parrainage Écologique', 'Eco-Sponsor', 'J\'ai convaincu un collègue de s\'inscrire sur Shift\'Up aujourd\'hui.', 'I convinced a colleague to sign up for Shift\'Up today.', 100, 0, 'difficile', 'ecologique', 'Général', 1, 1, NULL),
(23, 'Partage de Réussite', 'Success Sharing', 'J\'ai posté une astuce ou une réussite sur le mur social de l\'entreprise.', 'I posted a tip or a success story on the company social wall.', 15, 0, 'facile', 'ecologique', 'Général', 1, 1, NULL),
(24, 'Tri Sélectif', 'Recycling Pro', 'J\'ai correctement trié mes déchets (papier, plastique, verre) dans les bacs de l\'entreprise.', 'I correctly sorted my waste (paper, plastic, glass) in the company bins.', 10, 0.1, 'facile', 'ecologique', 'Général', 1, 2, NULL),
(25, 'Seconde Main', 'Second Hand', 'J\'ai acheté ou apporté un vêtement/objet de seconde main au lieu du neuf.', 'I bought or brought a second-hand item instead of a new one.', 40, 2, 'moyen', 'ecologique', 'Général', 1, 1, NULL),
(26, 'Main Verte : Sauver l\'Orchidée', 'Green Thumb: Save the Orchid', 'Arrosez la plante du bureau tous les jours pendant 2 semaines.', 'Water the office plant every day for 2 weeks.', 500, 0.5, 'moyen', 'ecologique', 'Général', 14, 1, NULL);

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
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `companies`
--

INSERT INTO `companies` (`id`, `nom`, `secteur`, `logo_url`, `code_invite`, `created_at`) VALUES
(1, 'test1', '1', '120', '1234', '2026-02-03 19:08:56');

-- --------------------------------------------------------

--
-- Structure de la table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `company_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `cost` int(11) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `company_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `role` enum('shifter','admin','super_admin') DEFAULT 'shifter',
  `points_wallet` int(11) DEFAULT 0,
  `points_rank` int(11) DEFAULT 0,
  `initial_footprint_kg` decimal(5,2) DEFAULT 32.60,
  `total_carbon_saved` float DEFAULT 0,
  `current_streak` int(11) DEFAULT 0,
  `last_activity` date DEFAULT NULL,
  `est_actif` tinyint(1) DEFAULT 1,
  `language_pref` varchar(5) DEFAULT 'fr',
  `company_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `pseudo`, `role`, `points_wallet`, `points_rank`, `initial_footprint_kg`, `total_carbon_saved`, `current_streak`, `last_activity`, `est_actif`, `language_pref`, `company_id`, `department_id`) VALUES
(1, 'ryan@gmail.com', '$2y$10$HHaKIT3bmlJ8VZdS8U7xbe4a5Ym5zDqQ.c.MS0f4HQhhtBpN/BR.O', 'ryan', 'shifter', 10010669, 12170, 32.60, 123.49, 1, '2026-02-12', 1, 'en', 1, NULL),
(2, 'sophie@shiftup.com', '$2y$10$HHaKIT3bmlJ8VZdS8U7xbe4a5Ym5zDqQ.c.MS0f4HQhhtBpN/BR.O', 'Sophie RSE', 'shifter', 1280, 1280, 28.50, 48.9, 5, '2026-02-12', 1, 'fr', 1, NULL),
(3, 'marc@shiftup.com', '$2y$10$HHaKIT3bmlJ8VZdS8U7xbe4a5Ym5zDqQ.c.MS0f4HQhhtBpN/BR.O', 'Marc Vitesse', 'shifter', 4800, 4800, 35.00, 150.5, 21, '2026-02-12', 1, 'fr', 1, NULL),
(4, 'julie@shiftup.com', '$2y$10$HHaKIT3bmlJ8VZdS8U7xbe4a5Ym5zDqQ.c.MS0f4HQhhtBpN/BR.O', 'Julie Green', 'shifter', 15450, 15450, 18.20, 522.5, 43, '2026-02-13', 1, 'fr', 1, NULL),
(5, 'thomas@shiftup.com', '$2y$10$HHaKIT3bmlJ8VZdS8U7xbe4a5Ym5zDqQ.c.MS0f4HQhhtBpN/BR.O', 'Thomas Novice', 'shifter', 50, 50, 32.60, 2.5, 0, '2026-02-07', 1, 'fr', 1, NULL);

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
(87, 4, 1, '2026-02-13 08:46:00');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `social_posts`
--
ALTER TABLE `social_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `user_actions`
--
ALTER TABLE `user_actions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

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
