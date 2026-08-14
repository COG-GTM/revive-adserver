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

require_once __DIR__ . '/../../../RV/Legacy/OA/Dal/Statistics/Advertiser.php';

if (!class_exists('OA_Dal_Statistics_Advertiser', false)) {
    if (false) {
        class OA_Dal_Statistics_Advertiser extends \RV\Legacy\OA\Dal\Statistics\Advertiser
        {
        }
    }

    class_alias(\RV\Legacy\OA\Dal\Statistics\Advertiser::class, 'OA_Dal_Statistics_Advertiser');
}
