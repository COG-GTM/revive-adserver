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

require_once __DIR__ . '/../../RV/Legacy/OA/Dal/ApplicationVariables.php';

if (!class_exists('OA_Dal_ApplicationVariables', false)) {
    if (false) {
        class OA_Dal_ApplicationVariables extends \RV\Legacy\OA\Dal\ApplicationVariables
        {
        }
    }

    class_alias(\RV\Legacy\OA\Dal\ApplicationVariables::class, 'OA_Dal_ApplicationVariables');
}
