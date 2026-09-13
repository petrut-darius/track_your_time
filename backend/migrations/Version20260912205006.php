<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260912205006 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE circuit_time DROP FOREIGN KEY `FK_66CFEB8A76ED395`');
        $this->addSql('ALTER TABLE circuit_time DROP FOREIGN KEY `FK_66CFEB8CF2182C8`');
        $this->addSql('ALTER TABLE circuit_time ADD CONSTRAINT FK_66CFEB8A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE circuit_time ADD CONSTRAINT FK_66CFEB8CF2182C8 FOREIGN KEY (circuit_id) REFERENCES circuit (id)');
        $this->addSql('ALTER TABLE friendship DROP FOREIGN KEY `FK_7234A45F6A5458E8`');
        $this->addSql('ALTER TABLE friendship DROP FOREIGN KEY `FK_7234A45FA76ED395`');
        $this->addSql('ALTER TABLE friendship ADD status VARCHAR(10) NOT NULL, CHANGE user_id user_id INT DEFAULT NULL, CHANGE friend_id friend_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE friendship ADD CONSTRAINT FK_7234A45F6A5458E8 FOREIGN KEY (friend_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE friendship ADD CONSTRAINT FK_7234A45FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user CHANGE avatar avatar VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE circuit_time DROP FOREIGN KEY FK_66CFEB8A76ED395');
        $this->addSql('ALTER TABLE circuit_time DROP FOREIGN KEY FK_66CFEB8CF2182C8');
        $this->addSql('ALTER TABLE circuit_time ADD CONSTRAINT `FK_66CFEB8A76ED395` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE circuit_time ADD CONSTRAINT `FK_66CFEB8CF2182C8` FOREIGN KEY (circuit_id) REFERENCES circuit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE friendship DROP FOREIGN KEY FK_7234A45FA76ED395');
        $this->addSql('ALTER TABLE friendship DROP FOREIGN KEY FK_7234A45F6A5458E8');
        $this->addSql('ALTER TABLE friendship DROP status, CHANGE user_id user_id INT NOT NULL, CHANGE friend_id friend_id INT NOT NULL');
        $this->addSql('ALTER TABLE friendship ADD CONSTRAINT `FK_7234A45FA76ED395` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE friendship ADD CONSTRAINT `FK_7234A45F6A5458E8` FOREIGN KEY (friend_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user CHANGE avatar avatar JSON DEFAULT NULL');
    }
}
