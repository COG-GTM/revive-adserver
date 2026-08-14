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

require_once __DIR__ . '/../../../RV/Legacy/OA/Dal/Statistics/Agency.php';

if (!class_exists('OA_Dal_Statistics_Agency', false)) {
    if (false) {
        class OA_Dal_Statistics_Agency extends \RV\Legacy\OA\Dal\Statistics\Agency
        {
        }
    }

    class_alias(\RV\Legacy\OA\Dal\Statistics\Agency::class, 'OA_Dal_Statistics_Agency');
}
