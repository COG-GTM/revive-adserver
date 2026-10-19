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
 * A class for testing the legacy GUI helpers in www/admin/lib-gui.inc.php.
 *
 * @package    OpenXAdmin
 * @subpackage TestSuite
 */
class Test_Admin_LibGui extends UnitTestCase
{
    /**
     * phpAds_ShowSections() is a no-op (its body is commented out; section
     * tabs are rendered by OA_Admin_UI), so it must not print anything.
     */
    public function test_phpAds_ShowSections_printsNothing()
    {
        $aCalls = [
            [["4.1", "4.3", "4.4", "4.4.1"]],
            [["5.1", "5.2", "5.3", "5.5", "5.6", "5.4"]],
            [["4.1.2", "4.1.3", "4.1.5", "4.1.5.2"], ['clientid' => 1, 'userid' => 2]],
            [["4.1"], false, false],
            [["custom"], false, true, '', ['custom' => ['custom.php' => 'Custom']]],
        ];

        foreach ($aCalls as $aArgs) {
            ob_start();
            $result = phpAds_ShowSections(...$aArgs);
            $output = ob_get_clean();

            $this->assertIdentical('', $output, 'phpAds_ShowSections(' . json_encode($aArgs) . ') printed output');
            $this->assertNull($result);
        }
    }
}
