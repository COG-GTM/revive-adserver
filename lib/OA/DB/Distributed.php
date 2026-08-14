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

require_once __DIR__ . '/../../RV/Legacy/OA/DB/Distributed.php';

if (!class_exists('OA_DB_Distributed', false)) {
    class_alias(\RV\Legacy\OA\DB\Distributed::class, 'OA_DB_Distributed');
}

if (false) {
    class OA_DB_Distributed extends \RV\Legacy\OA\DB\Distributed
    {
    }
}
