-- team-player-module-complet-v2 : DB update
-- Adds:
-- - player.discord_avatar (hash)
-- - team.banner_name (filename)
-- Note: adjust table names if your schema differs.

ALTER TABLE player ADD COLUMN discord_avatar VARCHAR(64) DEFAULT NULL AFTER discord_avatar_url;
ALTER TABLE team ADD COLUMN banner_name VARCHAR(255) DEFAULT NULL AFTER logo_name;

-- Optional: invitation status 'expired' is stored in same status column (no schema change)
