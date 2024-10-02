<?php

declare(strict_types=1);

namespace App\ImportGitHubEvents;

final class ArchiveNotFoundException extends \Exception
{
    public function __construct(ArchiveId $archiveId, \Exception $exception)
    {
        parent::__construct(
            message: sprintf('Archive with id "%s" not found', $archiveId),
            previous: $exception,
        );
    }
}
