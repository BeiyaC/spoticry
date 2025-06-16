<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 *
 * @method Article|null find($id, $lockMode = null, $lockVersion = null)
 * @method Article|null findOneBy(array $criteria, array $orderBy = null)
 * @method Article[]    findAll()
 * @method Article[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    /**
     * Find published articles
     */
    public function findPublished(int $limit = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.isPublished = :published')
            ->setParameter('published', true)
            ->orderBy('a.publishedAt', 'DESC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find articles by tag
     */
    public function findByTag(string $tag): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.isPublished = :published')
            ->andWhere('JSON_CONTAINS(a.tags, :tag) = 1')
            ->setParameter('published', true)
            ->setParameter('tag', json_encode($tag))
            ->orderBy('a.publishedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find related articles (same tags or artist)
     */
    public function findRelated(Article $article, int $limit = 5): array
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.isPublished = :published')
            ->andWhere('a.id != :id')
            ->setParameter('published', true)
            ->setParameter('id', $article->getId());

        // Add condition for same artist
        $qb->andWhere('(a.artistName = :artist')
            ->setParameter('artist', $article->getArtistName());

        // Add condition for common tags
        if (!empty($article->getTags())) {
            $orConditions = [];
            foreach ($article->getTags() as $index => $tag) {
                $paramName = 'tag_' . $index;
                $orConditions[] = "JSON_CONTAINS(a.tags, :$paramName) = 1";
                $qb->setParameter($paramName, json_encode($tag));
            }
            if (!empty($orConditions)) {
                $qb->orWhere(implode(' OR ', $orConditions) . ')');
            } else {
                $qb->orWhere('1 = 0)'); // Close the parenthesis
            }
        } else {
            $qb->orWhere('1 = 0)'); // Close the parenthesis
        }

        return $qb->orderBy('a.publishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Search articles
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.isPublished = :published')
            ->andWhere('a.title LIKE :search OR a.content LIKE :search OR a.artistName LIKE :search OR a.excerpt LIKE :search')
            ->setParameter('published', true)
            ->setParameter('search', '%' . $query . '%')
            ->orderBy('a.publishedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find most viewed articles
     */
    public function findMostViewed(int $limit = 10): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.isPublished = :published')
            ->setParameter('published', true)
            ->orderBy('a.viewCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get all unique tags
     */
    public function findAllTags(): array
    {
        $articles = $this->createQueryBuilder('a')
            ->select('a.tags')
            ->where('a.isPublished = :published')
            ->setParameter('published', true)
            ->getQuery()
            ->getResult();

        $allTags = [];
        foreach ($articles as $article) {
            if (is_array($article['tags'])) {
                $allTags = array_merge($allTags, $article['tags']);
            }
        }

        return array_unique($allTags);
    }

    /**
     * Count articles by author
     */
    public function countByAuthor(int $authorId): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.author = :author')
            ->setParameter('author', $authorId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
