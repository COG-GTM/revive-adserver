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

require_once MAX_PATH . '/lib/RV/Legacy/OA/Dll/Advertiser.php';

if (!class_exists('OA_Dll_Advertiser', false)) {
    class_alias(\RV\Legacy\OA\Dll\Advertiser::class, 'OA_Dll_Advertiser');
}

if (false) {
    /** @deprecated Use {@see \RV\Legacy\OA\Dll\Advertiser} instead. Declaration exists only for the composer classmap. */
    class OA_Dll_Advertiser extends \RV\Legacy\OA\Dll\Advertiser
    {
    }
}
