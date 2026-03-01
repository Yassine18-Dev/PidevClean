<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260228222027 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE promotion CHANGE value value NUMERIC(5, 2) NOT NULL');
        $this->addSql('ALTER TABLE promotion CHANGE min_amount min_amount NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE shop_order CHANGE total total NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE shop_order_item CHANGE price price NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE shop_product CHANGE price price NUMERIC(10, 2) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_C11D7DD177153098 ON promotion');
        $this->addSql('ALTER TABLE promotion CHANGE value value NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE shop_order CHANGE total total DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE shop_order_item CHANGE price price DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE shop_product CHANGE price price DOUBLE PRECISION NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
    }
}
