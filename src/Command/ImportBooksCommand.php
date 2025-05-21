<?php

namespace App\Command;

use App\Service\BookImportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:import-books',
    description: 'Import books from Gutendex API',
)]
class ImportBooksCommand extends Command
{
    public function __construct(private BookImportService $importService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->importService->importBooks();
        $output->writeln('Import terminé.');
        return Command::SUCCESS;
    }
}
