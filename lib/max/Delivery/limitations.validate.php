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

/**
 * @package    MaxDelivery
 * @subpackage limitations
 */

/**
 * Checks that a "compiledlimitation" string only uses the grammar emitted by
 * the delivery limitation plugins' compile() methods, before it is eval'd:
 * true/false/null, and/or/&&/||/!, parentheses, commas, string and number
 * literals and calls to MAX_check{Group}_{Component}() functions.
 *
 * @param string $code
 * @return bool
 */
function MAX_limitationsIsSafeCompiled($code)
{
    static $aCache = [];

    if (!is_string($code)) {
        return false;
    }
    if (isset($aCache[$code])) {
        return $aCache[$code];
    }
    if (count($aCache) >= 1000) {
        $aCache = [];
    }

    return $aCache[$code] = _limitationsIsSafeCompiled($code);
}

/**
 * @access private
 * @param string $code
 * @return bool
 */
function _limitationsIsSafeCompiled($code)
{
    $aTokens = token_get_all('<?php ' . $code);
    array_shift($aTokens);

    $depth = 0;
    $expectCall = false;
    // Whether a "(" is allowed here as a grouping parenthesis: it must not
    // follow an operand, e.g. 'system'('id') or ('system')('id')
    $canGroup = true;
    $empty = true;

    foreach ($aTokens as $token) {
        if (is_array($token) && $token[0] === T_WHITESPACE) {
            continue;
        }
        $empty = false;

        if ($expectCall) {
            if ($token !== '(') {
                return false;
            }
            $expectCall = false;
            $canGroup = true;
            $depth++;
            continue;
        }

        if (is_array($token)) {
            switch ($token[0]) {
                case T_LOGICAL_AND:
                case T_LOGICAL_OR:
                case T_BOOLEAN_AND:
                case T_BOOLEAN_OR:
                    $canGroup = true;
                    break;
                case T_CONSTANT_ENCAPSED_STRING:
                case T_LNUMBER:
                case T_DNUMBER:
                    $canGroup = false;
                    break;
                case T_STRING:
                    if (in_array(strtolower($token[1]), ['true', 'false', 'null'], true)) {
                        $canGroup = false;
                    } elseif (preg_match('/^MAX_check[A-Za-z0-9]+_[A-Za-z0-9_]+$/D', $token[1])) {
                        $expectCall = true;
                    } else {
                        return false;
                    }
                    break;
                default:
                    return false;
            }
            continue;
        }

        switch ($token) {
            case '(':
                if (!$canGroup) {
                    return false;
                }
                $depth++;
                break;
            case ')':
                if (--$depth < 0) {
                    return false;
                }
                $canGroup = false;
                break;
            case ',':
            case '!':
                $canGroup = true;
                break;
            default:
                return false;
        }
    }

    return !$empty && !$expectCall && $depth === 0;
}
