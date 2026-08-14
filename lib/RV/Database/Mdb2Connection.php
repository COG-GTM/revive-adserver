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

use MDB2_Driver_Common;
use PEAR;
use PEAR_Error;
use RV;

/**
 * A {@see ConnectionInterface} implementation delegating to the PEAR::MDB2
 * connection created by OA_DB. It owns no connection of its own, so wrapping an
 * existing handle has no effect on the legacy call sites still using it.
 */
class Mdb2Connection implements ConnectionInterface
{
    private MDB2_Driver_Common $oDbh;

    public function __construct(MDB2_Driver_Common $oDbh)
    {
        $this->oDbh = $oDbh;
    }

    /**
     * The underlying MDB2 handle, for call sites which have not been migrated
     * to this interface yet.
     */
    public function getMdb2Connection(): MDB2_Driver_Common
    {
        return $this->oDbh;
    }

    public function exec(string $query): int
    {
        return (int) $this->call('exec', [$query]);
    }

    public function queryAll(string $query): array
    {
        return (array) $this->call('queryAll', [$query, null, MDB2_FETCHMODE_ASSOC]);
    }

    public function queryRow(string $query): ?array
    {
        $aRow = $this->call('queryRow', [$query, null, MDB2_FETCHMODE_ASSOC]);

        return is_array($aRow) ? $aRow : null;
    }

    public function queryCol(string $query): array
    {
        return (array) $this->call('queryCol', [$query]);
    }

    public function queryOne(string $query): mixed
    {
        $value = $this->call('queryOne', [$query]);

        return null === $value || false === $value ? null : $value;
    }

    public function quote(mixed $value, ?string $type = null): string
    {
        return (string) $this->oDbh->quote($value, $type);
    }

    public function quoteIdentifier(string $identifier): string
    {
        return (string) $this->oDbh->quoteIdentifier($identifier);
    }

    public function beginTransaction(): void
    {
        $this->call('beginTransaction');
    }

    public function commit(): void
    {
        $this->call('commit');
    }

    public function rollback(): void
    {
        $this->call('rollback');
    }

    public function getSyntax(): string
    {
        return (string) $this->oDbh->dbsyntax;
    }

    /**
     * Calls an MDB2 method with the PEAR error handler disabled, turning the
     * returned PEAR_Error, if any, into a DatabaseException.
     *
     * @param array<int, mixed> $aArguments
     *
     * @throws DatabaseException
     */
    private function call(string $method, array $aArguments = []): mixed
    {
        RV::disableErrorHandling();

        try {
            $result = $this->oDbh->{$method}(...$aArguments);
        } finally {
            RV::enableErrorHandling();
        }

        if (PEAR::isError($result)) {
            /** @var PEAR_Error $result */
            throw new DatabaseException($result->getMessage(), (int) $result->getCode());
        }

        return $result;
    }
}
