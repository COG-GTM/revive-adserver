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

require_once MAX_PATH . '/lib/RV/Legacy/OA/Dll/PublisherInfo.php';

if (!class_exists('OA_Dll_PublisherInfo', false)) {
    class_alias(\RV\Legacy\OA\Dll\PublisherInfo::class, 'OA_Dll_PublisherInfo');
}

if (false) {
    /** @deprecated Use {@see \RV\Legacy\OA\Dll\PublisherInfo} instead. Declaration exists only for the composer classmap. */
    class OA_Dll_PublisherInfo extends \RV\Legacy\OA\Dll\PublisherInfo
    {
    }
}
