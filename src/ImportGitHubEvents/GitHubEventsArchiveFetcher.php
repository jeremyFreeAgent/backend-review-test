<?php

declare(strict_types=1);

namespace App\ImportGitHubEvents;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GitHubEventsArchiveFetcher implements GitHubEventsArchiveFetcherInterface
{
    private const GITHUB_ARCHIVE_URL = 'https://data.gharchive.org';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $varDir,
    ) {
    }

    public function fetch(ArchiveId $archiveId): void
    {
        $archiveUrl = $this->getArchiveUrl($archiveId);

        $this->logger->debug("[GitHubEventsArchiveFetcher] Fetching archive {$archiveId} from {$archiveUrl}");

        $response = $this->httpClient->request('GET', $archiveUrl);

        $archiveGzFilename = $this->getArchiveGzFilename($archiveId);

        $this->logger->debug("[GitHubEventsArchiveFetcher] Saving archive {$archiveId} to {$archiveGzFilename}");

        $archiveGzFileHandler = fopen($archiveGzFilename, 'w');

        try {
            foreach ($this->httpClient->stream($response) as $chunk) {
                fwrite($archiveGzFileHandler, $chunk->getContent());
            }
        } catch (ClientException $e) {
            if ($e->getCode() === 404) {
                $this->logger->error("[GitHubEventsArchiveFetcher] Archive {$archiveId} not found");
                throw new ArchiveNotFoundException($archiveId, $e);
            }

            $this->logger->error("[GitHubEventsArchiveFetcher] Error fetching archive {$archiveId}: {$e->getMessage()}");

            throw $e;
        } finally {
            fclose($archiveGzFileHandler);
        }

        $archiveFilename = $this->getArchiveFilename($archiveId);

        $this->logger->debug("[GitHubEventsArchiveFetcher] Extracting archive {$archiveId} to {$archiveFilename}");

        $bufferSize = 4096;

        $archiveGzFileHandler = gzopen($archiveGzFilename, 'rb');
        $archiveFileHandler = fopen($archiveFilename, 'wb');

        while (!gzeof($archiveGzFileHandler)) {
            fwrite($archiveFileHandler, gzread($archiveGzFileHandler, $bufferSize));
        }

        fclose($archiveFileHandler);
        gzclose($archiveGzFileHandler);
    }

    public function clean(ArchiveId $archiveId): void
    {
        $this->logger->debug("[GitHubEventsArchiveFetcher] Cleaning archive {$archiveId} temporary files");

        unlink($this->getArchiveFilename($archiveId));
        unlink($this->getArchiveGzFilename($archiveId));
    }

    private function getArchiveUrl(ArchiveId $archiveId): string
    {
        return sprintf(
            '%s/%s.json.gz',
            self::GITHUB_ARCHIVE_URL,
            $archiveId,
        );
    }

    private function getArchiveFilename(ArchiveId $archiveId): string
    {
        return sprintf(
            '%s/%s.json',
            $this->varDir,
            $archiveId,
        );
    }

    private function getArchiveGzFilename(ArchiveId $archiveId): string
    {
        return sprintf(
            '%s.gz',
            $this->getArchiveFilename($archiveId),
        );
    }
}
