<?php

namespace App\Repository;

use App\Entity\Book;
use App\Entity\Rating;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Rating>
 */
class RatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rating::class);
    }

    public function getAverageForBook(Book $book): ?float
    {
        return $this->createQueryBuilder('r')
            ->select('AVG(r.rating)')
            ->where('r.book = :book')
            ->setParameter('book', $book)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
