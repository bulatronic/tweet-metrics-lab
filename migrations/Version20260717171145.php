<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260717171145 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create feed_items read-model projection table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE feed_items (id UUID NOT NULL, tweet_id UUID NOT NULL, author_id UUID NOT NULL, author_username VARCHAR(30) NOT NULL, text TEXT NOT NULL, likes_count INT DEFAULT 0 NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_feed_items_created_at_id ON feed_items (created_at, id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_feed_items_tweet_id ON feed_items (tweet_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE feed_items');
    }
}
