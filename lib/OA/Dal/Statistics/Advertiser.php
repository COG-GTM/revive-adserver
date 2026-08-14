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

// Back-compat shim: OA_Dal_Statistics_Advertiser now lives in \RV\Legacy\OA\Dal\Statistics\Advertiser.
// The dead declaration below is never executed; it exists so that Composer's
// classmap generator indexes the legacy name against this file.
if (false) {
    class OA_Dal_Statistics_Advertiser extends \RV\Legacy\OA\Dal\Statistics\Advertiser {}
}

if (!class_exists('OA_Dal_Statistics_Advertiser', false)) {
    class_alias(\RV\Legacy\OA\Dal\Statistics\Advertiser::class, 'OA_Dal_Statistics_Advertiser');
}
