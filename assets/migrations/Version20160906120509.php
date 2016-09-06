<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Migration for ff_audit_log
 */
class Version20160906120509 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('CREATE TABLE ff_audit_log (
            id int(10) unsigned NOT NULL AUTO_INCREMENT,
            timestamp int(10) unsigned NOT NULL,
            type int(10) unsigned NOT NULL DEFAULT 0,
            user int(10) unsigned NOT NULL DEFAULT 0,
            data tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
            PRIMARY KEY (id)
            ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('DROP TABLE ff_audit_log');
    }
}
