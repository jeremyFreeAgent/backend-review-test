<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\EventInput;
use App\Dto\SearchInput;
use Doctrine\DBAL\Connection;
use phpDocumentor\Reflection\DocBlock\Tags\Author;

final class DbalWriteEventRepository implements WriteEventRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function update(EventInput $authorInput, int $id): void
    {
        $sql = <<<SQL
        UPDATE event
        SET comment = :comment
        WHERE id = :id
SQL;

        $this->connection->executeQuery($sql, ['id' => $id, 'comment' => $authorInput->comment]);
    }
}
