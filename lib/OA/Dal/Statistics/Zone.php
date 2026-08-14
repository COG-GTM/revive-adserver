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

require_once __DIR__ . '/../../../RV/Legacy/OA/Dal/Statistics/Zone.php';

// Back-compat shim: OA_Dal_Statistics_Zone now lives in \RV\Legacy\OA\Dal\Statistics\Zone.
// The dead declaration below is never executed; it exists so that Composer's
// classmap generator indexes the legacy name against this file.
if (false) {
    class OA_Dal_Statistics_Zone extends \RV\Legacy\OA\Dal\Statistics\Zone {}
}

if (!class_exists('OA_Dal_Statistics_Zone', false)) {
    class_alias(\RV\Legacy\OA\Dal\Statistics\Zone::class, 'OA_Dal_Statistics_Zone');
}
