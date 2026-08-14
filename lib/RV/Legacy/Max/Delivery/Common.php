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

require_once dirname(__DIR__, 4) . '/max/Delivery/common.php';

/**
 * Namespaced facade for the procedural delivery library
 * lib/max/Delivery/common.php. Each static method delegates to the
 * corresponding global function, which remains the canonical
 * implementation on the delivery fast path.
 */
class Common
{
    public static function MAX_commonGetDeliveryUrl($file = '')
    {
        return \MAX_commonGetDeliveryUrl($file);
    }

    public static function MAX_commonConstructDeliveryUrl($file, bool $secure = false)
    {
        return \MAX_commonConstructDeliveryUrl($file, $secure);
    }

    public static function MAX_commonConstructSecureDeliveryUrl($file)
    {
        return \MAX_commonConstructSecureDeliveryUrl($file);
    }

    public static function MAX_commonConstructPartialDeliveryUrl($file, $ssl = false)
    {
        return \MAX_commonConstructPartialDeliveryUrl($file, $ssl);
    }

    public static function MAX_commonRemoveSpecialChars(&$var)
    {
        return \MAX_commonRemoveSpecialChars($var);
    }

    public static function MAX_commonConvertEncoding($content, $toEncoding, $fromEncoding = 'UTF-8', $aExtensions = null)
    {
        return \MAX_commonConvertEncoding($content, $toEncoding, $fromEncoding, $aExtensions);
    }

    public static function MAX_commonSendContentTypeHeader($type = 'text/html', $charset = null)
    {
        return \MAX_commonSendContentTypeHeader($type, $charset);
    }

    public static function MAX_commonSetNoCacheHeaders()
    {
        return \MAX_commonSetNoCacheHeaders();
    }

    public static function MAX_commonAddslashesRecursive($a)
    {
        return \MAX_commonAddslashesRecursive($a);
    }

    public static function MAX_commonRegisterGlobalsArray($args = [])
    {
        return \MAX_commonRegisterGlobalsArray($args);
    }

    public static function MAX_commonDeriveSource($source)
    {
        return \MAX_commonDeriveSource($source);
    }

    public static function MAX_commonEncrypt($string)
    {
        return \MAX_commonEncrypt($string);
    }

    public static function MAX_commonDecrypt($string)
    {
        return \MAX_commonDecrypt($string);
    }

    public static function MAX_commonInitVariables()
    {
        return \MAX_commonInitVariables();
    }

    public static function MAX_commonIsAdActionBlockedBecauseInactive($adId)
    {
        return \MAX_commonIsAdActionBlockedBecauseInactive($adId);
    }

    public static function MAX_commonDisplay1x1()
    {
        return \MAX_commonDisplay1x1();
    }

    public static function MAX_commonGetTimeNow()
    {
        return \MAX_commonGetTimeNow();
    }

    public static function MAX_getRandomNumber($length = 10)
    {
        return \MAX_getRandomNumber($length);
    }

    public static function MAX_header($value)
    {
        return \MAX_header($value);
    }

    public static function MAX_redirect($url)
    {
        return \MAX_redirect($url);
    }

    public static function MAX_sendStatusCode($iStatusCode)
    {
        return \MAX_sendStatusCode($iStatusCode);
    }

    public static function MAX_commonPackContext($context = [])
    {
        return \MAX_commonPackContext($context);
    }

    public static function MAX_commonUnpackContext($context = '')
    {
        return \MAX_commonUnpackContext($context);
    }

    public static function MAX_commonCompressInt($int)
    {
        return \MAX_commonCompressInt($int);
    }

    public static function MAX_commonUnCompressInt($string)
    {
        return \MAX_commonUnCompressInt($string);
    }

    public static function _convertContextArray($key, $array)
    {
        return \_convertContextArray($key, $array);
    }

    public static function OX_Delivery_Common_hook($hookName, $aParams = [], $functionName = '')
    {
        return \OX_Delivery_Common_hook($hookName, $aParams, $functionName);
    }

    public static function OX_Delivery_Common_getFunctionFromComponentIdentifier($identifier, $hook = null)
    {
        return \OX_Delivery_Common_getFunctionFromComponentIdentifier($identifier, $hook);
    }

    public static function OX_Delivery_Common_getClickSignature(int $adId, int $zoneId, string $data)
    {
        return \OX_Delivery_Common_getClickSignature($adId, $zoneId, $data);
    }

    public static function OX_Delivery_Common_checkClickSignature(int $adId, int $zoneId, string $dest)
    {
        return \OX_Delivery_Common_checkClickSignature($adId, $zoneId, $dest);
    }

    public static function OX_Delivery_Common_sendPreconnectHeaders()
    {
        return \OX_Delivery_Common_sendPreconnectHeaders();
    }

    public static function _includeDeliveryPluginFile($fileName)
    {
        return \_includeDeliveryPluginFile($fileName);
    }

    public static function OX_Delivery_logMessage($message, $priority = 6)
    {
        return \OX_Delivery_logMessage($message, $priority);
    }
}
