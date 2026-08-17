<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260812091017 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE circuit ADD country_id INT NOT NULL');
        $this->addSql('ALTER TABLE circuit ADD CONSTRAINT FK_1325F3A6F92F3E70 FOREIGN KEY (country_id) REFERENCES country (id)');
        $this->addSql('CREATE INDEX IDX_1325F3A6F92F3E70 ON circuit (country_id)');
        $this->addSql('ALTER TABLE country CHANGE name name VARCHAR(40) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE circuit DROP FOREIGN KEY FK_1325F3A6F92F3E70');
        $this->addSql('DROP INDEX IDX_1325F3A6F92F3E70 ON circuit');
        $this->addSql('ALTER TABLE circuit DROP country_id');
        $this->addSql('ALTER TABLE country CHANGE name name VARCHAR(50) NOT NULL');
    }
}
