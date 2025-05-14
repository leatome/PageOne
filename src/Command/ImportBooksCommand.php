<?php

namespace App\Command;

use App\Entity\Book;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:import-books',
    description: 'Importe books from API Gutendex',
)]
class ImportBooksCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private HttpClientInterface $httpClient
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $page = 1;
        $hasNext = true;

        while ($hasNext) {
            $response = $this->httpClient->request('GET', "https://gutendex.com/books/?languages=fr&page=$page");
            $data = $response->toArray();

            foreach ($data['results'] as $item) {
                // Vérifie si le livre existe déjà via son ID Gutendex ou un titre unique pour éviter les doublons
                $existing = $this->em->getRepository(Book::class)->findOneBy(['title' => $item['title']]);
                if ($existing) {
                    continue;
                }

                $book = new Book();
                $book->setTitle($item['title']);
                $book->setAuthor($item['authors'][0]['name'] ?? 'Inconnu');
                $book->setDescription($item['summaries'][0] ?? null);
                $book->setCoverUrl($item['formats']['image/jpeg'] ?? null);
                $book->setHtmlUrl($item['formats']['text/html'] ?? null);
                $book->setEpubUrl($item['formats']['application/epub+zip'] ?? null);
                $book->setSubjects($item['subjects']);
                $book->setBookshelves($item['bookshelves']);
                $book->setDownloadCount($item['download_count']);

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
