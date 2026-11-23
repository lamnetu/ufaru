<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Репозиторий сущности «Статья».
 *
 * @extends ServiceEntityRepository<Article>
 */
final class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    /** Все статьи, свежие сверху. */
    public function findAllPublished(int $limit = 50): array
    {
        return $this->publishedQb()
            ->orderBy('a.publishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /** Статья по ЧПУ-адресу (slug). */
    public function findBySlug(string $slug): ?Article
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** Статьи рубрики. */
    public function findByCategory(string $category, int $limit = 50): array
    {
        return $this->publishedQb()
            ->andWhere('a.category = :category')
            ->setParameter('category', $category)
            ->orderBy('a.publishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Поиск по заголовку и лиду + необязательный фильтр по рубрике.
     *
     * @return Article[]
     */
    public function search(string $query = '', string $category = 'all', int $limit = 50): array
    {
        $qb = $this->publishedQb()->orderBy('a.publishedAt', 'DESC')->setMaxResults($limit);

        if ('' !== trim($query)) {
            $qb->andWhere('LOWER(a.title) LIKE LOWER(:q) OR LOWER(a.excerpt) LIKE LOWER(:q)')
                ->setParameter('q', '%'.trim($query).'%');
        }

        if ('all' !== $category) {
            $qb->andWhere('a.category = :cat')->setParameter('cat', $category);
        }

        return $qb->getQuery()->getResult();
    }

    /** Последние N статей, кроме указанной (блок «Читайте также»). */
    public function findRelated(Article $exclude, int $limit = 3): array
    {
        return $this->publishedQb()
            ->andWhere('a.id != :id')
            ->setParameter('id', $exclude->getId())
            ->orderBy('a.publishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /** Список уникальных рубрик. */
    public function findCategories(): array
    {
        return array_column(
            $this->createQueryBuilder('a')
                ->select('DISTINCT a.category')
                ->orderBy('a.category', 'ASC')
                ->getQuery()
                ->getArrayResult(),
            'category'
        );
    }

    private function publishedQb(): QueryBuilder
    {
        return $this->createQueryBuilder('a');
    }
}
