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

require_once dirname(__DIR__, 4) . '/max/Delivery/adSelect.php';

/**
 * Namespaced facade for the procedural delivery library
 * lib/max/Delivery/adSelect.php. Each static method delegates to the
 * corresponding global function, which remains the canonical
 * implementation on the delivery fast path.
 */
class AdSelect
{
    public static function MAX_adSelect($what, $campaignid = '', $target = '', $source = '', $withtext = 0, $charset = '', $context = [], $richmedia = true, $ct0 = '', $loc = '', $referer = '')
    {
        return \MAX_adSelect($what, $campaignid, $target, $source, $withtext, $charset, $context, $richmedia, $ct0, $loc, $referer);
    }

    public static function _adSelectDirect($what, $campaignid = '', $context = [], $source = '', $richMedia = true, $lastpart = true)
    {
        return \_adSelectDirect($what, $campaignid, $context, $source, $richMedia, $lastpart);
    }

    public static function _getNextZone($zoneId, $arrZone)
    {
        return \_getNextZone($zoneId, $arrZone);
    }

    public static function _adSelectZone($zoneId, $context = [], $source = '', $richMedia = true)
    {
        return \_adSelectZone($zoneId, $context, $source, $richMedia);
    }

    public static function _adSelectCommon($aAds, $context, $source, $richMedia)
    {
        return \_adSelectCommon($aAds, $context, $source, $richMedia);
    }

    public static function _adSelectInnerLoop($adSelectFunction, $aAds, $context, $source, $richMedia, $companion = false)
    {
        return \_adSelectInnerLoop($adSelectFunction, $aAds, $context, $source, $richMedia, $companion);
    }

    public static function _adSelect(&$aLinkedAdInfos, $context, $source, $richMedia, $companion, $adArrayVar = 'ads', $cp = null)
    {
        return \_adSelect($aLinkedAdInfos, $context, $source, $richMedia, $companion, $adArrayVar, $cp);
    }

    public static function _controlTrafficEnabled(&$aAds)
    {
        return \_controlTrafficEnabled($aAds);
    }

    public static function _adSelectCheckCriteria($aAd, $aContext, $source, $richMedia)
    {
        return \_adSelectCheckCriteria($aAd, $aContext, $source, $richMedia);
    }

    public static function _adSelectBuildContextArray(&$aLinkedAds, $adArrayVar, $context, $companion = false)
    {
        return \_adSelectBuildContextArray($aLinkedAds, $adArrayVar, $context, $companion);
    }

    public static function _adSelectBuildContext($aBanner, $context = [])
    {
        return \_adSelectBuildContext($aBanner, $context);
    }

    public static function _adSelectDiscardNonMatchingAds(&$aAds, $aContext, $source, $richMedia)
    {
        return \_adSelectDiscardNonMatchingAds($aAds, $aContext, $source, $richMedia);
    }
}
