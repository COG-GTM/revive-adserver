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

require_once __DIR__ . '/../../../RV/Legacy/OA/DB/Table/Statistics.php';

if (!class_exists('OA_DB_Table_Statistics', false)) {
    class_alias(\RV\Legacy\OA\DB\Table\Statistics::class, 'OA_DB_Table_Statistics');
}

if (false) {
    class OA_DB_Table_Statistics extends \RV\Legacy\OA\DB\Table\Statistics
    {
    }
}
