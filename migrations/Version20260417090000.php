<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260417090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add delete confirmation preference to app settings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_settings ADD delete_confirmation_enabled BOOLEAN NOT NULL DEFAULT 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TEMPORARY TABLE __temp__app_settings AS SELECT id, skin, font_size, refresh_interval, api_key, board_version, updated_at FROM app_settings');
        $this->addSql('DROP TABLE app_settings');
        $this->addSql('CREATE TABLE app_settings (id INTEGER NOT NULL, skin VARCHAR(20) NOT NULL, font_size VARCHAR(20) NOT NULL, refresh_interval INTEGER NOT NULL, api_key VARCHAR(64) DEFAULT NULL, board_version INTEGER NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id))');
        $this->addSql('INSERT INTO app_settings (id, skin, font_size, refresh_interval, api_key, board_version, updated_at) SELECT id, skin, font_size, refresh_interval, api_key, board_version, updated_at FROM __temp__app_settings');
        $this->addSql('DROP TABLE __temp__app_settings');
    }
}
