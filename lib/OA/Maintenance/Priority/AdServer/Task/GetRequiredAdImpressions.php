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

// Back-compat shim: the class now lives at lib/RV/Legacy, this file keeps the
// legacy include path and class name working.

require_once __DIR__ . '/../../../../../RV/Legacy/OA/Maintenance/Priority/AdServer/Task/GetRequiredAdImpressions.php';

if (!class_exists('OA_Maintenance_Priority_AdServer_Task_GetRequiredAdImpressions', false)) {
    class_alias(\RV\Legacy\OA\Maintenance\Priority\AdServer\Task\GetRequiredAdImpressions::class, 'OA_Maintenance_Priority_AdServer_Task_GetRequiredAdImpressions');
}

if (false) {
    /** @deprecated use \RV\Legacy\OA\Maintenance\Priority\AdServer\Task\GetRequiredAdImpressions */
    class OA_Maintenance_Priority_AdServer_Task_GetRequiredAdImpressions extends \RV\Legacy\OA\Maintenance\Priority\AdServer\Task\GetRequiredAdImpressions
    {
    }
}
