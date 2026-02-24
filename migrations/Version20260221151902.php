<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260221151902 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE promotion (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, type VARCHAR(20) NOT NULL, value DOUBLE PRECISION NOT NULL, min_amount DOUBLE PRECISION NOT NULL, start_date DATETIME NOT NULL, end_date DATETIME NOT NULL, is_active TINYINT NOT NULL, is_public TINYINT NOT NULL, apply_to_all_products TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE promotion_products (promotion_id INT NOT NULL, shop_product_id INT NOT NULL, INDEX IDX_75EEFE1E139DF194 (promotion_id), INDEX IDX_75EEFE1E3FF78B7C (shop_product_id), PRIMARY KEY (promotion_id, shop_product_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE promotion_products ADD CONSTRAINT FK_75EEFE1E139DF194 FOREIGN KEY (promotion_id) REFERENCES promotion (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE promotion_products ADD CONSTRAINT FK_75EEFE1E3FF78B7C FOREIGN KEY (shop_product_id) REFERENCES shop_product (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE promotion_products DROP FOREIGN KEY FK_75EEFE1E139DF194');
        $this->addSql('ALTER TABLE promotion_products DROP FOREIGN KEY FK_75EEFE1E3FF78B7C');
        $this->addSql('DROP TABLE promotion');
        $this->addSql('DROP TABLE promotion_products');
    }
}
