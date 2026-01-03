SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


CREATE TABLE `games` (
  `id` int(11) NOT NULL,
  `player1_id` varchar(50) DEFAULT NULL,
  `player2_id` varchar(50) DEFAULT NULL,
  `current_player` varchar(50) DEFAULT NULL,
  `dice1` int(11) DEFAULT NULL,
  `dice2` int(11) DEFAULT NULL,
  `board_state` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`board_state`)),
  `status` enum('waiting','active','finished') DEFAULT 'waiting',
  `winner` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `moves` (
  `id` int(11) NOT NULL,
  `game_id` int(11) DEFAULT NULL,
  `player_id` varchar(50) DEFAULT NULL,
  `move_from` int(11) DEFAULT NULL,
  `move_to` int(11) DEFAULT NULL,
  `dice_used` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `players` (
  `id` varchar(50) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


ALTER TABLE `games`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `moves`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`);

ALTER TABLE `players`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

ALTER TABLE `games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `moves`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


ALTER TABLE `moves`
  ADD CONSTRAINT `moves_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`);
COMMIT;

