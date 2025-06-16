<?php

namespace App\Repository;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 *
 * @method Comment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Comment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Comment[]    findAll()
 * @method Comment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /**
     * Find approved comments for an article
     */
    public function findApprovedForArticle(int $articleId): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.article = :article')
            ->andWhere('c.isApproved = :approved')
            ->andWhere('c.isDeleted = :deleted')
            ->setParameter('article', $articleId)
            ->setParameter('approved', true)
            ->setParameter('deleted', false)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find pending comments
     */
    public function findPending(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.isApproved = :approved')
            ->andWhere('c.isDeleted = :deleted')
            ->setParameter('approved', false)
            ->setParameter('deleted', false)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count pending comments
     */
    public function countPending(): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.isApproved = :approved')
            ->andWhere('c.isDeleted = :deleted')
            ->setParameter('approved', false)
            ->setParameter('deleted', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find recent comments
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.isApproved = :approved')
            ->andWhere('c.isDeleted = :deleted')
            ->setParameter('approved', true)
            ->setParameter('deleted', false)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find comments by user
     */
    public function findByUser(int $userId, bool $includeDeleted = false): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.author = :user')
            ->setParameter('user', $userId);

        if (!$includeDeleted) {
            $qb->andWhere('c.isDeleted = :deleted')
                ->setParameter('deleted', false);
        }

        return $qb->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
