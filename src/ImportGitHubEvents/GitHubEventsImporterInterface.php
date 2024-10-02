<?php

declare(strict_types=1);

namespace App\ImportGitHubEvents;

interface GitHubEventsImporterInterface
{
    public function import(ArchiveId $archiveId): void;
}
