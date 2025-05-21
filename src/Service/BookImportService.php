<?php

namespace App\Service;

use App\Entity\Book;
use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BookImportService
{
    public function __construct(
        private EntityManagerInterface $em,
        private HttpClientInterface $httpClient,
        private CategoryRepository $categoryRepository
    ) {}

    public function importBooks(): void
    {
        $page = 1;
        $hasNext = true;
        $categoriesByName = [];

        while ($hasNext) {
            $response = $this->httpClient->request('GET', "https://gutendex.com/books/?languages=fr&page=$page");
            $data = $response->toArray();

            foreach ($data['results'] as $item) {
                if ($this->em->getRepository(Book::class)->findOneBy(['title' => $item['title']])) {
                    continue;
                }

                $book = new Book();
                $title = substr($item['title'] ?? 'Titre inconnu', 0, 255);
                $book->setTitle($title);
                $book->setAuthor($item['authors'][0]['name'] ?? 'Inconnu');
                $description = $item['summaries'][0] ?? '';
                $book->setDescription($description);
                $book->setCoverUrl($item['formats']['image/jpeg'] ?? null);

                $textUrl = $item['formats']['text/plain; charset=utf-8'] ?? null;
                if (!$textUrl) continue;

                $book->setTextUrl($textUrl);
                $book->setSubjects($item['subjects']);
                $book->setBookshelves($item['bookshelves']);

                $categoryNames = [];

                foreach ($item['bookshelves'] ?? [] as $shelf) {
                    if (str_starts_with($shelf, 'Browsing: ')) {
                        $browsing = substr($shelf, strlen('Browsing: '));
                        foreach (explode('/', $browsing) as $part) {
                            $name = trim($part);
                            if ($name) $categoryNames[] = $name;
                        }
                    }
                }

                if (empty($categoryNames)) {
                    $categoryNames[] = 'Autres';
                }

                foreach ($categoryNames as $categoryName) {
                    if (!isset($categoriesByName[$categoryName])) {
                        $existingCategory = $this->categoryRepository->findOneBy(['name' => $categoryName]);
                        if (!$existingCategory) {
                            $existingCategory = new Category();
                            $existingCategory->setName($categoryName);
                            $this->em->persist($existingCategory);
                        }
                        $categoriesByName[$categoryName] = $existingCategory;
                    }

                    $book->addCategory($categoriesByName[$categoryName]);
                }

                $this->em->persist($book);
            }

            $this->em->flush();
            $hasNext = $data['next'] !== null;
            $page++;
        }
    }
}
