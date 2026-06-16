-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: db_server
-- Erstellungszeit: 16. Jun 2026 um 01:08
-- Server-Version: 9.4.0
-- PHP-Version: 8.2.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `Playerstats`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `Event`
--

CREATE TABLE `Event` (
  `event_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `multiplier` double DEFAULT '1',
  `duration` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Daten für Tabelle `Event`
--

INSERT INTO `Event` (`event_id`, `name`, `description`, `multiplier`, `duration`) VALUES
(1, 'Double Money', 'Alle Einnahmen sind für kurze Zeit verdoppelt!', 2, 300),
(2, 'Golden Rush', 'Die Chance auf goldene Mutationen ist extrem erhöht.', 1.5, 600),
(3, 'Crypto Crash', 'Die Preise im Shop sind drastisch gesunken.', 0.5, 450),
(4, 'Cyber Attack', 'Gefahr im Netz! Schütze deine Systeme.', 0.8, 200),
(5, 'Hyperdrive Active', 'Arbeitsgeschwindigkeit ist verdreifacht!', 3, 150),
(6, 'Tax Haven', 'Steuerfreie Zone – behalte jeden verdienten Cent.', 1.8, 400);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `Mutation`
--

CREATE TABLE `Mutation` (
  `mutation_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `variant_id` int DEFAULT NULL,
  `multiplier` double DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Daten für Tabelle `Mutation`
--

INSERT INTO `Mutation` (`mutation_id`, `name`, `variant_id`, `multiplier`) VALUES
(1, 'Zombie Virus', 1, 1.2),
(2, 'Cybernetic Upgrade', 1, 1.5),
(3, 'Nano Bots', 1, 1.8),
(4, 'Radiation Leak', 1, 2),
(5, 'Alien DNA', 1, 2.2),
(6, 'Golden Touch', 2, 2.5),
(7, 'Midas Breath', 2, 3),
(8, 'Alchemist Spark', 2, 3.5),
(9, 'Gilded Core', 2, 4),
(10, 'Diamond Mind', 3, 5),
(11, 'Quantum Crystal', 3, 6.5),
(12, 'Plasma Gaze', 3, 8);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `PlayerState`
--

CREATE TABLE `PlayerState` (
  `player_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rebirths` int DEFAULT '0',
  `health` int DEFAULT '100',
  `isAlive` tinyint DEFAULT '1',
  `score` int DEFAULT '0',
  `money_multiplier` float NOT NULL,
  `speed_multiplier` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Daten für Tabelle `PlayerState`
--

INSERT INTO `PlayerState` (`player_id`, `name`, `password`, `rebirths`, `health`, `isAlive`, `score`, `money_multiplier`, `speed_multiplier`) VALUES
(1, 'a', '$2y$10$/TeNmv.oqHvCY1NtCVvEf.w5H1yN2GrfHl3BkcEmvh2rQg3jpSbSG', 1, 100, 1, 0, 0, 0),
(2, 'admin', '$2y$10$TIcF4VQTMmuf6qvPb9QD9eXuQASIK40mkPhNoYkXtw54VohWuNoqa', 25, 100, 1, 2298718, 0, 0),
(3, 'hi', '$2y$10$SPejWwVx2va4dOPvqPLyDOZWzRN3qQkmuRtAr3WMKvTOiKjB7Fpdq', 0, 100, 1, 0, 0, 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `Product`
--

CREATE TABLE `Product` (
  `product_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` int NOT NULL,
  `price` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Daten für Tabelle `Product`
--

INSERT INTO `Product` (`product_id`, `name`, `value`, `price`) VALUES
(1, 'Standard Pack', 100, 10),
(2, 'Super Pack', 500, 45),
(3, 'Mega Pack', 1500, 120),
(4, 'Ultra Pack', 5000, 350),
(5, 'Hyper Bundle', 12000, 800),
(6, 'Omega Chest', 30000, 1800),
(7, 'Infinity Box', 75000, 4000);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `Variant`
--

CREATE TABLE `Variant` (
  `variant_id` int NOT NULL,
  `variant` varchar(50) NOT NULL,
  `multiplier` double DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Daten für Tabelle `Variant`
--

INSERT INTO `Variant` (`variant_id`, `variant`, `multiplier`) VALUES
(1, 'Normal', 1),
(2, 'Golden', 2),
(3, 'Diamond', 3);

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `Event`
--
ALTER TABLE `Event`
  ADD PRIMARY KEY (`event_id`);

--
-- Indizes für die Tabelle `Mutation`
--
ALTER TABLE `Mutation`
  ADD PRIMARY KEY (`mutation_id`),
  ADD KEY `variant_id` (`variant_id`);

--
-- Indizes für die Tabelle `PlayerState`
--
ALTER TABLE `PlayerState`
  ADD PRIMARY KEY (`player_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indizes für die Tabelle `Product`
--
ALTER TABLE `Product`
  ADD PRIMARY KEY (`product_id`);

--
-- Indizes für die Tabelle `Variant`
--
ALTER TABLE `Variant`
  ADD PRIMARY KEY (`variant_id`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `Event`
--
ALTER TABLE `Event`
  MODIFY `event_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT für Tabelle `Mutation`
--
ALTER TABLE `Mutation`
  MODIFY `mutation_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT für Tabelle `PlayerState`
--
ALTER TABLE `PlayerState`
  MODIFY `player_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT für Tabelle `Product`
--
ALTER TABLE `Product`
  MODIFY `product_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT für Tabelle `Variant`
--
ALTER TABLE `Variant`
  MODIFY `variant_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `Mutation`
--
ALTER TABLE `Mutation`
  ADD CONSTRAINT `Mutation_ibfk_1` FOREIGN KEY (`variant_id`) REFERENCES `Variant` (`variant_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
