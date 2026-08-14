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

if (!\function_exists('MAX_Delivery_log_logAdRequest')) {
    require_once dirname(__DIR__, 4) . '/max/Delivery/log.php';
}

/**
 * Namespaced facade for the procedural delivery library
 * lib/max/Delivery/log.php. Each static method delegates to the
 * corresponding global function, which remains the canonical
 * implementation on the delivery fast path.
 */
class Log
{
    public static function MAX_Delivery_log_logAdRequest($adId, $zoneId, $aAd = [])
    {
        return \MAX_Delivery_log_logAdRequest($adId, $zoneId, $aAd);
    }

    public static function MAX_Delivery_log_logAdImpression($adId, $zoneId)
    {
        return \MAX_Delivery_log_logAdImpression($adId, $zoneId);
    }

    public static function MAX_Delivery_log_logAdClick($adId, $zoneId)
    {
        return \MAX_Delivery_log_logAdClick($adId, $zoneId);
    }

    public static function MAX_Delivery_log_logConversion($trackerId, $aConversion)
    {
        return \MAX_Delivery_log_logConversion($trackerId, $aConversion);
    }

    public static function MAX_Delivery_log_logVariableValues($aVariables, $trackerId, $serverConvId, $serverRawIp, $pluginId = null)
    {
        return \MAX_Delivery_log_logVariableValues($aVariables, $trackerId, $serverConvId, $serverRawIp, $pluginId);
    }

    public static function _viewersHostOkayToLog($adId = 0, $zoneId = 0, $trackerId = 0)
    {
        return \_viewersHostOkayToLog($adId, $zoneId, $trackerId);
    }

    public static function MAX_Delivery_log_getArrGetVariable(string $name, ?array $array = null)
    {
        return \MAX_Delivery_log_getArrGetVariable($name, $array);
    }

    public static function MAX_Delivery_log_ensureIntegerSet(&$aArray, $index)
    {
        return \MAX_Delivery_log_ensureIntegerSet($aArray, $index);
    }

    public static function MAX_Delivery_log_setAdLimitations($index, $aAds, $aCaps)
    {
        return \MAX_Delivery_log_setAdLimitations($index, $aAds, $aCaps);
    }

    public static function MAX_Delivery_log_setCampaignLimitations($index, $aCampaigns, $aCaps)
    {
        return \MAX_Delivery_log_setCampaignLimitations($index, $aCampaigns, $aCaps);
    }

    public static function MAX_Delivery_log_setZoneLimitations($index, $aZones, $aCaps)
    {
        return \MAX_Delivery_log_setZoneLimitations($index, $aZones, $aCaps);
    }

    public static function MAX_Delivery_log_setLastAction($index, $aAdIds, $aZoneIds, $aSetLastSeen, $action = 'view')
    {
        return \MAX_Delivery_log_setLastAction($index, $aAdIds, $aZoneIds, $aSetLastSeen, $action);
    }

    public static function MAX_Delivery_log_setClickBlocked($index, $aAdIds)
    {
        return \MAX_Delivery_log_setClickBlocked($index, $aAdIds);
    }

    public static function MAX_Delivery_log_isClickBlocked($adId, $aBlockLoggingClick)
    {
        return \MAX_Delivery_log_isClickBlocked($adId, $aBlockLoggingClick);
    }

    public static function _setLimitations($type, $index, $aItems, $aCaps)
    {
        return \_setLimitations($type, $index, $aItems, $aCaps);
    }
}
