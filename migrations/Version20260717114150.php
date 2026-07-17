<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260717114150 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create outbox_messages table for the transactional outbox pattern';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE outbox_messages (id UUID NOT NULL, payload JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, published_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, attempts INT DEFAULT 0 NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_outbox_messages_published_at ON outbox_messages (published_at)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE outbox_messages');
    }
}
