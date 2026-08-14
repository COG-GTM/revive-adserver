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

namespace RV\Legacy\Max\Delivery;

if (!\function_exists('MAX_limitationsCheckAcl')) {
    require_once dirname(__DIR__, 4) . '/max/Delivery/limitations.php';
}

/**
 * Namespaced facade for the procedural delivery library
 * lib/max/Delivery/limitations.php. Each static method delegates to the
 * corresponding global function, which remains the canonical
 * implementation on the delivery fast path.
 */
class Limitations
{
    public static function MAX_limitationsCheckAcl($row, $source = '')
    {
        return \MAX_limitationsCheckAcl($row, $source);
    }

    public static function MAX_limitationsIsAdForbidden($aAd)
    {
        return \MAX_limitationsIsAdForbidden($aAd);
    }

    public static function MAX_limitationsIsZoneForbidden($zoneId, $aCapping)
    {
        return \MAX_limitationsIsZoneForbidden($zoneId, $aCapping);
    }

    public static function _limitationsIsAdCapped($adId, $cap, $sessionCap, $block, $showCappedNoCookie)
    {
        return \_limitationsIsAdCapped($adId, $cap, $sessionCap, $block, $showCappedNoCookie);
    }

    public static function _limitationsIsCampaignCapped($campaignId, $cap, $sessionCap, $block, $showCappedNoCookie)
    {
        return \_limitationsIsCampaignCapped($campaignId, $cap, $sessionCap, $block, $showCappedNoCookie);
    }

    public static function _limitationsIsZoneCapped($zoneId, $cap, $sessionCap, $block, $showCappedNoCookie)
    {
        return \_limitationsIsZoneCapped($zoneId, $cap, $sessionCap, $block, $showCappedNoCookie);
    }

    public static function _limitationsIsCapped($type, $id, $cap, $sessionCap, $block, $showCappedNoCookie)
    {
        return \_limitationsIsCapped($type, $id, $cap, $sessionCap, $block, $showCappedNoCookie);
    }

    public static function _areCookiesDisabled($filterActive = true)
    {
        return \_areCookiesDisabled($filterActive);
    }
}
