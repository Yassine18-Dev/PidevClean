<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260206132317 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE game (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, max_players INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE shop_order (id INT AUTO_INCREMENT NOT NULL, total DOUBLE PRECISION NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_323FC9CAA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE shop_order_item (id INT AUTO_INCREMENT NOT NULL, quantity INT NOT NULL, price DOUBLE PRECISION NOT NULL, order_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_2899F22F8D9F6D38 (order_id), INDEX IDX_2899F22F4584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE shop_product (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, price DOUBLE PRECISION NOT NULL, type VARCHAR(20) NOT NULL, image VARCHAR(255) DEFAULT NULL, game_id INT DEFAULT NULL, INDEX IDX_D0794487E48FD905 (game_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE shop_product_image (id INT AUTO_INCREMENT NOT NULL, filename VARCHAR(255) NOT NULL, product_id INT NOT NULL, INDEX IDX_7A7DE80C4584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE shop_order ADD CONSTRAINT FK_323FC9CAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE shop_order_item ADD CONSTRAINT FK_2899F22F8D9F6D38 FOREIGN KEY (order_id) REFERENCES shop_order (id)');
        $this->addSql('ALTER TABLE shop_order_item ADD CONSTRAINT FK_2899F22F4584665A FOREIGN KEY (product_id) REFERENCES shop_product (id)');
        $this->addSql('ALTER TABLE shop_product ADD CONSTRAINT FK_D0794487E48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
        $this->addSql('ALTER TABLE shop_product_image ADD CONSTRAINT FK_7A7DE80C4584665A FOREIGN KEY (product_id) REFERENCES shop_product (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE shop_order DROP FOREIGN KEY FK_323FC9CAA76ED395');
        $this->addSql('ALTER TABLE shop_order_item DROP FOREIGN KEY FK_2899F22F8D9F6D38');
        $this->addSql('ALTER TABLE shop_order_item DROP FOREIGN KEY FK_2899F22F4584665A');
        $this->addSql('ALTER TABLE shop_product DROP FOREIGN KEY FK_D0794487E48FD905');
        $this->addSql('ALTER TABLE shop_product_image DROP FOREIGN KEY FK_7A7DE80C4584665A');
        $this->addSql('DROP TABLE game');
        $this->addSql('DROP TABLE shop_order');
        $this->addSql('DROP TABLE shop_order_item');
        $this->addSql('DROP TABLE shop_product');
        $this->addSql('DROP TABLE shop_product_image');
    }
}
