<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\ImportGitHubEvents\ArchiveId;
use App\ImportGitHubEvents\GitHubEventsArchiveReader;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

final class GitHubEventsArchiveReaderTest extends TestCase
{
    public const VAR_DIR = __DIR__.'/../../var/tests/GitHubEventsArchiveReaderTest';

    public function setUp(): void
    {
        $fileSystem = new Filesystem();
        $fileSystem->remove(self::VAR_DIR);
        $fileSystem->mkdir(self::VAR_DIR);
    }

    public function testRead()
    {
        $archiveId = new ArchiveId('2015-01-01-15');

        $this->givenTheGitHubEventsArchiveHasBeenFetched($archiveId);

        $gitHubEventsArchiveReader = $this->givenThereIsAnGitHubEventsArchiveReader(
            $this->createMock(LoggerInterface::class),
            self::VAR_DIR,
        );

        $eventCount = 0;
        foreach ($this->whenTheGitHubEventsArchiveIsRead($archiveId, $gitHubEventsArchiveReader) as $line) {
            ++$eventCount;
        }

        $this->thenTheGitHubEventsArchiveFilesShouldBeRead($archiveId, 11351, $eventCount);
    }

    private function givenTheGitHubEventsArchiveHasBeenFetched(ArchiveId $archiveId): void
    {
        $fileSystem = new Filesystem();
        $fileSystem->copy(__DIR__.'/2015-01-01-15.json.gz', self::VAR_DIR.'/'.$archiveId.'.json.gz');
        $fileSystem->copy(__DIR__.'/2015-01-01-15.json', self::VAR_DIR.'/'.$archiveId.'.json');
    }

    private function givenThereIsAnGitHubEventsArchiveReader(
        LoggerInterface $logger,
        string $varDir,
    ): GitHubEventsArchiveReader {
        return new GitHubEventsArchiveReader(
            $logger,
            $varDir,
        );
    }

    private function whenTheGitHubEventsArchiveIsRead(
        ArchiveId $archiveId,
        GitHubEventsArchiveReader $gitHubEventsArchiveReader,
    ): \Generator {
        foreach ($gitHubEventsArchiveReader->read($archiveId) as $line) {
            yield $line;
        }
    }

    private function thenTheGitHubEventsArchiveFilesShouldBeRead(
        ArchiveId $archiveId,
        int $expectedEventCount,
        int $actualEventCount,
    ): void {
        $this->assertEquals($expectedEventCount, $actualEventCount);
    }
}
