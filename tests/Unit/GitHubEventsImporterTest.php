<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\Actor;
use App\Entity\Event;
use App\Entity\Repo;
use App\ImportGitHubEvents\ArchiveId;
use App\ImportGitHubEvents\GitHubEventsArchiveFetcherInterface;
use App\ImportGitHubEvents\GitHubEventsArchiveReader;
use App\ImportGitHubEvents\GitHubEventsArchiveReaderInterface;
use App\ImportGitHubEvents\GitHubEventsImporter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

final class GitHubEventsImporterTest extends TestCase
{
    public const VAR_DIR = __DIR__.'/../../var/tests/GitHubEventsImporterTest';

    public function setUp(): void
    {
        $fileSystem = new Filesystem();
        $fileSystem->remove(self::VAR_DIR);
        $fileSystem->mkdir(self::VAR_DIR);
    }

    public function testImport()
    {
        $archiveId = new ArchiveId('2015-01-01-15');

        $this->givenTheGitHubEventsArchiveHasBeenFetched($archiveId);

        $gitHubEventsArchiveReader = new GitHubEventsArchiveReader(
            $this->createMock(LoggerInterface::class),
            self::VAR_DIR,
        );

        $gitHubEventsImporter = $this->givenThereIsAnGitHubEventsImporter(
            $this->givenThereIsAnEntityManager(eventCount: 6362),
            $this->createMock(GitHubEventsArchiveFetcherInterface::class),
            $gitHubEventsArchiveReader,
            $this->createMock(LoggerInterface::class),
        );

        $this->whenTheGitHubEventsArchiveIsImported($archiveId, $gitHubEventsImporter);
    }

    private function givenTheGitHubEventsArchiveHasBeenFetched(ArchiveId $archiveId): void
    {
        $fileSystem = new Filesystem();
        $fileSystem->copy(__DIR__.'/2015-01-01-15.json.gz', self::VAR_DIR.'/'.$archiveId.'.json.gz');
        $fileSystem->copy(__DIR__.'/2015-01-01-15.json', self::VAR_DIR.'/'.$archiveId.'.json');
    }

    private function givenThereIsAnGitHubEventsImporter(
        EntityManagerInterface $entityManager,
        GitHubEventsArchiveFetcherInterface $archiveFetcher,
        GitHubEventsArchiveReaderInterface $archiveReader,
        LoggerInterface $logger,
    ): GitHubEventsImporter {
        return new GitHubEventsImporter(
            $entityManager,
            $archiveFetcher,
            $archiveReader,
            $logger,
        );
    }

    private function givenThereIsAnEntityManager(int $eventCount): EntityManagerInterface
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $eventRepository = $this->createMock(ObjectRepository::class);
        $eventRepository
            ->method('find')
            ->willReturn(null)
        ;
        $actorRepository = $this->createMock(ObjectRepository::class);
        $actorRepository
            ->method('find')
            ->willReturn($this->createMock(Actor::class))
        ;
        $repoRepository = $this->createMock(ObjectRepository::class);
        $repoRepository
            ->method('find')
            ->willReturn($this->createMock(Repo::class))
        ;

        $entityManager
            ->expects($this->exactly($eventCount))
            ->method('persist')
        ;

        $entityManager
            ->method('getRepository')
            ->will($this->returnValueMap([
                [Event::class, $eventRepository],
                [Actor::class, $actorRepository],
                [Repo::class, $repoRepository],
            ]))
        ;

        return $entityManager;
    }

    private function whenTheGitHubEventsArchiveIsImported(
        ArchiveId $archiveId,
        GitHubEventsImporter $gitHubEventsImporter,
    ): void {
        $gitHubEventsImporter->import($archiveId);
    }

    private function thenTheGitHubEventsArchiveFilesShouldBeImported(
        ArchiveId $archiveId,
        int $expectedEventCount,
        int $actualEventCount,
    ): void {
        $this->assertEquals($expectedEventCount, $actualEventCount);
    }
}
