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

require_once MAX_PATH . '/www/admin/lib-gui.inc.php';

/**
 * A class for testing the phpAds_ShowSections() function, and that the
 * first-party code it shares a code path with no longer relies on the
 * each() function, which was removed in PHP 8.0.
 *
 * @package    OpenXAdmin
 * @subpackage TestSuite
 */
class Test_OA_Admin_ShowSections extends UnitTestCase
{
    public function testShowSectionsRendersNoOutput()
    {
        global $OA_Navigation, $OA_Navigation_ID;

        $OA_Navigation = [
            OA_ACCOUNT_ADMIN => [
                '5.1' => ['account-index.php' => 'My Account'],
                '5.2' => ['account-preferences-index.php' => 'Preferences'],
                '5.4' => ['userlog-index.php' => 'User Log'],
            ],
        ];
        $OA_Navigation_ID = '5.4';

        $aCustomNav = [
            '4.1' => ['admin-user-start.php' => 'User Access', 'unused.php' => 'Unused'],
            '4.3' => ['' => 'No link'],
        ];

        $aCalls = [
            [['5.1', '5.2', '5.4']],
            [['5.1', '5.2', '5.4'], ['clientid' => 1]],
            [['5.1', 'missing'], false, false],
            [['4.1', '4.3', 'missing'], ['userid' => 2], true, '', $aCustomNav],
            [[]],
        ];

        foreach ($aCalls as $aArgs) {
            ob_start();
            $result = phpAds_ShowSections(...$aArgs);
            $output = ob_get_clean();

            $this->assertNull($result);
            $this->assertIdentical($output, '');
        }
    }

    public function testFirstPartyCodeDoesNotCallEach()
    {
        $aFiles = [
            '/www/admin/lib-gui.inc.php',
            '/lib/max/Delivery/adSelect.php',
            '/lib/max/Delivery/common.php',
            '/www/delivery_dev/ck.php',
            '/www/delivery_dev/cl.php',
        ];

        foreach ($aFiles as $file) {
            $this->assertFalse(
                $this->_callsEach(file_get_contents(MAX_PATH . $file)),
                "{$file} calls each(), which was removed in PHP 8.0",
            );
        }
    }

    public function testCallsEachDetection()
    {
        $this->assertTrue($this->_callsEach('<?php [$k, $v] = each($a);'));
        $this->assertTrue($this->_callsEach('<?php list($k, $v) = EACH ($a);'));
        $this->assertFalse($this->_callsEach('<?php $k = key($a); $v = current($a);'));
        $this->assertFalse($this->_callsEach('<?php $o->each($a); Foo::each($a); // each($a)'));
        $this->assertFalse($this->_callsEach('<?php function each(&$array) {}'));
        $this->assertFalse($this->_callsEach('<?php /* list($k, $v) = each($a); */'));
    }

    /**
     * Checks PHP source for calls to the global each() function, ignoring
     * comments, method calls and function declarations.
     *
     * @param string $source
     * @return bool
     */
    public function _callsEach($source)
    {
        $aTokens = array_values(array_filter(
            token_get_all($source),
            fn($token) => !is_array($token) || !in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true),
        ));

        foreach ($aTokens as $i => $token) {
            if (!is_array($token) || $token[0] !== T_STRING || strtolower($token[1]) !== 'each') {
                continue;
            }
            if (($aTokens[$i + 1] ?? null) !== '(') {
                continue;
            }
            $previous = $aTokens[$i - 1] ?? null;
            if (is_array($previous) && in_array($previous[0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION], true)) {
                continue;
            }

            return true;
        }

        return false;
    }
}
