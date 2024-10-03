<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\ImportGitHubEvents\ArchiveId;
use App\ImportGitHubEvents\GitHubEventsArchiveFetcher;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class GitHubEventsArchiveFetcherTest extends TestCase
{
    public const VAR_DIR = __DIR__.'/../../var/tests/GitHubEventsArchiveFetcherTest';

    public function setUp(): void
    {
        $fileSystem = new Filesystem();
        $fileSystem->remove(self::VAR_DIR);
        $fileSystem->mkdir(self::VAR_DIR);
    }

    public function testFetch()
    {
        $archiveId = new ArchiveId('2015-01-01-15');

        $this->givenThereIsAnGitHubEventsArchiveOnGitHubArchive($archiveId);

        $response = new MockResponse(file_get_contents(self::VAR_DIR.'/gh_archive_'.$archiveId.'.json.gz'));

        $client = new MockHttpClient($response);

        $gitHubEventsArchiveFetcher = $this->givenThereIsAnGitHubEventsArchiveFetcher(
            $client,
            $this->createMock(LoggerInterface::class),
            self::VAR_DIR,
        );

        $this->whenTheGitHubEventsArchiveIsFetch($archiveId, $gitHubEventsArchiveFetcher);

        $this->thenTheGitHubEventsArchiveFilesShouldBeFetched($archiveId);
    }

    public function testClean()
    {
        $archiveId = new ArchiveId('2015-01-01-15');

        $this->givenTheGitHubEventsArchiveHasBeenFetched($archiveId);

        $gitHubEventsArchiveFetcher = $this->givenThereIsAnGitHubEventsArchiveFetcher(
            $client = new MockHttpClient(),
            $this->createMock(LoggerInterface::class),
            self::VAR_DIR,
        );

        $this->whenTheGitHubEventsArchiveIsClean($archiveId, $gitHubEventsArchiveFetcher);

        $this->thenTheGitHubEventsArchiveFilesShouldBeCleaned($archiveId);
    }

    private function givenThereIsAnGitHubEventsArchiveOnGitHubArchive(ArchiveId $archiveId): void
    {
        $fileSystem = new Filesystem();
        $fileSystem->copy(__DIR__.'/2015-01-01-15.json.gz', self::VAR_DIR.'/gh_archive_'.$archiveId.'.json.gz');
    }

    private function givenTheGitHubEventsArchiveHasBeenFetched(ArchiveId $archiveId): void
    {
        $fileSystem = new Filesystem();
        $fileSystem->copy(__DIR__.'/2015-01-01-15.json.gz', self::VAR_DIR.'/'.$archiveId.'.json.gz');
        $fileSystem->copy(__DIR__.'/2015-01-01-15.json', self::VAR_DIR.'/'.$archiveId.'.json');
    }

    private function givenThereIsAnGitHubEventsArchiveFetcher(
        MockHttpClient $httpClient,
        LoggerInterface $logger,
        string $varDir,
    ): GitHubEventsArchiveFetcher {
        return new GitHubEventsArchiveFetcher(
            $httpClient,
            $logger,
            $varDir,
        );
    }

    private function whenTheGitHubEventsArchiveIsFetch(
        ArchiveId $archiveId,
        GitHubEventsArchiveFetcher $gitHubEventsArchiveFetcher,
    ): void {
        $gitHubEventsArchiveFetcher->fetch($archiveId);
    }

    private function whenTheGitHubEventsArchiveIsClean(
        ArchiveId $archiveId,
        GitHubEventsArchiveFetcher $gitHubEventsArchiveFetcher,
    ): void {
        $gitHubEventsArchiveFetcher->clean($archiveId);
    }

    private function thenTheGitHubEventsArchiveFilesShouldBeFetched(ArchiveId $archiveId): void
    {
        $this->assertFileExists(self::VAR_DIR.'/'.$archiveId.'.json.gz');
        $this->assertFileExists(self::VAR_DIR.'/'.$archiveId.'.json');
    }

    private function thenTheGitHubEventsArchiveFilesShouldBeCleaned(ArchiveId $archiveId): void
    {
        $this->assertFileDoesNotExist(self::VAR_DIR.'/'.$archiveId.'.json.gz');
        $this->assertFileDoesNotExist(self::VAR_DIR.'/'.$archiveId.'.json');
    }
}
