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

require_once MAX_PATH . '/lib/RV/Legacy/OA/Dll/ZoneInfo.php';

if (!class_exists('OA_Dll_ZoneInfo', false)) {
    class_alias(\RV\Legacy\OA\Dll\ZoneInfo::class, 'OA_Dll_ZoneInfo');
}

if (false) {
    /** @deprecated Use {@see \RV\Legacy\OA\Dll\ZoneInfo} instead. Declaration exists only for the composer classmap. */
    class OA_Dll_ZoneInfo extends \RV\Legacy\OA\Dll\ZoneInfo
    {
    }
}
