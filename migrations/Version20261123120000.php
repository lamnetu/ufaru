<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Миграция: модель данных сущности «Статья» (table article),
 * вспомогательная сущность «Тег» (table tag) и связующая таблица article_tag.
 */
final class Version20261123120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Создаёт таблицы article, tag и article_tag (модель данных сущности "Статья")';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE article (
            id BIGINT AUTO_INCREMENT NOT NULL,
            slug VARCHAR(120) NOT NULL,
            title VARCHAR(255) NOT NULL,
            excerpt LONGTEXT NOT NULL,
            body JSON NOT NULL,
            category VARCHAR(60) NOT NULL,
            tone VARCHAR(20) DEFAULT 'green' NOT NULL,
            variant SMALLINT DEFAULT 1 NOT NULL,
            published_at DATETIME NOT NULL,
            views INT DEFAULT 0 NOT NULL,
            featured TINYINT(1) DEFAULT 0 NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE INDEX UNIQ_article_slug (slug),
            INDEX idx_article_published_at (published_at),
            INDEX idx_article_category (category),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        $this->addSql('CREATE TABLE tag (
            id BIGINT AUTO_INCREMENT NOT NULL,
            name VARCHAR(60) NOT NULL,
            UNIQUE INDEX UNIQ_tag_name (name),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE article_tag (
            article_id BIGINT NOT NULL,
            tag_id BIGINT NOT NULL,
            INDEX IDX_article_tag_article (article_id),
            INDEX IDX_article_tag_tag (tag_id),
            PRIMARY KEY(article_id, tag_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE article_tag
            ADD CONSTRAINT FK_article_tag_article FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE,
            ADD CONSTRAINT FK_article_tag_tag FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article_tag DROP FOREIGN KEY FK_article_tag_article');
        $this->addSql('ALTER TABLE article_tag DROP FOREIGN KEY FK_article_tag_tag');
        $this->addSql('DROP TABLE article_tag');
        $this->addSql('DROP TABLE tag');
        $this->addSql('DROP TABLE article');
    }
}
