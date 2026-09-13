<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260910184010 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE circuit_time (id INT AUTO_INCREMENT NOT NULL, time TIME NOT NULL, user_id INT NOT NULL, circuit_id INT NOT NULL, INDEX IDX_66CFEB8A76ED395 (user_id), INDEX IDX_66CFEB8CF2182C8 (circuit_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE circuit_time ADD CONSTRAINT FK_66CFEB8A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE circuit_time ADD CONSTRAINT FK_66CFEB8CF2182C8 FOREIGN KEY (circuit_id) REFERENCES circuit (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE circuit_time DROP FOREIGN KEY FK_66CFEB8A76ED395');
        $this->addSql('ALTER TABLE circuit_time DROP FOREIGN KEY FK_66CFEB8CF2182C8');
        $this->addSql('DROP TABLE circuit_time');
    }
}
