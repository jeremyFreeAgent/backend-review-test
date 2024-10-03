<?php

declare(strict_types=1);

namespace App\ImportGitHubEvents;

use App\Entity\Actor;
use App\Entity\Event;
use App\Entity\EventType;
use App\Entity\Repo;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final class GitHubEventsImporter implements GitHubEventsImporterInterface
{
    private const BATCH_SIZE = 1000;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GitHubEventsArchiveFetcherInterface $archiveFetcher,
        private readonly GitHubEventsArchiveReaderInterface $archiveReader,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function import(ArchiveId $archiveId): void
    {
        $this->logger->info("[GitHubEventsImporter] Importing GitHub events from {$archiveId} archive");

        $this->archiveFetcher->fetch($archiveId);

        $events = [];

        $i = 0;
        foreach ($this->archiveReader->read($archiveId) as $line) {
            $eventData = json_decode($line, true);
            if (false === in_array($eventData['type'], EventType::getGitHubEventTypes())) {
                $this->logger->debug("Skipping event {$eventData['id']} with type {$eventData['type']}");

                continue;
            }

            $this->createEvent($eventData);

            ++$i;
            if (0 === $i % self::BATCH_SIZE) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }

        $this->entityManager->flush();

        $this->archiveFetcher->clean($archiveId);
    }

    private function createEvent(array $eventData): void
    {
        $event = $this->entityManager->getRepository(Event::class)->find($eventData['id']);

        if (null !== $event) {
            $this->logger->info("[GitHubEventsImporter] Event {$eventData['id']} already exists");

            return;
        }

        $repo = $this->getRepo($eventData);
        $actor = $this->getActor($eventData);

        $this->entityManager->persist(
            new Event(
                (int) $eventData['id'],
                EventType::fromGitHubEventType($eventData['type']),
                $actor,
                $repo,
                $eventData['payload'],
                new \DateTimeImmutable($eventData['created_at']),
                $eventData['comment'] ?? null,
            ),
        );
    }

    private function getActor(array $eventData): Actor
    {
        $actor = $this->entityManager->getRepository(Actor::class)->find($eventData['actor']['id']);

        if (null === $actor) {
            $this->logger->info("[GitHubEventsImporter] Creating actor {$eventData['actor']['id']}");

            $actor = Actor::fromArray($eventData['actor']);

            $this->entityManager->persist($actor);
        }

        return $actor;
    }

    private function getRepo(array $eventData): Repo
    {
        $repo = $this->entityManager->getRepository(Repo::class)->find($eventData['repo']['id']);
        if (null === $repo) {
            $this->logger->info("[GitHubEventsImporter] Creating repo {$eventData['repo']['id']}");

            $repo = Repo::fromArray($eventData['repo']);

            $this->entityManager->persist($repo);
        }

        return $repo;
    }
}
