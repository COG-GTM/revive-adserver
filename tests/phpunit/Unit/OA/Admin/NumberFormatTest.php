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

declare(strict_types=1);

namespace RV\Tests\Unit\OA\Admin;

use OA_Admin_NumberFormat;
use PHPUnit\Framework\TestCase;

require_once MAX_PATH . '/lib/OA/Admin/NumberFormat.php';

/**
 * Ported from the SimpleTest suite lib/OA/Admin/tests/unit/NumberFormat.admin.test.php
 */
class NumberFormatTest extends TestCase
{
    private array $aBackup = [];

    protected function setUp(): void
    {
        $this->aBackup = [
            'phpAds_DecimalPoint' => $GLOBALS['phpAds_DecimalPoint'] ?? null,
            'phpAds_ThousandsSeperator' => $GLOBALS['phpAds_ThousandsSeperator'] ?? null,
            'ui_percentage_decimals' => $GLOBALS['_MAX']['PREF']['ui_percentage_decimals'] ?? null,
        ];
    }

    protected function tearDown(): void
    {
        foreach (['phpAds_DecimalPoint', 'phpAds_ThousandsSeperator'] as $name) {
            if (isset($this->aBackup[$name])) {
                $GLOBALS[$name] = $this->aBackup[$name];
            } else {
                unset($GLOBALS[$name]);
            }
        }

        if (isset($this->aBackup['ui_percentage_decimals'])) {
            $GLOBALS['_MAX']['PREF']['ui_percentage_decimals'] = $this->aBackup['ui_percentage_decimals'];
        } else {
            unset($GLOBALS['_MAX']['PREF']['ui_percentage_decimals']);
        }
    }

    public function testUnformatNumber(): void
    {
        // Set default decimalPoint
        $GLOBALS['phpAds_DecimalPoint'] = '.';

        $this->assertEquals('12345.67', OA_Admin_NumberFormat::unformatNumber('12.345,67')); // "," as decimal separator
        $this->assertEquals('12345.67', OA_Admin_NumberFormat::unformatNumber('+12,345.67')); // "+" symbol
        $this->assertEquals('12345.67', OA_Admin_NumberFormat::unformatNumber('12 345.67')); // " " as thousands separator
        $this->assertEquals('-12345678', OA_Admin_NumberFormat::unformatNumber('-12,345,678')); // "-" symbol
        $this->assertEquals('12345678', OA_Admin_NumberFormat::unformatNumber('  12.345.678  ')); // white spaces
        $this->assertFalse(OA_Admin_NumberFormat::unformatNumber('12.34.567')); // missing numeral between thousands separators
        $this->assertEquals('.123', OA_Admin_NumberFormat::unformatNumber('.123')); // decimal separator at beginning
        $this->assertEquals('.123', OA_Admin_NumberFormat::unformatNumber(',123')); // decimal separator at beginning

        // Hazard situations; results are determined by the default decimal separator
        $this->assertEquals('1234', OA_Admin_NumberFormat::unformatNumber('1,234'));
        $GLOBALS['phpAds_DecimalPoint'] = ',';
        $this->assertEquals('1.234', OA_Admin_NumberFormat::unformatNumber('1,234'));
        // Single dot always as decimal point!
        $this->assertEquals('1.234', OA_Admin_NumberFormat::unformatNumber('1.234'));
    }

    public function testFormatNumber(): void
    {
        $GLOBALS['phpAds_DecimalPoint'] = '.';
        $GLOBALS['phpAds_ThousandsSeperator'] = ',';
        $GLOBALS['_MAX']['PREF']['ui_percentage_decimals'] = 2;

        $number = 3.1415926535897932384626433832795 * 1000;

        $this->assertEquals('1,234', OA_Admin_NumberFormat::formatNumber('1234', 0));
        $this->assertEquals('3,141.59', OA_Admin_NumberFormat::formatNumber($number));
        $this->assertEquals('3,141.593', OA_Admin_NumberFormat::formatNumber($number, 3));
        $this->assertEquals('3,141,5927', OA_Admin_NumberFormat::formatNumber($number, 4, ','));
        $this->assertEquals('3.141,59265', OA_Admin_NumberFormat::formatNumber($number, 5, ',', '.'));

        // Change defaults
        $GLOBALS['phpAds_DecimalPoint'] = ',';
        $GLOBALS['phpAds_ThousandsSeperator'] = '.';
        $GLOBALS['_MAX']['PREF']['ui_percentage_decimals'] = 4;

        $this->assertEquals('3.141,5927', OA_Admin_NumberFormat::formatNumber($number));
        $this->assertEquals('3.141,593', OA_Admin_NumberFormat::formatNumber($number, 3));
        $this->assertEquals('3.141,5927', OA_Admin_NumberFormat::formatNumber($number, 4, ','));
        $this->assertEquals('3.141,59265', OA_Admin_NumberFormat::formatNumber($number, 5, ',', '.'));

        // Test NAN (NotANumber)
        $this->assertFalse(OA_Admin_NumberFormat::formatNumber('string'));
    }
}
