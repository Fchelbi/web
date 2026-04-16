-- Consultation module additions for the provided project.sql dump.
-- Run this after importing project (1).sql.
-- It keeps the existing tables unchanged and adds only what the consultation feature needs.

START TRANSACTION;

--
-- Add at least one Coach user for the psychologue dropdown.
-- The consultation logic uses users where role = 'Coach'.
--
INSERT INTO `user` (`nom`, `prenom`, `email`, `mdp`, `role`, `num_tel`, `photo`)
SELECT 'Coach', 'Demo', 'coach@test.com', '1234', 'Coach', '22334455', NULL
WHERE NOT EXISTS (
    SELECT 1
    FROM `user`
    WHERE `email` = 'coach@test.com'
);

--
-- Table structure for table `consultation_en_ligne`
--
CREATE TABLE IF NOT EXISTS `consultation_en_ligne` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `psychologue_id` int NOT NULL,
  `date_consultation` datetime NOT NULL,
  `motif` varchar(255) NOT NULL,
  `statut` enum('en_attente','confirmée','annulée') NOT NULL DEFAULT 'en_attente',
  `meet_link` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_consultation_user` (`user_id`),
  KEY `idx_consultation_psychologue` (`psychologue_id`),
  CONSTRAINT `fk_consultation_user`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id_user`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_consultation_psychologue`
    FOREIGN KEY (`psychologue_id`) REFERENCES `user` (`id_user`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

COMMIT;
