<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260208184748 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE shop_product_size (shop_product_id INT NOT NULL, size_id INT NOT NULL, INDEX IDX_674D61593FF78B7C (shop_product_id), INDEX IDX_674D6159498DA827 (size_id), PRIMARY KEY (shop_product_id, size_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE size (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE shop_product_size ADD CONSTRAINT FK_674D61593FF78B7C FOREIGN KEY (shop_product_id) REFERENCES shop_product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE shop_product_size ADD CONSTRAINT FK_674D6159498DA827 FOREIGN KEY (size_id) REFERENCES size (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE shop_product_size DROP FOREIGN KEY FK_674D61593FF78B7C');
        $this->addSql('ALTER TABLE shop_product_size DROP FOREIGN KEY FK_674D6159498DA827');
        $this->addSql('DROP TABLE shop_product_size');
        $this->addSql('DROP TABLE size');
    }
}
