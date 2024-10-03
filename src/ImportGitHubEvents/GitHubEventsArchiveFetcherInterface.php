<?php

declare(strict_types=1);

namespace App\ImportGitHubEvents;

interface GitHubEventsArchiveFetcherInterface
{
    public function fetch(ArchiveId $archiveId): void;

    public function clean(ArchiveId $archiveId): void;
}
