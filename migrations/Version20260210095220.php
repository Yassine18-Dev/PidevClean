<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260210095220 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE shop_order_item ADD size_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE shop_order_item ADD CONSTRAINT FK_2899F22F498DA827 FOREIGN KEY (size_id) REFERENCES size (id)');
        $this->addSql('CREATE INDEX IDX_2899F22F498DA827 ON shop_order_item (size_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE shop_order_item DROP FOREIGN KEY FK_2899F22F498DA827');
        $this->addSql('DROP INDEX IDX_2899F22F498DA827 ON shop_order_item');
        $this->addSql('ALTER TABLE shop_order_item DROP size_id');
    }
}
