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

require_once __DIR__ . '/../../../RV/Legacy/OA/DB/Table/Priority.php';

if (!class_exists('OA_DB_Table_Priority', false)) {
    class_alias(\RV\Legacy\OA\DB\Table\Priority::class, 'OA_DB_Table_Priority');
}

if (false) {
    class OA_DB_Table_Priority extends \RV\Legacy\OA\DB\Table\Priority
    {
    }
}
