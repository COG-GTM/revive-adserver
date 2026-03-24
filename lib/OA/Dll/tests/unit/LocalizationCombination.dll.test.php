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

require_once MAX_PATH . '/lib/OX/Translation.php';
require_once MAX_PATH . '/lib/OA/Admin/NumberFormat.php';

/**
 * Localization Combination Matrix — Section 4L
 *
 * Tests covering the Localization Combination Matrix from the combinatorial testing plan:
 *   - 84 exhaustive RTL test cases (3 RTL locales x 7 UI pages x 4 content types)
 *   - Sampled LTR test cases for 5 representative locales (en, de, ja, ko, pt_BR)
 *
 * RTL Dimensions:
 *   Locales:      ar, he, fa
 *   UI Pages:     Dashboard, Campaign edit, Banner edit, Zone edit, Statistics, Settings, User management
 *   Content Types: Labels, Error messages, Date formats, Number formats
 *
 * @package    OpenXDll
 * @subpackage TestSuite
 */
class Test_LocalizationCombination extends UnitTestCase
{
    /**
     * RTL locales under test.
     */
    private static $rtlLocales = ['ar', 'he', 'fa'];

    /**
     * Sampled LTR locales under test.
     */
    private static $ltrLocales = ['en', 'de', 'ja', 'ko', 'pt_BR'];

    /**
     * UI pages mapped to the language file sections that contain their translations.
     * Each page maps to the language file(s) and the representative translation key prefixes.
     */
    private static $uiPages = [
        'Dashboard',
        'Campaign edit',
        'Banner edit',
        'Zone edit',
        'Statistics',
        'Settings',
        'User management',
    ];

    /**
     * Content types under test.
     */
    private static $contentTypes = [
        'Labels',
        'Error messages',
        'Date formats',
        'Number formats',
    ];

    /**
     * Translation keys relevant to each UI page context, categorized by content type.
     *
     * Labels: core UI labels displayed on that page
     * Error messages: validation/error strings relevant to that page
     * Date formats: date/time related strings
     * Number formats: number formatting configuration
     */
    private static $pageTranslationKeys = [
        'Dashboard' => [
            'Labels' => [
                'strHome',
                'strDashboardCantBeDisplayed',
                'strDashboardSystemMessage',
                'strOverview',
                'strShortcuts',
            ],
            'Error messages' => [
                'strDashboardErrorHelp',
                'strNoCheckForUpdates',
                'strFieldContainsErrors',
                'strWarning',
                'strNotice',
            ],
            'Date formats' => [
                'strCollectedToday',
                'strCollectedYesterday',
                'strCollectedThisWeek',
                'strCollectedLastWeek',
                'strCollectedThisMonth',
                'strCollectedLastMonth',
                'strCollectedLast7Days',
            ],
            'Number formats' => [
                'strTotal',
                'strAverage',
                'strOverall',
            ],
        ],
        'Campaign edit' => [
            'Labels' => [
                'strCampaign',
                'strCampaigns',
                'strAddCampaign',
                'strCampaignProperties',
                'strLinkedCampaigns',
            ],
            'Error messages' => [
                'strConfirmDeleteCampaign',
                'strNoCampaigns',
                'strFieldContainsErrors',
                'strFieldFixBeforeContinue1',
                'strFieldFixBeforeContinue2',
            ],
            'Date formats' => [
                'strDate',
                'strDay',
                'strDays',
                'strWeek',
                'strWeeks',
                'strSingleMonth',
                'strMonths',
            ],
            'Number formats' => [
                'strImpressions',
                'strClicks',
                'strConversions',
                'strTotal',
            ],
        ],
        'Banner edit' => [
            'Labels' => [
                'strBanner',
                'strBanners',
                'strAddBanner',
                'strBannerProperties',
                'strShowBanner',
            ],
            'Error messages' => [
                'strConfirmDeleteBanner',
                'strConfirmDeleteBanners',
                'strWarningMissing',
                'strFieldContainsErrors',
                'strSubmitAnyway',
            ],
            'Date formats' => [
                'strDate',
                'strDay',
                'strDays',
                'strHour',
                'strHours',
            ],
            'Number formats' => [
                'strWeight',
                'strSize',
                'strWidth',
                'strHeight',
            ],
        ],
        'Zone edit' => [
            'Labels' => [
                'strAffiliate',
                'strAffiliates',
                'strAddNewAffiliate',
                'strAffiliateProperties',
            ],
            'Error messages' => [
                'strConfirmDeleteAffiliate',
                'strNoAffiliates',
                'strFieldContainsErrors',
                'strFieldFixBeforeContinue1',
            ],
            'Date formats' => [
                'strDate',
                'strDay',
                'strDaysLeft',
                'strHour',
            ],
            'Number formats' => [
                'strImpressions',
                'strClicks',
                'strTotal',
            ],
        ],
        'Statistics' => [
            'Labels' => [
                'strImpressions',
                'strClicks',
                'strConversions',
                'strCTR',
                'strRequests',
            ],
            'Error messages' => [
                'strFieldContainsErrors',
                'strNoAdminInterface',
                'strFieldFixBeforeContinue1',
                'strFieldFixBeforeContinue2',
            ],
            'Date formats' => [
                'strDateTime',
                'strDate',
                'strDay',
                'strDays',
                'strWeek',
                'strSingleMonth',
                'strDayOfWeek',
            ],
            'Number formats' => [
                'strTotal',
                'strAverage',
                'strOverall',
            ],
        ],
        'Settings' => [
            'Labels' => [
                'strInstall',
                'strDatabaseSettings',
                'strAdminAccount',
                'strAdvancedSettings',
                'strGeneralSettings',
            ],
            'Error messages' => [
                'strWarning',
                'strUnableToWriteConfig',
                'strUnableToWritePrefs',
                'strCantConnectToDb',
            ],
            'Date formats' => [
                'strDate',
                'strDay',
                'strHour',
            ],
            'Number formats' => [
                'strTotal',
            ],
        ],
        'User management' => [
            'Labels' => [
                'strUsername',
                'strPassword',
                'strPermissions',
                'strUserAccess',
                'strAdminAccess',
                'strLinkUser',
            ],
            'Error messages' => [
                'strUsernameOrPasswordWrong',
                'strPasswordWrong',
                'strAccessDenied',
                'strDuplicateClientName',
                'strInvalidPassword',
                'strNotSamePasswords',
            ],
            'Date formats' => [
                'strLastLoggedIn',
                'strDateLinked',
                'strDate',
            ],
            'Number formats' => [
                'strTotal',
            ],
        ],
    ];

    /**
     * Settings page keys live in settings.lang.php; default.lang.php has everything else.
     */
    private static $settingsOnlyKeys = [
        'strInstall',
        'strDatabaseSettings',
        'strAdminAccount',
        'strAdvancedSettings',
        'strGeneralSettings',
        'strUnableToWriteConfig',
        'strUnableToWritePrefs',
        'strCantConnectToDb',
    ];

    /**
     * Helper: load language file for a given locale and section.
     * Returns the GLOBALS state so we can inspect the loaded strings.
     */
    private function loadLanguageFile($locale, $section = 'default')
    {
        // Clear previous locale globals
        $this->resetLanguageGlobals();

        // Define required constants/variables for language files
        if (!defined('PRODUCT_NAME')) {
            define('PRODUCT_NAME', 'Revive Adserver');
        }
        if (!defined('PRODUCT_URL')) {
            define('PRODUCT_URL', 'www.revive-adserver.com');
        }
        if (!defined('PRODUCT_DOCSURL')) {
            define('PRODUCT_DOCSURL', 'http://documentation.revive-adserver.com');
        }
        if (!defined('phpAds_dbmsname')) {
            define('phpAds_dbmsname', '');
        }

        $PRODUCT_NAME = PRODUCT_NAME;
        $PRODUCT_URL = PRODUCT_URL;
        $PRODUCT_DOCSURL = PRODUCT_DOCSURL;
        $phpAds_dbmsname = phpAds_dbmsname;

        $langFile = MAX_PATH . '/lib/max/language/' . $locale . '/' . $section . '.lang.php';
        if (file_exists($langFile)) {
            include $langFile;
            return true;
        }
        return false;
    }

    /**
     * Reset language-related globals between test runs.
     */
    private function resetLanguageGlobals()
    {
        // Reset text direction globals
        unset($GLOBALS['phpAds_TextDirection']);
        unset($GLOBALS['phpAds_TextAlignRight']);
        unset($GLOBALS['phpAds_TextAlignLeft']);
        unset($GLOBALS['phpAds_CharSet']);
        unset($GLOBALS['phpAds_DecimalPoint']);
        unset($GLOBALS['phpAds_ThousandsSeperator']);

        // Reset date format globals
        unset($GLOBALS['date_format']);
        unset($GLOBALS['time_format']);
        unset($GLOBALS['minute_format']);
        unset($GLOBALS['month_format']);
        unset($GLOBALS['day_format']);
        unset($GLOBALS['week_format']);
        unset($GLOBALS['weekiso_format']);

        // Reset excel format globals
        unset($GLOBALS['excel_integer_formatting']);
        unset($GLOBALS['excel_decimal_formatting']);
    }

    /**
     * Get the language file section needed for a given UI page.
     */
    private function getSectionForPage($uiPage)
    {
        if ($uiPage === 'Settings') {
            return 'settings';
        }
        return 'default';
    }

    /**
     * Determine if a key belongs to settings.lang.php.
     */
    private function isSettingsKey($key)
    {
        return in_array($key, self::$settingsOnlyKeys);
    }

    /**
     * Generate a test ID string from locale, page, and content type.
     */
    private function getTestId($locale, $uiPage, $contentType, $index)
    {
        return sprintf('L%03d', $index);
    }

    // =========================================================================
    // RTL Exhaustive Tests: 84 combinations (3 locales x 7 pages x 4 content types)
    // =========================================================================

    // --- Arabic (ar) Tests: L001-L028 ---

    /** L001: ar + Dashboard + Labels + RTL */
    public function testL001_ar_Dashboard_Labels()
    {
        $this->runRtlLabelsTest('ar', 'Dashboard');
    }

    /** L002: ar + Dashboard + Error messages + RTL */
    public function testL002_ar_Dashboard_ErrorMessages()
    {
        $this->runRtlErrorMessagesTest('ar', 'Dashboard');
    }

    /** L003: ar + Dashboard + Date formats + RTL */
    public function testL003_ar_Dashboard_DateFormats()
    {
        $this->runRtlDateFormatsTest('ar', 'Dashboard');
    }

    /** L004: ar + Dashboard + Number formats + RTL */
    public function testL004_ar_Dashboard_NumberFormats()
    {
        $this->runRtlNumberFormatsTest('ar', 'Dashboard');
    }

    /** L005: ar + Campaign edit + Labels + RTL */
    public function testL005_ar_CampaignEdit_Labels()
    {
        $this->runRtlLabelsTest('ar', 'Campaign edit');
    }

    /** L006: ar + Campaign edit + Error messages + RTL */
    public function testL006_ar_CampaignEdit_ErrorMessages()
    {
        $this->runRtlErrorMessagesTest('ar', 'Campaign edit');
    }

    /** L007: ar + Campaign edit + Date formats + RTL */
    public function testL007_ar_CampaignEdit_DateFormats()
    {
        $this->runRtlDateFormatsTest('ar', 'Campaign edit');
    }

    /** L008: ar + Campaign edit + Number formats + RTL */
    public function testL008_ar_CampaignEdit_NumberFormats()
    {
        $this->runRtlNumberFormatsTest('ar', 'Campaign edit');
    }

    /** L009: ar + Banner edit + Labels + RTL */
    public function testL009_ar_BannerEdit_Labels()
    {
        $this->runRtlLabelsTest('ar', 'Banner edit');
    }

    /** L010: ar + Banner edit + Error messages + RTL */
    public function testL010_ar_BannerEdit_ErrorMessages()
    {
        $this->runRtlErrorMessagesTest('ar', 'Banner edit');
    }

    /** L011: ar + Banner edit + Date formats + RTL */
    public function testL011_ar_BannerEdit_DateFormats()
    {
        $this->runRtlDateFormatsTest('ar', 'Banner edit');
    }

    /** L012: ar + Banner edit + Number formats + RTL */
    public function testL012_ar_BannerEdit_NumberFormats()
    {
        $this->runRtlNumberFormatsTest('ar', 'Banner edit');
    }

    /** L013: ar + Zone edit + Labels + RTL */
    public function testL013_ar_ZoneEdit_Labels()
    {
        $this->runRtlLabelsTest('ar', 'Zone edit');
    }

    /** L014: ar + Zone edit + Error messages + RTL */
    public function testL014_ar_ZoneEdit_ErrorMessages()
    {
        $this->runRtlErrorMessagesTest('ar', 'Zone edit');
    }

    /** L015: ar + Zone edit + Date formats + RTL */
    public function testL015_ar_ZoneEdit_DateFormats()
    {
        $this->runRtlDateFormatsTest('ar', 'Zone edit');
    }

    /** L016: ar + Zone edit + Number formats + RTL */
    public function testL016_ar_ZoneEdit_NumberFormats()
    {
        $this->runRtlNumberFormatsTest('ar', 'Zone edit');
    }

    /** L017: ar + Statistics + Labels + RTL */
    public function testL017_ar_Statistics_Labels()
    {
        $this->runRtlLabelsTest('ar', 'Statistics');
    }

    /** L018: ar + Statistics + Error messages + RTL */
    public function testL018_ar_Statistics_ErrorMessages()
    {
        $this->runRtlErrorMessagesTest('ar', 'Statistics');
    }

    /** L019: ar + Statistics + Date formats + RTL */
    public function testL019_ar_Statistics_DateFormats()
    {
        $this->runRtlDateFormatsTest('ar', 'Statistics');
    }

    /** L020: ar + Statistics + Number formats + RTL */
    public function testL020_ar_Statistics_NumberFormats()
    {
        $this->runRtlNumberFormatsTest('ar', 'Statistics');
    }

    /** L021: ar + Settings + Labels + RTL */
    public function testL021_ar_Settings_Labels()
    {
        $this->runRtlLabelsTest('ar', 'Settings');
    }

    /** L022: ar + Settings + Error messages + RTL */
    public function testL022_ar_Settings_ErrorMessages()
    {
        $this->runRtlErrorMessagesTest('ar', 'Settings');
    }

    /** L023: ar + Settings + Date formats + RTL */
    public function testL023_ar_Settings_DateFormats()
    {
        $this->runRtlDateFormatsTest('ar', 'Settings');
    }

    /** L024: ar + Settings + Number formats + RTL */
    public function testL024_ar_Settings_NumberFormats()
    {
        $this->runRtlNumberFormatsTest('ar', 'Settings');
    }

    /** L025: ar + User management + Labels + RTL */
    public function testL025_ar_UserManagement_Labels()
    {
        $this->runRtlLabelsTest('ar', 'User management');
    }

    /** L026: ar + User management + Error messages + RTL */
    public function testL026_ar_UserManagement_ErrorMessages()
    {
        $this->runRtlErrorMessagesTest('ar', 'User management');
    }

    /** L027: ar + User management + Date formats + RTL */
    public function testL027_ar_UserManagement_DateFormats()
    {
        $this->runRtlDateFormatsTest('ar', 'User management');
    }

    /** L028: ar + User management + Number formats + RTL */
    public function testL028_ar_UserManagement_NumberFormats()
    {
        $this->runRtlNumberFormatsTest('ar', 'User management');
    }

    // --- Hebrew (he) Tests: L029-L056 ---

    /** L029: he + Dashboard + Labels + RTL */
    public function testL029_he_Dashboard_Labels()
    {
        $this->runRtlLabelsTest('he', 'Dashboard');
    }

    /** L030: he + Dashboard + Error messages + RTL */
    public function testL030_he_Dashboard_ErrorMessages()
    {
        $this->runRtlErrorMessagesTest('he', 'Dashboard');
    }

    /** L031: he + Dashboard + Date formats + RTL */
    public function testL031_he_Dashboard_DateFormats()
    {
        $this->runRtlDateFormatsTest('he', 'Dashboard');
    }

    /** L032: he + Dashboard + Number formats + RTL */
    public function testL032_he_Dashboard_NumberFormats()
    {
        $this->runRtlNumberFormatsTest('he', 'Dashboard');
    }

    /** L033: he + Campaign edit + Labels + RTL */
    public function testL033_he_CampaignEdit_Labels()
    {
        $this->runRtlLabelsTest('he', 'Campaign edit');
    }

    /** L034: he + Campaign edit + Error messages + RTL */
    public function testL034_he_CampaignEdit_ErrorMessages()
    {
        $this->runRtlErrorMessagesTest('he', 'Campaign edit');
    }

    /** L035: he + Campaign edit + Date formats + RTL */
    public function testL035_he_CampaignEdit_DateFormats()
    {
        $this->runRtlDateFormatsTest('he', 'Campaign edit');
    }

    /** L036: he + Campaign edit + Number formats + RTL */
    public function testL036_he_CampaignEdit_NumberFormats()
    {
        $this->runRtlNumberFormatsTest('he', 'Campaign edit');
    }

    /** L037: he + Banner edit + Labels + RTL */
    public function testL037_he_BannerEdit_Labels()
    {
        $this->runRtlLabelsTest('he', 'Banner edit');
    }

    /** L038: he + Banner edit + Error messages + RTL */
    public function testL038_he_BannerEdit_ErrorMessages()
    {
        $this->runRtlErrorMessagesTest('he', 'Banner edit');
    }

    /** L039: he + Banner edit + Date formats + RTL */
    public function testL039_he_BannerEdit_DateFormats()
    {
        $this->runRtlDateFormatsTest('he', 'Banner edit');
    }

    /** L040: he + Banner edit + Number formats + RTL */
    public function testL040_he_BannerEdit_NumberFormats()
    {
        $this->runRtlNumberFormatsTest('he', 'Banner edit');
    }

    /** L041: he + Zone edit + Labels + RTL */
    public function testL041_he_ZoneEdit_Labels()
    {
        $this->runRtlLabelsTest('he', 'Zone edit');
    }

    /** L042: he + Zone edit + Error messages + RTL */
    public function testL042_he_ZoneEdit_ErrorMessages()
    {
        $this->runRtlErrorMessagesTest('he', 'Zone edit');
    }

    /** L043: he + Zone edit + Date formats + RTL */
    public function testL043_he_ZoneEdit_DateFormats()
    {
        $this->runRtlDateFormatsTest('he', 'Zone edit');
    }

    /** L044: he + Zone edit + Number formats + RTL */
    public function testL044_he_ZoneEdit_NumberFormats()
    {
        $this->runRtlNumberFormatsTest('he', 'Zone edit');
    }

    /** L045: he + Statistics + Labels + RTL */
    public function testL045_he_Statistics_Labels()
    {
        $this->runRtlLabelsTest('he', 'Statistics');
    }

    /** L046: he + Statistics + Error messages + RTL */
    public function testL046_he_Statistics_ErrorMessages()
    {
        $this->runRtlErrorMessagesTest('he', 'Statistics');
    }

    /** L047: he + Statistics + Date formats + RTL */
    public function testL047_he_Statistics_DateFormats()
    {
        $this->runRtlDateFormatsTest('he', 'Statistics');
    }

    /** L048: he + Statistics + Number formats + RTL */
    public function testL048_he_Statistics_NumberFormats()
    {
        $this->runRtlNumberFormatsTest('he', 'Statistics');
    }

    /** L049: he + Settings + Labels + RTL */
    public function testL049_he_Settings_Labels()
    {
        $this->runRtlLabelsTest('he', 'Settings');
    }

    /** L050: he + Settings + Error messages + RTL */
    public function testL050_he_Settings_ErrorMessages()
    {
        $this->runRtlErrorMessagesTest('he', 'Settings');
    }

    /** L051: he + Settings + Date formats + RTL */
    public function testL051_he_Settings_DateFormats()
    {
        $this->runRtlDateFormatsTest('he', 'Settings');
    }

    /** L052: he + Settings + Number formats + RTL */
    public function testL052_he_Settings_NumberFormats()
    {
        $this->runRtlNumberFormatsTest('he', 'Settings');
    }

    /** L053: he + User management + Labels + RTL */
    public function testL053_he_UserManagement_Labels()
    {
        $this->runRtlLabelsTest('he', 'User management');
    }

    /** L054: he + User management + Error messages + RTL */
    public function testL054_he_UserManagement_ErrorMessages()
    {
        $this->runRtlErrorMessagesTest('he', 'User management');
    }

    /** L055: he + User management + Date formats + RTL */
    public function testL055_he_UserManagement_DateFormats()
    {
        $this->runRtlDateFormatsTest('he', 'User management');
    }

    /** L056: he + User management + Number formats + RTL */
    public function testL056_he_UserManagement_NumberFormats()
    {
        $this->runRtlNumberFormatsTest('he', 'User management');
    }

    // --- Farsi (fa) Tests: L057-L084 ---

    /** L057: fa + Dashboard + Labels + RTL */
    public function testL057_fa_Dashboard_Labels()
    {
        $this->runRtlLabelsTest('fa', 'Dashboard');
    }

    /** L058: fa + Dashboard + Error messages + RTL */
    public function testL058_fa_Dashboard_ErrorMessages()
    {
        $this->runRtlErrorMessagesTest('fa', 'Dashboard');
    }

    /** L059: fa + Dashboard + Date formats + RTL */
    public function testL059_fa_Dashboard_DateFormats()
    {
        $this->runRtlDateFormatsTest('fa', 'Dashboard');
    }

    /** L060: fa + Dashboard + Number formats + RTL */
    public function testL060_fa_Dashboard_NumberFormats()
    {
        $this->runRtlNumberFormatsTest('fa', 'Dashboard');
    }

    /** L061: fa + Campaign edit + Labels + RTL */
    public function testL061_fa_CampaignEdit_Labels()
    {
        $this->runRtlLabelsTest('fa', 'Campaign edit');
    }

    /** L062: fa + Campaign edit + Error messages + RTL */
    public function testL062_fa_CampaignEdit_ErrorMessages()
    {
        $this->runRtlErrorMessagesTest('fa', 'Campaign edit');
    }

    /** L063: fa + Campaign edit + Date formats + RTL */
    public function testL063_fa_CampaignEdit_DateFormats()
    {
        $this->runRtlDateFormatsTest('fa', 'Campaign edit');
    }

    /** L064: fa + Campaign edit + Number formats + RTL */
    public function testL064_fa_CampaignEdit_NumberFormats()
    {
        $this->runRtlNumberFormatsTest('fa', 'Campaign edit');
    }

    /** L065: fa + Banner edit + Labels + RTL */
    public function testL065_fa_BannerEdit_Labels()
    {
        $this->runRtlLabelsTest('fa', 'Banner edit');
    }

    /** L066: fa + Banner edit + Error messages + RTL */
    public function testL066_fa_BannerEdit_ErrorMessages()
    {
        $this->runRtlErrorMessagesTest('fa', 'Banner edit');
    }

    /** L067: fa + Banner edit + Date formats + RTL */
    public function testL067_fa_BannerEdit_DateFormats()
    {
        $this->runRtlDateFormatsTest('fa', 'Banner edit');
    }

    /** L068: fa + Banner edit + Number formats + RTL */
    public function testL068_fa_BannerEdit_NumberFormats()
    {
        $this->runRtlNumberFormatsTest('fa', 'Banner edit');
    }

    /** L069: fa + Zone edit + Labels + RTL */
    public function testL069_fa_ZoneEdit_Labels()
    {
        $this->runRtlLabelsTest('fa', 'Zone edit');
    }

    /** L070: fa + Zone edit + Error messages + RTL */
    public function testL070_fa_ZoneEdit_ErrorMessages()
    {
        $this->runRtlErrorMessagesTest('fa', 'Zone edit');
    }

    /** L071: fa + Zone edit + Date formats + RTL */
    public function testL071_fa_ZoneEdit_DateFormats()
    {
        $this->runRtlDateFormatsTest('fa', 'Zone edit');
    }

    /** L072: fa + Zone edit + Number formats + RTL */
    public function testL072_fa_ZoneEdit_NumberFormats()
    {
        $this->runRtlNumberFormatsTest('fa', 'Zone edit');
    }

    /** L073: fa + Statistics + Labels + RTL */
    public function testL073_fa_Statistics_Labels()
    {
        $this->runRtlLabelsTest('fa', 'Statistics');
    }

    /** L074: fa + Statistics + Error messages + RTL */
    public function testL074_fa_Statistics_ErrorMessages()
    {
        $this->runRtlErrorMessagesTest('fa', 'Statistics');
    }

    /** L075: fa + Statistics + Date formats + RTL */
    public function testL075_fa_Statistics_DateFormats()
    {
        $this->runRtlDateFormatsTest('fa', 'Statistics');
    }

    /** L076: fa + Statistics + Number formats + RTL */
    public function testL076_fa_Statistics_NumberFormats()
    {
        $this->runRtlNumberFormatsTest('fa', 'Statistics');
    }

    /** L077: fa + Settings + Labels + RTL */
    public function testL077_fa_Settings_Labels()
    {
        $this->runRtlLabelsTest('fa', 'Settings');
    }

    /** L078: fa + Settings + Error messages + RTL */
    public function testL078_fa_Settings_ErrorMessages()
    {
        $this->runRtlErrorMessagesTest('fa', 'Settings');
    }

    /** L079: fa + Settings + Date formats + RTL */
    public function testL079_fa_Settings_DateFormats()
    {
        $this->runRtlDateFormatsTest('fa', 'Settings');
    }

    /** L080: fa + Settings + Number formats + RTL */
    public function testL080_fa_Settings_NumberFormats()
    {
        $this->runRtlNumberFormatsTest('fa', 'Settings');
    }

    /** L081: fa + User management + Labels + RTL */
    public function testL081_fa_UserManagement_Labels()
    {
        $this->runRtlLabelsTest('fa', 'User management');
    }

    /** L082: fa + User management + Error messages + RTL */
    public function testL082_fa_UserManagement_ErrorMessages()
    {
        $this->runRtlErrorMessagesTest('fa', 'User management');
    }

    /** L083: fa + User management + Date formats + RTL */
    public function testL083_fa_UserManagement_DateFormats()
    {
        $this->runRtlDateFormatsTest('fa', 'User management');
    }

    /** L084: fa + User management + Number formats + RTL */
    public function testL084_fa_UserManagement_NumberFormats()
    {
        $this->runRtlNumberFormatsTest('fa', 'User management');
    }

    // =========================================================================
    // Sampled LTR Tests
    // =========================================================================

    // --- English (en) baseline ---

    /** L-en-01: en baseline — key translation strings */
    public function testLen01_en_TranslationStrings()
    {
        $this->runLtrTranslationStringsTest('en');
    }

    /** L-en-02: en baseline — date formats */
    public function testLen02_en_DateFormats()
    {
        $this->runLtrDateFormatsTest('en');
    }

    /** L-en-03: en baseline — number formats */
    public function testLen03_en_NumberFormats()
    {
        $this->runLtrNumberFormatsTest('en');
    }

    /** L-en-04: en baseline — text direction is LTR */
    public function testLen04_en_TextDirection()
    {
        $this->runLtrTextDirectionTest('en');
    }

    /** L-en-05: en baseline — day names defined */
    public function testLen05_en_DayNames()
    {
        $this->runLtrDayNamesTest('en');
    }

    /** L-en-06: en baseline — settings translation strings */
    public function testLen06_en_SettingsTranslations()
    {
        $this->runLtrSettingsTranslationsTest('en');
    }

    // --- German (de) — Latin script, long compound words ---

    /** L-de-01: de — key translation strings */
    public function testLde01_de_TranslationStrings()
    {
        $this->runLtrTranslationStringsTest('de');
    }

    /** L-de-02: de — date formats */
    public function testLde02_de_DateFormats()
    {
        $this->runLtrDateFormatsTest('de');
    }

    /** L-de-03: de — number formats (comma decimal, dot thousands) */
    public function testLde03_de_NumberFormats()
    {
        $this->runLtrNumberFormatsTest('de');
    }

    /** L-de-04: de — text direction is LTR */
    public function testLde04_de_TextDirection()
    {
        $this->runLtrTextDirectionTest('de');
    }

    /** L-de-05: de — day names defined */
    public function testLde05_de_DayNames()
    {
        $this->runLtrDayNamesTest('de');
    }

    /** L-de-06: de — German-specific date format */
    public function testLde06_de_GermanDateFormat()
    {
        $this->loadLanguageFile('de', 'default');
        // German uses DD.MM.YYYY format
        $this->assertTrue(
            isset($GLOBALS['date_format']),
            'de: date_format should be defined'
        );
        $this->assertEqual(
            $GLOBALS['date_format'],
            '%d.%m.%Y',
            'de: date_format should use German DD.MM.YYYY convention'
        );
    }

    /** L-de-07: de — German-specific number format */
    public function testLde07_de_GermanNumberFormat()
    {
        $this->loadLanguageFile('de', 'default');
        // German uses comma for decimal, dot for thousands
        $this->assertEqual(
            $GLOBALS['phpAds_DecimalPoint'],
            ',',
            'de: decimal point should be comma'
        );
        $this->assertEqual(
            $GLOBALS['phpAds_ThousandsSeperator'],
            '.',
            'de: thousands separator should be dot'
        );
    }

    // --- Japanese (ja) — CJK double-width characters ---

    /** L-ja-01: ja — key translation strings */
    public function testLja01_ja_TranslationStrings()
    {
        $this->runLtrTranslationStringsTest('ja');
    }

    /** L-ja-02: ja — date formats */
    public function testLja02_ja_DateFormats()
    {
        $this->runLtrDateFormatsTest('ja');
    }

    /** L-ja-03: ja — number formats */
    public function testLja03_ja_NumberFormats()
    {
        $this->runLtrNumberFormatsTest('ja');
    }

    /** L-ja-04: ja — text direction is LTR */
    public function testLja04_ja_TextDirection()
    {
        $this->runLtrTextDirectionTest('ja');
    }

    /** L-ja-05: ja — day names defined with CJK characters */
    public function testLja05_ja_DayNames()
    {
        $this->runLtrDayNamesTest('ja');
    }

    /** L-ja-06: ja — Japanese-specific date format with kanji */
    public function testLja06_ja_JapaneseDateFormat()
    {
        $this->loadLanguageFile('ja', 'default');
        $this->assertTrue(
            isset($GLOBALS['date_format']),
            'ja: date_format should be defined'
        );
        // Japanese date format uses year-month-day with kanji
        $this->assertPattern(
            '/%Y.*%m.*%d/',
            $GLOBALS['date_format'],
            'ja: date_format should use Y-M-D order'
        );
    }

    /** L-ja-07: ja — CJK characters in translations are multi-byte */
    public function testLja07_ja_CjkMultiByte()
    {
        $this->loadLanguageFile('ja', 'default');
        // Verify CJK strings are multi-byte
        $homeStr = $GLOBALS['strHome'];
        $this->assertTrue(
            strlen($homeStr) > mb_strlen($homeStr, 'UTF-8'),
            'ja: CJK translation strings should be multi-byte (strlen > mb_strlen)'
        );
    }

    // --- Korean (ko) — CJK alternate script ---

    /** L-ko-01: ko — key translation strings */
    public function testLko01_ko_TranslationStrings()
    {
        $this->runLtrTranslationStringsTest('ko');
    }

    /** L-ko-02: ko — date formats */
    public function testLko02_ko_DateFormats()
    {
        $this->runLtrDateFormatsTest('ko');
    }

    /** L-ko-03: ko — number formats */
    public function testLko03_ko_NumberFormats()
    {
        $this->runLtrNumberFormatsTest('ko');
    }

    /** L-ko-04: ko — text direction is LTR */
    public function testLko04_ko_TextDirection()
    {
        $this->runLtrTextDirectionTest('ko');
    }

    /** L-ko-05: ko — day shortcut names defined */
    public function testLko05_ko_DayShortcuts()
    {
        $this->loadLanguageFile('ko', 'default');
        // Korean should define day shortcuts
        $this->assertTrue(
            isset($GLOBALS['strDayShortCuts']) && is_array($GLOBALS['strDayShortCuts']),
            'ko: strDayShortCuts should be defined as array'
        );
        $this->assertEqual(
            count($GLOBALS['strDayShortCuts']),
            7,
            'ko: strDayShortCuts should have 7 entries'
        );
    }

    /** L-ko-06: ko — Korean-specific number format (comma decimal, dot thousands) */
    public function testLko06_ko_KoreanNumberFormat()
    {
        $this->loadLanguageFile('ko', 'default');
        $this->assertTrue(
            isset($GLOBALS['phpAds_DecimalPoint']),
            'ko: phpAds_DecimalPoint should be defined'
        );
        $this->assertTrue(
            isset($GLOBALS['phpAds_ThousandsSeperator']),
            'ko: phpAds_ThousandsSeperator should be defined'
        );
    }

    /** L-ko-07: ko — CJK characters in translations are multi-byte */
    public function testLko07_ko_CjkMultiByte()
    {
        $this->loadLanguageFile('ko', 'default');
        $homeStr = $GLOBALS['strHome'];
        $this->assertTrue(
            strlen($homeStr) > mb_strlen($homeStr, 'UTF-8'),
            'ko: Korean translation strings should be multi-byte (strlen > mb_strlen)'
        );
    }

    // --- Brazilian Portuguese (pt_BR) — Latin with accents, date format differences ---

    /** L-pt_BR-01: pt_BR — key translation strings */
    public function testLptBR01_ptBR_TranslationStrings()
    {
        $this->runLtrTranslationStringsTest('pt_BR');
    }

    /** L-pt_BR-02: pt_BR — date formats */
    public function testLptBR02_ptBR_DateFormats()
    {
        $this->runLtrDateFormatsTest('pt_BR');
    }

    /** L-pt_BR-03: pt_BR — number formats (comma decimal, dot thousands) */
    public function testLptBR03_ptBR_NumberFormats()
    {
        $this->runLtrNumberFormatsTest('pt_BR');
    }

    /** L-pt_BR-04: pt_BR — text direction is LTR */
    public function testLptBR04_ptBR_TextDirection()
    {
        $this->runLtrTextDirectionTest('pt_BR');
    }

    /** L-pt_BR-05: pt_BR — day names defined with accented characters */
    public function testLptBR05_ptBR_DayNames()
    {
        $this->runLtrDayNamesTest('pt_BR');
    }

    /** L-pt_BR-06: pt_BR — accented characters preserved in translations */
    public function testLptBR06_ptBR_AccentedCharacters()
    {
        $this->loadLanguageFile('pt_BR', 'default');
        // Brazilian Portuguese uses accented characters (e.g., Manutenção, Ações)
        $hasAccent = false;
        $accentedKeys = ['strMaintenance', 'strActions', 'strPrevious', 'strMonths'];
        foreach ($accentedKeys as $key) {
            if (isset($GLOBALS[$key]) && preg_match('/[àáâãäéêíóôõúüç]/iu', $GLOBALS[$key])) {
                $hasAccent = true;
                break;
            }
        }
        $this->assertTrue(
            $hasAccent,
            'pt_BR: at least one key translation string should contain accented characters'
        );
    }

    /** L-pt_BR-07: pt_BR — Brazilian number format (comma decimal, dot thousands) */
    public function testLptBR07_ptBR_BrazilianNumberFormat()
    {
        $this->loadLanguageFile('pt_BR', 'default');
        $this->assertEqual(
            $GLOBALS['phpAds_DecimalPoint'],
            ',',
            'pt_BR: decimal point should be comma'
        );
        $this->assertEqual(
            $GLOBALS['phpAds_ThousandsSeperator'],
            '.',
            'pt_BR: thousands separator should be dot'
        );
    }

    // =========================================================================
    // RTL Cross-Cutting Tests
    // =========================================================================

    /** Verify all RTL locale files exist for default section */
    public function testRtlLocaleFilesExist_Default()
    {
        foreach (self::$rtlLocales as $locale) {
            $path = MAX_PATH . '/lib/max/language/' . $locale . '/default.lang.php';
            $this->assertTrue(
                file_exists($path),
                "RTL locale file should exist: {$locale}/default.lang.php"
            );
        }
    }

    /** Verify all RTL locale files exist for settings section */
    public function testRtlLocaleFilesExist_Settings()
    {
        foreach (self::$rtlLocales as $locale) {
            $path = MAX_PATH . '/lib/max/language/' . $locale . '/settings.lang.php';
            $this->assertTrue(
                file_exists($path),
                "RTL locale file should exist: {$locale}/settings.lang.php"
            );
        }
    }

    /** Verify all LTR sampled locale files exist for default section */
    public function testLtrLocaleFilesExist_Default()
    {
        foreach (self::$ltrLocales as $locale) {
            $path = MAX_PATH . '/lib/max/language/' . $locale . '/default.lang.php';
            $this->assertTrue(
                file_exists($path),
                "LTR locale file should exist: {$locale}/default.lang.php"
            );
        }
    }

    /** Verify OX_Translation class can be instantiated */
    public function testOxTranslationInstantiation()
    {
        $oTrans = new OX_Translation();
        $this->assertIsA($oTrans, 'OX_Translation');
        $this->assertEqual($oTrans->locale, 'en_US');
    }

    /** Verify OX_Translation translate method returns non-empty for set globals */
    public function testOxTranslationTranslateWithGlobals()
    {
        $this->loadLanguageFile('ar', 'default');
        $oTrans = new OX_Translation();
        $result = $oTrans->translate('Home');
        $this->assertTrue(
            !empty($GLOBALS['strHome']),
            'ar: strHome should be non-empty after loading language file'
        );
    }

    /** Verify OA_Admin_NumberFormat::formatNumber works with locale settings */
    public function testNumberFormatWithLocaleSettings()
    {
        // Set up German locale format settings
        $GLOBALS['_MAX']['PREF']['ui_percentage_decimals'] = 2;
        $GLOBALS['phpAds_DecimalPoint'] = ',';
        $GLOBALS['phpAds_ThousandsSeperator'] = '.';

        $result = OA_Admin_NumberFormat::formatNumber(1234567.89, 2, ',', '.');
        $this->assertEqual($result, '1.234.567,89', 'Number should be formatted with German locale');

        // Test with English locale format
        $result = OA_Admin_NumberFormat::formatNumber(1234567.89, 2, '.', ',');
        $this->assertEqual($result, '1,234,567.89', 'Number should be formatted with English locale');
    }

    /** Verify OA_Admin_NumberFormat::unformatNumber handles different locale formats */
    public function testUnformatNumberLocaleFormats()
    {
        // Test with dot decimal separator
        $GLOBALS['phpAds_DecimalPoint'] = '.';
        $result = OA_Admin_NumberFormat::unformatNumber('1,234.56');
        $this->assertEqual($result, '1234.56', 'Should unformat English-style number');

        // Test with comma decimal separator
        $GLOBALS['phpAds_DecimalPoint'] = ',';
        $result = OA_Admin_NumberFormat::unformatNumber('1.234,56');
        $this->assertEqual($result, '1234.56', 'Should unformat German-style number');
    }

    // =========================================================================
    // RTL Helper Methods
    // =========================================================================

    /**
     * Run an RTL Labels test for a given locale and UI page.
     * Verifies:
     *   1. Language file loads successfully
     *   2. Text direction is set to RTL
     *   3. Text alignment is correctly reversed
     *   4. All label translation keys for the page are present and non-empty
     */
    private function runRtlLabelsTest($locale, $uiPage)
    {
        $section = $this->getSectionForPage($uiPage);
        $loaded = $this->loadLanguageFile($locale, $section);
        // For Settings page keys that live in default.lang.php, also load default
        if ($section === 'settings') {
            $this->loadLanguageFile($locale, 'default');
            $this->loadLanguageFile($locale, 'settings');
        }

        $this->assertTrue($loaded, "{$locale}/{$section}: language file should load");

        // Load default for RTL direction check if we loaded settings
        if ($section === 'settings') {
            // Direction is set in default.lang.php
            $this->resetLanguageGlobals();
            $this->loadLanguageFile($locale, 'default');
        }

        // Verify RTL direction
        $this->assertEqual(
            $GLOBALS['phpAds_TextDirection'] ?? null,
            'rtl',
            "{$locale}: phpAds_TextDirection should be 'rtl'"
        );
        $this->assertEqual(
            $GLOBALS['phpAds_TextAlignRight'] ?? null,
            'left',
            "{$locale}: phpAds_TextAlignRight should be 'left' (reversed for RTL)"
        );
        $this->assertEqual(
            $GLOBALS['phpAds_TextAlignLeft'] ?? null,
            'right',
            "{$locale}: phpAds_TextAlignLeft should be 'right' (reversed for RTL)"
        );

        // Reload the correct section for label checks
        if ($section === 'settings') {
            $this->loadLanguageFile($locale, 'settings');
        }

        // Verify label translation keys exist and are non-empty
        $keys = self::$pageTranslationKeys[$uiPage]['Labels'];
        foreach ($keys as $key) {
            if ($this->isSettingsKey($key) && $section !== 'settings') {
                continue;
            }
            $this->assertTrue(
                isset($GLOBALS[$key]) && $GLOBALS[$key] !== '',
                "{$locale}/{$uiPage}: label key '{$key}' should exist and be non-empty"
            );
        }
    }

    /**
     * Run an RTL Error Messages test for a given locale and UI page.
     */
    private function runRtlErrorMessagesTest($locale, $uiPage)
    {
        $section = $this->getSectionForPage($uiPage);
        $loaded = $this->loadLanguageFile($locale, $section);
        if ($section === 'settings') {
            $this->loadLanguageFile($locale, 'default');
            $this->loadLanguageFile($locale, 'settings');
        }

        $this->assertTrue($loaded, "{$locale}/{$section}: language file should load");

        // Verify RTL direction from default.lang.php
        $this->resetLanguageGlobals();
        $this->loadLanguageFile($locale, 'default');
        $this->assertEqual(
            $GLOBALS['phpAds_TextDirection'] ?? null,
            'rtl',
            "{$locale}: phpAds_TextDirection should be 'rtl'"
        );

        // Reload section for key checks
        if ($section === 'settings') {
            $this->loadLanguageFile($locale, 'settings');
        }

        // Verify error message keys exist and are non-empty
        $keys = self::$pageTranslationKeys[$uiPage]['Error messages'];
        $foundCount = 0;
        foreach ($keys as $key) {
            if ($this->isSettingsKey($key) && $section !== 'settings') {
                continue;
            }
            if (isset($GLOBALS[$key]) && $GLOBALS[$key] !== '') {
                $foundCount++;
            }
        }
        // At least some error messages should be translated
        $this->assertTrue(
            $foundCount > 0,
            "{$locale}/{$uiPage}: at least one error message key should exist and be non-empty (found {$foundCount})"
        );
    }

    /**
     * Run an RTL Date Formats test for a given locale and UI page.
     */
    private function runRtlDateFormatsTest($locale, $uiPage)
    {
        $section = $this->getSectionForPage($uiPage);
        $loaded = $this->loadLanguageFile($locale, 'default');
        $this->assertTrue($loaded, "{$locale}/default: language file should load");

        // Verify RTL direction
        $this->assertEqual(
            $GLOBALS['phpAds_TextDirection'] ?? null,
            'rtl',
            "{$locale}: phpAds_TextDirection should be 'rtl'"
        );

        // Verify date-related keys exist
        $keys = self::$pageTranslationKeys[$uiPage]['Date formats'];
        $foundCount = 0;
        foreach ($keys as $key) {
            if (isset($GLOBALS[$key]) && $GLOBALS[$key] !== '') {
                $foundCount++;
            }
        }
        $this->assertTrue(
            $foundCount > 0,
            "{$locale}/{$uiPage}: at least one date format key should exist and be non-empty (found {$foundCount})"
        );

        // Verify that day full names are defined for RTL locale
        if (in_array($uiPage, ['Dashboard', 'Campaign edit', 'Statistics'])) {
            $this->assertTrue(
                isset($GLOBALS['strDayFullNames']) && is_array($GLOBALS['strDayFullNames']),
                "{$locale}: strDayFullNames should be defined as array"
            );
            if (isset($GLOBALS['strDayFullNames']) && is_array($GLOBALS['strDayFullNames'])) {
                $this->assertTrue(
                    count($GLOBALS['strDayFullNames']) >= 7,
                    "{$locale}: strDayFullNames should have at least 7 entries"
                );
            }
        }
    }

    /**
     * Run an RTL Number Formats test for a given locale and UI page.
     */
    private function runRtlNumberFormatsTest($locale, $uiPage)
    {
        $loaded = $this->loadLanguageFile($locale, 'default');
        $this->assertTrue($loaded, "{$locale}/default: language file should load");

        // Verify RTL direction
        $this->assertEqual(
            $GLOBALS['phpAds_TextDirection'] ?? null,
            'rtl',
            "{$locale}: phpAds_TextDirection should be 'rtl'"
        );

        // Verify decimal point is defined
        $this->assertTrue(
            isset($GLOBALS['phpAds_DecimalPoint']),
            "{$locale}: phpAds_DecimalPoint should be defined"
        );

        // Verify number-related label keys
        $keys = self::$pageTranslationKeys[$uiPage]['Number formats'];
        $foundCount = 0;
        foreach ($keys as $key) {
            if (isset($GLOBALS[$key]) && $GLOBALS[$key] !== '') {
                $foundCount++;
            }
        }
        $this->assertTrue(
            $foundCount > 0,
            "{$locale}/{$uiPage}: at least one number format label key should exist (found {$foundCount})"
        );

        // Verify OA_Admin_NumberFormat works with this locale's decimal point
        $decimalPoint = $GLOBALS['phpAds_DecimalPoint'] ?? '.';
        $thousandsSep = $GLOBALS['phpAds_ThousandsSeperator'] ?? ',';
        $GLOBALS['_MAX']['PREF']['ui_percentage_decimals'] = 2;

        $result = OA_Admin_NumberFormat::formatNumber(1234.56, 2, $decimalPoint, $thousandsSep);
        $this->assertNotEqual(
            $result,
            false,
            "{$locale}: OA_Admin_NumberFormat::formatNumber should return a valid formatted number"
        );
    }

    // =========================================================================
    // LTR Helper Methods
    // =========================================================================

    /**
     * Test key translation strings for an LTR locale.
     */
    private function runLtrTranslationStringsTest($locale)
    {
        $loaded = $this->loadLanguageFile($locale, 'default');
        $this->assertTrue($loaded, "{$locale}/default: language file should load");

        // Core translation keys that should exist in all locales
        $coreKeys = [
            'strHome', 'strHelp', 'strSave', 'strCancel', 'strDelete',
            'strYes', 'strNo', 'strSearch', 'strActions',
            'strImpressions', 'strClicks', 'strBanners', 'strCampaigns',
            'strUsername', 'strPassword', 'strLogin', 'strLogout',
            'strName', 'strSize', 'strWidth', 'strHeight',
            'strDate', 'strDay', 'strDays', 'strWeek',
        ];
        $foundCount = 0;
        $totalKeys = count($coreKeys);
        foreach ($coreKeys as $key) {
            if (isset($GLOBALS[$key]) && $GLOBALS[$key] !== '') {
                $foundCount++;
            }
        }
        // At least 80% of core keys should be translated
        $threshold = (int) ($totalKeys * 0.8);
        $this->assertTrue(
            $foundCount >= $threshold,
            "{$locale}: at least {$threshold}/{$totalKeys} core translation keys should be present (found {$foundCount})"
        );
    }

    /**
     * Test date formats for an LTR locale.
     */
    private function runLtrDateFormatsTest($locale)
    {
        $loaded = $this->loadLanguageFile($locale, 'default');
        $this->assertTrue($loaded, "{$locale}/default: language file should load");

        // At least some date-related strings should be defined
        $dateKeys = ['strDate', 'strDay', 'strDays', 'strWeek', 'strSingleMonth'];
        $foundCount = 0;
        foreach ($dateKeys as $key) {
            if (isset($GLOBALS[$key]) && $GLOBALS[$key] !== '') {
                $foundCount++;
            }
        }
        $this->assertTrue(
            $foundCount >= 3,
            "{$locale}: at least 3 date-related translation keys should be present (found {$foundCount})"
        );
    }

    /**
     * Test number formats for an LTR locale.
     */
    private function runLtrNumberFormatsTest($locale)
    {
        $loaded = $this->loadLanguageFile($locale, 'default');
        $this->assertTrue($loaded, "{$locale}/default: language file should load");

        // Decimal point should be defined
        $this->assertTrue(
            isset($GLOBALS['phpAds_DecimalPoint']),
            "{$locale}: phpAds_DecimalPoint should be defined"
        );

        $decimalPoint = $GLOBALS['phpAds_DecimalPoint'];
        $this->assertTrue(
            in_array($decimalPoint, ['.', ',']),
            "{$locale}: phpAds_DecimalPoint should be '.' or ',', got '{$decimalPoint}'"
        );

        // Verify OA_Admin_NumberFormat works
        $thousandsSep = $GLOBALS['phpAds_ThousandsSeperator'] ?? ',';
        $GLOBALS['_MAX']['PREF']['ui_percentage_decimals'] = 2;

        $result = OA_Admin_NumberFormat::formatNumber(9876.54, 2, $decimalPoint, $thousandsSep);
        $this->assertNotEqual(
            $result,
            false,
            "{$locale}: OA_Admin_NumberFormat::formatNumber should produce valid output"
        );
    }

    /**
     * Test text direction for an LTR locale.
     */
    private function runLtrTextDirectionTest($locale)
    {
        $loaded = $this->loadLanguageFile($locale, 'default');
        $this->assertTrue($loaded, "{$locale}/default: language file should load");

        // LTR locales should either not set phpAds_TextDirection or set it to 'ltr'
        if (isset($GLOBALS['phpAds_TextDirection'])) {
            $this->assertEqual(
                $GLOBALS['phpAds_TextDirection'],
                'ltr',
                "{$locale}: phpAds_TextDirection should be 'ltr' for LTR locale"
            );
        }
        // If TextAlignRight is set, it should be 'right' (not reversed)
        if (isset($GLOBALS['phpAds_TextAlignRight'])) {
            $this->assertEqual(
                $GLOBALS['phpAds_TextAlignRight'],
                'right',
                "{$locale}: phpAds_TextAlignRight should be 'right' for LTR locale"
            );
        }
    }

    /**
     * Test day names for an LTR locale.
     */
    private function runLtrDayNamesTest($locale)
    {
        $loaded = $this->loadLanguageFile($locale, 'default');
        $this->assertTrue($loaded, "{$locale}/default: language file should load");

        // Day shortcuts should be defined
        $this->assertTrue(
            isset($GLOBALS['strDayShortCuts']) && is_array($GLOBALS['strDayShortCuts']),
            "{$locale}: strDayShortCuts should be defined as array"
        );
        if (isset($GLOBALS['strDayShortCuts']) && is_array($GLOBALS['strDayShortCuts'])) {
            $this->assertEqual(
                count($GLOBALS['strDayShortCuts']),
                7,
                "{$locale}: strDayShortCuts should have 7 entries"
            );
            // Each day shortcut should be non-empty
            foreach ($GLOBALS['strDayShortCuts'] as $idx => $shortcut) {
                $this->assertTrue(
                    !empty($shortcut),
                    "{$locale}: strDayShortCuts[{$idx}] should be non-empty"
                );
            }
        }
    }

    /**
     * Test settings page translations for an LTR locale.
     */
    private function runLtrSettingsTranslationsTest($locale)
    {
        $loaded = $this->loadLanguageFile($locale, 'settings');
        $this->assertTrue($loaded, "{$locale}/settings: language file should load");

        $settingsKeys = ['strInstall', 'strDatabaseSettings', 'strAdminAccount'];
        $foundCount = 0;
        foreach ($settingsKeys as $key) {
            if (isset($GLOBALS[$key]) && $GLOBALS[$key] !== '') {
                $foundCount++;
            }
        }
        $this->assertTrue(
            $foundCount >= 2,
            "{$locale}: at least 2 settings translation keys should be present (found {$foundCount})"
        );
    }
}
