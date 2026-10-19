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

use PHPUnit\Framework\TestCase;
use RV_Admin_DateTimeFormat;

require_once MAX_PATH . '/lib/RV/Admin/DateTimeFormat.php';

/**
 * Ported from the SimpleTest suite lib/RV/Admin/tests/unit/DateTimeFormat.admin.test.php
 */
class DateTimeFormatTest extends TestCase
{
    private const GLOBALS_USED = ['_DATE_TIMEZONE_DEFAULT', 'date_format', 'time_format'];

    private const UTC_DATE_TIME = '2017-09-06 00:00:00';

    private array $aBackup = [];

    protected function setUp(): void
    {
        foreach (self::GLOBALS_USED as $name) {
            $this->aBackup[$name] = $GLOBALS[$name] ?? null;
        }

        // Set the user locale timezone, date_format and time_format to something unusual
        $GLOBALS['_DATE_TIMEZONE_DEFAULT'] = 'Australia/Adelaide';
        $GLOBALS['date_format'] = '%m--%Y--%d';
        $GLOBALS['time_format'] = '%S::%H::%M';
    }

    protected function tearDown(): void
    {
        foreach (self::GLOBALS_USED as $name) {
            if (isset($this->aBackup[$name])) {
                $GLOBALS[$name] = $this->aBackup[$name];
            } else {
                unset($GLOBALS[$name]);
            }
        }
    }

    public function testFormatUTCDateTime(): void
    {
        $this->assertEquals(
            '09--2017--06 00::09::30',
            RV_Admin_DateTimeFormat::formatUTCDateTime(self::UTC_DATE_TIME),
        );
    }

    public function testFormatUTCDate(): void
    {
        $this->assertEquals('09--2017--06', RV_Admin_DateTimeFormat::formatUTCDate(self::UTC_DATE_TIME));
    }

    public function testFormatUTCTime(): void
    {
        $this->assertEquals('00::09::30', RV_Admin_DateTimeFormat::formatUTCTime(self::UTC_DATE_TIME));
    }
}
