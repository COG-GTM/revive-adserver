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

require_once __DIR__ . '/../../RV/Legacy/OA/DB/Charset.php';

if (!class_exists('OA_DB_Charset', false)) {
    class_alias(\RV\Legacy\OA\DB\Charset::class, 'OA_DB_Charset');
}

if (false) {
    class OA_DB_Charset extends \RV\Legacy\OA\DB\Charset
    {
    }
}
