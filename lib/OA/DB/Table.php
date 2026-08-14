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

require_once __DIR__ . '/../../RV/Legacy/OA/DB/Table.php';

if (!class_exists('OA_DB_Table', false)) {
    class_alias(\RV\Legacy\OA\DB\Table::class, 'OA_DB_Table');
}

if (false) {
    class OA_DB_Table extends \RV\Legacy\OA\DB\Table
    {
    }
}
