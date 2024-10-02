<?php

declare(strict_types=1);

namespace App\ImportGitHubEvents;

interface GitHubEventsArchiveReaderInterface
{
    public function read(ArchiveId $archiveId): \Generator;
}
