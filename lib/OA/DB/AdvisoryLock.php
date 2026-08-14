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

require_once __DIR__ . '/../../RV/Legacy/OA/DB/AdvisoryLock.php';

if (!class_exists('OA_DB_AdvisoryLock', false)) {
    class_alias(\RV\Legacy\OA\DB\AdvisoryLock::class, 'OA_DB_AdvisoryLock');
}

if (false) {
    class OA_DB_AdvisoryLock extends \RV\Legacy\OA\DB\AdvisoryLock
    {
    }
}
