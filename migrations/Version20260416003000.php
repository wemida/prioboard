<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260416003000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create board, settings, and single-user tables for PrioBoard';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE app_settings (id INTEGER NOT NULL, skin VARCHAR(20) NOT NULL, card_colors_enabled BOOLEAN NOT NULL, font_size VARCHAR(20) NOT NULL, refresh_interval INTEGER NOT NULL, api_key VARCHAR(64) DEFAULT NULL, board_version INTEGER NOT NULL, updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE card (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(100) NOT NULL, column_key VARCHAR(20) NOT NULL, position INTEGER NOT NULL, color VARCHAR(20) DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('CREATE TABLE user (id INTEGER NOT NULL, username VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_USERNAME ON user (username)');

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $apiKey = bin2hex(random_bytes(16));
        $passwordHash = '$2y$13$rDL.08BRJhU/NQE2.WHLyO323wOe7OwMQ5OHaXLY2yU4NGr4R/CM6';

        $this->addSql(
            sprintf(
                "INSERT INTO app_settings (id, skin, card_colors_enabled, font_size, refresh_interval, api_key, board_version, updated_at) VALUES (1, 'color', 1, 'medium', 30, '%s', 1, '%s')",
                $apiKey,
                $now
            )
        );
        $this->addSql(
            sprintf(
                "INSERT INTO user (id, username, password) VALUES (1, 'admin', '%s')",
                $passwordHash
            )
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE app_settings');
        $this->addSql('DROP TABLE card');
        $this->addSql('DROP TABLE user');
    }
}
