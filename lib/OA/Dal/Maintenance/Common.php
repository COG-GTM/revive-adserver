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

require_once __DIR__ . '/../../../RV/Legacy/OA/Dal/Maintenance/Common.php';

// Back-compat shim: OA_Dal_Maintenance_Common now lives in \RV\Legacy\OA\Dal\Maintenance\Common.
// The dead declaration below is never executed; it exists so that Composer's
// classmap generator indexes the legacy name against this file.
if (false) {
    class OA_Dal_Maintenance_Common extends \RV\Legacy\OA\Dal\Maintenance\Common {}
}

if (!class_exists('OA_Dal_Maintenance_Common', false)) {
    class_alias(\RV\Legacy\OA\Dal\Maintenance\Common::class, 'OA_Dal_Maintenance_Common');
}
