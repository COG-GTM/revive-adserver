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

require_once MAX_PATH . '/lib/RV/Legacy/OA/Dll/TrackerInfo.php';

if (!class_exists('OA_Dll_TrackerInfo', false)) {
    class_alias(\RV\Legacy\OA\Dll\TrackerInfo::class, 'OA_Dll_TrackerInfo');
}

if (false) {
    /** @deprecated Use {@see \RV\Legacy\OA\Dll\TrackerInfo} instead. Declaration exists only for the composer classmap. */
    class OA_Dll_TrackerInfo extends \RV\Legacy\OA\Dll\TrackerInfo
    {
    }
}
