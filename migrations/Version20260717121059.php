<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260717121059 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add max_attempts and failed_at to outbox_messages for dead-letter handling';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE outbox_messages ADD max_attempts INT DEFAULT 10 NOT NULL');
        $this->addSql('ALTER TABLE outbox_messages ADD failed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE outbox_messages DROP max_attempts');
        $this->addSql('ALTER TABLE outbox_messages DROP failed_at');
    }
}
