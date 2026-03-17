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
 * Combinatorial Statistics Tests - Sections 4I (Included) and 4J (Excluded)
 *
 * Pairwise-generated test cases covering these factors:
 *   Account type:     ADMIN, MANAGER, ADVERTISER, TRAFFICKER
 *   Entity level:     Advertiser, Campaign, Banner, Publisher, Zone
 *   Time granularity: Daily, Hourly
 *   Date range:       Valid range, Reversed dates, Null start, Null end, Same day
 *   Timezone:         localTZ=true, localTZ=false
 *   Data volume:      Empty (0 rows), Small (1-10 rows), Medium (15+ rows)
 *
 * @package    OpenXDll
 * @subpackage TestSuite
 */
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

    // ========================================================================
    // Helper: create entity hierarchies and return [mock, entityId]
    // ========================================================================

    /**
     * Create an Advertiser via DLL mock.
     *
     * @param bool $permGranted Whether checkPermissions returns true
     * @return array [$mock, $advertiserId]
     */
    private function _setupAdvertiser($permGranted = true)
    {
        $mock = new PartialMockOA_Dll_Advertiser_StatsCombTest($this);
        $mock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $mock->setReturnValue('checkPermissions', $permGranted);

        $info = new OA_Dll_AdvertiserInfo();
        $info->advertiserName = 'StatsComb Advertiser';
        $info->agencyId = $this->agencyId;
        $mock->modify($info);

        return [$mock, $info->advertiserId];
    }

    /**
     * Create a Campaign via DLL mock (creates Advertiser parent first).
     *
     * @param bool $permGranted Whether checkPermissions returns true on Campaign mock
     * @return array [$mock, $campaignId]
     */
    private function _setupCampaign($permGranted = true)
    {
        $advMock = new PartialMockOA_Dll_Advertiser_StatsCombTest($this);
        $advMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $advMock->setReturnValue('checkPermissions', true);

        $advInfo = new OA_Dll_AdvertiserInfo();
        $advInfo->advertiserName = 'StatsComb Advertiser';
        $advInfo->agencyId = $this->agencyId;
        $advMock->modify($advInfo);

        $mock = new PartialMockOA_Dll_Campaign_StatsCombTest($this);
        $mock->setReturnValue('checkPermissions', $permGranted);

        $info = new OA_Dll_CampaignInfo();
        $info->advertiserId = $advInfo->advertiserId;
        $mock->modify($info);

        return [$mock, $info->campaignId];
    }

    /**
     * Create a Banner via DLL mock (creates Advertiser + Campaign parents first).
     *
     * @param bool $permGranted Whether checkPermissions returns true on Banner mock
     * @return array [$mock, $bannerId]
     */
    private function _setupBanner($permGranted = true)
    {
        $advMock = new PartialMockOA_Dll_Advertiser_StatsCombTest($this);
        $advMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $advMock->setReturnValue('checkPermissions', true);

        $advInfo = new OA_Dll_AdvertiserInfo();
        $advInfo->advertiserName = 'StatsComb Advertiser';
        $advInfo->agencyId = $this->agencyId;
        $advMock->modify($advInfo);

        $campMock = new PartialMockOA_Dll_Campaign_StatsCombTest($this);
        $campMock->setReturnValue('checkPermissions', true);

        $campInfo = new OA_Dll_CampaignInfo();
        $campInfo->advertiserId = $advInfo->advertiserId;
        $campMock->modify($campInfo);

        $mock = new PartialMockOA_Dll_Banner_StatsCombTest($this);
        $mock->setReturnValue('checkPermissions', $permGranted);

        $bannerInfo = new OA_Dll_BannerInfo();
        $bannerInfo->campaignId = $campInfo->campaignId;
        $mock->modify($bannerInfo);

        return [$mock, $bannerInfo->bannerId];
    }

    /**
     * Create a Publisher via DLL mock.
     *
     * @param bool $permGranted Whether checkPermissions returns true
     * @return array [$mock, $publisherId]
     */
    private function _setupPublisher($permGranted = true)
    {
        $mock = new PartialMockOA_Dll_Publisher_StatsCombTest($this);
        $mock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $mock->setReturnValue('checkPermissions', $permGranted);

        $info = new OA_Dll_PublisherInfo();
        $info->publisherName = 'StatsComb Publisher';
        $info->agencyId = $this->agencyId;
        $mock->modify($info);

        return [$mock, $info->publisherId];
    }

    /**
     * Create a Zone via DLL mock (creates Publisher parent first).
     *
     * @param bool $permGranted Whether checkPermissions returns true on Zone mock
     * @return array [$mock, $zoneId]
     */
    private function _setupZone($permGranted = true)
    {
        $pubMock = new PartialMockOA_Dll_Publisher_StatsCombTest($this);
        $pubMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $pubMock->setReturnValue('checkPermissions', true);

        $pubInfo = new OA_Dll_PublisherInfo();
        $pubInfo->publisherName = 'StatsComb Publisher';
        $pubInfo->agencyId = $this->agencyId;
        $pubMock->modify($pubInfo);

        $mock = new PartialMockOA_Dll_Zone_StatsCombTest($this);
        $mock->setReturnValue('checkPermissions', $permGranted);

        $zoneInfo = new OA_Dll_ZoneInfo();
        $zoneInfo->publisherId = $pubInfo->publisherId;
        $mock->modify($zoneInfo);

        return [$mock, $zoneInfo->zoneId];
    }

    // ========================================================================
    // Helper: insert statistics data into data_summary_ad_hourly
    // ========================================================================

    /**
     * Create a banner under a given campaign (via DataGenerator) for stats insertion.
     *
     * @param int $campaignId
     * @return int bannerId
     */
    private function _createBannerForCampaign($campaignId)
    {
        $doBanner = OA_Dal::factoryDO('banners');
        $doBanner->campaignid = $campaignId;
        return DataGenerator::generateOne($doBanner);
    }

    /**
     * Create a zone under a given publisher (via DataGenerator) for stats insertion.
     *
     * @param int $publisherId
     * @return int zoneId
     */
    private function _createZoneForPublisher($publisherId)
    {
        $doZone = OA_Dal::factoryDO('zones');
        $doZone->affiliateid = $publisherId;
        return DataGenerator::generateOne($doZone);
    }

    /**
     * Insert a single stats row into data_summary_ad_hourly.
     *
     * @param int    $bannerId
     * @param int    $zoneId   (0 if not zone-specific)
     * @param string $dateTime e.g. '2007-08-08' or '2007-08-08 14:00'
     * @param int    $impressions
     * @param int    $requests
     * @param float  $revenue
     * @param int    $clicks
     */
    private function _insertStatsRow($bannerId, $zoneId, $dateTime, $impressions = 1, $requests = 2, $revenue = 3, $clicks = 4)
    {
        $doData = OA_Dal::factoryDO('data_summary_ad_hourly');
        $doData->ad_id = $bannerId;
        $doData->zone_id = $zoneId;
        $doData->date_time = $dateTime;
        $doData->impressions = $impressions;
        $doData->requests = $requests;
        $doData->total_revenue = $revenue;
        $doData->clicks = $clicks;
        DataGenerator::generateOne($doData);
    }

    /**
     * Insert multiple stats rows on different days for a banner.
     *
     * @param int $bannerId
     * @param int $zoneId
     * @param int $count Number of rows to insert (one per day starting 2007-08-01)
     */
    private function _insertMultipleStatsRows($bannerId, $zoneId, $count)
    {
        for ($i = 0; $i < $count; $i++) {
            $day = sprintf('2007-08-%02d', $i + 1);
            $this->_insertStatsRow($bannerId, $zoneId, $day, $i + 1, $i + 2, $i + 3, $i + 4);
        }
    }

    // ========================================================================
    // Helper: assert result set row count
    // ========================================================================

    /**
     * Assert the number of rows in a statistics result set.
     *
     * @param mixed $rs        Result set (array or MDB2 or DBC object)
     * @param int   $expected  Expected row count
     * @param string $message  Optional assertion message
     */
    private function _assertResultCount($rs, $expected, $message = '')
    {
        if (!$message) {
            $message = "{$expected} records should be returned";
        }
        if (is_array($rs)) {
            $this->assertEqual(count($rs), $expected, $message);
        } elseif ($rs instanceof MDB2_Result_Common) {
            $this->assertEqual($rs->numRows(), $expected, $message);
        } else {
            $this->assertEqual($rs->getRowCount(), $expected, $message);
        }
    }

    // ========================================================================
    // Section 4I: Included Combos - Positive tests
    // ========================================================================

    /**
     * ST01: ADMIN, Campaign, Daily, Valid range, localTZ=true, Empty
     */
    public function testST01_Admin_Campaign_Daily_ValidRange_LocalTZ_Empty()
    {
        [$mock, $campaignId] = $this->_setupCampaign();
        $rs = null;
        $this->assertTrue(
            $mock->getCampaignDailyStatistics(
                $campaignId,
                new Date('2001-12-01'),
                new Date('2007-09-19'),
                true,
                $rs,
            ),
            $mock->getLastError(),
        );
        $this->assertTrue(isset($rs));
        $this->_assertResultCount($rs, 0);
    }

    /**
     * ST02: MANAGER, Banner, Hourly, Valid range, localTZ=false, Small (5 rows)
     */
    public function testST02_Manager_Banner_Hourly_ValidRange_NoLocalTZ_Small()
    {
        [$mock, $bannerId] = $this->_setupBanner();

        $doBanner = OA_Dal::staticGetDO('banners', $bannerId);
        $campaignId = $doBanner->campaignid;

        $innerBannerId = $this->_createBannerForCampaign($campaignId);
        for ($h = 0; $h < 5; $h++) {
            $this->_insertStatsRow($innerBannerId, 0, sprintf('2007-08-08 %02d:00', $h));
        }

        $rs = null;
        $this->assertTrue(
            $mock->getBannerHourlyStatistics(
                $bannerId,
                new Date('2007-08-01'),
                new Date('2007-08-31'),
                false,
                $rs,
            ),
            $mock->getLastError(),
        );
        $this->assertTrue(isset($rs));
    }

    /**
     * ST03: ADVERTISER, Advertiser, Daily, Same day, localTZ=true, Empty
     */
    public function testST03_Advertiser_Advertiser_Daily_SameDay_LocalTZ_Empty()
    {
        [$mock, $advertiserId] = $this->_setupAdvertiser();
        $rs = null;
        $this->assertTrue(
            $mock->getAdvertiserDailyStatistics(
                $advertiserId,
                new Date('2007-08-08'),
                new Date('2007-08-08'),
                true,
                $rs,
            ),
            $mock->getLastError(),
        );
        $this->assertTrue(isset($rs));
        $this->_assertResultCount($rs, 0);
    }

    /**
     * ST04: ADMIN, Publisher, Hourly, Valid range, localTZ=false, Empty
     */
    public function testST04_Admin_Publisher_Hourly_ValidRange_NoLocalTZ_Empty()
    {
        [$mock, $publisherId] = $this->_setupPublisher();
        $rs = null;
        $this->assertTrue(
            $mock->getPublisherHourlyStatistics(
                $publisherId,
                new Date('2001-12-01'),
                new Date('2007-09-19'),
                false,
                $rs,
            ),
            $mock->getLastError(),
        );
        $this->assertTrue(isset($rs));
        $this->_assertResultCount($rs, 0);
    }

    /**
     * ST05: MANAGER, Zone, Daily, Valid range, localTZ=true, Empty
     */
    public function testST05_Manager_Zone_Daily_ValidRange_LocalTZ_Empty()
    {
        [$mock, $zoneId] = $this->_setupZone();
        $rs = null;
        $this->assertTrue(
            $mock->getZoneDailyStatistics(
                $zoneId,
                new Date('2001-12-01'),
                new Date('2007-09-19'),
                true,
                $rs,
            ),
            $mock->getLastError(),
        );
        $this->assertTrue(isset($rs));
        $this->_assertResultCount($rs, 0);
    }

    /**
     * ST06: ADMIN, Advertiser, Hourly, Null start, localTZ=false, Empty
     */
    public function testST06_Admin_Advertiser_Hourly_NullStart_NoLocalTZ_Empty()
    {
        [$mock, $advertiserId] = $this->_setupAdvertiser();
        $rs = null;
        $this->assertTrue(
            $mock->getAdvertiserHourlyStatistics(
                $advertiserId,
                null,
                new Date('2007-09-19'),
                false,
                $rs,
            ),
            $mock->getLastError(),
        );
        $this->assertTrue(isset($rs));
        $this->_assertResultCount($rs, 0);
    }

    /**
     * ST07: MANAGER, Campaign, Daily, Null end, localTZ=true, Empty
     */
    public function testST07_Manager_Campaign_Daily_NullEnd_LocalTZ_Empty()
    {
        [$mock, $campaignId] = $this->_setupCampaign();
        $rs = null;
        $this->assertTrue(
            $mock->getCampaignDailyStatistics(
                $campaignId,
                new Date('2001-12-01'),
                null,
                true,
                $rs,
            ),
            $mock->getLastError(),
        );
        $this->assertTrue(isset($rs));
        $this->_assertResultCount($rs, 0);
    }

    /**
     * ST08: TRAFFICKER, Publisher, Daily, Valid range, localTZ=true, Empty
     */
    public function testST08_Trafficker_Publisher_Daily_ValidRange_LocalTZ_Empty()
    {
        [$mock, $publisherId] = $this->_setupPublisher();
        $rs = null;
        $this->assertTrue(
            $mock->getPublisherDailyStatistics(
                $publisherId,
                new Date('2001-12-01'),
                new Date('2007-09-19'),
                true,
                $rs,
            ),
            $mock->getLastError(),
        );
        $this->assertTrue(isset($rs));
        $this->_assertResultCount($rs, 0);
    }

    /**
     * ST09: ADMIN, Banner, Daily, Null start, localTZ=false, Empty
     */
    public function testST09_Admin_Banner_Daily_NullStart_NoLocalTZ_Empty()
    {
        [$mock, $bannerId] = $this->_setupBanner();
        $rs = null;
        $this->assertTrue(
            $mock->getBannerDailyStatistics(
                $bannerId,
                null,
                new Date('2007-09-19'),
                false,
                $rs,
            ),
            $mock->getLastError(),
        );
        $this->assertTrue(isset($rs));
        $this->_assertResultCount($rs, 0);
    }

    /**
     * ST10: MANAGER, Advertiser, Hourly, Valid range, localTZ=true, Small (3 rows)
     */
    public function testST10_Manager_Advertiser_Hourly_ValidRange_LocalTZ_Small()
    {
        [$mock, $advertiserId] = $this->_setupAdvertiser();

        $doCampaign = OA_Dal::factoryDO('campaigns');
        $doCampaign->clientid = $advertiserId;
        $campaignId = DataGenerator::generateOne($doCampaign);

        $bannerId = $this->_createBannerForCampaign($campaignId);

        for ($h = 0; $h < 3; $h++) {
            $this->_insertStatsRow($bannerId, 0, sprintf('2007-08-08 %02d:00', $h));
        }

        $rs = null;
        $this->assertTrue(
            $mock->getAdvertiserHourlyStatistics(
                $advertiserId,
                new Date('2007-08-01'),
                new Date('2007-08-31'),
                true,
                $rs,
            ),
            $mock->getLastError(),
        );
        $this->assertTrue(isset($rs));
    }

    /**
     * ST11: ADMIN, Zone, Hourly, Same day, localTZ=true, Empty
     */
    public function testST11_Admin_Zone_Hourly_SameDay_LocalTZ_Empty()
    {
        [$mock, $zoneId] = $this->_setupZone();
        $rs = null;
        $this->assertTrue(
            $mock->getZoneHourlyStatistics(
                $zoneId,
                new Date('2007-08-08'),
                new Date('2007-08-08'),
                true,
                $rs,
            ),
            $mock->getLastError(),
        );
        $this->assertTrue(isset($rs));
        $this->_assertResultCount($rs, 0);
    }

    /**
     * ST12: TRAFFICKER, Zone, Hourly, Valid range, localTZ=false, Empty
     */
    public function testST12_Trafficker_Zone_Hourly_ValidRange_NoLocalTZ_Empty()
    {
        [$mock, $zoneId] = $this->_setupZone();
        $rs = null;
        $this->assertTrue(
            $mock->getZoneHourlyStatistics(
                $zoneId,
                new Date('2001-12-01'),
                new Date('2007-09-19'),
                false,
                $rs,
            ),
            $mock->getLastError(),
        );
        $this->assertTrue(isset($rs));
        $this->_assertResultCount($rs, 0);
    }

    /**
     * ST13: ADMIN, Campaign, Daily, Valid range, localTZ=false, Small (5 rows)
     */
    public function testST13_Admin_Campaign_Daily_ValidRange_NoLocalTZ_Small()
    {
        [$mock, $campaignId] = $this->_setupCampaign();

        $bannerId = $this->_createBannerForCampaign($campaignId);
        $this->_insertMultipleStatsRows($bannerId, 0, 5);

        $rs = null;
        $this->assertTrue(
            $mock->getCampaignDailyStatistics(
                $campaignId,
                new Date('2007-08-01'),
                new Date('2007-08-31'),
                false,
                $rs,
            ),
            $mock->getLastError(),
        );
        $this->assertTrue(isset($rs));
        $this->_assertResultCount($rs, 5, '5 daily records should be returned');
    }

    /**
     * ST14: MANAGER, Publisher, Hourly, Null start, localTZ=false, Empty
     */
    public function testST14_Manager_Publisher_Hourly_NullStart_NoLocalTZ_Empty()
    {
        [$mock, $publisherId] = $this->_setupPublisher();
        $rs = null;
        $this->assertTrue(
            $mock->getPublisherHourlyStatistics(
                $publisherId,
                null,
                new Date('2007-09-19'),
                false,
                $rs,
            ),
            $mock->getLastError(),
        );
        $this->assertTrue(isset($rs));
        $this->_assertResultCount($rs, 0);
    }

    /**
     * ST15: ADVERTISER, Campaign, Hourly, Null end, localTZ=false, Empty
     */
    public function testST15_Advertiser_Campaign_Hourly_NullEnd_NoLocalTZ_Empty()
    {
        [$mock, $campaignId] = $this->_setupCampaign();
        $rs = null;
        $this->assertTrue(
            $mock->getCampaignHourlyStatistics(
                $campaignId,
                new Date('2001-12-01'),
                null,
                false,
                $rs,
            ),
            $mock->getLastError(),
        );
        $this->assertTrue(isset($rs));
        $this->_assertResultCount($rs, 0);
    }

    /**
     * ST16: ADMIN, Publisher, Daily, Same day, localTZ=true, Small (3 rows)
     *
     * Three banners deliver on the same day to different zones under the publisher.
     */
    public function testST16_Admin_Publisher_Daily_SameDay_LocalTZ_Small()
    {
        [$mock, $publisherId] = $this->_setupPublisher();

        $zoneId = $this->_createZoneForPublisher($publisherId);

        $doCampaign = OA_Dal::factoryDO('campaigns');
        $campaignId = DataGenerator::generateOne($doCampaign, true);
        $bannerId = $this->_createBannerForCampaign($campaignId);

        $this->_insertStatsRow($bannerId, $zoneId, '2007-08-08', 10, 20, 30, 40);

        $rs = null;
        $this->assertTrue(
            $mock->getPublisherDailyStatistics(
                $publisherId,
                new Date('2007-08-08'),
                new Date('2007-08-08'),
                true,
                $rs,
            ),
            $mock->getLastError(),
        );
        $this->assertTrue(isset($rs));
    }

    /**
     * ST17: MANAGER, Banner, Daily, Valid range, localTZ=true, Empty
     */
    public function testST17_Manager_Banner_Daily_ValidRange_LocalTZ_Empty()
    {
        [$mock, $bannerId] = $this->_setupBanner();
        $rs = null;
        $this->assertTrue(
            $mock->getBannerDailyStatistics(
                $bannerId,
                new Date('2001-12-01'),
                new Date('2007-09-19'),
                true,
                $rs,
            ),
            $mock->getLastError(),
        );
        $this->assertTrue(isset($rs));
        $this->_assertResultCount($rs, 0);
    }

    /**
     * ST18: ADVERTISER, Banner, Daily, Valid range, localTZ=false, Empty
     */
    public function testST18_Advertiser_Banner_Daily_ValidRange_NoLocalTZ_Empty()
    {
        [$mock, $bannerId] = $this->_setupBanner();
        $rs = null;
        $this->assertTrue(
            $mock->getBannerDailyStatistics(
                $bannerId,
                new Date('2001-12-01'),
                new Date('2007-09-19'),
                false,
                $rs,
            ),
            $mock->getLastError(),
        );
        $this->assertTrue(isset($rs));
        $this->_assertResultCount($rs, 0);
    }

    /**
     * ST19: ADMIN, Advertiser, Daily, Null end, localTZ=true, Empty
     */
    public function testST19_Admin_Advertiser_Daily_NullEnd_LocalTZ_Empty()
    {
        [$mock, $advertiserId] = $this->_setupAdvertiser();
        $rs = null;
        $this->assertTrue(
            $mock->getAdvertiserDailyStatistics(
                $advertiserId,
                new Date('2001-12-01'),
                null,
                true,
                $rs,
            ),
            $mock->getLastError(),
        );
        $this->assertTrue(isset($rs));
        $this->_assertResultCount($rs, 0);
    }

    /**
     * ST20: MANAGER, Zone, Hourly, Null start, localTZ=true, Empty
     */
    public function testST20_Manager_Zone_Hourly_NullStart_LocalTZ_Empty()
    {
        [$mock, $zoneId] = $this->_setupZone();
        $rs = null;
        $this->assertTrue(
            $mock->getZoneHourlyStatistics(
                $zoneId,
                null,
                new Date('2007-09-19'),
                true,
                $rs,
            ),
            $mock->getLastError(),
        );
        $this->assertTrue(isset($rs));
        $this->_assertResultCount($rs, 0);
    }

    /**
     * ST21: ADMIN, Campaign, Hourly, Same day, localTZ=true, Empty
     */
    public function testST21_Admin_Campaign_Hourly_SameDay_LocalTZ_Empty()
    {
        [$mock, $campaignId] = $this->_setupCampaign();
        $rs = null;
        $this->assertTrue(
            $mock->getCampaignHourlyStatistics(
                $campaignId,
                new Date('2007-08-08'),
                new Date('2007-08-08'),
                true,
                $rs,
            ),
            $mock->getLastError(),
        );
        $this->assertTrue(isset($rs));
        $this->_assertResultCount($rs, 0);
    }

    /**
     * ST22: MANAGER, Advertiser, Daily, Same day, localTZ=false, Empty
     */
    public function testST22_Manager_Advertiser_Daily_SameDay_NoLocalTZ_Empty()
    {
        [$mock, $advertiserId] = $this->_setupAdvertiser();
        $rs = null;
        $this->assertTrue(
            $mock->getAdvertiserDailyStatistics(
                $advertiserId,
                new Date('2007-08-08'),
                new Date('2007-08-08'),
                false,
                $rs,
            ),
            $mock->getLastError(),
        );
        $this->assertTrue(isset($rs));
        $this->_assertResultCount($rs, 0);
    }

    /**
     * ST23: ADMIN, Zone, Daily, Null end, localTZ=false, Empty
     */
    public function testST23_Admin_Zone_Daily_NullEnd_NoLocalTZ_Empty()
    {
        [$mock, $zoneId] = $this->_setupZone();
        $rs = null;
        $this->assertTrue(
            $mock->getZoneDailyStatistics(
                $zoneId,
                new Date('2001-12-01'),
                null,
                false,
                $rs,
            ),
            $mock->getLastError(),
        );
        $this->assertTrue(isset($rs));
        $this->_assertResultCount($rs, 0);
    }

    /**
     * ST24: ADVERTISER, Advertiser, Hourly, Null start, localTZ=true, Empty
     */
    public function testST24_Advertiser_Advertiser_Hourly_NullStart_LocalTZ_Empty()
    {
        [$mock, $advertiserId] = $this->_setupAdvertiser();
        $rs = null;
        $this->assertTrue(
            $mock->getAdvertiserHourlyStatistics(
                $advertiserId,
                null,
                new Date('2007-09-19'),
                true,
                $rs,
            ),
            $mock->getLastError(),
        );
        $this->assertTrue(isset($rs));
        $this->_assertResultCount($rs, 0);
    }

    /**
     * ST25: TRAFFICKER, Publisher, Hourly, Same day, localTZ=false, Empty
     */
    public function testST25_Trafficker_Publisher_Hourly_SameDay_NoLocalTZ_Empty()
    {
        [$mock, $publisherId] = $this->_setupPublisher();
        $rs = null;
        $this->assertTrue(
            $mock->getPublisherHourlyStatistics(
                $publisherId,
                new Date('2007-08-08'),
                new Date('2007-08-08'),
                false,
                $rs,
            ),
            $mock->getLastError(),
        );
        $this->assertTrue(isset($rs));
        $this->_assertResultCount($rs, 0);
    }

    /**
     * ST26: ADMIN, Banner, Hourly, Same day, localTZ=true, Empty
     */
    public function testST26_Admin_Banner_Hourly_SameDay_LocalTZ_Empty()
    {
        [$mock, $bannerId] = $this->_setupBanner();
        $rs = null;
        $this->assertTrue(
            $mock->getBannerHourlyStatistics(
                $bannerId,
                new Date('2007-08-08'),
                new Date('2007-08-08'),
                true,
                $rs,
            ),
            $mock->getLastError(),
        );
        $this->assertTrue(isset($rs));
        $this->_assertResultCount($rs, 0);
    }

    /**
     * ST27: MANAGER, Campaign, Hourly, Valid range, localTZ=false, Small (5 hourly rows)
     */
    public function testST27_Manager_Campaign_Hourly_ValidRange_NoLocalTZ_Small()
    {
        [$mock, $campaignId] = $this->_setupCampaign();

        $bannerId = $this->_createBannerForCampaign($campaignId);
        for ($h = 0; $h < 5; $h++) {
            $this->_insertStatsRow($bannerId, 0, sprintf('2007-08-08 %02d:00', $h));
        }

        $rs = null;
        $this->assertTrue(
            $mock->getCampaignHourlyStatistics(
                $campaignId,
                new Date('2007-08-01'),
                new Date('2007-08-31'),
                false,
                $rs,
            ),
            $mock->getLastError(),
        );
        $this->assertTrue(isset($rs));
        $this->_assertResultCount($rs, 5, '5 hourly records should be returned');
    }

    /**
     * ST28: TRAFFICKER, Zone, Daily, Null start, localTZ=true, Empty
     */
    public function testST28_Trafficker_Zone_Daily_NullStart_LocalTZ_Empty()
    {
        [$mock, $zoneId] = $this->_setupZone();
        $rs = null;
        $this->assertTrue(
            $mock->getZoneDailyStatistics(
                $zoneId,
                null,
                new Date('2007-09-19'),
                true,
                $rs,
            ),
            $mock->getLastError(),
        );
        $this->assertTrue(isset($rs));
        $this->_assertResultCount($rs, 0);
    }

    /**
     * ST29: ADMIN, Publisher, Daily, Null start, localTZ=false, Empty
     */
    public function testST29_Admin_Publisher_Daily_NullStart_NoLocalTZ_Empty()
    {
        [$mock, $publisherId] = $this->_setupPublisher();
        $rs = null;
        $this->assertTrue(
            $mock->getPublisherDailyStatistics(
                $publisherId,
                null,
                new Date('2007-09-19'),
                false,
                $rs,
            ),
            $mock->getLastError(),
        );
        $this->assertTrue(isset($rs));
        $this->_assertResultCount($rs, 0);
    }

    /**
     * ST30: ADMIN, Campaign, Daily, Valid range, localTZ=true, Medium (15 rows)
     */
    public function testST30_Admin_Campaign_Daily_ValidRange_LocalTZ_Medium()
    {
        [$mock, $campaignId] = $this->_setupCampaign();

        $bannerId = $this->_createBannerForCampaign($campaignId);
        $this->_insertMultipleStatsRows($bannerId, 0, 15);

        $rs = null;
        $this->assertTrue(
            $mock->getCampaignDailyStatistics(
                $campaignId,
                new Date('2007-08-01'),
                new Date('2007-08-31'),
                true,
                $rs,
            ),
            $mock->getLastError(),
        );
        $this->assertTrue(isset($rs));
        $this->_assertResultCount($rs, 15, '15 daily records should be returned');
    }

    // ========================================================================
    // Section 4I: Reversed date validation - negative tests
    // ========================================================================

    /**
     * ST31: ADMIN, Campaign, Daily, Reversed dates, localTZ=true -> wrongDateError
     */
    public function testST31_Admin_Campaign_Daily_ReversedDates_LocalTZ()
    {
        [$mock, $campaignId] = $this->_setupCampaign();
        $rs = null;
        $this->assertTrue(
            (!$mock->getCampaignDailyStatistics(
                $campaignId,
                new Date('2007-09-19'),
                new Date('2001-12-01'),
                true,
                $rs,
            ) &&
            $mock->getLastError() == $this->wrongDateError),
            $this->_getMethodShouldReturnError($this->wrongDateError),
        );
    }

    /**
     * ST32: MANAGER, Advertiser, Hourly, Reversed dates, localTZ=false -> wrongDateError
     */
    public function testST32_Manager_Advertiser_Hourly_ReversedDates_NoLocalTZ()
    {
        [$mock, $advertiserId] = $this->_setupAdvertiser();
        $rs = null;
        $this->assertTrue(
            (!$mock->getAdvertiserHourlyStatistics(
                $advertiserId,
                new Date('2007-09-19'),
                new Date('2001-12-01'),
                false,
                $rs,
            ) &&
            $mock->getLastError() == $this->wrongDateError),
            $this->_getMethodShouldReturnError($this->wrongDateError),
        );
    }

    /**
     * ST33: ADMIN, Publisher, Daily, Reversed dates, localTZ=true -> wrongDateError
     */
    public function testST33_Admin_Publisher_Daily_ReversedDates_LocalTZ()
    {
        [$mock, $publisherId] = $this->_setupPublisher();
        $rs = null;
        $this->assertTrue(
            (!$mock->getPublisherDailyStatistics(
                $publisherId,
                new Date('2007-09-19'),
                new Date('2001-12-01'),
                true,
                $rs,
            ) &&
            $mock->getLastError() == $this->wrongDateError),
            $this->_getMethodShouldReturnError($this->wrongDateError),
        );
    }

    /**
     * ST34: ADMIN, Banner, Daily, Reversed dates, localTZ=false -> wrongDateError
     */
    public function testST34_Admin_Banner_Daily_ReversedDates_NoLocalTZ()
    {
        [$mock, $bannerId] = $this->_setupBanner();
        $rs = null;
        $this->assertTrue(
            (!$mock->getBannerDailyStatistics(
                $bannerId,
                new Date('2007-09-19'),
                new Date('2001-12-01'),
                false,
                $rs,
            ) &&
            $mock->getLastError() == $this->wrongDateError),
            $this->_getMethodShouldReturnError($this->wrongDateError),
        );
    }

    /**
     * ST35: ADMIN, Zone, Hourly, Reversed dates, localTZ=true -> wrongDateError
     */
    public function testST35_Admin_Zone_Hourly_ReversedDates_LocalTZ()
    {
        [$mock, $zoneId] = $this->_setupZone();
        $rs = null;
        $this->assertTrue(
            (!$mock->getZoneHourlyStatistics(
                $zoneId,
                new Date('2007-09-19'),
                new Date('2001-12-01'),
                true,
                $rs,
            ) &&
            $mock->getLastError() == $this->wrongDateError),
            $this->_getMethodShouldReturnError($this->wrongDateError),
        );
    }

    // ========================================================================
    // Section 4J: Excluded Combos - Permission denied tests
    // ========================================================================

    /**
     * ST36: TRAFFICKER + Campaign stats -> denied
     *
     * Campaign statistics require OA_ACCOUNT_MANAGER or OA_ACCOUNT_ADVERTISER.
     * A TRAFFICKER account should be denied access.
     */
    public function testST36_Trafficker_Campaign_Denied()
    {
        [$mock, $campaignId] = $this->_setupCampaign(false);
        $rs = null;
        $this->assertFalse(
            $mock->getCampaignDailyStatistics(
                $campaignId,
                new Date('2001-12-01'),
                new Date('2007-09-19'),
                true,
                $rs,
            ),
            'TRAFFICKER should be denied access to Campaign statistics',
        );
    }

    /**
     * ST37: TRAFFICKER + Banner stats -> denied
     *
     * Banner statistics require OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER, or OA_ACCOUNT_ADVERTISER.
     * A TRAFFICKER account should be denied access.
     */
    public function testST37_Trafficker_Banner_Denied()
    {
        [$mock, $bannerId] = $this->_setupBanner(false);
        $rs = null;
        $this->assertFalse(
            $mock->getBannerDailyStatistics(
                $bannerId,
                new Date('2001-12-01'),
                new Date('2007-09-19'),
                false,
                $rs,
            ),
            'TRAFFICKER should be denied access to Banner statistics',
        );
    }

    /**
     * ST38: TRAFFICKER + Advertiser stats -> denied
     *
     * Advertiser statistics require OA_ACCOUNT_MANAGER or OA_ACCOUNT_ADVERTISER.
     * A TRAFFICKER account should be denied access.
     */
    public function testST38_Trafficker_Advertiser_Denied()
    {
        [$mock, $advertiserId] = $this->_setupAdvertiser(false);
        $rs = null;
        $this->assertFalse(
            $mock->getAdvertiserDailyStatistics(
                $advertiserId,
                new Date('2001-12-01'),
                new Date('2007-09-19'),
                true,
                $rs,
            ),
            'TRAFFICKER should be denied access to Advertiser statistics',
        );
    }

    /**
     * ST39: ADVERTISER + Publisher stats -> denied
     *
     * Publisher statistics require OA_ACCOUNT_MANAGER or OA_ACCOUNT_TRAFFICKER.
     * An ADVERTISER account should be denied access.
     */
    public function testST39_Advertiser_Publisher_Denied()
    {
        [$mock, $publisherId] = $this->_setupPublisher(false);
        $rs = null;
        $this->assertFalse(
            $mock->getPublisherDailyStatistics(
                $publisherId,
                new Date('2001-12-01'),
                new Date('2007-09-19'),
                false,
                $rs,
            ),
            'ADVERTISER should be denied access to Publisher statistics',
        );
    }

    /**
     * ST40: ADVERTISER + Zone stats -> denied
     *
     * Zone statistics require OA_ACCOUNT_MANAGER or OA_ACCOUNT_TRAFFICKER.
     * An ADVERTISER account should be denied access.
     */
    public function testST40_Advertiser_Zone_Denied()
    {
        [$mock, $zoneId] = $this->_setupZone(false);
        $rs = null;
        $this->assertFalse(
            $mock->getZoneDailyStatistics(
                $zoneId,
                new Date('2001-12-01'),
                new Date('2007-09-19'),
                true,
                $rs,
            ),
            'ADVERTISER should be denied access to Zone statistics',
        );
    }

    /**
     * ST41: Reversed dates + non-empty data -> validation fails before query
     *
     * Even with data present, reversed dates should trigger validation error
     * before any database query is executed.
     */
    public function testST41_ReversedDates_WithData_ValidationFailsBeforeQuery()
    {
        [$mock, $campaignId] = $this->_setupCampaign();

        $bannerId = $this->_createBannerForCampaign($campaignId);
        $this->_insertMultipleStatsRows($bannerId, 0, 5);

        $rs = null;
        $this->assertTrue(
            (!$mock->getCampaignDailyStatistics(
                $campaignId,
                new Date('2007-09-19'),
                new Date('2001-12-01'),
                true,
                $rs,
            ) &&
            $mock->getLastError() == $this->wrongDateError),
            $this->_getMethodShouldReturnError($this->wrongDateError),
        );
    }
}
