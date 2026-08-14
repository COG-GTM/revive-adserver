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

if (!class_exists('OA_Dal_Maintenance_Common', false)) {
    if (false) {
        class OA_Dal_Maintenance_Common extends \RV\Legacy\OA\Dal\Maintenance\Common
        {
        }
    }

    class_alias(\RV\Legacy\OA\Dal\Maintenance\Common::class, 'OA_Dal_Maintenance_Common');
}
