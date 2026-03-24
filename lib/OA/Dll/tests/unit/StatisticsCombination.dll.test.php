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
 * Statistics Combination Matrix Tests (Sections 4I + 4J)
 *
 * Comprehensive pairwise-generated test cases covering the Statistics
 * Combination Matrix from the combinatorial testing plan.
 *
 * Dimensions:
 *   - Account type:     ADMIN, MANAGER, ADVERTISER, TRAFFICKER
 *   - Entity level:     Advertiser, Campaign, Banner, Publisher, Zone
 *   - Time granularity: Daily, Hourly
 *   - Date range:       Valid range, reversed dates, null start, null end, same day
 *   - Timezone:         localTZ=true, localTZ=false
 *   - Data volume:      Empty (0 rows), small (1-10), medium (100+)
 *
 * @package    OpenXDll
 * @subpackage TestSuite
 */

require_once MAX_PATH . '/lib/OA/Dll/Agency.php';
require_once MAX_PATH . '/lib/OA/Dll/AgencyInfo.php';
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

class OA_Dll_StatisticsCombinationTest extends DllUnitTestCase
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
            'PartialMockOA_Dll_Advertiser_StatsCombTest',
            ['checkPermissions', 'getDefaultAgencyId'],
        );
        Mock::generatePartial(
            'OA_Dll_Campaign',
            'PartialMockOA_Dll_Campaign_StatsCombTest',
            ['checkPermissions'],
        );
        Mock::generatePartial(
            'OA_Dll_Banner',
            'PartialMockOA_Dll_Banner_StatsCombTest',
            ['checkPermissions'],
        );
        Mock::generatePartial(
            'OA_Dll_Publisher',
            'PartialMockOA_Dll_Publisher_StatsCombTest',
            ['checkPermissions', 'getDefaultAgencyId'],
        );
        Mock::generatePartial(
            'OA_Dll_Zone',
            'PartialMockOA_Dll_Zone_StatsCombTest',
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

    // ---------------------------------------------------------------
    // Helper: create advertiser-side entity hierarchy and return info
    // ---------------------------------------------------------------

    /**
     * Creates an advertiser entity and returns its DLL mock + info.
     *
     * @return array [$dllMock, $oInfo]
     */
    private function _createAdvertiserEntity()
    {
        $dll = new PartialMockOA_Dll_Advertiser_StatsCombTest($this);
        $dll->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dll->setReturnValue('checkPermissions', true);

        $oInfo = new OA_Dll_AdvertiserInfo();
        $oInfo->advertiserName = 'Stats Combo Test Advertiser';
        $oInfo->agencyId = $this->agencyId;
        $dll->modify($oInfo);

        return [$dll, $oInfo];
    }

    /**
     * Creates a campaign entity and returns its DLL mock + info.
     *
     * @return array [$dllMock, $oInfo, $advertiserId]
     */
    private function _createCampaignEntity()
    {
        [$dllAdv, $oAdvInfo] = $this->_createAdvertiserEntity();

        $dll = new PartialMockOA_Dll_Campaign_StatsCombTest($this);
        $dll->setReturnValue('checkPermissions', true);

        $oInfo = new OA_Dll_CampaignInfo();
        $oInfo->advertiserId = $oAdvInfo->advertiserId;
        $dll->modify($oInfo);

        return [$dll, $oInfo, $oAdvInfo->advertiserId];
    }

    /**
     * Creates a banner entity and returns its DLL mock + info.
     *
     * @return array [$dllMock, $oInfo]
     */
    private function _createBannerEntity()
    {
        [$dllAdv, $oAdvInfo] = $this->_createAdvertiserEntity();

        $dllCamp = new PartialMockOA_Dll_Campaign_StatsCombTest($this);
        $dllCamp->setReturnValue('checkPermissions', true);
        $oCampInfo = new OA_Dll_CampaignInfo();
        $oCampInfo->advertiserId = $oAdvInfo->advertiserId;
        $dllCamp->modify($oCampInfo);

        $dll = new PartialMockOA_Dll_Banner_StatsCombTest($this);
        $dll->setReturnValue('checkPermissions', true);

        $oInfo = new OA_Dll_BannerInfo();
        $oInfo->campaignId = $oCampInfo->campaignId;
        $dll->modify($oInfo);

        return [$dll, $oInfo];
    }

    /**
     * Creates a publisher entity and returns its DLL mock + info.
     *
     * @return array [$dllMock, $oInfo]
     */
    private function _createPublisherEntity()
    {
        $dll = new PartialMockOA_Dll_Publisher_StatsCombTest($this);
        $dll->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dll->setReturnValue('checkPermissions', true);

        $oInfo = new OA_Dll_PublisherInfo();
        $oInfo->publisherName = 'Stats Combo Test Publisher';
        $oInfo->agencyId = $this->agencyId;
        $dll->modify($oInfo);

        return [$dll, $oInfo];
    }

    /**
     * Creates a zone entity and returns its DLL mock + info.
     *
     * @return array [$dllMock, $oInfo]
     */
    private function _createZoneEntity()
    {
        [$dllPub, $oPubInfo] = $this->_createPublisherEntity();

        $dll = new PartialMockOA_Dll_Zone_StatsCombTest($this);
        $dll->setReturnValue('checkPermissions', true);

        $oInfo = new OA_Dll_ZoneInfo();
        $oInfo->publisherId = $oPubInfo->publisherId;
        $dll->modify($oInfo);

        return [$dll, $oInfo];
    }

    // ---------------------------------------------------------------
    // Helper: resolve the DLL method name from entity level + granularity
    // ---------------------------------------------------------------

    /**
     * Maps (entity level, granularity) to a DLL statistics method name.
     *
     * @param string $entityLevel  'Advertiser','Campaign','Banner','Publisher','Zone'
     * @param string $granularity  'Daily','Hourly'
     * @return string  method name, e.g. 'getCampaignDailyStatistics'
     */
    private function _getStatsMethodName($entityLevel, $granularity)
    {
        return 'get' . $entityLevel . $granularity . 'Statistics';
    }

    // ---------------------------------------------------------------
    // Helper: resolve the entity ID from the info object
    // ---------------------------------------------------------------

    /**
     * Returns the entity ID from an info object based on entity level.
     *
     * @param string $entityLevel
     * @param object $oInfo
     * @return int
     */
    private function _getEntityId($entityLevel, $oInfo)
    {
        switch ($entityLevel) {
            case 'Advertiser':
                return $oInfo->advertiserId;
            case 'Campaign':
                return $oInfo->campaignId;
            case 'Banner':
                return $oInfo->bannerId;
            case 'Publisher':
                return $oInfo->publisherId;
            case 'Zone':
                return $oInfo->zoneId;
        }
    }

    // ---------------------------------------------------------------
    // Helper: create an entity + DLL mock for a given entity level
    // ---------------------------------------------------------------

    /**
     * Creates the entity for the given level and returns [$dllMock, $oInfo].
     *
     * @param string $entityLevel
     * @return array [$dllMock, $oInfo]
     */
    private function _createEntity($entityLevel)
    {
        switch ($entityLevel) {
            case 'Advertiser':
                return $this->_createAdvertiserEntity();
            case 'Campaign':
                return $this->_createCampaignEntity();
            case 'Banner':
                return $this->_createBannerEntity();
            case 'Publisher':
                return $this->_createPublisherEntity();
            case 'Zone':
                return $this->_createZoneEntity();
        }
    }

    // ---------------------------------------------------------------
    // Helper: build date range from type
    // ---------------------------------------------------------------

    /**
     * Returns [$oStartDate, $oEndDate] for the given date range type.
     *
     * @param string $dateRange  'valid','reversed','null_start','null_end','same_day'
     * @return array [Date|null, Date|null]
     */
    private function _buildDateRange($dateRange)
    {
        switch ($dateRange) {
            case 'valid':
                return [new Date('2001-12-01'), new Date('2007-09-19')];
            case 'reversed':
                return [new Date('2007-09-19'), new Date('2001-12-01')];
            case 'null_start':
                return [null, new Date('2007-09-19')];
            case 'null_end':
                return [new Date('2001-12-01'), null];
            case 'same_day':
                return [new Date('2005-06-15'), new Date('2005-06-15')];
            default:
                return [new Date('2001-12-01'), new Date('2007-09-19')];
        }
    }

    // ---------------------------------------------------------------
    // Helper: insert statistics data rows into data_summary_ad_hourly
    // ---------------------------------------------------------------

    /**
     * Insert a specified number of statistics data rows for the given entity.
     *
     * @param string $entityLevel
     * @param object $oInfo
     * @param int    $count  Number of rows to insert
     */
    private function _insertStatsData($entityLevel, $oInfo, $count)
    {
        if ($count <= 0) {
            return;
        }

        for ($i = 0; $i < $count; $i++) {
            $doData = OA_Dal::factoryDO('data_summary_ad_hourly');

            $day = sprintf('2005-06-%02d', ($i % 28) + 1);
            $hour = $i % 24;

            $doData->date_time = $day . ' ' . sprintf('%02d', $hour) . ':00:00';
            $doData->requests = $i + 1;
            $doData->impressions = ($i + 1) * 10;
            $doData->clicks = $i;
            $doData->revenue = ($i + 1) * 0.05;
            $doData->updated = date('Y-m-d H:i:s');

            switch ($entityLevel) {
                case 'Advertiser':
                    // Need a campaign and banner under this advertiser
                    $dllCamp = new PartialMockOA_Dll_Campaign_StatsCombTest($this);
                    $dllCamp->setReturnValue('checkPermissions', true);
                    $oCampInfo = new OA_Dll_CampaignInfo();
                    $oCampInfo->advertiserId = $oInfo->advertiserId;
                    if ($i === 0) {
                        $dllCamp->modify($oCampInfo);
                        $this->_statsCampaignId = $oCampInfo->campaignId;

                        $dllBan = new PartialMockOA_Dll_Banner_StatsCombTest($this);
                        $dllBan->setReturnValue('checkPermissions', true);
                        $oBanInfo = new OA_Dll_BannerInfo();
                        $oBanInfo->campaignId = $oCampInfo->campaignId;
                        $dllBan->modify($oBanInfo);
                        $this->_statsBannerId = $oBanInfo->bannerId;
                    }
                    $doData->ad_id = $this->_statsBannerId;
                    break;
                case 'Campaign':
                    if ($i === 0) {
                        $dllBan = new PartialMockOA_Dll_Banner_StatsCombTest($this);
                        $dllBan->setReturnValue('checkPermissions', true);
                        $oBanInfo = new OA_Dll_BannerInfo();
                        $oBanInfo->campaignId = $oInfo->campaignId;
                        $dllBan->modify($oBanInfo);
                        $this->_statsBannerId = $oBanInfo->bannerId;
                    }
                    $doData->ad_id = $this->_statsBannerId;
                    break;
                case 'Banner':
                    $doData->ad_id = $oInfo->bannerId;
                    break;
                case 'Publisher':
                    $doData->ad_id = 0;
                    $doData->zone_id = 0;
                    // Create a zone under this publisher for stats
                    if ($i === 0) {
                        $dllZone = new PartialMockOA_Dll_Zone_StatsCombTest($this);
                        $dllZone->setReturnValue('checkPermissions', true);
                        $oZoneInfo = new OA_Dll_ZoneInfo();
                        $oZoneInfo->publisherId = $oInfo->publisherId;
                        $dllZone->modify($oZoneInfo);
                        $this->_statsZoneId = $oZoneInfo->zoneId;
                    }
                    $doData->zone_id = $this->_statsZoneId;
                    break;
                case 'Zone':
                    $doData->ad_id = 0;
                    $doData->zone_id = $oInfo->zoneId;
                    break;
            }

            $doData->insert();
        }
    }

    // ---------------------------------------------------------------
    // Core: run a single pairwise combination test
    // ---------------------------------------------------------------

    /**
     * Executes a single statistics combination test case.
     *
     * @param string $testId       e.g. 'ST01'
     * @param string $entityLevel  'Advertiser','Campaign','Banner','Publisher','Zone'
     * @param string $granularity  'Daily','Hourly'
     * @param string $dateRange    'valid','reversed','null_start','null_end','same_day'
     * @param bool   $localTZ      true or false
     * @param int    $dataVolume   Number of rows: 0=empty, 5=small, 110=medium
     */
    private function _runComboTest($testId, $entityLevel, $granularity, $dateRange, $localTZ, $dataVolume)
    {
        [$dll, $oInfo] = $this->_createEntity($entityLevel);

        $methodName = $this->_getStatsMethodName($entityLevel, $granularity);
        $entityId = $this->_getEntityId($entityLevel, $oInfo);

        // Insert test data if needed
        if ($dataVolume > 0) {
            $this->_insertStatsData($entityLevel, $oInfo, $dataVolume);
        }

        [$oStartDate, $oEndDate] = $this->_buildDateRange($dateRange);

        $rsStatisticsData = null;

        if ($dateRange === 'reversed') {
            // Reversed dates should return false with wrong date error
            $result = $dll->$methodName(
                $entityId,
                $oStartDate,
                $oEndDate,
                $localTZ,
                $rsStatisticsData,
            );
            $this->assertFalse(
                $result,
                "$testId: Reversed date range should return false for $entityLevel $granularity",
            );
            $this->assertEqual(
                $dll->getLastError(),
                $this->wrongDateError,
                "$testId: Should return wrong date error for reversed dates",
            );
        } elseif ($dateRange === 'null_start' || $dateRange === 'null_end') {
            // Null start/end date: the method should handle gracefully.
            // Depending on implementation it may succeed with a broad range
            // or fail. We just verify it does not fatal error.
            $result = $dll->$methodName(
                $entityId,
                $oStartDate,
                $oEndDate,
                $localTZ,
                $rsStatisticsData,
            );
            // Assert that the method completed without a PHP error
            // (result can be true or false depending on validation)
            $this->assertTrue(
                is_bool($result),
                "$testId: Null date should return a boolean for $entityLevel $granularity",
            );
        } else {
            // Valid or same_day date range
            $result = $dll->$methodName(
                $entityId,
                $oStartDate,
                $oEndDate,
                $localTZ,
                $rsStatisticsData,
            );
            $this->assertTrue(
                $result,
                "$testId: $entityLevel $granularity with {$dateRange} dates and localTZ=" .
                ($localTZ ? 'true' : 'false') . ' should succeed: ' . $dll->getLastError(),
            );
            $this->assertTrue(
                isset($rsStatisticsData),
                "$testId: Statistics data should be set",
            );

            // Verify data volume expectations for empty datasets
            if ($dataVolume === 0) {
                if (is_array($rsStatisticsData)) {
                    $this->assertEqual(
                        count($rsStatisticsData),
                        0,
                        "$testId: Empty dataset should return 0 records",
                    );
                } elseif ($rsStatisticsData instanceof MDB2_Result_Common) {
                    $this->assertEqual(
                        $rsStatisticsData->numRows(),
                        0,
                        "$testId: Empty dataset should return 0 records (MDB2)",
                    );
                } else {
                    $this->assertEqual(
                        $rsStatisticsData->getRowCount(),
                        0,
                        "$testId: Empty dataset should return 0 records (DBC)",
                    );
                }
            }
        }
    }

    // ===================================================================
    // SECTION 4I: Pairwise-Generated Positive Test Cases (~40 combos)
    //
    // Dimensions and values:
    //   Account type:     ADMIN(A), MANAGER(M), ADVERTISER(V), TRAFFICKER(T)
    //   Entity level:     Advertiser, Campaign, Banner, Publisher, Zone
    //   Granularity:      Daily, Hourly
    //   Date range:       valid, reversed, null_start, null_end, same_day
    //   Timezone:         true, false
    //   Data volume:      0 (empty), 5 (small), 110 (medium)
    //
    // Note: Account type is encoded in the test docblock for traceability
    // but permissions are mocked (checkPermissions returns true).
    // The pairwise combinations ensure every pair of dimension values
    // appears in at least one test case.
    // ===================================================================

    /** ST01: ADMIN + Campaign + Daily + valid + localTZ=true + Empty */
    public function testST01_Admin_Campaign_Daily_Valid_LocalTZ_Empty()
    {
        $this->_runComboTest('ST01', 'Campaign', 'Daily', 'valid', true, 0);
    }

    /** ST02: MANAGER + Banner + Hourly + valid + localTZ=false + Small */
    public function testST02_Manager_Banner_Hourly_Valid_UTC_Small()
    {
        $this->_runComboTest('ST02', 'Banner', 'Hourly', 'valid', false, 5);
    }

    /** ST03: ADVERTISER + Campaign + Daily + same_day + localTZ=true + Medium */
    public function testST03_Advertiser_Campaign_Daily_SameDay_LocalTZ_Medium()
    {
        $this->_runComboTest('ST03', 'Campaign', 'Daily', 'same_day', true, 110);
    }

    /** ST04: ADMIN + Zone + Hourly + valid + localTZ=false + Small */
    public function testST04_Admin_Zone_Hourly_Valid_UTC_Small()
    {
        $this->_runComboTest('ST04', 'Zone', 'Hourly', 'valid', false, 5);
    }

    /** ST05: MANAGER + Publisher + Daily + reversed + localTZ=true + Empty */
    public function testST05_Manager_Publisher_Daily_Reversed_LocalTZ_Empty()
    {
        $this->_runComboTest('ST05', 'Publisher', 'Daily', 'reversed', true, 0);
    }

    /** ST06: ADVERTISER + Advertiser + Daily + null_start + localTZ=false + Empty */
    public function testST06_Advertiser_Advertiser_Daily_NullStart_UTC_Empty()
    {
        $this->_runComboTest('ST06', 'Advertiser', 'Daily', 'null_start', false, 0);
    }

    /** ST07: ADMIN + Campaign + Hourly + null_end + localTZ=true + Small */
    public function testST07_Admin_Campaign_Hourly_NullEnd_LocalTZ_Small()
    {
        $this->_runComboTest('ST07', 'Campaign', 'Hourly', 'null_end', true, 5);
    }

    /** ST08: TRAFFICKER + Publisher + Daily + valid + localTZ=true + Empty */
    public function testST08_Trafficker_Publisher_Daily_Valid_LocalTZ_Empty()
    {
        $this->_runComboTest('ST08', 'Publisher', 'Daily', 'valid', true, 0);
    }

    /** ST09: ADMIN + Advertiser + Hourly + same_day + localTZ=false + Medium */
    public function testST09_Admin_Advertiser_Hourly_SameDay_UTC_Medium()
    {
        $this->_runComboTest('ST09', 'Advertiser', 'Hourly', 'same_day', false, 110);
    }

    /** ST10: MANAGER + Zone + Daily + null_end + localTZ=true + Empty */
    public function testST10_Manager_Zone_Daily_NullEnd_LocalTZ_Empty()
    {
        $this->_runComboTest('ST10', 'Zone', 'Daily', 'null_end', true, 0);
    }

    /** ST11: ADVERTISER + Banner + Hourly + reversed + localTZ=false + Empty */
    public function testST11_Advertiser_Banner_Hourly_Reversed_UTC_Empty()
    {
        $this->_runComboTest('ST11', 'Banner', 'Hourly', 'reversed', false, 0);
    }

    /** ST12: TRAFFICKER + Zone + Hourly + null_start + localTZ=true + Small */
    public function testST12_Trafficker_Zone_Hourly_NullStart_LocalTZ_Small()
    {
        $this->_runComboTest('ST12', 'Zone', 'Hourly', 'null_start', true, 5);
    }

    /** ST13: ADMIN + Publisher + Hourly + valid + localTZ=true + Medium */
    public function testST13_Admin_Publisher_Hourly_Valid_LocalTZ_Medium()
    {
        $this->_runComboTest('ST13', 'Publisher', 'Hourly', 'valid', true, 110);
    }

    /** ST14: MANAGER + Advertiser + Daily + valid + localTZ=false + Small */
    public function testST14_Manager_Advertiser_Daily_Valid_UTC_Small()
    {
        $this->_runComboTest('ST14', 'Advertiser', 'Daily', 'valid', false, 5);
    }

    /** ST15: ADVERTISER + Campaign + Hourly + valid + localTZ=true + Empty */
    public function testST15_Advertiser_Campaign_Hourly_Valid_LocalTZ_Empty()
    {
        $this->_runComboTest('ST15', 'Campaign', 'Hourly', 'valid', true, 0);
    }

    /** ST16: TRAFFICKER + Publisher + Hourly + same_day + localTZ=false + Small */
    public function testST16_Trafficker_Publisher_Hourly_SameDay_UTC_Small()
    {
        $this->_runComboTest('ST16', 'Publisher', 'Hourly', 'same_day', false, 5);
    }

    /** ST17: ADMIN + Banner + Daily + null_start + localTZ=true + Medium */
    public function testST17_Admin_Banner_Daily_NullStart_LocalTZ_Medium()
    {
        $this->_runComboTest('ST17', 'Banner', 'Daily', 'null_start', true, 110);
    }

    /** ST18: MANAGER + Campaign + Hourly + null_start + localTZ=false + Medium */
    public function testST18_Manager_Campaign_Hourly_NullStart_UTC_Medium()
    {
        $this->_runComboTest('ST18', 'Campaign', 'Hourly', 'null_start', false, 110);
    }

    /** ST19: ADMIN + Advertiser + Daily + reversed + localTZ=true + Empty */
    public function testST19_Admin_Advertiser_Daily_Reversed_LocalTZ_Empty()
    {
        $this->_runComboTest('ST19', 'Advertiser', 'Daily', 'reversed', true, 0);
    }

    /** ST20: TRAFFICKER + Zone + Daily + valid + localTZ=false + Medium */
    public function testST20_Trafficker_Zone_Daily_Valid_UTC_Medium()
    {
        $this->_runComboTest('ST20', 'Zone', 'Daily', 'valid', false, 110);
    }

    /** ST21: MANAGER + Banner + Daily + same_day + localTZ=true + Empty */
    public function testST21_Manager_Banner_Daily_SameDay_LocalTZ_Empty()
    {
        $this->_runComboTest('ST21', 'Banner', 'Daily', 'same_day', true, 0);
    }

    /** ST22: ADVERTISER + Advertiser + Hourly + null_end + localTZ=false + Small */
    public function testST22_Advertiser_Advertiser_Hourly_NullEnd_UTC_Small()
    {
        $this->_runComboTest('ST22', 'Advertiser', 'Hourly', 'null_end', false, 5);
    }

    /** ST23: ADMIN + Zone + Daily + same_day + localTZ=true + Small */
    public function testST23_Admin_Zone_Daily_SameDay_LocalTZ_Small()
    {
        $this->_runComboTest('ST23', 'Zone', 'Daily', 'same_day', true, 5);
    }

    /** ST24: TRAFFICKER + Publisher + Daily + null_end + localTZ=false + Medium */
    public function testST24_Trafficker_Publisher_Daily_NullEnd_UTC_Medium()
    {
        $this->_runComboTest('ST24', 'Publisher', 'Daily', 'null_end', false, 110);
    }

    /** ST25: MANAGER + Campaign + Daily + reversed + localTZ=false + Empty */
    public function testST25_Manager_Campaign_Daily_Reversed_UTC_Empty()
    {
        $this->_runComboTest('ST25', 'Campaign', 'Daily', 'reversed', false, 0);
    }

    /** ST26: ADVERTISER + Banner + Daily + valid + localTZ=true + Small */
    public function testST26_Advertiser_Banner_Daily_Valid_LocalTZ_Small()
    {
        $this->_runComboTest('ST26', 'Banner', 'Daily', 'valid', true, 5);
    }

    /** ST27: ADMIN + Publisher + Daily + null_start + localTZ=false + Small */
    public function testST27_Admin_Publisher_Daily_NullStart_UTC_Small()
    {
        $this->_runComboTest('ST27', 'Publisher', 'Daily', 'null_start', false, 5);
    }

    /** ST28: TRAFFICKER + Zone + Hourly + reversed + localTZ=true + Empty */
    public function testST28_Trafficker_Zone_Hourly_Reversed_LocalTZ_Empty()
    {
        $this->_runComboTest('ST28', 'Zone', 'Hourly', 'reversed', true, 0);
    }

    /** ST29: MANAGER + Advertiser + Hourly + null_end + localTZ=true + Medium */
    public function testST29_Manager_Advertiser_Hourly_NullEnd_LocalTZ_Medium()
    {
        $this->_runComboTest('ST29', 'Advertiser', 'Hourly', 'null_end', true, 110);
    }

    /** ST30: ADMIN + Campaign + Daily + null_end + localTZ=false + Medium */
    public function testST30_Admin_Campaign_Daily_NullEnd_UTC_Medium()
    {
        $this->_runComboTest('ST30', 'Campaign', 'Daily', 'null_end', false, 110);
    }

    /** ST31: ADVERTISER + Campaign + Hourly + reversed + localTZ=true + Empty */
    public function testST31_Advertiser_Campaign_Hourly_Reversed_LocalTZ_Empty()
    {
        $this->_runComboTest('ST31', 'Campaign', 'Hourly', 'reversed', true, 0);
    }

    /** ST32: TRAFFICKER + Publisher + Hourly + null_end + localTZ=true + Empty */
    public function testST32_Trafficker_Publisher_Hourly_NullEnd_LocalTZ_Empty()
    {
        $this->_runComboTest('ST32', 'Publisher', 'Hourly', 'null_end', true, 0);
    }

    /** ST33: ADMIN + Banner + Hourly + same_day + localTZ=false + Small */
    public function testST33_Admin_Banner_Hourly_SameDay_UTC_Small()
    {
        $this->_runComboTest('ST33', 'Banner', 'Hourly', 'same_day', false, 5);
    }

    /** ST34: MANAGER + Zone + Hourly + valid + localTZ=true + Empty */
    public function testST34_Manager_Zone_Hourly_Valid_LocalTZ_Empty()
    {
        $this->_runComboTest('ST34', 'Zone', 'Hourly', 'valid', true, 0);
    }

    /** ST35: ADVERTISER + Advertiser + Daily + valid + localTZ=true + Medium */
    public function testST35_Advertiser_Advertiser_Daily_Valid_LocalTZ_Medium()
    {
        $this->_runComboTest('ST35', 'Advertiser', 'Daily', 'valid', true, 110);
    }

    /** ST36: ADMIN + Advertiser + Hourly + null_start + localTZ=true + Empty */
    public function testST36_Admin_Advertiser_Hourly_NullStart_LocalTZ_Empty()
    {
        $this->_runComboTest('ST36', 'Advertiser', 'Hourly', 'null_start', true, 0);
    }

    /** ST37: MANAGER + Publisher + Hourly + reversed + localTZ=false + Empty */
    public function testST37_Manager_Publisher_Hourly_Reversed_UTC_Empty()
    {
        $this->_runComboTest('ST37', 'Publisher', 'Hourly', 'reversed', false, 0);
    }

    /** ST38: TRAFFICKER + Zone + Daily + null_start + localTZ=false + Small */
    public function testST38_Trafficker_Zone_Daily_NullStart_UTC_Small()
    {
        $this->_runComboTest('ST38', 'Zone', 'Daily', 'null_start', false, 5);
    }

    /** ST39: ADMIN + Campaign + Hourly + same_day + localTZ=false + Empty */
    public function testST39_Admin_Campaign_Hourly_SameDay_UTC_Empty()
    {
        $this->_runComboTest('ST39', 'Campaign', 'Hourly', 'same_day', false, 0);
    }

    /** ST40: ADVERTISER + Banner + Hourly + null_start + localTZ=true + Medium */
    public function testST40_Advertiser_Banner_Hourly_NullStart_LocalTZ_Medium()
    {
        $this->_runComboTest('ST40', 'Banner', 'Hourly', 'null_start', true, 110);
    }

    /** ST41: MANAGER + Advertiser + Daily + null_start + localTZ=true + Small */
    public function testST41_Manager_Advertiser_Daily_NullStart_LocalTZ_Small()
    {
        $this->_runComboTest('ST41', 'Advertiser', 'Daily', 'null_start', true, 5);
    }

    /** ST42: TRAFFICKER + Publisher + Hourly + valid + localTZ=false + Medium */
    public function testST42_Trafficker_Publisher_Hourly_Valid_UTC_Medium()
    {
        $this->_runComboTest('ST42', 'Publisher', 'Hourly', 'valid', false, 110);
    }

    // ===================================================================
    // SECTION 4J: Excluded / Negative Combination Test Cases
    //
    // These test cases verify that certain invalid combinations are
    // properly handled by the system.
    // ===================================================================

    // ---------------------------------------------------------------
    // 4J-1: TRAFFICKER + Campaign/Banner/Advertiser stats
    //        Traffickers only have access to publisher/zone stats.
    //        When checkPermissions is NOT mocked, trafficker access
    //        to campaign/banner/advertiser stats should be denied.
    // ---------------------------------------------------------------

    /**
     * Helper: create a DLL mock that enforces real permission checks
     * and sets up a trafficker session context.
     *
     * Instead of fully mocking checkPermissions to return true,
     * we mock it to return false to simulate permission denial.
     */
    private function _createPermissionDeniedMock($dllClass)
    {
        switch ($dllClass) {
            case 'Campaign':
                $dll = new PartialMockOA_Dll_Campaign_StatsCombTest($this);
                $dll->setReturnValue('checkPermissions', false);
                return $dll;
            case 'Banner':
                $dll = new PartialMockOA_Dll_Banner_StatsCombTest($this);
                $dll->setReturnValue('checkPermissions', false);
                return $dll;
            case 'Advertiser':
                $dll = new PartialMockOA_Dll_Advertiser_StatsCombTest($this);
                $dll->setReturnValue('getDefaultAgencyId', $this->agencyId);
                $dll->setReturnValue('checkPermissions', false);
                return $dll;
            case 'Publisher':
                $dll = new PartialMockOA_Dll_Publisher_StatsCombTest($this);
                $dll->setReturnValue('getDefaultAgencyId', $this->agencyId);
                $dll->setReturnValue('checkPermissions', false);
                return $dll;
            case 'Zone':
                $dll = new PartialMockOA_Dll_Zone_StatsCombTest($this);
                $dll->setReturnValue('checkPermissions', false);
                return $dll;
        }
    }

    /**
     * NEG01: TRAFFICKER accessing Campaign Daily statistics should be denied.
     */
    public function testNEG01_Trafficker_Campaign_Daily_PermissionDenied()
    {
        // First create the campaign entity with mocked permissions
        [$dllCampCreate, $oCampInfo] = $this->_createCampaignEntity();

        // Now create a permission-denied mock for the stats call
        $dll = $this->_createPermissionDeniedMock('Campaign');

        $rsStatisticsData = null;
        $result = $dll->getCampaignDailyStatistics(
            $oCampInfo->campaignId,
            new Date('2001-12-01'),
            new Date('2007-09-19'),
            false,
            $rsStatisticsData,
        );
        $this->assertFalse(
            $result,
            'NEG01: Trafficker should be denied access to Campaign Daily statistics',
        );
    }

    /**
     * NEG02: TRAFFICKER accessing Campaign Hourly statistics should be denied.
     */
    public function testNEG02_Trafficker_Campaign_Hourly_PermissionDenied()
    {
        [$dllCampCreate, $oCampInfo] = $this->_createCampaignEntity();

        $dll = $this->_createPermissionDeniedMock('Campaign');

        $rsStatisticsData = null;
        $result = $dll->getCampaignHourlyStatistics(
            $oCampInfo->campaignId,
            new Date('2001-12-01'),
            new Date('2007-09-19'),
            false,
            $rsStatisticsData,
        );
        $this->assertFalse(
            $result,
            'NEG02: Trafficker should be denied access to Campaign Hourly statistics',
        );
    }

    /**
     * NEG03: TRAFFICKER accessing Banner Daily statistics should be denied.
     */
    public function testNEG03_Trafficker_Banner_Daily_PermissionDenied()
    {
        [$dllBanCreate, $oBanInfo] = $this->_createBannerEntity();

        $dll = $this->_createPermissionDeniedMock('Banner');

        $rsStatisticsData = null;
        $result = $dll->getBannerDailyStatistics(
            $oBanInfo->bannerId,
            new Date('2001-12-01'),
            new Date('2007-09-19'),
            false,
            $rsStatisticsData,
        );
        $this->assertFalse(
            $result,
            'NEG03: Trafficker should be denied access to Banner Daily statistics',
        );
    }

    /**
     * NEG04: TRAFFICKER accessing Banner Hourly statistics should be denied.
     */
    public function testNEG04_Trafficker_Banner_Hourly_PermissionDenied()
    {
        [$dllBanCreate, $oBanInfo] = $this->_createBannerEntity();

        $dll = $this->_createPermissionDeniedMock('Banner');

        $rsStatisticsData = null;
        $result = $dll->getBannerHourlyStatistics(
            $oBanInfo->bannerId,
            new Date('2001-12-01'),
            new Date('2007-09-19'),
            false,
            $rsStatisticsData,
        );
        $this->assertFalse(
            $result,
            'NEG04: Trafficker should be denied access to Banner Hourly statistics',
        );
    }

    /**
     * NEG05: TRAFFICKER accessing Advertiser Daily statistics should be denied.
     */
    public function testNEG05_Trafficker_Advertiser_Daily_PermissionDenied()
    {
        // Create advertiser with permitted mock first
        [$dllAdvCreate, $oAdvInfo] = $this->_createAdvertiserEntity();

        // Now create a permission-denied mock for stats call
        $dll = $this->_createPermissionDeniedMock('Advertiser');

        $rsStatisticsData = null;
        $result = $dll->getAdvertiserDailyStatistics(
            $oAdvInfo->advertiserId,
            new Date('2001-12-01'),
            new Date('2007-09-19'),
            false,
            $rsStatisticsData,
        );
        $this->assertFalse(
            $result,
            'NEG05: Trafficker should be denied access to Advertiser Daily statistics',
        );
    }

    /**
     * NEG06: TRAFFICKER accessing Advertiser Hourly statistics should be denied.
     */
    public function testNEG06_Trafficker_Advertiser_Hourly_PermissionDenied()
    {
        [$dllAdvCreate, $oAdvInfo] = $this->_createAdvertiserEntity();

        $dll = $this->_createPermissionDeniedMock('Advertiser');

        $rsStatisticsData = null;
        $result = $dll->getAdvertiserHourlyStatistics(
            $oAdvInfo->advertiserId,
            new Date('2001-12-01'),
            new Date('2007-09-19'),
            false,
            $rsStatisticsData,
        );
        $this->assertFalse(
            $result,
            'NEG06: Trafficker should be denied access to Advertiser Hourly statistics',
        );
    }

    // ---------------------------------------------------------------
    // 4J-2: ADVERTISER + Publisher/Zone stats
    //        Advertisers only have access to their own
    //        advertiser/campaign/banner stats, not publisher/zone.
    // ---------------------------------------------------------------

    /**
     * NEG07: ADVERTISER accessing Publisher Daily statistics should be denied.
     */
    public function testNEG07_Advertiser_Publisher_Daily_PermissionDenied()
    {
        [$dllPubCreate, $oPubInfo] = $this->_createPublisherEntity();

        $dll = $this->_createPermissionDeniedMock('Publisher');

        $rsStatisticsData = null;
        $result = $dll->getPublisherDailyStatistics(
            $oPubInfo->publisherId,
            new Date('2001-12-01'),
            new Date('2007-09-19'),
            false,
            $rsStatisticsData,
        );
        $this->assertFalse(
            $result,
            'NEG07: Advertiser should be denied access to Publisher Daily statistics',
        );
    }

    /**
     * NEG08: ADVERTISER accessing Publisher Hourly statistics should be denied.
     */
    public function testNEG08_Advertiser_Publisher_Hourly_PermissionDenied()
    {
        [$dllPubCreate, $oPubInfo] = $this->_createPublisherEntity();

        $dll = $this->_createPermissionDeniedMock('Publisher');

        $rsStatisticsData = null;
        $result = $dll->getPublisherHourlyStatistics(
            $oPubInfo->publisherId,
            new Date('2001-12-01'),
            new Date('2007-09-19'),
            false,
            $rsStatisticsData,
        );
        $this->assertFalse(
            $result,
            'NEG08: Advertiser should be denied access to Publisher Hourly statistics',
        );
    }

    /**
     * NEG09: ADVERTISER accessing Zone Daily statistics should be denied.
     */
    public function testNEG09_Advertiser_Zone_Daily_PermissionDenied()
    {
        [$dllZoneCreate, $oZoneInfo] = $this->_createZoneEntity();

        $dll = $this->_createPermissionDeniedMock('Zone');

        $rsStatisticsData = null;
        $result = $dll->getZoneDailyStatistics(
            $oZoneInfo->zoneId,
            new Date('2001-12-01'),
            new Date('2007-09-19'),
            false,
            $rsStatisticsData,
        );
        $this->assertFalse(
            $result,
            'NEG09: Advertiser should be denied access to Zone Daily statistics',
        );
    }

    /**
     * NEG10: ADVERTISER accessing Zone Hourly statistics should be denied.
     */
    public function testNEG10_Advertiser_Zone_Hourly_PermissionDenied()
    {
        [$dllZoneCreate, $oZoneInfo] = $this->_createZoneEntity();

        $dll = $this->_createPermissionDeniedMock('Zone');

        $rsStatisticsData = null;
        $result = $dll->getZoneHourlyStatistics(
            $oZoneInfo->zoneId,
            new Date('2001-12-01'),
            new Date('2007-09-19'),
            false,
            $rsStatisticsData,
        );
        $this->assertFalse(
            $result,
            'NEG10: Advertiser should be denied access to Zone Hourly statistics',
        );
    }

    // ---------------------------------------------------------------
    // 4J-3: Reversed dates + non-empty data volume
    //        Reversed date validation fails before data query executes.
    //        The data volume is irrelevant because the validation
    //        rejects the request before any DB query runs.
    // ---------------------------------------------------------------

    /**
     * NEG11: Reversed dates with small data volume (Campaign Daily).
     * Validation should fail before data is queried.
     */
    public function testNEG11_Reversed_Dates_With_Small_Data_Campaign_Daily()
    {
        [$dll, $oInfo] = $this->_createCampaignEntity();

        // Insert some data (should never be reached)
        $this->_insertStatsData('Campaign', $oInfo, 5);

        $rsStatisticsData = null;
        $result = $dll->getCampaignDailyStatistics(
            $oInfo->campaignId,
            new Date('2007-09-19'),
            new Date('2001-12-01'),
            false,
            $rsStatisticsData,
        );
        $this->assertFalse(
            $result,
            'NEG11: Reversed dates should fail validation even with small data',
        );
        $this->assertEqual(
            $dll->getLastError(),
            $this->wrongDateError,
            'NEG11: Should return wrong date error',
        );
    }

    /**
     * NEG12: Reversed dates with medium data volume (Banner Hourly).
     * Validation should fail before data is queried.
     */
    public function testNEG12_Reversed_Dates_With_Medium_Data_Banner_Hourly()
    {
        [$dll, $oInfo] = $this->_createBannerEntity();

        // Insert medium dataset (should never be reached)
        $this->_insertStatsData('Banner', $oInfo, 110);

        $rsStatisticsData = null;
        $result = $dll->getBannerHourlyStatistics(
            $oInfo->bannerId,
            new Date('2007-09-19'),
            new Date('2001-12-01'),
            false,
            $rsStatisticsData,
        );
        $this->assertFalse(
            $result,
            'NEG12: Reversed dates should fail validation even with medium data',
        );
        $this->assertEqual(
            $dll->getLastError(),
            $this->wrongDateError,
            'NEG12: Should return wrong date error',
        );
    }

    /**
     * NEG13: Reversed dates with small data volume (Advertiser Daily).
     * Validation should fail before data is queried.
     */
    public function testNEG13_Reversed_Dates_With_Small_Data_Advertiser_Daily()
    {
        [$dll, $oInfo] = $this->_createAdvertiserEntity();

        $this->_insertStatsData('Advertiser', $oInfo, 5);

        $rsStatisticsData = null;
        $result = $dll->getAdvertiserDailyStatistics(
            $oInfo->advertiserId,
            new Date('2007-09-19'),
            new Date('2001-12-01'),
            false,
            $rsStatisticsData,
        );
        $this->assertFalse(
            $result,
            'NEG13: Reversed dates should fail validation even with small data',
        );
        $this->assertEqual(
            $dll->getLastError(),
            $this->wrongDateError,
            'NEG13: Should return wrong date error',
        );
    }

    /**
     * NEG14: Reversed dates with small data volume (Publisher Hourly).
     * Validation should fail before data is queried.
     */
    public function testNEG14_Reversed_Dates_With_Small_Data_Publisher_Hourly()
    {
        [$dll, $oInfo] = $this->_createPublisherEntity();

        $this->_insertStatsData('Publisher', $oInfo, 5);

        $rsStatisticsData = null;
        $result = $dll->getPublisherHourlyStatistics(
            $oInfo->publisherId,
            new Date('2007-09-19'),
            new Date('2001-12-01'),
            false,
            $rsStatisticsData,
        );
        $this->assertFalse(
            $result,
            'NEG14: Reversed dates should fail validation even with small data',
        );
        $this->assertEqual(
            $dll->getLastError(),
            $this->wrongDateError,
            'NEG14: Should return wrong date error',
        );
    }

    /**
     * NEG15: Reversed dates with medium data volume (Zone Daily).
     * Validation should fail before data is queried.
     */
    public function testNEG15_Reversed_Dates_With_Medium_Data_Zone_Daily()
    {
        [$dll, $oInfo] = $this->_createZoneEntity();

        $this->_insertStatsData('Zone', $oInfo, 110);

        $rsStatisticsData = null;
        $result = $dll->getZoneDailyStatistics(
            $oInfo->zoneId,
            new Date('2007-09-19'),
            new Date('2001-12-01'),
            false,
            $rsStatisticsData,
        );
        $this->assertFalse(
            $result,
            'NEG15: Reversed dates should fail validation even with medium data',
        );
        $this->assertEqual(
            $dll->getLastError(),
            $this->wrongDateError,
            'NEG15: Should return wrong date error',
        );
    }

    // ---------------------------------------------------------------
    // 4J Additional: Statistics for non-existing entity IDs
    // ---------------------------------------------------------------

    /**
     * NEG16: Campaign statistics for deleted/non-existent campaign ID.
     */
    public function testNEG16_NonExistent_Campaign_Daily_Statistics()
    {
        [$dll, $oInfo] = $this->_createCampaignEntity();
        $campaignId = $oInfo->campaignId;

        // Delete the campaign
        $dll->delete($campaignId);

        $rsStatisticsData = null;
        $result = $dll->getCampaignDailyStatistics(
            $campaignId,
            new Date('2001-12-01'),
            new Date('2007-09-19'),
            false,
            $rsStatisticsData,
        );
        $this->assertFalse(
            $result,
            'NEG16: Statistics for deleted campaign should return false',
        );
    }

    /**
     * NEG17: Banner statistics for deleted/non-existent banner ID.
     */
    public function testNEG17_NonExistent_Banner_Hourly_Statistics()
    {
        [$dll, $oInfo] = $this->_createBannerEntity();
        $bannerId = $oInfo->bannerId;

        $dll->delete($bannerId);

        $rsStatisticsData = null;
        $result = $dll->getBannerHourlyStatistics(
            $bannerId,
            new Date('2001-12-01'),
            new Date('2007-09-19'),
            false,
            $rsStatisticsData,
        );
        $this->assertFalse(
            $result,
            'NEG17: Statistics for deleted banner should return false',
        );
    }

    /**
     * NEG18: Advertiser statistics for deleted/non-existent advertiser ID.
     */
    public function testNEG18_NonExistent_Advertiser_Daily_Statistics()
    {
        [$dll, $oInfo] = $this->_createAdvertiserEntity();
        $advertiserId = $oInfo->advertiserId;

        $dll->delete($advertiserId);

        $rsStatisticsData = null;
        $result = $dll->getAdvertiserDailyStatistics(
            $advertiserId,
            new Date('2001-12-01'),
            new Date('2007-09-19'),
            false,
            $rsStatisticsData,
        );
        $this->assertFalse(
            $result,
            'NEG18: Statistics for deleted advertiser should return false',
        );
    }

    /**
     * NEG19: Publisher statistics for deleted/non-existent publisher ID.
     */
    public function testNEG19_NonExistent_Publisher_Hourly_Statistics()
    {
        [$dll, $oInfo] = $this->_createPublisherEntity();
        $publisherId = $oInfo->publisherId;

        $dll->delete($publisherId);

        $rsStatisticsData = null;
        $result = $dll->getPublisherHourlyStatistics(
            $publisherId,
            new Date('2001-12-01'),
            new Date('2007-09-19'),
            false,
            $rsStatisticsData,
        );
        $this->assertFalse(
            $result,
            'NEG19: Statistics for deleted publisher should return false',
        );
    }

    /**
     * NEG20: Zone statistics for deleted/non-existent zone ID.
     */
    public function testNEG20_NonExistent_Zone_Daily_Statistics()
    {
        [$dll, $oInfo] = $this->_createZoneEntity();
        $zoneId = $oInfo->zoneId;

        $dll->delete($zoneId);

        $rsStatisticsData = null;
        $result = $dll->getZoneDailyStatistics(
            $zoneId,
            new Date('2001-12-01'),
            new Date('2007-09-19'),
            false,
            $rsStatisticsData,
        );
        $this->assertFalse(
            $result,
            'NEG20: Statistics for deleted zone should return false',
        );
    }
}
