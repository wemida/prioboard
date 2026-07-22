<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260722220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the board language preference';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE app_settings ADD language VARCHAR(5) DEFAULT 'en' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_settings DROP COLUMN language');
    }
}
