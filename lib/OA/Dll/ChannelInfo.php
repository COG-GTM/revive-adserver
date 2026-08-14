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

require_once MAX_PATH . '/lib/RV/Legacy/OA/Dll/ChannelInfo.php';

if (!class_exists('OA_Dll_ChannelInfo', false)) {
    class_alias(\RV\Legacy\OA\Dll\ChannelInfo::class, 'OA_Dll_ChannelInfo');
}

if (false) {
    /** @deprecated Use {@see \RV\Legacy\OA\Dll\ChannelInfo} instead. Declaration exists only for the composer classmap. */
    class OA_Dll_ChannelInfo extends \RV\Legacy\OA\Dll\ChannelInfo
    {
    }
}
