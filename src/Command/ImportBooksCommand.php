<?php

namespace App\Command;

use App\Entity\Book;
use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:import-books',
    description: 'Import books from Gutendex API',
)]
class ImportBooksCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private HttpClientInterface $httpClient,
        private CategoryRepository $categoryRepository
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $page = 1;
        $hasNext = true;

        $categoriesByName = [];

        while ($hasNext) {
            $response = $this->httpClient->request('GET', "https://gutendex.com/books/?languages=fr&page=$page");
            $data = $response->toArray();

            foreach ($data['results'] as $item) {
                // Vérifie si le livre existe déjà
                $existing = $this->em->getRepository(Book::class)->findOneBy(['title' => $item['title']]);
                if ($existing) {
                    continue;
                }

                $book = new Book();
                $title = substr($item['title'] ?? 'Titre inconnu', 0, 255);
                $book->setTitle($title);
                $book->setAuthor($item['authors'][0]['name'] ?? 'Inconnu');
                $descriptionArray = $item['summaries'] ?? [];
                $description = is_array($descriptionArray) && isset($descriptionArray[0]) ? $descriptionArray[0] : '';
                $book->setDescription($description);
                $book->setCoverUrl($item['formats']['image/jpeg'] ?? null);
                $book->setTextUrl($item['formats']['text/plain; charset=utf-8'] ?? null);
                $book->setSubjects($item['subjects']);
                $book->setBookshelves($item['bookshelves']);

                $categoryNames = [];
                foreach ($item['bookshelves'] ?? [] as $shelf) {
                    if (str_starts_with($shelf, 'Browsing: ')) {
                        $browsing = trim(substr($shelf, strlen('Browsing: ')));
                        $parts = explode('/', $browsing);
                        foreach ($parts as $part) {
                            $name = trim($part);
                            if ($name !== '') {
                                $categoryNames[] = $name;
                            }
                        }
                    }
                }

                if (empty($categoryNames)) {
                    $categoryNames[] = 'Autres';
                }

                foreach ($categoryNames as $categoryName) {
                    // Si déjà en mémoire, on réutilise
                    if (!isset($categoriesByName[$categoryName])) {
                        // On vérifie si ça existe en base
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

        $output->writeln('Import terminé.');
        return Command::SUCCESS;
    }
}
