<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Миграция: сущность «Новость» (таблица news) со связью ManyToOne на article.
 */
final class Version20261123140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Создаёт таблицу news (модель данных сущности "Новость")';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE news (
            id BIGINT AUTO_INCREMENT NOT NULL,
            article_id BIGINT DEFAULT NULL,
            code VARCHAR(40) NOT NULL,
            title VARCHAR(255) NOT NULL,
            summary LONGTEXT NOT NULL,
            content LONGTEXT DEFAULT NULL,
            category VARCHAR(60) NOT NULL,
            status VARCHAR(20) DEFAULT \'draft\' NOT NULL,
            published_at DATETIME NOT NULL,
            is_pinned TINYINT(1) DEFAULT 0 NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE INDEX UNIQ_news_code (code),
            INDEX IDX_news_article (article_id),
            INDEX idx_news_published_at (published_at),
            INDEX idx_news_category (category),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Убран лишний sprintf, так как в строке нет плейсхолдеров для форматирования
        $this->addSql('ALTER TABLE news ADD CONSTRAINT FK_news_article FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE news DROP FOREIGN KEY FK_news_article');
        $this->addSql('DROP TABLE news');
    }
}
