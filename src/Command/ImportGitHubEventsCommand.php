<?php

declare(strict_types=1);

namespace App\Command;

use App\ImportGitHubEvents\ArchiveId;
use App\ImportGitHubEvents\GitHubEventsImporterInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

#[AsCommand(
    name: 'app:import-github-events',
    description: 'Import GitHub events',
)]
final class ImportGitHubEventsCommand extends Command
{
    public function __construct(
        private readonly GitHubEventsImporterInterface $gitHubEventsImporter,
        private readonly Stopwatch $stopwatch,
    ) {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this
            ->addArgument(
                'archive',
                InputArgument::REQUIRED,
                'The archive to import events from (format is Y-m-d-h example: 2015-01-01-15).',
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $archiveId = new ArchiveId($input->getArgument('archive'));

        $io->text('Importing GitHub events from ' . $archiveId . ' archive....');

        $this->stopwatch->start('import_github_events');
        $this->gitHubEventsImporter->import($archiveId);
        $this->stopwatch->stop('import_github_events');

        $io->text((string) $this->stopwatch->getEvent('import_github_events'));
        $io->success('GitHub events imported successfully.');

        return Command::SUCCESS;
    }
}
