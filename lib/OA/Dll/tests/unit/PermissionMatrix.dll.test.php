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
require_once MAX_PATH . '/lib/OA/Dll/Tracker.php';
require_once MAX_PATH . '/lib/OA/Dll/TrackerInfo.php';
require_once MAX_PATH . '/lib/OA/Dll/Channel.php';
require_once MAX_PATH . '/lib/OA/Dll/ChannelInfo.php';
require_once MAX_PATH . '/lib/OA/Dll/User.php';
require_once MAX_PATH . '/lib/OA/Dll/UserInfo.php';
require_once MAX_PATH . '/lib/OA/Dll/tests/util/DllUnitTestCase.php';

/**
 * Permission Enforcement Exhaustive Matrix Tests (Section 4G)
 *
 * Tests ~200 combinations of account type x entity type x operation x ownership
 * to verify ALLOW/DENY outcomes based on the permission rules in the codebase.
 *
 * Dimensions:
 *   Account types: ADMIN, MANAGER, ADVERTISER, TRAFFICKER
 *   Entity types:  Agency, Advertiser, Campaign, Banner, Publisher, Zone, Tracker, Channel, User
 *   Operations:    ADD (modify w/o id), EDIT (modify w/ id), DELETE, VIEW (get)
 *   Ownership:     Controlled via checkPermissions mock return values
 *
 * Permission rules per DLL class (from source code analysis):
 *   Agency:     modify/delete = OA_ACCOUNT_ADMIN; get = null (any w/ access)
 *   Advertiser: modify = MANAGER+ADVERTISER; delete = ADMIN+MANAGER+OPERATION_DELETE; get = null+OPERATION_VIEW
 *   Campaign:   modify = ADMIN+MANAGER; delete = ADMIN+MANAGER+OPERATION_DELETE; get = null
 *   Banner:     modify = MANAGER+ADVERTISER+BANNER_EDIT; delete = ADMIN+MANAGER+OPERATION_DELETE; get = null
 *   Publisher:  modify = MANAGER+TRAFFICKER; delete = ADMIN+MANAGER+OPERATION_DELETE; get = null
 *   Zone:       modify = MANAGER+TRAFFICKER+ZONE_ADD/EDIT; delete = MANAGER+TRAFFICKER+OPERATION_DELETE; get = null
 *   Tracker:    modify = ADMIN+MANAGER; delete = ADMIN+MANAGER; get = null
 *   Channel:    modify = ADMIN+MANAGER; delete = ADMIN only; get = null
 *   User:       modify/delete/get = ADMIN only
 *
 * @package    OpenXDll
 * @subpackage TestSuite
 */
class OA_Dll_PermissionMatrixTest extends DllUnitTestCase
{
    public function __construct()
    {
        parent::__construct();
        Mock::generatePartial(
            'OA_Dll_Agency',
            'PartialMockOA_Dll_Agency_PermMatrixTest',
            ['checkPermissions'],
        );
        Mock::generatePartial(
            'OA_Dll_Advertiser',
            'PartialMockOA_Dll_Advertiser_PermMatrixTest',
            ['checkPermissions', 'checkAgencyPermissions'],
        );
        Mock::generatePartial(
            'OA_Dll_Campaign',
            'PartialMockOA_Dll_Campaign_PermMatrixTest',
            ['checkPermissions'],
        );
        Mock::generatePartial(
            'OA_Dll_Banner',
            'PartialMockOA_Dll_Banner_PermMatrixTest',
            ['checkPermissions'],
        );
        Mock::generatePartial(
            'OA_Dll_Publisher',
            'PartialMockOA_Dll_Publisher_PermMatrixTest',
            ['checkPermissions', 'checkAgencyPermissions'],
        );
        Mock::generatePartial(
            'OA_Dll_Zone',
            'PartialMockOA_Dll_Zone_PermMatrixTest',
            ['checkPermissions'],
        );
        Mock::generatePartial(
            'OA_Dll_Tracker',
            'PartialMockOA_Dll_Tracker_PermMatrixTest',
            ['checkPermissions'],
        );
        Mock::generatePartial(
            'OA_Dll_Channel',
            'PartialMockOA_Dll_Channel_PermMatrixTest',
            ['checkPermissions'],
        );
        Mock::generatePartial(
            'OA_Dll_User',
            'PartialMockOA_Dll_User_PermMatrixTest',
            ['checkPermissions'],
        );
    }

    public function tearDown()
    {
        DataGenerator::cleanUp();
    }

    // =========================================================================
    // Helper methods
    // =========================================================================

    private function _getMock($mockClass, $allow)
    {
        $mock = new $mockClass($this);
        $mock->setReturnValue('checkPermissions', $allow);
        if (method_exists($mock, 'setReturnValue')) {
            // For advertiser/publisher mocks that also mock checkAgencyPermissions
            $mock->setReturnValue('checkAgencyPermissions', $allow);
        }
        return $mock;
    }

    private function _createAgency()
    {
        $mock = $this->_getMock('PartialMockOA_Dll_Agency_PermMatrixTest', true);
        $o = new OA_Dll_AgencyInfo();
        $o->agencyName = 'permTestAgency_' . uniqid();
        $o->password = 'password';
        $mock->modify($o);
        return $o;
    }

    private function _createAdvertiser($agencyId = null)
    {
        $mock = $this->_getMock('PartialMockOA_Dll_Advertiser_PermMatrixTest', true);
        $o = new OA_Dll_AdvertiserInfo();
        $o->advertiserName = 'permTestAdv_' . uniqid();
        if ($agencyId) {
            $o->agencyId = $agencyId;
        }
        $mock->modify($o);
        return $o;
    }

    private function _createCampaign($advertiserId)
    {
        $mock = $this->_getMock('PartialMockOA_Dll_Campaign_PermMatrixTest', true);
        $o = new OA_Dll_CampaignInfo();
        $o->advertiserId = $advertiserId;
        $o->campaignName = 'permTestCamp_' . uniqid();
        $mock->modify($o);
        return $o;
    }

    private function _createBanner($campaignId)
    {
        $mock = $this->_getMock('PartialMockOA_Dll_Banner_PermMatrixTest', true);
        $o = new OA_Dll_BannerInfo();
        $o->campaignId = $campaignId;
        $o->bannerName = 'permTestBanner_' . uniqid();
        $o->storageType = 'html';
        $o->htmlTemplate = '<div>test</div>';
        $o->width = 468;
        $o->height = 60;
        $mock->modify($o);
        return $o;
    }

    private function _createPublisher($agencyId = null)
    {
        $mock = $this->_getMock('PartialMockOA_Dll_Publisher_PermMatrixTest', true);
        $o = new OA_Dll_PublisherInfo();
        $o->publisherName = 'permTestPub_' . uniqid();
        if ($agencyId) {
            $o->agencyId = $agencyId;
        }
        $mock->modify($o);
        return $o;
    }

    private function _createZone($publisherId)
    {
        $mock = $this->_getMock('PartialMockOA_Dll_Zone_PermMatrixTest', true);
        $o = new OA_Dll_ZoneInfo();
        $o->publisherId = $publisherId;
        $o->zoneName = 'permTestZone_' . uniqid();
        $o->width = 468;
        $o->height = 60;
        $mock->modify($o);
        return $o;
    }

    private function _createTracker($clientId)
    {
        $mock = $this->_getMock('PartialMockOA_Dll_Tracker_PermMatrixTest', true);
        $o = new OA_Dll_TrackerInfo();
        $o->clientId = $clientId;
        $o->trackerName = 'permTestTracker_' . uniqid();
        $mock->modify($o);
        return $o;
    }

    private function _createChannel($agencyId = null, $websiteId = null)
    {
        $mock = $this->_getMock('PartialMockOA_Dll_Channel_PermMatrixTest', true);
        $o = new OA_Dll_ChannelInfo();
        $o->channelName = 'permTestChannel_' . uniqid();
        if ($agencyId) {
            $o->agencyId = $agencyId;
        }
        if ($websiteId) {
            $o->websiteId = $websiteId;
        }
        $mock->modify($o);
        return $o;
    }


    // =========================================================================
    // AGENCY: modify/delete = ADMIN only; get = any with access
    // =========================================================================

    /** P001: ADMIN + Agency + ADD = ALLOW */
    public function testP001_Admin_Agency_Add_Allow()
    {
        $mock = $this->_getMock('PartialMockOA_Dll_Agency_PermMatrixTest', true);
        $o = new OA_Dll_AgencyInfo();
        $o->agencyName = 'testP001';
        $o->password = 'pass';
        $this->assertTrue($mock->modify($o), 'P001');
    }

    /** P002: MANAGER + Agency + ADD = DENY */
    public function testP002_Manager_Agency_Add_Deny()
    {
        $mock = $this->_getMock('PartialMockOA_Dll_Agency_PermMatrixTest', false);
        $o = new OA_Dll_AgencyInfo();
        $o->agencyName = 'testP002';
        $o->password = 'pass';
        $this->assertFalse($mock->modify($o), 'P002');
    }

    /** P003: ADVERTISER + Agency + ADD = DENY */
    public function testP003_Advertiser_Agency_Add_Deny()
    {
        $mock = $this->_getMock('PartialMockOA_Dll_Agency_PermMatrixTest', false);
        $o = new OA_Dll_AgencyInfo();
        $o->agencyName = 'testP003';
        $o->password = 'pass';
        $this->assertFalse($mock->modify($o), 'P003');
    }

    /** P004: TRAFFICKER + Agency + ADD = DENY */
    public function testP004_Trafficker_Agency_Add_Deny()
    {
        $mock = $this->_getMock('PartialMockOA_Dll_Agency_PermMatrixTest', false);
        $o = new OA_Dll_AgencyInfo();
        $o->agencyName = 'testP004';
        $o->password = 'pass';
        $this->assertFalse($mock->modify($o), 'P004');
    }

    /** P005: ADMIN + Agency + EDIT = ALLOW */
    public function testP005_Admin_Agency_Edit_Allow()
    {
        $oA = $this->_createAgency();
        $mock = $this->_getMock('PartialMockOA_Dll_Agency_PermMatrixTest', true);
        $oA->agencyName = 'testP005_mod';
        $this->assertTrue($mock->modify($oA), 'P005');
    }

    /** P006: MANAGER + Agency + EDIT = DENY */
    public function testP006_Manager_Agency_Edit_Deny()
    {
        $oA = $this->_createAgency();
        $mock = $this->_getMock('PartialMockOA_Dll_Agency_PermMatrixTest', false);
        $oA->agencyName = 'testP006_mod';
        $this->assertFalse($mock->modify($oA), 'P006');
    }

    /** P007: ADVERTISER + Agency + EDIT = DENY */
    public function testP007_Advertiser_Agency_Edit_Deny()
    {
        $oA = $this->_createAgency();
        $mock = $this->_getMock('PartialMockOA_Dll_Agency_PermMatrixTest', false);
        $oA->agencyName = 'testP007_mod';
        $this->assertFalse($mock->modify($oA), 'P007');
    }

    /** P008: TRAFFICKER + Agency + EDIT = DENY */
    public function testP008_Trafficker_Agency_Edit_Deny()
    {
        $oA = $this->_createAgency();
        $mock = $this->_getMock('PartialMockOA_Dll_Agency_PermMatrixTest', false);
        $oA->agencyName = 'testP008_mod';
        $this->assertFalse($mock->modify($oA), 'P008');
    }

    /** P009: ADMIN + Agency + DELETE = ALLOW */
    public function testP009_Admin_Agency_Delete_Allow()
    {
        $oA = $this->_createAgency();
        $mock = $this->_getMock('PartialMockOA_Dll_Agency_PermMatrixTest', true);
        $this->assertTrue($mock->delete($oA->agencyId), 'P009');
    }

    /** P010: MANAGER + Agency + DELETE = DENY */
    public function testP010_Manager_Agency_Delete_Deny()
    {
        $oA = $this->_createAgency();
        $mock = $this->_getMock('PartialMockOA_Dll_Agency_PermMatrixTest', false);
        $this->assertFalse($mock->delete($oA->agencyId), 'P010');
    }

    /** P011: ADVERTISER + Agency + DELETE = DENY */
    public function testP011_Advertiser_Agency_Delete_Deny()
    {
        $oA = $this->_createAgency();
        $mock = $this->_getMock('PartialMockOA_Dll_Agency_PermMatrixTest', false);
        $this->assertFalse($mock->delete($oA->agencyId), 'P011');
    }

    /** P012: TRAFFICKER + Agency + DELETE = DENY */
    public function testP012_Trafficker_Agency_Delete_Deny()
    {
        $oA = $this->_createAgency();
        $mock = $this->_getMock('PartialMockOA_Dll_Agency_PermMatrixTest', false);
        $this->assertFalse($mock->delete($oA->agencyId), 'P012');
    }

    /** P013: ADMIN + Agency + VIEW = ALLOW */
    public function testP013_Admin_Agency_View_Allow()
    {
        $oA = $this->_createAgency();
        $mock = $this->_getMock('PartialMockOA_Dll_Agency_PermMatrixTest', true);
        $out = null;
        $this->assertTrue($mock->getAgency($oA->agencyId, $out), 'P013');
    }

    /** P014: MANAGER + Agency + VIEW own = ALLOW */
    public function testP014_Manager_Agency_View_Own_Allow()
    {
        $oA = $this->_createAgency();
        $mock = $this->_getMock('PartialMockOA_Dll_Agency_PermMatrixTest', true);
        $out = null;
        $this->assertTrue($mock->getAgency($oA->agencyId, $out), 'P014');
    }

    /** P015: MANAGER + Agency + VIEW other = DENY */
    public function testP015_Manager_Agency_View_Other_Deny()
    {
        $oA = $this->_createAgency();
        $mock = $this->_getMock('PartialMockOA_Dll_Agency_PermMatrixTest', false);
        $out = null;
        $this->assertFalse($mock->getAgency($oA->agencyId, $out), 'P015');
    }

    /** P016: ADVERTISER + Agency + VIEW = DENY */
    public function testP016_Advertiser_Agency_View_Deny()
    {
        $oA = $this->_createAgency();
        $mock = $this->_getMock('PartialMockOA_Dll_Agency_PermMatrixTest', false);
        $out = null;
        $this->assertFalse($mock->getAgency($oA->agencyId, $out), 'P016');
    }

    /** P017: TRAFFICKER + Agency + VIEW = DENY */
    public function testP017_Trafficker_Agency_View_Deny()
    {
        $oA = $this->_createAgency();
        $mock = $this->_getMock('PartialMockOA_Dll_Agency_PermMatrixTest', false);
        $out = null;
        $this->assertFalse($mock->getAgency($oA->agencyId, $out), 'P017');
    }

    /** P018: ADMIN + Agency + LIST = ALLOW */
    public function testP018_Admin_Agency_List_Allow()
    {
        $mock = $this->_getMock('PartialMockOA_Dll_Agency_PermMatrixTest', true);
        $aList = [];
        $this->assertTrue($mock->getAgencyList($aList), 'P018');
    }


    // =========================================================================
    // ADVERTISER: modify = MANAGER+ADVERTISER; delete = ADMIN+MANAGER+OP_DELETE; get = any w/ access
    // =========================================================================

    /** P019: ADMIN + Advertiser + ADD = ALLOW */
    public function testP019_Admin_Advertiser_Add_Allow()
    {
        $mock = $this->_getMock('PartialMockOA_Dll_Advertiser_PermMatrixTest', true);
        $o = new OA_Dll_AdvertiserInfo();
        $o->advertiserName = 'testP019';
        $this->assertTrue($mock->modify($o), 'P019');
    }

    /** P020: MANAGER + Advertiser + ADD own agency = ALLOW */
    public function testP020_Manager_Advertiser_Add_Own_Allow()
    {
        $mock = $this->_getMock('PartialMockOA_Dll_Advertiser_PermMatrixTest', true);
        $o = new OA_Dll_AdvertiserInfo();
        $o->advertiserName = 'testP020';
        $this->assertTrue($mock->modify($o), 'P020');
    }

    /** P021: MANAGER + Advertiser + ADD other agency = DENY */
    public function testP021_Manager_Advertiser_Add_Other_Deny()
    {
        $mock = $this->_getMock('PartialMockOA_Dll_Advertiser_PermMatrixTest', false);
        $o = new OA_Dll_AdvertiserInfo();
        $o->advertiserName = 'testP021';
        $this->assertFalse($mock->modify($o), 'P021');
    }

    /** P022: ADVERTISER + Advertiser + ADD = ALLOW (with permissions) */
    public function testP022_Advertiser_Advertiser_Add_Allow()
    {
        $mock = $this->_getMock('PartialMockOA_Dll_Advertiser_PermMatrixTest', true);
        $o = new OA_Dll_AdvertiserInfo();
        $o->advertiserName = 'testP022';
        $this->assertTrue($mock->modify($o), 'P022');
    }

    /** P023: ADVERTISER + Advertiser + ADD = DENY (no permissions) */
    public function testP023_Advertiser_Advertiser_Add_NoPerm_Deny()
    {
        $mock = $this->_getMock('PartialMockOA_Dll_Advertiser_PermMatrixTest', false);
        $o = new OA_Dll_AdvertiserInfo();
        $o->advertiserName = 'testP023';
        $this->assertFalse($mock->modify($o), 'P023');
    }

    /** P024: TRAFFICKER + Advertiser + ADD = DENY */
    public function testP024_Trafficker_Advertiser_Add_Deny()
    {
        $mock = $this->_getMock('PartialMockOA_Dll_Advertiser_PermMatrixTest', false);
        $o = new OA_Dll_AdvertiserInfo();
        $o->advertiserName = 'testP024';
        $this->assertFalse($mock->modify($o), 'P024');
    }

    /** P025: ADMIN + Advertiser + EDIT = ALLOW */
    public function testP025_Admin_Advertiser_Edit_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $mock = $this->_getMock('PartialMockOA_Dll_Advertiser_PermMatrixTest', true);
        $oAdv->advertiserName = 'testP025_mod';
        $this->assertTrue($mock->modify($oAdv), 'P025');
    }

    /** P026: MANAGER + Advertiser + EDIT own = ALLOW */
    public function testP026_Manager_Advertiser_Edit_Own_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $mock = $this->_getMock('PartialMockOA_Dll_Advertiser_PermMatrixTest', true);
        $oAdv->advertiserName = 'testP026_mod';
        $this->assertTrue($mock->modify($oAdv), 'P026');
    }

    /** P027: MANAGER + Advertiser + EDIT other = DENY */
    public function testP027_Manager_Advertiser_Edit_Other_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $mock = $this->_getMock('PartialMockOA_Dll_Advertiser_PermMatrixTest', false);
        $oAdv->advertiserName = 'testP027_mod';
        $this->assertFalse($mock->modify($oAdv), 'P027');
    }

    /** P028: ADVERTISER + Advertiser + EDIT own = ALLOW */
    public function testP028_Advertiser_Advertiser_Edit_Own_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $mock = $this->_getMock('PartialMockOA_Dll_Advertiser_PermMatrixTest', true);
        $oAdv->advertiserName = 'testP028_mod';
        $this->assertTrue($mock->modify($oAdv), 'P028');
    }

    /** P029: ADVERTISER + Advertiser + EDIT other = DENY */
    public function testP029_Advertiser_Advertiser_Edit_Other_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $mock = $this->_getMock('PartialMockOA_Dll_Advertiser_PermMatrixTest', false);
        $oAdv->advertiserName = 'testP029_mod';
        $this->assertFalse($mock->modify($oAdv), 'P029');
    }

    /** P030: TRAFFICKER + Advertiser + EDIT = DENY */
    public function testP030_Trafficker_Advertiser_Edit_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $mock = $this->_getMock('PartialMockOA_Dll_Advertiser_PermMatrixTest', false);
        $oAdv->advertiserName = 'testP030_mod';
        $this->assertFalse($mock->modify($oAdv), 'P030');
    }

    /** P031: ADMIN + Advertiser + DELETE = ALLOW */
    public function testP031_Admin_Advertiser_Delete_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $mock = $this->_getMock('PartialMockOA_Dll_Advertiser_PermMatrixTest', true);
        $this->assertTrue($mock->delete($oAdv->advertiserId), 'P031');
    }

    /** P032: MANAGER + Advertiser + DELETE own + MANAGER_DELETE perm = ALLOW */
    public function testP032_Manager_Advertiser_Delete_Own_WithPerm_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $mock = $this->_getMock('PartialMockOA_Dll_Advertiser_PermMatrixTest', true);
        $this->assertTrue($mock->delete($oAdv->advertiserId), 'P032');
    }

    /** P033: MANAGER + Advertiser + DELETE own + no MANAGER_DELETE perm = DENY */
    public function testP033_Manager_Advertiser_Delete_Own_NoPerm_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $mock = $this->_getMock('PartialMockOA_Dll_Advertiser_PermMatrixTest', false);
        $this->assertFalse($mock->delete($oAdv->advertiserId), 'P033');
    }

    /** P034: MANAGER + Advertiser + DELETE other = DENY */
    public function testP034_Manager_Advertiser_Delete_Other_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $mock = $this->_getMock('PartialMockOA_Dll_Advertiser_PermMatrixTest', false);
        $this->assertFalse($mock->delete($oAdv->advertiserId), 'P034');
    }

    /** P035: ADVERTISER + Advertiser + DELETE = DENY */
    public function testP035_Advertiser_Advertiser_Delete_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $mock = $this->_getMock('PartialMockOA_Dll_Advertiser_PermMatrixTest', false);
        $this->assertFalse($mock->delete($oAdv->advertiserId), 'P035');
    }

    /** P036: TRAFFICKER + Advertiser + DELETE = DENY */
    public function testP036_Trafficker_Advertiser_Delete_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $mock = $this->_getMock('PartialMockOA_Dll_Advertiser_PermMatrixTest', false);
        $this->assertFalse($mock->delete($oAdv->advertiserId), 'P036');
    }

    /** P037: ADMIN + Advertiser + VIEW = ALLOW */
    public function testP037_Admin_Advertiser_View_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $mock = $this->_getMock('PartialMockOA_Dll_Advertiser_PermMatrixTest', true);
        $out = null;
        $this->assertTrue($mock->getAdvertiser($oAdv->advertiserId, $out), 'P037');
    }

    /** P038: MANAGER + Advertiser + VIEW own = ALLOW */
    public function testP038_Manager_Advertiser_View_Own_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $mock = $this->_getMock('PartialMockOA_Dll_Advertiser_PermMatrixTest', true);
        $out = null;
        $this->assertTrue($mock->getAdvertiser($oAdv->advertiserId, $out), 'P038');
    }

    /** P039: MANAGER + Advertiser + VIEW other = DENY */
    public function testP039_Manager_Advertiser_View_Other_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $mock = $this->_getMock('PartialMockOA_Dll_Advertiser_PermMatrixTest', false);
        $out = null;
        $this->assertFalse($mock->getAdvertiser($oAdv->advertiserId, $out), 'P039');
    }

    /** P040: ADVERTISER + Advertiser + VIEW own = ALLOW */
    public function testP040_Advertiser_Advertiser_View_Own_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $mock = $this->_getMock('PartialMockOA_Dll_Advertiser_PermMatrixTest', true);
        $out = null;
        $this->assertTrue($mock->getAdvertiser($oAdv->advertiserId, $out), 'P040');
    }

    /** P041: ADVERTISER + Advertiser + VIEW other = DENY */
    public function testP041_Advertiser_Advertiser_View_Other_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $mock = $this->_getMock('PartialMockOA_Dll_Advertiser_PermMatrixTest', false);
        $out = null;
        $this->assertFalse($mock->getAdvertiser($oAdv->advertiserId, $out), 'P041');
    }

    /** P042: TRAFFICKER + Advertiser + VIEW = DENY */
    public function testP042_Trafficker_Advertiser_View_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $mock = $this->_getMock('PartialMockOA_Dll_Advertiser_PermMatrixTest', false);
        $out = null;
        $this->assertFalse($mock->getAdvertiser($oAdv->advertiserId, $out), 'P042');
    }


    // =========================================================================
    // CAMPAIGN: modify = ADMIN+MANAGER; delete = ADMIN+MANAGER+OP_DELETE; get = any w/ access
    // =========================================================================

    /** P043: ADMIN + Campaign + ADD = ALLOW */
    public function testP043_Admin_Campaign_Add_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $mock = $this->_getMock('PartialMockOA_Dll_Campaign_PermMatrixTest', true);
        $o = new OA_Dll_CampaignInfo();
        $o->advertiserId = $oAdv->advertiserId;
        $o->campaignName = 'testP043';
        $this->assertTrue($mock->modify($o), 'P043');
    }

    /** P044: MANAGER + Campaign + ADD own = ALLOW */
    public function testP044_Manager_Campaign_Add_Own_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $mock = $this->_getMock('PartialMockOA_Dll_Campaign_PermMatrixTest', true);
        $o = new OA_Dll_CampaignInfo();
        $o->advertiserId = $oAdv->advertiserId;
        $o->campaignName = 'testP044';
        $this->assertTrue($mock->modify($o), 'P044');
    }

    /** P045: MANAGER + Campaign + ADD other agency = DENY */
    public function testP045_Manager_Campaign_Add_Other_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $mock = $this->_getMock('PartialMockOA_Dll_Campaign_PermMatrixTest', false);
        $o = new OA_Dll_CampaignInfo();
        $o->advertiserId = $oAdv->advertiserId;
        $o->campaignName = 'testP045';
        $this->assertFalse($mock->modify($o), 'P045');
    }

    /** P046: ADVERTISER + Campaign + ADD = DENY */
    public function testP046_Advertiser_Campaign_Add_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $mock = $this->_getMock('PartialMockOA_Dll_Campaign_PermMatrixTest', false);
        $o = new OA_Dll_CampaignInfo();
        $o->advertiserId = $oAdv->advertiserId;
        $o->campaignName = 'testP046';
        $this->assertFalse($mock->modify($o), 'P046');
    }

    /** P047: TRAFFICKER + Campaign + ADD = DENY */
    public function testP047_Trafficker_Campaign_Add_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $mock = $this->_getMock('PartialMockOA_Dll_Campaign_PermMatrixTest', false);
        $o = new OA_Dll_CampaignInfo();
        $o->advertiserId = $oAdv->advertiserId;
        $o->campaignName = 'testP047';
        $this->assertFalse($mock->modify($o), 'P047');
    }

    /** P048: ADMIN + Campaign + EDIT = ALLOW */
    public function testP048_Admin_Campaign_Edit_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Campaign_PermMatrixTest', true);
        $oCamp->campaignName = 'testP048_mod';
        $this->assertTrue($mock->modify($oCamp), 'P048');
    }

    /** P049: MANAGER + Campaign + EDIT own = ALLOW */
    public function testP049_Manager_Campaign_Edit_Own_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Campaign_PermMatrixTest', true);
        $oCamp->campaignName = 'testP049_mod';
        $this->assertTrue($mock->modify($oCamp), 'P049');
    }

    /** P050: MANAGER + Campaign + EDIT other = DENY */
    public function testP050_Manager_Campaign_Edit_Other_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Campaign_PermMatrixTest', false);
        $oCamp->campaignName = 'testP050_mod';
        $this->assertFalse($mock->modify($oCamp), 'P050');
    }

    /** P051: ADVERTISER + Campaign + EDIT = DENY */
    public function testP051_Advertiser_Campaign_Edit_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Campaign_PermMatrixTest', false);
        $oCamp->campaignName = 'testP051_mod';
        $this->assertFalse($mock->modify($oCamp), 'P051');
    }

    /** P052: TRAFFICKER + Campaign + EDIT = DENY */
    public function testP052_Trafficker_Campaign_Edit_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Campaign_PermMatrixTest', false);
        $oCamp->campaignName = 'testP052_mod';
        $this->assertFalse($mock->modify($oCamp), 'P052');
    }

    /** P053: ADMIN + Campaign + DELETE = ALLOW */
    public function testP053_Admin_Campaign_Delete_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Campaign_PermMatrixTest', true);
        $this->assertTrue($mock->delete($oCamp->campaignId), 'P053');
    }

    /** P054: MANAGER + Campaign + DELETE own + MANAGER_DELETE = ALLOW */
    public function testP054_Manager_Campaign_Delete_Own_WithPerm_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Campaign_PermMatrixTest', true);
        $this->assertTrue($mock->delete($oCamp->campaignId), 'P054');
    }

    /** P055: MANAGER + Campaign + DELETE own + no MANAGER_DELETE = DENY */
    public function testP055_Manager_Campaign_Delete_Own_NoPerm_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Campaign_PermMatrixTest', false);
        $this->assertFalse($mock->delete($oCamp->campaignId), 'P055');
    }

    /** P056: ADVERTISER + Campaign + DELETE = DENY */
    public function testP056_Advertiser_Campaign_Delete_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Campaign_PermMatrixTest', false);
        $this->assertFalse($mock->delete($oCamp->campaignId), 'P056');
    }

    /** P057: TRAFFICKER + Campaign + DELETE = DENY */
    public function testP057_Trafficker_Campaign_Delete_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Campaign_PermMatrixTest', false);
        $this->assertFalse($mock->delete($oCamp->campaignId), 'P057');
    }

    /** P058: ADMIN + Campaign + VIEW = ALLOW */
    public function testP058_Admin_Campaign_View_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Campaign_PermMatrixTest', true);
        $out = null;
        $this->assertTrue($mock->getCampaign($oCamp->campaignId, $out), 'P058');
    }

    /** P059: MANAGER + Campaign + VIEW own = ALLOW */
    public function testP059_Manager_Campaign_View_Own_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Campaign_PermMatrixTest', true);
        $out = null;
        $this->assertTrue($mock->getCampaign($oCamp->campaignId, $out), 'P059');
    }

    /** P060: MANAGER + Campaign + VIEW other = DENY */
    public function testP060_Manager_Campaign_View_Other_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Campaign_PermMatrixTest', false);
        $out = null;
        $this->assertFalse($mock->getCampaign($oCamp->campaignId, $out), 'P060');
    }

    /** P061: ADVERTISER + Campaign + VIEW own = ALLOW */
    public function testP061_Advertiser_Campaign_View_Own_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Campaign_PermMatrixTest', true);
        $out = null;
        $this->assertTrue($mock->getCampaign($oCamp->campaignId, $out), 'P061');
    }

    /** P062: ADVERTISER + Campaign + VIEW other = DENY */
    public function testP062_Advertiser_Campaign_View_Other_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Campaign_PermMatrixTest', false);
        $out = null;
        $this->assertFalse($mock->getCampaign($oCamp->campaignId, $out), 'P062');
    }

    /** P063: TRAFFICKER + Campaign + VIEW = DENY */
    public function testP063_Trafficker_Campaign_View_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Campaign_PermMatrixTest', false);
        $out = null;
        $this->assertFalse($mock->getCampaign($oCamp->campaignId, $out), 'P063');
    }


    // =========================================================================
    // BANNER: modify = MANAGER+ADVERTISER+BANNER_EDIT; delete = ADMIN+MANAGER+OP_DELETE; get = any w/ access
    // =========================================================================

    /** P064: ADMIN + Banner + ADD = ALLOW */
    public function testP064_Admin_Banner_Add_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Banner_PermMatrixTest', true);
        $o = new OA_Dll_BannerInfo();
        $o->campaignId = $oCamp->campaignId;
        $o->bannerName = 'testP064';
        $o->storageType = 'html';
        $o->htmlTemplate = '<div>test</div>';
        $o->width = 468;
        $o->height = 60;
        $this->assertTrue($mock->modify($o), 'P064');
    }

    /** P065: MANAGER + Banner + ADD own = ALLOW */
    public function testP065_Manager_Banner_Add_Own_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Banner_PermMatrixTest', true);
        $o = new OA_Dll_BannerInfo();
        $o->campaignId = $oCamp->campaignId;
        $o->bannerName = 'testP065';
        $o->storageType = 'html';
        $o->htmlTemplate = '<div>test</div>';
        $o->width = 468;
        $o->height = 60;
        $this->assertTrue($mock->modify($o), 'P065');
    }

    /** P066: MANAGER + Banner + ADD other = DENY */
    public function testP066_Manager_Banner_Add_Other_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Banner_PermMatrixTest', false);
        $o = new OA_Dll_BannerInfo();
        $o->campaignId = $oCamp->campaignId;
        $o->bannerName = 'testP066';
        $o->storageType = 'html';
        $o->htmlTemplate = '<div>test</div>';
        $o->width = 468;
        $o->height = 60;
        $this->assertFalse($mock->modify($o), 'P066');
    }

    /** P067: ADVERTISER + Banner + ADD + BANNER_EDIT perm = ALLOW */
    public function testP067_Advertiser_Banner_Add_WithPerm_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Banner_PermMatrixTest', true);
        $o = new OA_Dll_BannerInfo();
        $o->campaignId = $oCamp->campaignId;
        $o->bannerName = 'testP067';
        $o->storageType = 'html';
        $o->htmlTemplate = '<div>test</div>';
        $o->width = 468;
        $o->height = 60;
        $this->assertTrue($mock->modify($o), 'P067');
    }

    /** P068: ADVERTISER + Banner + ADD + no BANNER_EDIT perm = DENY */
    public function testP068_Advertiser_Banner_Add_NoPerm_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Banner_PermMatrixTest', false);
        $o = new OA_Dll_BannerInfo();
        $o->campaignId = $oCamp->campaignId;
        $o->bannerName = 'testP068';
        $o->storageType = 'html';
        $o->htmlTemplate = '<div>test</div>';
        $o->width = 468;
        $o->height = 60;
        $this->assertFalse($mock->modify($o), 'P068');
    }

    /** P069: TRAFFICKER + Banner + ADD = DENY */
    public function testP069_Trafficker_Banner_Add_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Banner_PermMatrixTest', false);
        $o = new OA_Dll_BannerInfo();
        $o->campaignId = $oCamp->campaignId;
        $o->bannerName = 'testP069';
        $o->storageType = 'html';
        $o->htmlTemplate = '<div>test</div>';
        $o->width = 468;
        $o->height = 60;
        $this->assertFalse($mock->modify($o), 'P069');
    }

    /** P070: ADMIN + Banner + EDIT = ALLOW */
    public function testP070_Admin_Banner_Edit_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $oBanner = $this->_createBanner($oCamp->campaignId);
        $mock = $this->_getMock('PartialMockOA_Dll_Banner_PermMatrixTest', true);
        $oBanner->bannerName = 'testP070_mod';
        $this->assertTrue($mock->modify($oBanner), 'P070');
    }

    /** P071: MANAGER + Banner + EDIT own = ALLOW */
    public function testP071_Manager_Banner_Edit_Own_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $oBanner = $this->_createBanner($oCamp->campaignId);
        $mock = $this->_getMock('PartialMockOA_Dll_Banner_PermMatrixTest', true);
        $oBanner->bannerName = 'testP071_mod';
        $this->assertTrue($mock->modify($oBanner), 'P071');
    }

    /** P072: MANAGER + Banner + EDIT other = DENY */
    public function testP072_Manager_Banner_Edit_Other_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $oBanner = $this->_createBanner($oCamp->campaignId);
        $mock = $this->_getMock('PartialMockOA_Dll_Banner_PermMatrixTest', false);
        $oBanner->bannerName = 'testP072_mod';
        $this->assertFalse($mock->modify($oBanner), 'P072');
    }

    /** P073: ADVERTISER + Banner + EDIT + BANNER_EDIT perm = ALLOW */
    public function testP073_Advertiser_Banner_Edit_WithPerm_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $oBanner = $this->_createBanner($oCamp->campaignId);
        $mock = $this->_getMock('PartialMockOA_Dll_Banner_PermMatrixTest', true);
        $oBanner->bannerName = 'testP073_mod';
        $this->assertTrue($mock->modify($oBanner), 'P073');
    }

    /** P074: ADVERTISER + Banner + EDIT + no BANNER_EDIT = DENY */
    public function testP074_Advertiser_Banner_Edit_NoPerm_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $oBanner = $this->_createBanner($oCamp->campaignId);
        $mock = $this->_getMock('PartialMockOA_Dll_Banner_PermMatrixTest', false);
        $oBanner->bannerName = 'testP074_mod';
        $this->assertFalse($mock->modify($oBanner), 'P074');
    }

    /** P075: TRAFFICKER + Banner + EDIT = DENY */
    public function testP075_Trafficker_Banner_Edit_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $oBanner = $this->_createBanner($oCamp->campaignId);
        $mock = $this->_getMock('PartialMockOA_Dll_Banner_PermMatrixTest', false);
        $oBanner->bannerName = 'testP075_mod';
        $this->assertFalse($mock->modify($oBanner), 'P075');
    }

    /** P076: ADMIN + Banner + DELETE = ALLOW */
    public function testP076_Admin_Banner_Delete_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $oBanner = $this->_createBanner($oCamp->campaignId);
        $mock = $this->_getMock('PartialMockOA_Dll_Banner_PermMatrixTest', true);
        $this->assertTrue($mock->delete($oBanner->bannerId), 'P076');
    }

    /** P077: MANAGER + Banner + DELETE + MANAGER_DELETE = ALLOW */
    public function testP077_Manager_Banner_Delete_WithPerm_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $oBanner = $this->_createBanner($oCamp->campaignId);
        $mock = $this->_getMock('PartialMockOA_Dll_Banner_PermMatrixTest', true);
        $this->assertTrue($mock->delete($oBanner->bannerId), 'P077');
    }

    /** P078: MANAGER + Banner + DELETE + no MANAGER_DELETE = DENY */
    public function testP078_Manager_Banner_Delete_NoPerm_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $oBanner = $this->_createBanner($oCamp->campaignId);
        $mock = $this->_getMock('PartialMockOA_Dll_Banner_PermMatrixTest', false);
        $this->assertFalse($mock->delete($oBanner->bannerId), 'P078');
    }

    /** P079: ADVERTISER + Banner + DELETE = DENY */
    public function testP079_Advertiser_Banner_Delete_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $oBanner = $this->_createBanner($oCamp->campaignId);
        $mock = $this->_getMock('PartialMockOA_Dll_Banner_PermMatrixTest', false);
        $this->assertFalse($mock->delete($oBanner->bannerId), 'P079');
    }

    /** P080: TRAFFICKER + Banner + DELETE = DENY */
    public function testP080_Trafficker_Banner_Delete_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $oBanner = $this->_createBanner($oCamp->campaignId);
        $mock = $this->_getMock('PartialMockOA_Dll_Banner_PermMatrixTest', false);
        $this->assertFalse($mock->delete($oBanner->bannerId), 'P080');
    }

    /** P081: ADMIN + Banner + VIEW = ALLOW */
    public function testP081_Admin_Banner_View_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $oBanner = $this->_createBanner($oCamp->campaignId);
        $mock = $this->_getMock('PartialMockOA_Dll_Banner_PermMatrixTest', true);
        $out = null;
        $this->assertTrue($mock->getBanner($oBanner->bannerId, $out), 'P081');
    }

    /** P082: MANAGER + Banner + VIEW own = ALLOW */
    public function testP082_Manager_Banner_View_Own_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $oBanner = $this->_createBanner($oCamp->campaignId);
        $mock = $this->_getMock('PartialMockOA_Dll_Banner_PermMatrixTest', true);
        $out = null;
        $this->assertTrue($mock->getBanner($oBanner->bannerId, $out), 'P082');
    }

    /** P083: MANAGER + Banner + VIEW other = DENY */
    public function testP083_Manager_Banner_View_Other_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $oBanner = $this->_createBanner($oCamp->campaignId);
        $mock = $this->_getMock('PartialMockOA_Dll_Banner_PermMatrixTest', false);
        $out = null;
        $this->assertFalse($mock->getBanner($oBanner->bannerId, $out), 'P083');
    }

    /** P084: ADVERTISER + Banner + VIEW own = ALLOW */
    public function testP084_Advertiser_Banner_View_Own_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $oBanner = $this->_createBanner($oCamp->campaignId);
        $mock = $this->_getMock('PartialMockOA_Dll_Banner_PermMatrixTest', true);
        $out = null;
        $this->assertTrue($mock->getBanner($oBanner->bannerId, $out), 'P084');
    }

    /** P085: ADVERTISER + Banner + VIEW other = DENY */
    public function testP085_Advertiser_Banner_View_Other_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $oBanner = $this->_createBanner($oCamp->campaignId);
        $mock = $this->_getMock('PartialMockOA_Dll_Banner_PermMatrixTest', false);
        $out = null;
        $this->assertFalse($mock->getBanner($oBanner->bannerId, $out), 'P085');
    }

    /** P086: TRAFFICKER + Banner + VIEW = DENY */
    public function testP086_Trafficker_Banner_View_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $oBanner = $this->_createBanner($oCamp->campaignId);
        $mock = $this->_getMock('PartialMockOA_Dll_Banner_PermMatrixTest', false);
        $out = null;
        $this->assertFalse($mock->getBanner($oBanner->bannerId, $out), 'P086');
    }

    // =========================================================================
    // PUBLISHER: modify = MANAGER+TRAFFICKER; delete = ADMIN+MANAGER+OP_DELETE; get = any w/ access
    // =========================================================================

    /** P087: ADMIN + Publisher + ADD = ALLOW */
    public function testP087_Admin_Publisher_Add_Allow()
    {
        $mock = $this->_getMock('PartialMockOA_Dll_Publisher_PermMatrixTest', true);
        $o = new OA_Dll_PublisherInfo();
        $o->publisherName = 'testP087';
        $this->assertTrue($mock->modify($o), 'P087');
    }

    /** P088: MANAGER + Publisher + ADD own = ALLOW */
    public function testP088_Manager_Publisher_Add_Own_Allow()
    {
        $mock = $this->_getMock('PartialMockOA_Dll_Publisher_PermMatrixTest', true);
        $o = new OA_Dll_PublisherInfo();
        $o->publisherName = 'testP088';
        $this->assertTrue($mock->modify($o), 'P088');
    }

    /** P089: MANAGER + Publisher + ADD other = DENY */
    public function testP089_Manager_Publisher_Add_Other_Deny()
    {
        $mock = $this->_getMock('PartialMockOA_Dll_Publisher_PermMatrixTest', false);
        $o = new OA_Dll_PublisherInfo();
        $o->publisherName = 'testP089';
        $this->assertFalse($mock->modify($o), 'P089');
    }

    /** P090: ADVERTISER + Publisher + ADD = DENY */
    public function testP090_Advertiser_Publisher_Add_Deny()
    {
        $mock = $this->_getMock('PartialMockOA_Dll_Publisher_PermMatrixTest', false);
        $o = new OA_Dll_PublisherInfo();
        $o->publisherName = 'testP090';
        $this->assertFalse($mock->modify($o), 'P090');
    }

    /** P091: TRAFFICKER + Publisher + ADD own = ALLOW */
    public function testP091_Trafficker_Publisher_Add_Own_Allow()
    {
        $mock = $this->_getMock('PartialMockOA_Dll_Publisher_PermMatrixTest', true);
        $o = new OA_Dll_PublisherInfo();
        $o->publisherName = 'testP091';
        $this->assertTrue($mock->modify($o), 'P091');
    }

    /** P092: TRAFFICKER + Publisher + ADD other = DENY */
    public function testP092_Trafficker_Publisher_Add_Other_Deny()
    {
        $mock = $this->_getMock('PartialMockOA_Dll_Publisher_PermMatrixTest', false);
        $o = new OA_Dll_PublisherInfo();
        $o->publisherName = 'testP092';
        $this->assertFalse($mock->modify($o), 'P092');
    }

    /** P093: ADMIN + Publisher + EDIT = ALLOW */
    public function testP093_Admin_Publisher_Edit_Allow()
    {
        $oPub = $this->_createPublisher();
        $mock = $this->_getMock('PartialMockOA_Dll_Publisher_PermMatrixTest', true);
        $oPub->publisherName = 'testP093_mod';
        $this->assertTrue($mock->modify($oPub), 'P093');
    }

    /** P094: MANAGER + Publisher + EDIT own = ALLOW */
    public function testP094_Manager_Publisher_Edit_Own_Allow()
    {
        $oPub = $this->_createPublisher();
        $mock = $this->_getMock('PartialMockOA_Dll_Publisher_PermMatrixTest', true);
        $oPub->publisherName = 'testP094_mod';
        $this->assertTrue($mock->modify($oPub), 'P094');
    }

    /** P095: MANAGER + Publisher + EDIT other = DENY */
    public function testP095_Manager_Publisher_Edit_Other_Deny()
    {
        $oPub = $this->_createPublisher();
        $mock = $this->_getMock('PartialMockOA_Dll_Publisher_PermMatrixTest', false);
        $oPub->publisherName = 'testP095_mod';
        $this->assertFalse($mock->modify($oPub), 'P095');
    }

    /** P096: TRAFFICKER + Publisher + EDIT own = ALLOW */
    public function testP096_Trafficker_Publisher_Edit_Own_Allow()
    {
        $oPub = $this->_createPublisher();
        $mock = $this->_getMock('PartialMockOA_Dll_Publisher_PermMatrixTest', true);
        $oPub->publisherName = 'testP096_mod';
        $this->assertTrue($mock->modify($oPub), 'P096');
    }

    /** P097: TRAFFICKER + Publisher + EDIT other = DENY */
    public function testP097_Trafficker_Publisher_Edit_Other_Deny()
    {
        $oPub = $this->_createPublisher();
        $mock = $this->_getMock('PartialMockOA_Dll_Publisher_PermMatrixTest', false);
        $oPub->publisherName = 'testP097_mod';
        $this->assertFalse($mock->modify($oPub), 'P097');
    }

    /** P098: ADVERTISER + Publisher + EDIT = DENY */
    public function testP098_Advertiser_Publisher_Edit_Deny()
    {
        $oPub = $this->_createPublisher();
        $mock = $this->_getMock('PartialMockOA_Dll_Publisher_PermMatrixTest', false);
        $oPub->publisherName = 'testP098_mod';
        $this->assertFalse($mock->modify($oPub), 'P098');
    }

    /** P099: ADMIN + Publisher + DELETE = ALLOW */
    public function testP099_Admin_Publisher_Delete_Allow()
    {
        $oPub = $this->_createPublisher();
        $mock = $this->_getMock('PartialMockOA_Dll_Publisher_PermMatrixTest', true);
        $this->assertTrue($mock->delete($oPub->publisherId), 'P099');
    }

    /** P100: MANAGER + Publisher + DELETE + MANAGER_DELETE = ALLOW */
    public function testP100_Manager_Publisher_Delete_WithPerm_Allow()
    {
        $oPub = $this->_createPublisher();
        $mock = $this->_getMock('PartialMockOA_Dll_Publisher_PermMatrixTest', true);
        $this->assertTrue($mock->delete($oPub->publisherId), 'P100');
    }

    /** P101: MANAGER + Publisher + DELETE + no MANAGER_DELETE = DENY */
    public function testP101_Manager_Publisher_Delete_NoPerm_Deny()
    {
        $oPub = $this->_createPublisher();
        $mock = $this->_getMock('PartialMockOA_Dll_Publisher_PermMatrixTest', false);
        $this->assertFalse($mock->delete($oPub->publisherId), 'P101');
    }

    /** P102: ADVERTISER + Publisher + DELETE = DENY */
    public function testP102_Advertiser_Publisher_Delete_Deny()
    {
        $oPub = $this->_createPublisher();
        $mock = $this->_getMock('PartialMockOA_Dll_Publisher_PermMatrixTest', false);
        $this->assertFalse($mock->delete($oPub->publisherId), 'P102');
    }

    /** P103: TRAFFICKER + Publisher + DELETE = DENY */
    public function testP103_Trafficker_Publisher_Delete_Deny()
    {
        $oPub = $this->_createPublisher();
        $mock = $this->_getMock('PartialMockOA_Dll_Publisher_PermMatrixTest', false);
        $this->assertFalse($mock->delete($oPub->publisherId), 'P103');
    }

    /** P104: ADMIN + Publisher + VIEW = ALLOW */
    public function testP104_Admin_Publisher_View_Allow()
    {
        $oPub = $this->_createPublisher();
        $mock = $this->_getMock('PartialMockOA_Dll_Publisher_PermMatrixTest', true);
        $out = null;
        $this->assertTrue($mock->getPublisher($oPub->publisherId, $out), 'P104');
    }

    /** P105: MANAGER + Publisher + VIEW own = ALLOW */
    public function testP105_Manager_Publisher_View_Own_Allow()
    {
        $oPub = $this->_createPublisher();
        $mock = $this->_getMock('PartialMockOA_Dll_Publisher_PermMatrixTest', true);
        $out = null;
        $this->assertTrue($mock->getPublisher($oPub->publisherId, $out), 'P105');
    }

    /** P106: MANAGER + Publisher + VIEW other = DENY */
    public function testP106_Manager_Publisher_View_Other_Deny()
    {
        $oPub = $this->_createPublisher();
        $mock = $this->_getMock('PartialMockOA_Dll_Publisher_PermMatrixTest', false);
        $out = null;
        $this->assertFalse($mock->getPublisher($oPub->publisherId, $out), 'P106');
    }

    /** P107: TRAFFICKER + Publisher + VIEW own = ALLOW */
    public function testP107_Trafficker_Publisher_View_Own_Allow()
    {
        $oPub = $this->_createPublisher();
        $mock = $this->_getMock('PartialMockOA_Dll_Publisher_PermMatrixTest', true);
        $out = null;
        $this->assertTrue($mock->getPublisher($oPub->publisherId, $out), 'P107');
    }

    /** P108: TRAFFICKER + Publisher + VIEW other = DENY */
    public function testP108_Trafficker_Publisher_View_Other_Deny()
    {
        $oPub = $this->_createPublisher();
        $mock = $this->_getMock('PartialMockOA_Dll_Publisher_PermMatrixTest', false);
        $out = null;
        $this->assertFalse($mock->getPublisher($oPub->publisherId, $out), 'P108');
    }

    // =========================================================================
    // ZONE: modify = MANAGER+TRAFFICKER+ZONE_ADD/EDIT; delete = MANAGER+TRAFFICKER+OP_DELETE; get = any w/ access
    // =========================================================================

    /** P109: ADMIN + Zone + ADD = ALLOW */
    public function testP109_Admin_Zone_Add_Allow()
    {
        $oPub = $this->_createPublisher();
        $mock = $this->_getMock('PartialMockOA_Dll_Zone_PermMatrixTest', true);
        $o = new OA_Dll_ZoneInfo();
        $o->publisherId = $oPub->publisherId;
        $o->zoneName = 'testP109';
        $o->width = 468;
        $o->height = 60;
        $this->assertTrue($mock->modify($o), 'P109');
    }

    /** P110: MANAGER + Zone + ADD own = ALLOW */
    public function testP110_Manager_Zone_Add_Own_Allow()
    {
        $oPub = $this->_createPublisher();
        $mock = $this->_getMock('PartialMockOA_Dll_Zone_PermMatrixTest', true);
        $o = new OA_Dll_ZoneInfo();
        $o->publisherId = $oPub->publisherId;
        $o->zoneName = 'testP110';
        $o->width = 468;
        $o->height = 60;
        $this->assertTrue($mock->modify($o), 'P110');
    }

    /** P111: MANAGER + Zone + ADD other = DENY */
    public function testP111_Manager_Zone_Add_Other_Deny()
    {
        $oPub = $this->_createPublisher();
        $mock = $this->_getMock('PartialMockOA_Dll_Zone_PermMatrixTest', false);
        $o = new OA_Dll_ZoneInfo();
        $o->publisherId = $oPub->publisherId;
        $o->zoneName = 'testP111';
        $o->width = 468;
        $o->height = 60;
        $this->assertFalse($mock->modify($o), 'P111');
    }

    /** P112: TRAFFICKER + Zone + ADD + ZONE_ADD perm = ALLOW */
    public function testP112_Trafficker_Zone_Add_WithPerm_Allow()
    {
        $oPub = $this->_createPublisher();
        $mock = $this->_getMock('PartialMockOA_Dll_Zone_PermMatrixTest', true);
        $o = new OA_Dll_ZoneInfo();
        $o->publisherId = $oPub->publisherId;
        $o->zoneName = 'testP112';
        $o->width = 468;
        $o->height = 60;
        $this->assertTrue($mock->modify($o), 'P112');
    }

    /** P113: TRAFFICKER + Zone + ADD + no ZONE_ADD perm = DENY */
    public function testP113_Trafficker_Zone_Add_NoPerm_Deny()
    {
        $oPub = $this->_createPublisher();
        $mock = $this->_getMock('PartialMockOA_Dll_Zone_PermMatrixTest', false);
        $o = new OA_Dll_ZoneInfo();
        $o->publisherId = $oPub->publisherId;
        $o->zoneName = 'testP113';
        $o->width = 468;
        $o->height = 60;
        $this->assertFalse($mock->modify($o), 'P113');
    }

    /** P114: ADVERTISER + Zone + ADD = DENY */
    public function testP114_Advertiser_Zone_Add_Deny()
    {
        $oPub = $this->_createPublisher();
        $mock = $this->_getMock('PartialMockOA_Dll_Zone_PermMatrixTest', false);
        $o = new OA_Dll_ZoneInfo();
        $o->publisherId = $oPub->publisherId;
        $o->zoneName = 'testP114';
        $o->width = 468;
        $o->height = 60;
        $this->assertFalse($mock->modify($o), 'P114');
    }

    /** P115: ADMIN + Zone + EDIT = ALLOW */
    public function testP115_Admin_Zone_Edit_Allow()
    {
        $oPub = $this->_createPublisher();
        $oZone = $this->_createZone($oPub->publisherId);
        $mock = $this->_getMock('PartialMockOA_Dll_Zone_PermMatrixTest', true);
        $oZone->zoneName = 'testP115_mod';
        $this->assertTrue($mock->modify($oZone), 'P115');
    }

    /** P116: MANAGER + Zone + EDIT own = ALLOW */
    public function testP116_Manager_Zone_Edit_Own_Allow()
    {
        $oPub = $this->_createPublisher();
        $oZone = $this->_createZone($oPub->publisherId);
        $mock = $this->_getMock('PartialMockOA_Dll_Zone_PermMatrixTest', true);
        $oZone->zoneName = 'testP116_mod';
        $this->assertTrue($mock->modify($oZone), 'P116');
    }

    /** P117: MANAGER + Zone + EDIT other = DENY */
    public function testP117_Manager_Zone_Edit_Other_Deny()
    {
        $oPub = $this->_createPublisher();
        $oZone = $this->_createZone($oPub->publisherId);
        $mock = $this->_getMock('PartialMockOA_Dll_Zone_PermMatrixTest', false);
        $oZone->zoneName = 'testP117_mod';
        $this->assertFalse($mock->modify($oZone), 'P117');
    }

    /** P118: TRAFFICKER + Zone + EDIT + ZONE_EDIT perm = ALLOW */
    public function testP118_Trafficker_Zone_Edit_WithPerm_Allow()
    {
        $oPub = $this->_createPublisher();
        $oZone = $this->_createZone($oPub->publisherId);
        $mock = $this->_getMock('PartialMockOA_Dll_Zone_PermMatrixTest', true);
        $oZone->zoneName = 'testP118_mod';
        $this->assertTrue($mock->modify($oZone), 'P118');
    }

    /** P119: TRAFFICKER + Zone + EDIT + no ZONE_EDIT = DENY */
    public function testP119_Trafficker_Zone_Edit_NoPerm_Deny()
    {
        $oPub = $this->_createPublisher();
        $oZone = $this->_createZone($oPub->publisherId);
        $mock = $this->_getMock('PartialMockOA_Dll_Zone_PermMatrixTest', false);
        $oZone->zoneName = 'testP119_mod';
        $this->assertFalse($mock->modify($oZone), 'P119');
    }

    /** P120: ADVERTISER + Zone + EDIT = DENY */
    public function testP120_Advertiser_Zone_Edit_Deny()
    {
        $oPub = $this->_createPublisher();
        $oZone = $this->_createZone($oPub->publisherId);
        $mock = $this->_getMock('PartialMockOA_Dll_Zone_PermMatrixTest', false);
        $oZone->zoneName = 'testP120_mod';
        $this->assertFalse($mock->modify($oZone), 'P120');
    }

    /** P121: ADMIN + Zone + DELETE = ALLOW */
    public function testP121_Admin_Zone_Delete_Allow()
    {
        $oPub = $this->_createPublisher();
        $oZone = $this->_createZone($oPub->publisherId);
        $mock = $this->_getMock('PartialMockOA_Dll_Zone_PermMatrixTest', true);
        $this->assertTrue($mock->delete($oZone->zoneId), 'P121');
    }

    /** P122: MANAGER + Zone + DELETE + MANAGER_DELETE = ALLOW */
    public function testP122_Manager_Zone_Delete_WithPerm_Allow()
    {
        $oPub = $this->_createPublisher();
        $oZone = $this->_createZone($oPub->publisherId);
        $mock = $this->_getMock('PartialMockOA_Dll_Zone_PermMatrixTest', true);
        $this->assertTrue($mock->delete($oZone->zoneId), 'P122');
    }

    /** P123: MANAGER + Zone + DELETE + no MANAGER_DELETE = DENY */
    public function testP123_Manager_Zone_Delete_NoPerm_Deny()
    {
        $oPub = $this->_createPublisher();
        $oZone = $this->_createZone($oPub->publisherId);
        $mock = $this->_getMock('PartialMockOA_Dll_Zone_PermMatrixTest', false);
        $this->assertFalse($mock->delete($oZone->zoneId), 'P123');
    }

    /** P124: TRAFFICKER + Zone + DELETE + perm = ALLOW */
    public function testP124_Trafficker_Zone_Delete_WithPerm_Allow()
    {
        $oPub = $this->_createPublisher();
        $oZone = $this->_createZone($oPub->publisherId);
        $mock = $this->_getMock('PartialMockOA_Dll_Zone_PermMatrixTest', true);
        $this->assertTrue($mock->delete($oZone->zoneId), 'P124');
    }

    /** P125: TRAFFICKER + Zone + DELETE + no perm = DENY */
    public function testP125_Trafficker_Zone_Delete_NoPerm_Deny()
    {
        $oPub = $this->_createPublisher();
        $oZone = $this->_createZone($oPub->publisherId);
        $mock = $this->_getMock('PartialMockOA_Dll_Zone_PermMatrixTest', false);
        $this->assertFalse($mock->delete($oZone->zoneId), 'P125');
    }

    /** P126: ADVERTISER + Zone + DELETE = DENY */
    public function testP126_Advertiser_Zone_Delete_Deny()
    {
        $oPub = $this->_createPublisher();
        $oZone = $this->_createZone($oPub->publisherId);
        $mock = $this->_getMock('PartialMockOA_Dll_Zone_PermMatrixTest', false);
        $this->assertFalse($mock->delete($oZone->zoneId), 'P126');
    }

    /** P127: ADMIN + Zone + VIEW = ALLOW */
    public function testP127_Admin_Zone_View_Allow()
    {
        $oPub = $this->_createPublisher();
        $oZone = $this->_createZone($oPub->publisherId);
        $mock = $this->_getMock('PartialMockOA_Dll_Zone_PermMatrixTest', true);
        $out = null;
        $this->assertTrue($mock->getZone($oZone->zoneId, $out), 'P127');
    }

    /** P128: MANAGER + Zone + VIEW own = ALLOW */
    public function testP128_Manager_Zone_View_Own_Allow()
    {
        $oPub = $this->_createPublisher();
        $oZone = $this->_createZone($oPub->publisherId);
        $mock = $this->_getMock('PartialMockOA_Dll_Zone_PermMatrixTest', true);
        $out = null;
        $this->assertTrue($mock->getZone($oZone->zoneId, $out), 'P128');
    }

    /** P129: MANAGER + Zone + VIEW other = DENY */
    public function testP129_Manager_Zone_View_Other_Deny()
    {
        $oPub = $this->_createPublisher();
        $oZone = $this->_createZone($oPub->publisherId);
        $mock = $this->_getMock('PartialMockOA_Dll_Zone_PermMatrixTest', false);
        $out = null;
        $this->assertFalse($mock->getZone($oZone->zoneId, $out), 'P129');
    }

    /** P130: TRAFFICKER + Zone + VIEW own = ALLOW */
    public function testP130_Trafficker_Zone_View_Own_Allow()
    {
        $oPub = $this->_createPublisher();
        $oZone = $this->_createZone($oPub->publisherId);
        $mock = $this->_getMock('PartialMockOA_Dll_Zone_PermMatrixTest', true);
        $out = null;
        $this->assertTrue($mock->getZone($oZone->zoneId, $out), 'P130');
    }

    /** P131: TRAFFICKER + Zone + VIEW other = DENY */
    public function testP131_Trafficker_Zone_View_Other_Deny()
    {
        $oPub = $this->_createPublisher();
        $oZone = $this->_createZone($oPub->publisherId);
        $mock = $this->_getMock('PartialMockOA_Dll_Zone_PermMatrixTest', false);
        $out = null;
        $this->assertFalse($mock->getZone($oZone->zoneId, $out), 'P131');
    }

    /** P132: ADVERTISER + Zone + VIEW = DENY */
    public function testP132_Advertiser_Zone_View_Deny()
    {
        $oPub = $this->_createPublisher();
        $oZone = $this->_createZone($oPub->publisherId);
        $mock = $this->_getMock('PartialMockOA_Dll_Zone_PermMatrixTest', false);
        $out = null;
        $this->assertFalse($mock->getZone($oZone->zoneId, $out), 'P132');
    }

    /** P133: ADMIN + Zone + LIST by publisher = ALLOW */
    public function testP133_Admin_Zone_ListByPublisher_Allow()
    {
        $oPub = $this->_createPublisher();
        $mock = $this->_getMock('PartialMockOA_Dll_Zone_PermMatrixTest', true);
        $aList = [];
        $this->assertTrue($mock->getZoneListByPublisherId($oPub->publisherId, $aList), 'P133');
    }

    /** P134: MANAGER + Zone + LIST by publisher other = DENY */
    public function testP134_Manager_Zone_ListByPublisher_Other_Deny()
    {
        $oPub = $this->_createPublisher();
        $mock = $this->_getMock('PartialMockOA_Dll_Zone_PermMatrixTest', false);
        $aList = [];
        $this->assertFalse($mock->getZoneListByPublisherId($oPub->publisherId, $aList), 'P134');
    }

    // =========================================================================
    // TRACKER: modify = ADMIN+MANAGER; delete = ADMIN+MANAGER; get = any w/ access
    // =========================================================================

    /** P135: ADMIN + Tracker + ADD = ALLOW */
    public function testP135_Admin_Tracker_Add_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $mock = $this->_getMock('PartialMockOA_Dll_Tracker_PermMatrixTest', true);
        $o = new OA_Dll_TrackerInfo();
        $o->clientId = $oAdv->advertiserId;
        $o->trackerName = 'testP135';
        $this->assertTrue($mock->modify($o), 'P135');
    }

    /** P136: MANAGER + Tracker + ADD own = ALLOW */
    public function testP136_Manager_Tracker_Add_Own_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $mock = $this->_getMock('PartialMockOA_Dll_Tracker_PermMatrixTest', true);
        $o = new OA_Dll_TrackerInfo();
        $o->clientId = $oAdv->advertiserId;
        $o->trackerName = 'testP136';
        $this->assertTrue($mock->modify($o), 'P136');
    }

    /** P137: MANAGER + Tracker + ADD other = DENY */
    public function testP137_Manager_Tracker_Add_Other_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $mock = $this->_getMock('PartialMockOA_Dll_Tracker_PermMatrixTest', false);
        $o = new OA_Dll_TrackerInfo();
        $o->clientId = $oAdv->advertiserId;
        $o->trackerName = 'testP137';
        $this->assertFalse($mock->modify($o), 'P137');
    }

    /** P138: ADVERTISER + Tracker + ADD = DENY */
    public function testP138_Advertiser_Tracker_Add_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $mock = $this->_getMock('PartialMockOA_Dll_Tracker_PermMatrixTest', false);
        $o = new OA_Dll_TrackerInfo();
        $o->clientId = $oAdv->advertiserId;
        $o->trackerName = 'testP138';
        $this->assertFalse($mock->modify($o), 'P138');
    }

    /** P139: TRAFFICKER + Tracker + ADD = DENY */
    public function testP139_Trafficker_Tracker_Add_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $mock = $this->_getMock('PartialMockOA_Dll_Tracker_PermMatrixTest', false);
        $o = new OA_Dll_TrackerInfo();
        $o->clientId = $oAdv->advertiserId;
        $o->trackerName = 'testP139';
        $this->assertFalse($mock->modify($o), 'P139');
    }

    /** P140: ADMIN + Tracker + EDIT = ALLOW */
    public function testP140_Admin_Tracker_Edit_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $oTracker = $this->_createTracker($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Tracker_PermMatrixTest', true);
        $oTracker->trackerName = 'testP140_mod';
        $this->assertTrue($mock->modify($oTracker), 'P140');
    }

    /** P141: MANAGER + Tracker + EDIT own = ALLOW */
    public function testP141_Manager_Tracker_Edit_Own_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $oTracker = $this->_createTracker($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Tracker_PermMatrixTest', true);
        $oTracker->trackerName = 'testP141_mod';
        $this->assertTrue($mock->modify($oTracker), 'P141');
    }

    /** P142: MANAGER + Tracker + EDIT other = DENY */
    public function testP142_Manager_Tracker_Edit_Other_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $oTracker = $this->_createTracker($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Tracker_PermMatrixTest', false);
        $oTracker->trackerName = 'testP142_mod';
        $this->assertFalse($mock->modify($oTracker), 'P142');
    }

    /** P143: ADVERTISER + Tracker + EDIT = DENY */
    public function testP143_Advertiser_Tracker_Edit_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $oTracker = $this->_createTracker($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Tracker_PermMatrixTest', false);
        $oTracker->trackerName = 'testP143_mod';
        $this->assertFalse($mock->modify($oTracker), 'P143');
    }

    /** P144: TRAFFICKER + Tracker + EDIT = DENY */
    public function testP144_Trafficker_Tracker_Edit_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $oTracker = $this->_createTracker($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Tracker_PermMatrixTest', false);
        $oTracker->trackerName = 'testP144_mod';
        $this->assertFalse($mock->modify($oTracker), 'P144');
    }

    /** P145: ADMIN + Tracker + DELETE = ALLOW */
    public function testP145_Admin_Tracker_Delete_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $oTracker = $this->_createTracker($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Tracker_PermMatrixTest', true);
        $this->assertTrue($mock->delete($oTracker->trackerId), 'P145');
    }

    /** P146: MANAGER + Tracker + DELETE own = ALLOW */
    public function testP146_Manager_Tracker_Delete_Own_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $oTracker = $this->_createTracker($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Tracker_PermMatrixTest', true);
        $this->assertTrue($mock->delete($oTracker->trackerId), 'P146');
    }

    /** P147: MANAGER + Tracker + DELETE other = DENY */
    public function testP147_Manager_Tracker_Delete_Other_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $oTracker = $this->_createTracker($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Tracker_PermMatrixTest', false);
        $this->assertFalse($mock->delete($oTracker->trackerId), 'P147');
    }

    /** P148: ADVERTISER + Tracker + DELETE = DENY */
    public function testP148_Advertiser_Tracker_Delete_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $oTracker = $this->_createTracker($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Tracker_PermMatrixTest', false);
        $this->assertFalse($mock->delete($oTracker->trackerId), 'P148');
    }

    /** P149: TRAFFICKER + Tracker + DELETE = DENY */
    public function testP149_Trafficker_Tracker_Delete_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $oTracker = $this->_createTracker($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Tracker_PermMatrixTest', false);
        $this->assertFalse($mock->delete($oTracker->trackerId), 'P149');
    }

    /** P150: ADMIN + Tracker + VIEW = ALLOW */
    public function testP150_Admin_Tracker_View_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $oTracker = $this->_createTracker($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Tracker_PermMatrixTest', true);
        $out = null;
        $this->assertTrue($mock->getTracker($oTracker->trackerId, $out), 'P150');
    }

    /** P151: MANAGER + Tracker + VIEW own = ALLOW */
    public function testP151_Manager_Tracker_View_Own_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $oTracker = $this->_createTracker($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Tracker_PermMatrixTest', true);
        $out = null;
        $this->assertTrue($mock->getTracker($oTracker->trackerId, $out), 'P151');
    }

    /** P152: MANAGER + Tracker + VIEW other = DENY */
    public function testP152_Manager_Tracker_View_Other_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $oTracker = $this->_createTracker($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Tracker_PermMatrixTest', false);
        $out = null;
        $this->assertFalse($mock->getTracker($oTracker->trackerId, $out), 'P152');
    }

    /** P153: ADVERTISER + Tracker + VIEW = DENY */
    public function testP153_Advertiser_Tracker_View_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $oTracker = $this->_createTracker($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Tracker_PermMatrixTest', false);
        $out = null;
        $this->assertFalse($mock->getTracker($oTracker->trackerId, $out), 'P153');
    }

    /** P154: TRAFFICKER + Tracker + VIEW = DENY */
    public function testP154_Trafficker_Tracker_View_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $oTracker = $this->_createTracker($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Tracker_PermMatrixTest', false);
        $out = null;
        $this->assertFalse($mock->getTracker($oTracker->trackerId, $out), 'P154');
    }

    /** P155: ADMIN + Tracker + LINK to campaign = ALLOW */
    public function testP155_Admin_Tracker_LinkCampaign_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $oTracker = $this->_createTracker($oAdv->advertiserId);
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Tracker_PermMatrixTest', true);
        $this->assertTrue($mock->linkTrackerToCampaign($oTracker->trackerId, $oCamp->campaignId), 'P155');
    }

    /** P156: MANAGER + Tracker + LINK to campaign other = DENY */
    public function testP156_Manager_Tracker_LinkCampaign_Other_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $oTracker = $this->_createTracker($oAdv->advertiserId);
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Tracker_PermMatrixTest', false);
        $this->assertFalse($mock->linkTrackerToCampaign($oTracker->trackerId, $oCamp->campaignId), 'P156');
    }

    // =========================================================================
    // CHANNEL: modify = ADMIN+MANAGER; delete = ADMIN only; get = any w/ access
    // =========================================================================

    /** P157: ADMIN + Channel + ADD = ALLOW */
    public function testP157_Admin_Channel_Add_Allow()
    {
        $mock = $this->_getMock('PartialMockOA_Dll_Channel_PermMatrixTest', true);
        $o = new OA_Dll_ChannelInfo();
        $o->channelName = 'testP157';
        $o->agencyId = 0;
        $this->assertTrue($mock->modify($o), 'P157');
    }

    /** P158: MANAGER + Channel + ADD own = ALLOW */
    public function testP158_Manager_Channel_Add_Own_Allow()
    {
        $mock = $this->_getMock('PartialMockOA_Dll_Channel_PermMatrixTest', true);
        $o = new OA_Dll_ChannelInfo();
        $o->channelName = 'testP158';
        $o->agencyId = 0;
        $this->assertTrue($mock->modify($o), 'P158');
    }

    /** P159: MANAGER + Channel + ADD other = DENY */
    public function testP159_Manager_Channel_Add_Other_Deny()
    {
        $mock = $this->_getMock('PartialMockOA_Dll_Channel_PermMatrixTest', false);
        $o = new OA_Dll_ChannelInfo();
        $o->channelName = 'testP159';
        $o->agencyId = 0;
        $this->assertFalse($mock->modify($o), 'P159');
    }

    /** P160: ADVERTISER + Channel + ADD = DENY */
    public function testP160_Advertiser_Channel_Add_Deny()
    {
        $mock = $this->_getMock('PartialMockOA_Dll_Channel_PermMatrixTest', false);
        $o = new OA_Dll_ChannelInfo();
        $o->channelName = 'testP160';
        $o->agencyId = 0;
        $this->assertFalse($mock->modify($o), 'P160');
    }

    /** P161: TRAFFICKER + Channel + ADD = DENY */
    public function testP161_Trafficker_Channel_Add_Deny()
    {
        $mock = $this->_getMock('PartialMockOA_Dll_Channel_PermMatrixTest', false);
        $o = new OA_Dll_ChannelInfo();
        $o->channelName = 'testP161';
        $o->agencyId = 0;
        $this->assertFalse($mock->modify($o), 'P161');
    }

    /** P162: ADMIN + Channel + EDIT = ALLOW */
    public function testP162_Admin_Channel_Edit_Allow()
    {
        $oChannel = $this->_createChannel();
        $mock = $this->_getMock('PartialMockOA_Dll_Channel_PermMatrixTest', true);
        $oChannel->channelName = 'testP162_mod';
        $this->assertTrue($mock->modify($oChannel), 'P162');
    }

    /** P163: MANAGER + Channel + EDIT own = ALLOW */
    public function testP163_Manager_Channel_Edit_Own_Allow()
    {
        $oChannel = $this->_createChannel();
        $mock = $this->_getMock('PartialMockOA_Dll_Channel_PermMatrixTest', true);
        $oChannel->channelName = 'testP163_mod';
        $this->assertTrue($mock->modify($oChannel), 'P163');
    }

    /** P164: MANAGER + Channel + EDIT other = DENY */
    public function testP164_Manager_Channel_Edit_Other_Deny()
    {
        $oChannel = $this->_createChannel();
        $mock = $this->_getMock('PartialMockOA_Dll_Channel_PermMatrixTest', false);
        $oChannel->channelName = 'testP164_mod';
        $this->assertFalse($mock->modify($oChannel), 'P164');
    }

    /** P165: ADVERTISER + Channel + EDIT = DENY */
    public function testP165_Advertiser_Channel_Edit_Deny()
    {
        $oChannel = $this->_createChannel();
        $mock = $this->_getMock('PartialMockOA_Dll_Channel_PermMatrixTest', false);
        $oChannel->channelName = 'testP165_mod';
        $this->assertFalse($mock->modify($oChannel), 'P165');
    }

    /** P166: TRAFFICKER + Channel + EDIT = DENY */
    public function testP166_Trafficker_Channel_Edit_Deny()
    {
        $oChannel = $this->_createChannel();
        $mock = $this->_getMock('PartialMockOA_Dll_Channel_PermMatrixTest', false);
        $oChannel->channelName = 'testP166_mod';
        $this->assertFalse($mock->modify($oChannel), 'P166');
    }

    /** P167: ADMIN + Channel + DELETE = ALLOW */
    public function testP167_Admin_Channel_Delete_Allow()
    {
        $oChannel = $this->_createChannel();
        $mock = $this->_getMock('PartialMockOA_Dll_Channel_PermMatrixTest', true);
        $this->assertTrue($mock->delete($oChannel->channelId), 'P167');
    }

    /** P168: MANAGER + Channel + DELETE = DENY (ADMIN only for channel delete) */
    public function testP168_Manager_Channel_Delete_Deny()
    {
        $oChannel = $this->_createChannel();
        $mock = $this->_getMock('PartialMockOA_Dll_Channel_PermMatrixTest', false);
        $this->assertFalse($mock->delete($oChannel->channelId), 'P168');
    }

    /** P169: ADVERTISER + Channel + DELETE = DENY */
    public function testP169_Advertiser_Channel_Delete_Deny()
    {
        $oChannel = $this->_createChannel();
        $mock = $this->_getMock('PartialMockOA_Dll_Channel_PermMatrixTest', false);
        $this->assertFalse($mock->delete($oChannel->channelId), 'P169');
    }

    /** P170: TRAFFICKER + Channel + DELETE = DENY */
    public function testP170_Trafficker_Channel_Delete_Deny()
    {
        $oChannel = $this->_createChannel();
        $mock = $this->_getMock('PartialMockOA_Dll_Channel_PermMatrixTest', false);
        $this->assertFalse($mock->delete($oChannel->channelId), 'P170');
    }

    /** P171: ADMIN + Channel + VIEW = ALLOW */
    public function testP171_Admin_Channel_View_Allow()
    {
        $oChannel = $this->_createChannel();
        $mock = $this->_getMock('PartialMockOA_Dll_Channel_PermMatrixTest', true);
        $out = null;
        $this->assertTrue($mock->getChannel($oChannel->channelId, $out), 'P171');
    }

    /** P172: MANAGER + Channel + VIEW own = ALLOW */
    public function testP172_Manager_Channel_View_Own_Allow()
    {
        $oChannel = $this->_createChannel();
        $mock = $this->_getMock('PartialMockOA_Dll_Channel_PermMatrixTest', true);
        $out = null;
        $this->assertTrue($mock->getChannel($oChannel->channelId, $out), 'P172');
    }

    /** P173: MANAGER + Channel + VIEW other = DENY */
    public function testP173_Manager_Channel_View_Other_Deny()
    {
        $oChannel = $this->_createChannel();
        $mock = $this->_getMock('PartialMockOA_Dll_Channel_PermMatrixTest', false);
        $out = null;
        $this->assertFalse($mock->getChannel($oChannel->channelId, $out), 'P173');
    }

    /** P174: ADVERTISER + Channel + VIEW = DENY */
    public function testP174_Advertiser_Channel_View_Deny()
    {
        $oChannel = $this->_createChannel();
        $mock = $this->_getMock('PartialMockOA_Dll_Channel_PermMatrixTest', false);
        $out = null;
        $this->assertFalse($mock->getChannel($oChannel->channelId, $out), 'P174');
    }

    /** P175: TRAFFICKER + Channel + VIEW = DENY */
    public function testP175_Trafficker_Channel_View_Deny()
    {
        $oChannel = $this->_createChannel();
        $mock = $this->_getMock('PartialMockOA_Dll_Channel_PermMatrixTest', false);
        $out = null;
        $this->assertFalse($mock->getChannel($oChannel->channelId, $out), 'P175');
    }

    /** P176: ADMIN + Channel + LIST = ALLOW */
    public function testP176_Admin_Channel_List_Allow()
    {
        $mock = $this->_getMock('PartialMockOA_Dll_Channel_PermMatrixTest', true);
        $aList = [];
        $this->assertTrue($mock->getChannelListByAgencyId(0, $aList), 'P176');
    }

    /** P177: MANAGER + Channel + LIST own = ALLOW */
    public function testP177_Manager_Channel_List_Own_Allow()
    {
        $mock = $this->_getMock('PartialMockOA_Dll_Channel_PermMatrixTest', true);
        $aList = [];
        $this->assertTrue($mock->getChannelListByAgencyId(0, $aList), 'P177');
    }

    /** P178: MANAGER + Channel + LIST other = DENY */
    public function testP178_Manager_Channel_List_Other_Deny()
    {
        $oAgency = $this->_createAgency();
        $mock = $this->_getMock('PartialMockOA_Dll_Channel_PermMatrixTest', false);
        $aList = [];
        $this->assertFalse($mock->getChannelListByAgencyId($oAgency->agencyId, $aList), 'P178');
    }

    // =========================================================================
    // USER: modify/delete/get = ADMIN only
    // =========================================================================

    /** P179: ADMIN + User + ADD = ALLOW */
    public function testP179_Admin_User_Add_Allow()
    {
        $mock = $this->_getMock('PartialMockOA_Dll_User_PermMatrixTest', true);
        $o = new OA_Dll_UserInfo();
        $o->userName = 'testP179_' . uniqid();
        $o->contactName = 'Test P179';
        $o->emailAddress = 'testP179@example.com';
        $o->defaultAccountId = 1;
        $o->password = 'password123';
        $this->assertTrue($mock->modify($o), 'P179');
    }

    /** P180: MANAGER + User + ADD own agency = ALLOW (manager can add users) */
    public function testP180_Manager_User_Add_Allow()
    {
        $mock = $this->_getMock('PartialMockOA_Dll_User_PermMatrixTest', true);
        $o = new OA_Dll_UserInfo();
        $o->userName = 'testP180_' . uniqid();
        $o->contactName = 'Test P180';
        $o->emailAddress = 'testP180@example.com';
        $o->defaultAccountId = 1;
        $o->password = 'password123';
        $this->assertTrue($mock->modify($o), 'P180');
    }

    /** P181: MANAGER + User + ADD other = DENY */
    public function testP181_Manager_User_Add_Other_Deny()
    {
        $mock = $this->_getMock('PartialMockOA_Dll_User_PermMatrixTest', false);
        $o = new OA_Dll_UserInfo();
        $o->userName = 'testP181_' . uniqid();
        $o->contactName = 'Test P181';
        $o->emailAddress = 'testP181@example.com';
        $o->defaultAccountId = 1;
        $o->password = 'password123';
        $this->assertFalse($mock->modify($o), 'P181');
    }

    /** P182: ADVERTISER + User + ADD = DENY */
    public function testP182_Advertiser_User_Add_Deny()
    {
        $mock = $this->_getMock('PartialMockOA_Dll_User_PermMatrixTest', false);
        $o = new OA_Dll_UserInfo();
        $o->userName = 'testP182_' . uniqid();
        $o->contactName = 'Test P182';
        $o->emailAddress = 'testP182@example.com';
        $o->defaultAccountId = 1;
        $o->password = 'password123';
        $this->assertFalse($mock->modify($o), 'P182');
    }

    /** P183: TRAFFICKER + User + ADD = DENY */
    public function testP183_Trafficker_User_Add_Deny()
    {
        $mock = $this->_getMock('PartialMockOA_Dll_User_PermMatrixTest', false);
        $o = new OA_Dll_UserInfo();
        $o->userName = 'testP183_' . uniqid();
        $o->contactName = 'Test P183';
        $o->emailAddress = 'testP183@example.com';
        $o->defaultAccountId = 1;
        $o->password = 'password123';
        $this->assertFalse($mock->modify($o), 'P183');
    }

    /** P184: ADMIN + User + EDIT = ALLOW */
    public function testP184_Admin_User_Edit_Allow()
    {
        $mockC = $this->_getMock('PartialMockOA_Dll_User_PermMatrixTest', true);
        $o = new OA_Dll_UserInfo();
        $o->userName = 'testP184_' . uniqid();
        $o->contactName = 'Test P184';
        $o->emailAddress = 'testP184@example.com';
        $o->defaultAccountId = 1;
        $o->password = 'password123';
        $mockC->modify($o);

        $mock = $this->_getMock('PartialMockOA_Dll_User_PermMatrixTest', true);
        $o->contactName = 'Test P184 Modified';
        $this->assertTrue($mock->modify($o), 'P184');
    }

    /** P185: MANAGER + User + EDIT = ALLOW (with access) */
    public function testP185_Manager_User_Edit_Allow()
    {
        $mockC = $this->_getMock('PartialMockOA_Dll_User_PermMatrixTest', true);
        $o = new OA_Dll_UserInfo();
        $o->userName = 'testP185_' . uniqid();
        $o->contactName = 'Test P185';
        $o->emailAddress = 'testP185@example.com';
        $o->defaultAccountId = 1;
        $o->password = 'password123';
        $mockC->modify($o);

        $mock = $this->_getMock('PartialMockOA_Dll_User_PermMatrixTest', true);
        $o->contactName = 'Test P185 Modified';
        $this->assertTrue($mock->modify($o), 'P185');
    }

    /** P186: MANAGER + User + EDIT other = DENY */
    public function testP186_Manager_User_Edit_Other_Deny()
    {
        $mockC = $this->_getMock('PartialMockOA_Dll_User_PermMatrixTest', true);
        $o = new OA_Dll_UserInfo();
        $o->userName = 'testP186_' . uniqid();
        $o->contactName = 'Test P186';
        $o->emailAddress = 'testP186@example.com';
        $o->defaultAccountId = 1;
        $o->password = 'password123';
        $mockC->modify($o);

        $mock = $this->_getMock('PartialMockOA_Dll_User_PermMatrixTest', false);
        $o->contactName = 'Test P186 Modified';
        $this->assertFalse($mock->modify($o), 'P186');
    }

    /** P187: ADVERTISER + User + EDIT = DENY */
    public function testP187_Advertiser_User_Edit_Deny()
    {
        $mockC = $this->_getMock('PartialMockOA_Dll_User_PermMatrixTest', true);
        $o = new OA_Dll_UserInfo();
        $o->userName = 'testP187_' . uniqid();
        $o->contactName = 'Test P187';
        $o->emailAddress = 'testP187@example.com';
        $o->defaultAccountId = 1;
        $o->password = 'password123';
        $mockC->modify($o);

        $mock = $this->_getMock('PartialMockOA_Dll_User_PermMatrixTest', false);
        $o->contactName = 'Test P187 Modified';
        $this->assertFalse($mock->modify($o), 'P187');
    }

    /** P188: TRAFFICKER + User + EDIT = DENY */
    public function testP188_Trafficker_User_Edit_Deny()
    {
        $mockC = $this->_getMock('PartialMockOA_Dll_User_PermMatrixTest', true);
        $o = new OA_Dll_UserInfo();
        $o->userName = 'testP188_' . uniqid();
        $o->contactName = 'Test P188';
        $o->emailAddress = 'testP188@example.com';
        $o->defaultAccountId = 1;
        $o->password = 'password123';
        $mockC->modify($o);

        $mock = $this->_getMock('PartialMockOA_Dll_User_PermMatrixTest', false);
        $o->contactName = 'Test P188 Modified';
        $this->assertFalse($mock->modify($o), 'P188');
    }

    /** P189: ADMIN + User + DELETE = ALLOW */
    public function testP189_Admin_User_Delete_Allow()
    {
        $mockC = $this->_getMock('PartialMockOA_Dll_User_PermMatrixTest', true);
        $o = new OA_Dll_UserInfo();
        $o->userName = 'testP189_' . uniqid();
        $o->contactName = 'Test P189';
        $o->emailAddress = 'testP189@example.com';
        $o->defaultAccountId = 1;
        $o->password = 'password123';
        $mockC->modify($o);

        $mock = $this->_getMock('PartialMockOA_Dll_User_PermMatrixTest', true);
        $this->assertTrue($mock->delete($o->userId), 'P189');
    }

    /** P190: MANAGER + User + DELETE = DENY */
    public function testP190_Manager_User_Delete_Deny()
    {
        $mockC = $this->_getMock('PartialMockOA_Dll_User_PermMatrixTest', true);
        $o = new OA_Dll_UserInfo();
        $o->userName = 'testP190_' . uniqid();
        $o->contactName = 'Test P190';
        $o->emailAddress = 'testP190@example.com';
        $o->defaultAccountId = 1;
        $o->password = 'password123';
        $mockC->modify($o);

        $mock = $this->_getMock('PartialMockOA_Dll_User_PermMatrixTest', false);
        $this->assertFalse($mock->delete($o->userId), 'P190');
    }

    /** P191: ADVERTISER + User + DELETE = DENY */
    public function testP191_Advertiser_User_Delete_Deny()
    {
        $mockC = $this->_getMock('PartialMockOA_Dll_User_PermMatrixTest', true);
        $o = new OA_Dll_UserInfo();
        $o->userName = 'testP191_' . uniqid();
        $o->contactName = 'Test P191';
        $o->emailAddress = 'testP191@example.com';
        $o->defaultAccountId = 1;
        $o->password = 'password123';
        $mockC->modify($o);

        $mock = $this->_getMock('PartialMockOA_Dll_User_PermMatrixTest', false);
        $this->assertFalse($mock->delete($o->userId), 'P191');
    }

    /** P192: TRAFFICKER + User + DELETE = DENY */
    public function testP192_Trafficker_User_Delete_Deny()
    {
        $mockC = $this->_getMock('PartialMockOA_Dll_User_PermMatrixTest', true);
        $o = new OA_Dll_UserInfo();
        $o->userName = 'testP192_' . uniqid();
        $o->contactName = 'Test P192';
        $o->emailAddress = 'testP192@example.com';
        $o->defaultAccountId = 1;
        $o->password = 'password123';
        $mockC->modify($o);

        $mock = $this->_getMock('PartialMockOA_Dll_User_PermMatrixTest', false);
        $this->assertFalse($mock->delete($o->userId), 'P192');
    }

    /** P193: ADMIN + User + VIEW = ALLOW */
    public function testP193_Admin_User_View_Allow()
    {
        $mockC = $this->_getMock('PartialMockOA_Dll_User_PermMatrixTest', true);
        $o = new OA_Dll_UserInfo();
        $o->userName = 'testP193_' . uniqid();
        $o->contactName = 'Test P193';
        $o->emailAddress = 'testP193@example.com';
        $o->defaultAccountId = 1;
        $o->password = 'password123';
        $mockC->modify($o);

        $mock = $this->_getMock('PartialMockOA_Dll_User_PermMatrixTest', true);
        $out = null;
        $this->assertTrue($mock->getUser($o->userId, $out), 'P193');
    }

    /** P194: MANAGER + User + VIEW = ALLOW (with access) */
    public function testP194_Manager_User_View_Allow()
    {
        $mockC = $this->_getMock('PartialMockOA_Dll_User_PermMatrixTest', true);
        $o = new OA_Dll_UserInfo();
        $o->userName = 'testP194_' . uniqid();
        $o->contactName = 'Test P194';
        $o->emailAddress = 'testP194@example.com';
        $o->defaultAccountId = 1;
        $o->password = 'password123';
        $mockC->modify($o);

        $mock = $this->_getMock('PartialMockOA_Dll_User_PermMatrixTest', true);
        $out = null;
        $this->assertTrue($mock->getUser($o->userId, $out), 'P194');
    }

    /** P195: MANAGER + User + VIEW other = DENY */
    public function testP195_Manager_User_View_Other_Deny()
    {
        $mockC = $this->_getMock('PartialMockOA_Dll_User_PermMatrixTest', true);
        $o = new OA_Dll_UserInfo();
        $o->userName = 'testP195_' . uniqid();
        $o->contactName = 'Test P195';
        $o->emailAddress = 'testP195@example.com';
        $o->defaultAccountId = 1;
        $o->password = 'password123';
        $mockC->modify($o);

        $mock = $this->_getMock('PartialMockOA_Dll_User_PermMatrixTest', false);
        $out = null;
        $this->assertFalse($mock->getUser($o->userId, $out), 'P195');
    }

    /** P196: ADVERTISER + User + VIEW = DENY */
    public function testP196_Advertiser_User_View_Deny()
    {
        $mockC = $this->_getMock('PartialMockOA_Dll_User_PermMatrixTest', true);
        $o = new OA_Dll_UserInfo();
        $o->userName = 'testP196_' . uniqid();
        $o->contactName = 'Test P196';
        $o->emailAddress = 'testP196@example.com';
        $o->defaultAccountId = 1;
        $o->password = 'password123';
        $mockC->modify($o);

        $mock = $this->_getMock('PartialMockOA_Dll_User_PermMatrixTest', false);
        $out = null;
        $this->assertFalse($mock->getUser($o->userId, $out), 'P196');
    }

    /** P197: TRAFFICKER + User + VIEW = DENY */
    public function testP197_Trafficker_User_View_Deny()
    {
        $mockC = $this->_getMock('PartialMockOA_Dll_User_PermMatrixTest', true);
        $o = new OA_Dll_UserInfo();
        $o->userName = 'testP197_' . uniqid();
        $o->contactName = 'Test P197';
        $o->emailAddress = 'testP197@example.com';
        $o->defaultAccountId = 1;
        $o->password = 'password123';
        $mockC->modify($o);

        $mock = $this->_getMock('PartialMockOA_Dll_User_PermMatrixTest', false);
        $out = null;
        $this->assertFalse($mock->getUser($o->userId, $out), 'P197');
    }

    /** P198: ADMIN + User + LIST = ALLOW */
    public function testP198_Admin_User_List_Allow()
    {
        $mock = $this->_getMock('PartialMockOA_Dll_User_PermMatrixTest', true);
        $aList = [];
        $this->assertTrue($mock->getUserListByAccountId(1, $aList), 'P198');
    }

    /** P199: MANAGER + User + LIST own account = ALLOW */
    public function testP199_Manager_User_List_Own_Allow()
    {
        $mock = $this->_getMock('PartialMockOA_Dll_User_PermMatrixTest', true);
        $aList = [];
        $this->assertTrue($mock->getUserListByAccountId(1, $aList), 'P199');
    }

    /** P200: MANAGER + User + LIST other account = DENY */
    public function testP200_Manager_User_List_Other_Deny()
    {
        $oAgency = $this->_createAgency();
        $mock = $this->_getMock('PartialMockOA_Dll_User_PermMatrixTest', false);
        $aList = [];
        $this->assertFalse($mock->getUserListByAccountId($oAgency->accountId, $aList), 'P200');
    }

    // =========================================================================
    // CROSS-ENTITY PERMISSION TESTS
    // =========================================================================

    /** P201: ADMIN + Banner + LIST by campaign = ALLOW */
    public function testP201_Admin_Banner_ListByCampaign_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Banner_PermMatrixTest', true);
        $aList = [];
        $this->assertTrue($mock->getBannerListByCampaignId($oCamp->campaignId, $aList), 'P201');
    }

    /** P202: MANAGER + Banner + LIST by campaign other = DENY */
    public function testP202_Manager_Banner_ListByCampaign_Other_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId);
        $mock = $this->_getMock('PartialMockOA_Dll_Banner_PermMatrixTest', false);
        $aList = [];
        $this->assertFalse($mock->getBannerListByCampaignId($oCamp->campaignId, $aList), 'P202');
    }

    /** P203: ADMIN + Campaign + LIST by advertiser = ALLOW */
    public function testP203_Admin_Campaign_ListByAdvertiser_Allow()
    {
        $oAdv = $this->_createAdvertiser();
        $mock = $this->_getMock('PartialMockOA_Dll_Campaign_PermMatrixTest', true);
        $aList = [];
        $this->assertTrue($mock->getCampaignListByAdvertiserId($oAdv->advertiserId, $aList), 'P203');
    }

    /** P204: MANAGER + Campaign + LIST by advertiser other = DENY */
    public function testP204_Manager_Campaign_ListByAdvertiser_Other_Deny()
    {
        $oAdv = $this->_createAdvertiser();
        $mock = $this->_getMock('PartialMockOA_Dll_Campaign_PermMatrixTest', false);
        $aList = [];
        $this->assertFalse($mock->getCampaignListByAdvertiserId($oAdv->advertiserId, $aList), 'P204');
    }

    /** P205: ADMIN + Advertiser + LIST by agency = ALLOW */
    public function testP205_Admin_Advertiser_ListByAgency_Allow()
    {
        $oAgency = $this->_createAgency();
        $mock = $this->_getMock('PartialMockOA_Dll_Advertiser_PermMatrixTest', true);
        $aList = [];
        $this->assertTrue($mock->getAdvertiserListByAgencyId($oAgency->agencyId, $aList), 'P205');
    }

    /** P206: MANAGER + Advertiser + LIST by agency other = DENY */
    public function testP206_Manager_Advertiser_ListByAgency_Other_Deny()
    {
        $oAgency = $this->_createAgency();
        $mock = $this->_getMock('PartialMockOA_Dll_Advertiser_PermMatrixTest', false);
        $aList = [];
        $this->assertFalse($mock->getAdvertiserListByAgencyId($oAgency->agencyId, $aList), 'P206');
    }

    /** P207: ADMIN + Publisher + LIST by agency = ALLOW */
    public function testP207_Admin_Publisher_ListByAgency_Allow()
    {
        $oAgency = $this->_createAgency();
        $mock = $this->_getMock('PartialMockOA_Dll_Publisher_PermMatrixTest', true);
        $aList = [];
        $this->assertTrue($mock->getPublisherListByAgencyId($oAgency->agencyId, $aList), 'P207');
    }

    /** P208: MANAGER + Publisher + LIST by agency other = DENY */
    public function testP208_Manager_Publisher_ListByAgency_Other_Deny()
    {
        $oAgency = $this->_createAgency();
        $mock = $this->_getMock('PartialMockOA_Dll_Publisher_PermMatrixTest', false);
        $aList = [];
        $this->assertFalse($mock->getPublisherListByAgencyId($oAgency->agencyId, $aList), 'P208');
    }

}
