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
 * A class for testing the legacy section navigation helper in lib-gui.inc.php.
 *
 * Section tabs are rendered by OA_Admin_UI, so phpAds_ShowSections() is kept
 * as a no-op for the admin pages that still call it.
 *
 * @package    OpenXAdmin
 * @subpackage TestSuite
 */
class Test_LibGui extends UnitTestCase
{
    private $aOriginalNavigation;
    private $originalNavigationId;

    public function setUp()
    {
        $this->aOriginalNavigation = $GLOBALS['OA_Navigation'] ?? null;
        $this->originalNavigationId = $GLOBALS['OA_Navigation_ID'] ?? null;

        $GLOBALS['OA_Navigation_ID'] = '5.1';
    }

    public function tearDown()
    {
        $GLOBALS['OA_Navigation'] = $this->aOriginalNavigation;
        $GLOBALS['OA_Navigation_ID'] = $this->originalNavigationId;
    }

    public function testShowSectionsRendersNothingForAdminPageSections()
    {
        $aSectionLists = [
            ['5.1', '5.2', '5.3', '5.5', '5.6', '5.4'],   // userlog-index.php
            ['4.1', '4.3', '4.4', '4.4.1'],               // admin-user-start.php
            ['4.1.2', '4.1.3', '4.1.5', '4.1.5.2'],       // advertiser-user.php
        ];
        foreach ($aSectionLists as $aSections) {
            $this->assertIdentical('', $this->_showSections([$aSections]));
            $this->assertIdentical('', $this->_showSections([$aSections, ['clientid' => 1], false]));
        }
    }

    public function testShowSectionsRendersNothingForCustomNavigation()
    {
        $aCustomNav = [
            '5.1' => ['account-index.php' => 'My Account'],
            '5.2' => ['account-user-name-language.php' => 'User Preferences'],
            '5.3' => ['' => 'No link'],
            '5.4' => [],
        ];

        $this->assertIdentical('', $this->_showSections([['5.1', '5.2', '5.3', '5.4', '9.9'], false, true, '', $aCustomNav]));
    }

    public function testShowSectionsDoesNotRaiseErrors()
    {
        $aErrors = [];
        set_error_handler(function ($errno, $errstr) use (&$aErrors) {
            $aErrors[] = $errstr;
            return true;
        });
        try {
            $this->_showSections([['4.1', '4.3', '4.4']]);
            $this->_showSections([[], false, false]);
        } finally {
            restore_error_handler();
        }

        $this->assertIdentical([], $aErrors);
    }

    /**
     * Calls phpAds_ShowSections() and returns whatever it printed.
     *
     * @param array $aArgs
     * @return string
     */
    private function _showSections(array $aArgs)
    {
        ob_start();
        try {
            $result = phpAds_ShowSections(...$aArgs);
        } finally {
            $output = ob_get_clean();
        }
        $this->assertNull($result);

        return $output;
    }
}
