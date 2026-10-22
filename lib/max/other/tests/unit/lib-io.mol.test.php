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

require_once MAX_PATH . '/lib/max/other/lib-io.inc.php';

/*
 * A class for testing the lib-io return URL helpers.
 *
 * @package    MaxOther
 * @subpackage TestSuite
 */
class LibIoTest extends UnitTestCase
{
    public function test_phpAds_isLocalUrl()
    {
        $this->assertTrue(phpAds_isLocalUrl('advertiser-index.php'));
        $this->assertTrue(phpAds_isLocalUrl('campaign-banners.php?clientid=1'));
        $this->assertTrue(phpAds_isLocalUrl('/www/admin/index.php'));

        $this->assertFalse(phpAds_isLocalUrl(''));
        $this->assertFalse(phpAds_isLocalUrl('https://example.com/'));
        $this->assertFalse(phpAds_isLocalUrl('//example.com/'));
        $this->assertFalse(phpAds_isLocalUrl('/\\example.com/'));
        $this->assertFalse(phpAds_isLocalUrl('javascript:alert(1)'));
        $this->assertFalse(phpAds_isLocalUrl("java\tscript:alert(1)"));
        $this->assertFalse(phpAds_isLocalUrl("index.php\r\nX-Header: 1"));
        $this->assertFalse(phpAds_isLocalUrl('///example.com/'));
        $this->assertFalse(phpAds_isLocalUrl('/\\/example.com/'));
        $this->assertFalse(phpAds_isLocalUrl('http:/example.com/'));
        $this->assertFalse(phpAds_isLocalUrl('http:example.com'));
        $this->assertFalse(phpAds_isLocalUrl('example.com:80/'));
        $this->assertTrue(phpAds_isLocalUrl('/./index.php'));
        $this->assertTrue(phpAds_isLocalUrl('index.php?next=https://example.com/'));
        $this->assertTrue(phpAds_isLocalUrl('?next=https://example.com/'));
        $this->assertTrue(phpAds_isLocalUrl('#a:b'));
    }

    public function test_phpAds_getReturnUrl()
    {
        $get = $_GET;
        $post = $_POST;

        $_GET = $_POST = [];
        $this->assertEqual(phpAds_getReturnUrl('default.php'), 'default.php');

        $_GET['returnurl'] = 'https://example.com/';
        $this->assertEqual(phpAds_getReturnUrl('default.php'), 'default.php');

        $_GET['returnurl'] = 'zone-index.php';
        $this->assertEqual(phpAds_getReturnUrl('default.php'), 'zone-index.php');

        $_POST['returnurl'] = 'banner-edit.php';
        $this->assertEqual(phpAds_getReturnUrl('default.php'), 'banner-edit.php');

        $_POST['returnurl'] = ['array.php'];
        $this->assertEqual(phpAds_getReturnUrl('default.php'), 'default.php');

        $_GET = $get;
        $_POST = $post;
    }
}
