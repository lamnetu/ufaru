<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Сущность «Статья» (новость городского портала). Таблица: article.
 */
#[ORM\Entity(repositoryClass: ArticleRepository::class)]
#[ORM\Table(name: 'article')]
#[ORM\Index(name: 'idx_article_published_at', columns: ['published_at'])]
#[ORM\Index(name: 'idx_article_category', columns: ['category'])]
#[ORM\HasLifecycleCallbacks]
class Article
{
    public const TONES = ['green', 'river', 'sand', 'clay'];

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    /** Уникальный ЧПУ-идентификатор статьи: /novosti/park-amfiteatr */
    #[ORM\Column(type: Types::STRING, length: 120, unique: true)]
    private string $slug = '';

    /** Заголовок статьи */
    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $title = '';

    /** Лид — краткое описание, используется в карточках, сниппетах и поиске */
    #[ORM\Column(type: Types::TEXT)]
    private string $excerpt = '';

    /** Основной текст: массив абзацев (каждый элемент рендерится как <p>) */
    #[ORM\Column(type: Types::JSON)]
    private array $body = [];

    /** Рубрика: Город / Транспорт / Культура / ЖКХ / Спорт … */
    #[ORM\Column(type: Types::STRING, length: 60)]
    private string $category = '';

    /** Цветовой тон метки рубрики (см. self::TONES) */
    #[ORM\Column(type: Types::STRING, length: 20, options: ['default' => 'green'])]
    private string $tone = 'green';

    /** Вариант декоративной обложки */
    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 1])]
    private int $variant = 1;

    /** Дата публикации */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $publishedAt;

    /** Счётчик просмотров */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $views = 0;

    /** Прикреплена ли статья к главной ленте (featured) */
    #[ORM\Column(name: 'is_featured', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $featured = false;

    /** Теги статьи (многие-ко-многим через таблицу article_tag) */
    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'articles')]
    #[ORM\JoinTable(name: 'article_tag')]
    private Collection $tags;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->publishedAt = new \DateTimeImmutable();
        $this->tags = new ArrayCollection();
    }

    /* ---------------------------------------------------------------- ID */

    public function getId(): ?string
    {
        return $this->id;
    }

    /* -------------------------------------------------------------- Slug */

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

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

    /* ----------------------------------------------------------- Excerpt */

    public function getExcerpt(): string
    {
        return $this->excerpt;
    }

    public function setExcerpt(string $excerpt): static
    {
        $this->excerpt = $excerpt;

        return $this;
    }

    /* ------------------------------------------------------------------ Body (array of paragraphs) */

    /** @return string[] */
    public function getBody(): array
    {
        return $this->body;
    }

    /** @param string[] $body */
    public function setBody(array $body): static
    {
        $this->body = array_values(array_map(static fn ($p): string => (string) $p, $body));

        return $this;
    }

    public function addParagraph(string $paragraph): static
    {
        $this->body[] = $paragraph;

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

    /* ---------------------------------------------------------------- Tone */

    public function getTone(): string
    {
        return $this->tone;
    }

    public function setTone(string $tone): static
    {
        if (!\in_array($tone, self::TONES, true)) {
            throw new \InvalidArgumentException(sprintf('Недопустимый тон "%s". Допустимы: %s', $tone, implode(', ', self::TONES)));
        }

        $this->tone = $tone;

        return $this;
    }

    /* ------------------------------------------------------------- Variant */

    public function getVariant(): int
    {
        return $this->variant;
    }

    public function setVariant(int $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    /* -------------------------------------------------------- PublishedAt */

    public function getPublishedAt(): \DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(\DateTimeImmutable $publishedAt): static
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    /* --------------------------------------------------------------- Views */

    public function getViews(): int
    {
        return $this->views;
    }

    public function setViews(int $views): static
    {
        $this->views = max(0, $views);

        return $this;
    }

    public function incrementViews(): static
    {
        ++$this->views;

        return $this;
    }

    /* ------------------------------------------------------------ Featured */

    public function isFeatured(): bool
    {
        return $this->featured;
    }

    public function setFeatured(bool $featured): static
    {
        $this->featured = $featured;

        return $this;
    }

    /* ---------------------------------------------------------------- Tags */

    /** @return Collection<int, Tag> */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        $this->tags->removeElement($tag);

        return $this;
    }

    /* ------------------------------------------------------ Timestamps */

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
