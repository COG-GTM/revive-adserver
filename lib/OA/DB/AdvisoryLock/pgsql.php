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

require_once __DIR__ . '/../../../RV/Legacy/OA/DB/AdvisoryLock/pgsql.php';

if (!class_exists('OA_DB_AdvisoryLock_pgsql', false)) {
    class_alias(\RV\Legacy\OA\DB\AdvisoryLock\pgsql::class, 'OA_DB_AdvisoryLock_pgsql');
}

if (false) {
    class OA_DB_AdvisoryLock_pgsql extends \RV\Legacy\OA\DB\AdvisoryLock\pgsql
    {
    }
}
