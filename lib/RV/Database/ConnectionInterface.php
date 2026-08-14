<?php

/*
+---------------------------------------------------------------------------+
| Revive Adserver                                                           |
| http://www.revive-adserver.com                                            |
|                                                                           |
| Copyright: See the COPYRIGHT.txt file.                                    |
| License: GPLv2 or later, see the LICENSE.txt file.                        |
+---------------------------------------------------------------------------+
*/

namespace RV\Database;

/**
 * The database operations Revive Adserver actually needs from a connection.
 *
 * This is the seam that allows the PEAR::MDB2 dependency to be replaced
 * incrementally: call sites can be moved onto this interface one at a time,
 * while OA_DB keeps handing out an MDB2 backed implementation.
 *
 * Implementations throw {@see DatabaseException} instead of returning error
 * objects, so callers do not have to know about PEAR_Error.
 */
interface ConnectionInterface
{
    /**
     * Executes a statement which does not return a result set (INSERT, UPDATE,
     * DELETE, DDL, ...) and returns the number of affected rows.
     *
     * @throws DatabaseException
     */
    public function exec(string $query): int;

    /**
     * Returns every row of the result set as an associative array.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws DatabaseException
     */
    public function queryAll(string $query): array;

    /**
     * Returns the first row of the result set as an associative array, or null
     * when the result set is empty.
     *
     * @return array<string, mixed>|null
     *
     * @throws DatabaseException
     */
    public function queryRow(string $query): ?array;

    /**
     * Returns the first column of every row of the result set.
     *
     * @return array<int, mixed>
     *
     * @throws DatabaseException
     */
    public function queryCol(string $query): array;

    /**
     * Returns the first field of the first row of the result set, or null when
     * the result set is empty.
     *
     * @throws DatabaseException
     */
    public function queryOne(string $query): mixed;

    /**
     * Quotes a value so that it can be safely embedded in a query.
     *
     * @param string|null $type An implementation specific datatype hint, e.g. "text" or "integer"
     */
    public function quote(mixed $value, ?string $type = null): string;

    /**
     * Quotes a table or column name according to the current database syntax.
     */
    public function quoteIdentifier(string $identifier): string;

    public function beginTransaction(): void;

    /**
     * @throws DatabaseException
     */
    public function commit(): void;

    /**
     * @throws DatabaseException
     */
    public function rollback(): void;

    /**
     * The syntax (dialect) in use, e.g. "mysql" or "pgsql".
     */
    public function getSyntax(): string;
}
