<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261007192538 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE circuit_time ADD minutes INT NOT NULL, ADD seconds INT NOT NULL, ADD car_id INT NOT NULL, DROP time');
        $this->addSql('ALTER TABLE circuit_time ADD CONSTRAINT FK_66CFEB8C3C6F69F FOREIGN KEY (car_id) REFERENCES car (id)');
        $this->addSql('CREATE INDEX IDX_66CFEB8C3C6F69F ON circuit_time (car_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE circuit_time DROP FOREIGN KEY FK_66CFEB8C3C6F69F');
        $this->addSql('DROP INDEX IDX_66CFEB8C3C6F69F ON circuit_time');
        $this->addSql('ALTER TABLE circuit_time ADD time TIME NOT NULL, DROP minutes, DROP seconds, DROP car_id');
    }
}
