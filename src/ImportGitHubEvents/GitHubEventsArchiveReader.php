<?php

declare(strict_types=1);

namespace App\ImportGitHubEvents;

use Psr\Log\LoggerInterface;

final class GitHubEventsArchiveReader implements GitHubEventsArchiveReaderInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $varDir,
    ) {
    }

    public function read(ArchiveId $archiveId): \Generator
    {
        $archiveFilename = $this->getArchiveFilename($archiveId);

        $this->logger->info("[GitHubEventsArchiveReader] Reading GitHub events from {$archiveId} archive from {$archiveFilename} file");

        $archiveFileHandler = fopen($archiveFilename, 'r');

        $i = 0;
        if ($archiveFileHandler) {
            while (($line = fgets($archiveFileHandler)) !== false) {
                yield $line;
            }
        }

        fclose($archiveFileHandler);
    }

    private function getArchiveFilename(ArchiveId $archiveId): string
    {
        return sprintf(
            '%s/%s.json',
            $this->varDir,
            $archiveId,
        );
    }
}
