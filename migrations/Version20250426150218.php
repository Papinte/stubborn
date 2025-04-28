<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250426150218 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE stock (id INT AUTO_INCREMENT NOT NULL, sweatshirt_id INT DEFAULT NULL, size VARCHAR(255) NOT NULL, quantity INT NOT NULL, INDEX IDX_4B365660A143AB7B (sweatshirt_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE stock ADD CONSTRAINT FK_4B365660A143AB7B FOREIGN KEY (sweatshirt_id) REFERENCES sweatshirt (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sweatshirt DROP stock
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE stock DROP FOREIGN KEY FK_4B365660A143AB7B
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE stock
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sweatshirt ADD stock JSON NOT NULL COMMENT '(DC2Type:json)'
        SQL);
    }
}
