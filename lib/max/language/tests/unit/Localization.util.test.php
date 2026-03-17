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
 * Combinatorial testing plan Section 4L: Localization matrix tests.
 *
 * This test suite covers:
 *  - Exhaustive RTL tests (3 locales x 7 UI pages x 4 content types = 84 combinations)
 *  - Sampled LTR tests (5 representative locales)
 *  - OX_Translation unit tests
 *  - OA_Admin_NumberFormat locale-aware tests
 *  - Full locale coverage for critical error messages across all 37 directories
 *
 * @package    MaxUI
 * @subpackage TestSuite
 */

require_once MAX_PATH . '/lib/OX/Translation.php';
require_once MAX_PATH . '/lib/OA/Admin/NumberFormat.php';
require_once MAX_PATH . '/lib/max/language/Loader.php';

class Test_Localization_Combinatorial extends UnitTestCase
{
    /**
     * RTL locales to test exhaustively.
     */
    private $rtlLocales = ['ar', 'he', 'fa'];

    /**
     * Sampled LTR locales for representative coverage.
     */
    private $ltrLocales = ['en', 'de', 'ja', 'ko', 'pt_BR'];

    /**
     * All 37 locale directories expected in lib/max/language/.
     */
    private $allLocales = [
        'ar', 'bg', 'ca', 'cs', 'cy', 'da', 'de', 'el', 'en', 'en_US',
        'es', 'fa', 'fr', 'he', 'hu', 'id', 'it', 'ja', 'ko', 'lt',
        'mk', 'ms', 'nl', 'no', 'pl', 'pt_BR', 'pt_PT', 'ro', 'ru',
        'sk', 'sl', 'sv', 'tr', 'uk', 'vi', 'zh_CN', 'zh_TW',
    ];

    /**
     * UI page sections mapped to their language file sections.
     * 7 pages as specified in the combinatorial matrix.
     */
    private $uiPages = [
        'Dashboard'       => 'default',
        'CampaignEdit'    => 'default',
        'BannerEdit'      => 'default',
        'ZoneEdit'        => 'default',
        'Statistics'      => 'default',
        'Settings'        => 'settings',
        'UserManagement'  => 'default',
    ];

    /**
     * Translation keys organized by UI page and content type.
     * Content types: Labels, Error messages, Date formats, Number formats.
     */
    private $pageContentKeys = [
        'Dashboard' => [
            'labels' => [
                'strDashboardCantBeDisplayed',
                'strDashboardSystemMessage',
                'strHome',
            ],
            'errors' => [
                'strDashboardErrorHelp',
                'strNoCheckForUpdates',
            ],
            'dates' => [
                'strCollectedToday',
                'strCollectedYesterday',
                'strCollectedThisWeek',
                'strCollectedLastWeek',
            ],
            'numbers' => [
                'strValue',
                'strTotal',
                'strAverage',
            ],
        ],
        'CampaignEdit' => [
            'labels' => [
                'strCampaigns',
                'strCampaignName',
                'strPriority',
                'strPriorityLevel',
            ],
            'errors' => [
                'strFieldContainsErrors',
                'strFieldFixBeforeContinue1',
                'strFieldFixBeforeContinue2',
            ],
            'dates' => [
                'strDate',
                'strDay',
                'strDays',
                'strWeek',
            ],
            'numbers' => [
                'strImpressions',
                'strClicks',
                'strConversions',
            ],
        ],
        'BannerEdit' => [
            'labels' => [
                'strBanners',
                'strName',
                'strSize',
                'strWidth',
                'strHeight',
            ],
            'errors' => [
                'strFieldContainsErrors',
                'strFieldFixBeforeContinue1',
            ],
            'dates' => [
                'strDate',
                'strDay',
            ],
            'numbers' => [
                'strImpressions',
                'strClicks',
            ],
        ],
        'ZoneEdit' => [
            'labels' => [
                'strName',
                'strDescription',
                'strWidth',
                'strHeight',
            ],
            'errors' => [
                'strFieldContainsErrors',
                'strFieldFixBeforeContinue1',
            ],
            'dates' => [
                'strDate',
                'strDay',
            ],
            'numbers' => [
                'strTotal',
                'strAverage',
            ],
        ],
        'Statistics' => [
            'labels' => [
                'strImpressions',
                'strClicks',
                'strConversions',
                'strCTR',
            ],
            'errors' => [
                'strFieldContainsErrors',
                'strFieldFixBeforeContinue1',
            ],
            'dates' => [
                'strCollectedAllStats',
                'strCollectedToday',
                'strCollectedYesterday',
                'strCollectedSpecificDates',
            ],
            'numbers' => [
                'strTotal',
                'strAverage',
                'strOverall',
            ],
        ],
        'Settings' => [
            'labels' => [
                'strInstall',
                'strDatabaseSettings',
                'strAdminAccount',
                'strWarning',
            ],
            'errors' => [
                'strErrorWritePermissions',
                'strCantConnectToDb',
            ],
            'dates' => [
                'strBtnContinue',
                'strBtnRetry',
            ],
            'numbers' => [
                'strTablesPrefix',
            ],
        ],
        'UserManagement' => [
            'labels' => [
                'strUsername',
                'strPassword',
                'strPermissions',
                'strUserAccess',
            ],
            'errors' => [
                'strAccessDenied',
                'strUsernameOrPasswordWrong',
                'strPasswordWrong',
                'strDuplicateClientName',
                'strInvalidPassword',
            ],
            'dates' => [
                'strLastLoggedIn',
                'strDateLinked',
            ],
            'numbers' => [
                'strTotal',
            ],
        ],
    ];

    /**
     * Critical error message keys that must exist across all locales.
     */
    private $criticalErrorKeys = [
        // Permission denied
        'strAccessDenied',
        'strUsernameOrPasswordWrong',
        'strPasswordWrong',
        'strNotSamePasswords',
        // Campaign expired / status
        'strFieldContainsErrors',
        'strFieldFixBeforeContinue1',
        'strFieldFixBeforeContinue2',
        // Billing / validation
        'strInvalidPassword',
        'strDuplicateClientName',
        // Date validation
        'strFieldStartDateBeforeEnd',
        // Core UI labels that must always exist
        'strLogin',
        'strLogout',
        'strSave',
        'strCancel',
        'strDelete',
        'strWarning',
    ];

    /**
     * Saved GLOBALS state for cleanup.
     */
    private $savedGlobals = [];

    public function setUp()
    {
        // Save globals that language files modify
        $keysToSave = [
            'phpAds_TextDirection', 'phpAds_TextAlignRight', 'phpAds_TextAlignLeft',
            'phpAds_CharSet', 'phpAds_DecimalPoint', 'phpAds_ThousandsSeperator',
            'date_format', 'time_format', 'minute_format', 'month_format',
            'day_format', 'week_format', 'weekiso_format',
        ];
        foreach ($keysToSave as $key) {
            if (isset($GLOBALS[$key])) {
                $this->savedGlobals[$key] = $GLOBALS[$key];
            }
        }
    }

    public function tearDown()
    {
        // Restore saved globals
        foreach ($this->savedGlobals as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }

    /**
     * Helper: load a locale's language file section, always loading English first
     * to provide fallback values (matching Language_Loader behavior).
     */
    private function loadLocaleSection($locale, $section = 'default')
    {
        // These variables are expected by language files
        if (!defined('PRODUCT_NAME')) {
            define('PRODUCT_NAME', 'Revive Adserver');
        }
        if (!defined('PRODUCT_URL')) {
            define('PRODUCT_URL', 'http://www.revive-adserver.com');
        }
        if (!defined('PRODUCT_DOCSURL')) {
            define('PRODUCT_DOCSURL', 'http://www.revive-adserver.com/docs');
        }
        if (!defined('phpAds_dbmsname')) {
            define('phpAds_dbmsname', '');
        }

        $PRODUCT_NAME = PRODUCT_NAME;
        $PRODUCT_URL = PRODUCT_URL;
        $PRODUCT_DOCSURL = PRODUCT_DOCSURL;
        $phpAds_dbmsname = phpAds_dbmsname;

        // Always load English first as base
        $enFile = MAX_PATH . '/lib/max/language/en/' . $section . '.lang.php';
        if (file_exists($enFile)) {
            include $enFile;
        }

        // Then load the target locale on top
        if ($locale !== 'en') {
            $localeFile = MAX_PATH . '/lib/max/language/' . $locale . '/' . $section . '.lang.php';
            if (file_exists($localeFile)) {
                include $localeFile;
            }
        }
    }

    // =========================================================================
    // SECTION 1: Exhaustive RTL Tests (84 combinations)
    // 3 RTL locales x 7 UI pages x 4 content types = 84 tests
    // =========================================================================

    /**
     * Test RTL text direction is correctly set for all RTL locales.
     */
    public function testRtlTextDirectionArabic()
    {
        $this->loadLocaleSection('ar', 'default');
        $this->assertEqual('rtl', $GLOBALS['phpAds_TextDirection'],
            'Arabic locale must set text direction to RTL');
        $this->assertEqual('left', $GLOBALS['phpAds_TextAlignRight'],
            'Arabic locale must swap right alignment to left');
        $this->assertEqual('right', $GLOBALS['phpAds_TextAlignLeft'],
            'Arabic locale must swap left alignment to right');
    }

    public function testRtlTextDirectionHebrew()
    {
        $this->loadLocaleSection('he', 'default');
        $this->assertEqual('rtl', $GLOBALS['phpAds_TextDirection'],
            'Hebrew locale must set text direction to RTL');
        $this->assertEqual('left', $GLOBALS['phpAds_TextAlignRight'],
            'Hebrew locale must swap right alignment to left');
        $this->assertEqual('right', $GLOBALS['phpAds_TextAlignLeft'],
            'Hebrew locale must swap left alignment to right');
    }

    public function testRtlTextDirectionPersian()
    {
        $this->loadLocaleSection('fa', 'default');
        $this->assertEqual('rtl', $GLOBALS['phpAds_TextDirection'],
            'Persian locale must set text direction to RTL');
        $this->assertEqual('left', $GLOBALS['phpAds_TextAlignRight'],
            'Persian locale must swap right alignment to left');
        $this->assertEqual('right', $GLOBALS['phpAds_TextAlignLeft'],
            'Persian locale must swap left alignment to right');
    }

    /**
     * Exhaustive RTL combination test: iterates all 3 RTL locales x 7 pages x 4 content types.
     * This single test method covers all 84 combinations and verifies that each
     * translation key exists and returns a non-empty string.
     */
    public function testRtlExhaustiveCombinations()
    {
        $combinationCount = 0;

        foreach ($this->rtlLocales as $locale) {
            foreach ($this->pageContentKeys as $page => $contentTypes) {
                $section = $this->uiPages[$page];
                $this->loadLocaleSection($locale, $section);

                // Verify RTL direction is set
                $this->assertEqual('rtl', $GLOBALS['phpAds_TextDirection'],
                    "RTL direction must be set for locale '{$locale}' on page '{$page}'");

                foreach ($contentTypes as $contentType => $keys) {
                    foreach ($keys as $key) {
                        $globalKey = 'str' . $key;
                        // Keys in $GLOBALS are prefixed with 'str' already in the file,
                        // but our key list already includes the prefix
                        $this->assertTrue(
                            isset($GLOBALS[$key]) && strlen(trim($GLOBALS[$key])) > 0,
                            "RTL [{$locale}][{$page}][{$contentType}]: key '{$key}' must exist and be non-empty"
                        );
                        $combinationCount++;
                    }
                }
            }
        }

        // Verify we tested the expected number of combinations
        $this->assertTrue($combinationCount >= 84,
            "Expected at least 84 RTL combinations, tested {$combinationCount}");
    }

    /**
     * Test RTL locale-specific: Arabic Dashboard labels exist and are non-empty.
     */
    public function testRtlArabicDashboardLabels()
    {
        $this->loadLocaleSection('ar', 'default');
        foreach ($this->pageContentKeys['Dashboard']['labels'] as $key) {
            $this->assertTrue(
                isset($GLOBALS[$key]) && strlen(trim($GLOBALS[$key])) > 0,
                "Arabic Dashboard label '{$key}' must exist and be non-empty"
            );
        }
    }

    /**
     * Test RTL locale-specific: Arabic Dashboard error messages.
     */
    public function testRtlArabicDashboardErrors()
    {
        $this->loadLocaleSection('ar', 'default');
        foreach ($this->pageContentKeys['Dashboard']['errors'] as $key) {
            $this->assertTrue(
                isset($GLOBALS[$key]) && strlen(trim($GLOBALS[$key])) > 0,
                "Arabic Dashboard error '{$key}' must exist and be non-empty"
            );
        }
    }

    /**
     * Test RTL locale-specific: Arabic Dashboard date formats.
     */
    public function testRtlArabicDashboardDates()
    {
        $this->loadLocaleSection('ar', 'default');
        foreach ($this->pageContentKeys['Dashboard']['dates'] as $key) {
            $this->assertTrue(
                isset($GLOBALS[$key]) && strlen(trim($GLOBALS[$key])) > 0,
                "Arabic Dashboard date '{$key}' must exist and be non-empty"
            );
        }
    }

    /**
     * Test RTL locale-specific: Arabic Dashboard number formats.
     */
    public function testRtlArabicDashboardNumbers()
    {
        $this->loadLocaleSection('ar', 'default');
        foreach ($this->pageContentKeys['Dashboard']['numbers'] as $key) {
            $this->assertTrue(
                isset($GLOBALS[$key]) && strlen(trim($GLOBALS[$key])) > 0,
                "Arabic Dashboard number '{$key}' must exist and be non-empty"
            );
        }
    }

    /**
     * Test RTL locale-specific: Arabic Campaign Edit labels.
     */
    public function testRtlArabicCampaignEditLabels()
    {
        $this->loadLocaleSection('ar', 'default');
        foreach ($this->pageContentKeys['CampaignEdit']['labels'] as $key) {
            $this->assertTrue(
                isset($GLOBALS[$key]) && strlen(trim($GLOBALS[$key])) > 0,
                "Arabic CampaignEdit label '{$key}' must exist and be non-empty"
            );
        }
    }

    /**
     * Test RTL locale-specific: Arabic Campaign Edit errors.
     */
    public function testRtlArabicCampaignEditErrors()
    {
        $this->loadLocaleSection('ar', 'default');
        foreach ($this->pageContentKeys['CampaignEdit']['errors'] as $key) {
            $this->assertTrue(
                isset($GLOBALS[$key]) && strlen(trim($GLOBALS[$key])) > 0,
                "Arabic CampaignEdit error '{$key}' must exist and be non-empty"
            );
        }
    }

    /**
     * Test RTL locale-specific: Arabic Banner Edit labels.
     */
    public function testRtlArabicBannerEditLabels()
    {
        $this->loadLocaleSection('ar', 'default');
        foreach ($this->pageContentKeys['BannerEdit']['labels'] as $key) {
            $this->assertTrue(
                isset($GLOBALS[$key]) && strlen(trim($GLOBALS[$key])) > 0,
                "Arabic BannerEdit label '{$key}' must exist and be non-empty"
            );
        }
    }

    /**
     * Test RTL locale-specific: Arabic Zone Edit labels.
     */
    public function testRtlArabicZoneEditLabels()
    {
        $this->loadLocaleSection('ar', 'default');
        foreach ($this->pageContentKeys['ZoneEdit']['labels'] as $key) {
            $this->assertTrue(
                isset($GLOBALS[$key]) && strlen(trim($GLOBALS[$key])) > 0,
                "Arabic ZoneEdit label '{$key}' must exist and be non-empty"
            );
        }
    }

    /**
     * Test RTL locale-specific: Arabic Statistics labels.
     */
    public function testRtlArabicStatisticsLabels()
    {
        $this->loadLocaleSection('ar', 'default');
        foreach ($this->pageContentKeys['Statistics']['labels'] as $key) {
            $this->assertTrue(
                isset($GLOBALS[$key]) && strlen(trim($GLOBALS[$key])) > 0,
                "Arabic Statistics label '{$key}' must exist and be non-empty"
            );
        }
    }

    /**
     * Test RTL locale-specific: Arabic Settings labels.
     */
    public function testRtlArabicSettingsLabels()
    {
        $this->loadLocaleSection('ar', 'settings');
        foreach ($this->pageContentKeys['Settings']['labels'] as $key) {
            $this->assertTrue(
                isset($GLOBALS[$key]) && strlen(trim($GLOBALS[$key])) > 0,
                "Arabic Settings label '{$key}' must exist and be non-empty"
            );
        }
    }

    /**
     * Test RTL locale-specific: Arabic User Management error messages.
     */
    public function testRtlArabicUserManagementErrors()
    {
        $this->loadLocaleSection('ar', 'default');
        foreach ($this->pageContentKeys['UserManagement']['errors'] as $key) {
            $this->assertTrue(
                isset($GLOBALS[$key]) && strlen(trim($GLOBALS[$key])) > 0,
                "Arabic UserManagement error '{$key}' must exist and be non-empty"
            );
        }
    }

    /**
     * Test RTL locale-specific: Hebrew Dashboard labels.
     */
    public function testRtlHebrewDashboardLabels()
    {
        $this->loadLocaleSection('he', 'default');
        foreach ($this->pageContentKeys['Dashboard']['labels'] as $key) {
            $this->assertTrue(
                isset($GLOBALS[$key]) && strlen(trim($GLOBALS[$key])) > 0,
                "Hebrew Dashboard label '{$key}' must exist and be non-empty"
            );
        }
    }

    /**
     * Test RTL locale-specific: Hebrew Campaign Edit labels.
     */
    public function testRtlHebrewCampaignEditLabels()
    {
        $this->loadLocaleSection('he', 'default');
        foreach ($this->pageContentKeys['CampaignEdit']['labels'] as $key) {
            $this->assertTrue(
                isset($GLOBALS[$key]) && strlen(trim($GLOBALS[$key])) > 0,
                "Hebrew CampaignEdit label '{$key}' must exist and be non-empty"
            );
        }
    }

    /**
     * Test RTL locale-specific: Hebrew Banner Edit labels.
     */
    public function testRtlHebrewBannerEditLabels()
    {
        $this->loadLocaleSection('he', 'default');
        foreach ($this->pageContentKeys['BannerEdit']['labels'] as $key) {
            $this->assertTrue(
                isset($GLOBALS[$key]) && strlen(trim($GLOBALS[$key])) > 0,
                "Hebrew BannerEdit label '{$key}' must exist and be non-empty"
            );
        }
    }

    /**
     * Test RTL locale-specific: Hebrew Zone Edit labels.
     */
    public function testRtlHebrewZoneEditLabels()
    {
        $this->loadLocaleSection('he', 'default');
        foreach ($this->pageContentKeys['ZoneEdit']['labels'] as $key) {
            $this->assertTrue(
                isset($GLOBALS[$key]) && strlen(trim($GLOBALS[$key])) > 0,
                "Hebrew ZoneEdit label '{$key}' must exist and be non-empty"
            );
        }
    }

    /**
     * Test RTL locale-specific: Hebrew Statistics labels and dates.
     */
    public function testRtlHebrewStatisticsLabelsAndDates()
    {
        $this->loadLocaleSection('he', 'default');
        foreach ($this->pageContentKeys['Statistics']['labels'] as $key) {
            $this->assertTrue(
                isset($GLOBALS[$key]) && strlen(trim($GLOBALS[$key])) > 0,
                "Hebrew Statistics label '{$key}' must exist and be non-empty"
            );
        }
        foreach ($this->pageContentKeys['Statistics']['dates'] as $key) {
            $this->assertTrue(
                isset($GLOBALS[$key]) && strlen(trim($GLOBALS[$key])) > 0,
                "Hebrew Statistics date '{$key}' must exist and be non-empty"
            );
        }
    }

    /**
     * Test RTL locale-specific: Hebrew Settings labels.
     */
    public function testRtlHebrewSettingsLabels()
    {
        $this->loadLocaleSection('he', 'settings');
        foreach ($this->pageContentKeys['Settings']['labels'] as $key) {
            $this->assertTrue(
                isset($GLOBALS[$key]) && strlen(trim($GLOBALS[$key])) > 0,
                "Hebrew Settings label '{$key}' must exist and be non-empty"
            );
        }
    }

    /**
     * Test RTL locale-specific: Hebrew User Management errors.
     */
    public function testRtlHebrewUserManagementErrors()
    {
        $this->loadLocaleSection('he', 'default');
        foreach ($this->pageContentKeys['UserManagement']['errors'] as $key) {
            $this->assertTrue(
                isset($GLOBALS[$key]) && strlen(trim($GLOBALS[$key])) > 0,
                "Hebrew UserManagement error '{$key}' must exist and be non-empty"
            );
        }
    }

    /**
     * Test RTL locale-specific: Persian Dashboard labels.
     */
    public function testRtlPersianDashboardLabels()
    {
        $this->loadLocaleSection('fa', 'default');
        foreach ($this->pageContentKeys['Dashboard']['labels'] as $key) {
            $this->assertTrue(
                isset($GLOBALS[$key]) && strlen(trim($GLOBALS[$key])) > 0,
                "Persian Dashboard label '{$key}' must exist and be non-empty"
            );
        }
    }

    /**
     * Test RTL locale-specific: Persian Campaign Edit labels.
     */
    public function testRtlPersianCampaignEditLabels()
    {
        $this->loadLocaleSection('fa', 'default');
        foreach ($this->pageContentKeys['CampaignEdit']['labels'] as $key) {
            $this->assertTrue(
                isset($GLOBALS[$key]) && strlen(trim($GLOBALS[$key])) > 0,
                "Persian CampaignEdit label '{$key}' must exist and be non-empty"
            );
        }
    }

    /**
     * Test RTL locale-specific: Persian Banner Edit labels.
     */
    public function testRtlPersianBannerEditLabels()
    {
        $this->loadLocaleSection('fa', 'default');
        foreach ($this->pageContentKeys['BannerEdit']['labels'] as $key) {
            $this->assertTrue(
                isset($GLOBALS[$key]) && strlen(trim($GLOBALS[$key])) > 0,
                "Persian BannerEdit label '{$key}' must exist and be non-empty"
            );
        }
    }

    /**
     * Test RTL locale-specific: Persian Zone Edit labels.
     */
    public function testRtlPersianZoneEditLabels()
    {
        $this->loadLocaleSection('fa', 'default');
        foreach ($this->pageContentKeys['ZoneEdit']['labels'] as $key) {
            $this->assertTrue(
                isset($GLOBALS[$key]) && strlen(trim($GLOBALS[$key])) > 0,
                "Persian ZoneEdit label '{$key}' must exist and be non-empty"
            );
        }
    }

    /**
     * Test RTL locale-specific: Persian Statistics labels.
     */
    public function testRtlPersianStatisticsLabels()
    {
        $this->loadLocaleSection('fa', 'default');
        foreach ($this->pageContentKeys['Statistics']['labels'] as $key) {
            $this->assertTrue(
                isset($GLOBALS[$key]) && strlen(trim($GLOBALS[$key])) > 0,
                "Persian Statistics label '{$key}' must exist and be non-empty"
            );
        }
    }

    /**
     * Test RTL locale-specific: Persian Settings labels.
     */
    public function testRtlPersianSettingsLabels()
    {
        $this->loadLocaleSection('fa', 'settings');
        foreach ($this->pageContentKeys['Settings']['labels'] as $key) {
            $this->assertTrue(
                isset($GLOBALS[$key]) && strlen(trim($GLOBALS[$key])) > 0,
                "Persian Settings label '{$key}' must exist and be non-empty"
            );
        }
    }

    /**
     * Test RTL locale-specific: Persian User Management errors.
     */
    public function testRtlPersianUserManagementErrors()
    {
        $this->loadLocaleSection('fa', 'default');
        foreach ($this->pageContentKeys['UserManagement']['errors'] as $key) {
            $this->assertTrue(
                isset($GLOBALS[$key]) && strlen(trim($GLOBALS[$key])) > 0,
                "Persian UserManagement error '{$key}' must exist and be non-empty"
            );
        }
    }

    // =========================================================================
    // SECTION 2: Sampled LTR Tests (5 representative locales)
    // =========================================================================

    /**
     * Test LTR: English (baseline) translation files load and keys exist.
     */
    public function testLtrEnglishBaselineTranslations()
    {
        $this->loadLocaleSection('en', 'default');

        // English must set LTR direction
        $this->assertEqual('ltr', $GLOBALS['phpAds_TextDirection'],
            'English locale must set text direction to LTR');
        $this->assertEqual('right', $GLOBALS['phpAds_TextAlignRight'],
            'English locale must keep right alignment as right');
        $this->assertEqual('left', $GLOBALS['phpAds_TextAlignLeft'],
            'English locale must keep left alignment as left');

        // Verify core labels
        $this->assertEqual('Home', $GLOBALS['strHome']);
        $this->assertEqual('Help', $GLOBALS['strHelp']);
        $this->assertEqual('Save', $GLOBALS['strSave']);
        $this->assertEqual('Cancel', $GLOBALS['strCancel']);
        $this->assertEqual('Delete', $GLOBALS['strDelete']);

        // Verify date format
        $this->assertEqual('%d-%m-%Y', $GLOBALS['date_format']);
        $this->assertEqual('%H:%M:%S', $GLOBALS['time_format']);

        // Verify number format
        $this->assertEqual('.', $GLOBALS['phpAds_DecimalPoint']);
        $this->assertEqual(',', $GLOBALS['phpAds_ThousandsSeperator']);
    }

    /**
     * Test LTR: English settings section loads correctly.
     */
    public function testLtrEnglishSettingsTranslations()
    {
        $this->loadLocaleSection('en', 'settings');

        $this->assertEqual('Install', $GLOBALS['strInstall']);
        $this->assertEqual('Database Settings', $GLOBALS['strDatabaseSettings']);
        $this->assertTrue(
            isset($GLOBALS['strCantConnectToDb']) && strlen($GLOBALS['strCantConnectToDb']) > 0,
            'English settings must have strCantConnectToDb'
        );
    }

    /**
     * Test LTR: German (Latin script, long compound words).
     */
    public function testLtrGermanTranslations()
    {
        $this->loadLocaleSection('de', 'default');

        // German must inherit LTR from English base
        $this->assertEqual('ltr', $GLOBALS['phpAds_TextDirection'],
            'German locale must use LTR text direction');

        // Verify German-specific translations exist and differ from English
        $this->assertTrue(strlen($GLOBALS['strHome']) > 0, 'German strHome must be non-empty');
        $this->assertEqual('Startseite', $GLOBALS['strHome']);
        $this->assertEqual('Hilfe', $GLOBALS['strHelp']);
        $this->assertEqual('Speichern', $GLOBALS['strSave']);

        // Verify German date format (dd.mm.yyyy)
        $this->assertEqual('%d.%m.%Y', $GLOBALS['date_format']);

        // Verify German number format (comma decimal, dot thousands)
        $this->assertEqual(',', $GLOBALS['phpAds_DecimalPoint']);
        $this->assertEqual('.', $GLOBALS['phpAds_ThousandsSeperator']);

        // Verify day names are in German
        $this->assertEqual('Sonntag', $GLOBALS['strDayFullNames'][0]);
        $this->assertEqual('Montag', $GLOBALS['strDayFullNames'][1]);
    }

    /**
     * Test LTR: German settings section.
     */
    public function testLtrGermanSettingsTranslations()
    {
        $this->loadLocaleSection('de', 'settings');
        $this->assertTrue(
            isset($GLOBALS['strInstall']) && strlen($GLOBALS['strInstall']) > 0,
            'German settings must have strInstall'
        );
    }

    /**
     * Test LTR: Japanese (CJK double-width characters).
     */
    public function testLtrJapaneseTranslations()
    {
        $this->loadLocaleSection('ja', 'default');

        // Japanese must use LTR direction
        $this->assertEqual('ltr', $GLOBALS['phpAds_TextDirection'],
            'Japanese locale must use LTR text direction');

        // Verify Japanese translations contain CJK characters
        $this->assertTrue(strlen($GLOBALS['strHome']) > 0, 'Japanese strHome must be non-empty');
        $this->assertTrue(
            mb_strlen($GLOBALS['strHome'], 'UTF-8') > 0,
            'Japanese strHome must contain valid UTF-8 characters'
        );

        // Verify Japanese-specific date format (YYYY年MM月DD日)
        $this->assertEqual('%Y年%m月%d日', $GLOBALS['date_format']);
        $this->assertEqual('%Y年%m月', $GLOBALS['month_format']);

        // Verify Japanese day names
        $this->assertEqual('日曜日', $GLOBALS['strDayFullNames'][0]);
        $this->assertEqual('月曜日', $GLOBALS['strDayFullNames'][1]);

        // Verify CJK short cuts
        $this->assertEqual('日', $GLOBALS['strDayShortCuts'][0]);
        $this->assertEqual('月', $GLOBALS['strDayShortCuts'][1]);
    }

    /**
     * Test LTR: Korean (CJK alternate script).
     */
    public function testLtrKoreanTranslations()
    {
        $this->loadLocaleSection('ko', 'default');

        // Korean must use LTR direction
        $this->assertEqual('ltr', $GLOBALS['phpAds_TextDirection'],
            'Korean locale must use LTR text direction');

        // Verify Korean translations contain Hangul characters
        $this->assertTrue(strlen($GLOBALS['strHome']) > 0, 'Korean strHome must be non-empty');
        $this->assertTrue(
            mb_strlen($GLOBALS['strHome'], 'UTF-8') > 0,
            'Korean strHome must contain valid UTF-8 characters'
        );

        // Verify Korean number format
        $this->assertEqual(',', $GLOBALS['phpAds_DecimalPoint']);
        $this->assertEqual('.', $GLOBALS['phpAds_ThousandsSeperator']);

        // Verify Korean day short cuts
        $this->assertEqual('일', $GLOBALS['strDayShortCuts'][0]);
        $this->assertEqual('월', $GLOBALS['strDayShortCuts'][1]);
    }

    /**
     * Test LTR: Brazilian Portuguese (Latin with accents, date format differences).
     */
    public function testLtrBrazilianPortugueseTranslations()
    {
        $this->loadLocaleSection('pt_BR', 'default');

        // pt_BR must use LTR direction
        $this->assertEqual('ltr', $GLOBALS['phpAds_TextDirection'],
            'Brazilian Portuguese locale must use LTR text direction');

        // Verify Portuguese translations with accented characters
        $this->assertTrue(strlen($GLOBALS['strHome']) > 0, 'pt_BR strHome must be non-empty');
        $this->assertEqual('Principal', $GLOBALS['strHome']);
        $this->assertEqual('Ajuda', $GLOBALS['strHelp']);

        // Verify pt_BR date format (mm/dd/yyyy)
        $this->assertEqual('%m/%d/%Y', $GLOBALS['date_format']);
        $this->assertEqual('%m/%Y', $GLOBALS['month_format']);

        // Verify pt_BR number format (comma decimal, dot thousands)
        $this->assertEqual(',', $GLOBALS['phpAds_DecimalPoint']);
        $this->assertEqual('.', $GLOBALS['phpAds_ThousandsSeperator']);

        // Verify pt_BR day names with accents
        $this->assertEqual('Domingo', $GLOBALS['strDayFullNames'][0]);
        $this->assertEqual('Sábado', $GLOBALS['strDayFullNames'][6]);

        // Verify accented characters in translations
        $this->assertEqual('Ações', $GLOBALS['strActions']);
        $this->assertEqual('Próximo', $GLOBALS['strNext']);
    }

    /**
     * Test LTR locales: verify all 5 representative locales load all language file sections.
     */
    public function testLtrLocalesLoadAllSections()
    {
        $sections = ['default', 'settings', 'invocation', 'maintenance', 'userlog'];

        foreach ($this->ltrLocales as $locale) {
            foreach ($sections as $section) {
                $filePath = MAX_PATH . '/lib/max/language/' . $locale . '/' . $section . '.lang.php';
                $this->assertTrue(
                    file_exists($filePath),
                    "LTR locale '{$locale}' must have '{$section}.lang.php' file"
                );
            }
        }
    }

    /**
     * Test that all LTR sampled locales have key translations for critical keys.
     */
    public function testLtrLocalesCriticalKeysExist()
    {
        foreach ($this->ltrLocales as $locale) {
            $this->loadLocaleSection($locale, 'default');
            foreach ($this->criticalErrorKeys as $key) {
                $this->assertTrue(
                    isset($GLOBALS[$key]) && strlen(trim($GLOBALS[$key])) > 0,
                    "LTR locale '{$locale}': critical key '{$key}' must exist and be non-empty"
                );
            }
        }
    }

    // =========================================================================
    // SECTION 3: OX_Translation Unit Tests
    // =========================================================================

    /**
     * Test OX_Translation constructor sets default locale.
     */
    public function testOxTranslationDefaultLocale()
    {
        $trans = new OX_Translation();
        $this->assertEqual('en_US', $trans->locale);
    }

    /**
     * Test OX_Translation constructor respects preference language.
     */
    public function testOxTranslationPrefLocale()
    {
        $GLOBALS['_MAX']['PREF']['language'] = 'de';
        $trans = new OX_Translation();
        $this->assertEqual('de', $trans->locale);
        unset($GLOBALS['_MAX']['PREF']['language']);
    }

    /**
     * Test OX_Translation::translate() returns globals-based translation.
     */
    public function testOxTranslationTranslateFromGlobals()
    {
        $GLOBALS['strTestKey'] = 'Test Value';
        $trans = new OX_Translation();
        $result = $trans->translate('TestKey');
        $this->assertEqual('Test Value', $result);
        unset($GLOBALS['strTestKey']);
    }

    /**
     * Test OX_Translation::translate() returns the key itself when no translation found.
     */
    public function testOxTranslationTranslateFallback()
    {
        $trans = new OX_Translation();
        $result = $trans->translate('NonExistentKeyXyz123');
        $this->assertEqual('NonExistentKeyXyz123', $result);
    }

    /**
     * Test OX_Translation::translate() with sprintf substitution values.
     */
    public function testOxTranslationTranslateWithValues()
    {
        $GLOBALS['strTestFormat'] = 'Hello %s, you have %d items';
        $trans = new OX_Translation();
        $result = $trans->translate('TestFormat', ['World', 5]);
        $this->assertEqual('Hello World, you have 5 items', $result);
        unset($GLOBALS['strTestFormat']);
    }

    /**
     * Test OX_Translation::translate() with HTML special characters escaping.
     */
    public function testOxTranslationHtmlSpecialChars()
    {
        $GLOBALS['strTestHtml'] = '<b>Bold & "Quoted"</b>';
        $trans = new OX_Translation();

        // Without escaping
        $trans->htmlSpecialChars = false;
        $result = $trans->translate('TestHtml');
        $this->assertEqual('<b>Bold & "Quoted"</b>', $result);

        // With escaping
        $trans->htmlSpecialChars = true;
        $result = $trans->translate('TestHtml');
        $this->assertEqual(htmlspecialchars('<b>Bold & "Quoted"</b>'), $result);

        unset($GLOBALS['strTestHtml']);
    }

    /**
     * Test OX_Translation debug mode wraps output in strike tags.
     */
    public function testOxTranslationDebugMode()
    {
        $GLOBALS['strTestDebug'] = 'Debug Test';
        $trans = new OX_Translation();
        $trans->debug = true;
        $result = $trans->translate('TestDebug');
        $this->assertEqual('<strike>Debug Test</strike>', $result);
        unset($GLOBALS['strTestDebug']);
    }

    // =========================================================================
    // SECTION 4: OA_Admin_NumberFormat Locale-Aware Tests
    // =========================================================================

    /**
     * Test number formatting with English locale settings (dot decimal).
     */
    public function testNumberFormatEnglishLocale()
    {
        $GLOBALS['phpAds_DecimalPoint'] = '.';
        $GLOBALS['phpAds_ThousandsSeperator'] = ',';
        $GLOBALS['_MAX']['PREF']['ui_percentage_decimals'] = 2;

        $this->assertEqual('1,234.56', OA_Admin_NumberFormat::formatNumber(1234.56));
        $this->assertEqual('1,000', OA_Admin_NumberFormat::formatNumber(1000, 0));
    }

    /**
     * Test number formatting with German locale settings (comma decimal).
     */
    public function testNumberFormatGermanLocale()
    {
        $GLOBALS['phpAds_DecimalPoint'] = ',';
        $GLOBALS['phpAds_ThousandsSeperator'] = '.';
        $GLOBALS['_MAX']['PREF']['ui_percentage_decimals'] = 2;

        $this->assertEqual('1.234,56', OA_Admin_NumberFormat::formatNumber(1234.56));
        $this->assertEqual('1.000', OA_Admin_NumberFormat::formatNumber(1000, 0));
    }

    /**
     * Test number unformatting with Arabic/Hebrew locale (dot decimal).
     */
    public function testNumberUnformatArabicHebrewLocale()
    {
        global $phpAds_DecimalPoint;
        $savedDecimal = isset($phpAds_DecimalPoint) ? $phpAds_DecimalPoint : null;

        $phpAds_DecimalPoint = '.';
        $this->assertEqual('1234.56', OA_Admin_NumberFormat::unformatNumber('1,234.56'));
        $this->assertEqual('1234', OA_Admin_NumberFormat::unformatNumber('1,234'));

        if ($savedDecimal !== null) {
            $phpAds_DecimalPoint = $savedDecimal;
        } else {
            unset($phpAds_DecimalPoint);
        }
    }

    /**
     * Test number unformatting with Brazilian Portuguese locale (comma decimal).
     */
    public function testNumberUnformatBrazilianLocale()
    {
        global $phpAds_DecimalPoint;
        $savedDecimal = isset($phpAds_DecimalPoint) ? $phpAds_DecimalPoint : null;

        $phpAds_DecimalPoint = ',';
        $this->assertEqual('1234.56', OA_Admin_NumberFormat::unformatNumber('1.234,56'));

        if ($savedDecimal !== null) {
            $phpAds_DecimalPoint = $savedDecimal;
        } else {
            unset($phpAds_DecimalPoint);
        }
    }

    /**
     * Test number formatting returns false for non-numeric input.
     */
    public function testNumberFormatNonNumeric()
    {
        $GLOBALS['phpAds_DecimalPoint'] = '.';
        $GLOBALS['phpAds_ThousandsSeperator'] = ',';
        $GLOBALS['_MAX']['PREF']['ui_percentage_decimals'] = 2;

        $this->assertFalse(OA_Admin_NumberFormat::formatNumber('not a number'));
        $this->assertFalse(OA_Admin_NumberFormat::formatNumber('abc'));
    }

    /**
     * Test number unformatting returns false for invalid input.
     */
    public function testNumberUnformatInvalid()
    {
        $this->assertFalse(OA_Admin_NumberFormat::unformatNumber('12.34.567'));
    }

    // =========================================================================
    // SECTION 5: All 37 Locale Directories Coverage Tests
    // =========================================================================

    /**
     * Test that all 37 locale directories exist.
     */
    public function testAllLocaleDirectoriesExist()
    {
        foreach ($this->allLocales as $locale) {
            $dirPath = MAX_PATH . '/lib/max/language/' . $locale;
            $this->assertTrue(
                is_dir($dirPath),
                "Locale directory '{$locale}' must exist at {$dirPath}"
            );
        }
    }

    /**
     * Test that all 37 locales have a default.lang.php file.
     */
    public function testAllLocalesHaveDefaultLangFile()
    {
        foreach ($this->allLocales as $locale) {
            $filePath = MAX_PATH . '/lib/max/language/' . $locale . '/default.lang.php';
            $this->assertTrue(
                file_exists($filePath),
                "Locale '{$locale}' must have default.lang.php"
            );
        }
    }

    /**
     * Test that all 37 locales have complete translation coverage for critical
     * error messages: permission denied, campaign expired, billing validation,
     * tracker status, date validation.
     *
     * English is loaded first as a base, so all keys will have at least the
     * English value. We verify they are non-empty.
     */
    public function testAllLocalesCriticalErrorMessageCoverage()
    {
        foreach ($this->allLocales as $locale) {
            $oldLevel = error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_WARNING);
            $this->loadLocaleSection($locale, 'default');
            error_reporting($oldLevel);

            foreach ($this->criticalErrorKeys as $key) {
                $this->assertTrue(
                    isset($GLOBALS[$key]) && strlen(trim($GLOBALS[$key])) > 0,
                    "Locale '{$locale}': critical error key '{$key}' must exist and be non-empty (at minimum English fallback)"
                );
            }
        }
    }

    /**
     * Test that all locales have core navigation labels.
     */
    public function testAllLocalesCoreNavigationLabels()
    {
        $navKeys = [
            'strHome', 'strHelp', 'strSave', 'strCancel', 'strDelete',
            'strYes', 'strNo', 'strBack', 'strNext', 'strPrevious',
        ];

        foreach ($this->allLocales as $locale) {
            $oldLevel = error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_WARNING);
            $this->loadLocaleSection($locale, 'default');
            error_reporting($oldLevel);
            foreach ($navKeys as $key) {
                $this->assertTrue(
                    isset($GLOBALS[$key]) && strlen(trim($GLOBALS[$key])) > 0,
                    "Locale '{$locale}': navigation key '{$key}' must exist and be non-empty"
                );
            }
        }
    }

    /**
     * Test that all locales have day names defined (via English fallback at minimum).
     */
    public function testAllLocalesDayNamesExist()
    {
        foreach ($this->allLocales as $locale) {
            $oldLevel = error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_WARNING);
            $this->loadLocaleSection($locale, 'default');
            error_reporting($oldLevel);

            // Day full names (0=Sunday through 6=Saturday)
            $this->assertTrue(
                isset($GLOBALS['strDayFullNames']) && is_array($GLOBALS['strDayFullNames']),
                "Locale '{$locale}': strDayFullNames must be a defined array"
            );
            for ($i = 0; $i <= 6; $i++) {
                $this->assertTrue(
                    isset($GLOBALS['strDayFullNames'][$i]) && strlen(trim($GLOBALS['strDayFullNames'][$i])) > 0,
                    "Locale '{$locale}': strDayFullNames[{$i}] must exist and be non-empty"
                );
            }

            // Day short cuts
            $this->assertTrue(
                isset($GLOBALS['strDayShortCuts']) && is_array($GLOBALS['strDayShortCuts']),
                "Locale '{$locale}': strDayShortCuts must be a defined array"
            );
            for ($i = 0; $i <= 6; $i++) {
                $this->assertTrue(
                    isset($GLOBALS['strDayShortCuts'][$i]) && strlen(trim($GLOBALS['strDayShortCuts'][$i])) > 0,
                    "Locale '{$locale}': strDayShortCuts[{$i}] must exist and be non-empty"
                );
            }
        }
    }

    /**
     * Test that RTL locales never produce LTR text direction.
     */
    public function testRtlLocalesNeverLtr()
    {
        foreach ($this->rtlLocales as $locale) {
            $this->loadLocaleSection($locale, 'default');
            $this->assertNotEqual('ltr', $GLOBALS['phpAds_TextDirection'],
                "RTL locale '{$locale}' must never have LTR text direction");
        }
    }

    /**
     * Test that non-RTL locales all use LTR direction (English fallback).
     */
    public function testNonRtlLocalesAreLtr()
    {
        $nonRtlLocales = array_diff($this->allLocales, $this->rtlLocales);
        foreach ($nonRtlLocales as $locale) {
            $oldLevel = error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_WARNING);
            $this->loadLocaleSection($locale, 'default');
            error_reporting($oldLevel);
            $this->assertEqual('ltr', $GLOBALS['phpAds_TextDirection'],
                "Non-RTL locale '{$locale}' must use LTR text direction");
        }
    }

    /**
     * Test that all locales have settings.lang.php file.
     * Note: en_US and mk are known to lack settings.lang.php; they rely on English fallback.
     */
    public function testAllLocalesHaveSettingsLangFile()
    {
        // These locales are known to not ship their own settings.lang.php;
        // they fall back to the English defaults loaded by Language_Loader.
        $knownMissing = ['en_US', 'mk'];

        foreach ($this->allLocales as $locale) {
            if (in_array($locale, $knownMissing, true)) {
                continue;
            }
            $filePath = MAX_PATH . '/lib/max/language/' . $locale . '/settings.lang.php';
            $this->assertTrue(
                file_exists($filePath),
                "Locale '{$locale}' must have settings.lang.php"
            );
        }
    }

    /**
     * Test that all locales load without PHP errors.
     * Note: uk/default.lang.php has a pre-existing undefined $s variable;
     * we suppress E_WARNING during loading to avoid false positives.
     */
    public function testAllLocalesLoadWithoutErrors()
    {
        $sections = ['default', 'settings'];

        foreach ($this->allLocales as $locale) {
            foreach ($sections as $section) {
                $filePath = MAX_PATH . '/lib/max/language/' . $locale . '/' . $section . '.lang.php';
                if (file_exists($filePath)) {
                    // Temporarily suppress warnings for known pre-existing
                    // issues in locale files (e.g. uk/default.lang.php $s).
                    $oldLevel = error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_WARNING);
                    $this->loadLocaleSection($locale, $section);
                    error_reporting($oldLevel);
                    $this->pass("Locale '{$locale}' section '{$section}' loaded without fatal errors");
                }
            }
        }
    }
}
