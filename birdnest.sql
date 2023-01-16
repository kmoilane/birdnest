DROP TABLE IF EXISTS `violations`;
CREATE TABLE IF NOT EXISTS `violations` (
  `drone_serial_number` varchar(255) NOT NULL,
  `pilot_fname` varchar(50) NOT NULL,
  `pilot_lname` varchar(50) NOT NULL,
  `pilot_email` varchar(255) NOT NULL,
  `pilot_phone_number` varchar(20) NOT NULL,
  `closest_distance` int NOT NULL,
  `last_seen` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `closest_x` float NOT NULL,
  `closest_y` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
COMMIT;
