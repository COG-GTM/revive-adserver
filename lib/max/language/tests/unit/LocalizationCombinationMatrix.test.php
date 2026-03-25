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
 * Section 4L — Localization Combination Matrix Tests
 *
 * Exhaustive RTL tests: 3 RTL locales × 7 UI pages × 4 content types = 84 tests
 * Sampled LTR tests:   5 LTR locales × 7 UI pages × 2 content types = 70 tests
 * Total: 154 tests
 *
 * @package    MaxUI
 * @subpackage TestSuite
 */
class LocalizationCombinationMatrixTest extends UnitTestCase
{
    /**
     * RTL locales to test exhaustively.
     */
    private static $rtlLocales = ['ar', 'he', 'fa'];

    /**
     * Sampled LTR locales with rationale.
     *   en    — Baseline / reference
     *   de    — Latin script, long compound words
     *   ja    — CJK double-width characters
     *   ko    — CJK alternate script
     *   pt_BR — Latin with accents, date format differences
     */
    private static $ltrLocales = ['en', 'de', 'ja', 'ko', 'pt_BR'];

    /**
     * UI pages mapped to the translation keys that belong to them.
     *
     * Keys are grouped by page context based on the section comments
     * in the English default.lang.php and settings.lang.php files.
     */
    private static $uiPageKeyMap = [
        'dashboard' => [
            'labels' => [
                'strDashboardCantBeDisplayed',
                'strDashboardSystemMessage',
                'strHome',
                'strHelp',
                'strShortcuts',
                'strOverview',
            ],
            'error_messages' => [
                'strDashboardErrorHelp',
                'strNoCheckForUpdates',
                'strFieldContainsErrors',
                'strFieldFixBeforeContinue1',
            ],
            'date_formats' => [
                'strCollectedToday',
                'strCollectedYesterday',
                'strCollectedThisWeek',
                'strCollectedLastWeek',
                'strCollectedThisMonth',
                'strCollectedLastMonth',
                'strCollectedLast7Days',
            ],
            'number_formats' => [
                'strImpressions',
                'strClicks',
                'strCTR',
                'strRequests',
                'strConversions',
            ],
        ],
        'campaign_edit' => [
            'labels' => [
                'strCampaign',
                'strCampaigns',
                'strAddCampaign',
                'strCampaignName',
                'strLinkedCampaigns',
                'strCampaignProperties',
            ],
            'error_messages' => [
                'strConfirmDeleteCampaign',
                'strNoCampaigns',
                'strFieldContainsErrors',
                'strFieldFixBeforeContinue1',
            ],
            'date_formats' => [
                'strDate',
                'strDay',
                'strDays',
                'strWeek',
                'strWeeks',
                'strSingleMonth',
                'strMonths',
            ],
            'number_formats' => [
                'strImpressions',
                'strClicks',
                'strConversions',
                'strTotal',
                'strPriority',
            ],
        ],
        'banner_edit' => [
            'labels' => [
                'strBanners',
                'strAddBanner',
                'strBannerName',
                'strName',
                'strSize',
                'strWidth',
                'strHeight',
            ],
            'error_messages' => [
                'strConfirmDeleteBanner',
                'strFieldContainsErrors',
                'strFieldFixBeforeContinue1',
                'strFieldFixBeforeContinue2',
            ],
            'date_formats' => [
                'strDate',
                'strDay',
                'strDays',
                'strWeek',
                'strSingleMonth',
            ],
            'number_formats' => [
                'strImpressions',
                'strClicks',
                'strTotal',
                'strKiloByte',
            ],
        ],
        'zone_edit' => [
            'labels' => [
                'strZone',
                'strZones',
                'strAddNewZone',
                'strZoneProperties',
                'strZoneType',
                'strLinkedZones',
            ],
            'error_messages' => [
                'strConfirmDeleteZone',
                'strNoZones',
                'strFieldContainsErrors',
                'strFieldFixBeforeContinue1',
            ],
            'date_formats' => [
                'strDate',
                'strDay',
                'strDays',
                'strWeek',
                'strSingleMonth',
            ],
            'number_formats' => [
                'strImpressions',
                'strClicks',
                'strTotal',
                'strWidth',
                'strHeight',
            ],
        ],
        'statistics' => [
            'labels' => [
                'strStats',
                'strNoStats',
                'strGlobalHistory',
                'strDailyHistory',
                'strDailyStats',
                'strWeeklyHistory',
                'strMonthlyHistory',
            ],
            'error_messages' => [
                'strNoStatsForPeriod',
                'strGDnotEnabled',
                'strFieldContainsErrors',
                'strFieldFixBeforeContinue1',
            ],
            'date_formats' => [
                'strDate',
                'strDay',
                'strDays',
                'strWeek',
                'strSingleMonth',
                'strMonths',
                'strDayOfWeek',
            ],
            'number_formats' => [
                'strImpressions',
                'strClicks',
                'strConversions',
                'strTotal',
                'strAverage',
            ],
        ],
        'settings' => [
            'labels' => [
                'strUpdateSettings',
                'strSaveChanges',
                'strSave',
                'strCancel',
                'strDefault',
                'strLanguage',
            ],
            'error_messages' => [
                'strFieldContainsErrors',
                'strFieldFixBeforeContinue1',
                'strFieldFixBeforeContinue2',
                'strWarning',
            ],
            'date_formats' => [
                'strDate',
                'strDay',
                'strDays',
                'strWeek',
                'strSingleMonth',
            ],
            'number_formats' => [
                'strTotal',
                'strUnlimited',
                'strValue',
            ],
        ],
        'user_management' => [
            'labels' => [
                'strUser',
                'strUsername',
                'strPassword',
                'strLogin',
                'strLogout',
                'strPermissions',
                'strUserAccess',
                'strAdminAccess',
            ],
            'error_messages' => [
                'strUsernameOrPasswordWrong',
                'strPasswordWrong',
                'strAccessDenied',
                'strNotSamePasswords',
                'strInvalidPassword',
                'strDuplicateClientName',
            ],
            'date_formats' => [
                'strDate',
                'strDay',
                'strDays',
                'strLastLoggedIn',
            ],
            'number_formats' => [
                'strTotal',
                'strID',
            ],
        ],
    ];

    /**
     * Cache of loaded locale data to avoid re-including files.
     *
     * @var array
     */
    private $localeCache = [];

    /**
     * Load all translation keys for a given locale by including the
     * default.lang.php (and settings.lang.php) files and capturing
     * the $GLOBALS they set.
     *
     * @param string $locale Locale code (e.g. 'ar', 'en', 'de')
     * @return array Associative array of translation key => value
     */
    private function loadLocaleData($locale)
    {
        if (isset($this->localeCache[$locale])) {
            return $this->localeCache[$locale];
        }

        // Snapshot current GLOBALS to detect new keys
        $before = $GLOBALS;

        // Required variables for language files
        $PRODUCT_NAME = defined('PRODUCT_NAME') ? PRODUCT_NAME : 'Revive Adserver';
        $PRODUCT_URL = defined('PRODUCT_URL') ? PRODUCT_URL : 'https://www.revive-adserver.com';
        $PRODUCT_DOCSURL = defined('PRODUCT_DOCSURL') ? PRODUCT_DOCSURL : 'https://www.revive-adserver.com';

        // Always load English first as the base
        $enFile = MAX_PATH . '/lib/max/language/en/default.lang.php';
        if (file_exists($enFile)) {
            include $enFile;
        }

        // Load the target locale on top
        if ($locale !== 'en') {
            $localeFile = MAX_PATH . '/lib/max/language/' . $locale . '/default.lang.php';
            if (file_exists($localeFile)) {
                include $localeFile;
            }
        }

        // Also load settings.lang.php for settings page keys
        $enSettingsFile = MAX_PATH . '/lib/max/language/en/settings.lang.php';
        if (file_exists($enSettingsFile)) {
            include $enSettingsFile;
        }

        if ($locale !== 'en') {
            $settingsFile = MAX_PATH . '/lib/max/language/' . $locale . '/settings.lang.php';
            if (file_exists($settingsFile)) {
                include $settingsFile;
            }
        }

        // Capture all str* keys and config keys
        $data = [];
        foreach ($GLOBALS as $key => $value) {
            if (str_starts_with($key, 'str') && is_string($value)) {
                $data[$key] = $value;
            }
        }

        // Capture text direction settings
        $data['phpAds_TextDirection'] = $GLOBALS['phpAds_TextDirection'] ?? 'ltr';
        $data['phpAds_TextAlignRight'] = $GLOBALS['phpAds_TextAlignRight'] ?? 'right';
        $data['phpAds_TextAlignLeft'] = $GLOBALS['phpAds_TextAlignLeft'] ?? 'left';

        // Capture number format settings
        if (isset($GLOBALS['phpAds_DecimalPoint'])) {
            $data['phpAds_DecimalPoint'] = $GLOBALS['phpAds_DecimalPoint'];
        }
        if (isset($GLOBALS['phpAds_ThousandsSeperator'])) {
            $data['phpAds_ThousandsSeperator'] = $GLOBALS['phpAds_ThousandsSeperator'];
        }

        // Capture date format settings
        foreach (['date_format', 'time_format', 'minute_format', 'month_format',
                   'day_format', 'week_format', 'weekiso_format'] as $fmt) {
            if (isset($GLOBALS[$fmt])) {
                $data[$fmt] = $GLOBALS[$fmt];
            }
        }

        // Capture day names
        if (isset($GLOBALS['strDayFullNames']) && is_array($GLOBALS['strDayFullNames'])) {
            $data['strDayFullNames'] = $GLOBALS['strDayFullNames'];
        }
        if (isset($GLOBALS['strDayShortCuts']) && is_array($GLOBALS['strDayShortCuts'])) {
            $data['strDayShortCuts'] = $GLOBALS['strDayShortCuts'];
        }

        $this->localeCache[$locale] = $data;
        return $data;
    }

    /**
     * Get the translation keys relevant for a specific UI page and content type.
     *
     * @param string $page        UI page name
     * @param string $contentType Content type (labels, error_messages, date_formats, number_formats)
     * @return array List of translation key names
     */
    private function getKeysForPageAndContentType($page, $contentType)
    {
        if (isset(self::$uiPageKeyMap[$page][$contentType])) {
            return self::$uiPageKeyMap[$page][$contentType];
        }
        return [];
    }

    /**
     * Assert that all expected translation keys exist and have non-empty values
     * for the given locale.
     *
     * @param string $locale      Locale code
     * @param string $page        UI page name
     * @param string $contentType Content type
     * @param array  $localeData  Loaded locale data
     */
    private function assertTranslationKeysPresent($locale, $page, $contentType, $localeData)
    {
        $keys = $this->getKeysForPageAndContentType($page, $contentType);
        $this->assertTrue(
            count($keys) > 0,
            "[$locale/$page/$contentType] No translation keys defined for this combination"
        );

        foreach ($keys as $key) {
            $this->assertTrue(
                isset($localeData[$key]),
                "[$locale/$page/$contentType] Translation key '$key' is missing"
            );
            $this->assertTrue(
                strlen(trim($localeData[$key])) > 0,
                "[$locale/$page/$contentType] Translation key '$key' is empty"
            );
        }
    }

    /**
     * Assert RTL text direction markers are correctly set for an RTL locale.
     *
     * @param string $locale     Locale code
     * @param array  $localeData Loaded locale data
     */
    private function assertRtlDirectionMarkers($locale, $localeData)
    {
        $this->assertEqual(
            $localeData['phpAds_TextDirection'],
            'rtl',
            "[$locale] Expected text direction 'rtl' but got '{$localeData['phpAds_TextDirection']}'"
        );
        // In RTL locales, right-align and left-align are swapped
        $this->assertEqual(
            $localeData['phpAds_TextAlignRight'],
            'left',
            "[$locale] Expected phpAds_TextAlignRight to be 'left' for RTL locale"
        );
        $this->assertEqual(
            $localeData['phpAds_TextAlignLeft'],
            'right',
            "[$locale] Expected phpAds_TextAlignLeft to be 'right' for RTL locale"
        );
    }

    /**
     * Assert that date format related keys use locale-appropriate values.
     *
     * @param string $locale     Locale code
     * @param array  $localeData Loaded locale data
     */
    private function assertDateFormatsLocaleAppropriate($locale, $localeData)
    {
        // Day names must be present and be 7-element arrays
        if (isset($localeData['strDayFullNames'])) {
            $this->assertEqual(
                count($localeData['strDayFullNames']),
                7,
                "[$locale] strDayFullNames should have 7 entries"
            );
            foreach ($localeData['strDayFullNames'] as $idx => $name) {
                $this->assertTrue(
                    strlen(trim($name)) > 0,
                    "[$locale] strDayFullNames[$idx] is empty"
                );
            }
        }

        if (isset($localeData['strDayShortCuts'])) {
            $this->assertEqual(
                count($localeData['strDayShortCuts']),
                7,
                "[$locale] strDayShortCuts should have 7 entries"
            );
            foreach ($localeData['strDayShortCuts'] as $idx => $shortcut) {
                $this->assertTrue(
                    strlen(trim($shortcut)) > 0,
                    "[$locale] strDayShortCuts[$idx] is empty"
                );
            }
        }
    }

    /**
     * Assert number format settings are present for a locale.
     *
     * @param string $locale     Locale code
     * @param array  $localeData Loaded locale data
     */
    private function assertNumberFormatsPresent($locale, $localeData)
    {
        $this->assertTrue(
            isset($localeData['phpAds_DecimalPoint']),
            "[$locale] phpAds_DecimalPoint should be defined"
        );
        $this->assertTrue(
            strlen($localeData['phpAds_DecimalPoint']) > 0,
            "[$locale] phpAds_DecimalPoint should not be empty"
        );
    }

    // =========================================================================
    // RTL EXHAUSTIVE TESTS: 3 locales × 7 pages × 4 content types = 84 tests
    // =========================================================================

    // --- Arabic (ar) — 28 tests ---

    // L001: ar × Dashboard × Labels
    public function testL001_ar_dashboard_labels()
    {
        $data = $this->loadLocaleData('ar');
        $this->assertRtlDirectionMarkers('ar', $data);
        $this->assertTranslationKeysPresent('ar', 'dashboard', 'labels', $data);
    }

    // L002: ar × Campaign edit × Error messages
    public function testL002_ar_campaign_edit_error_messages()
    {
        $data = $this->loadLocaleData('ar');
        $this->assertRtlDirectionMarkers('ar', $data);
        $this->assertTranslationKeysPresent('ar', 'campaign_edit', 'error_messages', $data);
    }

    // L003: ar × Banner edit × Date formats
    public function testL003_ar_banner_edit_date_formats()
    {
        $data = $this->loadLocaleData('ar');
        $this->assertRtlDirectionMarkers('ar', $data);
        $this->assertTranslationKeysPresent('ar', 'banner_edit', 'date_formats', $data);
        $this->assertDateFormatsLocaleAppropriate('ar', $data);
    }

    // L004: ar × Zone edit × Number formats
    public function testL004_ar_zone_edit_number_formats()
    {
        $data = $this->loadLocaleData('ar');
        $this->assertRtlDirectionMarkers('ar', $data);
        $this->assertTranslationKeysPresent('ar', 'zone_edit', 'number_formats', $data);
        $this->assertNumberFormatsPresent('ar', $data);
    }

    // L005: ar × Statistics × Labels
    public function testL005_ar_statistics_labels()
    {
        $data = $this->loadLocaleData('ar');
        $this->assertRtlDirectionMarkers('ar', $data);
        $this->assertTranslationKeysPresent('ar', 'statistics', 'labels', $data);
    }

    // L006: ar × Settings × Error messages
    public function testL006_ar_settings_error_messages()
    {
        $data = $this->loadLocaleData('ar');
        $this->assertRtlDirectionMarkers('ar', $data);
        $this->assertTranslationKeysPresent('ar', 'settings', 'error_messages', $data);
    }

    // L007: ar × User management × Labels
    public function testL007_ar_user_management_labels()
    {
        $data = $this->loadLocaleData('ar');
        $this->assertRtlDirectionMarkers('ar', $data);
        $this->assertTranslationKeysPresent('ar', 'user_management', 'labels', $data);
    }

    // L008: ar × Dashboard × Error messages
    public function testL008_ar_dashboard_error_messages()
    {
        $data = $this->loadLocaleData('ar');
        $this->assertRtlDirectionMarkers('ar', $data);
        $this->assertTranslationKeysPresent('ar', 'dashboard', 'error_messages', $data);
    }

    // L009: ar × Campaign edit × Labels
    public function testL009_ar_campaign_edit_labels()
    {
        $data = $this->loadLocaleData('ar');
        $this->assertRtlDirectionMarkers('ar', $data);
        $this->assertTranslationKeysPresent('ar', 'campaign_edit', 'labels', $data);
    }

    // L010: ar × Banner edit × Labels
    public function testL010_ar_banner_edit_labels()
    {
        $data = $this->loadLocaleData('ar');
        $this->assertRtlDirectionMarkers('ar', $data);
        $this->assertTranslationKeysPresent('ar', 'banner_edit', 'labels', $data);
    }

    // L011: ar × Zone edit × Labels
    public function testL011_ar_zone_edit_labels()
    {
        $data = $this->loadLocaleData('ar');
        $this->assertRtlDirectionMarkers('ar', $data);
        $this->assertTranslationKeysPresent('ar', 'zone_edit', 'labels', $data);
    }

    // L012: ar × Statistics × Error messages
    public function testL012_ar_statistics_error_messages()
    {
        $data = $this->loadLocaleData('ar');
        $this->assertRtlDirectionMarkers('ar', $data);
        $this->assertTranslationKeysPresent('ar', 'statistics', 'error_messages', $data);
    }

    // L013: ar × Settings × Labels
    public function testL013_ar_settings_labels()
    {
        $data = $this->loadLocaleData('ar');
        $this->assertRtlDirectionMarkers('ar', $data);
        $this->assertTranslationKeysPresent('ar', 'settings', 'labels', $data);
    }

    // L014: ar × User management × Error messages
    public function testL014_ar_user_management_error_messages()
    {
        $data = $this->loadLocaleData('ar');
        $this->assertRtlDirectionMarkers('ar', $data);
        $this->assertTranslationKeysPresent('ar', 'user_management', 'error_messages', $data);
    }

    // L015: ar × Dashboard × Date formats
    public function testL015_ar_dashboard_date_formats()
    {
        $data = $this->loadLocaleData('ar');
        $this->assertRtlDirectionMarkers('ar', $data);
        $this->assertTranslationKeysPresent('ar', 'dashboard', 'date_formats', $data);
        $this->assertDateFormatsLocaleAppropriate('ar', $data);
    }

    // L016: ar × Campaign edit × Date formats
    public function testL016_ar_campaign_edit_date_formats()
    {
        $data = $this->loadLocaleData('ar');
        $this->assertRtlDirectionMarkers('ar', $data);
        $this->assertTranslationKeysPresent('ar', 'campaign_edit', 'date_formats', $data);
        $this->assertDateFormatsLocaleAppropriate('ar', $data);
    }

    // L017: ar × Banner edit × Error messages
    public function testL017_ar_banner_edit_error_messages()
    {
        $data = $this->loadLocaleData('ar');
        $this->assertRtlDirectionMarkers('ar', $data);
        $this->assertTranslationKeysPresent('ar', 'banner_edit', 'error_messages', $data);
    }

    // L018: ar × Zone edit × Error messages
    public function testL018_ar_zone_edit_error_messages()
    {
        $data = $this->loadLocaleData('ar');
        $this->assertRtlDirectionMarkers('ar', $data);
        $this->assertTranslationKeysPresent('ar', 'zone_edit', 'error_messages', $data);
    }

    // L019: ar × Statistics × Date formats
    public function testL019_ar_statistics_date_formats()
    {
        $data = $this->loadLocaleData('ar');
        $this->assertRtlDirectionMarkers('ar', $data);
        $this->assertTranslationKeysPresent('ar', 'statistics', 'date_formats', $data);
        $this->assertDateFormatsLocaleAppropriate('ar', $data);
    }

    // L020: ar × Settings × Date formats
    public function testL020_ar_settings_date_formats()
    {
        $data = $this->loadLocaleData('ar');
        $this->assertRtlDirectionMarkers('ar', $data);
        $this->assertTranslationKeysPresent('ar', 'settings', 'date_formats', $data);
        $this->assertDateFormatsLocaleAppropriate('ar', $data);
    }

    // L021: ar × User management × Date formats
    public function testL021_ar_user_management_date_formats()
    {
        $data = $this->loadLocaleData('ar');
        $this->assertRtlDirectionMarkers('ar', $data);
        $this->assertTranslationKeysPresent('ar', 'user_management', 'date_formats', $data);
        $this->assertDateFormatsLocaleAppropriate('ar', $data);
    }

    // L022: ar × Dashboard × Number formats
    public function testL022_ar_dashboard_number_formats()
    {
        $data = $this->loadLocaleData('ar');
        $this->assertRtlDirectionMarkers('ar', $data);
        $this->assertTranslationKeysPresent('ar', 'dashboard', 'number_formats', $data);
        $this->assertNumberFormatsPresent('ar', $data);
    }

    // L023: ar × Campaign edit × Number formats
    public function testL023_ar_campaign_edit_number_formats()
    {
        $data = $this->loadLocaleData('ar');
        $this->assertRtlDirectionMarkers('ar', $data);
        $this->assertTranslationKeysPresent('ar', 'campaign_edit', 'number_formats', $data);
        $this->assertNumberFormatsPresent('ar', $data);
    }

    // L024: ar × Banner edit × Number formats
    public function testL024_ar_banner_edit_number_formats()
    {
        $data = $this->loadLocaleData('ar');
        $this->assertRtlDirectionMarkers('ar', $data);
        $this->assertTranslationKeysPresent('ar', 'banner_edit', 'number_formats', $data);
        $this->assertNumberFormatsPresent('ar', $data);
    }

    // L025: ar × Zone edit × Date formats
    public function testL025_ar_zone_edit_date_formats()
    {
        $data = $this->loadLocaleData('ar');
        $this->assertRtlDirectionMarkers('ar', $data);
        $this->assertTranslationKeysPresent('ar', 'zone_edit', 'date_formats', $data);
        $this->assertDateFormatsLocaleAppropriate('ar', $data);
    }

    // L026: ar × Statistics × Number formats
    public function testL026_ar_statistics_number_formats()
    {
        $data = $this->loadLocaleData('ar');
        $this->assertRtlDirectionMarkers('ar', $data);
        $this->assertTranslationKeysPresent('ar', 'statistics', 'number_formats', $data);
        $this->assertNumberFormatsPresent('ar', $data);
    }

    // L027: ar × Settings × Number formats
    public function testL027_ar_settings_number_formats()
    {
        $data = $this->loadLocaleData('ar');
        $this->assertRtlDirectionMarkers('ar', $data);
        $this->assertTranslationKeysPresent('ar', 'settings', 'number_formats', $data);
        $this->assertNumberFormatsPresent('ar', $data);
    }

    // L028: ar × User management × Number formats
    public function testL028_ar_user_management_number_formats()
    {
        $data = $this->loadLocaleData('ar');
        $this->assertRtlDirectionMarkers('ar', $data);
        $this->assertTranslationKeysPresent('ar', 'user_management', 'number_formats', $data);
        $this->assertNumberFormatsPresent('ar', $data);
    }

    // --- Hebrew (he) — 28 tests ---

    // L029: he × Dashboard × Labels
    public function testL029_he_dashboard_labels()
    {
        $data = $this->loadLocaleData('he');
        $this->assertRtlDirectionMarkers('he', $data);
        $this->assertTranslationKeysPresent('he', 'dashboard', 'labels', $data);
    }

    // L030: he × Campaign edit × Error messages
    public function testL030_he_campaign_edit_error_messages()
    {
        $data = $this->loadLocaleData('he');
        $this->assertRtlDirectionMarkers('he', $data);
        $this->assertTranslationKeysPresent('he', 'campaign_edit', 'error_messages', $data);
    }

    // L031: he × Banner edit × Date formats
    public function testL031_he_banner_edit_date_formats()
    {
        $data = $this->loadLocaleData('he');
        $this->assertRtlDirectionMarkers('he', $data);
        $this->assertTranslationKeysPresent('he', 'banner_edit', 'date_formats', $data);
        $this->assertDateFormatsLocaleAppropriate('he', $data);
    }

    // L032: he × Zone edit × Number formats
    public function testL032_he_zone_edit_number_formats()
    {
        $data = $this->loadLocaleData('he');
        $this->assertRtlDirectionMarkers('he', $data);
        $this->assertTranslationKeysPresent('he', 'zone_edit', 'number_formats', $data);
        $this->assertNumberFormatsPresent('he', $data);
    }

    // L033: he × Statistics × Labels
    public function testL033_he_statistics_labels()
    {
        $data = $this->loadLocaleData('he');
        $this->assertRtlDirectionMarkers('he', $data);
        $this->assertTranslationKeysPresent('he', 'statistics', 'labels', $data);
    }

    // L034: he × Settings × Error messages
    public function testL034_he_settings_error_messages()
    {
        $data = $this->loadLocaleData('he');
        $this->assertRtlDirectionMarkers('he', $data);
        $this->assertTranslationKeysPresent('he', 'settings', 'error_messages', $data);
    }

    // L035: he × User management × Labels
    public function testL035_he_user_management_labels()
    {
        $data = $this->loadLocaleData('he');
        $this->assertRtlDirectionMarkers('he', $data);
        $this->assertTranslationKeysPresent('he', 'user_management', 'labels', $data);
    }

    // L036: he × Dashboard × Error messages
    public function testL036_he_dashboard_error_messages()
    {
        $data = $this->loadLocaleData('he');
        $this->assertRtlDirectionMarkers('he', $data);
        $this->assertTranslationKeysPresent('he', 'dashboard', 'error_messages', $data);
    }

    // L037: he × Campaign edit × Labels
    public function testL037_he_campaign_edit_labels()
    {
        $data = $this->loadLocaleData('he');
        $this->assertRtlDirectionMarkers('he', $data);
        $this->assertTranslationKeysPresent('he', 'campaign_edit', 'labels', $data);
    }

    // L038: he × Banner edit × Labels
    public function testL038_he_banner_edit_labels()
    {
        $data = $this->loadLocaleData('he');
        $this->assertRtlDirectionMarkers('he', $data);
        $this->assertTranslationKeysPresent('he', 'banner_edit', 'labels', $data);
    }

    // L039: he × Zone edit × Labels
    public function testL039_he_zone_edit_labels()
    {
        $data = $this->loadLocaleData('he');
        $this->assertRtlDirectionMarkers('he', $data);
        $this->assertTranslationKeysPresent('he', 'zone_edit', 'labels', $data);
    }

    // L040: he × Statistics × Error messages
    public function testL040_he_statistics_error_messages()
    {
        $data = $this->loadLocaleData('he');
        $this->assertRtlDirectionMarkers('he', $data);
        $this->assertTranslationKeysPresent('he', 'statistics', 'error_messages', $data);
    }

    // L041: he × Settings × Labels
    public function testL041_he_settings_labels()
    {
        $data = $this->loadLocaleData('he');
        $this->assertRtlDirectionMarkers('he', $data);
        $this->assertTranslationKeysPresent('he', 'settings', 'labels', $data);
    }

    // L042: he × User management × Error messages
    public function testL042_he_user_management_error_messages()
    {
        $data = $this->loadLocaleData('he');
        $this->assertRtlDirectionMarkers('he', $data);
        $this->assertTranslationKeysPresent('he', 'user_management', 'error_messages', $data);
    }

    // L043: he × Dashboard × Date formats
    public function testL043_he_dashboard_date_formats()
    {
        $data = $this->loadLocaleData('he');
        $this->assertRtlDirectionMarkers('he', $data);
        $this->assertTranslationKeysPresent('he', 'dashboard', 'date_formats', $data);
        $this->assertDateFormatsLocaleAppropriate('he', $data);
    }

    // L044: he × Campaign edit × Date formats
    public function testL044_he_campaign_edit_date_formats()
    {
        $data = $this->loadLocaleData('he');
        $this->assertRtlDirectionMarkers('he', $data);
        $this->assertTranslationKeysPresent('he', 'campaign_edit', 'date_formats', $data);
        $this->assertDateFormatsLocaleAppropriate('he', $data);
    }

    // L045: he × Banner edit × Error messages
    public function testL045_he_banner_edit_error_messages()
    {
        $data = $this->loadLocaleData('he');
        $this->assertRtlDirectionMarkers('he', $data);
        $this->assertTranslationKeysPresent('he', 'banner_edit', 'error_messages', $data);
    }

    // L046: he × Zone edit × Error messages
    public function testL046_he_zone_edit_error_messages()
    {
        $data = $this->loadLocaleData('he');
        $this->assertRtlDirectionMarkers('he', $data);
        $this->assertTranslationKeysPresent('he', 'zone_edit', 'error_messages', $data);
    }

    // L047: he × Statistics × Date formats
    public function testL047_he_statistics_date_formats()
    {
        $data = $this->loadLocaleData('he');
        $this->assertRtlDirectionMarkers('he', $data);
        $this->assertTranslationKeysPresent('he', 'statistics', 'date_formats', $data);
        $this->assertDateFormatsLocaleAppropriate('he', $data);
    }

    // L048: he × Settings × Date formats
    public function testL048_he_settings_date_formats()
    {
        $data = $this->loadLocaleData('he');
        $this->assertRtlDirectionMarkers('he', $data);
        $this->assertTranslationKeysPresent('he', 'settings', 'date_formats', $data);
        $this->assertDateFormatsLocaleAppropriate('he', $data);
    }

    // L049: he × User management × Date formats
    public function testL049_he_user_management_date_formats()
    {
        $data = $this->loadLocaleData('he');
        $this->assertRtlDirectionMarkers('he', $data);
        $this->assertTranslationKeysPresent('he', 'user_management', 'date_formats', $data);
        $this->assertDateFormatsLocaleAppropriate('he', $data);
    }

    // L050: he × Dashboard × Number formats
    public function testL050_he_dashboard_number_formats()
    {
        $data = $this->loadLocaleData('he');
        $this->assertRtlDirectionMarkers('he', $data);
        $this->assertTranslationKeysPresent('he', 'dashboard', 'number_formats', $data);
        $this->assertNumberFormatsPresent('he', $data);
    }

    // L051: he × Campaign edit × Number formats
    public function testL051_he_campaign_edit_number_formats()
    {
        $data = $this->loadLocaleData('he');
        $this->assertRtlDirectionMarkers('he', $data);
        $this->assertTranslationKeysPresent('he', 'campaign_edit', 'number_formats', $data);
        $this->assertNumberFormatsPresent('he', $data);
    }

    // L052: he × Banner edit × Number formats
    public function testL052_he_banner_edit_number_formats()
    {
        $data = $this->loadLocaleData('he');
        $this->assertRtlDirectionMarkers('he', $data);
        $this->assertTranslationKeysPresent('he', 'banner_edit', 'number_formats', $data);
        $this->assertNumberFormatsPresent('he', $data);
    }

    // L053: he × Zone edit × Date formats
    public function testL053_he_zone_edit_date_formats()
    {
        $data = $this->loadLocaleData('he');
        $this->assertRtlDirectionMarkers('he', $data);
        $this->assertTranslationKeysPresent('he', 'zone_edit', 'date_formats', $data);
        $this->assertDateFormatsLocaleAppropriate('he', $data);
    }

    // L054: he × Statistics × Number formats
    public function testL054_he_statistics_number_formats()
    {
        $data = $this->loadLocaleData('he');
        $this->assertRtlDirectionMarkers('he', $data);
        $this->assertTranslationKeysPresent('he', 'statistics', 'number_formats', $data);
        $this->assertNumberFormatsPresent('he', $data);
    }

    // L055: he × Settings × Number formats
    public function testL055_he_settings_number_formats()
    {
        $data = $this->loadLocaleData('he');
        $this->assertRtlDirectionMarkers('he', $data);
        $this->assertTranslationKeysPresent('he', 'settings', 'number_formats', $data);
        $this->assertNumberFormatsPresent('he', $data);
    }

    // L056: he × User management × Number formats
    public function testL056_he_user_management_number_formats()
    {
        $data = $this->loadLocaleData('he');
        $this->assertRtlDirectionMarkers('he', $data);
        $this->assertTranslationKeysPresent('he', 'user_management', 'number_formats', $data);
        $this->assertNumberFormatsPresent('he', $data);
    }

    // --- Persian/Farsi (fa) — 28 tests ---

    // L057: fa × Dashboard × Labels
    public function testL057_fa_dashboard_labels()
    {
        $data = $this->loadLocaleData('fa');
        $this->assertRtlDirectionMarkers('fa', $data);
        $this->assertTranslationKeysPresent('fa', 'dashboard', 'labels', $data);
    }

    // L058: fa × Campaign edit × Error messages
    public function testL058_fa_campaign_edit_error_messages()
    {
        $data = $this->loadLocaleData('fa');
        $this->assertRtlDirectionMarkers('fa', $data);
        $this->assertTranslationKeysPresent('fa', 'campaign_edit', 'error_messages', $data);
    }

    // L059: fa × Banner edit × Date formats
    public function testL059_fa_banner_edit_date_formats()
    {
        $data = $this->loadLocaleData('fa');
        $this->assertRtlDirectionMarkers('fa', $data);
        $this->assertTranslationKeysPresent('fa', 'banner_edit', 'date_formats', $data);
        $this->assertDateFormatsLocaleAppropriate('fa', $data);
    }

    // L060: fa × Zone edit × Number formats
    public function testL060_fa_zone_edit_number_formats()
    {
        $data = $this->loadLocaleData('fa');
        $this->assertRtlDirectionMarkers('fa', $data);
        $this->assertTranslationKeysPresent('fa', 'zone_edit', 'number_formats', $data);
        $this->assertNumberFormatsPresent('fa', $data);
    }

    // L061: fa × Statistics × Labels
    public function testL061_fa_statistics_labels()
    {
        $data = $this->loadLocaleData('fa');
        $this->assertRtlDirectionMarkers('fa', $data);
        $this->assertTranslationKeysPresent('fa', 'statistics', 'labels', $data);
    }

    // L062: fa × Settings × Error messages
    public function testL062_fa_settings_error_messages()
    {
        $data = $this->loadLocaleData('fa');
        $this->assertRtlDirectionMarkers('fa', $data);
        $this->assertTranslationKeysPresent('fa', 'settings', 'error_messages', $data);
    }

    // L063: fa × User management × Labels
    public function testL063_fa_user_management_labels()
    {
        $data = $this->loadLocaleData('fa');
        $this->assertRtlDirectionMarkers('fa', $data);
        $this->assertTranslationKeysPresent('fa', 'user_management', 'labels', $data);
    }

    // L064: fa × Dashboard × Error messages
    public function testL064_fa_dashboard_error_messages()
    {
        $data = $this->loadLocaleData('fa');
        $this->assertRtlDirectionMarkers('fa', $data);
        $this->assertTranslationKeysPresent('fa', 'dashboard', 'error_messages', $data);
    }

    // L065: fa × Campaign edit × Labels
    public function testL065_fa_campaign_edit_labels()
    {
        $data = $this->loadLocaleData('fa');
        $this->assertRtlDirectionMarkers('fa', $data);
        $this->assertTranslationKeysPresent('fa', 'campaign_edit', 'labels', $data);
    }

    // L066: fa × Banner edit × Labels
    public function testL066_fa_banner_edit_labels()
    {
        $data = $this->loadLocaleData('fa');
        $this->assertRtlDirectionMarkers('fa', $data);
        $this->assertTranslationKeysPresent('fa', 'banner_edit', 'labels', $data);
    }

    // L067: fa × Zone edit × Labels
    public function testL067_fa_zone_edit_labels()
    {
        $data = $this->loadLocaleData('fa');
        $this->assertRtlDirectionMarkers('fa', $data);
        $this->assertTranslationKeysPresent('fa', 'zone_edit', 'labels', $data);
    }

    // L068: fa × Statistics × Error messages
    public function testL068_fa_statistics_error_messages()
    {
        $data = $this->loadLocaleData('fa');
        $this->assertRtlDirectionMarkers('fa', $data);
        $this->assertTranslationKeysPresent('fa', 'statistics', 'error_messages', $data);
    }

    // L069: fa × Settings × Labels
    public function testL069_fa_settings_labels()
    {
        $data = $this->loadLocaleData('fa');
        $this->assertRtlDirectionMarkers('fa', $data);
        $this->assertTranslationKeysPresent('fa', 'settings', 'labels', $data);
    }

    // L070: fa × User management × Error messages
    public function testL070_fa_user_management_error_messages()
    {
        $data = $this->loadLocaleData('fa');
        $this->assertRtlDirectionMarkers('fa', $data);
        $this->assertTranslationKeysPresent('fa', 'user_management', 'error_messages', $data);
    }

    // L071: fa × Dashboard × Date formats
    public function testL071_fa_dashboard_date_formats()
    {
        $data = $this->loadLocaleData('fa');
        $this->assertRtlDirectionMarkers('fa', $data);
        $this->assertTranslationKeysPresent('fa', 'dashboard', 'date_formats', $data);
        $this->assertDateFormatsLocaleAppropriate('fa', $data);
    }

    // L072: fa × Campaign edit × Date formats
    public function testL072_fa_campaign_edit_date_formats()
    {
        $data = $this->loadLocaleData('fa');
        $this->assertRtlDirectionMarkers('fa', $data);
        $this->assertTranslationKeysPresent('fa', 'campaign_edit', 'date_formats', $data);
        $this->assertDateFormatsLocaleAppropriate('fa', $data);
    }

    // L073: fa × Banner edit × Error messages
    public function testL073_fa_banner_edit_error_messages()
    {
        $data = $this->loadLocaleData('fa');
        $this->assertRtlDirectionMarkers('fa', $data);
        $this->assertTranslationKeysPresent('fa', 'banner_edit', 'error_messages', $data);
    }

    // L074: fa × Zone edit × Error messages
    public function testL074_fa_zone_edit_error_messages()
    {
        $data = $this->loadLocaleData('fa');
        $this->assertRtlDirectionMarkers('fa', $data);
        $this->assertTranslationKeysPresent('fa', 'zone_edit', 'error_messages', $data);
    }

    // L075: fa × Statistics × Date formats
    public function testL075_fa_statistics_date_formats()
    {
        $data = $this->loadLocaleData('fa');
        $this->assertRtlDirectionMarkers('fa', $data);
        $this->assertTranslationKeysPresent('fa', 'statistics', 'date_formats', $data);
        $this->assertDateFormatsLocaleAppropriate('fa', $data);
    }

    // L076: fa × Settings × Date formats
    public function testL076_fa_settings_date_formats()
    {
        $data = $this->loadLocaleData('fa');
        $this->assertRtlDirectionMarkers('fa', $data);
        $this->assertTranslationKeysPresent('fa', 'settings', 'date_formats', $data);
        $this->assertDateFormatsLocaleAppropriate('fa', $data);
    }

    // L077: fa × User management × Date formats
    public function testL077_fa_user_management_date_formats()
    {
        $data = $this->loadLocaleData('fa');
        $this->assertRtlDirectionMarkers('fa', $data);
        $this->assertTranslationKeysPresent('fa', 'user_management', 'date_formats', $data);
        $this->assertDateFormatsLocaleAppropriate('fa', $data);
    }

    // L078: fa × Dashboard × Number formats
    public function testL078_fa_dashboard_number_formats()
    {
        $data = $this->loadLocaleData('fa');
        $this->assertRtlDirectionMarkers('fa', $data);
        $this->assertTranslationKeysPresent('fa', 'dashboard', 'number_formats', $data);
        $this->assertNumberFormatsPresent('fa', $data);
    }

    // L079: fa × Campaign edit × Number formats
    public function testL079_fa_campaign_edit_number_formats()
    {
        $data = $this->loadLocaleData('fa');
        $this->assertRtlDirectionMarkers('fa', $data);
        $this->assertTranslationKeysPresent('fa', 'campaign_edit', 'number_formats', $data);
        $this->assertNumberFormatsPresent('fa', $data);
    }

    // L080: fa × Banner edit × Number formats
    public function testL080_fa_banner_edit_number_formats()
    {
        $data = $this->loadLocaleData('fa');
        $this->assertRtlDirectionMarkers('fa', $data);
        $this->assertTranslationKeysPresent('fa', 'banner_edit', 'number_formats', $data);
        $this->assertNumberFormatsPresent('fa', $data);
    }

    // L081: fa × Zone edit × Date formats
    public function testL081_fa_zone_edit_date_formats()
    {
        $data = $this->loadLocaleData('fa');
        $this->assertRtlDirectionMarkers('fa', $data);
        $this->assertTranslationKeysPresent('fa', 'zone_edit', 'date_formats', $data);
        $this->assertDateFormatsLocaleAppropriate('fa', $data);
    }

    // L082: fa × Statistics × Number formats
    public function testL082_fa_statistics_number_formats()
    {
        $data = $this->loadLocaleData('fa');
        $this->assertRtlDirectionMarkers('fa', $data);
        $this->assertTranslationKeysPresent('fa', 'statistics', 'number_formats', $data);
        $this->assertNumberFormatsPresent('fa', $data);
    }

    // L083: fa × Settings × Number formats
    public function testL083_fa_settings_number_formats()
    {
        $data = $this->loadLocaleData('fa');
        $this->assertRtlDirectionMarkers('fa', $data);
        $this->assertTranslationKeysPresent('fa', 'settings', 'number_formats', $data);
        $this->assertNumberFormatsPresent('fa', $data);
    }

    // L084: fa × User management × Number formats
    public function testL084_fa_user_management_number_formats()
    {
        $data = $this->loadLocaleData('fa');
        $this->assertRtlDirectionMarkers('fa', $data);
        $this->assertTranslationKeysPresent('fa', 'user_management', 'number_formats', $data);
        $this->assertNumberFormatsPresent('fa', $data);
    }

    // =========================================================================
    // LTR SAMPLED TESTS: 5 locales × 7 pages × 2 content types = 70 tests
    // =========================================================================

    /**
     * Assert that an LTR locale does NOT have RTL direction markers.
     *
     * @param string $locale     Locale code
     * @param array  $localeData Loaded locale data
     */
    private function assertLtrDirectionMarkers($locale, $localeData)
    {
        $direction = $localeData['phpAds_TextDirection'] ?? 'ltr';
        $this->assertEqual(
            $direction,
            'ltr',
            "[$locale] Expected text direction 'ltr' but got '$direction'"
        );
    }

    // --- English (en) — Baseline / reference — 14 tests ---

    // L-en-01: en × Dashboard × Labels
    public function testLen01_en_dashboard_labels()
    {
        $data = $this->loadLocaleData('en');
        $this->assertLtrDirectionMarkers('en', $data);
        $this->assertTranslationKeysPresent('en', 'dashboard', 'labels', $data);
    }

    // L-en-02: en × Dashboard × Error messages
    public function testLen02_en_dashboard_error_messages()
    {
        $data = $this->loadLocaleData('en');
        $this->assertLtrDirectionMarkers('en', $data);
        $this->assertTranslationKeysPresent('en', 'dashboard', 'error_messages', $data);
    }

    // L-en-03: en × Campaign edit × Labels
    public function testLen03_en_campaign_edit_labels()
    {
        $data = $this->loadLocaleData('en');
        $this->assertLtrDirectionMarkers('en', $data);
        $this->assertTranslationKeysPresent('en', 'campaign_edit', 'labels', $data);
    }

    // L-en-04: en × Campaign edit × Error messages
    public function testLen04_en_campaign_edit_error_messages()
    {
        $data = $this->loadLocaleData('en');
        $this->assertLtrDirectionMarkers('en', $data);
        $this->assertTranslationKeysPresent('en', 'campaign_edit', 'error_messages', $data);
    }

    // L-en-05: en × Banner edit × Labels
    public function testLen05_en_banner_edit_labels()
    {
        $data = $this->loadLocaleData('en');
        $this->assertLtrDirectionMarkers('en', $data);
        $this->assertTranslationKeysPresent('en', 'banner_edit', 'labels', $data);
    }

    // L-en-06: en × Banner edit × Error messages
    public function testLen06_en_banner_edit_error_messages()
    {
        $data = $this->loadLocaleData('en');
        $this->assertLtrDirectionMarkers('en', $data);
        $this->assertTranslationKeysPresent('en', 'banner_edit', 'error_messages', $data);
    }

    // L-en-07: en × Zone edit × Labels
    public function testLen07_en_zone_edit_labels()
    {
        $data = $this->loadLocaleData('en');
        $this->assertLtrDirectionMarkers('en', $data);
        $this->assertTranslationKeysPresent('en', 'zone_edit', 'labels', $data);
    }

    // L-en-08: en × Zone edit × Error messages
    public function testLen08_en_zone_edit_error_messages()
    {
        $data = $this->loadLocaleData('en');
        $this->assertLtrDirectionMarkers('en', $data);
        $this->assertTranslationKeysPresent('en', 'zone_edit', 'error_messages', $data);
    }

    // L-en-09: en × Statistics × Labels
    public function testLen09_en_statistics_labels()
    {
        $data = $this->loadLocaleData('en');
        $this->assertLtrDirectionMarkers('en', $data);
        $this->assertTranslationKeysPresent('en', 'statistics', 'labels', $data);
    }

    // L-en-10: en × Statistics × Error messages
    public function testLen10_en_statistics_error_messages()
    {
        $data = $this->loadLocaleData('en');
        $this->assertLtrDirectionMarkers('en', $data);
        $this->assertTranslationKeysPresent('en', 'statistics', 'error_messages', $data);
    }

    // L-en-11: en × Settings × Labels
    public function testLen11_en_settings_labels()
    {
        $data = $this->loadLocaleData('en');
        $this->assertLtrDirectionMarkers('en', $data);
        $this->assertTranslationKeysPresent('en', 'settings', 'labels', $data);
    }

    // L-en-12: en × Settings × Error messages
    public function testLen12_en_settings_error_messages()
    {
        $data = $this->loadLocaleData('en');
        $this->assertLtrDirectionMarkers('en', $data);
        $this->assertTranslationKeysPresent('en', 'settings', 'error_messages', $data);
    }

    // L-en-13: en × User management × Labels
    public function testLen13_en_user_management_labels()
    {
        $data = $this->loadLocaleData('en');
        $this->assertLtrDirectionMarkers('en', $data);
        $this->assertTranslationKeysPresent('en', 'user_management', 'labels', $data);
    }

    // L-en-14: en × User management × Error messages
    public function testLen14_en_user_management_error_messages()
    {
        $data = $this->loadLocaleData('en');
        $this->assertLtrDirectionMarkers('en', $data);
        $this->assertTranslationKeysPresent('en', 'user_management', 'error_messages', $data);
    }

    // --- German (de) — Latin script, long compound words — 14 tests ---

    // L-de-01: de × Dashboard × Labels
    public function testLde01_de_dashboard_labels()
    {
        $data = $this->loadLocaleData('de');
        $this->assertLtrDirectionMarkers('de', $data);
        $this->assertTranslationKeysPresent('de', 'dashboard', 'labels', $data);
    }

    // L-de-02: de × Dashboard × Error messages
    public function testLde02_de_dashboard_error_messages()
    {
        $data = $this->loadLocaleData('de');
        $this->assertLtrDirectionMarkers('de', $data);
        $this->assertTranslationKeysPresent('de', 'dashboard', 'error_messages', $data);
    }

    // L-de-03: de × Campaign edit × Labels
    public function testLde03_de_campaign_edit_labels()
    {
        $data = $this->loadLocaleData('de');
        $this->assertLtrDirectionMarkers('de', $data);
        $this->assertTranslationKeysPresent('de', 'campaign_edit', 'labels', $data);
    }

    // L-de-04: de × Campaign edit × Error messages
    public function testLde04_de_campaign_edit_error_messages()
    {
        $data = $this->loadLocaleData('de');
        $this->assertLtrDirectionMarkers('de', $data);
        $this->assertTranslationKeysPresent('de', 'campaign_edit', 'error_messages', $data);
    }

    // L-de-05: de × Banner edit × Labels
    public function testLde05_de_banner_edit_labels()
    {
        $data = $this->loadLocaleData('de');
        $this->assertLtrDirectionMarkers('de', $data);
        $this->assertTranslationKeysPresent('de', 'banner_edit', 'labels', $data);
    }

    // L-de-06: de × Banner edit × Error messages
    public function testLde06_de_banner_edit_error_messages()
    {
        $data = $this->loadLocaleData('de');
        $this->assertLtrDirectionMarkers('de', $data);
        $this->assertTranslationKeysPresent('de', 'banner_edit', 'error_messages', $data);
    }

    // L-de-07: de × Zone edit × Labels
    public function testLde07_de_zone_edit_labels()
    {
        $data = $this->loadLocaleData('de');
        $this->assertLtrDirectionMarkers('de', $data);
        $this->assertTranslationKeysPresent('de', 'zone_edit', 'labels', $data);
    }

    // L-de-08: de × Zone edit × Error messages
    public function testLde08_de_zone_edit_error_messages()
    {
        $data = $this->loadLocaleData('de');
        $this->assertLtrDirectionMarkers('de', $data);
        $this->assertTranslationKeysPresent('de', 'zone_edit', 'error_messages', $data);
    }

    // L-de-09: de × Statistics × Labels
    public function testLde09_de_statistics_labels()
    {
        $data = $this->loadLocaleData('de');
        $this->assertLtrDirectionMarkers('de', $data);
        $this->assertTranslationKeysPresent('de', 'statistics', 'labels', $data);
    }

    // L-de-10: de × Statistics × Error messages
    public function testLde10_de_statistics_error_messages()
    {
        $data = $this->loadLocaleData('de');
        $this->assertLtrDirectionMarkers('de', $data);
        $this->assertTranslationKeysPresent('de', 'statistics', 'error_messages', $data);
    }

    // L-de-11: de × Settings × Labels
    public function testLde11_de_settings_labels()
    {
        $data = $this->loadLocaleData('de');
        $this->assertLtrDirectionMarkers('de', $data);
        $this->assertTranslationKeysPresent('de', 'settings', 'labels', $data);
    }

    // L-de-12: de × Settings × Error messages
    public function testLde12_de_settings_error_messages()
    {
        $data = $this->loadLocaleData('de');
        $this->assertLtrDirectionMarkers('de', $data);
        $this->assertTranslationKeysPresent('de', 'settings', 'error_messages', $data);
    }

    // L-de-13: de × User management × Labels
    public function testLde13_de_user_management_labels()
    {
        $data = $this->loadLocaleData('de');
        $this->assertLtrDirectionMarkers('de', $data);
        $this->assertTranslationKeysPresent('de', 'user_management', 'labels', $data);
    }

    // L-de-14: de × User management × Error messages
    public function testLde14_de_user_management_error_messages()
    {
        $data = $this->loadLocaleData('de');
        $this->assertLtrDirectionMarkers('de', $data);
        $this->assertTranslationKeysPresent('de', 'user_management', 'error_messages', $data);
    }

    // --- Japanese (ja) — CJK double-width characters — 14 tests ---

    // L-ja-01: ja × Dashboard × Labels
    public function testLja01_ja_dashboard_labels()
    {
        $data = $this->loadLocaleData('ja');
        $this->assertLtrDirectionMarkers('ja', $data);
        $this->assertTranslationKeysPresent('ja', 'dashboard', 'labels', $data);
    }

    // L-ja-02: ja × Dashboard × Error messages
    public function testLja02_ja_dashboard_error_messages()
    {
        $data = $this->loadLocaleData('ja');
        $this->assertLtrDirectionMarkers('ja', $data);
        $this->assertTranslationKeysPresent('ja', 'dashboard', 'error_messages', $data);
    }

    // L-ja-03: ja × Campaign edit × Labels
    public function testLja03_ja_campaign_edit_labels()
    {
        $data = $this->loadLocaleData('ja');
        $this->assertLtrDirectionMarkers('ja', $data);
        $this->assertTranslationKeysPresent('ja', 'campaign_edit', 'labels', $data);
    }

    // L-ja-04: ja × Campaign edit × Error messages
    public function testLja04_ja_campaign_edit_error_messages()
    {
        $data = $this->loadLocaleData('ja');
        $this->assertLtrDirectionMarkers('ja', $data);
        $this->assertTranslationKeysPresent('ja', 'campaign_edit', 'error_messages', $data);
    }

    // L-ja-05: ja × Banner edit × Labels
    public function testLja05_ja_banner_edit_labels()
    {
        $data = $this->loadLocaleData('ja');
        $this->assertLtrDirectionMarkers('ja', $data);
        $this->assertTranslationKeysPresent('ja', 'banner_edit', 'labels', $data);
    }

    // L-ja-06: ja × Banner edit × Error messages
    public function testLja06_ja_banner_edit_error_messages()
    {
        $data = $this->loadLocaleData('ja');
        $this->assertLtrDirectionMarkers('ja', $data);
        $this->assertTranslationKeysPresent('ja', 'banner_edit', 'error_messages', $data);
    }

    // L-ja-07: ja × Zone edit × Labels
    public function testLja07_ja_zone_edit_labels()
    {
        $data = $this->loadLocaleData('ja');
        $this->assertLtrDirectionMarkers('ja', $data);
        $this->assertTranslationKeysPresent('ja', 'zone_edit', 'labels', $data);
    }

    // L-ja-08: ja × Zone edit × Error messages
    public function testLja08_ja_zone_edit_error_messages()
    {
        $data = $this->loadLocaleData('ja');
        $this->assertLtrDirectionMarkers('ja', $data);
        $this->assertTranslationKeysPresent('ja', 'zone_edit', 'error_messages', $data);
    }

    // L-ja-09: ja × Statistics × Labels
    public function testLja09_ja_statistics_labels()
    {
        $data = $this->loadLocaleData('ja');
        $this->assertLtrDirectionMarkers('ja', $data);
        $this->assertTranslationKeysPresent('ja', 'statistics', 'labels', $data);
    }

    // L-ja-10: ja × Statistics × Error messages
    public function testLja10_ja_statistics_error_messages()
    {
        $data = $this->loadLocaleData('ja');
        $this->assertLtrDirectionMarkers('ja', $data);
        $this->assertTranslationKeysPresent('ja', 'statistics', 'error_messages', $data);
    }

    // L-ja-11: ja × Settings × Labels
    public function testLja11_ja_settings_labels()
    {
        $data = $this->loadLocaleData('ja');
        $this->assertLtrDirectionMarkers('ja', $data);
        $this->assertTranslationKeysPresent('ja', 'settings', 'labels', $data);
    }

    // L-ja-12: ja × Settings × Error messages
    public function testLja12_ja_settings_error_messages()
    {
        $data = $this->loadLocaleData('ja');
        $this->assertLtrDirectionMarkers('ja', $data);
        $this->assertTranslationKeysPresent('ja', 'settings', 'error_messages', $data);
    }

    // L-ja-13: ja × User management × Labels
    public function testLja13_ja_user_management_labels()
    {
        $data = $this->loadLocaleData('ja');
        $this->assertLtrDirectionMarkers('ja', $data);
        $this->assertTranslationKeysPresent('ja', 'user_management', 'labels', $data);
    }

    // L-ja-14: ja × User management × Error messages
    public function testLja14_ja_user_management_error_messages()
    {
        $data = $this->loadLocaleData('ja');
        $this->assertLtrDirectionMarkers('ja', $data);
        $this->assertTranslationKeysPresent('ja', 'user_management', 'error_messages', $data);
    }

    // --- Korean (ko) — CJK alternate script — 14 tests ---

    // L-ko-01: ko × Dashboard × Labels
    public function testLko01_ko_dashboard_labels()
    {
        $data = $this->loadLocaleData('ko');
        $this->assertLtrDirectionMarkers('ko', $data);
        $this->assertTranslationKeysPresent('ko', 'dashboard', 'labels', $data);
    }

    // L-ko-02: ko × Dashboard × Error messages
    public function testLko02_ko_dashboard_error_messages()
    {
        $data = $this->loadLocaleData('ko');
        $this->assertLtrDirectionMarkers('ko', $data);
        $this->assertTranslationKeysPresent('ko', 'dashboard', 'error_messages', $data);
    }

    // L-ko-03: ko × Campaign edit × Labels
    public function testLko03_ko_campaign_edit_labels()
    {
        $data = $this->loadLocaleData('ko');
        $this->assertLtrDirectionMarkers('ko', $data);
        $this->assertTranslationKeysPresent('ko', 'campaign_edit', 'labels', $data);
    }

    // L-ko-04: ko × Campaign edit × Error messages
    public function testLko04_ko_campaign_edit_error_messages()
    {
        $data = $this->loadLocaleData('ko');
        $this->assertLtrDirectionMarkers('ko', $data);
        $this->assertTranslationKeysPresent('ko', 'campaign_edit', 'error_messages', $data);
    }

    // L-ko-05: ko × Banner edit × Labels
    public function testLko05_ko_banner_edit_labels()
    {
        $data = $this->loadLocaleData('ko');
        $this->assertLtrDirectionMarkers('ko', $data);
        $this->assertTranslationKeysPresent('ko', 'banner_edit', 'labels', $data);
    }

    // L-ko-06: ko × Banner edit × Error messages
    public function testLko06_ko_banner_edit_error_messages()
    {
        $data = $this->loadLocaleData('ko');
        $this->assertLtrDirectionMarkers('ko', $data);
        $this->assertTranslationKeysPresent('ko', 'banner_edit', 'error_messages', $data);
    }

    // L-ko-07: ko × Zone edit × Labels
    public function testLko07_ko_zone_edit_labels()
    {
        $data = $this->loadLocaleData('ko');
        $this->assertLtrDirectionMarkers('ko', $data);
        $this->assertTranslationKeysPresent('ko', 'zone_edit', 'labels', $data);
    }

    // L-ko-08: ko × Zone edit × Error messages
    public function testLko08_ko_zone_edit_error_messages()
    {
        $data = $this->loadLocaleData('ko');
        $this->assertLtrDirectionMarkers('ko', $data);
        $this->assertTranslationKeysPresent('ko', 'zone_edit', 'error_messages', $data);
    }

    // L-ko-09: ko × Statistics × Labels
    public function testLko09_ko_statistics_labels()
    {
        $data = $this->loadLocaleData('ko');
        $this->assertLtrDirectionMarkers('ko', $data);
        $this->assertTranslationKeysPresent('ko', 'statistics', 'labels', $data);
    }

    // L-ko-10: ko × Statistics × Error messages
    public function testLko10_ko_statistics_error_messages()
    {
        $data = $this->loadLocaleData('ko');
        $this->assertLtrDirectionMarkers('ko', $data);
        $this->assertTranslationKeysPresent('ko', 'statistics', 'error_messages', $data);
    }

    // L-ko-11: ko × Settings × Labels
    public function testLko11_ko_settings_labels()
    {
        $data = $this->loadLocaleData('ko');
        $this->assertLtrDirectionMarkers('ko', $data);
        $this->assertTranslationKeysPresent('ko', 'settings', 'labels', $data);
    }

    // L-ko-12: ko × Settings × Error messages
    public function testLko12_ko_settings_error_messages()
    {
        $data = $this->loadLocaleData('ko');
        $this->assertLtrDirectionMarkers('ko', $data);
        $this->assertTranslationKeysPresent('ko', 'settings', 'error_messages', $data);
    }

    // L-ko-13: ko × User management × Labels
    public function testLko13_ko_user_management_labels()
    {
        $data = $this->loadLocaleData('ko');
        $this->assertLtrDirectionMarkers('ko', $data);
        $this->assertTranslationKeysPresent('ko', 'user_management', 'labels', $data);
    }

    // L-ko-14: ko × User management × Error messages
    public function testLko14_ko_user_management_error_messages()
    {
        $data = $this->loadLocaleData('ko');
        $this->assertLtrDirectionMarkers('ko', $data);
        $this->assertTranslationKeysPresent('ko', 'user_management', 'error_messages', $data);
    }

    // --- Brazilian Portuguese (pt_BR) — Latin with accents, date format differences — 14 tests ---

    // L-pt_BR-01: pt_BR × Dashboard × Labels
    public function testLptBR01_pt_BR_dashboard_labels()
    {
        $data = $this->loadLocaleData('pt_BR');
        $this->assertLtrDirectionMarkers('pt_BR', $data);
        $this->assertTranslationKeysPresent('pt_BR', 'dashboard', 'labels', $data);
    }

    // L-pt_BR-02: pt_BR × Dashboard × Error messages
    public function testLptBR02_pt_BR_dashboard_error_messages()
    {
        $data = $this->loadLocaleData('pt_BR');
        $this->assertLtrDirectionMarkers('pt_BR', $data);
        $this->assertTranslationKeysPresent('pt_BR', 'dashboard', 'error_messages', $data);
    }

    // L-pt_BR-03: pt_BR × Campaign edit × Labels
    public function testLptBR03_pt_BR_campaign_edit_labels()
    {
        $data = $this->loadLocaleData('pt_BR');
        $this->assertLtrDirectionMarkers('pt_BR', $data);
        $this->assertTranslationKeysPresent('pt_BR', 'campaign_edit', 'labels', $data);
    }

    // L-pt_BR-04: pt_BR × Campaign edit × Error messages
    public function testLptBR04_pt_BR_campaign_edit_error_messages()
    {
        $data = $this->loadLocaleData('pt_BR');
        $this->assertLtrDirectionMarkers('pt_BR', $data);
        $this->assertTranslationKeysPresent('pt_BR', 'campaign_edit', 'error_messages', $data);
    }

    // L-pt_BR-05: pt_BR × Banner edit × Labels
    public function testLptBR05_pt_BR_banner_edit_labels()
    {
        $data = $this->loadLocaleData('pt_BR');
        $this->assertLtrDirectionMarkers('pt_BR', $data);
        $this->assertTranslationKeysPresent('pt_BR', 'banner_edit', 'labels', $data);
    }

    // L-pt_BR-06: pt_BR × Banner edit × Error messages
    public function testLptBR06_pt_BR_banner_edit_error_messages()
    {
        $data = $this->loadLocaleData('pt_BR');
        $this->assertLtrDirectionMarkers('pt_BR', $data);
        $this->assertTranslationKeysPresent('pt_BR', 'banner_edit', 'error_messages', $data);
    }

    // L-pt_BR-07: pt_BR × Zone edit × Labels
    public function testLptBR07_pt_BR_zone_edit_labels()
    {
        $data = $this->loadLocaleData('pt_BR');
        $this->assertLtrDirectionMarkers('pt_BR', $data);
        $this->assertTranslationKeysPresent('pt_BR', 'zone_edit', 'labels', $data);
    }

    // L-pt_BR-08: pt_BR × Zone edit × Error messages
    public function testLptBR08_pt_BR_zone_edit_error_messages()
    {
        $data = $this->loadLocaleData('pt_BR');
        $this->assertLtrDirectionMarkers('pt_BR', $data);
        $this->assertTranslationKeysPresent('pt_BR', 'zone_edit', 'error_messages', $data);
    }

    // L-pt_BR-09: pt_BR × Statistics × Labels
    public function testLptBR09_pt_BR_statistics_labels()
    {
        $data = $this->loadLocaleData('pt_BR');
        $this->assertLtrDirectionMarkers('pt_BR', $data);
        $this->assertTranslationKeysPresent('pt_BR', 'statistics', 'labels', $data);
    }

    // L-pt_BR-10: pt_BR × Statistics × Error messages
    public function testLptBR10_pt_BR_statistics_error_messages()
    {
        $data = $this->loadLocaleData('pt_BR');
        $this->assertLtrDirectionMarkers('pt_BR', $data);
        $this->assertTranslationKeysPresent('pt_BR', 'statistics', 'error_messages', $data);
    }

    // L-pt_BR-11: pt_BR × Settings × Labels
    public function testLptBR11_pt_BR_settings_labels()
    {
        $data = $this->loadLocaleData('pt_BR');
        $this->assertLtrDirectionMarkers('pt_BR', $data);
        $this->assertTranslationKeysPresent('pt_BR', 'settings', 'labels', $data);
    }

    // L-pt_BR-12: pt_BR × Settings × Error messages
    public function testLptBR12_pt_BR_settings_error_messages()
    {
        $data = $this->loadLocaleData('pt_BR');
        $this->assertLtrDirectionMarkers('pt_BR', $data);
        $this->assertTranslationKeysPresent('pt_BR', 'settings', 'error_messages', $data);
    }

    // L-pt_BR-13: pt_BR × User management × Labels
    public function testLptBR13_pt_BR_user_management_labels()
    {
        $data = $this->loadLocaleData('pt_BR');
        $this->assertLtrDirectionMarkers('pt_BR', $data);
        $this->assertTranslationKeysPresent('pt_BR', 'user_management', 'labels', $data);
    }

    // L-pt_BR-14: pt_BR × User management × Error messages
    public function testLptBR14_pt_BR_user_management_error_messages()
    {
        $data = $this->loadLocaleData('pt_BR');
        $this->assertLtrDirectionMarkers('pt_BR', $data);
        $this->assertTranslationKeysPresent('pt_BR', 'user_management', 'error_messages', $data);
    }

    // =========================================================================
    // CROSS-LOCALE VALIDATION TESTS
    // =========================================================================

    /**
     * Verify all RTL locales have RTL direction set in their language files.
     */
    public function testAllRtlLocalesHaveCorrectDirection()
    {
        foreach (self::$rtlLocales as $locale) {
            $data = $this->loadLocaleData($locale);
            $this->assertRtlDirectionMarkers($locale, $data);
        }
    }

    /**
     * Verify all sampled LTR locales have LTR direction (or no direction set,
     * defaulting to LTR).
     */
    public function testAllLtrLocalesHaveCorrectDirection()
    {
        foreach (self::$ltrLocales as $locale) {
            $data = $this->loadLocaleData($locale);
            $this->assertLtrDirectionMarkers($locale, $data);
        }
    }

    /**
     * Verify that RTL locale translations differ from English (i.e. they
     * are actually translated and not just copies of the English strings).
     */
    public function testRtlTranslationsDifferFromEnglish()
    {
        $enData = $this->loadLocaleData('en');

        foreach (self::$rtlLocales as $locale) {
            $localeData = $this->loadLocaleData($locale);
            $diffCount = 0;
            $totalChecked = 0;

            foreach (['strHome', 'strHelp', 'strSave', 'strCancel', 'strDelete',
                       'strYes', 'strNo', 'strLogin', 'strLogout', 'strPassword'] as $key) {
                if (isset($localeData[$key]) && isset($enData[$key])) {
                    $totalChecked++;
                    if ($localeData[$key] !== $enData[$key]) {
                        $diffCount++;
                    }
                }
            }

            $this->assertTrue(
                $diffCount > 0,
                "[$locale] RTL locale should have at least some translations that differ from English (checked $totalChecked keys, found $diffCount differences)"
            );
        }
    }

    /**
     * Verify that all locales have the required locale file (default.lang.php)
     * present in the filesystem.
     */
    public function testAllLocaleFilesExist()
    {
        $allLocales = array_merge(self::$rtlLocales, self::$ltrLocales);
        foreach ($allLocales as $locale) {
            $filePath = MAX_PATH . '/lib/max/language/' . $locale . '/default.lang.php';
            $this->assertTrue(
                file_exists($filePath),
                "[$locale] Language file does not exist: $filePath"
            );
        }
    }

    /**
     * Verify that all RTL locales have properly swapped text alignment values.
     * In RTL, "right" should map to "left" and "left" to "right".
     */
    public function testRtlTextAlignmentSwap()
    {
        foreach (self::$rtlLocales as $locale) {
            $data = $this->loadLocaleData($locale);
            $this->assertEqual(
                $data['phpAds_TextAlignRight'],
                'left',
                "[$locale] RTL locale should swap TextAlignRight to 'left'"
            );
            $this->assertEqual(
                $data['phpAds_TextAlignLeft'],
                'right',
                "[$locale] RTL locale should swap TextAlignLeft to 'right'"
            );
        }
    }
}
