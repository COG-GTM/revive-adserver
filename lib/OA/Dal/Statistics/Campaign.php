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

require_once __DIR__ . '/../../../RV/Legacy/OA/Dal/Statistics/Campaign.php';

if (!class_exists('OA_Dal_Statistics_Campaign', false)) {
    if (false) {
        class OA_Dal_Statistics_Campaign extends \RV\Legacy\OA\Dal\Statistics\Campaign
        {
        }
    }

    class_alias(\RV\Legacy\OA\Dal\Statistics\Campaign::class, 'OA_Dal_Statistics_Campaign');
}
