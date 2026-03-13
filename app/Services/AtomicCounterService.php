<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * AtomicCounterService - Thread-safe counter operations
 *
 * Provides atomic increment/decrement operations that prevent lost updates
 * under concurrent load. Critical for financial counters (billing, credits, etc.)
 *
 * Usage:
 * app(AtomicCounterService::class)->increment(
 *     table: 'client_asset_counters',
 *     where: ['client_id' => $clientId, 'asset_type' => 'chromebook'],
 *     column: 'count',
 *     amount: 1
 * );
 */
class AtomicCounterService
{
    /**
     * Atomically increment a counter
     *
     * @param  string  $table  Database table name
     * @param  array<string, mixed>  $where  Where conditions (e.g., ['client_id' => 1])
     * @param  string  $column  Counter column name
     * @param  int  $amount  Amount to increment by (default: 1)
     * @return int New counter value after increment
     */
    public function increment(string $table, array $where, string $column, int $amount = 1): int
    {
        if ($amount < 0) {
            throw new InvalidArgumentException('Use decrement() for negative amounts');
        }

        return $this->atomicUpdate($table, $where, $column, $amount);
    }

    /**
     * Atomically decrement a counter
     *
     * @param  string  $table  Database table name
     * @param  array<string, mixed>  $where  Where conditions
     * @param  string  $column  Counter column name
     * @param  int  $amount  Amount to decrement by (default: 1)
     * @return int New counter value after decrement
     */
    public function decrement(string $table, array $where, string $column, int $amount = 1): int
    {
        if ($amount < 0) {
            throw new InvalidArgumentException('Use increment() for negative amounts');
        }

        return $this->atomicUpdate($table, $where, $column, -$amount);
    }

    /**
     * Get current counter value
     *
     * @param  string  $table  Database table name
     * @param  array<string, mixed>  $where  Where conditions
     * @param  string  $column  Counter column name
     * @return int Current counter value
     */
    public function get(string $table, array $where, string $column): int
    {
        $query = DB::table($table);

        foreach ($where as $key => $value) {
            $query->where($key, $value);
        }

        /** @var string|int|float|null $result */
        $result = $query->value($column);

        return is_numeric($result) ? (int) $result : 0;
    }

    /**
     * Set counter to specific value
     *
     * @param  string  $table  Database table name
     * @param  array<string, mixed>  $where  Where conditions
     * @param  string  $column  Counter column name
     * @param  int  $value  New counter value
     */
    public function set(string $table, array $where, string $column, int $value): void
    {
        $query = DB::table($table);

        foreach ($where as $key => $val) {
            $query->where($key, $val);
        }

        $query->update([$column => $value]);
    }

    /**
     * Perform atomic counter update
     * Uses raw SQL to ensure atomicity at database level
     *
     * @param  string  $table  Database table name
     * @param  array<string, mixed>  $where  Where conditions
     * @param  string  $column  Counter column name
     * @param  int  $delta  Amount to change (+/-)
     * @return int New counter value
     */
    protected function atomicUpdate(string $table, array $where, string $column, int $delta): int
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            return $this->atomicUpdatePostgres($table, $where, $column, $delta);
        } else {
            return $this->atomicUpdateMysql($table, $where, $column, $delta);
        }
    }

    /**
     * PostgreSQL atomic update with RETURNING clause
     *
     * @param  array<string, mixed>  $where
     */
    protected function atomicUpdatePostgres(string $table, array $where, string $column, int $delta): int
    {
        $whereClause = $this->buildWhereClause($where);
        $whereValues = array_values($where);

        $sql = "UPDATE {$table} 
                SET {$column} = {$column} + ? 
                WHERE {$whereClause} 
                RETURNING {$column}";

        $result = DB::selectOne($sql, array_merge([$delta], $whereValues));

        return $result->{$column} ?? 0;
    }

    /**
     * MySQL atomic update with separate SELECT
     *
     * @param  array<string, mixed>  $where
     */
    protected function atomicUpdateMysql(string $table, array $where, string $column, int $delta): int
    {
        $whereClause = $this->buildWhereClause($where);
        $whereValues = array_values($where);

        // Perform atomic update
        $sql = "UPDATE {$table} 
                SET {$column} = {$column} + ? 
                WHERE {$whereClause}";

        DB::update($sql, array_merge([$delta], $whereValues));

        // Fetch new value
        return $this->get($table, $where, $column);
    }

    /**
     * Build WHERE clause from conditions array
     *
     * @param  array<string, mixed>  $where
     */
    protected function buildWhereClause(array $where): string
    {
        $conditions = [];

        foreach (array_keys($where) as $key) {
            $conditions[] = "{$key} = ?";
        }

        return implode(' AND ', $conditions);
    }
}
