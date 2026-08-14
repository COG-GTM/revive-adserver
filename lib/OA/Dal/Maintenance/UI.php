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

require_once __DIR__ . '/../../../RV/Legacy/OA/Dal/Maintenance/UI.php';

if (!class_exists('OA_Dal_Maintenance_UI', false)) {
    if (false) {
        class OA_Dal_Maintenance_UI extends \RV\Legacy\OA\Dal\Maintenance\UI
        {
        }
    }

    class_alias(\RV\Legacy\OA\Dal\Maintenance\UI::class, 'OA_Dal_Maintenance_UI');
}
