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

require_once MAX_PATH . '/lib/max/Delivery/limitations.validate.php';

/**
 * A class for testing the MAX_limitationsIsSafeCompiled() function.
 *
 * @package    MaxDelivery
 * @subpackage TestSuite
 */
class Test_DeliveryLimitationsValidate extends UnitTestCase
{
    public function test_validCompiledLimitations()
    {
        $aValid = [
            'true',
            'false',
            'TRUE',
            "MAX_checkDummy_Dummy('1', '==')",
            "MAX_checkDummy_Dummy('1', '==') and MAX_checkDummy_Dummy('2', '==')",
            "MAX_checkDummy_Dummy('openx.org', '!~') or MAX_checkDummy_Dummy('0,1', '=~')",
            "MAX_checkTime_Date('20250101', '>=') AND MAX_checkGeo_Country('gb,us', '=~')",
            "MAX_checkSite_Variable('foo|bar\\'baz', '==')",
            "MAX_checkSite_Variable('a\\\\', '==')",
            "MAX_checkClient_Ip('10.0.0.0/8', '==')",
            "(MAX_checkSite_Channel('1', '==') && MAX_checkSite_Channel('2', '=='))",
            "!(MAX_checkSite_Channel('1', '!~') || MAX_checkSite_Channel('2', '!~'))",
            "MAX_checkTime_Hour('1,2,3', '=~') and (MAX_checkSite_Channel('7', '=='))",
            "MAX_checkDummy_Dummy(1, '==') and MAX_checkDummy_Dummy(1.5, '==')",
            "MAX_checkDummy_Dummy(NULL, '==')",
            "MAX_checkDummy_Dummy(\"double\", '==')",
            "true and true",
        ];
        foreach ($aValid as $code) {
            $this->assertTrue(MAX_limitationsIsSafeCompiled($code), "Expected valid: {$code}");
        }
    }

    public function test_invalidCompiledLimitations()
    {
        $aInvalid = [
            '',
            "MAX_checkDummy_Dummy('1', '==') and system('id') and MAX_checkDummy_Dummy('2', '==')",
            "true and system('id')",
            "system('id')",
            "MAX_checkDummy_Dummy('1', '==') and 'system'('id')",
            "MAX_checkDummy_Dummy('1', '==') and ('system')('id')",
            "MAX_checkDummy_Dummy('1', '==')('id')",
            "MAX_checkDummy_Dummy('1', '=='); system('id')",
            "true) or system('id') or (true",
            "true or (true",
            "true)",
            "MAX_checkDummy_Dummy",
            "MAX_checkDummy_Dummy and true",
            "MAX_checkAd(1, 2, 3)",
            "\\MAX_checkDummy_Dummy('1', '==')",
            "true and \$GLOBALS['x'] = 1",
            "true and `id`",
            "true and \"{\${system('id')}}\"",
            "true and \"\$x\"",
            "true and 'a' . 'b'",
            "true /* comment */",
            "true ?><?php system('id')",
            "true and new stdClass()",
            "true and print('x')",
            "true and eval('1')",
            "true and include('/etc/passwd')",
            "true and PHP_VERSION",
            "true xor false",
            "1 + 1",
            "true ? true : false",
        ];
        foreach ($aInvalid as $code) {
            $this->assertFalse(MAX_limitationsIsSafeCompiled($code), "Expected invalid: {$code}");
        }
    }

    public function test_nonStringInput()
    {
        $this->assertFalse(MAX_limitationsIsSafeCompiled(null));
        $this->assertFalse(MAX_limitationsIsSafeCompiled(['true']));
    }
}
