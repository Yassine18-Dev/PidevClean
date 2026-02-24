-- ArenaMind - Team/Player Invitations (Team -> Player)
-- MariaDB/MySQL SQL script
--
-- NOTE:
-- - This script is meant to be imported via phpMyAdmin.
-- - If you already have data you want to keep, review the ALTER/DROP lines before running.

START TRANSACTION;

/* =============================
   TEAM
   ============================= */

-- Adds required fields for Team
ALTER TABLE team
  ADD COLUMN game VARCHAR(20) NOT NULL DEFAULT 'lol',
  ADD COLUMN logo_name VARCHAR(255) DEFAULT NULL,
  ADD COLUMN max_players INT NOT NULL DEFAULT 5;

/* =============================
   PLAYER
   ============================= */

-- Minimal alignment with the Player entity used in this ZIP.
-- If your table already uses different column names, adapt the CHANGE lines accordingly.

-- 1) Ensure team_id is nullable (player may have no team)
ALTER TABLE player
  MODIFY team_id INT DEFAULT NULL;

-- 2) Rename name -> nickname (if you still use "name")
-- If you already have nickname, comment this line.
ALTER TABLE player
  CHANGE name nickname VARCHAR(80) NOT NULL;

-- 3) Add new columns
ALTER TABLE player
  ADD COLUMN game VARCHAR(20) NOT NULL DEFAULT 'lol',
  ADD COLUMN `rank` VARCHAR(30) DEFAULT NULL,
  ADD COLUMN avatar_name VARCHAR(255) DEFAULT NULL,
  ADD COLUMN user_id INT DEFAULT NULL;

-- 4) Add foreign keys (skip if they already exist)
ALTER TABLE player
  ADD CONSTRAINT FK_PLAYER_TEAM FOREIGN KEY (team_id) REFERENCES team (id) ON DELETE SET NULL,
  ADD CONSTRAINT FK_PLAYER_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL;

CREATE INDEX IDX_PLAYER_TEAM ON player (team_id);
CREATE INDEX IDX_PLAYER_USER ON player (user_id);

/* =============================
   INVITATION
   ============================= */

DROP TABLE IF EXISTS invitation;

CREATE TABLE invitation (
  id INT AUTO_INCREMENT NOT NULL,
  team_id INT NOT NULL,
  player_id INT NOT NULL,
  invited_by_id INT NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL,
  expires_at DATETIME DEFAULT NULL,
  INDEX IDX_INV_TEAM (team_id),
  INDEX IDX_INV_PLAYER (player_id),
  INDEX IDX_INV_INVITED_BY (invited_by_id),
  PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

ALTER TABLE invitation
  ADD CONSTRAINT FK_INV_TEAM FOREIGN KEY (team_id) REFERENCES team (id) ON DELETE CASCADE,
  ADD CONSTRAINT FK_INV_PLAYER FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE,
  ADD CONSTRAINT FK_INV_INVITED_BY FOREIGN KEY (invited_by_id) REFERENCES player (id) ON DELETE CASCADE;

COMMIT;
