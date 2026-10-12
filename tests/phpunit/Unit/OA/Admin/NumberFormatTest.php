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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

require_once MAX_PATH . '/lib/OA/Admin/NumberFormat.php';

#[CoversClass(OA_Admin_NumberFormat::class)]
final class NumberFormatTest extends TestCase
{
    private const GLOBALS_TO_RESTORE = ['phpAds_DecimalPoint', 'phpAds_ThousandsSeperator'];

    private array $savedGlobals = [];

    private mixed $savedPercentageDecimals = null;

    protected function setUp(): void
    {
        foreach (self::GLOBALS_TO_RESTORE as $name) {
            if (array_key_exists($name, $GLOBALS)) {
                $this->savedGlobals[$name] = $GLOBALS[$name];
            }
        }
        $this->savedPercentageDecimals = $GLOBALS['_MAX']['PREF']['ui_percentage_decimals'] ?? null;
    }

    protected function tearDown(): void
    {
        foreach (self::GLOBALS_TO_RESTORE as $name) {
            if (array_key_exists($name, $this->savedGlobals)) {
                $GLOBALS[$name] = $this->savedGlobals[$name];
            } else {
                unset($GLOBALS[$name]);
            }
        }
        if (null === $this->savedPercentageDecimals) {
            unset($GLOBALS['_MAX']['PREF']['ui_percentage_decimals']);
        } else {
            $GLOBALS['_MAX']['PREF']['ui_percentage_decimals'] = $this->savedPercentageDecimals;
        }
    }

    public function testUnformatNumber(): void
    {
        // Set default decimal point
        $GLOBALS['phpAds_DecimalPoint'] = '.';

        // "," as decimal separator
        $this->assertEquals('12345.67', OA_Admin_NumberFormat::unformatNumber('12.345,67'));
        // "+" symbol
        $this->assertEquals('12345.67', OA_Admin_NumberFormat::unformatNumber('+12,345.67'));
        // " " as thousands separator
        $this->assertEquals('12345.67', OA_Admin_NumberFormat::unformatNumber('12 345.67'));
        // "-" symbol
        $this->assertEquals('-12345678', OA_Admin_NumberFormat::unformatNumber('-12,345,678'));
        // White spaces
        $this->assertEquals('12345678', OA_Admin_NumberFormat::unformatNumber('  12.345.678  '));
        // Missing numeral between thousands separators
        $this->assertFalse(OA_Admin_NumberFormat::unformatNumber('12.34.567'));
        // Decimal separator at beginning
        $this->assertEquals('.123', OA_Admin_NumberFormat::unformatNumber('.123'));
        $this->assertEquals('.123', OA_Admin_NumberFormat::unformatNumber(',123'));

        // Hazard situations; results are determined by default decimal separator
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

        // NAN (Not A Number)
        $this->assertFalse(OA_Admin_NumberFormat::formatNumber('string'));
    }
}
