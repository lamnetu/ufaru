<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\NewsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Сущность «Новость» — короткая новость городской ленты (таблица: news).
 *
 * В отличие от «Статьи» (Article), новость — это компактный заметочный формат:
 * заголовок, лид/краткий текст, рубрика и опциональная связь со статьёй,
 * раскрывающей тему подробнее.
 */
#[ORM\Entity(repositoryClass: NewsRepository::class)]
#[ORM\Table(name: 'news')]
#[ORM\Index(name: 'idx_news_published_at', columns: ['published_at'])]
#[ORM\Index(name: 'idx_news_category', columns: ['category'])]
#[ORM\HasLifecycleCallbacks]
class News
{
    public const STATUSES = ['draft', 'published', 'archived'];

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    /** Короткий текстовый идентификатор новости: n-2026-0001 */
    #[ORM\Column(type: Types::STRING, length: 40, unique: true)]
    private string $code = '';

    /** Заголовок новости */
    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $title = '';

    /** Лид — краткое содержание (одно-два предложения) */
    #[ORM\Column(type: Types::TEXT)]
    private string $summary = '';

    /** Основной текст новости (необязательный, для развёрнутых заметок) */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $content = null;

    /** Рубрика: Город / Транспорт / Культура / ЖКХ / Спорт … */
    #[ORM\Column(type: Types::STRING, length: 60)]
    private string $category = '';

    /** Статус публикации (см. self::STATUSES) */
    #[ORM\Column(type: Types::STRING, length: 20, options: ['default' => 'draft'])]
    private string $status = 'draft';

    /** Дата и время публикации в ленту */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $publishedAt;

    /** Прикреплена ли новость к началу ленты */
    #[ORM\Column(name: 'is_pinned', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $pinned = false;

    /** Ссылка на подробную статью (nullable — не у каждой новости есть статья) */
    #[ORM\ManyToOne(targetEntity: Article::class)]
    #[ORM\JoinColumn(name: 'article_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Article $article = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->publishedAt = new \DateTimeImmutable();
    }

    /* ---------------------------------------------------------------- ID */

    public function getId(): ?string
    {
        return $this->id;
    }

    /* -------------------------------------------------------------- Code */

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    /* ------------------------------------------------------------- Title */

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /* ----------------------------------------------------------- Summary */

    public function getSummary(): string
    {
        return $this->summary;
    }

    public function setSummary(string $summary): static
    {
        $this->summary = $summary;

        return $this;
    }

    /* ------------------------------------------------------------ Content */

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = $content;

        return $this;
    }

    /* ---------------------------------------------------------- Category */

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;

        return $this;
    }

    /* ------------------------------------------------------------ Status */

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        if (!\in_array($status, self::STATUSES, true)) {
            throw new \InvalidArgumentException(
                sprintf('Недопустимый статус новости "%s". Допустимые: %s', $status, implode(', ', self::STATUSES))
            );
        }

        $this->status = $status;

        return $this;
    }

    public function isPublished(): bool
    {
        return 'published' === $this->status;
    }

    /* ------------------------------------------------------- PublishedAt */

    public function getPublishedAt(): \DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(\DateTimeImmutable $publishedAt): static
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    /* ------------------------------------------------------------ Pinned */

    public function isPinned(): bool
    {
        return $this->pinned;
    }

    public function setPinned(bool $pinned): static
    {
        $this->pinned = $pinned;

        return $this;
    }

    /* ------------------------------------------------------------ Article */

    public function getArticle(): ?Article
    {
        return $this->article;
    }

    public function setArticle(?Article $article): static
    {
        $this->article = $article;

        return $this;
    }

    /* -------------------------------------------------------- Timestamps */

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
