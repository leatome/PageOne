<?php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Book>
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    public function searchBooksByTerm(string $term): array
    {
        $term = strtolower($term);
        $qb = $this->createQueryBuilder('b');

        // Recherche dans titre ou auteur
        $qb->where('LOWER(b.title) LIKE :term')
        ->orWhere('LOWER(b.author) LIKE :term')
        ->setParameter('term', '%' . $term . '%');

        $books = $qb->getQuery()->getResult();

        // Recherche dans subjects
        $allBooks = $this->findAll();
        foreach ($allBooks as $book) {
            foreach ($book->getSubjects() as $subject) {
                if (stripos($subject, $term) !== false) {
                    $books[] = $book;
                    break;
                }
            }
        }

        // Suppression des doublons
        $books = array_unique($books, SORT_REGULAR);

        return $books;
    }
}
