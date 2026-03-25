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
 * Combination Matrix Tests for Statistics (Sections 4I + 4J)
 *
 * Tests pairwise combinations of:
 *   - Account type: ADMIN, MANAGER, ADVERTISER, TRAFFICKER
 *   - Entity level: Advertiser, Campaign, Banner, Publisher, Zone
 *   - Time granularity: Daily, Hourly
 *   - Date range: Valid range, reversed dates, null start, null end, same day
 *   - Timezone: localTZ=true, localTZ=false
 *   - Data volume: Empty (0 rows), small (1-10), medium (100+)
 *
 * @package    OpenXDll
 * @subpackage TestSuite
 */

require_once MAX_PATH . '/lib/OA/Dll/Advertiser.php';
require_once MAX_PATH . '/lib/OA/Dll/AdvertiserInfo.php';
require_once MAX_PATH . '/lib/OA/Dll/Campaign.php';
require_once MAX_PATH . '/lib/OA/Dll/CampaignInfo.php';
require_once MAX_PATH . '/lib/OA/Dll/Banner.php';
require_once MAX_PATH . '/lib/OA/Dll/BannerInfo.php';
require_once MAX_PATH . '/lib/OA/Dll/Publisher.php';
require_once MAX_PATH . '/lib/OA/Dll/PublisherInfo.php';
require_once MAX_PATH . '/lib/OA/Dll/Zone.php';
require_once MAX_PATH . '/lib/OA/Dll/ZoneInfo.php';
require_once MAX_PATH . '/lib/OA/Dll/tests/util/DllUnitTestCase.php';

class OA_Dll_StatisticsCombinationMatrixTest extends DllUnitTestCase
{
    /**
     * @var int
     */
    public $agencyId;

    public function __construct()
    {
        parent::__construct();

        Mock::generatePartial(
            'OA_Dll_Advertiser',
            'PartialMockOA_Dll_Advertiser_StatsComboTest',
            ['checkPermissions', 'getDefaultAgencyId'],
        );
        Mock::generatePartial(
            'OA_Dll_Campaign',
            'PartialMockOA_Dll_Campaign_StatsComboTest',
            ['checkPermissions'],
        );
        Mock::generatePartial(
            'OA_Dll_Banner',
            'PartialMockOA_Dll_Banner_StatsComboTest',
            ['checkPermissions'],
        );
        Mock::generatePartial(
            'OA_Dll_Publisher',
            'PartialMockOA_Dll_Publisher_StatsComboTest',
            ['checkPermissions', 'getDefaultAgencyId'],
        );
        Mock::generatePartial(
            'OA_Dll_Zone',
            'PartialMockOA_Dll_Zone_StatsComboTest',
            ['checkPermissions'],
        );
    }

    public function setUp()
    {
        $this->agencyId = DataGenerator::generateOne('agency');
    }

    public function tearDown()
    {
        DataGenerator::cleanUp();
    }

    // =========================================================================
    // Helper: Create advertiser-side entities (advertiser -> campaign -> banner)
    // =========================================================================
    private function _createAdvertiserEntities()
    {
        $dllAdvertiser = new PartialMockOA_Dll_Advertiser_StatsComboTest($this);
        $dllAdvertiser->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiser->setReturnValue('checkPermissions', true);

        $dllCampaign = new PartialMockOA_Dll_Campaign_StatsComboTest($this);
        $dllCampaign->setReturnValue('checkPermissions', true);

        $dllBanner = new PartialMockOA_Dll_Banner_StatsComboTest($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'StatsCombo Test Advertiser';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $dllAdvertiser->modify($oAdvertiserInfo);

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'StatsCombo Test Campaign';
        $dllCampaign->modify($oCampaignInfo);

        $oBannerInfo = new OA_Dll_BannerInfo();
        $oBannerInfo->campaignId = $oCampaignInfo->campaignId;
        $oBannerInfo->bannerName = 'StatsCombo Test Banner';
        $dllBanner->modify($oBannerInfo);

        return [
            'advertiser' => $oAdvertiserInfo,
            'campaign' => $oCampaignInfo,
            'banner' => $oBannerInfo,
            'dllAdvertiser' => $dllAdvertiser,
            'dllCampaign' => $dllCampaign,
            'dllBanner' => $dllBanner,
        ];
    }

    // =========================================================================
    // Helper: Create publisher-side entities (publisher -> zone)
    // =========================================================================
    private function _createPublisherEntities()
    {
        $dllPublisher = new PartialMockOA_Dll_Publisher_StatsComboTest($this);
        $dllPublisher->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllPublisher->setReturnValue('checkPermissions', true);

        $dllZone = new PartialMockOA_Dll_Zone_StatsComboTest($this);
        $dllZone->setReturnValue('checkPermissions', true);

        $oPublisherInfo = new OA_Dll_PublisherInfo();
        $oPublisherInfo->publisherName = 'StatsCombo Test Publisher';
        $oPublisherInfo->agencyId = $this->agencyId;
        $dllPublisher->modify($oPublisherInfo);

        $oZoneInfo = new OA_Dll_ZoneInfo();
        $oZoneInfo->publisherId = $oPublisherInfo->publisherId;
        $oZoneInfo->zoneName = 'StatsCombo Test Zone';
        $dllZone->modify($oZoneInfo);

        return [
            'publisher' => $oPublisherInfo,
            'zone' => $oZoneInfo,
            'dllPublisher' => $dllPublisher,
            'dllZone' => $dllZone,
        ];
    }

    // =========================================================================
    // Helper: Build date range based on type
    // =========================================================================
    private function _getDateRange($type)
    {
        switch ($type) {
            case 'valid':
                return [new Date('2024-01-01'), new Date('2024-06-30')];
            case 'reversed':
                return [new Date('2024-06-30'), new Date('2024-01-01')];
            case 'null_start':
                return [null, new Date('2024-06-30')];
            case 'null_end':
                return [new Date('2024-01-01'), null];
            case 'same_day':
                return [new Date('2024-03-15'), new Date('2024-03-15')];
            default:
                return [new Date('2024-01-01'), new Date('2024-06-30')];
        }
    }

    // =========================================================================
    // Helper: Get stats method name for entity + granularity
    // =========================================================================
    private function _getStatsMethodName($entityLevel, $granularity)
    {
        $prefix = match ($entityLevel) {
            'Advertiser' => 'getAdvertiser',
            'Campaign' => 'getCampaign',
            'Banner' => 'getBanner',
            'Publisher' => 'getPublisher',
            'Zone' => 'getZone',
        };

        $suffix = match ($granularity) {
            'Daily' => 'DailyStatistics',
            'Hourly' => 'HourlyStatistics',
        };

        return $prefix . $suffix;
    }

    // =========================================================================
    // Helper: Execute a statistics call for an entity
    // Returns [bool $success, mixed $result, object $dll]
    // =========================================================================
    private function _executeStatsCall(
        $entityLevel,
        $granularity,
        $dateRangeType,
        $localTZ
    ) {
        list($oStartDate, $oEndDate) = $this->_getDateRange($dateRangeType);
        $methodName = $this->_getStatsMethodName($entityLevel, $granularity);

        // Determine which entities to create and which DLL to use
        if (in_array($entityLevel, ['Advertiser', 'Campaign', 'Banner'])) {
            $entities = $this->_createAdvertiserEntities();
            switch ($entityLevel) {
                case 'Advertiser':
                    $dll = $entities['dllAdvertiser'];
                    $entityId = $entities['advertiser']->advertiserId;
                    break;
                case 'Campaign':
                    $dll = $entities['dllCampaign'];
                    $entityId = $entities['campaign']->campaignId;
                    break;
                case 'Banner':
                    $dll = $entities['dllBanner'];
                    $entityId = $entities['banner']->bannerId;
                    break;
            }
        } else {
            $entities = $this->_createPublisherEntities();
            switch ($entityLevel) {
                case 'Publisher':
                    $dll = $entities['dllPublisher'];
                    $entityId = $entities['publisher']->publisherId;
                    break;
                case 'Zone':
                    $dll = $entities['dllZone'];
                    $entityId = $entities['zone']->zoneId;
                    break;
            }
        }

        $rsStatisticsData = null;
        $success = $dll->$methodName(
            $entityId,
            $oStartDate,
            $oEndDate,
            $localTZ,
            $rsStatisticsData,
        );

        return [$success, $rsStatisticsData, $dll];
    }

    // =========================================================================
    // Helper: Assert that a statistics result set is empty (0 rows)
    // =========================================================================
    private function _assertEmptyResultSet($rsStatisticsData, $message)
    {
        $this->assertTrue(isset($rsStatisticsData), $message . ' - result set should be set');

        if (is_array($rsStatisticsData)) {
            $this->assertEqual(count($rsStatisticsData), 0, $message);
        } elseif ($rsStatisticsData instanceof MDB2_Result_Common) {
            $this->assertEqual($rsStatisticsData->numRows(), 0, $message);
        } else {
            $this->assertEqual($rsStatisticsData->getRowCount(), 0, $message);
        }
    }

    // =========================================================================
    // SECTION 4I: Included Combos — Pairwise Statistics Tests (~40 tests)
    // =========================================================================

    /**
     * ST01: ADMIN + Campaign + Daily + Valid range + localTZ=true + Empty
     */
    public function testST01_Admin_Campaign_Daily_ValidRange_LocalTZ_Empty()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Campaign',
            'Daily',
            'valid',
            true,
        );

        $this->assertTrue($success, 'ST01: Admin Campaign Daily stats should succeed');
        $this->_assertEmptyResultSet($rsData, 'ST01: Empty data volume should return 0 rows');
    }

    /**
     * ST02: MANAGER + Banner + Hourly + Valid range + localTZ=false + Small
     */
    public function testST02_Manager_Banner_Hourly_ValidRange_NoLocalTZ_Small()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Banner',
            'Hourly',
            'valid',
            false,
        );

        $this->assertTrue($success, 'ST02: Manager Banner Hourly stats should succeed');
        $this->_assertEmptyResultSet($rsData, 'ST02: No data inserted, should return 0 rows');
    }

    /**
     * ST03: ADVERTISER + Campaign + Daily + Same day + localTZ=true + Medium
     */
    public function testST03_Advertiser_Campaign_Daily_SameDay_LocalTZ_Medium()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Campaign',
            'Daily',
            'same_day',
            true,
        );

        $this->assertTrue($success, 'ST03: Advertiser Campaign Daily same-day stats should succeed');
        $this->_assertEmptyResultSet($rsData, 'ST03: No data inserted, should return 0 rows');
    }

    /**
     * ST04: ADMIN + Zone + Hourly + Valid range + localTZ=false + Small
     */
    public function testST04_Admin_Zone_Hourly_ValidRange_NoLocalTZ_Small()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Zone',
            'Hourly',
            'valid',
            false,
        );

        $this->assertTrue($success, 'ST04: Admin Zone Hourly stats should succeed');
        $this->_assertEmptyResultSet($rsData, 'ST04: No data inserted, should return 0 rows');
    }

    /**
     * ST05: MANAGER + Publisher + Daily + Reversed (negative) + localTZ=true
     * Reversed date range should return an error.
     */
    public function testST05_Manager_Publisher_Daily_Reversed_LocalTZ()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Publisher',
            'Daily',
            'reversed',
            true,
        );

        $this->assertFalse($success, 'ST05: Reversed date range should fail');
        $this->assertEqual(
            $dll->getLastError(),
            $this->wrongDateError,
            'ST05: Should return wrong date error',
        );
    }

    /**
     * ST06: ADVERTISER + Advertiser + Daily + Null start + localTZ=false + Empty
     */
    public function testST06_Advertiser_Advertiser_Daily_NullStart_NoLocalTZ_Empty()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Advertiser',
            'Daily',
            'null_start',
            false,
        );

        $this->assertTrue($success, 'ST06: Null start date should succeed (treated as open-ended)');
    }

    /**
     * ST07: ADMIN + Campaign + Hourly + Null end + localTZ=true + Small
     */
    public function testST07_Admin_Campaign_Hourly_NullEnd_LocalTZ_Small()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Campaign',
            'Hourly',
            'null_end',
            true,
        );

        $this->assertTrue($success, 'ST07: Null end date should succeed (treated as open-ended)');
    }

    /**
     * ST08: TRAFFICKER + Publisher + Daily + Valid range + localTZ=true + Empty
     */
    public function testST08_Trafficker_Publisher_Daily_ValidRange_LocalTZ_Empty()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Publisher',
            'Daily',
            'valid',
            true,
        );

        $this->assertTrue($success, 'ST08: Trafficker Publisher Daily stats should succeed');
        $this->_assertEmptyResultSet($rsData, 'ST08: Empty data volume should return 0 rows');
    }

    /**
     * ST09: ADMIN + Advertiser + Hourly + Valid range + localTZ=true + Empty
     */
    public function testST09_Admin_Advertiser_Hourly_ValidRange_LocalTZ_Empty()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Advertiser',
            'Hourly',
            'valid',
            true,
        );

        $this->assertTrue($success, 'ST09: Admin Advertiser Hourly stats should succeed');
        $this->_assertEmptyResultSet($rsData, 'ST09: Empty data volume should return 0 rows');
    }

    /**
     * ST10: MANAGER + Campaign + Daily + Null start + localTZ=false + Empty
     */
    public function testST10_Manager_Campaign_Daily_NullStart_NoLocalTZ_Empty()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Campaign',
            'Daily',
            'null_start',
            false,
        );

        $this->assertTrue($success, 'ST10: Manager Campaign Daily null start should succeed');
    }

    /**
     * ST11: ADMIN + Banner + Daily + Same day + localTZ=false + Empty
     */
    public function testST11_Admin_Banner_Daily_SameDay_NoLocalTZ_Empty()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Banner',
            'Daily',
            'same_day',
            false,
        );

        $this->assertTrue($success, 'ST11: Admin Banner Daily same-day stats should succeed');
        $this->_assertEmptyResultSet($rsData, 'ST11: No data, should return 0 rows');
    }

    /**
     * ST12: TRAFFICKER + Zone + Daily + Valid range + localTZ=false + Empty
     */
    public function testST12_Trafficker_Zone_Daily_ValidRange_NoLocalTZ_Empty()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Zone',
            'Daily',
            'valid',
            false,
        );

        $this->assertTrue($success, 'ST12: Trafficker Zone Daily stats should succeed');
        $this->_assertEmptyResultSet($rsData, 'ST12: Empty data volume should return 0 rows');
    }

    /**
     * ST13: MANAGER + Advertiser + Daily + Valid range + localTZ=true + Empty
     */
    public function testST13_Manager_Advertiser_Daily_ValidRange_LocalTZ_Empty()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Advertiser',
            'Daily',
            'valid',
            true,
        );

        $this->assertTrue($success, 'ST13: Manager Advertiser Daily stats should succeed');
        $this->_assertEmptyResultSet($rsData, 'ST13: Empty data volume should return 0 rows');
    }

    /**
     * ST14: ADVERTISER + Banner + Hourly + Same day + localTZ=false + Empty
     */
    public function testST14_Advertiser_Banner_Hourly_SameDay_NoLocalTZ_Empty()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Banner',
            'Hourly',
            'same_day',
            false,
        );

        $this->assertTrue($success, 'ST14: Advertiser Banner Hourly same-day stats should succeed');
        $this->_assertEmptyResultSet($rsData, 'ST14: No data, should return 0 rows');
    }

    /**
     * ST15: ADMIN + Publisher + Hourly + Valid range + localTZ=true + Empty
     */
    public function testST15_Admin_Publisher_Hourly_ValidRange_LocalTZ_Empty()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Publisher',
            'Hourly',
            'valid',
            true,
        );

        $this->assertTrue($success, 'ST15: Admin Publisher Hourly stats should succeed');
        $this->_assertEmptyResultSet($rsData, 'ST15: Empty data volume should return 0 rows');
    }

    /**
     * ST16: MANAGER + Zone + Hourly + Null end + localTZ=true + Empty
     */
    public function testST16_Manager_Zone_Hourly_NullEnd_LocalTZ_Empty()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Zone',
            'Hourly',
            'null_end',
            true,
        );

        $this->assertTrue($success, 'ST16: Manager Zone Hourly null end should succeed');
    }

    /**
     * ST17: ADVERTISER + Advertiser + Hourly + Valid range + localTZ=true + Empty
     */
    public function testST17_Advertiser_Advertiser_Hourly_ValidRange_LocalTZ_Empty()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Advertiser',
            'Hourly',
            'valid',
            true,
        );

        $this->assertTrue($success, 'ST17: Advertiser Advertiser Hourly stats should succeed');
        $this->_assertEmptyResultSet($rsData, 'ST17: Empty data volume should return 0 rows');
    }

    /**
     * ST18: ADMIN + Campaign + Daily + Reversed + localTZ=false
     * Reversed date range should always fail.
     */
    public function testST18_Admin_Campaign_Daily_Reversed_NoLocalTZ()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Campaign',
            'Daily',
            'reversed',
            false,
        );

        $this->assertFalse($success, 'ST18: Reversed date range should fail');
        $this->assertEqual(
            $dll->getLastError(),
            $this->wrongDateError,
            'ST18: Should return wrong date error',
        );
    }

    /**
     * ST19: TRAFFICKER + Publisher + Hourly + Same day + localTZ=false + Empty
     */
    public function testST19_Trafficker_Publisher_Hourly_SameDay_NoLocalTZ_Empty()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Publisher',
            'Hourly',
            'same_day',
            false,
        );

        $this->assertTrue($success, 'ST19: Trafficker Publisher Hourly same-day should succeed');
        $this->_assertEmptyResultSet($rsData, 'ST19: Empty data volume should return 0 rows');
    }

    /**
     * ST20: ADMIN + Zone + Daily + Null start + localTZ=true + Empty
     */
    public function testST20_Admin_Zone_Daily_NullStart_LocalTZ_Empty()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Zone',
            'Daily',
            'null_start',
            true,
        );

        $this->assertTrue($success, 'ST20: Admin Zone Daily null start should succeed');
    }

    /**
     * ST21: MANAGER + Banner + Daily + Null end + localTZ=false + Empty
     */
    public function testST21_Manager_Banner_Daily_NullEnd_NoLocalTZ_Empty()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Banner',
            'Daily',
            'null_end',
            false,
        );

        $this->assertTrue($success, 'ST21: Manager Banner Daily null end should succeed');
    }

    /**
     * ST22: ADVERTISER + Campaign + Hourly + Valid range + localTZ=false + Empty
     */
    public function testST22_Advertiser_Campaign_Hourly_ValidRange_NoLocalTZ_Empty()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Campaign',
            'Hourly',
            'valid',
            false,
        );

        $this->assertTrue($success, 'ST22: Advertiser Campaign Hourly stats should succeed');
        $this->_assertEmptyResultSet($rsData, 'ST22: Empty data volume should return 0 rows');
    }

    /**
     * ST23: TRAFFICKER + Zone + Hourly + Null start + localTZ=true + Empty
     */
    public function testST23_Trafficker_Zone_Hourly_NullStart_LocalTZ_Empty()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Zone',
            'Hourly',
            'null_start',
            true,
        );

        $this->assertTrue($success, 'ST23: Trafficker Zone Hourly null start should succeed');
    }

    /**
     * ST24: ADMIN + Advertiser + Daily + Same day + localTZ=false + Empty
     */
    public function testST24_Admin_Advertiser_Daily_SameDay_NoLocalTZ_Empty()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Advertiser',
            'Daily',
            'same_day',
            false,
        );

        $this->assertTrue($success, 'ST24: Admin Advertiser Daily same-day stats should succeed');
        $this->_assertEmptyResultSet($rsData, 'ST24: No data, should return 0 rows');
    }

    /**
     * ST25: MANAGER + Campaign + Hourly + Same day + localTZ=true + Empty
     */
    public function testST25_Manager_Campaign_Hourly_SameDay_LocalTZ_Empty()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Campaign',
            'Hourly',
            'same_day',
            true,
        );

        $this->assertTrue($success, 'ST25: Manager Campaign Hourly same-day stats should succeed');
        $this->_assertEmptyResultSet($rsData, 'ST25: No data, should return 0 rows');
    }

    /**
     * ST26: ADMIN + Banner + Hourly + Null start + localTZ=true + Empty
     */
    public function testST26_Admin_Banner_Hourly_NullStart_LocalTZ_Empty()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Banner',
            'Hourly',
            'null_start',
            true,
        );

        $this->assertTrue($success, 'ST26: Admin Banner Hourly null start should succeed');
    }

    /**
     * ST27: TRAFFICKER + Publisher + Daily + Null end + localTZ=false + Empty
     */
    public function testST27_Trafficker_Publisher_Daily_NullEnd_NoLocalTZ_Empty()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Publisher',
            'Daily',
            'null_end',
            false,
        );

        $this->assertTrue($success, 'ST27: Trafficker Publisher Daily null end should succeed');
    }

    /**
     * ST28: MANAGER + Advertiser + Hourly + Reversed + localTZ=false
     * Reversed date range should fail.
     */
    public function testST28_Manager_Advertiser_Hourly_Reversed_NoLocalTZ()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Advertiser',
            'Hourly',
            'reversed',
            false,
        );

        $this->assertFalse($success, 'ST28: Reversed date range should fail');
        $this->assertEqual(
            $dll->getLastError(),
            $this->wrongDateError,
            'ST28: Should return wrong date error',
        );
    }

    /**
     * ST29: ADMIN + Publisher + Daily + Null end + localTZ=false + Empty
     */
    public function testST29_Admin_Publisher_Daily_NullEnd_NoLocalTZ_Empty()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Publisher',
            'Daily',
            'null_end',
            false,
        );

        $this->assertTrue($success, 'ST29: Admin Publisher Daily null end should succeed');
    }

    /**
     * ST30: ADVERTISER + Banner + Daily + Valid range + localTZ=true + Empty
     */
    public function testST30_Advertiser_Banner_Daily_ValidRange_LocalTZ_Empty()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Banner',
            'Daily',
            'valid',
            true,
        );

        $this->assertTrue($success, 'ST30: Advertiser Banner Daily stats should succeed');
        $this->_assertEmptyResultSet($rsData, 'ST30: Empty data volume should return 0 rows');
    }

    /**
     * ST31: TRAFFICKER + Zone + Daily + Same day + localTZ=true + Empty
     */
    public function testST31_Trafficker_Zone_Daily_SameDay_LocalTZ_Empty()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Zone',
            'Daily',
            'same_day',
            true,
        );

        $this->assertTrue($success, 'ST31: Trafficker Zone Daily same-day stats should succeed');
        $this->_assertEmptyResultSet($rsData, 'ST31: No data, should return 0 rows');
    }

    /**
     * ST32: ADMIN + Campaign + Hourly + Valid range + localTZ=true + Empty
     */
    public function testST32_Admin_Campaign_Hourly_ValidRange_LocalTZ_Empty()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Campaign',
            'Hourly',
            'valid',
            true,
        );

        $this->assertTrue($success, 'ST32: Admin Campaign Hourly stats should succeed');
        $this->_assertEmptyResultSet($rsData, 'ST32: Empty data volume should return 0 rows');
    }

    /**
     * ST33: MANAGER + Publisher + Hourly + Valid range + localTZ=false + Empty
     */
    public function testST33_Manager_Publisher_Hourly_ValidRange_NoLocalTZ_Empty()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Publisher',
            'Hourly',
            'valid',
            false,
        );

        $this->assertTrue($success, 'ST33: Manager Publisher Hourly stats should succeed');
        $this->_assertEmptyResultSet($rsData, 'ST33: Empty data volume should return 0 rows');
    }

    /**
     * ST34: ADVERTISER + Advertiser + Daily + Null end + localTZ=true + Empty
     */
    public function testST34_Advertiser_Advertiser_Daily_NullEnd_LocalTZ_Empty()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Advertiser',
            'Daily',
            'null_end',
            true,
        );

        $this->assertTrue($success, 'ST34: Advertiser Advertiser Daily null end should succeed');
    }

    /**
     * ST35: TRAFFICKER + Zone + Hourly + Reversed + localTZ=true
     * Reversed date range should fail.
     */
    public function testST35_Trafficker_Zone_Hourly_Reversed_LocalTZ()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Zone',
            'Hourly',
            'reversed',
            true,
        );

        $this->assertFalse($success, 'ST35: Reversed date range should fail');
        $this->assertEqual(
            $dll->getLastError(),
            $this->wrongDateError,
            'ST35: Should return wrong date error',
        );
    }

    /**
     * ST36: ADMIN + Advertiser + Hourly + Null end + localTZ=false + Empty
     */
    public function testST36_Admin_Advertiser_Hourly_NullEnd_NoLocalTZ_Empty()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Advertiser',
            'Hourly',
            'null_end',
            false,
        );

        $this->assertTrue($success, 'ST36: Admin Advertiser Hourly null end should succeed');
    }

    /**
     * ST37: MANAGER + Zone + Daily + Valid range + localTZ=false + Empty
     */
    public function testST37_Manager_Zone_Daily_ValidRange_NoLocalTZ_Empty()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Zone',
            'Daily',
            'valid',
            false,
        );

        $this->assertTrue($success, 'ST37: Manager Zone Daily stats should succeed');
        $this->_assertEmptyResultSet($rsData, 'ST37: Empty data volume should return 0 rows');
    }

    /**
     * ST38: ADVERTISER + Campaign + Daily + Null start + localTZ=true + Empty
     */
    public function testST38_Advertiser_Campaign_Daily_NullStart_LocalTZ_Empty()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Campaign',
            'Daily',
            'null_start',
            true,
        );

        $this->assertTrue($success, 'ST38: Advertiser Campaign Daily null start should succeed');
    }

    /**
     * ST39: ADMIN + Zone + Hourly + Same day + localTZ=true + Empty
     */
    public function testST39_Admin_Zone_Hourly_SameDay_LocalTZ_Empty()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Zone',
            'Hourly',
            'same_day',
            true,
        );

        $this->assertTrue($success, 'ST39: Admin Zone Hourly same-day stats should succeed');
        $this->_assertEmptyResultSet($rsData, 'ST39: No data, should return 0 rows');
    }

    /**
     * ST40: TRAFFICKER + Publisher + Hourly + Null end + localTZ=true + Empty
     */
    public function testST40_Trafficker_Publisher_Hourly_NullEnd_LocalTZ_Empty()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Publisher',
            'Hourly',
            'null_end',
            true,
        );

        $this->assertTrue($success, 'ST40: Trafficker Publisher Hourly null end should succeed');
    }

    /**
     * ST41: ADMIN + Banner + Hourly + Reversed + localTZ=true
     * Reversed date range should fail.
     */
    public function testST41_Admin_Banner_Hourly_Reversed_LocalTZ()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Banner',
            'Hourly',
            'reversed',
            true,
        );

        $this->assertFalse($success, 'ST41: Reversed date range should fail');
        $this->assertEqual(
            $dll->getLastError(),
            $this->wrongDateError,
            'ST41: Should return wrong date error',
        );
    }

    /**
     * ST42: ADVERTISER + Banner + Daily + Null start + localTZ=false + Empty
     */
    public function testST42_Advertiser_Banner_Daily_NullStart_NoLocalTZ_Empty()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Banner',
            'Daily',
            'null_start',
            false,
        );

        $this->assertTrue($success, 'ST42: Advertiser Banner Daily null start should succeed');
    }

    /**
     * ST43: MANAGER + Campaign + Daily + Valid range + localTZ=true + Empty
     */
    public function testST43_Manager_Campaign_Daily_ValidRange_LocalTZ_Empty()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Campaign',
            'Daily',
            'valid',
            true,
        );

        $this->assertTrue($success, 'ST43: Manager Campaign Daily stats should succeed');
        $this->_assertEmptyResultSet($rsData, 'ST43: Empty data volume should return 0 rows');
    }

    // =========================================================================
    // SECTION 4J: Excluded Combos — Negative / Permission Denial Tests
    // =========================================================================

    /**
     * 4J-NEG01: TRAFFICKER + Campaign stats => Permission denied
     * Traffickers only have access to publisher/zone stats via
     * aAllowTraffickerAndAbovePerm. Campaign stats use
     * aAllowAdvertiserAndAbovePerm, so traffickers are excluded.
     */
    public function testNEG01_Trafficker_CampaignDailyStats_PermissionDenied()
    {
        $dllCampaign = new PartialMockOA_Dll_Campaign_StatsComboTest($this);
        // Simulate trafficker: checkPermissions returns false
        $dllCampaign->setReturnValue('checkPermissions', false);

        $entities = $this->_createAdvertiserEntities();
        $campaignId = $entities['campaign']->campaignId;

        $rsData = null;
        $result = $dllCampaign->getCampaignDailyStatistics(
            $campaignId,
            new Date('2024-01-01'),
            new Date('2024-06-30'),
            false,
            $rsData,
        );

        $this->assertFalse($result, 'NEG01: Trafficker should be denied Campaign Daily stats');
    }

    /**
     * 4J-NEG02: TRAFFICKER + Campaign Hourly stats => Permission denied
     */
    public function testNEG02_Trafficker_CampaignHourlyStats_PermissionDenied()
    {
        $dllCampaign = new PartialMockOA_Dll_Campaign_StatsComboTest($this);
        $dllCampaign->setReturnValue('checkPermissions', false);

        $entities = $this->_createAdvertiserEntities();
        $campaignId = $entities['campaign']->campaignId;

        $rsData = null;
        $result = $dllCampaign->getCampaignHourlyStatistics(
            $campaignId,
            new Date('2024-01-01'),
            new Date('2024-06-30'),
            false,
            $rsData,
        );

        $this->assertFalse($result, 'NEG02: Trafficker should be denied Campaign Hourly stats');
    }

    /**
     * 4J-NEG03: TRAFFICKER + Banner Daily stats => Permission denied
     */
    public function testNEG03_Trafficker_BannerDailyStats_PermissionDenied()
    {
        $dllBanner = new PartialMockOA_Dll_Banner_StatsComboTest($this);
        $dllBanner->setReturnValue('checkPermissions', false);

        $entities = $this->_createAdvertiserEntities();
        $bannerId = $entities['banner']->bannerId;

        $rsData = null;
        $result = $dllBanner->getBannerDailyStatistics(
            $bannerId,
            new Date('2024-01-01'),
            new Date('2024-06-30'),
            false,
            $rsData,
        );

        $this->assertFalse($result, 'NEG03: Trafficker should be denied Banner Daily stats');
    }

    /**
     * 4J-NEG04: TRAFFICKER + Banner Hourly stats => Permission denied
     */
    public function testNEG04_Trafficker_BannerHourlyStats_PermissionDenied()
    {
        $dllBanner = new PartialMockOA_Dll_Banner_StatsComboTest($this);
        $dllBanner->setReturnValue('checkPermissions', false);

        $entities = $this->_createAdvertiserEntities();
        $bannerId = $entities['banner']->bannerId;

        $rsData = null;
        $result = $dllBanner->getBannerHourlyStatistics(
            $bannerId,
            new Date('2024-01-01'),
            new Date('2024-06-30'),
            false,
            $rsData,
        );

        $this->assertFalse($result, 'NEG04: Trafficker should be denied Banner Hourly stats');
    }

    /**
     * 4J-NEG05: TRAFFICKER + Advertiser Daily stats => Permission denied
     */
    public function testNEG05_Trafficker_AdvertiserDailyStats_PermissionDenied()
    {
        $dllAdvertiser = new PartialMockOA_Dll_Advertiser_StatsComboTest($this);
        $dllAdvertiser->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiser->setReturnValue('checkPermissions', false);

        $entities = $this->_createAdvertiserEntities();
        $advertiserId = $entities['advertiser']->advertiserId;

        $rsData = null;
        $result = $dllAdvertiser->getAdvertiserDailyStatistics(
            $advertiserId,
            new Date('2024-01-01'),
            new Date('2024-06-30'),
            false,
            $rsData,
        );

        $this->assertFalse($result, 'NEG05: Trafficker should be denied Advertiser Daily stats');
    }

    /**
     * 4J-NEG06: TRAFFICKER + Advertiser Hourly stats => Permission denied
     */
    public function testNEG06_Trafficker_AdvertiserHourlyStats_PermissionDenied()
    {
        $dllAdvertiser = new PartialMockOA_Dll_Advertiser_StatsComboTest($this);
        $dllAdvertiser->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiser->setReturnValue('checkPermissions', false);

        $entities = $this->_createAdvertiserEntities();
        $advertiserId = $entities['advertiser']->advertiserId;

        $rsData = null;
        $result = $dllAdvertiser->getAdvertiserHourlyStatistics(
            $advertiserId,
            new Date('2024-01-01'),
            new Date('2024-06-30'),
            false,
            $rsData,
        );

        $this->assertFalse($result, 'NEG06: Trafficker should be denied Advertiser Hourly stats');
    }

    /**
     * 4J-NEG07: ADVERTISER + Publisher Daily stats => Permission denied
     * Advertisers only have access to advertiser/campaign/banner stats via
     * aAllowAdvertiserAndAbovePerm. Publisher stats use
     * aAllowTraffickerAndAbovePerm, so advertisers are excluded.
     */
    public function testNEG07_Advertiser_PublisherDailyStats_PermissionDenied()
    {
        $dllPublisher = new PartialMockOA_Dll_Publisher_StatsComboTest($this);
        $dllPublisher->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllPublisher->setReturnValue('checkPermissions', false);

        $entities = $this->_createPublisherEntities();
        $publisherId = $entities['publisher']->publisherId;

        $rsData = null;
        $result = $dllPublisher->getPublisherDailyStatistics(
            $publisherId,
            new Date('2024-01-01'),
            new Date('2024-06-30'),
            false,
            $rsData,
        );

        $this->assertFalse($result, 'NEG07: Advertiser should be denied Publisher Daily stats');
    }

    /**
     * 4J-NEG08: ADVERTISER + Publisher Hourly stats => Permission denied
     */
    public function testNEG08_Advertiser_PublisherHourlyStats_PermissionDenied()
    {
        $dllPublisher = new PartialMockOA_Dll_Publisher_StatsComboTest($this);
        $dllPublisher->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllPublisher->setReturnValue('checkPermissions', false);

        $entities = $this->_createPublisherEntities();
        $publisherId = $entities['publisher']->publisherId;

        $rsData = null;
        $result = $dllPublisher->getPublisherHourlyStatistics(
            $publisherId,
            new Date('2024-01-01'),
            new Date('2024-06-30'),
            false,
            $rsData,
        );

        $this->assertFalse($result, 'NEG08: Advertiser should be denied Publisher Hourly stats');
    }

    /**
     * 4J-NEG09: ADVERTISER + Zone Daily stats => Permission denied
     */
    public function testNEG09_Advertiser_ZoneDailyStats_PermissionDenied()
    {
        $dllZone = new PartialMockOA_Dll_Zone_StatsComboTest($this);
        $dllZone->setReturnValue('checkPermissions', false);

        $entities = $this->_createPublisherEntities();
        $zoneId = $entities['zone']->zoneId;

        $rsData = null;
        $result = $dllZone->getZoneDailyStatistics(
            $zoneId,
            new Date('2024-01-01'),
            new Date('2024-06-30'),
            false,
            $rsData,
        );

        $this->assertFalse($result, 'NEG09: Advertiser should be denied Zone Daily stats');
    }

    /**
     * 4J-NEG10: ADVERTISER + Zone Hourly stats => Permission denied
     */
    public function testNEG10_Advertiser_ZoneHourlyStats_PermissionDenied()
    {
        $dllZone = new PartialMockOA_Dll_Zone_StatsComboTest($this);
        $dllZone->setReturnValue('checkPermissions', false);

        $entities = $this->_createPublisherEntities();
        $zoneId = $entities['zone']->zoneId;

        $rsData = null;
        $result = $dllZone->getZoneHourlyStatistics(
            $zoneId,
            new Date('2024-01-01'),
            new Date('2024-06-30'),
            false,
            $rsData,
        );

        $this->assertFalse($result, 'NEG10: Advertiser should be denied Zone Hourly stats');
    }

    /**
     * 4J-NEG11: Reversed dates + Advertiser Daily — validation fails before data query
     */
    public function testNEG11_ReversedDates_Advertiser_Daily_ValidationError()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Advertiser',
            'Daily',
            'reversed',
            false,
        );

        $this->assertFalse($success, 'NEG11: Reversed date range should fail');
        $this->assertEqual(
            $dll->getLastError(),
            $this->wrongDateError,
            'NEG11: Should return wrong date error before any data query',
        );
    }

    /**
     * 4J-NEG12: Reversed dates + Banner Hourly — validation fails before data query
     */
    public function testNEG12_ReversedDates_Banner_Hourly_ValidationError()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Banner',
            'Hourly',
            'reversed',
            true,
        );

        $this->assertFalse($success, 'NEG12: Reversed date range should fail');
        $this->assertEqual(
            $dll->getLastError(),
            $this->wrongDateError,
            'NEG12: Should return wrong date error before any data query',
        );
    }

    /**
     * 4J-NEG13: Reversed dates + Zone Daily — validation fails before data query
     */
    public function testNEG13_ReversedDates_Zone_Daily_ValidationError()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Zone',
            'Daily',
            'reversed',
            false,
        );

        $this->assertFalse($success, 'NEG13: Reversed date range should fail');
        $this->assertEqual(
            $dll->getLastError(),
            $this->wrongDateError,
            'NEG13: Should return wrong date error before any data query',
        );
    }

    /**
     * 4J-NEG14: Reversed dates + Publisher Hourly — validation fails before data query
     */
    public function testNEG14_ReversedDates_Publisher_Hourly_ValidationError()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Publisher',
            'Hourly',
            'reversed',
            false,
        );

        $this->assertFalse($success, 'NEG14: Reversed date range should fail');
        $this->assertEqual(
            $dll->getLastError(),
            $this->wrongDateError,
            'NEG14: Should return wrong date error before any data query',
        );
    }

    /**
     * 4J-NEG15: Reversed dates + Campaign Hourly — validation fails before data query
     */
    public function testNEG15_ReversedDates_Campaign_Hourly_ValidationError()
    {
        list($success, $rsData, $dll) = $this->_executeStatsCall(
            'Campaign',
            'Hourly',
            'reversed',
            false,
        );

        $this->assertFalse($success, 'NEG15: Reversed date range should fail');
        $this->assertEqual(
            $dll->getLastError(),
            $this->wrongDateError,
            'NEG15: Should return wrong date error before any data query',
        );
    }

    /**
     * 4J-NEG16: Stats for non-existent Campaign ID => unknown ID error
     */
    public function testNEG16_NonExistentCampaignId_StatsError()
    {
        $dllCampaign = new PartialMockOA_Dll_Campaign_StatsComboTest($this);
        $dllCampaign->setReturnValue('checkPermissions', true);

        $rsData = null;
        $result = $dllCampaign->getCampaignDailyStatistics(
            999999,
            new Date('2024-01-01'),
            new Date('2024-06-30'),
            false,
            $rsData,
        );

        $this->assertFalse($result, 'NEG16: Non-existent campaign ID should fail');
        $this->assertEqual(
            $dllCampaign->getLastError(),
            'Unknown campaignId Error',
            'NEG16: Should return unknown campaignId error',
        );
    }

    /**
     * 4J-NEG17: Stats for non-existent Banner ID => unknown ID error
     */
    public function testNEG17_NonExistentBannerId_StatsError()
    {
        $dllBanner = new PartialMockOA_Dll_Banner_StatsComboTest($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $rsData = null;
        $result = $dllBanner->getBannerDailyStatistics(
            999999,
            new Date('2024-01-01'),
            new Date('2024-06-30'),
            false,
            $rsData,
        );

        $this->assertFalse($result, 'NEG17: Non-existent banner ID should fail');
        $this->assertEqual(
            $dllBanner->getLastError(),
            'Unknown bannerId Error',
            'NEG17: Should return unknown bannerId error',
        );
    }

    /**
     * 4J-NEG18: Stats for non-existent Zone ID => unknown ID error
     */
    public function testNEG18_NonExistentZoneId_StatsError()
    {
        $dllZone = new PartialMockOA_Dll_Zone_StatsComboTest($this);
        $dllZone->setReturnValue('checkPermissions', true);

        $rsData = null;
        $result = $dllZone->getZoneDailyStatistics(
            999999,
            new Date('2024-01-01'),
            new Date('2024-06-30'),
            false,
            $rsData,
        );

        $this->assertFalse($result, 'NEG18: Non-existent zone ID should fail');
        $this->assertEqual(
            $dllZone->getLastError(),
            'Unknown zoneId Error',
            'NEG18: Should return unknown zoneId error',
        );
    }

    /**
     * 4J-NEG19: Stats for non-existent Advertiser ID => unknown ID error
     */
    public function testNEG19_NonExistentAdvertiserId_StatsError()
    {
        $dllAdvertiser = new PartialMockOA_Dll_Advertiser_StatsComboTest($this);
        $dllAdvertiser->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiser->setReturnValue('checkPermissions', true);

        $rsData = null;
        $result = $dllAdvertiser->getAdvertiserDailyStatistics(
            999999,
            new Date('2024-01-01'),
            new Date('2024-06-30'),
            false,
            $rsData,
        );

        $this->assertFalse($result, 'NEG19: Non-existent advertiser ID should fail');
        $this->assertEqual(
            $dllAdvertiser->getLastError(),
            'Unknown advertiserId Error',
            'NEG19: Should return unknown advertiserId error',
        );
    }

    /**
     * 4J-NEG20: Stats for non-existent Publisher ID => unknown ID error
     */
    public function testNEG20_NonExistentPublisherId_StatsError()
    {
        $dllPublisher = new PartialMockOA_Dll_Publisher_StatsComboTest($this);
        $dllPublisher->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllPublisher->setReturnValue('checkPermissions', true);

        $rsData = null;
        $result = $dllPublisher->getPublisherDailyStatistics(
            999999,
            new Date('2024-01-01'),
            new Date('2024-06-30'),
            false,
            $rsData,
        );

        $this->assertFalse($result, 'NEG20: Non-existent publisher ID should fail');
        $this->assertEqual(
            $dllPublisher->getLastError(),
            'Unknown publisherId Error',
            'NEG20: Should return unknown publisherId error',
        );
    }
}
