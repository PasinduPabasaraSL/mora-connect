<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

abstract class Model
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    protected function select(string $sql, array $bindings = []): array
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    protected function selectOne(string $sql, array $bindings = []): ?array
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($bindings);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    protected function execute(string $sql, array $bindings = []): int
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($bindings);

        return $statement->rowCount();
    }

    protected function lastInsertId(): int
    {
        return (int) $this->db->lastInsertId();
    }
}
