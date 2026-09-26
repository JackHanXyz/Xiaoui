<?php

declare(strict_types=1);

namespace Xiaoui\Database;

/**
 * A minimal, chainable SQL query builder.
 */
class QueryBuilder
{
    private string $table = '';
    private array $select = ['*'];
    private array $wheres = [];
    private array $bindings = [];
    private array $orderBy = [];
    private ?int $limit = null;
    private ?int $offset = null;

    public function __construct(private readonly Connection $connection)
    {
    }

    public function table(string $table): self
    {
        $this->table = $table;

        return $this;
    }

    /**
     * @param list<string> $columns
     */
    public function select(array $columns = ['*']): self
    {
        $this->select = $columns;

        return $this;
    }

    /**
     * @param mixed $value
     */
    public function where(string $column, string $operator, mixed $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = "{$column} {$operator} ?";
        $this->bindings[] = $value;

        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->orderBy[] = "{$column} " . strtoupper($direction);

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function get(): array
    {
        return $this->connection->select($this->toSql(), $this->bindings);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function first(): ?array
    {
        $rows = $this->limit(1)->get();

        return $rows[0] ?? null;
    }

    /**
     * @return mixed
     */
    public function value(string $column): mixed
    {
        $row = $this->select([$column])->first();

        return $row[$column] ?? null;
    }

    public function count(): int
    {
        $previousSelect = $this->select;
        $previousLimit = $this->limit;
        $previousOffset = $this->offset;
        $this->select = ['COUNT(*) as aggregate'];
        $this->limit = null;
        $this->offset = null;

        try {
            return (int) ($this->first()['aggregate'] ?? 0);
        } finally {
            $this->select = $previousSelect;
            $this->limit = $previousLimit;
            $this->offset = $previousOffset;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insert(array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders),
        );

        $this->connection->execute($sql, array_values($data));

        return (int) $this->connection->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(array $data): int
    {
        $sets = [];
        $params = [];

        foreach ($data as $column => $value) {
            $sets[] = "{$column} = ?";
            $params[] = $value;
        }

        $sql = sprintf(
            'UPDATE %s SET %s%s',
            $this->table,
            implode(', ', $sets),
            $this->compileWheres(),
        );

        return $this->connection->execute($sql, [...$params, ...$this->bindings]);
    }

    public function delete(): int
    {
        $sql = sprintf('DELETE FROM %s%s', $this->table, $this->compileWheres());

        return $this->connection->execute($sql, $this->bindings);
    }

    public function toSql(): string
    {
        $sql = sprintf(
            'SELECT %s FROM %s%s%s%s%s',
            implode(', ', $this->select),
            $this->table,
            $this->compileWheres(),
            $this->orderBy ? ' ORDER BY ' . implode(', ', $this->orderBy) : '',
            $this->limit !== null ? " LIMIT {$this->limit}" : '',
            $this->offset !== null ? " OFFSET {$this->offset}" : '',
        );

        return $sql;
    }

    private function compileWheres(): string
    {
        return $this->wheres ? ' WHERE ' . implode(' AND ', $this->wheres) : '';
    }
}
