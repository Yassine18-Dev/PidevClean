<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260218110905 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE game (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, max_players INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE player (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, photo VARCHAR(255) DEFAULT NULL, team_id INT NOT NULL, INDEX IDX_98197A65296CD8AE (team_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE shop_order (id INT AUTO_INCREMENT NOT NULL, total DOUBLE PRECISION NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_323FC9CAA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE shop_order_item (id INT AUTO_INCREMENT NOT NULL, quantity INT NOT NULL, price DOUBLE PRECISION NOT NULL, order_id INT NOT NULL, product_id INT NOT NULL, size_id INT DEFAULT NULL, INDEX IDX_2899F22F8D9F6D38 (order_id), INDEX IDX_2899F22F4584665A (product_id), INDEX IDX_2899F22F498DA827 (size_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE shop_product (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, price DOUBLE PRECISION NOT NULL, type VARCHAR(20) NOT NULL, is_active TINYINT NOT NULL, image VARCHAR(255) DEFAULT NULL, game_id INT DEFAULT NULL, INDEX IDX_D0794487E48FD905 (game_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE shop_product_size (shop_product_id INT NOT NULL, size_id INT NOT NULL, INDEX IDX_674D61593FF78B7C (shop_product_id), INDEX IDX_674D6159498DA827 (size_id), PRIMARY KEY (shop_product_id, size_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE shop_product_image (id INT AUTO_INCREMENT NOT NULL, filename VARCHAR(255) NOT NULL, product_id INT NOT NULL, INDEX IDX_7A7DE80C4584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE size (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE team (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, username VARCHAR(50) NOT NULL, role_type VARCHAR(20) NOT NULL, status VARCHAR(20) NOT NULL, bio LONGTEXT DEFAULT NULL, favorite_game VARCHAR(50) DEFAULT NULL, created_at DATETIME NOT NULL, last_activity_at DATETIME DEFAULT NULL, reset_token VARCHAR(64) DEFAULT NULL, reset_expires_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT FK_98197A65296CD8AE FOREIGN KEY (team_id) REFERENCES team (id)');
        $this->addSql('ALTER TABLE shop_order ADD CONSTRAINT FK_323FC9CAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE shop_order_item ADD CONSTRAINT FK_2899F22F8D9F6D38 FOREIGN KEY (order_id) REFERENCES shop_order (id)');
        $this->addSql('ALTER TABLE shop_order_item ADD CONSTRAINT FK_2899F22F4584665A FOREIGN KEY (product_id) REFERENCES shop_product (id)');
        $this->addSql('ALTER TABLE shop_order_item ADD CONSTRAINT FK_2899F22F498DA827 FOREIGN KEY (size_id) REFERENCES size (id)');
        $this->addSql('ALTER TABLE shop_product ADD CONSTRAINT FK_D0794487E48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
        $this->addSql('ALTER TABLE shop_product_size ADD CONSTRAINT FK_674D61593FF78B7C FOREIGN KEY (shop_product_id) REFERENCES shop_product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE shop_product_size ADD CONSTRAINT FK_674D6159498DA827 FOREIGN KEY (size_id) REFERENCES size (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE shop_product_image ADD CONSTRAINT FK_7A7DE80C4584665A FOREIGN KEY (product_id) REFERENCES shop_product (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE player DROP FOREIGN KEY FK_98197A65296CD8AE');
        $this->addSql('ALTER TABLE shop_order DROP FOREIGN KEY FK_323FC9CAA76ED395');
        $this->addSql('ALTER TABLE shop_order_item DROP FOREIGN KEY FK_2899F22F8D9F6D38');
        $this->addSql('ALTER TABLE shop_order_item DROP FOREIGN KEY FK_2899F22F4584665A');
        $this->addSql('ALTER TABLE shop_order_item DROP FOREIGN KEY FK_2899F22F498DA827');
        $this->addSql('ALTER TABLE shop_product DROP FOREIGN KEY FK_D0794487E48FD905');
        $this->addSql('ALTER TABLE shop_product_size DROP FOREIGN KEY FK_674D61593FF78B7C');
        $this->addSql('ALTER TABLE shop_product_size DROP FOREIGN KEY FK_674D6159498DA827');
        $this->addSql('ALTER TABLE shop_product_image DROP FOREIGN KEY FK_7A7DE80C4584665A');
        $this->addSql('DROP TABLE game');
        $this->addSql('DROP TABLE player');
        $this->addSql('DROP TABLE shop_order');
        $this->addSql('DROP TABLE shop_order_item');
        $this->addSql('DROP TABLE shop_product');
        $this->addSql('DROP TABLE shop_product_size');
        $this->addSql('DROP TABLE shop_product_image');
        $this->addSql('DROP TABLE size');
        $this->addSql('DROP TABLE team');
        $this->addSql('DROP TABLE user');
    }
}
