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

require_once __DIR__ . '/../../../RV/Legacy/OA/Dal/Statistics/Publisher.php';

if (!class_exists('OA_Dal_Statistics_Publisher', false)) {
    if (false) {
        class OA_Dal_Statistics_Publisher extends \RV\Legacy\OA\Dal\Statistics\Publisher
        {
        }
    }

    class_alias(\RV\Legacy\OA\Dal\Statistics\Publisher::class, 'OA_Dal_Statistics_Publisher');
}
