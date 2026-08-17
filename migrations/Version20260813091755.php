<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260813091755 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
      //  $this->addSql('CREATE TABLE configuration (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        //$this->addSql('CREATE TABLE configuration_circuit (configuration_id INT NOT NULL, circuit_id INT NOT NULL, INDEX IDX_9D6D3B7873F32DD8 (configuration_id), INDEX IDX_9D6D3B78CF2182C8 (circuit_id), PRIMARY KEY (configuration_id, circuit_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE grade (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE grade_circuit (grade_id INT NOT NULL, circuit_id INT NOT NULL, INDEX IDX_E9686144FE19A1A8 (grade_id), INDEX IDX_E9686144CF2182C8 (circuit_id), PRIMARY KEY (grade_id, circuit_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE status_circuit (status_id INT NOT NULL, circuit_id INT NOT NULL, INDEX IDX_1BF584236BF700BD (status_id), INDEX IDX_1BF58423CF2182C8 (circuit_id), PRIMARY KEY (status_id, circuit_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE type_circuit (type_id INT NOT NULL, circuit_id INT NOT NULL, INDEX IDX_3B049C53C54C8C93 (type_id), INDEX IDX_3B049C53CF2182C8 (circuit_id), PRIMARY KEY (type_id, circuit_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
    //    $this->addSql('ALTER TABLE configuration_circuit ADD CONSTRAINT FK_9D6D3B7873F32DD8 FOREIGN KEY (configuration_id) REFERENCES configuration (id) ON DELETE CASCADE');
      //  $this->addSql('ALTER TABLE configuration_circuit ADD CONSTRAINT FK_9D6D3B78CF2182C8 FOREIGN KEY (circuit_id) REFERENCES circuit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE grade_circuit ADD CONSTRAINT FK_E9686144FE19A1A8 FOREIGN KEY (grade_id) REFERENCES grade (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE grade_circuit ADD CONSTRAINT FK_E9686144CF2182C8 FOREIGN KEY (circuit_id) REFERENCES circuit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE status_circuit ADD CONSTRAINT FK_1BF584236BF700BD FOREIGN KEY (status_id) REFERENCES status (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE status_circuit ADD CONSTRAINT FK_1BF58423CF2182C8 FOREIGN KEY (circuit_id) REFERENCES circuit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE type_circuit ADD CONSTRAINT FK_3B049C53C54C8C93 FOREIGN KEY (type_id) REFERENCES type (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE type_circuit ADD CONSTRAINT FK_3B049C53CF2182C8 FOREIGN KEY (circuit_id) REFERENCES circuit (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
       // $this->addSql('ALTER TABLE configuration_circuit DROP FOREIGN KEY FK_9D6D3B7873F32DD8');
       // $this->addSql('ALTER TABLE configuration_circuit DROP FOREIGN KEY FK_9D6D3B78CF2182C8');
        $this->addSql('ALTER TABLE grade_circuit DROP FOREIGN KEY FK_E9686144FE19A1A8');
        $this->addSql('ALTER TABLE grade_circuit DROP FOREIGN KEY FK_E9686144CF2182C8');
        $this->addSql('ALTER TABLE status_circuit DROP FOREIGN KEY FK_1BF584236BF700BD');
        $this->addSql('ALTER TABLE status_circuit DROP FOREIGN KEY FK_1BF58423CF2182C8');
        $this->addSql('ALTER TABLE type_circuit DROP FOREIGN KEY FK_3B049C53C54C8C93');
        $this->addSql('ALTER TABLE type_circuit DROP FOREIGN KEY FK_3B049C53CF2182C8');
       // $this->addSql('DROP TABLE configuration');
       // $this->addSql('DROP TABLE configuration_circuit');
        $this->addSql('DROP TABLE grade');
        $this->addSql('DROP TABLE grade_circuit');
        $this->addSql('DROP TABLE status_circuit');
        $this->addSql('DROP TABLE type');
        $this->addSql('DROP TABLE type_circuit');
    }
}
