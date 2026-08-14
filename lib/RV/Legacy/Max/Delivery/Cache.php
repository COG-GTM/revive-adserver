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

require_once dirname(__DIR__, 4) . '/max/Delivery/cache.php';

/**
 * Namespaced facade for the procedural delivery library
 * lib/max/Delivery/cache.php. Each static method delegates to the
 * corresponding global function, which remains the canonical
 * implementation on the delivery fast path.
 */
class Cache
{
    public static function OA_Delivery_Cache_fetch($name, $isHash = false, $expiryTime = null)
    {
        return \OA_Delivery_Cache_fetch($name, $isHash, $expiryTime);
    }

    public static function OA_Delivery_Cache_store($name, $cache, $isHash = false, $expireAt = null)
    {
        return \OA_Delivery_Cache_store($name, $cache, $isHash, $expireAt);
    }

    public static function OA_Delivery_Cache_store_return($name, $cache, $isHash = false, $expireAt = null)
    {
        return \OA_Delivery_Cache_store_return($name, $cache, $isHash, $expireAt);
    }

    public static function OA_Delivery_Cache_getHookName($name)
    {
        return \OA_Delivery_Cache_getHookName($name);
    }

    public static function OA_Delivery_Cache_buildFileName($name, $isHash = false)
    {
        return \OA_Delivery_Cache_buildFileName($name, $isHash);
    }

    public static function OA_Delivery_Cache_getName($functionName, ...$args)
    {
        return \OA_Delivery_Cache_getName($functionName, ...$args);
    }

    public static function MAX_cacheGetAd($ad_id, $cached = true)
    {
        return \MAX_cacheGetAd($ad_id, $cached);
    }

    public static function MAX_cacheGetAccountTZs($cached = true)
    {
        return \MAX_cacheGetAccountTZs($cached);
    }

    public static function MAX_cacheGetZoneLinkedAds($zoneId, $cached = true)
    {
        return \MAX_cacheGetZoneLinkedAds($zoneId, $cached);
    }

    public static function MAX_cacheGetZoneLinkedAdInfos($zoneId, $cached = true)
    {
        return \MAX_cacheGetZoneLinkedAdInfos($zoneId, $cached);
    }

    public static function MAX_cacheGetZoneInfo($zoneId, $cached = true)
    {
        return \MAX_cacheGetZoneInfo($zoneId, $cached);
    }

    public static function MAX_cacheGetLinkedAds($search, $campaignid, $laspart, $cached = true)
    {
        return \MAX_cacheGetLinkedAds($search, $campaignid, $laspart, $cached);
    }

    public static function MAX_cacheGetLinkedAdInfos($search, $campaignid, $laspart, $cached = true)
    {
        return \MAX_cacheGetLinkedAdInfos($search, $campaignid, $laspart, $cached);
    }

    public static function MAX_cacheGetCreative($filename, $cached = true)
    {
        return \MAX_cacheGetCreative($filename, $cached);
    }

    public static function MAX_cacheGetTracker($trackerid, $cached = true)
    {
        return \MAX_cacheGetTracker($trackerid, $cached);
    }

    public static function MAX_cacheGetTrackerLinkedCreatives($trackerid = null, $cached = true)
    {
        return \MAX_cacheGetTrackerLinkedCreatives($trackerid, $cached);
    }

    public static function MAX_cacheGetTrackerVariables($trackerid, $cached = true)
    {
        return \MAX_cacheGetTrackerVariables($trackerid, $cached);
    }

    public static function MAX_cacheCheckIfMaintenanceShouldRun($cached = true)
    {
        return \MAX_cacheCheckIfMaintenanceShouldRun($cached);
    }

    public static function MAX_cacheGetChannelLimitations($channelid, $cached = true)
    {
        return \MAX_cacheGetChannelLimitations($channelid, $cached);
    }

    public static function OA_cacheGetPublisherZones($affiliateid, $cached = true)
    {
        return \OA_cacheGetPublisherZones($affiliateid, $cached);
    }
}
