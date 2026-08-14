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

require_once MAX_PATH . '/lib/RV/Legacy/OA/Dll/CampaignInfo.php';

if (!class_exists('OA_Dll_CampaignInfo', false)) {
    class_alias(\RV\Legacy\OA\Dll\CampaignInfo::class, 'OA_Dll_CampaignInfo');
}

if (false) {
    /** @deprecated Use {@see \RV\Legacy\OA\Dll\CampaignInfo} instead. Declaration exists only for the composer classmap. */
    class OA_Dll_CampaignInfo extends \RV\Legacy\OA\Dll\CampaignInfo
    {
    }
}
