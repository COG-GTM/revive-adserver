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

require_once MAX_PATH . '/lib/max/language/Loader.php';
require_once MAX_PATH . '/lib/OX/Translation.php';
require_once MAX_PATH . '/lib/OA/Admin/NumberFormat.php';

/**
 * Localization Combination Matrix Tests (Section 4L)
 *
 * Covers:
 * - 84 exhaustive RTL combos: 3 RTL locales (ar, he, fa) x 7 UI pages x 4 content types
 * - Sampled LTR tests: 5 representative locales (en, de, ja, ko, pt_BR) with key pages
 *
 * @package    OpenXDll
 * @subpackage TestSuite
 */
class OA_Dll_LocalizationCombinatorialTest extends UnitTestCase
{
    /**
     * RTL locales to exhaustively test.
     */
    private $rtlLocales = ['ar', 'he', 'fa'];

    /**
     * Sampled LTR locales for representative testing.
     */
    private $ltrLocales = ['en', 'de', 'ja', 'ko', 'pt_BR'];

    /**
     * UI pages mapped to the language file section and the string keys
     * that are expected to exist for each page context.
     *
     * Keys: page name
     * Values: ['section' => lang file section, 'keys' => string key suffixes]
     */
    private $uiPages = [
        'Dashboard' => [
            'section' => 'default',
            'keys' => [
                'strHome',
                'strDashboardCantBeDisplayed',
                'strDashboardSystemMessage',
                'strOverview',
                'strShortcuts',
            ],
        ],
        'Campaign edit' => [
            'section' => 'default',
            'keys' => [
                'strCampaign',
                'strCampaigns',
                'strCampaignProperties',
                'strSaveChanges',
                'strName',
            ],
        ],
        'Banner edit' => [
            'section' => 'default',
            'keys' => [
                'strBanners',
                'strName',
                'strWidth',
                'strHeight',
                'strDescription',
            ],
        ],
        'Zone edit' => [
            'section' => 'default',
            'keys' => [
                'strZone',
                'strZones',
                'strZoneProperties',
                'strDelete',
                'strSave',
            ],
        ],
        'Statistics' => [
            'section' => 'default',
            'keys' => [
                'strImpressions',
                'strClicks',
                'strTotal',
                'strDate',
                'strAverage',
            ],
        ],
        'Settings' => [
            'section' => 'settings',
            'keys' => [
                'strInstall',
                'strDatabaseSettings',
                'strAdminAccount',
                'strWarning',
                'strBtnContinue',
            ],
        ],
        'User management' => [
            'section' => 'default',
            'keys' => [
                'strUserAccess',
                'strPermissions',
                'strUsername',
                'strPassword',
                'strLogin',
            ],
        ],
    ];

    /**
     * Content type definitions mapping content type names to the global
     * variable keys that should be checked for each type.
     */
    private $contentTypes = [
        'Labels' => [
            'section' => 'default',
            'keys' => [
                'strHome',
                'strHelp',
                'strName',
                'strSave',
                'strCancel',
                'strDelete',
                'strYes',
                'strNo',
            ],
        ],
        'Error messages' => [
            'section' => 'default',
            'keys' => [
                'strAccessDenied',
                'strFieldContainsErrors',
                'strUsernameOrPasswordWrong',
                'strPasswordWrong',
                'strInvalidPassword',
            ],
        ],
        'Date formats' => [
            'section' => 'default',
            'keys' => [
                'strDate',
                'strDay',
                'strDays',
                'strWeek',
                'strSingleMonth',
                'strMonths',
            ],
        ],
        'Number formats' => [
            'section' => 'default',
            'keys' => [
                'phpAds_DecimalPoint',
            ],
        ],
    ];

    /**
     * Saved globals to restore after each test.
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
            'excel_integer_formatting', 'excel_decimal_formatting',
            'strHome', 'strHelp', 'strInstall',
            '_MAX',
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
        $this->savedGlobals = [];
    }

    /**
     * Helper: Load a locale's language file section and return the globals
     * that were set. Uses a fresh include to isolate side-effects.
     *
     * @param string $locale  Locale code (e.g. 'ar', 'en')
     * @param string $section Language file section (e.g. 'default', 'settings')
     * @return bool True if the file was loaded successfully
     */
    private function loadLocaleSection($locale, $section)
    {
        $PRODUCT_NAME = defined('PRODUCT_NAME') ? PRODUCT_NAME : 'Revive Adserver';
        $PRODUCT_URL = defined('PRODUCT_URL') ? PRODUCT_URL : '';
        $PRODUCT_DOCSURL = defined('PRODUCT_DOCSURL') ? PRODUCT_DOCSURL : '';

        $filePath = MAX_PATH . '/lib/max/language/' . $locale . '/' . $section . '.lang.php';
        if (!file_exists($filePath)) {
            return false;
        }
        include $filePath;
        return true;
    }

    /**
     * Helper: Check that a global string key is set and non-empty.
     *
     * @param string $key     The GLOBALS key to check
     * @param string $locale  Locale for the error message
     * @param string $context Context description for the error message
     */
    private function assertGlobalStringExists($key, $locale, $context)
    {
        $this->assertTrue(
            isset($GLOBALS[$key]) && (is_string($GLOBALS[$key]) ? strlen(trim($GLOBALS[$key])) > 0 : !empty($GLOBALS[$key])),
            "[$locale] $context: \$GLOBALS['$key'] should be set and non-empty"
        );
    }

    // ========================================================================
    // SECTION 1: RTL EXHAUSTIVE MATRIX (84 combos)
    // 3 RTL locales x 7 UI pages x 4 content types = 84
    // ========================================================================

    /**
     * Test RTL text direction properties for all three RTL locales.
     * This verifies the fundamental RTL configuration is correct.
     */
    public function testRtlTextDirectionProperties()
    {
        foreach ($this->rtlLocales as $locale) {
            $this->assertTrue(
                $this->loadLocaleSection($locale, 'default'),
                "[$locale] default.lang.php should exist and load"
            );
            $this->assertEqual(
                $GLOBALS['phpAds_TextDirection'],
                'rtl',
                "[$locale] phpAds_TextDirection should be 'rtl'"
            );
            $this->assertEqual(
                $GLOBALS['phpAds_TextAlignRight'],
                'left',
                "[$locale] phpAds_TextAlignRight should be 'left' (swapped for RTL)"
            );
            $this->assertEqual(
                $GLOBALS['phpAds_TextAlignLeft'],
                'right',
                "[$locale] phpAds_TextAlignLeft should be 'right' (swapped for RTL)"
            );
        }
    }

    // --- Arabic (ar) RTL: 7 pages x 4 content types = 28 combos ---

    public function testRtl_ar_Dashboard_Labels()
    {
        $this->_runRtlCombo('ar', 'Dashboard', 'Labels');
    }

    public function testRtl_ar_Dashboard_ErrorMessages()
    {
        $this->_runRtlCombo('ar', 'Dashboard', 'Error messages');
    }

    public function testRtl_ar_Dashboard_DateFormats()
    {
        $this->_runRtlCombo('ar', 'Dashboard', 'Date formats');
    }

    public function testRtl_ar_Dashboard_NumberFormats()
    {
        $this->_runRtlCombo('ar', 'Dashboard', 'Number formats');
    }

    public function testRtl_ar_CampaignEdit_Labels()
    {
        $this->_runRtlCombo('ar', 'Campaign edit', 'Labels');
    }

    public function testRtl_ar_CampaignEdit_ErrorMessages()
    {
        $this->_runRtlCombo('ar', 'Campaign edit', 'Error messages');
    }

    public function testRtl_ar_CampaignEdit_DateFormats()
    {
        $this->_runRtlCombo('ar', 'Campaign edit', 'Date formats');
    }

    public function testRtl_ar_CampaignEdit_NumberFormats()
    {
        $this->_runRtlCombo('ar', 'Campaign edit', 'Number formats');
    }

    public function testRtl_ar_BannerEdit_Labels()
    {
        $this->_runRtlCombo('ar', 'Banner edit', 'Labels');
    }

    public function testRtl_ar_BannerEdit_ErrorMessages()
    {
        $this->_runRtlCombo('ar', 'Banner edit', 'Error messages');
    }

    public function testRtl_ar_BannerEdit_DateFormats()
    {
        $this->_runRtlCombo('ar', 'Banner edit', 'Date formats');
    }

    public function testRtl_ar_BannerEdit_NumberFormats()
    {
        $this->_runRtlCombo('ar', 'Banner edit', 'Number formats');
    }

    public function testRtl_ar_ZoneEdit_Labels()
    {
        $this->_runRtlCombo('ar', 'Zone edit', 'Labels');
    }

    public function testRtl_ar_ZoneEdit_ErrorMessages()
    {
        $this->_runRtlCombo('ar', 'Zone edit', 'Error messages');
    }

    public function testRtl_ar_ZoneEdit_DateFormats()
    {
        $this->_runRtlCombo('ar', 'Zone edit', 'Date formats');
    }

    public function testRtl_ar_ZoneEdit_NumberFormats()
    {
        $this->_runRtlCombo('ar', 'Zone edit', 'Number formats');
    }

    public function testRtl_ar_Statistics_Labels()
    {
        $this->_runRtlCombo('ar', 'Statistics', 'Labels');
    }

    public function testRtl_ar_Statistics_ErrorMessages()
    {
        $this->_runRtlCombo('ar', 'Statistics', 'Error messages');
    }

    public function testRtl_ar_Statistics_DateFormats()
    {
        $this->_runRtlCombo('ar', 'Statistics', 'Date formats');
    }

    public function testRtl_ar_Statistics_NumberFormats()
    {
        $this->_runRtlCombo('ar', 'Statistics', 'Number formats');
    }

    public function testRtl_ar_Settings_Labels()
    {
        $this->_runRtlCombo('ar', 'Settings', 'Labels');
    }

    public function testRtl_ar_Settings_ErrorMessages()
    {
        $this->_runRtlCombo('ar', 'Settings', 'Error messages');
    }

    public function testRtl_ar_Settings_DateFormats()
    {
        $this->_runRtlCombo('ar', 'Settings', 'Date formats');
    }

    public function testRtl_ar_Settings_NumberFormats()
    {
        $this->_runRtlCombo('ar', 'Settings', 'Number formats');
    }

    public function testRtl_ar_UserManagement_Labels()
    {
        $this->_runRtlCombo('ar', 'User management', 'Labels');
    }

    public function testRtl_ar_UserManagement_ErrorMessages()
    {
        $this->_runRtlCombo('ar', 'User management', 'Error messages');
    }

    public function testRtl_ar_UserManagement_DateFormats()
    {
        $this->_runRtlCombo('ar', 'User management', 'Date formats');
    }

    public function testRtl_ar_UserManagement_NumberFormats()
    {
        $this->_runRtlCombo('ar', 'User management', 'Number formats');
    }

    // --- Hebrew (he) RTL: 7 pages x 4 content types = 28 combos ---

    public function testRtl_he_Dashboard_Labels()
    {
        $this->_runRtlCombo('he', 'Dashboard', 'Labels');
    }

    public function testRtl_he_Dashboard_ErrorMessages()
    {
        $this->_runRtlCombo('he', 'Dashboard', 'Error messages');
    }

    public function testRtl_he_Dashboard_DateFormats()
    {
        $this->_runRtlCombo('he', 'Dashboard', 'Date formats');
    }

    public function testRtl_he_Dashboard_NumberFormats()
    {
        $this->_runRtlCombo('he', 'Dashboard', 'Number formats');
    }

    public function testRtl_he_CampaignEdit_Labels()
    {
        $this->_runRtlCombo('he', 'Campaign edit', 'Labels');
    }

    public function testRtl_he_CampaignEdit_ErrorMessages()
    {
        $this->_runRtlCombo('he', 'Campaign edit', 'Error messages');
    }

    public function testRtl_he_CampaignEdit_DateFormats()
    {
        $this->_runRtlCombo('he', 'Campaign edit', 'Date formats');
    }

    public function testRtl_he_CampaignEdit_NumberFormats()
    {
        $this->_runRtlCombo('he', 'Campaign edit', 'Number formats');
    }

    public function testRtl_he_BannerEdit_Labels()
    {
        $this->_runRtlCombo('he', 'Banner edit', 'Labels');
    }

    public function testRtl_he_BannerEdit_ErrorMessages()
    {
        $this->_runRtlCombo('he', 'Banner edit', 'Error messages');
    }

    public function testRtl_he_BannerEdit_DateFormats()
    {
        $this->_runRtlCombo('he', 'Banner edit', 'Date formats');
    }

    public function testRtl_he_BannerEdit_NumberFormats()
    {
        $this->_runRtlCombo('he', 'Banner edit', 'Number formats');
    }

    public function testRtl_he_ZoneEdit_Labels()
    {
        $this->_runRtlCombo('he', 'Zone edit', 'Labels');
    }

    public function testRtl_he_ZoneEdit_ErrorMessages()
    {
        $this->_runRtlCombo('he', 'Zone edit', 'Error messages');
    }

    public function testRtl_he_ZoneEdit_DateFormats()
    {
        $this->_runRtlCombo('he', 'Zone edit', 'Date formats');
    }

    public function testRtl_he_ZoneEdit_NumberFormats()
    {
        $this->_runRtlCombo('he', 'Zone edit', 'Number formats');
    }

    public function testRtl_he_Statistics_Labels()
    {
        $this->_runRtlCombo('he', 'Statistics', 'Labels');
    }

    public function testRtl_he_Statistics_ErrorMessages()
    {
        $this->_runRtlCombo('he', 'Statistics', 'Error messages');
    }

    public function testRtl_he_Statistics_DateFormats()
    {
        $this->_runRtlCombo('he', 'Statistics', 'Date formats');
    }

    public function testRtl_he_Statistics_NumberFormats()
    {
        $this->_runRtlCombo('he', 'Statistics', 'Number formats');
    }

    public function testRtl_he_Settings_Labels()
    {
        $this->_runRtlCombo('he', 'Settings', 'Labels');
    }

    public function testRtl_he_Settings_ErrorMessages()
    {
        $this->_runRtlCombo('he', 'Settings', 'Error messages');
    }

    public function testRtl_he_Settings_DateFormats()
    {
        $this->_runRtlCombo('he', 'Settings', 'Date formats');
    }

    public function testRtl_he_Settings_NumberFormats()
    {
        $this->_runRtlCombo('he', 'Settings', 'Number formats');
    }

    public function testRtl_he_UserManagement_Labels()
    {
        $this->_runRtlCombo('he', 'User management', 'Labels');
    }

    public function testRtl_he_UserManagement_ErrorMessages()
    {
        $this->_runRtlCombo('he', 'User management', 'Error messages');
    }

    public function testRtl_he_UserManagement_DateFormats()
    {
        $this->_runRtlCombo('he', 'User management', 'Date formats');
    }

    public function testRtl_he_UserManagement_NumberFormats()
    {
        $this->_runRtlCombo('he', 'User management', 'Number formats');
    }

    // --- Persian/Farsi (fa) RTL: 7 pages x 4 content types = 28 combos ---

    public function testRtl_fa_Dashboard_Labels()
    {
        $this->_runRtlCombo('fa', 'Dashboard', 'Labels');
    }

    public function testRtl_fa_Dashboard_ErrorMessages()
    {
        $this->_runRtlCombo('fa', 'Dashboard', 'Error messages');
    }

    public function testRtl_fa_Dashboard_DateFormats()
    {
        $this->_runRtlCombo('fa', 'Dashboard', 'Date formats');
    }

    public function testRtl_fa_Dashboard_NumberFormats()
    {
        $this->_runRtlCombo('fa', 'Dashboard', 'Number formats');
    }

    public function testRtl_fa_CampaignEdit_Labels()
    {
        $this->_runRtlCombo('fa', 'Campaign edit', 'Labels');
    }

    public function testRtl_fa_CampaignEdit_ErrorMessages()
    {
        $this->_runRtlCombo('fa', 'Campaign edit', 'Error messages');
    }

    public function testRtl_fa_CampaignEdit_DateFormats()
    {
        $this->_runRtlCombo('fa', 'Campaign edit', 'Date formats');
    }

    public function testRtl_fa_CampaignEdit_NumberFormats()
    {
        $this->_runRtlCombo('fa', 'Campaign edit', 'Number formats');
    }

    public function testRtl_fa_BannerEdit_Labels()
    {
        $this->_runRtlCombo('fa', 'Banner edit', 'Labels');
    }

    public function testRtl_fa_BannerEdit_ErrorMessages()
    {
        $this->_runRtlCombo('fa', 'Banner edit', 'Error messages');
    }

    public function testRtl_fa_BannerEdit_DateFormats()
    {
        $this->_runRtlCombo('fa', 'Banner edit', 'Date formats');
    }

    public function testRtl_fa_BannerEdit_NumberFormats()
    {
        $this->_runRtlCombo('fa', 'Banner edit', 'Number formats');
    }

    public function testRtl_fa_ZoneEdit_Labels()
    {
        $this->_runRtlCombo('fa', 'Zone edit', 'Labels');
    }

    public function testRtl_fa_ZoneEdit_ErrorMessages()
    {
        $this->_runRtlCombo('fa', 'Zone edit', 'Error messages');
    }

    public function testRtl_fa_ZoneEdit_DateFormats()
    {
        $this->_runRtlCombo('fa', 'Zone edit', 'Date formats');
    }

    public function testRtl_fa_ZoneEdit_NumberFormats()
    {
        $this->_runRtlCombo('fa', 'Zone edit', 'Number formats');
    }

    public function testRtl_fa_Statistics_Labels()
    {
        $this->_runRtlCombo('fa', 'Statistics', 'Labels');
    }

    public function testRtl_fa_Statistics_ErrorMessages()
    {
        $this->_runRtlCombo('fa', 'Statistics', 'Error messages');
    }

    public function testRtl_fa_Statistics_DateFormats()
    {
        $this->_runRtlCombo('fa', 'Statistics', 'Date formats');
    }

    public function testRtl_fa_Statistics_NumberFormats()
    {
        $this->_runRtlCombo('fa', 'Statistics', 'Number formats');
    }

    public function testRtl_fa_Settings_Labels()
    {
        $this->_runRtlCombo('fa', 'Settings', 'Labels');
    }

    public function testRtl_fa_Settings_ErrorMessages()
    {
        $this->_runRtlCombo('fa', 'Settings', 'Error messages');
    }

    public function testRtl_fa_Settings_DateFormats()
    {
        $this->_runRtlCombo('fa', 'Settings', 'Date formats');
    }

    public function testRtl_fa_Settings_NumberFormats()
    {
        $this->_runRtlCombo('fa', 'Settings', 'Number formats');
    }

    public function testRtl_fa_UserManagement_Labels()
    {
        $this->_runRtlCombo('fa', 'User management', 'Labels');
    }

    public function testRtl_fa_UserManagement_ErrorMessages()
    {
        $this->_runRtlCombo('fa', 'User management', 'Error messages');
    }

    public function testRtl_fa_UserManagement_DateFormats()
    {
        $this->_runRtlCombo('fa', 'User management', 'Date formats');
    }

    public function testRtl_fa_UserManagement_NumberFormats()
    {
        $this->_runRtlCombo('fa', 'User management', 'Number formats');
    }

    // ========================================================================
    // SECTION 2: SAMPLED LTR LOCALE TESTS
    // 5 representative locales with key pages
    // ========================================================================

    /**
     * Test LTR text direction for all sampled LTR locales.
     */
    public function testLtrTextDirectionProperties()
    {
        foreach ($this->ltrLocales as $locale) {
            $this->assertTrue(
                $this->loadLocaleSection($locale, 'default'),
                "[$locale] default.lang.php should exist and load"
            );
            // LTR locales either explicitly set 'ltr' or inherit from English (loaded first)
            // English always loads first via Language_Loader, so we verify the direction
            // is not 'rtl'
            if (isset($GLOBALS['phpAds_TextDirection'])) {
                $this->assertNotEqual(
                    $GLOBALS['phpAds_TextDirection'],
                    'rtl',
                    "[$locale] phpAds_TextDirection should not be 'rtl'"
                );
            }
        }
    }

    // --- English (en): Baseline / reference ---

    public function testLtr_en_Dashboard_Labels()
    {
        $this->_runLtrCombo('en', 'Dashboard', 'Labels');
    }

    public function testLtr_en_CampaignEdit_Labels()
    {
        $this->_runLtrCombo('en', 'Campaign edit', 'Labels');
    }

    public function testLtr_en_BannerEdit_Labels()
    {
        $this->_runLtrCombo('en', 'Banner edit', 'Labels');
    }

    public function testLtr_en_Statistics_Labels()
    {
        $this->_runLtrCombo('en', 'Statistics', 'Labels');
    }

    public function testLtr_en_Settings_Labels()
    {
        $this->_runLtrCombo('en', 'Settings', 'Labels');
    }

    public function testLtr_en_UserManagement_ErrorMessages()
    {
        $this->_runLtrCombo('en', 'User management', 'Error messages');
    }

    public function testLtr_en_DateFormats()
    {
        $this->_runLtrCombo('en', 'Dashboard', 'Date formats');
    }

    public function testLtr_en_NumberFormats()
    {
        $this->_runLtrCombo('en', 'Dashboard', 'Number formats');
    }

    // --- German (de): Latin script, long compound words ---

    public function testLtr_de_Dashboard_Labels()
    {
        $this->_runLtrCombo('de', 'Dashboard', 'Labels');
    }

    public function testLtr_de_CampaignEdit_Labels()
    {
        $this->_runLtrCombo('de', 'Campaign edit', 'Labels');
    }

    public function testLtr_de_Settings_Labels()
    {
        $this->_runLtrCombo('de', 'Settings', 'Labels');
    }

    public function testLtr_de_UserManagement_ErrorMessages()
    {
        $this->_runLtrCombo('de', 'User management', 'Error messages');
    }

    public function testLtr_de_DateFormats()
    {
        $this->_runLtrCombo('de', 'Dashboard', 'Date formats');
    }

    public function testLtr_de_NumberFormats()
    {
        $this->_runLtrCombo('de', 'Dashboard', 'Number formats');
    }

    // --- Japanese (ja): CJK double-width characters ---

    public function testLtr_ja_Dashboard_Labels()
    {
        $this->_runLtrCombo('ja', 'Dashboard', 'Labels');
    }

    public function testLtr_ja_CampaignEdit_Labels()
    {
        $this->_runLtrCombo('ja', 'Campaign edit', 'Labels');
    }

    public function testLtr_ja_Statistics_Labels()
    {
        $this->_runLtrCombo('ja', 'Statistics', 'Labels');
    }

    public function testLtr_ja_Settings_Labels()
    {
        $this->_runLtrCombo('ja', 'Settings', 'Labels');
    }

    public function testLtr_ja_DateFormats()
    {
        $this->_runLtrCombo('ja', 'Dashboard', 'Date formats');
    }

    // --- Korean (ko): CJK alternate script ---

    public function testLtr_ko_Dashboard_Labels()
    {
        $this->_runLtrCombo('ko', 'Dashboard', 'Labels');
    }

    public function testLtr_ko_CampaignEdit_Labels()
    {
        $this->_runLtrCombo('ko', 'Campaign edit', 'Labels');
    }

    public function testLtr_ko_Statistics_Labels()
    {
        $this->_runLtrCombo('ko', 'Statistics', 'Labels');
    }

    public function testLtr_ko_Settings_Labels()
    {
        $this->_runLtrCombo('ko', 'Settings', 'Labels');
    }

    public function testLtr_ko_DateFormats()
    {
        $this->_runLtrCombo('ko', 'Dashboard', 'Date formats');
    }

    // --- Brazilian Portuguese (pt_BR): Latin with accents, date format differences ---

    public function testLtr_ptBR_Dashboard_Labels()
    {
        $this->_runLtrCombo('pt_BR', 'Dashboard', 'Labels');
    }

    public function testLtr_ptBR_CampaignEdit_Labels()
    {
        $this->_runLtrCombo('pt_BR', 'Campaign edit', 'Labels');
    }

    public function testLtr_ptBR_Statistics_Labels()
    {
        $this->_runLtrCombo('pt_BR', 'Statistics', 'Labels');
    }

    public function testLtr_ptBR_Settings_Labels()
    {
        $this->_runLtrCombo('pt_BR', 'Settings', 'Labels');
    }

    public function testLtr_ptBR_UserManagement_ErrorMessages()
    {
        $this->_runLtrCombo('pt_BR', 'User management', 'Error messages');
    }

    public function testLtr_ptBR_DateFormats()
    {
        $this->_runLtrCombo('pt_BR', 'Dashboard', 'Date formats');
    }

    // ========================================================================
    // SECTION 3: TRANSLATION FILE EXISTENCE & COMPLETENESS
    // ========================================================================

    /**
     * Verify that all RTL locale directories contain the expected language files.
     */
    public function testRtlLocaleFileExistence()
    {
        $expectedSections = ['default', 'settings', 'invocation', 'installer', 'maintenance', 'userlog'];

        foreach ($this->rtlLocales as $locale) {
            $localeDir = MAX_PATH . '/lib/max/language/' . $locale;
            $this->assertTrue(
                is_dir($localeDir),
                "[$locale] Language directory should exist: $localeDir"
            );

            foreach ($expectedSections as $section) {
                $filePath = $localeDir . '/' . $section . '.lang.php';
                $this->assertTrue(
                    file_exists($filePath),
                    "[$locale] Language file should exist: $section.lang.php"
                );
            }
        }
    }

    /**
     * Verify that all LTR sample locale directories contain the expected language files.
     */
    public function testLtrLocaleFileExistence()
    {
        $expectedSections = ['default', 'settings', 'invocation', 'installer', 'maintenance', 'userlog'];

        foreach ($this->ltrLocales as $locale) {
            $localeDir = MAX_PATH . '/lib/max/language/' . $locale;
            $this->assertTrue(
                is_dir($localeDir),
                "[$locale] Language directory should exist: $localeDir"
            );

            foreach ($expectedSections as $section) {
                $filePath = $localeDir . '/' . $section . '.lang.php';
                $this->assertTrue(
                    file_exists($filePath),
                    "[$locale] Language file should exist: $section.lang.php"
                );
            }
        }
    }

    // ========================================================================
    // SECTION 4: OX_Translation INTEGRATION TESTS
    // ========================================================================

    /**
     * Test that OX_Translation correctly loads translations for RTL locales.
     */
    public function testOxTranslationLoadsRtlLocales()
    {
        foreach ($this->rtlLocales as $locale) {
            // Set up the global preference for the locale
            $GLOBALS['_MAX']['PREF']['language'] = $locale;

            // Use Language_Loader to load the locale (mimics app behavior)
            Language_Loader::load('default', $locale);

            // Verify that a known string was loaded and is non-empty
            $this->assertTrue(
                isset($GLOBALS['strHome']) && strlen(trim($GLOBALS['strHome'])) > 0,
                "[$locale] OX_Translation: strHome should be loaded and non-empty"
            );
            $this->assertTrue(
                isset($GLOBALS['strDelete']) && strlen(trim($GLOBALS['strDelete'])) > 0,
                "[$locale] OX_Translation: strDelete should be loaded and non-empty"
            );
        }
    }

    /**
     * Test that OX_Translation correctly loads translations for LTR locales.
     */
    public function testOxTranslationLoadsLtrLocales()
    {
        foreach ($this->ltrLocales as $locale) {
            $GLOBALS['_MAX']['PREF']['language'] = $locale;

            Language_Loader::load('default', $locale);

            $this->assertTrue(
                isset($GLOBALS['strHome']) && strlen(trim($GLOBALS['strHome'])) > 0,
                "[$locale] OX_Translation: strHome should be loaded and non-empty"
            );
            $this->assertTrue(
                isset($GLOBALS['strDelete']) && strlen(trim($GLOBALS['strDelete'])) > 0,
                "[$locale] OX_Translation: strDelete should be loaded and non-empty"
            );
        }
    }

    /**
     * Test that Language_Loader correctly falls back from RTL locale to English
     * for the settings section.
     */
    public function testLanguageLoaderSettingsFallbackRtl()
    {
        foreach ($this->rtlLocales as $locale) {
            $GLOBALS['_MAX']['PREF']['language'] = $locale;

            Language_Loader::load('settings', $locale);

            // Settings section should have strInstall from either the locale or English fallback
            $this->assertTrue(
                isset($GLOBALS['strInstall']) && strlen(trim($GLOBALS['strInstall'])) > 0,
                "[$locale] Language_Loader settings: strInstall should be available"
            );
        }
    }

    // ========================================================================
    // SECTION 5: NUMBER FORMAT LOCALE-SPECIFIC TESTS
    // ========================================================================

    /**
     * Test that RTL locales have proper decimal point configuration.
     */
    public function testRtlLocaleDecimalPointConfig()
    {
        foreach ($this->rtlLocales as $locale) {
            $this->loadLocaleSection($locale, 'default');

            $this->assertTrue(
                isset($GLOBALS['phpAds_DecimalPoint']),
                "[$locale] phpAds_DecimalPoint should be defined"
            );
            $this->assertTrue(
                in_array($GLOBALS['phpAds_DecimalPoint'], ['.', ','], true),
                "[$locale] phpAds_DecimalPoint should be '.' or ','"
            );
        }
    }

    /**
     * Test that LTR locales have proper number formatting configuration.
     */
    public function testLtrLocaleNumberFormatConfig()
    {
        foreach ($this->ltrLocales as $locale) {
            $this->loadLocaleSection($locale, 'default');

            $this->assertTrue(
                isset($GLOBALS['phpAds_DecimalPoint']),
                "[$locale] phpAds_DecimalPoint should be defined"
            );
            $this->assertTrue(
                in_array($GLOBALS['phpAds_DecimalPoint'], ['.', ','], true),
                "[$locale] phpAds_DecimalPoint should be '.' or ','"
            );
        }
    }

    /**
     * Test that German locale uses comma as decimal separator (locale-specific).
     */
    public function testGermanNumberFormatConvention()
    {
        $this->loadLocaleSection('de', 'default');

        $this->assertEqual(
            $GLOBALS['phpAds_DecimalPoint'],
            ',',
            "[de] German locale should use comma as decimal separator"
        );
        $this->assertEqual(
            $GLOBALS['phpAds_ThousandsSeperator'],
            '.',
            "[de] German locale should use period as thousands separator"
        );
    }

    /**
     * Test OA_Admin_NumberFormat::formatNumber with locale-specific separators.
     */
    public function testNumberFormatWithLocaleSettings()
    {
        // Test with English-style formatting (dot decimal, comma thousands)
        $GLOBALS['_MAX']['PREF']['ui_percentage_decimals'] = 2;
        $GLOBALS['phpAds_DecimalPoint'] = '.';
        $GLOBALS['phpAds_ThousandsSeperator'] = ',';

        $result = OA_Admin_NumberFormat::formatNumber(1234567.89, 2, '.', ',');
        $this->assertEqual($result, '1,234,567.89', "English-style number formatting");

        // Test with German-style formatting (comma decimal, dot thousands)
        $result = OA_Admin_NumberFormat::formatNumber(1234567.89, 2, ',', '.');
        $this->assertEqual($result, '1.234.567,89', "German-style number formatting");

        // Test with Arabic-style (same as English for decimal point)
        $result = OA_Admin_NumberFormat::formatNumber(1234567.89, 2, '.', ',');
        $this->assertEqual($result, '1,234,567.89', "Arabic-style number formatting");
    }

    /**
     * Test OA_Admin_NumberFormat::unformatNumber with various locale inputs.
     */
    public function testUnformatNumberLocaleVariants()
    {
        // English format: 1,234.56
        $result = OA_Admin_NumberFormat::unformatNumber('1,234.56');
        $this->assertEqual($result, '1234.56', "Unformat English number 1,234.56");

        // German format: 1.234,56 (needs comma decimal separator)
        $GLOBALS['phpAds_DecimalPoint'] = ',';
        $result = OA_Admin_NumberFormat::unformatNumber('1.234,56');
        $this->assertEqual($result, '1234.56', "Unformat German number 1.234,56");

        // Simple integer
        $result = OA_Admin_NumberFormat::unformatNumber('42');
        $this->assertEqual($result, '42', "Unformat simple integer");

        // Negative number
        $result = OA_Admin_NumberFormat::unformatNumber('-99.5');
        $this->assertEqual($result, '-99.5', "Unformat negative number");

        // Non-number returns false
        $result = OA_Admin_NumberFormat::unformatNumber('abc');
        $this->assertFalse($result, "Non-number should return false");
    }

    // ========================================================================
    // SECTION 6: DATE FORMAT LOCALE-SPECIFIC TESTS
    // ========================================================================

    /**
     * Test that English locale defines all expected date format strings.
     */
    public function testEnglishDateFormatCompleteness()
    {
        $this->loadLocaleSection('en', 'default');

        $dateFormatKeys = [
            'date_format', 'time_format', 'minute_format',
            'month_format', 'day_format', 'week_format', 'weekiso_format',
        ];

        foreach ($dateFormatKeys as $key) {
            $this->assertTrue(
                isset($GLOBALS[$key]) && strlen(trim($GLOBALS[$key])) > 0,
                "[en] Date format key '$key' should be defined and non-empty"
            );
        }
    }

    /**
     * Test that Hebrew locale defines date format strings.
     */
    public function testHebrewDateFormatStrings()
    {
        $this->loadLocaleSection('he', 'default');

        // Hebrew defines date_format, month_format, day_format, week_format, weekiso_format
        $this->assertTrue(
            isset($GLOBALS['date_format']) && strlen(trim($GLOBALS['date_format'])) > 0,
            "[he] date_format should be defined"
        );
        $this->assertTrue(
            isset($GLOBALS['month_format']) && strlen(trim($GLOBALS['month_format'])) > 0,
            "[he] month_format should be defined"
        );
    }

    /**
     * Test that German locale has a locale-specific date format.
     */
    public function testGermanDateFormat()
    {
        $this->loadLocaleSection('de', 'default');

        $this->assertTrue(
            isset($GLOBALS['date_format']),
            "[de] date_format should be defined"
        );
        // German uses dd.mm.YYYY format
        $this->assertEqual(
            $GLOBALS['date_format'],
            '%d.%m.%Y',
            "[de] German date format should be %d.%m.%Y"
        );
    }

    /**
     * Test that day full names are provided for all RTL locales.
     */
    public function testRtlDayFullNames()
    {
        foreach ($this->rtlLocales as $locale) {
            $this->loadLocaleSection($locale, 'default');

            $this->assertTrue(
                isset($GLOBALS['strDayFullNames']) && is_array($GLOBALS['strDayFullNames']),
                "[$locale] strDayFullNames should be an array"
            );

            // Check all 7 days are defined (indices 0-6)
            for ($i = 0; $i <= 6; $i++) {
                $this->assertTrue(
                    isset($GLOBALS['strDayFullNames'][$i]) && strlen(trim($GLOBALS['strDayFullNames'][$i])) > 0,
                    "[$locale] strDayFullNames[$i] should be defined and non-empty"
                );
            }
        }
    }

    /**
     * Test that day shortcut names are provided for all RTL locales.
     */
    public function testRtlDayShortCuts()
    {
        foreach ($this->rtlLocales as $locale) {
            $this->loadLocaleSection($locale, 'default');

            $this->assertTrue(
                isset($GLOBALS['strDayShortCuts']) && is_array($GLOBALS['strDayShortCuts']),
                "[$locale] strDayShortCuts should be an array"
            );

            for ($i = 0; $i <= 6; $i++) {
                $this->assertTrue(
                    isset($GLOBALS['strDayShortCuts'][$i]) && strlen(trim($GLOBALS['strDayShortCuts'][$i])) > 0,
                    "[$locale] strDayShortCuts[$i] should be defined and non-empty"
                );
            }
        }
    }

    /**
     * Test that day full names are provided for LTR sample locales.
     */
    public function testLtrDayFullNames()
    {
        foreach ($this->ltrLocales as $locale) {
            $this->loadLocaleSection($locale, 'default');

            $this->assertTrue(
                isset($GLOBALS['strDayFullNames']) && is_array($GLOBALS['strDayFullNames']),
                "[$locale] strDayFullNames should be an array"
            );

            for ($i = 0; $i <= 6; $i++) {
                $this->assertTrue(
                    isset($GLOBALS['strDayFullNames'][$i]) && strlen(trim($GLOBALS['strDayFullNames'][$i])) > 0,
                    "[$locale] strDayFullNames[$i] should be defined and non-empty"
                );
            }
        }
    }

    // ========================================================================
    // SECTION 7: RTL-SPECIFIC CONTENT VERIFICATION
    // ========================================================================

    /**
     * Test that RTL locales have non-English translations (not just copies of English).
     */
    public function testRtlTranslationsAreNotEnglish()
    {
        // Load English reference first
        $this->loadLocaleSection('en', 'default');
        $enHome = $GLOBALS['strHome'];
        $enHelp = $GLOBALS['strHelp'];
        $enSave = $GLOBALS['strSave'];

        foreach ($this->rtlLocales as $locale) {
            $this->loadLocaleSection($locale, 'default');

            $this->assertNotEqual(
                $GLOBALS['strHome'],
                $enHome,
                "[$locale] strHome should differ from English"
            );
            $this->assertNotEqual(
                $GLOBALS['strHelp'],
                $enHelp,
                "[$locale] strHelp should differ from English"
            );
            $this->assertNotEqual(
                $GLOBALS['strSave'],
                $enSave,
                "[$locale] strSave should differ from English"
            );
        }
    }

    /**
     * Test that RTL locale error messages exist and are translated.
     */
    public function testRtlErrorMessagesTranslated()
    {
        $this->loadLocaleSection('en', 'default');
        $enAccessDenied = $GLOBALS['strAccessDenied'];

        foreach ($this->rtlLocales as $locale) {
            $this->loadLocaleSection($locale, 'default');

            $this->assertTrue(
                isset($GLOBALS['strAccessDenied']) && strlen(trim($GLOBALS['strAccessDenied'])) > 0,
                "[$locale] strAccessDenied should exist and be non-empty"
            );
            $this->assertNotEqual(
                $GLOBALS['strAccessDenied'],
                $enAccessDenied,
                "[$locale] strAccessDenied should be translated (not English)"
            );
        }
    }

    /**
     * Test that RTL locale settings section strings exist.
     */
    public function testRtlSettingsSectionStrings()
    {
        foreach ($this->rtlLocales as $locale) {
            $this->assertTrue(
                $this->loadLocaleSection($locale, 'settings'),
                "[$locale] settings.lang.php should exist and load"
            );

            $this->assertTrue(
                isset($GLOBALS['strInstall']) && strlen(trim($GLOBALS['strInstall'])) > 0,
                "[$locale] Settings: strInstall should be defined and non-empty"
            );
            $this->assertTrue(
                isset($GLOBALS['strDatabaseSettings']) && strlen(trim($GLOBALS['strDatabaseSettings'])) > 0,
                "[$locale] Settings: strDatabaseSettings should be defined and non-empty"
            );
        }
    }

    // ========================================================================
    // PRIVATE HELPER METHODS
    // ========================================================================

    /**
     * Run a single RTL combination test.
     *
     * Verifies:
     * 1. Language file for the section loads correctly
     * 2. RTL text direction is set
     * 3. Content-type-specific strings exist and are non-empty
     * 4. Page-context-specific strings exist
     *
     * @param string $locale      RTL locale code
     * @param string $page        UI page name
     * @param string $contentType Content type name
     */
    private function _runRtlCombo($locale, $page, $contentType)
    {
        $pageConfig = $this->uiPages[$page];
        $section = $pageConfig['section'];
        $contentConfig = $this->contentTypes[$contentType];
        $contentSection = $contentConfig['section'];

        // 1. Load the primary section for this page
        $this->assertTrue(
            $this->loadLocaleSection($locale, $section),
            "[$locale][$page][$contentType] Language file '$section.lang.php' should load"
        );

        // If content type uses a different section, load that too
        if ($contentSection !== $section) {
            $this->assertTrue(
                $this->loadLocaleSection($locale, $contentSection),
                "[$locale][$page][$contentType] Language file '$contentSection.lang.php' should load"
            );
        }

        // 2. Verify RTL direction (only when default section is loaded)
        if ($section === 'default' || $contentSection === 'default') {
            $this->assertEqual(
                $GLOBALS['phpAds_TextDirection'],
                'rtl',
                "[$locale][$page][$contentType] Text direction should be RTL"
            );
        }

        // 3. Verify page-context strings exist
        foreach ($pageConfig['keys'] as $key) {
            $this->assertGlobalStringExists(
                $key,
                $locale,
                "$page/$contentType page context"
            );
        }

        // 4. Verify content-type strings exist
        foreach ($contentConfig['keys'] as $key) {
            $this->assertGlobalStringExists(
                $key,
                $locale,
                "$page/$contentType content type"
            );
        }

        // 5. Additional content-type-specific checks
        switch ($contentType) {
            case 'Date formats':
                $this->_verifyDateFormatStrings($locale, $page);
                break;
            case 'Number formats':
                $this->_verifyNumberFormatStrings($locale, $page);
                break;
            case 'Error messages':
                $this->_verifyErrorMessageStrings($locale, $page);
                break;
            case 'Labels':
                $this->_verifyLabelStrings($locale, $page);
                break;
        }
    }

    /**
     * Run a single LTR combination test.
     *
     * Similar to RTL combo but without RTL-specific direction checks.
     *
     * @param string $locale      LTR locale code
     * @param string $page        UI page name
     * @param string $contentType Content type name
     */
    private function _runLtrCombo($locale, $page, $contentType)
    {
        $pageConfig = $this->uiPages[$page];
        $section = $pageConfig['section'];
        $contentConfig = $this->contentTypes[$contentType];
        $contentSection = $contentConfig['section'];

        // 1. Load the primary section
        $this->assertTrue(
            $this->loadLocaleSection($locale, $section),
            "[$locale][$page][$contentType] Language file '$section.lang.php' should load"
        );

        if ($contentSection !== $section) {
            $this->assertTrue(
                $this->loadLocaleSection($locale, $contentSection),
                "[$locale][$page][$contentType] Language file '$contentSection.lang.php' should load"
            );
        }

        // 2. Verify NOT RTL
        if (isset($GLOBALS['phpAds_TextDirection'])) {
            $this->assertNotEqual(
                $GLOBALS['phpAds_TextDirection'],
                'rtl',
                "[$locale][$page][$contentType] LTR locale should not have RTL direction"
            );
        }

        // 3. Verify page-context strings exist
        foreach ($pageConfig['keys'] as $key) {
            $this->assertGlobalStringExists(
                $key,
                $locale,
                "$page/$contentType page context"
            );
        }

        // 4. Verify content-type strings exist
        foreach ($contentConfig['keys'] as $key) {
            $this->assertGlobalStringExists(
                $key,
                $locale,
                "$page/$contentType content type"
            );
        }

        // 5. Additional content-type-specific checks
        switch ($contentType) {
            case 'Date formats':
                $this->_verifyDateFormatStrings($locale, $page);
                break;
            case 'Number formats':
                $this->_verifyNumberFormatStrings($locale, $page);
                break;
            case 'Error messages':
                $this->_verifyErrorMessageStrings($locale, $page);
                break;
            case 'Labels':
                $this->_verifyLabelStrings($locale, $page);
                break;
        }
    }

    /**
     * Verify date format strings for a locale.
     */
    private function _verifyDateFormatStrings($locale, $page)
    {
        // Day names should be arrays with 7 entries
        if (isset($GLOBALS['strDayFullNames'])) {
            $this->assertTrue(
                is_array($GLOBALS['strDayFullNames']),
                "[$locale][$page] strDayFullNames should be an array"
            );
            $this->assertTrue(
                count($GLOBALS['strDayFullNames']) >= 7,
                "[$locale][$page] strDayFullNames should have at least 7 entries"
            );
        }

        // Time-related strings
        $this->assertGlobalStringExists('strDay', $locale, "$page Date formats");
        $this->assertGlobalStringExists('strDays', $locale, "$page Date formats");
        $this->assertGlobalStringExists('strWeek', $locale, "$page Date formats");
    }

    /**
     * Verify number format strings for a locale.
     */
    private function _verifyNumberFormatStrings($locale, $page)
    {
        $this->assertTrue(
            isset($GLOBALS['phpAds_DecimalPoint']),
            "[$locale][$page] phpAds_DecimalPoint should be defined"
        );

        $decPoint = $GLOBALS['phpAds_DecimalPoint'];
        $this->assertTrue(
            $decPoint === '.' || $decPoint === ',',
            "[$locale][$page] phpAds_DecimalPoint should be '.' or ',', got '$decPoint'"
        );
    }

    /**
     * Verify error message strings for a locale.
     */
    private function _verifyErrorMessageStrings($locale, $page)
    {
        // Core error messages that should exist in all locales
        $errorKeys = [
            'strAccessDenied',
            'strFieldContainsErrors',
            'strPasswordWrong',
        ];

        foreach ($errorKeys as $key) {
            $this->assertGlobalStringExists($key, $locale, "$page Error messages");
        }
    }

    /**
     * Verify label strings for a locale.
     */
    private function _verifyLabelStrings($locale, $page)
    {
        // Core labels that should exist in all locales
        $labelKeys = [
            'strHome',
            'strHelp',
            'strSave',
            'strCancel',
            'strDelete',
        ];

        foreach ($labelKeys as $key) {
            $this->assertGlobalStringExists($key, $locale, "$page Labels");
        }
    }
}
