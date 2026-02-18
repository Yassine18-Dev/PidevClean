<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260218172000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add tournament entity for ArenaMind (module Jeux & Tournois)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE tournament (id INT AUTO_INCREMENT NOT NULL, game_id INT NOT NULL, name VARCHAR(140) NOT NULL, start_at DATETIME NOT NULL, check_in_at DATETIME DEFAULT NULL, slots INT NOT NULL, format VARCHAR(40) NOT NULL, prize VARCHAR(60) NOT NULL, rules VARCHAR(60) NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_9D1A9B5BE48FD905 (game_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tournament ADD CONSTRAINT FK_9D1A9B5BE48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tournament DROP FOREIGN KEY FK_9D1A9B5BE48FD905');
        $this->addSql('DROP TABLE tournament');
    }
}
