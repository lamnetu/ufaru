<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\News;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Репозиторий сущности «Новость».
 *
 * @extends ServiceEntityRepository<News>
 */
class NewsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, News::class);
    }

    /**
     * Опубликованные новости для ленты: закреплённые — первыми,
     * далее по дате публикации (новые сверху).
     *
     * @return News[]
     */
    public function findPublished(int $limit = 50): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.status = :status')
            ->orderBy('n.pinned', 'DESC')
            ->addOrderBy('n.publishedAt', 'DESC')
            ->setParameter('status', 'published')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Новости по рубрике (только опубликованные).
     *
     * @return News[]
     */
    public function findByCategory(string $category, int $limit = 50): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.status = :status')
            ->andWhere('n.category = :category')
            ->orderBy('n.publishedAt', 'DESC')
            ->setParameters(['status' => 'published', 'category' => $category])
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Поиск по заголовку и лиду с необязательным фильтром по рубрике.
     *
     * @return News[]
     */
    public function search(string $query = '', string $category = 'all', int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('n')
            ->andWhere('n.status = :status')
            ->setParameter('status', 'published')
            ->orderBy('n.publishedAt', 'DESC')
            ->setMaxResults($limit);

        if ('' !== trim($query)) {
            $qb->andWhere('(n.title LIKE :q OR n.summary LIKE :q)')
                ->setParameter('q', '%'.$query.'%');
        }

        if ('all' !== $category && '' !== $category) {
            $qb->andWhere('n.category = :category')
                ->setParameter('category', $category);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Список уникальных рубрик опубликованных новостей.
     *
     * @return string[]
     */
    public function findCategories(): array
    {
        $rows = $this->createQueryBuilder('n')
            ->select('DISTINCT n.category')
            ->andWhere('n.status = :status')
            ->orderBy('n.category', 'ASC')
            ->setParameter('status', 'published')
            ->getQuery()
            ->getScalarResult();

        return array_map(static fn (array $row): string => (string) $row['category'], $rows);
    }

    public function findOneByCode(string $code): ?News
    {
        return $this->findOneBy(['code' => $code]);
    }
}
