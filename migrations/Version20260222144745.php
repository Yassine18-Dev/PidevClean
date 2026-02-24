<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260222144745 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Ajouter les colonnes createdAt et updatedAt à la table shop_product
        $this->addSql('ALTER TABLE shop_product ADD created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, ADD updated_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Supprimer les colonnes createdAt et updatedAt de la table shop_product
        $this->addSql('ALTER TABLE shop_product DROP created_at, DROP updated_at');
    }
}
