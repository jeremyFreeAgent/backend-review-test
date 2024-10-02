<?php

declare(strict_types=1);

namespace App\ImportGitHubEvents;

final class ArchiveId implements \Stringable
{
    public function __construct(public readonly string $value)
    {
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
