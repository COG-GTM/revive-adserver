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

if (!\function_exists('MAX_adRender')) {
    require_once dirname(__DIR__, 4) . '/max/Delivery/adRender.php';
}

/**
 * Namespaced facade for the procedural delivery library
 * lib/max/Delivery/adRender.php. Each static method delegates to the
 * corresponding global function, which remains the canonical
 * implementation on the delivery fast path.
 */
class AdRender
{
    public static function MAX_adRender(array &$aBanner, int $zoneId = 0, string $source = '', string $target = '', string $ct0 = '', bool $withText = false, string $charset = '', bool $logClick = true, bool $logView = true, bool $richMedia = true, string $loc = '', ?string $referer = null, array &$context = [])
    {
        return \MAX_adRender($aBanner, $zoneId, $source, $target, $ct0, $withText, $charset, $logClick, $logView, $richMedia, $loc, $referer, $context);
    }

    public static function MAX_adRenderImageBeacon($logUrl, $beaconId = 'beacon', $userAgent = null)
    {
        return \MAX_adRenderImageBeacon($logUrl, $beaconId, $userAgent);
    }

    public static function MAX_adRenderBlankBeacon($zoneId, $source, $loc, $referer)
    {
        return \MAX_adRenderBlankBeacon($zoneId, $source, $loc, $referer);
    }

    public static function _adRenderImage(&$aBanner, $zoneId = 0, $source = '', $ct0 = '', $withText = false, $logClick = true, $logView = true, $useAlt = false, $richMedia = true, $loc = '', $referer = '', $context = [], $useAppend = true)
    {
        return \_adRenderImage($aBanner, $zoneId, $source, $ct0, $withText, $logClick, $logView, $useAlt, $richMedia, $loc, $referer, $context, $useAppend);
    }

    public static function _adRenderHtml(&$aBanner, $zoneId = 0, $source = '', $ct0 = '', $withText = false, $logClick = true, $logView = true, $useAlt = false, $richMedia = true, $loc = '', $referer = '', $context = [])
    {
        return \_adRenderHtml($aBanner, $zoneId, $source, $ct0, $withText, $logClick, $logView, $useAlt, $richMedia, $loc, $referer, $context);
    }

    public static function _adRenderText(&$aBanner, $zoneId = 0, $source = '', $ct0 = '', $withText = false, $logClick = true, $logView = true, $useAlt = false, $richMedia = false, $loc = '', $referer = '', $context = [])
    {
        return \_adRenderText($aBanner, $zoneId, $source, $ct0, $withText, $logClick, $logView, $useAlt, $richMedia, $loc, $referer, $context);
    }

    public static function _adRenderBuildFileUrl($aBanner, $useAlt = false, $params = '')
    {
        return \_adRenderBuildFileUrl($aBanner, $useAlt, $params);
    }

    public static function _adRenderBuildImageUrlPrefix()
    {
        return \_adRenderBuildImageUrlPrefix();
    }

    public static function _adRenderBuildLogURL($aBanner, $zoneId = 0, $source = '', $loc = '', $referer = '', $amp = '&amp;', $fallBack = false)
    {
        return \_adRenderBuildLogURL($aBanner, $zoneId, $source, $loc, $referer, $amp, $fallBack);
    }

    public static function _adRenderImageBeacon($aBanner, $zoneId = 0, $source = '', $loc = '', $referer = '', $logUrl = '')
    {
        return \_adRenderImageBeacon($aBanner, $zoneId, $source, $loc, $referer, $logUrl);
    }

    public static function _adRenderBuildClickQueryString(array $aBanner, int $zoneId = 0, string $source = '', bool $logClick = true, ?string $customDestination = null)
    {
        return \_adRenderBuildClickQueryString($aBanner, $zoneId, $source, $logClick, $customDestination);
    }

    public static function _adRenderReplaceMagicMacros(array $aBanner, string $input)
    {
        return \_adRenderReplaceMagicMacros($aBanner, $input);
    }

    public static function _adRenderBuildSignedClickUrl(array $aBanner, int $zoneId = 0, string $source = '', ?string $ct0 = null, bool $logClick = true, ?string $customDestination = null)
    {
        return \_adRenderBuildSignedClickUrl($aBanner, $zoneId, $source, $ct0, $logClick, $customDestination);
    }

    public static function _adRenderBuildParams($aBanner, $zoneId = 0, $source = '', $ct0 = '', $logClick = true, $overrideDest = false)
    {
        return \_adRenderBuildParams($aBanner, $zoneId, $source, $ct0, $logClick, $overrideDest);
    }

    public static function _adRenderBuildClickUrl($aBanner, $zoneId = 0, $source = '', $ct0 = '', $logClick = true, $overrideDest = false)
    {
        return \_adRenderBuildClickUrl($aBanner, $zoneId, $source, $ct0, $logClick, $overrideDest);
    }

    public static function _adRenderBuildStatusCode($aBanner)
    {
        return \_adRenderBuildStatusCode($aBanner);
    }

    public static function _adRenderBuildRelAttribute($aBanner)
    {
        return \_adRenderBuildRelAttribute($aBanner);
    }

    public static function _getAdRenderFunction($aBanner, $richMedia = true)
    {
        return \_getAdRenderFunction($aBanner, $richMedia);
    }

    public static function _adRenderAddPluginMagicMacros(array &$aMagicMacros, array $aBanner, string $code)
    {
        return \_adRenderAddPluginMagicMacros($aMagicMacros, $aBanner, $code);
    }
}
