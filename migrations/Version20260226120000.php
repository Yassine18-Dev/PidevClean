<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260226120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add image_url column to game table';
    }

    public function up(Schema $schema): void
    {
        // Doctrine naming strategy => image_url
        $this->addSql("ALTER TABLE game ADD image_url VARCHAR(255) NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE game DROP image_url");
    }
}