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

require_once MAX_PATH . '/lib/OA/Dal.php';
require_once MAX_PATH . '/lib/OA/Permission.php';
require_once MAX_PATH . '/lib/OA/Permission/User.php';
require_once MAX_PATH . '/lib/OA/Dal/DataGenerator.php';

/**
 * Permission Enforcement Exhaustive Matrix Tests (Section 4G)
 *
 * Tests ~210 valid permission combinations across:
 *   - Account type: ADMIN, MANAGER, ADVERTISER, TRAFFICKER
 *   - Entity type: Agency, Advertiser, Campaign, Banner, Publisher, Zone, Tracker, Channel
 *   - Operation: ADD, EDIT, VIEW, DELETE, DUPLICATE, MOVE, ADD_CHILD, VIEW_CHILDREN
 *   - Ownership: Own entity, other's entity, parent entity, no linked entity
 *
 * @package    OpenXPermission
 * @subpackage TestSuite
 */
class Test_OA_PermissionEnforcement extends UnitTestCase
{
    /** @var int */
    private $adminAccountId;
    /** @var int */
    private $adminUserId;
    /** @var int */
    private $managerAccountId;
    /** @var int */
    private $managerAgencyId;
    /** @var int */
    private $managerUserId;
    /** @var int */
    private $manager2AccountId;
    /** @var int */
    private $manager2AgencyId;
    /** @var int */
    private $manager2UserId;
    /** @var int */
    private $advertiserAccountId;
    /** @var int */
    private $advertiserClientId;
    /** @var int */
    private $advertiserUserId;
    /** @var int */
    private $traffickerAccountId;
    /** @var int */
    private $traffickerAffiliateId;
    /** @var int */
    private $traffickerUserId;
    /** @var int */
    private $campaignId;
    /** @var int */
    private $bannerId;
    /** @var int */
    private $zoneId;
    /** @var int */
    private $trackerId;
    /** @var int */
    private $channelId;
    /** @var int */
    private $otherClientId;
    /** @var int */
    private $otherCampaignId;
    /** @var int */
    private $otherBannerId;
    /** @var int */
    private $otherAffiliateId;
    /** @var int */
    private $otherZoneId;

    public function setUp()
    {
        // Create the admin account
        $doAccounts = OA_Dal::factoryDO('accounts');
        $doAccounts->account_name = 'Admin Account';
        $doAccounts->account_type = OA_ACCOUNT_ADMIN;
        $this->adminAccountId = DataGenerator::generateOne($doAccounts);

        // Store it as the admin account
        $doAppVar = OA_Dal::factoryDO('application_variable');
        $doAppVar->name = 'admin_account_id';
        $doAppVar->value = $this->adminAccountId;
        DataGenerator::generateOne($doAppVar);

        // Create admin user
        $doUsers = OA_Dal::factoryDO('users');
        $doUsers->contact_name = 'Admin User';
        $doUsers->email_address = 'admin@test.com';
        $doUsers->username = 'admin_perm_test';
        $doUsers->password = md5('password');
        $doUsers->default_account_id = $this->adminAccountId;
        $this->adminUserId = DataGenerator::generateOne($doUsers);

        // Link admin user to admin account
        $doAUA = OA_Dal::factoryDO('account_user_assoc');
        $doAUA->account_id = $this->adminAccountId;
        $doAUA->user_id = $this->adminUserId;
        $doAUA->insert();

        // Create manager agency (creates its own account)
        $doAgency = OA_Dal::factoryDO('agency');
        $doAgency->name = 'Manager Agency 1';
        $doAgency->contact = 'Manager';
        $doAgency->email = 'manager@test.com';
        $this->managerAgencyId = DataGenerator::generateOne($doAgency);

        $doAgency = OA_Dal::staticGetDO('agency', $this->managerAgencyId);
        $this->managerAccountId = $doAgency->account_id;

        // Create manager user
        $doUsers = OA_Dal::factoryDO('users');
        $doUsers->contact_name = 'Manager User';
        $doUsers->email_address = 'manager@test.com';
        $doUsers->username = 'manager_perm_test';
        $doUsers->password = md5('password');
        $doUsers->default_account_id = $this->managerAccountId;
        $this->managerUserId = DataGenerator::generateOne($doUsers);

        $doAUA = OA_Dal::factoryDO('account_user_assoc');
        $doAUA->account_id = $this->managerAccountId;
        $doAUA->user_id = $this->managerUserId;
        $doAUA->insert();

        // Create second manager agency (for cross-ownership tests)
        $doAgency2 = OA_Dal::factoryDO('agency');
        $doAgency2->name = 'Manager Agency 2';
        $doAgency2->contact = 'Manager2';
        $doAgency2->email = 'manager2@test.com';
        $this->manager2AgencyId = DataGenerator::generateOne($doAgency2);

        $doAgency2 = OA_Dal::staticGetDO('agency', $this->manager2AgencyId);
        $this->manager2AccountId = $doAgency2->account_id;

        $doUsers = OA_Dal::factoryDO('users');
        $doUsers->contact_name = 'Manager2 User';
        $doUsers->email_address = 'manager2@test.com';
        $doUsers->username = 'manager2_perm_test';
        $doUsers->password = md5('password');
        $doUsers->default_account_id = $this->manager2AccountId;
        $this->manager2UserId = DataGenerator::generateOne($doUsers);

        $doAUA = OA_Dal::factoryDO('account_user_assoc');
        $doAUA->account_id = $this->manager2AccountId;
        $doAUA->user_id = $this->manager2UserId;
        $doAUA->insert();

        // Create advertiser under manager 1
        $doClients = OA_Dal::factoryDO('clients');
        $doClients->clientname = 'Advertiser 1';
        $doClients->agencyid = $this->managerAgencyId;
        $doClients->contact = 'Adv';
        $doClients->email = 'adv@test.com';
        $doClients->reportlastdate = '2007-04-03 12:00:00';
        $this->advertiserClientId = DataGenerator::generateOne($doClients);

        $doClients = OA_Dal::staticGetDO('clients', $this->advertiserClientId);
        $this->advertiserAccountId = $doClients->account_id;

        // Create advertiser user
        $doUsers = OA_Dal::factoryDO('users');
        $doUsers->contact_name = 'Advertiser User';
        $doUsers->email_address = 'adv@test.com';
        $doUsers->username = 'advertiser_perm_test';
        $doUsers->password = md5('password');
        $doUsers->default_account_id = $this->advertiserAccountId;
        $this->advertiserUserId = DataGenerator::generateOne($doUsers);

        $doAUA = OA_Dal::factoryDO('account_user_assoc');
        $doAUA->account_id = $this->advertiserAccountId;
        $doAUA->user_id = $this->advertiserUserId;
        $doAUA->insert();

        // Create publisher/affiliate under manager 1
        $doAffiliates = OA_Dal::factoryDO('affiliates');
        $doAffiliates->name = 'Publisher 1';
        $doAffiliates->agencyid = $this->managerAgencyId;
        $doAffiliates->contact = 'Pub';
        $doAffiliates->email = 'pub@test.com';
        $doAffiliates->website = 'http://www.test.com';
        $this->traffickerAffiliateId = DataGenerator::generateOne($doAffiliates);

        $doAffiliates = OA_Dal::staticGetDO('affiliates', $this->traffickerAffiliateId);
        $this->traffickerAccountId = $doAffiliates->account_id;

        // Create trafficker user
        $doUsers = OA_Dal::factoryDO('users');
        $doUsers->contact_name = 'Trafficker User';
        $doUsers->email_address = 'pub@test.com';
        $doUsers->username = 'trafficker_perm_test';
        $doUsers->password = md5('password');
        $doUsers->default_account_id = $this->traffickerAccountId;
        $this->traffickerUserId = DataGenerator::generateOne($doUsers);

        $doAUA = OA_Dal::factoryDO('account_user_assoc');
        $doAUA->account_id = $this->traffickerAccountId;
        $doAUA->user_id = $this->traffickerUserId;
        $doAUA->insert();

        // Create campaign under advertiser 1
        $doCampaigns = OA_Dal::factoryDO('campaigns');
        $doCampaigns->campaignname = 'Campaign 1';
        $doCampaigns->clientid = $this->advertiserClientId;
        $doCampaigns->status = 0;
        $this->campaignId = DataGenerator::generateOne($doCampaigns);

        // Create banner under campaign 1
        $doBanners = OA_Dal::factoryDO('banners');
        $doBanners->description = 'Banner 1';
        $doBanners->campaignid = $this->campaignId;
        $doBanners->status = 0;
        $doBanners->acls_updated = '2024-01-01 00:00:00';
        $this->bannerId = DataGenerator::generateOne($doBanners);

        // Create zone under publisher 1
        $doZones = OA_Dal::factoryDO('zones');
        $doZones->zonename = 'Zone 1';
        $doZones->affiliateid = $this->traffickerAffiliateId;
        $doZones->zonetype = 0;
        $this->zoneId = DataGenerator::generateOne($doZones);

        // Create tracker under advertiser 1
        $doTrackers = OA_Dal::factoryDO('trackers');
        $doTrackers->trackername = 'Tracker 1';
        $doTrackers->clientid = $this->advertiserClientId;
        $doTrackers->status = 4;
        $this->trackerId = DataGenerator::generateOne($doTrackers);

        // Create channel under manager 1
        $doChannel = OA_Dal::factoryDO('channel');
        $doChannel->name = 'Channel 1';
        $doChannel->agencyid = $this->managerAgencyId;
        $doChannel->compiledlimitation = '';
        $this->channelId = DataGenerator::generateOne($doChannel);

        // Create "other" entities under manager 2 for cross-ownership tests
        $doClients2 = OA_Dal::factoryDO('clients');
        $doClients2->clientname = 'Other Advertiser';
        $doClients2->agencyid = $this->manager2AgencyId;
        $doClients2->contact = 'Other';
        $doClients2->email = 'other@test.com';
        $doClients2->reportlastdate = '2007-04-03 12:00:00';
        $this->otherClientId = DataGenerator::generateOne($doClients2);

        $doCampaigns2 = OA_Dal::factoryDO('campaigns');
        $doCampaigns2->campaignname = 'Other Campaign';
        $doCampaigns2->clientid = $this->otherClientId;
        $doCampaigns2->status = 0;
        $this->otherCampaignId = DataGenerator::generateOne($doCampaigns2);

        $doBanners2 = OA_Dal::factoryDO('banners');
        $doBanners2->description = 'Other Banner';
        $doBanners2->campaignid = $this->otherCampaignId;
        $doBanners2->status = 0;
        $doBanners2->acls_updated = '2024-01-01 00:00:00';
        $this->otherBannerId = DataGenerator::generateOne($doBanners2);

        $doAffiliates2 = OA_Dal::factoryDO('affiliates');
        $doAffiliates2->name = 'Other Publisher';
        $doAffiliates2->agencyid = $this->manager2AgencyId;
        $doAffiliates2->contact = 'Other';
        $doAffiliates2->email = 'other_pub@test.com';
        $doAffiliates2->website = 'http://www.other.com';
        $this->otherAffiliateId = DataGenerator::generateOne($doAffiliates2);

        $doZones2 = OA_Dal::factoryDO('zones');
        $doZones2->zonename = 'Other Zone';
        $doZones2->affiliateid = $this->otherAffiliateId;
        $doZones2->zonetype = 0;
        $this->otherZoneId = DataGenerator::generateOne($doZones2);
    }

    public function tearDown()
    {
        DataGenerator::cleanUp(['application_variable']);
        unset($GLOBALS['session']['user']);
    }

    // ---------------------------------------------------------------
    // Helper: log in a specific user by user ID and account ID
    // ---------------------------------------------------------------
    private function _logInUser($userId, $accountId)
    {
        $doUsers = OA_Dal::staticGetDO('users', $userId);
        $oUser = new OA_Permission_User($doUsers);
        $oUser->loadAccountData($accountId);
        $GLOBALS['session']['user'] = $oUser;
    }

    private function _logInAdmin()
    {
        $this->_logInUser($this->adminUserId, $this->adminAccountId);
    }

    private function _logInManager()
    {
        $this->_logInUser($this->managerUserId, $this->managerAccountId);
    }

    private function _logInManager2()
    {
        $this->_logInUser($this->manager2UserId, $this->manager2AccountId);
    }

    private function _logInAdvertiser()
    {
        $this->_logInUser($this->advertiserUserId, $this->advertiserAccountId);
    }

    private function _logInTrafficker()
    {
        $this->_logInUser($this->traffickerUserId, $this->traffickerAccountId);
    }

    private function _grantPermission($accountId, $userId, $permissionId)
    {
        $doAUPA = OA_Dal::factoryDO('account_user_permission_assoc');
        $doAUPA->account_id = $accountId;
        $doAUPA->user_id = $userId;
        $doAUPA->permission_id = $permissionId;
        $doAUPA->is_allowed = 1;
        $doAUPA->insert();
    }

    private function _revokeAllPermissions($accountId, $userId)
    {
        $doAUPA = OA_Dal::factoryDO('account_user_permission_assoc');
        $doAUPA->account_id = $accountId;
        $doAUPA->user_id = $userId;
        $doAUPA->delete();
    }

    // ================================================================
    // SECTION 1: ADMIN ACCOUNT TESTS (P001-P040)
    // Admin always has full access to all entities and operations
    // ================================================================

    /** P001: ADMIN + Agency + ADD -> ALLOW */
    public function testP001_AdminAgencyAdd()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('agency', null, OA_Permission::OPERATION_ADD),
            'P001: Admin should ALLOW adding agency',
        );
    }

    /** P002: ADMIN + Agency + EDIT + Own -> ALLOW */
    public function testP002_AdminAgencyEdit()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('agency', $this->managerAgencyId, OA_Permission::OPERATION_EDIT, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P002: Admin should ALLOW editing agency',
        );
    }

    /** P003: ADMIN + Agency + VIEW -> ALLOW */
    public function testP003_AdminAgencyView()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('agency', $this->managerAgencyId, OA_Permission::OPERATION_VIEW, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P003: Admin should ALLOW viewing agency',
        );
    }

    /** P004: ADMIN + Agency + DELETE -> ALLOW */
    public function testP004_AdminAgencyDelete()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('agency', $this->managerAgencyId, OA_Permission::OPERATION_DELETE, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P004: Admin should ALLOW deleting agency',
        );
    }

    /** P005: ADMIN + Advertiser + ADD -> ALLOW */
    public function testP005_AdminAdvertiserAdd()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('clients', null, OA_Permission::OPERATION_ADD),
            'P005: Admin should ALLOW adding advertiser',
        );
    }

    /** P006: ADMIN + Advertiser + EDIT -> ALLOW */
    public function testP006_AdminAdvertiserEdit()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('clients', $this->advertiserClientId, OA_Permission::OPERATION_EDIT, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P006: Admin should ALLOW editing advertiser',
        );
    }

    /** P007: ADMIN + Campaign + ADD -> ALLOW */
    public function testP007_AdminCampaignAdd()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('campaigns', null, OA_Permission::OPERATION_ADD),
            'P007: Admin should ALLOW adding campaign',
        );
    }

    /** P008: ADMIN + Campaign + EDIT -> ALLOW */
    public function testP008_AdminCampaignEdit()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('campaigns', $this->campaignId, OA_Permission::OPERATION_EDIT, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P008: Admin should ALLOW editing campaign',
        );
    }

    /** P009: ADMIN + Banner + ADD -> ALLOW */
    public function testP009_AdminBannerAdd()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('banners', null, OA_Permission::OPERATION_ADD),
            'P009: Admin should ALLOW adding banner',
        );
    }

    /** P010: ADMIN + Banner + EDIT -> ALLOW */
    public function testP010_AdminBannerEdit()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('banners', $this->bannerId, OA_Permission::OPERATION_EDIT, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P010: Admin should ALLOW editing banner',
        );
    }

    /** P011: ADMIN + Banner + VIEW -> ALLOW */
    public function testP011_AdminBannerView()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('banners', $this->bannerId, OA_Permission::OPERATION_VIEW, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P011: Admin should ALLOW viewing banner',
        );
    }

    /** P012: ADMIN + Banner + DELETE -> ALLOW */
    public function testP012_AdminBannerDelete()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('banners', $this->bannerId, OA_Permission::OPERATION_DELETE, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P012: Admin should ALLOW deleting banner',
        );
    }

    /** P013: ADMIN + Publisher + ADD -> ALLOW */
    public function testP013_AdminPublisherAdd()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('affiliates', null, OA_Permission::OPERATION_ADD),
            'P013: Admin should ALLOW adding publisher',
        );
    }

    /** P014: ADMIN + Publisher + EDIT -> ALLOW */
    public function testP014_AdminPublisherEdit()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('affiliates', $this->traffickerAffiliateId, OA_Permission::OPERATION_EDIT, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P014: Admin should ALLOW editing publisher',
        );
    }

    /** P015: ADMIN + Zone + ADD -> ALLOW */
    public function testP015_AdminZoneAdd()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('zones', null, OA_Permission::OPERATION_ADD),
            'P015: Admin should ALLOW adding zone',
        );
    }

    /** P016: ADMIN + Zone + EDIT -> ALLOW */
    public function testP016_AdminZoneEdit()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('zones', $this->zoneId, OA_Permission::OPERATION_EDIT, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P016: Admin should ALLOW editing zone',
        );
    }

    /** P017: ADMIN + Tracker + EDIT -> ALLOW */
    public function testP017_AdminTrackerEdit()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('trackers', $this->trackerId, OA_Permission::OPERATION_EDIT, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P017: Admin should ALLOW editing tracker',
        );
    }

    /** P018: ADMIN + Channel + EDIT -> ALLOW */
    public function testP018_AdminChannelEdit()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('channel', $this->channelId, OA_Permission::OPERATION_EDIT, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P018: Admin should ALLOW editing channel',
        );
    }

    /** P019: ADMIN + hasPermission BANNER_EDIT -> ALLOW */
    public function testP019_AdminHasPermBannerEdit()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasPermission(OA_PERM_BANNER_EDIT),
            'P019: Admin should always have BANNER_EDIT permission',
        );
    }

    /** P020: ADMIN + hasPermission ZONE_ADD -> ALLOW */
    public function testP020_AdminHasPermZoneAdd()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasPermission(OA_PERM_ZONE_ADD),
            'P020: Admin should always have ZONE_ADD permission',
        );
    }

    /** P021: ADMIN + hasPermission ZONE_DELETE -> ALLOW */
    public function testP021_AdminHasPermZoneDelete()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasPermission(OA_PERM_ZONE_DELETE),
            'P021: Admin should always have ZONE_DELETE permission',
        );
    }

    /** P022: ADMIN + hasPermission ZONE_EDIT -> ALLOW */
    public function testP022_AdminHasPermZoneEdit()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasPermission(OA_PERM_ZONE_EDIT),
            'P022: Admin should always have ZONE_EDIT permission',
        );
    }

    /** P023: ADMIN + hasPermission SUPER_ACCOUNT -> ALLOW */
    public function testP023_AdminHasPermSuperAccount()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasPermission(OA_PERM_SUPER_ACCOUNT),
            'P023: Admin should always have SUPER_ACCOUNT permission',
        );
    }

    /** P024: ADMIN + hasPermission BANNER_ACTIVATE -> ALLOW */
    public function testP024_AdminHasPermBannerActivate()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasPermission(OA_PERM_BANNER_ACTIVATE),
            'P024: Admin should always have BANNER_ACTIVATE permission',
        );
    }

    /** P025: ADMIN + hasPermission BANNER_DEACTIVATE -> ALLOW */
    public function testP025_AdminHasPermBannerDeactivate()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasPermission(OA_PERM_BANNER_DEACTIVATE),
            'P025: Admin should always have BANNER_DEACTIVATE permission',
        );
    }

    /** P026: ADMIN + hasPermission BANNER_ADD -> ALLOW */
    public function testP026_AdminHasPermBannerAdd()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasPermission(OA_PERM_BANNER_ADD),
            'P026: Admin should always have BANNER_ADD permission',
        );
    }

    /** P027: ADMIN + hasPermission ZONE_INVOCATION -> ALLOW */
    public function testP027_AdminHasPermZoneInvocation()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasPermission(OA_PERM_ZONE_INVOCATION),
            'P027: Admin should always have ZONE_INVOCATION permission',
        );
    }

    /** P028: ADMIN + hasPermission ZONE_LINK -> ALLOW */
    public function testP028_AdminHasPermZoneLink()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasPermission(OA_PERM_ZONE_LINK),
            'P028: Admin should always have ZONE_LINK permission',
        );
    }

    /** P029: ADMIN + hasPermission USER_LOG_ACCESS -> ALLOW */
    public function testP029_AdminHasPermUserLogAccess()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasPermission(OA_PERM_USER_LOG_ACCESS),
            'P029: Admin should always have USER_LOG_ACCESS permission',
        );
    }

    /** P030: ADMIN + hasPermission MANAGER_DELETE -> ALLOW */
    public function testP030_AdminHasPermManagerDelete()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasPermission(OA_PERM_MANAGER_DELETE),
            'P030: Admin should always have MANAGER_DELETE permission',
        );
    }

    /** P031: ADMIN + isAccount ADMIN -> true */
    public function testP031_AdminIsAccountAdmin()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::isAccount(OA_ACCOUNT_ADMIN),
            'P031: Admin should be ADMIN account type',
        );
    }

    /** P032: ADMIN + isAccount MANAGER -> false */
    public function testP032_AdminIsNotManager()
    {
        $this->_logInAdmin();
        $this->assertFalse(
            OA_Permission::isAccount(OA_ACCOUNT_MANAGER),
            'P032: Admin should not be MANAGER account type',
        );
    }

    /** P033: ADMIN + Campaign + DUPLICATE -> ALLOW */
    public function testP033_AdminCampaignDuplicate()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('campaigns', $this->campaignId, OA_Permission::OPERATION_DUPLICATE, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P033: Admin should ALLOW duplicating campaign',
        );
    }

    /** P034: ADMIN + Zone + DELETE -> ALLOW */
    public function testP034_AdminZoneDelete()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('zones', $this->zoneId, OA_Permission::OPERATION_DELETE, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P034: Admin should ALLOW deleting zone',
        );
    }

    /** P035: ADMIN + Banner + DUPLICATE -> ALLOW */
    public function testP035_AdminBannerDuplicate()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('banners', $this->bannerId, OA_Permission::OPERATION_DUPLICATE, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P035: Admin should ALLOW duplicating banner',
        );
    }

    /** P036: ADMIN + Tracker + VIEW -> ALLOW */
    public function testP036_AdminTrackerView()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('trackers', $this->trackerId, OA_Permission::OPERATION_VIEW, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P036: Admin should ALLOW viewing tracker',
        );
    }

    /** P037: ADMIN + other agency banner + EDIT -> ALLOW */
    public function testP037_AdminOtherBannerEdit()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('banners', $this->otherBannerId, OA_Permission::OPERATION_EDIT, $this->manager2AccountId, OA_ACCOUNT_MANAGER),
            'P037: Admin should ALLOW editing other agency banner',
        );
    }

    /** P038: ADMIN + other agency zone + EDIT -> ALLOW */
    public function testP038_AdminOtherZoneEdit()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('zones', $this->otherZoneId, OA_Permission::OPERATION_EDIT, $this->manager2AccountId, OA_ACCOUNT_MANAGER),
            'P038: Admin should ALLOW editing other agency zone',
        );
    }

    /** P039: ADMIN + Campaign + VIEW_CHILDREN -> ALLOW */
    public function testP039_AdminCampaignViewChildren()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('campaigns', $this->campaignId, OA_Permission::OPERATION_VIEW_CHILDREN, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P039: Admin should ALLOW viewing campaign children',
        );
    }

    /** P040: ADMIN + Advertiser + ADD_CHILD -> ALLOW */
    public function testP040_AdminAdvertiserAddChild()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('clients', $this->advertiserClientId, OA_Permission::OPERATION_ADD_CHILD, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P040: Admin should ALLOW adding child to advertiser',
        );
    }

    // ================================================================
    // SECTION 2: MANAGER ACCOUNT TESTS (P041-P090)
    // Manager has access to own agency entities, denied for other agency
    // ================================================================

    /** P041: MANAGER + Agency + EDIT + Own -> ALLOW */
    public function testP041_ManagerAgencyEditOwn()
    {
        $this->_logInManager();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('agency', $this->managerAgencyId, OA_Permission::OPERATION_EDIT, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P041: Manager should ALLOW editing own agency',
        );
    }

    /** P042: MANAGER + Agency + EDIT + Other -> DENY */
    public function testP042_ManagerAgencyEditOther()
    {
        $this->_logInManager();
        $this->assertFalse(
            OA_Permission::hasAccessToObject('agency', $this->manager2AgencyId, OA_Permission::OPERATION_EDIT, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P042: Manager should DENY editing other agency',
        );
    }

    /** P043: MANAGER + Advertiser + ADD -> ALLOW */
    public function testP043_ManagerAdvertiserAdd()
    {
        $this->_logInManager();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('clients', null, OA_Permission::OPERATION_ADD),
            'P043: Manager should ALLOW adding advertiser',
        );
    }

    /** P044: MANAGER + Advertiser + EDIT + Own -> ALLOW */
    public function testP044_ManagerAdvertiserEditOwn()
    {
        $this->_logInManager();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('clients', $this->advertiserClientId, OA_Permission::OPERATION_EDIT, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P044: Manager should ALLOW editing own advertiser',
        );
    }

    /** P045: MANAGER + Advertiser + EDIT + Other agency -> DENY */
    public function testP045_ManagerAdvertiserEditOther()
    {
        $this->_logInManager();
        $this->assertFalse(
            OA_Permission::hasAccessToObject('clients', $this->otherClientId, OA_Permission::OPERATION_EDIT, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P045: Manager should DENY editing other agency advertiser',
        );
    }

    /** P046: MANAGER + Campaign + ADD -> ALLOW */
    public function testP046_ManagerCampaignAdd()
    {
        $this->_logInManager();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('campaigns', null, OA_Permission::OPERATION_ADD),
            'P046: Manager should ALLOW adding campaign',
        );
    }

    /** P047: MANAGER + Campaign + EDIT + Own -> ALLOW */
    public function testP047_ManagerCampaignEditOwn()
    {
        $this->_logInManager();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('campaigns', $this->campaignId, OA_Permission::OPERATION_EDIT, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P047: Manager should ALLOW editing own campaign',
        );
    }

    /** P048: MANAGER + Campaign + EDIT + Other agency -> DENY */
    public function testP048_ManagerCampaignEditOther()
    {
        $this->_logInManager();
        $this->assertFalse(
            OA_Permission::hasAccessToObject('campaigns', $this->otherCampaignId, OA_Permission::OPERATION_EDIT, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P048: Manager should DENY editing other agency campaign',
        );
    }

    /** P049: MANAGER + Banner + ADD -> ALLOW */
    public function testP049_ManagerBannerAdd()
    {
        $this->_logInManager();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('banners', null, OA_Permission::OPERATION_ADD),
            'P049: Manager should ALLOW adding banner',
        );
    }

    /** P050: MANAGER + Banner + EDIT + Own -> ALLOW */
    public function testP050_ManagerBannerEditOwn()
    {
        $this->_logInManager();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('banners', $this->bannerId, OA_Permission::OPERATION_EDIT, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P050: Manager should ALLOW editing own banner',
        );
    }

    /** P051: MANAGER + Banner + EDIT + Other agency -> DENY */
    public function testP051_ManagerBannerEditOther()
    {
        $this->_logInManager();
        $this->assertFalse(
            OA_Permission::hasAccessToObject('banners', $this->otherBannerId, OA_Permission::OPERATION_EDIT, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P051: Manager should DENY editing other agency banner',
        );
    }

    /** P052: MANAGER + Publisher + ADD -> ALLOW */
    public function testP052_ManagerPublisherAdd()
    {
        $this->_logInManager();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('affiliates', null, OA_Permission::OPERATION_ADD),
            'P052: Manager should ALLOW adding publisher',
        );
    }

    /** P053: MANAGER + Publisher + EDIT + Own -> ALLOW */
    public function testP053_ManagerPublisherEditOwn()
    {
        $this->_logInManager();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('affiliates', $this->traffickerAffiliateId, OA_Permission::OPERATION_EDIT, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P053: Manager should ALLOW editing own publisher',
        );
    }

    /** P054: MANAGER + Publisher + EDIT + Other -> DENY */
    public function testP054_ManagerPublisherEditOther()
    {
        $this->_logInManager();
        $this->assertFalse(
            OA_Permission::hasAccessToObject('affiliates', $this->otherAffiliateId, OA_Permission::OPERATION_EDIT, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P054: Manager should DENY editing other publisher',
        );
    }

    /** P055: MANAGER + Zone + ADD -> ALLOW */
    public function testP055_ManagerZoneAdd()
    {
        $this->_logInManager();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('zones', null, OA_Permission::OPERATION_ADD),
            'P055: Manager should ALLOW adding zone',
        );
    }

    /** P056: MANAGER + Zone + EDIT + Own -> ALLOW */
    public function testP056_ManagerZoneEditOwn()
    {
        $this->_logInManager();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('zones', $this->zoneId, OA_Permission::OPERATION_EDIT, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P056: Manager should ALLOW editing own zone',
        );
    }

    /** P057: MANAGER + Zone + EDIT + Other agency -> DENY */
    public function testP057_ManagerZoneEditOther()
    {
        $this->_logInManager();
        $this->assertFalse(
            OA_Permission::hasAccessToObject('zones', $this->otherZoneId, OA_Permission::OPERATION_EDIT, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P057: Manager should DENY editing other zone',
        );
    }

    /** P058: MANAGER + Tracker + ADD -> ALLOW */
    public function testP058_ManagerTrackerAdd()
    {
        $this->_logInManager();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('trackers', null, OA_Permission::OPERATION_ADD),
            'P058: Manager should ALLOW adding tracker',
        );
    }

    /** P059: MANAGER + Tracker + EDIT + Own -> ALLOW */
    public function testP059_ManagerTrackerEditOwn()
    {
        $this->_logInManager();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('trackers', $this->trackerId, OA_Permission::OPERATION_EDIT, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P059: Manager should ALLOW editing own tracker',
        );
    }

    /** P060: MANAGER + Channel + ADD -> ALLOW */
    public function testP060_ManagerChannelAdd()
    {
        $this->_logInManager();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('channel', null, OA_Permission::OPERATION_ADD),
            'P060: Manager should ALLOW adding channel',
        );
    }

    /** P061: MANAGER + Channel + EDIT + Own -> ALLOW */
    public function testP061_ManagerChannelEditOwn()
    {
        $this->_logInManager();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('channel', $this->channelId, OA_Permission::OPERATION_EDIT, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P061: Manager should ALLOW editing own channel',
        );
    }

    /** P062: MANAGER + isAccount MANAGER -> true */
    public function testP062_ManagerIsAccountManager()
    {
        $this->_logInManager();
        $this->assertTrue(
            OA_Permission::isAccount(OA_ACCOUNT_MANAGER),
            'P062: Manager should be MANAGER account type',
        );
    }

    /** P063: MANAGER + isAccount ADMIN -> false */
    public function testP063_ManagerIsNotAdmin()
    {
        $this->_logInManager();
        $this->assertFalse(
            OA_Permission::isAccount(OA_ACCOUNT_ADMIN),
            'P063: Manager should not be ADMIN account type',
        );
    }

    /** P064: MANAGER + isAccount ADVERTISER -> false */
    public function testP064_ManagerIsNotAdvertiser()
    {
        $this->_logInManager();
        $this->assertFalse(
            OA_Permission::isAccount(OA_ACCOUNT_ADVERTISER),
            'P064: Manager should not be ADVERTISER account type',
        );
    }

    /** P065: MANAGER + hasPermission SUPER_ACCOUNT with perm -> ALLOW */
    public function testP065_ManagerHasPermSuperAccountWithPerm()
    {
        $this->_logInManager();
        $this->_grantPermission($this->managerAccountId, $this->managerUserId, OA_PERM_SUPER_ACCOUNT);
        $this->assertTrue(
            OA_Permission::hasPermission(OA_PERM_SUPER_ACCOUNT, $this->managerAccountId, $this->managerUserId),
            'P065: Manager with SUPER_ACCOUNT perm should ALLOW',
        );
    }

    /** P066: MANAGER + hasPermission SUPER_ACCOUNT without perm -> DENY */
    public function testP066_ManagerHasPermSuperAccountNoPerm()
    {
        $this->_logInManager();
        $this->_revokeAllPermissions($this->managerAccountId, $this->managerUserId);
        $this->assertFalse(
            OA_Permission::hasPermission(OA_PERM_SUPER_ACCOUNT, $this->managerAccountId, $this->managerUserId),
            'P066: Manager without SUPER_ACCOUNT perm should DENY',
        );
    }

    /** P067: MANAGER + hasPermission MANAGER_DELETE with perm -> ALLOW */
    public function testP067_ManagerHasPermManagerDeleteWithPerm()
    {
        $this->_logInManager();
        $this->_grantPermission($this->managerAccountId, $this->managerUserId, OA_PERM_MANAGER_DELETE);
        $this->assertTrue(
            OA_Permission::hasPermission(OA_PERM_MANAGER_DELETE, $this->managerAccountId, $this->managerUserId),
            'P067: Manager with MANAGER_DELETE perm should ALLOW',
        );
    }

    /** P068: MANAGER + hasPermission MANAGER_DELETE without perm -> DENY */
    public function testP068_ManagerHasPermManagerDeleteNoPerm()
    {
        $this->_logInManager();
        $this->_revokeAllPermissions($this->managerAccountId, $this->managerUserId);
        $this->assertFalse(
            OA_Permission::hasPermission(OA_PERM_MANAGER_DELETE, $this->managerAccountId, $this->managerUserId),
            'P068: Manager without MANAGER_DELETE perm should DENY',
        );
    }

    /** P069: MANAGER + Campaign + VIEW + Own -> ALLOW */
    public function testP069_ManagerCampaignViewOwn()
    {
        $this->_logInManager();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('campaigns', $this->campaignId, OA_Permission::OPERATION_VIEW, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P069: Manager should ALLOW viewing own campaign',
        );
    }

    /** P070: MANAGER + Campaign + DELETE + Own -> ALLOW */
    public function testP070_ManagerCampaignDeleteOwn()
    {
        $this->_logInManager();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('campaigns', $this->campaignId, OA_Permission::OPERATION_DELETE, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P070: Manager should ALLOW deleting own campaign',
        );
    }

    /** P071: MANAGER + Campaign + DELETE + Other -> DENY */
    public function testP071_ManagerCampaignDeleteOther()
    {
        $this->_logInManager();
        $this->assertFalse(
            OA_Permission::hasAccessToObject('campaigns', $this->otherCampaignId, OA_Permission::OPERATION_DELETE, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P071: Manager should DENY deleting other campaign',
        );
    }

    /** P072: MANAGER + Banner + VIEW + Own -> ALLOW */
    public function testP072_ManagerBannerViewOwn()
    {
        $this->_logInManager();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('banners', $this->bannerId, OA_Permission::OPERATION_VIEW, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P072: Manager should ALLOW viewing own banner',
        );
    }

    /** P073: MANAGER + Banner + DELETE + Own -> ALLOW */
    public function testP073_ManagerBannerDeleteOwn()
    {
        $this->_logInManager();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('banners', $this->bannerId, OA_Permission::OPERATION_DELETE, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P073: Manager should ALLOW deleting own banner',
        );
    }

    /** P074: MANAGER + Banner + DELETE + Other -> DENY */
    public function testP074_ManagerBannerDeleteOther()
    {
        $this->_logInManager();
        $this->assertFalse(
            OA_Permission::hasAccessToObject('banners', $this->otherBannerId, OA_Permission::OPERATION_DELETE, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P074: Manager should DENY deleting other banner',
        );
    }

    /** P075: MANAGER + Zone + VIEW + Own -> ALLOW */
    public function testP075_ManagerZoneViewOwn()
    {
        $this->_logInManager();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('zones', $this->zoneId, OA_Permission::OPERATION_VIEW, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P075: Manager should ALLOW viewing own zone',
        );
    }

    /** P076: MANAGER + Zone + DELETE + Own -> ALLOW */
    public function testP076_ManagerZoneDeleteOwn()
    {
        $this->_logInManager();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('zones', $this->zoneId, OA_Permission::OPERATION_DELETE, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P076: Manager should ALLOW deleting own zone',
        );
    }

    /** P077: MANAGER + Zone + DELETE + Other -> DENY */
    public function testP077_ManagerZoneDeleteOther()
    {
        $this->_logInManager();
        $this->assertFalse(
            OA_Permission::hasAccessToObject('zones', $this->otherZoneId, OA_Permission::OPERATION_DELETE, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P077: Manager should DENY deleting other zone',
        );
    }

    /** P078: MANAGER + Campaign + DUPLICATE + Own -> ALLOW */
    public function testP078_ManagerCampaignDuplicateOwn()
    {
        $this->_logInManager();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('campaigns', $this->campaignId, OA_Permission::OPERATION_DUPLICATE, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P078: Manager should ALLOW duplicating own campaign',
        );
    }

    /** P079: MANAGER + Campaign + DUPLICATE + Other -> DENY */
    public function testP079_ManagerCampaignDuplicateOther()
    {
        $this->_logInManager();
        $this->assertFalse(
            OA_Permission::hasAccessToObject('campaigns', $this->otherCampaignId, OA_Permission::OPERATION_DUPLICATE, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P079: Manager should DENY duplicating other campaign',
        );
    }

    /** P080: MANAGER + Advertiser + VIEW + Own -> ALLOW */
    public function testP080_ManagerAdvertiserViewOwn()
    {
        $this->_logInManager();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('clients', $this->advertiserClientId, OA_Permission::OPERATION_VIEW, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P080: Manager should ALLOW viewing own advertiser',
        );
    }

    /** P081: MANAGER + Advertiser + DELETE + Own -> ALLOW */
    public function testP081_ManagerAdvertiserDeleteOwn()
    {
        $this->_logInManager();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('clients', $this->advertiserClientId, OA_Permission::OPERATION_DELETE, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P081: Manager should ALLOW deleting own advertiser',
        );
    }

    /** P082: MANAGER + Advertiser + DELETE + Other -> DENY */
    public function testP082_ManagerAdvertiserDeleteOther()
    {
        $this->_logInManager();
        $this->assertFalse(
            OA_Permission::hasAccessToObject('clients', $this->otherClientId, OA_Permission::OPERATION_DELETE, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P082: Manager should DENY deleting other advertiser',
        );
    }

    /** P083: MANAGER + Publisher + VIEW + Own -> ALLOW */
    public function testP083_ManagerPublisherViewOwn()
    {
        $this->_logInManager();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('affiliates', $this->traffickerAffiliateId, OA_Permission::OPERATION_VIEW, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P083: Manager should ALLOW viewing own publisher',
        );
    }

    /** P084: MANAGER + Publisher + DELETE + Own -> ALLOW */
    public function testP084_ManagerPublisherDeleteOwn()
    {
        $this->_logInManager();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('affiliates', $this->traffickerAffiliateId, OA_Permission::OPERATION_DELETE, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P084: Manager should ALLOW deleting own publisher',
        );
    }

    /** P085: MANAGER + Publisher + DELETE + Other -> DENY */
    public function testP085_ManagerPublisherDeleteOther()
    {
        $this->_logInManager();
        $this->assertFalse(
            OA_Permission::hasAccessToObject('affiliates', $this->otherAffiliateId, OA_Permission::OPERATION_DELETE, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P085: Manager should DENY deleting other publisher',
        );
    }

    /** P086: MANAGER + Banner + DUPLICATE + Own -> ALLOW */
    public function testP086_ManagerBannerDuplicateOwn()
    {
        $this->_logInManager();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('banners', $this->bannerId, OA_Permission::OPERATION_DUPLICATE, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P086: Manager should ALLOW duplicating own banner',
        );
    }

    /** P087: MANAGER + Banner + DUPLICATE + Other -> DENY */
    public function testP087_ManagerBannerDuplicateOther()
    {
        $this->_logInManager();
        $this->assertFalse(
            OA_Permission::hasAccessToObject('banners', $this->otherBannerId, OA_Permission::OPERATION_DUPLICATE, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P087: Manager should DENY duplicating other banner',
        );
    }

    /** P088: MANAGER + Advertiser + VIEW_CHILDREN + Own -> ALLOW */
    public function testP088_ManagerAdvertiserViewChildrenOwn()
    {
        $this->_logInManager();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('clients', $this->advertiserClientId, OA_Permission::OPERATION_VIEW_CHILDREN, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P088: Manager should ALLOW viewing children of own advertiser',
        );
    }

    /** P089: MANAGER + Advertiser + ADD_CHILD + Own -> ALLOW */
    public function testP089_ManagerAdvertiserAddChildOwn()
    {
        $this->_logInManager();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('clients', $this->advertiserClientId, OA_Permission::OPERATION_ADD_CHILD, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P089: Manager should ALLOW adding child to own advertiser',
        );
    }

    /** P090: MANAGER + hasPermission BANNER_EDIT (not related to manager) -> ALLOW */
    public function testP090_ManagerBannerEditPermNotRelated()
    {
        $this->_logInManager();
        $this->assertTrue(
            OA_Permission::hasPermission(OA_PERM_BANNER_EDIT, $this->managerAccountId, $this->managerUserId),
            'P090: Manager should ALLOW BANNER_EDIT (not related to manager type)',
        );
    }

    // ================================================================
    // SECTION 3: ADVERTISER ACCOUNT TESTS (P091-P130)
    // Advertiser access controlled by BANNER_* permissions
    // ================================================================

    /** P091: ADVERTISER + isAccount ADVERTISER -> true */
    public function testP091_AdvertiserIsAccountAdvertiser()
    {
        $this->_logInAdvertiser();
        $this->assertTrue(
            OA_Permission::isAccount(OA_ACCOUNT_ADVERTISER),
            'P091: Advertiser should be ADVERTISER account type',
        );
    }

    /** P092: ADVERTISER + isAccount ADMIN -> false */
    public function testP092_AdvertiserIsNotAdmin()
    {
        $this->_logInAdvertiser();
        $this->assertFalse(
            OA_Permission::isAccount(OA_ACCOUNT_ADMIN),
            'P092: Advertiser should not be ADMIN account type',
        );
    }

    /** P093: ADVERTISER + isAccount MANAGER -> false */
    public function testP093_AdvertiserIsNotManager()
    {
        $this->_logInAdvertiser();
        $this->assertFalse(
            OA_Permission::isAccount(OA_ACCOUNT_MANAGER),
            'P093: Advertiser should not be MANAGER account type',
        );
    }

    /** P094: ADVERTISER + Banner + EDIT + Own + BANNER_EDIT perm -> ALLOW */
    public function testP094_AdvertiserBannerEditOwnWithPerm()
    {
        $this->_logInAdvertiser();
        $this->_grantPermission($this->advertiserAccountId, $this->advertiserUserId, OA_PERM_BANNER_EDIT);
        $this->assertTrue(
            OA_Permission::hasPermission(OA_PERM_BANNER_EDIT, $this->advertiserAccountId, $this->advertiserUserId),
            'P094: Advertiser with BANNER_EDIT perm should ALLOW',
        );
    }

    /** P095: ADVERTISER + Banner + EDIT + Own + no BANNER_EDIT perm -> DENY */
    public function testP095_AdvertiserBannerEditOwnNoPerm()
    {
        $this->_logInAdvertiser();
        $this->_revokeAllPermissions($this->advertiserAccountId, $this->advertiserUserId);
        $this->assertFalse(
            OA_Permission::hasPermission(OA_PERM_BANNER_EDIT, $this->advertiserAccountId, $this->advertiserUserId),
            'P095: Advertiser without BANNER_EDIT perm should DENY',
        );
    }

    /** P096: ADVERTISER + Banner + ADD + Own + BANNER_ADD perm -> ALLOW */
    public function testP096_AdvertiserBannerAddWithPerm()
    {
        $this->_logInAdvertiser();
        $this->_grantPermission($this->advertiserAccountId, $this->advertiserUserId, OA_PERM_BANNER_ADD);
        $this->assertTrue(
            OA_Permission::hasPermission(OA_PERM_BANNER_ADD, $this->advertiserAccountId, $this->advertiserUserId),
            'P096: Advertiser with BANNER_ADD perm should ALLOW',
        );
    }

    /** P097: ADVERTISER + Banner + ADD + Own + no BANNER_ADD perm -> DENY */
    public function testP097_AdvertiserBannerAddNoPerm()
    {
        $this->_logInAdvertiser();
        $this->_revokeAllPermissions($this->advertiserAccountId, $this->advertiserUserId);
        $this->assertFalse(
            OA_Permission::hasPermission(OA_PERM_BANNER_ADD, $this->advertiserAccountId, $this->advertiserUserId),
            'P097: Advertiser without BANNER_ADD perm should DENY',
        );
    }

    /** P098: ADVERTISER + BANNER_ACTIVATE perm + granted -> ALLOW */
    public function testP098_AdvertiserBannerActivateWithPerm()
    {
        $this->_logInAdvertiser();
        $this->_grantPermission($this->advertiserAccountId, $this->advertiserUserId, OA_PERM_BANNER_ACTIVATE);
        $this->assertTrue(
            OA_Permission::hasPermission(OA_PERM_BANNER_ACTIVATE, $this->advertiserAccountId, $this->advertiserUserId),
            'P098: Advertiser with BANNER_ACTIVATE perm should ALLOW',
        );
    }

    /** P099: ADVERTISER + BANNER_ACTIVATE perm + not granted -> DENY */
    public function testP099_AdvertiserBannerActivateNoPerm()
    {
        $this->_logInAdvertiser();
        $this->_revokeAllPermissions($this->advertiserAccountId, $this->advertiserUserId);
        $this->assertFalse(
            OA_Permission::hasPermission(OA_PERM_BANNER_ACTIVATE, $this->advertiserAccountId, $this->advertiserUserId),
            'P099: Advertiser without BANNER_ACTIVATE perm should DENY',
        );
    }

    /** P100: ADVERTISER + BANNER_DEACTIVATE perm + granted -> ALLOW */
    public function testP100_AdvertiserBannerDeactivateWithPerm()
    {
        $this->_logInAdvertiser();
        $this->_grantPermission($this->advertiserAccountId, $this->advertiserUserId, OA_PERM_BANNER_DEACTIVATE);
        $this->assertTrue(
            OA_Permission::hasPermission(OA_PERM_BANNER_DEACTIVATE, $this->advertiserAccountId, $this->advertiserUserId),
            'P100: Advertiser with BANNER_DEACTIVATE perm should ALLOW',
        );
    }

    /** P101: ADVERTISER + BANNER_DEACTIVATE perm + not granted -> DENY */
    public function testP101_AdvertiserBannerDeactivateNoPerm()
    {
        $this->_logInAdvertiser();
        $this->_revokeAllPermissions($this->advertiserAccountId, $this->advertiserUserId);
        $this->assertFalse(
            OA_Permission::hasPermission(OA_PERM_BANNER_DEACTIVATE, $this->advertiserAccountId, $this->advertiserUserId),
            'P101: Advertiser without BANNER_DEACTIVATE perm should DENY',
        );
    }

    /** P102: ADVERTISER + SUPER_ACCOUNT perm + granted -> ALLOW */
    public function testP102_AdvertiserSuperAccountWithPerm()
    {
        $this->_logInAdvertiser();
        $this->_grantPermission($this->advertiserAccountId, $this->advertiserUserId, OA_PERM_SUPER_ACCOUNT);
        $this->assertTrue(
            OA_Permission::hasPermission(OA_PERM_SUPER_ACCOUNT, $this->advertiserAccountId, $this->advertiserUserId),
            'P102: Advertiser with SUPER_ACCOUNT perm should ALLOW',
        );
    }

    /** P103: ADVERTISER + SUPER_ACCOUNT perm + not granted -> DENY */
    public function testP103_AdvertiserSuperAccountNoPerm()
    {
        $this->_logInAdvertiser();
        $this->_revokeAllPermissions($this->advertiserAccountId, $this->advertiserUserId);
        $this->assertFalse(
            OA_Permission::hasPermission(OA_PERM_SUPER_ACCOUNT, $this->advertiserAccountId, $this->advertiserUserId),
            'P103: Advertiser without SUPER_ACCOUNT perm should DENY',
        );
    }

    /** P104: ADVERTISER + USER_LOG_ACCESS perm + granted -> ALLOW */
    public function testP104_AdvertiserUserLogAccessWithPerm()
    {
        $this->_logInAdvertiser();
        $this->_grantPermission($this->advertiserAccountId, $this->advertiserUserId, OA_PERM_USER_LOG_ACCESS);
        $this->assertTrue(
            OA_Permission::hasPermission(OA_PERM_USER_LOG_ACCESS, $this->advertiserAccountId, $this->advertiserUserId),
            'P104: Advertiser with USER_LOG_ACCESS perm should ALLOW',
        );
    }

    /** P105: ADVERTISER + USER_LOG_ACCESS perm + not granted -> DENY */
    public function testP105_AdvertiserUserLogAccessNoPerm()
    {
        $this->_logInAdvertiser();
        $this->_revokeAllPermissions($this->advertiserAccountId, $this->advertiserUserId);
        $this->assertFalse(
            OA_Permission::hasPermission(OA_PERM_USER_LOG_ACCESS, $this->advertiserAccountId, $this->advertiserUserId),
            'P105: Advertiser without USER_LOG_ACCESS perm should DENY',
        );
    }

    /** P106: ADVERTISER + own banner access -> ALLOW */
    public function testP106_AdvertiserOwnBannerAccess()
    {
        $this->_logInAdvertiser();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('banners', $this->bannerId, OA_Permission::OPERATION_ALL, $this->advertiserAccountId, OA_ACCOUNT_ADVERTISER),
            'P106: Advertiser should ALLOW access to own banner',
        );
    }

    /** P107: ADVERTISER + other banner access -> DENY */
    public function testP107_AdvertiserOtherBannerAccess()
    {
        $this->_logInAdvertiser();
        $this->assertFalse(
            OA_Permission::hasAccessToObject('banners', $this->otherBannerId, OA_Permission::OPERATION_ALL, $this->advertiserAccountId, OA_ACCOUNT_ADVERTISER),
            'P107: Advertiser should DENY access to other banner',
        );
    }

    /** P108: ADVERTISER + own campaign access -> ALLOW */
    public function testP108_AdvertiserOwnCampaignAccess()
    {
        $this->_logInAdvertiser();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('campaigns', $this->campaignId, OA_Permission::OPERATION_ALL, $this->advertiserAccountId, OA_ACCOUNT_ADVERTISER),
            'P108: Advertiser should ALLOW access to own campaign',
        );
    }

    /** P109: ADVERTISER + other campaign access -> DENY */
    public function testP109_AdvertiserOtherCampaignAccess()
    {
        $this->_logInAdvertiser();
        $this->assertFalse(
            OA_Permission::hasAccessToObject('campaigns', $this->otherCampaignId, OA_Permission::OPERATION_ALL, $this->advertiserAccountId, OA_ACCOUNT_ADVERTISER),
            'P109: Advertiser should DENY access to other campaign',
        );
    }

    /** P110: ADVERTISER + own tracker access -> ALLOW */
    public function testP110_AdvertiserOwnTrackerAccess()
    {
        $this->_logInAdvertiser();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('trackers', $this->trackerId, OA_Permission::OPERATION_ALL, $this->advertiserAccountId, OA_ACCOUNT_ADVERTISER),
            'P110: Advertiser should ALLOW access to own tracker',
        );
    }

    /** P111: ADVERTISER + new entity (null id) access -> ALLOW */
    public function testP111_AdvertiserNewEntityAccess()
    {
        $this->_logInAdvertiser();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('banners', null, OA_Permission::OPERATION_ADD),
            'P111: Advertiser should ALLOW creating new banner',
        );
    }

    /** P112: ADVERTISER + Campaign + VIEW + Own -> ALLOW */
    public function testP112_AdvertiserCampaignViewOwn()
    {
        $this->_logInAdvertiser();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('campaigns', $this->campaignId, OA_Permission::OPERATION_VIEW, $this->advertiserAccountId, OA_ACCOUNT_ADVERTISER),
            'P112: Advertiser should ALLOW viewing own campaign',
        );
    }

    /** P113: ADVERTISER + Campaign + EDIT + Own -> ALLOW */
    public function testP113_AdvertiserCampaignEditOwn()
    {
        $this->_logInAdvertiser();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('campaigns', $this->campaignId, OA_Permission::OPERATION_EDIT, $this->advertiserAccountId, OA_ACCOUNT_ADVERTISER),
            'P113: Advertiser should ALLOW editing own campaign',
        );
    }

    /** P114: ADVERTISER + Campaign + DELETE + Own -> ALLOW */
    public function testP114_AdvertiserCampaignDeleteOwn()
    {
        $this->_logInAdvertiser();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('campaigns', $this->campaignId, OA_Permission::OPERATION_DELETE, $this->advertiserAccountId, OA_ACCOUNT_ADVERTISER),
            'P114: Advertiser should ALLOW deleting own campaign',
        );
    }

    /** P115: ADVERTISER + Campaign + DUPLICATE + Own -> ALLOW */
    public function testP115_AdvertiserCampaignDuplicateOwn()
    {
        $this->_logInAdvertiser();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('campaigns', $this->campaignId, OA_Permission::OPERATION_DUPLICATE, $this->advertiserAccountId, OA_ACCOUNT_ADVERTISER),
            'P115: Advertiser should ALLOW duplicating own campaign',
        );
    }

    /** P116: ADVERTISER + Banner + VIEW + Own -> ALLOW */
    public function testP116_AdvertiserBannerViewOwn()
    {
        $this->_logInAdvertiser();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('banners', $this->bannerId, OA_Permission::OPERATION_VIEW, $this->advertiserAccountId, OA_ACCOUNT_ADVERTISER),
            'P116: Advertiser should ALLOW viewing own banner',
        );
    }

    /** P117: ADVERTISER + Banner + EDIT + Own -> ALLOW */
    public function testP117_AdvertiserBannerEditOwn()
    {
        $this->_logInAdvertiser();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('banners', $this->bannerId, OA_Permission::OPERATION_EDIT, $this->advertiserAccountId, OA_ACCOUNT_ADVERTISER),
            'P117: Advertiser should ALLOW editing own banner',
        );
    }

    /** P118: ADVERTISER + Banner + DELETE + Own -> ALLOW */
    public function testP118_AdvertiserBannerDeleteOwn()
    {
        $this->_logInAdvertiser();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('banners', $this->bannerId, OA_Permission::OPERATION_DELETE, $this->advertiserAccountId, OA_ACCOUNT_ADVERTISER),
            'P118: Advertiser should ALLOW deleting own banner',
        );
    }

    /** P119: ADVERTISER + Banner + DUPLICATE + Own -> ALLOW */
    public function testP119_AdvertiserBannerDuplicateOwn()
    {
        $this->_logInAdvertiser();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('banners', $this->bannerId, OA_Permission::OPERATION_DUPLICATE, $this->advertiserAccountId, OA_ACCOUNT_ADVERTISER),
            'P119: Advertiser should ALLOW duplicating own banner',
        );
    }

    /** P120: ADVERTISER + Campaign + VIEW_CHILDREN + Own -> ALLOW */
    public function testP120_AdvertiserCampaignViewChildrenOwn()
    {
        $this->_logInAdvertiser();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('campaigns', $this->campaignId, OA_Permission::OPERATION_VIEW_CHILDREN, $this->advertiserAccountId, OA_ACCOUNT_ADVERTISER),
            'P120: Advertiser should ALLOW viewing own campaign children',
        );
    }

    /** P121: ADVERTISER + Campaign + VIEW + Other -> DENY */
    public function testP121_AdvertiserCampaignViewOther()
    {
        $this->_logInAdvertiser();
        $this->assertFalse(
            OA_Permission::hasAccessToObject('campaigns', $this->otherCampaignId, OA_Permission::OPERATION_VIEW, $this->advertiserAccountId, OA_ACCOUNT_ADVERTISER),
            'P121: Advertiser should DENY viewing other campaign',
        );
    }

    /** P122: ADVERTISER + Banner + VIEW + Other -> DENY */
    public function testP122_AdvertiserBannerViewOther()
    {
        $this->_logInAdvertiser();
        $this->assertFalse(
            OA_Permission::hasAccessToObject('banners', $this->otherBannerId, OA_Permission::OPERATION_VIEW, $this->advertiserAccountId, OA_ACCOUNT_ADVERTISER),
            'P122: Advertiser should DENY viewing other banner',
        );
    }

    /** P123: ADVERTISER + checkAccountPermission BANNER_EDIT with perm -> ALLOW */
    public function testP123_AdvertiserCheckAccountPermBannerEditWithPerm()
    {
        $this->_logInAdvertiser();
        $this->_grantPermission($this->advertiserAccountId, $this->advertiserUserId, OA_PERM_BANNER_EDIT);
        $this->assertTrue(
            OA_Permission::checkAccountPermission(OA_ACCOUNT_ADVERTISER, OA_PERM_BANNER_EDIT),
            'P123: Advertiser with BANNER_EDIT should pass checkAccountPermission',
        );
    }

    /** P124: ADVERTISER + checkAccountPermission BANNER_EDIT without perm -> DENY */
    public function testP124_AdvertiserCheckAccountPermBannerEditNoPerm()
    {
        $this->_logInAdvertiser();
        $this->_revokeAllPermissions($this->advertiserAccountId, $this->advertiserUserId);
        $this->assertFalse(
            OA_Permission::checkAccountPermission(OA_ACCOUNT_ADVERTISER, OA_PERM_BANNER_EDIT),
            'P124: Advertiser without BANNER_EDIT should fail checkAccountPermission',
        );
    }

    /** P125: ADVERTISER + zone access -> DENY (zones belong to trafficker hierarchy) */
    public function testP125_AdvertiserZoneAccessDeny()
    {
        $this->_logInAdvertiser();
        $this->assertFalse(
            OA_Permission::hasAccessToObject('zones', $this->zoneId, OA_Permission::OPERATION_ALL, $this->advertiserAccountId, OA_ACCOUNT_ADVERTISER),
            'P125: Advertiser should DENY access to zone',
        );
    }

    /** P126: ADVERTISER + ZONE_ADD perm -> ALLOW (not related to advertiser type) */
    public function testP126_AdvertiserZoneAddPermNotRelated()
    {
        $this->_logInAdvertiser();
        $this->assertTrue(
            OA_Permission::hasPermission(OA_PERM_ZONE_ADD, $this->advertiserAccountId, $this->advertiserUserId),
            'P126: Advertiser gets ZONE_ADD allowed (not related to advertiser type)',
        );
    }

    /** P127: ADVERTISER + Tracker + VIEW + Own -> ALLOW */
    public function testP127_AdvertiserTrackerViewOwn()
    {
        $this->_logInAdvertiser();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('trackers', $this->trackerId, OA_Permission::OPERATION_VIEW, $this->advertiserAccountId, OA_ACCOUNT_ADVERTISER),
            'P127: Advertiser should ALLOW viewing own tracker',
        );
    }

    /** P128: ADVERTISER + Tracker + EDIT + Own -> ALLOW */
    public function testP128_AdvertiserTrackerEditOwn()
    {
        $this->_logInAdvertiser();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('trackers', $this->trackerId, OA_Permission::OPERATION_EDIT, $this->advertiserAccountId, OA_ACCOUNT_ADVERTISER),
            'P128: Advertiser should ALLOW editing own tracker',
        );
    }

    /** P129: ADVERTISER + Tracker + DELETE + Own -> ALLOW */
    public function testP129_AdvertiserTrackerDeleteOwn()
    {
        $this->_logInAdvertiser();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('trackers', $this->trackerId, OA_Permission::OPERATION_DELETE, $this->advertiserAccountId, OA_ACCOUNT_ADVERTISER),
            'P129: Advertiser should ALLOW deleting own tracker',
        );
    }

    /** P130: ADVERTISER + Campaign + ADD_CHILD + Own -> ALLOW */
    public function testP130_AdvertiserCampaignAddChildOwn()
    {
        $this->_logInAdvertiser();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('campaigns', $this->campaignId, OA_Permission::OPERATION_ADD_CHILD, $this->advertiserAccountId, OA_ACCOUNT_ADVERTISER),
            'P130: Advertiser should ALLOW adding child to own campaign',
        );
    }

    // ================================================================
    // SECTION 4: TRAFFICKER ACCOUNT TESTS (P131-P170)
    // Trafficker access controlled by ZONE_* permissions
    // ================================================================

    /** P131: TRAFFICKER + isAccount TRAFFICKER -> true */
    public function testP131_TraffickerIsAccountTrafficker()
    {
        $this->_logInTrafficker();
        $this->assertTrue(
            OA_Permission::isAccount(OA_ACCOUNT_TRAFFICKER),
            'P131: Trafficker should be TRAFFICKER account type',
        );
    }

    /** P132: TRAFFICKER + isAccount ADMIN -> false */
    public function testP132_TraffickerIsNotAdmin()
    {
        $this->_logInTrafficker();
        $this->assertFalse(
            OA_Permission::isAccount(OA_ACCOUNT_ADMIN),
            'P132: Trafficker should not be ADMIN account type',
        );
    }

    /** P133: TRAFFICKER + isAccount MANAGER -> false */
    public function testP133_TraffickerIsNotManager()
    {
        $this->_logInTrafficker();
        $this->assertFalse(
            OA_Permission::isAccount(OA_ACCOUNT_MANAGER),
            'P133: Trafficker should not be MANAGER account type',
        );
    }

    /** P134: TRAFFICKER + isAccount ADVERTISER -> false */
    public function testP134_TraffickerIsNotAdvertiser()
    {
        $this->_logInTrafficker();
        $this->assertFalse(
            OA_Permission::isAccount(OA_ACCOUNT_ADVERTISER),
            'P134: Trafficker should not be ADVERTISER account type',
        );
    }

    /** P135: TRAFFICKER + Zone + ADD + Own + ZONE_ADD perm -> ALLOW */
    public function testP135_TraffickerZoneAddWithPerm()
    {
        $this->_logInTrafficker();
        $this->_grantPermission($this->traffickerAccountId, $this->traffickerUserId, OA_PERM_ZONE_ADD);
        $this->assertTrue(
            OA_Permission::hasPermission(OA_PERM_ZONE_ADD, $this->traffickerAccountId, $this->traffickerUserId),
            'P135: Trafficker with ZONE_ADD perm should ALLOW',
        );
    }

    /** P136: TRAFFICKER + Zone + ADD + Own + no ZONE_ADD perm -> DENY */
    public function testP136_TraffickerZoneAddNoPerm()
    {
        $this->_logInTrafficker();
        $this->_revokeAllPermissions($this->traffickerAccountId, $this->traffickerUserId);
        $this->assertFalse(
            OA_Permission::hasPermission(OA_PERM_ZONE_ADD, $this->traffickerAccountId, $this->traffickerUserId),
            'P136: Trafficker without ZONE_ADD perm should DENY',
        );
    }

    /** P137: TRAFFICKER + Zone + EDIT + Own + ZONE_EDIT perm -> ALLOW */
    public function testP137_TraffickerZoneEditWithPerm()
    {
        $this->_logInTrafficker();
        $this->_grantPermission($this->traffickerAccountId, $this->traffickerUserId, OA_PERM_ZONE_EDIT);
        $this->assertTrue(
            OA_Permission::hasPermission(OA_PERM_ZONE_EDIT, $this->traffickerAccountId, $this->traffickerUserId),
            'P137: Trafficker with ZONE_EDIT perm should ALLOW',
        );
    }

    /** P138: TRAFFICKER + Zone + EDIT + Own + no ZONE_EDIT perm -> DENY */
    public function testP138_TraffickerZoneEditNoPerm()
    {
        $this->_logInTrafficker();
        $this->_revokeAllPermissions($this->traffickerAccountId, $this->traffickerUserId);
        $this->assertFalse(
            OA_Permission::hasPermission(OA_PERM_ZONE_EDIT, $this->traffickerAccountId, $this->traffickerUserId),
            'P138: Trafficker without ZONE_EDIT perm should DENY',
        );
    }

    /** P139: TRAFFICKER + Zone + DELETE + Own + ZONE_DELETE perm -> ALLOW */
    public function testP139_TraffickerZoneDeleteWithPerm()
    {
        $this->_logInTrafficker();
        $this->_grantPermission($this->traffickerAccountId, $this->traffickerUserId, OA_PERM_ZONE_DELETE);
        $this->assertTrue(
            OA_Permission::hasPermission(OA_PERM_ZONE_DELETE, $this->traffickerAccountId, $this->traffickerUserId),
            'P139: Trafficker with ZONE_DELETE perm should ALLOW',
        );
    }

    /** P140: TRAFFICKER + Zone + DELETE + Own + no ZONE_DELETE perm -> DENY */
    public function testP140_TraffickerZoneDeleteNoPerm()
    {
        $this->_logInTrafficker();
        $this->_revokeAllPermissions($this->traffickerAccountId, $this->traffickerUserId);
        $this->assertFalse(
            OA_Permission::hasPermission(OA_PERM_ZONE_DELETE, $this->traffickerAccountId, $this->traffickerUserId),
            'P140: Trafficker without ZONE_DELETE perm should DENY',
        );
    }

    /** P141: TRAFFICKER + ZONE_INVOCATION perm + granted -> ALLOW */
    public function testP141_TraffickerZoneInvocationWithPerm()
    {
        $this->_logInTrafficker();
        $this->_grantPermission($this->traffickerAccountId, $this->traffickerUserId, OA_PERM_ZONE_INVOCATION);
        $this->assertTrue(
            OA_Permission::hasPermission(OA_PERM_ZONE_INVOCATION, $this->traffickerAccountId, $this->traffickerUserId),
            'P141: Trafficker with ZONE_INVOCATION perm should ALLOW',
        );
    }

    /** P142: TRAFFICKER + ZONE_INVOCATION perm + not granted -> DENY */
    public function testP142_TraffickerZoneInvocationNoPerm()
    {
        $this->_logInTrafficker();
        $this->_revokeAllPermissions($this->traffickerAccountId, $this->traffickerUserId);
        $this->assertFalse(
            OA_Permission::hasPermission(OA_PERM_ZONE_INVOCATION, $this->traffickerAccountId, $this->traffickerUserId),
            'P142: Trafficker without ZONE_INVOCATION perm should DENY',
        );
    }

    /** P143: TRAFFICKER + ZONE_LINK perm + granted -> ALLOW */
    public function testP143_TraffickerZoneLinkWithPerm()
    {
        $this->_logInTrafficker();
        $this->_grantPermission($this->traffickerAccountId, $this->traffickerUserId, OA_PERM_ZONE_LINK);
        $this->assertTrue(
            OA_Permission::hasPermission(OA_PERM_ZONE_LINK, $this->traffickerAccountId, $this->traffickerUserId),
            'P143: Trafficker with ZONE_LINK perm should ALLOW',
        );
    }

    /** P144: TRAFFICKER + ZONE_LINK perm + not granted -> DENY */
    public function testP144_TraffickerZoneLinkNoPerm()
    {
        $this->_logInTrafficker();
        $this->_revokeAllPermissions($this->traffickerAccountId, $this->traffickerUserId);
        $this->assertFalse(
            OA_Permission::hasPermission(OA_PERM_ZONE_LINK, $this->traffickerAccountId, $this->traffickerUserId),
            'P144: Trafficker without ZONE_LINK perm should DENY',
        );
    }

    /** P145: TRAFFICKER + SUPER_ACCOUNT perm + granted -> ALLOW */
    public function testP145_TraffickerSuperAccountWithPerm()
    {
        $this->_logInTrafficker();
        $this->_grantPermission($this->traffickerAccountId, $this->traffickerUserId, OA_PERM_SUPER_ACCOUNT);
        $this->assertTrue(
            OA_Permission::hasPermission(OA_PERM_SUPER_ACCOUNT, $this->traffickerAccountId, $this->traffickerUserId),
            'P145: Trafficker with SUPER_ACCOUNT perm should ALLOW',
        );
    }

    /** P146: TRAFFICKER + SUPER_ACCOUNT perm + not granted -> DENY */
    public function testP146_TraffickerSuperAccountNoPerm()
    {
        $this->_logInTrafficker();
        $this->_revokeAllPermissions($this->traffickerAccountId, $this->traffickerUserId);
        $this->assertFalse(
            OA_Permission::hasPermission(OA_PERM_SUPER_ACCOUNT, $this->traffickerAccountId, $this->traffickerUserId),
            'P146: Trafficker without SUPER_ACCOUNT perm should DENY',
        );
    }

    /** P147: TRAFFICKER + USER_LOG_ACCESS perm + granted -> ALLOW */
    public function testP147_TraffickerUserLogAccessWithPerm()
    {
        $this->_logInTrafficker();
        $this->_grantPermission($this->traffickerAccountId, $this->traffickerUserId, OA_PERM_USER_LOG_ACCESS);
        $this->assertTrue(
            OA_Permission::hasPermission(OA_PERM_USER_LOG_ACCESS, $this->traffickerAccountId, $this->traffickerUserId),
            'P147: Trafficker with USER_LOG_ACCESS perm should ALLOW',
        );
    }

    /** P148: TRAFFICKER + USER_LOG_ACCESS perm + not granted -> DENY */
    public function testP148_TraffickerUserLogAccessNoPerm()
    {
        $this->_logInTrafficker();
        $this->_revokeAllPermissions($this->traffickerAccountId, $this->traffickerUserId);
        $this->assertFalse(
            OA_Permission::hasPermission(OA_PERM_USER_LOG_ACCESS, $this->traffickerAccountId, $this->traffickerUserId),
            'P148: Trafficker without USER_LOG_ACCESS perm should DENY',
        );
    }

    /** P149: TRAFFICKER + own zone access -> ALLOW */
    public function testP149_TraffickerOwnZoneAccess()
    {
        $this->_logInTrafficker();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('zones', $this->zoneId, OA_Permission::OPERATION_ALL, $this->traffickerAccountId, OA_ACCOUNT_TRAFFICKER),
            'P149: Trafficker should ALLOW access to own zone',
        );
    }

    /** P150: TRAFFICKER + other zone access -> DENY */
    public function testP150_TraffickerOtherZoneAccess()
    {
        $this->_logInTrafficker();
        $this->assertFalse(
            OA_Permission::hasAccessToObject('zones', $this->otherZoneId, OA_Permission::OPERATION_ALL, $this->traffickerAccountId, OA_ACCOUNT_TRAFFICKER),
            'P150: Trafficker should DENY access to other zone',
        );
    }

    /** P151: TRAFFICKER + own zone + VIEW -> ALLOW */
    public function testP151_TraffickerOwnZoneView()
    {
        $this->_logInTrafficker();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('zones', $this->zoneId, OA_Permission::OPERATION_VIEW, $this->traffickerAccountId, OA_ACCOUNT_TRAFFICKER),
            'P151: Trafficker should ALLOW viewing own zone',
        );
    }

    /** P152: TRAFFICKER + own zone + EDIT -> ALLOW */
    public function testP152_TraffickerOwnZoneEdit()
    {
        $this->_logInTrafficker();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('zones', $this->zoneId, OA_Permission::OPERATION_EDIT, $this->traffickerAccountId, OA_ACCOUNT_TRAFFICKER),
            'P152: Trafficker should ALLOW editing own zone',
        );
    }

    /** P153: TRAFFICKER + own zone + DELETE -> ALLOW */
    public function testP153_TraffickerOwnZoneDelete()
    {
        $this->_logInTrafficker();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('zones', $this->zoneId, OA_Permission::OPERATION_DELETE, $this->traffickerAccountId, OA_ACCOUNT_TRAFFICKER),
            'P153: Trafficker should ALLOW deleting own zone',
        );
    }

    /** P154: TRAFFICKER + other zone + VIEW -> DENY */
    public function testP154_TraffickerOtherZoneView()
    {
        $this->_logInTrafficker();
        $this->assertFalse(
            OA_Permission::hasAccessToObject('zones', $this->otherZoneId, OA_Permission::OPERATION_VIEW, $this->traffickerAccountId, OA_ACCOUNT_TRAFFICKER),
            'P154: Trafficker should DENY viewing other zone',
        );
    }

    /** P155: TRAFFICKER + other zone + EDIT -> DENY */
    public function testP155_TraffickerOtherZoneEdit()
    {
        $this->_logInTrafficker();
        $this->assertFalse(
            OA_Permission::hasAccessToObject('zones', $this->otherZoneId, OA_Permission::OPERATION_EDIT, $this->traffickerAccountId, OA_ACCOUNT_TRAFFICKER),
            'P155: Trafficker should DENY editing other zone',
        );
    }

    /** P156: TRAFFICKER + other zone + DELETE -> DENY */
    public function testP156_TraffickerOtherZoneDelete()
    {
        $this->_logInTrafficker();
        $this->assertFalse(
            OA_Permission::hasAccessToObject('zones', $this->otherZoneId, OA_Permission::OPERATION_DELETE, $this->traffickerAccountId, OA_ACCOUNT_TRAFFICKER),
            'P156: Trafficker should DENY deleting other zone',
        );
    }

    /** P157: TRAFFICKER + new zone (null id) -> ALLOW */
    public function testP157_TraffickerNewZoneAccess()
    {
        $this->_logInTrafficker();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('zones', null, OA_Permission::OPERATION_ADD),
            'P157: Trafficker should ALLOW creating new zone',
        );
    }

    /** P158: TRAFFICKER + own publisher access -> ALLOW */
    public function testP158_TraffickerOwnPublisherAccess()
    {
        $this->_logInTrafficker();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('affiliates', $this->traffickerAffiliateId, OA_Permission::OPERATION_ALL, $this->traffickerAccountId, OA_ACCOUNT_TRAFFICKER),
            'P158: Trafficker should ALLOW access to own publisher',
        );
    }

    /** P159: TRAFFICKER + other publisher access -> DENY */
    public function testP159_TraffickerOtherPublisherAccess()
    {
        $this->_logInTrafficker();
        $this->assertFalse(
            OA_Permission::hasAccessToObject('affiliates', $this->otherAffiliateId, OA_Permission::OPERATION_ALL, $this->traffickerAccountId, OA_ACCOUNT_TRAFFICKER),
            'P159: Trafficker should DENY access to other publisher',
        );
    }

    /** P160: TRAFFICKER + banner access -> DENY (banners belong to advertiser hierarchy) */
    public function testP160_TraffickerBannerAccessDeny()
    {
        $this->_logInTrafficker();
        $this->assertFalse(
            OA_Permission::hasAccessToObject('banners', $this->bannerId, OA_Permission::OPERATION_ALL, $this->traffickerAccountId, OA_ACCOUNT_TRAFFICKER),
            'P160: Trafficker should DENY access to banner',
        );
    }

    /** P161: TRAFFICKER + campaign access -> DENY */
    public function testP161_TraffickerCampaignAccessDeny()
    {
        $this->_logInTrafficker();
        $this->assertFalse(
            OA_Permission::hasAccessToObject('campaigns', $this->campaignId, OA_Permission::OPERATION_ALL, $this->traffickerAccountId, OA_ACCOUNT_TRAFFICKER),
            'P161: Trafficker should DENY access to campaign',
        );
    }

    /** P162: TRAFFICKER + BANNER_EDIT perm -> ALLOW (not related to trafficker type) */
    public function testP162_TraffickerBannerEditPermNotRelated()
    {
        $this->_logInTrafficker();
        $this->assertTrue(
            OA_Permission::hasPermission(OA_PERM_BANNER_EDIT, $this->traffickerAccountId, $this->traffickerUserId),
            'P162: Trafficker gets BANNER_EDIT allowed (not related to trafficker type)',
        );
    }

    /** P163: TRAFFICKER + own publisher + VIEW -> ALLOW */
    public function testP163_TraffickerOwnPublisherView()
    {
        $this->_logInTrafficker();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('affiliates', $this->traffickerAffiliateId, OA_Permission::OPERATION_VIEW, $this->traffickerAccountId, OA_ACCOUNT_TRAFFICKER),
            'P163: Trafficker should ALLOW viewing own publisher',
        );
    }

    /** P164: TRAFFICKER + own publisher + EDIT -> ALLOW */
    public function testP164_TraffickerOwnPublisherEdit()
    {
        $this->_logInTrafficker();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('affiliates', $this->traffickerAffiliateId, OA_Permission::OPERATION_EDIT, $this->traffickerAccountId, OA_ACCOUNT_TRAFFICKER),
            'P164: Trafficker should ALLOW editing own publisher',
        );
    }

    /** P165: TRAFFICKER + own zone + DUPLICATE -> ALLOW */
    public function testP165_TraffickerOwnZoneDuplicate()
    {
        $this->_logInTrafficker();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('zones', $this->zoneId, OA_Permission::OPERATION_DUPLICATE, $this->traffickerAccountId, OA_ACCOUNT_TRAFFICKER),
            'P165: Trafficker should ALLOW duplicating own zone',
        );
    }

    /** P166: TRAFFICKER + other zone + DUPLICATE -> DENY */
    public function testP166_TraffickerOtherZoneDuplicate()
    {
        $this->_logInTrafficker();
        $this->assertFalse(
            OA_Permission::hasAccessToObject('zones', $this->otherZoneId, OA_Permission::OPERATION_DUPLICATE, $this->traffickerAccountId, OA_ACCOUNT_TRAFFICKER),
            'P166: Trafficker should DENY duplicating other zone',
        );
    }

    /** P167: TRAFFICKER + own zone + VIEW_CHILDREN -> ALLOW */
    public function testP167_TraffickerOwnZoneViewChildren()
    {
        $this->_logInTrafficker();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('zones', $this->zoneId, OA_Permission::OPERATION_VIEW_CHILDREN, $this->traffickerAccountId, OA_ACCOUNT_TRAFFICKER),
            'P167: Trafficker should ALLOW viewing own zone children',
        );
    }

    /** P168: TRAFFICKER + own publisher + ADD_CHILD -> ALLOW */
    public function testP168_TraffickerOwnPublisherAddChild()
    {
        $this->_logInTrafficker();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('affiliates', $this->traffickerAffiliateId, OA_Permission::OPERATION_ADD_CHILD, $this->traffickerAccountId, OA_ACCOUNT_TRAFFICKER),
            'P168: Trafficker should ALLOW adding child to own publisher',
        );
    }

    /** P169: TRAFFICKER + checkAccountPermission ZONE_ADD with perm -> ALLOW */
    public function testP169_TraffickerCheckAccountPermZoneAddWithPerm()
    {
        $this->_logInTrafficker();
        $this->_grantPermission($this->traffickerAccountId, $this->traffickerUserId, OA_PERM_ZONE_ADD);
        $this->assertTrue(
            OA_Permission::checkAccountPermission(OA_ACCOUNT_TRAFFICKER, OA_PERM_ZONE_ADD),
            'P169: Trafficker with ZONE_ADD should pass checkAccountPermission',
        );
    }

    /** P170: TRAFFICKER + checkAccountPermission ZONE_ADD without perm -> DENY */
    public function testP170_TraffickerCheckAccountPermZoneAddNoPerm()
    {
        $this->_logInTrafficker();
        $this->_revokeAllPermissions($this->traffickerAccountId, $this->traffickerUserId);
        $this->assertFalse(
            OA_Permission::checkAccountPermission(OA_ACCOUNT_TRAFFICKER, OA_PERM_ZONE_ADD),
            'P170: Trafficker without ZONE_ADD should fail checkAccountPermission',
        );
    }

    // ================================================================
    // SECTION 5: CROSS-ACCOUNT AND EDGE CASE TESTS (P171-P210)
    // ================================================================

    /** P171: MANAGER + hasAccess to own account -> ALLOW */
    public function testP171_ManagerHasAccessOwnAccount()
    {
        $this->_logInManager();
        $this->assertTrue(
            OA_Permission::hasAccess($this->managerAccountId, $this->managerUserId),
            'P171: Manager should have access to own account',
        );
    }

    /** P172: MANAGER + hasAccess to other account -> DENY */
    public function testP172_ManagerHasAccessOtherAccount()
    {
        $this->_logInManager();
        $this->assertFalse(
            OA_Permission::hasAccess($this->manager2AccountId, $this->managerUserId),
            'P172: Manager should not have access to other account',
        );
    }

    /** P173: ADMIN + hasAccess to manager account -> ALLOW */
    public function testP173_AdminHasAccessManagerAccount()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasAccess($this->managerAccountId, $this->adminUserId),
            'P173: Admin should have access to any account',
        );
    }

    /** P174: ADMIN + hasAccess to advertiser account -> ALLOW */
    public function testP174_AdminHasAccessAdvertiserAccount()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasAccess($this->advertiserAccountId, $this->adminUserId),
            'P174: Admin should have access to advertiser account',
        );
    }

    /** P175: ADMIN + hasAccess to trafficker account -> ALLOW */
    public function testP175_AdminHasAccessTraffickerAccount()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasAccess($this->traffickerAccountId, $this->adminUserId),
            'P175: Admin should have access to trafficker account',
        );
    }

    /** P176: ADVERTISER + hasAccess to own account -> ALLOW */
    public function testP176_AdvertiserHasAccessOwnAccount()
    {
        $this->_logInAdvertiser();
        $this->assertTrue(
            OA_Permission::hasAccess($this->advertiserAccountId, $this->advertiserUserId),
            'P176: Advertiser should have access to own account',
        );
    }

    /** P177: ADVERTISER + hasAccess to manager account -> DENY */
    public function testP177_AdvertiserHasAccessManagerAccount()
    {
        $this->_logInAdvertiser();
        $this->assertFalse(
            OA_Permission::hasAccess($this->managerAccountId, $this->advertiserUserId),
            'P177: Advertiser should not have access to manager account',
        );
    }

    /** P178: TRAFFICKER + hasAccess to own account -> ALLOW */
    public function testP178_TraffickerHasAccessOwnAccount()
    {
        $this->_logInTrafficker();
        $this->assertTrue(
            OA_Permission::hasAccess($this->traffickerAccountId, $this->traffickerUserId),
            'P178: Trafficker should have access to own account',
        );
    }

    /** P179: TRAFFICKER + hasAccess to advertiser account -> DENY */
    public function testP179_TraffickerHasAccessAdvertiserAccount()
    {
        $this->_logInTrafficker();
        $this->assertFalse(
            OA_Permission::hasAccess($this->advertiserAccountId, $this->traffickerUserId),
            'P179: Trafficker should not have access to advertiser account',
        );
    }

    /** P180: isPermissionRelatedToAccountType - BANNER_EDIT + ADVERTISER -> true */
    public function testP180_BannerEditRelatedToAdvertiser()
    {
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_ADVERTISER, OA_PERM_BANNER_EDIT),
            'P180: BANNER_EDIT should be related to ADVERTISER',
        );
    }

    /** P181: isPermissionRelatedToAccountType - BANNER_EDIT + MANAGER -> false */
    public function testP181_BannerEditNotRelatedToManager()
    {
        $this->assertFalse(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_MANAGER, OA_PERM_BANNER_EDIT),
            'P181: BANNER_EDIT should not be related to MANAGER',
        );
    }

    /** P182: isPermissionRelatedToAccountType - ZONE_ADD + TRAFFICKER -> true */
    public function testP182_ZoneAddRelatedToTrafficker()
    {
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_TRAFFICKER, OA_PERM_ZONE_ADD),
            'P182: ZONE_ADD should be related to TRAFFICKER',
        );
    }

    /** P183: isPermissionRelatedToAccountType - ZONE_ADD + ADVERTISER -> false */
    public function testP183_ZoneAddNotRelatedToAdvertiser()
    {
        $this->assertFalse(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_ADVERTISER, OA_PERM_ZONE_ADD),
            'P183: ZONE_ADD should not be related to ADVERTISER',
        );
    }

    /** P184: isPermissionRelatedToAccountType - SUPER_ACCOUNT + MANAGER -> true */
    public function testP184_SuperAccountRelatedToManager()
    {
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_MANAGER, OA_PERM_SUPER_ACCOUNT),
            'P184: SUPER_ACCOUNT should be related to MANAGER',
        );
    }

    /** P185: isPermissionRelatedToAccountType - SUPER_ACCOUNT + ADVERTISER -> true */
    public function testP185_SuperAccountRelatedToAdvertiser()
    {
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_ADVERTISER, OA_PERM_SUPER_ACCOUNT),
            'P185: SUPER_ACCOUNT should be related to ADVERTISER',
        );
    }

    /** P186: isPermissionRelatedToAccountType - SUPER_ACCOUNT + TRAFFICKER -> true */
    public function testP186_SuperAccountRelatedToTrafficker()
    {
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_TRAFFICKER, OA_PERM_SUPER_ACCOUNT),
            'P186: SUPER_ACCOUNT should be related to TRAFFICKER',
        );
    }

    /** P187: isPermissionRelatedToAccountType - USER_LOG_ACCESS + ADVERTISER -> true */
    public function testP187_UserLogAccessRelatedToAdvertiser()
    {
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_ADVERTISER, OA_PERM_USER_LOG_ACCESS),
            'P187: USER_LOG_ACCESS should be related to ADVERTISER',
        );
    }

    /** P188: isPermissionRelatedToAccountType - USER_LOG_ACCESS + TRAFFICKER -> true */
    public function testP188_UserLogAccessRelatedToTrafficker()
    {
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_TRAFFICKER, OA_PERM_USER_LOG_ACCESS),
            'P188: USER_LOG_ACCESS should be related to TRAFFICKER',
        );
    }

    /** P189: isPermissionRelatedToAccountType - MANAGER_DELETE + MANAGER -> true */
    public function testP189_ManagerDeleteRelatedToManager()
    {
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_MANAGER, OA_PERM_MANAGER_DELETE),
            'P189: MANAGER_DELETE should be related to MANAGER',
        );
    }

    /** P190: isPermissionRelatedToAccountType - ZONE_DELETE + TRAFFICKER -> true */
    public function testP190_ZoneDeleteRelatedToTrafficker()
    {
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_TRAFFICKER, OA_PERM_ZONE_DELETE),
            'P190: ZONE_DELETE should be related to TRAFFICKER',
        );
    }

    /** P191: isPermissionRelatedToAccountType - ZONE_EDIT + TRAFFICKER -> true */
    public function testP191_ZoneEditRelatedToTrafficker()
    {
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_TRAFFICKER, OA_PERM_ZONE_EDIT),
            'P191: ZONE_EDIT should be related to TRAFFICKER',
        );
    }

    /** P192: isPermissionRelatedToAccountType - ZONE_INVOCATION + TRAFFICKER -> true */
    public function testP192_ZoneInvocationRelatedToTrafficker()
    {
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_TRAFFICKER, OA_PERM_ZONE_INVOCATION),
            'P192: ZONE_INVOCATION should be related to TRAFFICKER',
        );
    }

    /** P193: isPermissionRelatedToAccountType - ZONE_LINK + TRAFFICKER -> true */
    public function testP193_ZoneLinkRelatedToTrafficker()
    {
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_TRAFFICKER, OA_PERM_ZONE_LINK),
            'P193: ZONE_LINK should be related to TRAFFICKER',
        );
    }

    /** P194: isPermissionRelatedToAccountType - BANNER_ACTIVATE + ADVERTISER -> true */
    public function testP194_BannerActivateRelatedToAdvertiser()
    {
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_ADVERTISER, OA_PERM_BANNER_ACTIVATE),
            'P194: BANNER_ACTIVATE should be related to ADVERTISER',
        );
    }

    /** P195: isPermissionRelatedToAccountType - BANNER_DEACTIVATE + ADVERTISER -> true */
    public function testP195_BannerDeactivateRelatedToAdvertiser()
    {
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_ADVERTISER, OA_PERM_BANNER_DEACTIVATE),
            'P195: BANNER_DEACTIVATE should be related to ADVERTISER',
        );
    }

    /** P196: isPermissionRelatedToAccountType - BANNER_ADD + ADVERTISER -> true */
    public function testP196_BannerAddRelatedToAdvertiser()
    {
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_ADVERTISER, OA_PERM_BANNER_ADD),
            'P196: BANNER_ADD should be related to ADVERTISER',
        );
    }

    /** P197: MANAGER + other agency advertiser access -> DENY */
    public function testP197_ManagerOtherAdvertiserAccessDeny()
    {
        $this->_logInManager();
        $this->assertFalse(
            OA_Permission::hasAccessToObject('clients', $this->otherClientId, OA_Permission::OPERATION_VIEW, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P197: Manager should DENY viewing advertiser under other agency',
        );
    }

    /** P198: MANAGER2 + own entities -> ALLOW */
    public function testP198_Manager2OwnClientAccess()
    {
        $this->_logInManager2();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('clients', $this->otherClientId, OA_Permission::OPERATION_ALL, $this->manager2AccountId, OA_ACCOUNT_MANAGER),
            'P198: Manager2 should ALLOW access to own advertiser',
        );
    }

    /** P199: MANAGER2 + first manager entities -> DENY */
    public function testP199_Manager2FirstManagerClientDeny()
    {
        $this->_logInManager2();
        $this->assertFalse(
            OA_Permission::hasAccessToObject('clients', $this->advertiserClientId, OA_Permission::OPERATION_ALL, $this->manager2AccountId, OA_ACCOUNT_MANAGER),
            'P199: Manager2 should DENY access to first manager advertiser',
        );
    }

    /** P200: hasAccessToObject with invalid (non-numeric) entity ID -> DENY */
    public function testP200_InvalidEntityId()
    {
        $this->_logInAdmin();
        $this->assertFalse(
            OA_Permission::hasAccessToObject('banners', 'booId', OA_Permission::OPERATION_ALL, $this->adminAccountId, OA_ACCOUNT_ADMIN),
            'P200: Invalid entity ID should return false',
        );
    }

    /** P201: ADMIN + Banner + MOVE -> ALLOW */
    public function testP201_AdminBannerMove()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('banners', $this->bannerId, OA_Permission::OPERATION_MOVE, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P201: Admin should ALLOW moving banner',
        );
    }

    /** P202: MANAGER + Banner + MOVE + Own -> ALLOW */
    public function testP202_ManagerBannerMoveOwn()
    {
        $this->_logInManager();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('banners', $this->bannerId, OA_Permission::OPERATION_MOVE, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P202: Manager should ALLOW moving own banner',
        );
    }

    /** P203: MANAGER + Banner + MOVE + Other -> DENY */
    public function testP203_ManagerBannerMoveOther()
    {
        $this->_logInManager();
        $this->assertFalse(
            OA_Permission::hasAccessToObject('banners', $this->otherBannerId, OA_Permission::OPERATION_MOVE, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P203: Manager should DENY moving other banner',
        );
    }

    /** P204: ADMIN + Zone + MOVE -> ALLOW */
    public function testP204_AdminZoneMove()
    {
        $this->_logInAdmin();
        $this->assertTrue(
            OA_Permission::hasAccessToObject('zones', $this->zoneId, OA_Permission::OPERATION_MOVE, $this->managerAccountId, OA_ACCOUNT_MANAGER),
            'P204: Admin should ALLOW moving zone',
        );
    }

    /** P205: isPermissionRelatedToAccountType - MANAGER_DELETE + ADVERTISER -> false */
    public function testP205_ManagerDeleteNotRelatedToAdvertiser()
    {
        $this->assertFalse(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_ADVERTISER, OA_PERM_MANAGER_DELETE),
            'P205: MANAGER_DELETE should not be related to ADVERTISER',
        );
    }

    /** P206: isPermissionRelatedToAccountType - MANAGER_DELETE + TRAFFICKER -> false */
    public function testP206_ManagerDeleteNotRelatedToTrafficker()
    {
        $this->assertFalse(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_TRAFFICKER, OA_PERM_MANAGER_DELETE),
            'P206: MANAGER_DELETE should not be related to TRAFFICKER',
        );
    }

    /** P207: isPermissionRelatedToAccountType - BANNER_ADD + MANAGER -> false */
    public function testP207_BannerAddNotRelatedToManager()
    {
        $this->assertFalse(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_MANAGER, OA_PERM_BANNER_ADD),
            'P207: BANNER_ADD should not be related to MANAGER',
        );
    }

    /** P208: isPermissionRelatedToAccountType - ZONE_DELETE + MANAGER -> false */
    public function testP208_ZoneDeleteNotRelatedToManager()
    {
        $this->assertFalse(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_MANAGER, OA_PERM_ZONE_DELETE),
            'P208: ZONE_DELETE should not be related to MANAGER',
        );
    }

    /** P209: isPermissionRelatedToAccountType - ZONE_EDIT + ADVERTISER -> false */
    public function testP209_ZoneEditNotRelatedToAdvertiser()
    {
        $this->assertFalse(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_ADVERTISER, OA_PERM_ZONE_EDIT),
            'P209: ZONE_EDIT should not be related to ADVERTISER',
        );
    }

    /** P210: isPermissionRelatedToAccountType - USER_LOG_ACCESS + MANAGER -> false */
    public function testP210_UserLogAccessNotRelatedToManager()
    {
        $this->assertFalse(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_MANAGER, OA_PERM_USER_LOG_ACCESS),
            'P210: USER_LOG_ACCESS should not be related to MANAGER',
        );
    }
}
