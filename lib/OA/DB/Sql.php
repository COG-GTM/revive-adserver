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

require_once __DIR__ . '/../../RV/Legacy/OA/DB/Sql.php';

if (!class_exists('OA_DB_Sql', false)) {
    class_alias(\RV\Legacy\OA\DB\Sql::class, 'OA_DB_Sql');
}

if (false) {
    class OA_DB_Sql extends \RV\Legacy\OA\DB\Sql
    {
    }
}
