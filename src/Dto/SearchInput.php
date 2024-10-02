<?php

declare(strict_types=1);

namespace App\Dto;

final class SearchInput
{
    /**
     * @var \DateTimeImmutable
     */
    public $date;

    /**
     * @var string
     */
    public $keyword;
}
