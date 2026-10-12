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

namespace RV\Tests\Unit\RV\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RV_Admin_DateTimeFormat;

require_once MAX_PATH . '/lib/RV/Admin/DateTimeFormat.php';

#[CoversClass(RV_Admin_DateTimeFormat::class)]
final class DateTimeFormatTest extends TestCase
{
    // A UTC date/time string, as stored in the database
    private const UTC_DATE_TIME = '2017-09-06 00:00:00';

    private const GLOBALS_TO_RESTORE = ['_DATE_TIMEZONE_DEFAULT', 'date_format', 'time_format'];

    private array $savedGlobals = [];

    protected function setUp(): void
    {
        foreach (self::GLOBALS_TO_RESTORE as $name) {
            if (array_key_exists($name, $GLOBALS)) {
                $this->savedGlobals[$name] = $GLOBALS[$name];
            }
        }

        // Set the user locale timezone to something unusual
        $GLOBALS['_DATE_TIMEZONE_DEFAULT'] = 'Australia/Adelaide';
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
    }

    public function testFormatUTCDateTime(): void
    {
        // Set the user local date_format and time_format strings to something unusual
        $GLOBALS['date_format'] = '%m--%Y--%d';
        $GLOBALS['time_format'] = '%S::%H::%M';

        $this->assertEquals(
            '09--2017--06 00::09::30',
            RV_Admin_DateTimeFormat::formatUTCDateTime(self::UTC_DATE_TIME),
        );
    }

    public function testFormatUTCDate(): void
    {
        // Set the user local date_format string to something unusual
        $GLOBALS['date_format'] = '%m--%Y--%d';

        $this->assertEquals('09--2017--06', RV_Admin_DateTimeFormat::formatUTCDate(self::UTC_DATE_TIME));
    }

    public function testFormatUTCTime(): void
    {
        // Set the user local time_format string to something unusual
        $GLOBALS['time_format'] = '%S::%H::%M';

        $this->assertEquals('00::09::30', RV_Admin_DateTimeFormat::formatUTCTime(self::UTC_DATE_TIME));
    }
}
