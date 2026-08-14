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

require_once __DIR__ . '/../../../RV/Legacy/OA/DB/AdvisoryLock/mysqli.php';

if (!class_exists('OA_DB_AdvisoryLock_mysqli', false)) {
    class_alias(\RV\Legacy\OA\DB\AdvisoryLock\mysqli::class, 'OA_DB_AdvisoryLock_mysqli');
}

if (false) {
    class OA_DB_AdvisoryLock_mysqli extends \RV\Legacy\OA\DB\AdvisoryLock\mysqli
    {
    }
}
