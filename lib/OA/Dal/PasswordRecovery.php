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

require_once __DIR__ . '/../../RV/Legacy/OA/Dal/PasswordRecovery.php';

if (!class_exists('OA_Dal_PasswordRecovery', false)) {
    if (false) {
        class OA_Dal_PasswordRecovery extends \RV\Legacy\OA\Dal\PasswordRecovery
        {
        }
    }

    class_alias(\RV\Legacy\OA\Dal\PasswordRecovery::class, 'OA_Dal_PasswordRecovery');
}
