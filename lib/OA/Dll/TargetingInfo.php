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

require_once MAX_PATH . '/lib/RV/Legacy/OA/Dll/TargetingInfo.php';

if (!class_exists('OA_Dll_TargetingInfo', false)) {
    class_alias(\RV\Legacy\OA\Dll\TargetingInfo::class, 'OA_Dll_TargetingInfo');
}

if (false) {
    /** @deprecated Use {@see \RV\Legacy\OA\Dll\TargetingInfo} instead. Declaration exists only for the composer classmap. */
    class OA_Dll_TargetingInfo extends \RV\Legacy\OA\Dll\TargetingInfo
    {
    }
}
