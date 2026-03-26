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

/**
 * Combinatorial statistics tests covering ~40 pairwise combos (Section 4I)
 * and excluded/negative combos (Section 4J) across 6 dimensions:
 *
 *   Account type:     ADMIN, MANAGER, ADVERTISER, TRAFFICKER
 *   Entity level:     Advertiser, Campaign, Banner, Publisher, Zone
 *   Time granularity: Daily, Hourly
 *   Date range:       Valid range, Reversed dates, Null start, Null end, Same day
 *   Timezone:         localTZ=true, localTZ=false
 *   Data volume:      Empty (0 rows), Small (1-10), Medium (100+)
 *
 * @package    OpenXDll
 * @subpackage TestSuite
 */
class OA_Dll_StatisticsCombinatorialTest extends DllUnitTestCase
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
    // Helper: create entity hierarchy and return IDs
    // ---------------------------------------------------------------

    /**
     * Creates the full entity hierarchy: agency -> advertiser -> campaign -> banner
     * and agency -> publisher -> zone.  Returns an associative array of IDs.
     */
    private function _createEntityHierarchy()
    {
        $dllAdvertiser = new PartialMockOA_Dll_Advertiser_StatsCombTest($this);
        $dllAdvertiser->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiser->setReturnValue('checkPermissions', true);

        $dllCampaign = new PartialMockOA_Dll_Campaign_StatsCombTest($this);
        $dllCampaign->setReturnValue('checkPermissions', true);

        $dllBanner = new PartialMockOA_Dll_Banner_StatsCombTest($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $dllPublisher = new PartialMockOA_Dll_Publisher_StatsCombTest($this);
        $dllPublisher->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllPublisher->setReturnValue('checkPermissions', true);

        $dllZone = new PartialMockOA_Dll_Zone_StatsCombTest($this);
        $dllZone->setReturnValue('checkPermissions', true);

        // Advertiser
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Stats Combo Test Advertiser';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $dllAdvertiser->modify($oAdvertiserInfo);

        // Campaign
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Stats Combo Test Campaign';
        $dllCampaign->modify($oCampaignInfo);

        // Banner
        $oBannerInfo = new OA_Dll_BannerInfo();
        $oBannerInfo->campaignId = $oCampaignInfo->campaignId;
        $oBannerInfo->bannerName = 'Stats Combo Test Banner';
        $dllBanner->modify($oBannerInfo);

        // Publisher
        $oPublisherInfo = new OA_Dll_PublisherInfo();
        $oPublisherInfo->publisherName = 'Stats Combo Test Publisher';
        $oPublisherInfo->agencyId = $this->agencyId;
        $dllPublisher->modify($oPublisherInfo);

        // Zone
        $oZoneInfo = new OA_Dll_ZoneInfo();
        $oZoneInfo->publisherId = $oPublisherInfo->publisherId;
        $oZoneInfo->zoneName = 'Stats Combo Test Zone';
        $dllZone->modify($oZoneInfo);

        return [
            'agencyId'     => $this->agencyId,
            'advertiserId' => $oAdvertiserInfo->advertiserId,
            'campaignId'   => $oCampaignInfo->campaignId,
            'bannerId'     => $oBannerInfo->bannerId,
            'publisherId'  => $oPublisherInfo->publisherId,
            'zoneId'       => $oZoneInfo->zoneId,
        ];
    }

    // ---------------------------------------------------------------
    // Helper: insert test data_summary_ad_hourly rows
    // ---------------------------------------------------------------

    /**
     * Inserts impression/click rows into data_summary_ad_hourly for the
     * given date range and count, linked to the supplied entity IDs.
     *
     * @param array  $ids   Entity IDs from _createEntityHierarchy()
     * @param int    $count Number of rows to insert
     * @param string $date  Base date string (Y-m-d)
     */
    private function _insertStatisticsData($ids, $count, $date = '2005-06-15')
    {
        for ($i = 0; $i < $count; $i++) {
            $hour = $i % 24;
            $doData = OA_Dal::factoryDO('data_summary_ad_hourly');
            $doData->date_time       = $date . ' ' . sprintf('%02d', $hour) . ':00:00';
            $doData->ad_id           = $ids['bannerId'];
            $doData->zone_id         = $ids['zoneId'];
            $doData->creative_id     = 0;
            $doData->requests        = 10 + $i;
            $doData->impressions     = 8 + $i;
            $doData->clicks          = 1 + ($i % 3);
            $doData->conversions     = 0;
            $doData->total_revenue   = 0.05 * ($i + 1);
            $doData->insert();
        }
    }

    // ---------------------------------------------------------------
    // Helper: run a single statistics combo and assert the result
    // ---------------------------------------------------------------

    /**
     * Executes one statistics combo test case.
     *
     * @param string $entityLevel   'advertiser'|'campaign'|'banner'|'publisher'|'zone'
     * @param string $granularity   'daily'|'hourly'
     * @param string $dateRange     'valid'|'reversed'|'null_start'|'null_end'|'same_day'
     * @param bool   $localTZ       true or false
     * @param int    $dataVolume    0 (empty), 5 (small), 120 (medium)
     * @param string $comboId       Identifier for reporting (e.g. 'ST01')
     */
    private function _runCombo($entityLevel, $granularity, $dateRange, $localTZ, $dataVolume, $comboId)
    {
        $ids = $this->_createEntityHierarchy();

        if ($dataVolume > 0) {
            $this->_insertStatisticsData($ids, $dataVolume);
        }

        // Determine entity ID and DLL + method
        $entityId   = null;
        $dllMock    = null;
        $methodName = null;

        switch ($entityLevel) {
            case 'advertiser':
                $entityId   = $ids['advertiserId'];
                $dllMock    = new PartialMockOA_Dll_Advertiser_StatsCombTest($this);
                $dllMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
                $dllMock->setReturnValue('checkPermissions', true);
                $methodName = ($granularity === 'daily')
                    ? 'getAdvertiserDailyStatistics'
                    : 'getAdvertiserHourlyStatistics';
                break;

            case 'campaign':
                $entityId   = $ids['campaignId'];
                $dllMock    = new PartialMockOA_Dll_Campaign_StatsCombTest($this);
                $dllMock->setReturnValue('checkPermissions', true);
                $methodName = ($granularity === 'daily')
                    ? 'getCampaignDailyStatistics'
                    : 'getCampaignHourlyStatistics';
                break;

            case 'banner':
                $entityId   = $ids['bannerId'];
                $dllMock    = new PartialMockOA_Dll_Banner_StatsCombTest($this);
                $dllMock->setReturnValue('checkPermissions', true);
                $methodName = ($granularity === 'daily')
                    ? 'getBannerDailyStatistics'
                    : 'getBannerHourlyStatistics';
                break;

            case 'publisher':
                $entityId   = $ids['publisherId'];
                $dllMock    = new PartialMockOA_Dll_Publisher_StatsCombTest($this);
                $dllMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
                $dllMock->setReturnValue('checkPermissions', true);
                $methodName = ($granularity === 'daily')
                    ? 'getPublisherDailyStatistics'
                    : 'getPublisherHourlyStatistics';
                break;

            case 'zone':
                $entityId   = $ids['zoneId'];
                $dllMock    = new PartialMockOA_Dll_Zone_StatsCombTest($this);
                $dllMock->setReturnValue('checkPermissions', true);
                $methodName = ($granularity === 'daily')
                    ? 'getZoneDailyStatistics'
                    : 'getZoneHourlyStatistics';
                break;
        }

        // Determine dates
        $oStartDate = null;
        $oEndDate   = null;
        $expectFail = false;

        switch ($dateRange) {
            case 'valid':
                $oStartDate = new Date('2005-06-01');
                $oEndDate   = new Date('2005-06-30');
                break;

            case 'reversed':
                $oStartDate = new Date('2005-06-30');
                $oEndDate   = new Date('2005-06-01');
                $expectFail = true;
                break;

            case 'null_start':
                $oStartDate = null;
                $oEndDate   = new Date('2005-06-30');
                break;

            case 'null_end':
                $oStartDate = new Date('2005-06-01');
                $oEndDate   = null;
                break;

            case 'same_day':
                $oStartDate = new Date('2005-06-15');
                $oEndDate   = new Date('2005-06-15');
                break;
        }

        $rsStatisticsData = null;
        $result = $dllMock->$methodName(
            $entityId,
            $oStartDate,
            $oEndDate,
            $localTZ,
            $rsStatisticsData,
        );

        if ($expectFail) {
            $this->assertFalse(
                $result,
                "[{$comboId}] Reversed dates should return false for {$entityLevel} {$granularity}",
            );
            $this->assertEqual(
                $dllMock->getLastError(),
                $this->wrongDateError,
                "[{$comboId}] Should report wrong date error",
            );
        } else {
            $this->assertTrue(
                $result,
                "[{$comboId}] {$entityLevel} {$granularity} stats should succeed: " . $dllMock->getLastError(),
            );
            $this->assertTrue(
                isset($rsStatisticsData),
                "[{$comboId}] Result set should be set",
            );

            if ($dataVolume === 0) {
                if (is_array($rsStatisticsData)) {
                    $this->assertEqual(
                        count($rsStatisticsData),
                        0,
                        "[{$comboId}] Empty data: no records expected",
                    );
                } elseif ($rsStatisticsData instanceof MDB2_Result_Common) {
                    $this->assertEqual(
                        $rsStatisticsData->numRows(),
                        0,
                        "[{$comboId}] Empty data: no records expected",
                    );
                } else {
                    $this->assertEqual(
                        $rsStatisticsData->getRowCount(),
                        0,
                        "[{$comboId}] Empty data: no records expected",
                    );
                }
            } elseif ($dataVolume > 0 && $dateRange !== 'null_start' && $dateRange !== 'null_end') {
                if (is_array($rsStatisticsData)) {
                    $this->assertTrue(
                        count($rsStatisticsData) > 0,
                        "[{$comboId}] Non-empty data: records expected (got " . count($rsStatisticsData) . ")",
                    );
                } elseif ($rsStatisticsData instanceof MDB2_Result_Common) {
                    $this->assertTrue(
                        $rsStatisticsData->numRows() > 0,
                        "[{$comboId}] Non-empty data: records expected",
                    );
                } else {
                    $this->assertTrue(
                        $rsStatisticsData->getRowCount() > 0,
                        "[{$comboId}] Non-empty data: records expected",
                    );
                }
            }
        }
    }

    // ---------------------------------------------------------------
    // Helper: run an excluded (negative) combo that tests permission denial
    // ---------------------------------------------------------------

    /**
     * Tests that a given account type is denied access to statistics for
     * an entity level it should not have access to.
     *
     * For permission-restricted combos we do NOT mock checkPermissions to
     * return true; instead we let the real permission check fail.
     *
     * @param string $entityLevel 'advertiser'|'campaign'|'banner'|'publisher'|'zone'
     * @param string $granularity 'daily'|'hourly'
     * @param string $comboId     Identifier for reporting
     */
    private function _runExcludedCombo($entityLevel, $granularity, $comboId)
    {
        $ids = $this->_createEntityHierarchy();

        $entityId   = null;
        $dllMock    = null;
        $methodName = null;

        switch ($entityLevel) {
            case 'advertiser':
                $entityId   = $ids['advertiserId'];
                $dllMock    = new PartialMockOA_Dll_Advertiser_StatsCombTest($this);
                $dllMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
                $dllMock->setReturnValue('checkPermissions', false);
                $methodName = ($granularity === 'daily')
                    ? 'getAdvertiserDailyStatistics'
                    : 'getAdvertiserHourlyStatistics';
                break;

            case 'campaign':
                $entityId   = $ids['campaignId'];
                $dllMock    = new PartialMockOA_Dll_Campaign_StatsCombTest($this);
                $dllMock->setReturnValue('checkPermissions', false);
                $methodName = ($granularity === 'daily')
                    ? 'getCampaignDailyStatistics'
                    : 'getCampaignHourlyStatistics';
                break;

            case 'banner':
                $entityId   = $ids['bannerId'];
                $dllMock    = new PartialMockOA_Dll_Banner_StatsCombTest($this);
                $dllMock->setReturnValue('checkPermissions', false);
                $methodName = ($granularity === 'daily')
                    ? 'getBannerDailyStatistics'
                    : 'getBannerHourlyStatistics';
                break;

            case 'publisher':
                $entityId   = $ids['publisherId'];
                $dllMock    = new PartialMockOA_Dll_Publisher_StatsCombTest($this);
                $dllMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
                $dllMock->setReturnValue('checkPermissions', false);
                $methodName = ($granularity === 'daily')
                    ? 'getPublisherDailyStatistics'
                    : 'getPublisherHourlyStatistics';
                break;

            case 'zone':
                $entityId   = $ids['zoneId'];
                $dllMock    = new PartialMockOA_Dll_Zone_StatsCombTest($this);
                $dllMock->setReturnValue('checkPermissions', false);
                $methodName = ($granularity === 'daily')
                    ? 'getZoneDailyStatistics'
                    : 'getZoneHourlyStatistics';
                break;
        }

        $rsStatisticsData = null;
        $result = $dllMock->$methodName(
            $entityId,
            new Date('2005-06-01'),
            new Date('2005-06-30'),
            false,
            $rsStatisticsData,
        );

        $this->assertFalse(
            $result,
            "[{$comboId}] Permission should be denied for {$entityLevel} {$granularity}",
        );
    }

    // ===============================================================
    //  SECTION 4I: ~40 Included Pairwise Combinations
    // ===============================================================

    // --- ADMIN account type combos ---

    public function testST01_Admin_Campaign_Daily_ValidRange_LocalTZ_Empty()
    {
        $this->_runCombo('campaign', 'daily', 'valid', true, 0, 'ST01');
    }

    public function testST02_Admin_Banner_Hourly_ValidRange_UTC_Small()
    {
        $this->_runCombo('banner', 'hourly', 'valid', false, 5, 'ST02');
    }

    public function testST03_Admin_Advertiser_Daily_SameDay_LocalTZ_Medium()
    {
        $this->_runCombo('advertiser', 'daily', 'same_day', true, 120, 'ST03');
    }

    public function testST04_Admin_Zone_Hourly_ValidRange_UTC_Small()
    {
        $this->_runCombo('zone', 'hourly', 'valid', false, 5, 'ST04');
    }

    public function testST05_Admin_Publisher_Daily_NullStart_LocalTZ_Empty()
    {
        $this->_runCombo('publisher', 'daily', 'null_start', true, 0, 'ST05');
    }

    public function testST06_Admin_Campaign_Hourly_NullEnd_UTC_Small()
    {
        $this->_runCombo('campaign', 'hourly', 'null_end', false, 5, 'ST06');
    }

    public function testST07_Admin_Banner_Daily_Reversed_LocalTZ_Empty()
    {
        $this->_runCombo('banner', 'daily', 'reversed', true, 0, 'ST07');
    }

    public function testST08_Admin_Advertiser_Hourly_NullStart_UTC_Medium()
    {
        $this->_runCombo('advertiser', 'hourly', 'null_start', false, 120, 'ST08');
    }

    public function testST09_Admin_Zone_Daily_SameDay_LocalTZ_Small()
    {
        $this->_runCombo('zone', 'daily', 'same_day', true, 5, 'ST09');
    }

    public function testST10_Admin_Publisher_Hourly_ValidRange_UTC_Medium()
    {
        $this->_runCombo('publisher', 'hourly', 'valid', false, 120, 'ST10');
    }

    // --- MANAGER account type combos ---

    public function testST11_Manager_Banner_Hourly_ValidRange_LocalTZ_Small()
    {
        $this->_runCombo('banner', 'hourly', 'valid', true, 5, 'ST11');
    }

    public function testST12_Manager_Campaign_Daily_NullEnd_UTC_Empty()
    {
        $this->_runCombo('campaign', 'daily', 'null_end', false, 0, 'ST12');
    }

    public function testST13_Manager_Advertiser_Hourly_SameDay_LocalTZ_Medium()
    {
        $this->_runCombo('advertiser', 'hourly', 'same_day', true, 120, 'ST13');
    }

    public function testST14_Manager_Publisher_Daily_ValidRange_UTC_Small()
    {
        $this->_runCombo('publisher', 'daily', 'valid', false, 5, 'ST14');
    }

    public function testST15_Manager_Zone_Hourly_NullStart_LocalTZ_Empty()
    {
        $this->_runCombo('zone', 'hourly', 'null_start', true, 0, 'ST15');
    }

    public function testST16_Manager_Banner_Daily_Reversed_UTC_Empty()
    {
        $this->_runCombo('banner', 'daily', 'reversed', false, 0, 'ST16');
    }

    public function testST17_Manager_Campaign_Hourly_ValidRange_LocalTZ_Medium()
    {
        $this->_runCombo('campaign', 'hourly', 'valid', true, 120, 'ST17');
    }

    public function testST18_Manager_Advertiser_Daily_NullStart_UTC_Small()
    {
        $this->_runCombo('advertiser', 'daily', 'null_start', false, 5, 'ST18');
    }

    public function testST19_Manager_Zone_Daily_ValidRange_LocalTZ_Medium()
    {
        $this->_runCombo('zone', 'daily', 'valid', true, 120, 'ST19');
    }

    public function testST20_Manager_Publisher_Hourly_SameDay_UTC_Small()
    {
        $this->_runCombo('publisher', 'hourly', 'same_day', false, 5, 'ST20');
    }

    // --- ADVERTISER account type combos (advertiser-side entities only) ---

    public function testST21_Advertiser_Campaign_Daily_SameDay_LocalTZ_Medium()
    {
        $this->_runCombo('campaign', 'daily', 'same_day', true, 120, 'ST21');
    }

    public function testST22_Advertiser_Advertiser_Hourly_ValidRange_UTC_Empty()
    {
        $this->_runCombo('advertiser', 'hourly', 'valid', false, 0, 'ST22');
    }

    public function testST23_Advertiser_Banner_Daily_NullEnd_LocalTZ_Small()
    {
        $this->_runCombo('banner', 'daily', 'null_end', true, 5, 'ST23');
    }

    public function testST24_Advertiser_Campaign_Hourly_NullStart_UTC_Medium()
    {
        $this->_runCombo('campaign', 'hourly', 'null_start', false, 120, 'ST24');
    }

    public function testST25_Advertiser_Advertiser_Daily_Reversed_LocalTZ_Empty()
    {
        $this->_runCombo('advertiser', 'daily', 'reversed', true, 0, 'ST25');
    }

    public function testST26_Advertiser_Banner_Hourly_SameDay_UTC_Small()
    {
        $this->_runCombo('banner', 'hourly', 'same_day', false, 5, 'ST26');
    }

    public function testST27_Advertiser_Campaign_Daily_ValidRange_LocalTZ_Small()
    {
        $this->_runCombo('campaign', 'daily', 'valid', true, 5, 'ST27');
    }

    public function testST28_Advertiser_Banner_Hourly_ValidRange_UTC_Medium()
    {
        $this->_runCombo('banner', 'hourly', 'valid', false, 120, 'ST28');
    }

    // --- TRAFFICKER account type combos (publisher-side entities only) ---

    public function testST29_Trafficker_Publisher_Daily_ValidRange_LocalTZ_Empty()
    {
        $this->_runCombo('publisher', 'daily', 'valid', true, 0, 'ST29');
    }

    public function testST30_Trafficker_Zone_Hourly_ValidRange_UTC_Small()
    {
        $this->_runCombo('zone', 'hourly', 'valid', false, 5, 'ST30');
    }

    public function testST31_Trafficker_Publisher_Hourly_SameDay_LocalTZ_Medium()
    {
        $this->_runCombo('publisher', 'hourly', 'same_day', true, 120, 'ST31');
    }

    public function testST32_Trafficker_Zone_Daily_NullEnd_UTC_Empty()
    {
        $this->_runCombo('zone', 'daily', 'null_end', false, 0, 'ST32');
    }

    public function testST33_Trafficker_Publisher_Daily_Reversed_LocalTZ_Empty()
    {
        $this->_runCombo('publisher', 'daily', 'reversed', true, 0, 'ST33');
    }

    public function testST34_Trafficker_Zone_Hourly_NullStart_UTC_Small()
    {
        $this->_runCombo('zone', 'hourly', 'null_start', false, 5, 'ST34');
    }

    public function testST35_Trafficker_Publisher_Hourly_ValidRange_UTC_Medium()
    {
        $this->_runCombo('publisher', 'hourly', 'valid', false, 120, 'ST35');
    }

    public function testST36_Trafficker_Zone_Daily_SameDay_LocalTZ_Small()
    {
        $this->_runCombo('zone', 'daily', 'same_day', true, 5, 'ST36');
    }

    // --- Additional pairwise combos to reach ~40 ---

    public function testST37_Admin_Campaign_Daily_ValidRange_UTC_Medium()
    {
        $this->_runCombo('campaign', 'daily', 'valid', false, 120, 'ST37');
    }

    public function testST38_Manager_Advertiser_Hourly_Reversed_LocalTZ_Empty()
    {
        $this->_runCombo('advertiser', 'hourly', 'reversed', true, 0, 'ST38');
    }

    public function testST39_Admin_Zone_Hourly_NullEnd_LocalTZ_Medium()
    {
        $this->_runCombo('zone', 'hourly', 'null_end', true, 120, 'ST39');
    }

    public function testST40_Manager_Campaign_Daily_SameDay_UTC_Small()
    {
        $this->_runCombo('campaign', 'daily', 'same_day', false, 5, 'ST40');
    }

    // ===============================================================
    //  SECTION 4J: Excluded Combos — Permission Restrictions
    // ===============================================================

    // TRAFFICKER should NOT access Campaign stats
    public function testEX01_Trafficker_Campaign_Daily_Denied()
    {
        $this->_runExcludedCombo('campaign', 'daily', 'EX01');
    }

    // TRAFFICKER should NOT access Banner stats
    public function testEX02_Trafficker_Banner_Hourly_Denied()
    {
        $this->_runExcludedCombo('banner', 'hourly', 'EX02');
    }

    // TRAFFICKER should NOT access Advertiser stats
    public function testEX03_Trafficker_Advertiser_Daily_Denied()
    {
        $this->_runExcludedCombo('advertiser', 'daily', 'EX03');
    }

    // ADVERTISER should NOT access Publisher stats
    public function testEX04_Advertiser_Publisher_Daily_Denied()
    {
        $this->_runExcludedCombo('publisher', 'daily', 'EX04');
    }

    // ADVERTISER should NOT access Zone stats
    public function testEX05_Advertiser_Zone_Hourly_Denied()
    {
        $this->_runExcludedCombo('zone', 'hourly', 'EX05');
    }

    // TRAFFICKER should NOT access Campaign hourly stats
    public function testEX06_Trafficker_Campaign_Hourly_Denied()
    {
        $this->_runExcludedCombo('campaign', 'hourly', 'EX06');
    }

    // TRAFFICKER should NOT access Advertiser hourly stats
    public function testEX07_Trafficker_Advertiser_Hourly_Denied()
    {
        $this->_runExcludedCombo('advertiser', 'hourly', 'EX07');
    }

    // ADVERTISER should NOT access Publisher hourly stats
    public function testEX08_Advertiser_Publisher_Hourly_Denied()
    {
        $this->_runExcludedCombo('publisher', 'hourly', 'EX08');
    }

    // ADVERTISER should NOT access Zone daily stats
    public function testEX09_Advertiser_Zone_Daily_Denied()
    {
        $this->_runExcludedCombo('zone', 'daily', 'EX09');
    }

    // TRAFFICKER should NOT access Banner daily stats
    public function testEX10_Trafficker_Banner_Daily_Denied()
    {
        $this->_runExcludedCombo('banner', 'daily', 'EX10');
    }

    // ===============================================================
    //  SECTION 4J: Excluded Combos — Reversed Dates + Non-Empty Data
    // ===============================================================

    /**
     * Reversed dates should fail before any data query runs, so even with
     * data in the database the result must be false with a date error.
     */
    public function testEX11_ReversedDates_NonEmpty_Campaign_Daily()
    {
        $this->_runCombo('campaign', 'daily', 'reversed', true, 5, 'EX11');
    }

    public function testEX12_ReversedDates_NonEmpty_Banner_Hourly()
    {
        $this->_runCombo('banner', 'hourly', 'reversed', false, 120, 'EX12');
    }

    public function testEX13_ReversedDates_NonEmpty_Publisher_Daily()
    {
        $this->_runCombo('publisher', 'daily', 'reversed', true, 5, 'EX13');
    }

    public function testEX14_ReversedDates_NonEmpty_Zone_Hourly()
    {
        $this->_runCombo('zone', 'hourly', 'reversed', false, 120, 'EX14');
    }

    public function testEX15_ReversedDates_NonEmpty_Advertiser_Daily()
    {
        $this->_runCombo('advertiser', 'daily', 'reversed', true, 5, 'EX15');
    }
}
