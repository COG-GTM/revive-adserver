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

require_once MAX_PATH . '/lib/RV/Legacy/OA/Dll/Tracker.php';

if (!class_exists('OA_Dll_Tracker', false)) {
    class_alias(\RV\Legacy\OA\Dll\Tracker::class, 'OA_Dll_Tracker');
}

if (false) {
    /** @deprecated Use {@see \RV\Legacy\OA\Dll\Tracker} instead. Declaration exists only for the composer classmap. */
    class OA_Dll_Tracker extends \RV\Legacy\OA\Dll\Tracker
    {
    }
}
