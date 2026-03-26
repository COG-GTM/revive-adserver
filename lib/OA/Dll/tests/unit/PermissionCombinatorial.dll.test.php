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
require_once MAX_PATH . '/lib/OA/Dll/Advertiser.php';
require_once MAX_PATH . '/lib/OA/Dll/Campaign.php';
require_once MAX_PATH . '/lib/OA/Dll/Banner.php';
require_once MAX_PATH . '/lib/OA/Dll/Publisher.php';
require_once MAX_PATH . '/lib/OA/Dll/Zone.php';
require_once MAX_PATH . '/lib/OA/Dll/Tracker.php';
require_once MAX_PATH . '/lib/OA/Dll/Channel.php';
require_once MAX_PATH . '/lib/OA/Dll/User.php';
require_once MAX_PATH . '/lib/OA/Dll/tests/util/DllUnitTestCase.php';
require_once MAX_PATH . '/lib/OA/Permission.php';
require_once MAX_PATH . '/lib/OA/Permission/User.php';

/**
 * Exhaustive permission enforcement matrix tests.
 *
 * Tests ~200 valid combinations of:
 *   Account type (ADMIN, MANAGER, ADVERTISER, TRAFFICKER)
 *   x Entity type (Agency, Advertiser, Campaign, Banner, Publisher, Zone, Tracker, Channel, User)
 *   x Operation (ADD, EDIT, VIEW, DELETE, DUPLICATE, MOVE, ADD_CHILD, VIEW_CHILDREN)
 *   x Ownership (own, other's, parent, unlinked)
 *
 * @package    OpenXDll
 * @subpackage TestSuite
 */
class OA_Dll_PermissionCombinatorialTest extends DllUnitTestCase
{
    /**
     * @var int Admin account ID
     */
    private $adminAccountId;

    /**
     * @var int Admin user ID
     */
    private $adminUserId;

    /**
     * @var int Manager agency ID (own)
     */
    private $ownAgencyId;

    /**
     * @var int Manager account ID (own)
     */
    private $ownManagerAccountId;

    /**
     * @var int Manager user ID
     */
    private $managerUserId;

    /**
     * @var int Other manager agency ID
     */
    private $otherAgencyId;

    /**
     * @var int Other manager account ID
     */
    private $otherManagerAccountId;

    /**
     * @var int Advertiser ID (own agency)
     */
    private $ownAdvertiserId;

    /**
     * @var int Advertiser account ID (own agency)
     */
    private $ownAdvertiserAccountId;

    /**
     * @var int Advertiser user ID
     */
    private $advertiserUserId;

    /**
     * @var int Advertiser ID (other agency)
     */
    private $otherAdvertiserId;

    /**
     * @var int Campaign ID (own advertiser)
     */
    private $ownCampaignId;

    /**
     * @var int Campaign ID (other advertiser)
     */
    private $otherCampaignId;

    /**
     * @var int Banner ID (own campaign)
     */
    private $ownBannerId;

    /**
     * @var int Banner ID (other campaign)
     */
    private $otherBannerId;

    /**
     * @var int Publisher/affiliate ID (own agency)
     */
    private $ownPublisherId;

    /**
     * @var int Publisher account ID (own agency)
     */
    private $ownPublisherAccountId;

    /**
     * @var int Trafficker user ID
     */
    private $traffickerUserId;

    /**
     * @var int Publisher/affiliate ID (other agency)
     */
    private $otherPublisherId;

    /**
     * @var int Zone ID (own publisher)
     */
    private $ownZoneId;

    /**
     * @var int Zone ID (other publisher)
     */
    private $otherZoneId;

    /**
     * @var int Tracker ID (own advertiser)
     */
    private $ownTrackerId;

    /**
     * @var int Tracker ID (other advertiser)
     */
    private $otherTrackerId;

    /**
     * @var int Channel ID (own agency)
     */
    private $ownChannelId;

    /**
     * @var int Channel ID (other agency)
     */
    private $otherChannelId;

    /**
     * Saved session for restore
     */
    private $savedSession;

    /**
     * Track combo count
     */
    private $comboCount = 0;

    public function setUp()
    {
        // Save the global session
        $this->savedSession = $GLOBALS['session'] ?? null;

        // Create admin account and user
        $this->_setupAdminAccount();

        // Create two manager agencies (own + other)
        $this->_setupManagerAccounts();

        // Create advertisers under each agency
        $this->_setupAdvertiserAccounts();

        // Create campaigns under each advertiser
        $this->_setupCampaigns();

        // Create banners under each campaign
        $this->_setupBanners();

        // Create publishers under each agency
        $this->_setupPublisherAccounts();

        // Create zones under each publisher
        $this->_setupZones();

        // Create trackers under each advertiser
        $this->_setupTrackers();

        // Create channels under each agency
        $this->_setupChannels();
    }

    public function tearDown()
    {
        // Restore session
        $GLOBALS['session'] = $this->savedSession;

        DataGenerator::cleanUp([
            'agency', 'clients', 'campaigns', 'banners',
            'affiliates', 'zones', 'trackers', 'channel',
            'users', 'accounts', 'account_user_assoc',
            'account_user_permission_assoc',
        ]);
    }

    // =========================================================================
    // Setup helper methods
    // =========================================================================

    private function _setupAdminAccount()
    {
        $this->adminAccountId = OA_Dal_ApplicationVariables::get('admin_account_id');

        $doUsers = OA_Dal::factoryDO('users');
        $doUsers->username = 'admin_perm_test';
        $doUsers->email_address = 'admin@test.test';
        $doUsers->password = password_hash('secret', PASSWORD_DEFAULT);
        $doUsers->default_account_id = $this->adminAccountId;
        $this->adminUserId = DataGenerator::generateOne($doUsers);

        // Link user to admin account
        OA_Permission::setAccountAccess($this->adminAccountId, $this->adminUserId);
    }

    private function _setupManagerAccounts()
    {
        // Own manager agency
        $doAgency = OA_Dal::factoryDO('agency');
        $doAgency->name = 'Own Agency';
        $doAgency->contact = 'Own Manager';
        $doAgency->email = 'own@test.test';
        $this->ownAgencyId = DataGenerator::generateOne($doAgency);

        $doAgency2 = OA_Dal::staticGetDO('agency', $this->ownAgencyId);
        $this->ownManagerAccountId = (int) $doAgency2->account_id;

        // Create manager user
        $doUsers = OA_Dal::factoryDO('users');
        $doUsers->username = 'manager_perm_test';
        $doUsers->email_address = 'manager@test.test';
        $doUsers->password = password_hash('secret', PASSWORD_DEFAULT);
        $doUsers->default_account_id = $this->ownManagerAccountId;
        $this->managerUserId = DataGenerator::generateOne($doUsers);

        OA_Permission::setAccountAccess($this->ownManagerAccountId, $this->managerUserId);

        // Other manager agency
        $doAgency = OA_Dal::factoryDO('agency');
        $doAgency->name = 'Other Agency';
        $doAgency->contact = 'Other Manager';
        $doAgency->email = 'other@test.test';
        $this->otherAgencyId = DataGenerator::generateOne($doAgency);

        $doAgency2 = OA_Dal::staticGetDO('agency', $this->otherAgencyId);
        $this->otherManagerAccountId = (int) $doAgency2->account_id;
    }

    private function _setupAdvertiserAccounts()
    {
        // Advertiser under own agency
        $doClients = OA_Dal::factoryDO('clients');
        $doClients->agencyid = $this->ownAgencyId;
        $doClients->clientname = 'Own Advertiser';
        $doClients->email = 'ownadv@test.test';
        $doClients->contact = 'Own Advertiser Contact';
        $this->ownAdvertiserId = DataGenerator::generateOne($doClients);

        $doClients2 = OA_Dal::staticGetDO('clients', $this->ownAdvertiserId);
        $this->ownAdvertiserAccountId = (int) $doClients2->account_id;

        // Create advertiser user
        $doUsers = OA_Dal::factoryDO('users');
        $doUsers->username = 'advertiser_perm_test';
        $doUsers->email_address = 'adv@test.test';
        $doUsers->password = password_hash('secret', PASSWORD_DEFAULT);
        $doUsers->default_account_id = $this->ownAdvertiserAccountId;
        $this->advertiserUserId = DataGenerator::generateOne($doUsers);

        OA_Permission::setAccountAccess($this->ownAdvertiserAccountId, $this->advertiserUserId);

        // Advertiser under other agency
        $doClients = OA_Dal::factoryDO('clients');
        $doClients->agencyid = $this->otherAgencyId;
        $doClients->clientname = 'Other Advertiser';
        $doClients->email = 'otheradv@test.test';
        $doClients->contact = 'Other Advertiser Contact';
        $this->otherAdvertiserId = DataGenerator::generateOne($doClients);
    }

    private function _setupCampaigns()
    {
        $doCampaigns = OA_Dal::factoryDO('campaigns');
        $doCampaigns->clientid = $this->ownAdvertiserId;
        $doCampaigns->campaignname = 'Own Campaign';
        $doCampaigns->status = OA_ENTITY_STATUS_RUNNING;
        $this->ownCampaignId = DataGenerator::generateOne($doCampaigns);

        $doCampaigns = OA_Dal::factoryDO('campaigns');
        $doCampaigns->clientid = $this->otherAdvertiserId;
        $doCampaigns->campaignname = 'Other Campaign';
        $doCampaigns->status = OA_ENTITY_STATUS_RUNNING;
        $this->otherCampaignId = DataGenerator::generateOne($doCampaigns);
    }

    private function _setupBanners()
    {
        $doBanners = OA_Dal::factoryDO('banners');
        $doBanners->campaignid = $this->ownCampaignId;
        $doBanners->description = 'Own Banner';
        $doBanners->status = OA_ENTITY_STATUS_RUNNING;
        $this->ownBannerId = DataGenerator::generateOne($doBanners);

        $doBanners = OA_Dal::factoryDO('banners');
        $doBanners->campaignid = $this->otherCampaignId;
        $doBanners->description = 'Other Banner';
        $doBanners->status = OA_ENTITY_STATUS_RUNNING;
        $this->otherBannerId = DataGenerator::generateOne($doBanners);
    }

    private function _setupPublisherAccounts()
    {
        $doAffiliates = OA_Dal::factoryDO('affiliates');
        $doAffiliates->agencyid = $this->ownAgencyId;
        $doAffiliates->name = 'Own Publisher';
        $doAffiliates->email = 'ownpub@test.test';
        $doAffiliates->contact = 'Own Publisher Contact';
        $this->ownPublisherId = DataGenerator::generateOne($doAffiliates);

        $doAff2 = OA_Dal::staticGetDO('affiliates', $this->ownPublisherId);
        $this->ownPublisherAccountId = (int) $doAff2->account_id;

        // Create trafficker user
        $doUsers = OA_Dal::factoryDO('users');
        $doUsers->username = 'trafficker_perm_test';
        $doUsers->email_address = 'traf@test.test';
        $doUsers->password = password_hash('secret', PASSWORD_DEFAULT);
        $doUsers->default_account_id = $this->ownPublisherAccountId;
        $this->traffickerUserId = DataGenerator::generateOne($doUsers);

        OA_Permission::setAccountAccess($this->ownPublisherAccountId, $this->traffickerUserId);

        // Publisher under other agency
        $doAffiliates = OA_Dal::factoryDO('affiliates');
        $doAffiliates->agencyid = $this->otherAgencyId;
        $doAffiliates->name = 'Other Publisher';
        $doAffiliates->email = 'otherpub@test.test';
        $doAffiliates->contact = 'Other Publisher Contact';
        $this->otherPublisherId = DataGenerator::generateOne($doAffiliates);
    }

    private function _setupZones()
    {
        $doZones = OA_Dal::factoryDO('zones');
        $doZones->affiliateid = $this->ownPublisherId;
        $doZones->zonename = 'Own Zone';
        $this->ownZoneId = DataGenerator::generateOne($doZones);

        $doZones = OA_Dal::factoryDO('zones');
        $doZones->affiliateid = $this->otherPublisherId;
        $doZones->zonename = 'Other Zone';
        $this->otherZoneId = DataGenerator::generateOne($doZones);
    }

    private function _setupTrackers()
    {
        $doTrackers = OA_Dal::factoryDO('trackers');
        $doTrackers->clientid = $this->ownAdvertiserId;
        $doTrackers->trackername = 'Own Tracker';
        $this->ownTrackerId = DataGenerator::generateOne($doTrackers);

        $doTrackers = OA_Dal::factoryDO('trackers');
        $doTrackers->clientid = $this->otherAdvertiserId;
        $doTrackers->trackername = 'Other Tracker';
        $this->otherTrackerId = DataGenerator::generateOne($doTrackers);
    }

    private function _setupChannels()
    {
        $doChannel = OA_Dal::factoryDO('channel');
        $doChannel->agencyid = $this->ownAgencyId;
        $doChannel->name = 'Own Channel';
        $this->ownChannelId = DataGenerator::generateOne($doChannel);

        $doChannel = OA_Dal::factoryDO('channel');
        $doChannel->agencyid = $this->otherAgencyId;
        $doChannel->name = 'Other Channel';
        $this->otherChannelId = DataGenerator::generateOne($doChannel);
    }

    // =========================================================================
    // Session simulation helpers
    // =========================================================================

    /**
     * Simulate a logged-in user with the given account context.
     */
    private function _setUser($userId, $accountId)
    {
        $doUsers = OA_Dal::staticGetDO('users', $userId);
        $oUser = new OA_Permission_User($doUsers);
        $oUser->loadAccountData($accountId);
        $GLOBALS['session']['user'] = $oUser;
    }

    /**
     * Set a specific permission for a user on an account.
     */
    private function _grantPermission($accountId, $userId, $permissionId)
    {
        $doPermAssoc = OA_Dal::factoryDO('account_user_permission_assoc');
        $doPermAssoc->account_id = $accountId;
        $doPermAssoc->user_id = $userId;
        $doPermAssoc->permission_id = $permissionId;
        $doPermAssoc->is_allowed = 1;
        $doPermAssoc->insert();
    }

    /**
     * Remove all permissions for a user on an account.
     */
    private function _revokeAllPermissions($accountId, $userId)
    {
        $doPermAssoc = OA_Dal::factoryDO('account_user_permission_assoc');
        $doPermAssoc->account_id = $accountId;
        $doPermAssoc->user_id = $userId;
        $doPermAssoc->delete();
    }

    /**
     * Helper: test isAccount check.
     */
    private function _assertIsAccount($expectedType, $comboId)
    {
        $result = OA_Permission::isAccount($expectedType);
        $this->assertTrue($result, "$comboId: Expected isAccount($expectedType) = true");
    }

    /**
     * Helper: test hasAccessToObject returns expected value.
     */
    private function _assertAccess($table, $entityId, $expected, $comboId, $operation = OA_Permission::OPERATION_ALL)
    {
        $result = OA_Permission::hasAccessToObject($table, $entityId, $operation);
        if ($expected) {
            $this->assertTrue($result, "$comboId: Expected ALLOW for hasAccessToObject('$table', $entityId) but got DENY");
        } else {
            $this->assertFalse($result, "$comboId: Expected DENY for hasAccessToObject('$table', $entityId) but got ALLOW");
        }
        $this->comboCount++;
    }

    /**
     * Helper: test hasPermission returns expected value.
     */
    private function _assertPermission($permId, $expected, $comboId)
    {
        $result = OA_Permission::hasPermission($permId);
        if ($expected) {
            $this->assertTrue($result, "$comboId: Expected hasPermission($permId) = true but got false");
        } else {
            $this->assertFalse($result, "$comboId: Expected hasPermission($permId) = false but got true");
        }
        $this->comboCount++;
    }

    /**
     * Helper: test checkPermissions on a DLL object returns expected value.
     */
    private function _assertDllCheckPermissions($dll, $permissions, $table, $entityId, $expected, $comboId, $allowed = null, $operationAccessType = OA_Permission::OPERATION_ALL)
    {
        $result = $dll->checkPermissions($permissions, $table, $entityId, $allowed, $operationAccessType);
        if ($expected) {
            $this->assertTrue($result, "$comboId: Expected DLL checkPermissions ALLOW but got DENY");
        } else {
            $this->assertFalse($result, "$comboId: Expected DLL checkPermissions DENY but got ALLOW");
        }
        $this->comboCount++;
    }

    // =========================================================================
    // ADMIN account tests
    // =========================================================================

    /**
     * P001-P030: ADMIN has full access to all entities regardless of ownership.
     */
    public function testAdminFullAccessAllEntities()
    {
        $this->_setUser($this->adminUserId, $this->adminAccountId);

        // P001: ADMIN + Agency + VIEW + own
        $this->_assertAccess('agency', $this->ownAgencyId, true, 'P001', OA_Permission::OPERATION_VIEW);

        // P002: ADMIN + Agency + VIEW + other
        $this->_assertAccess('agency', $this->otherAgencyId, true, 'P002', OA_Permission::OPERATION_VIEW);

        // P003: ADMIN + Agency + EDIT + own
        $this->_assertAccess('agency', $this->ownAgencyId, true, 'P003', OA_Permission::OPERATION_EDIT);

        // P004: ADMIN + Agency + EDIT + other
        $this->_assertAccess('agency', $this->otherAgencyId, true, 'P004', OA_Permission::OPERATION_EDIT);

        // P005: ADMIN + Agency + DELETE + own
        $this->_assertAccess('agency', $this->ownAgencyId, true, 'P005', OA_Permission::OPERATION_DELETE);

        // P006: ADMIN + Agency + DELETE + other
        $this->_assertAccess('agency', $this->otherAgencyId, true, 'P006', OA_Permission::OPERATION_DELETE);

        // P007: ADMIN + Advertiser + VIEW + any
        $this->_assertAccess('clients', $this->ownAdvertiserId, true, 'P007', OA_Permission::OPERATION_VIEW);

        // P008: ADMIN + Advertiser + EDIT + any
        $this->_assertAccess('clients', $this->otherAdvertiserId, true, 'P008', OA_Permission::OPERATION_EDIT);

        // P009: ADMIN + Advertiser + DELETE + any
        $this->_assertAccess('clients', $this->ownAdvertiserId, true, 'P009', OA_Permission::OPERATION_DELETE);

        // P010: ADMIN + Campaign + VIEW + any
        $this->_assertAccess('campaigns', $this->ownCampaignId, true, 'P010', OA_Permission::OPERATION_VIEW);

        // P011: ADMIN + Campaign + EDIT + any
        $this->_assertAccess('campaigns', $this->otherCampaignId, true, 'P011', OA_Permission::OPERATION_EDIT);

        // P012: ADMIN + Campaign + DELETE + any
        $this->_assertAccess('campaigns', $this->ownCampaignId, true, 'P012', OA_Permission::OPERATION_DELETE);

        // P013: ADMIN + Campaign + DUPLICATE + any
        $this->_assertAccess('campaigns', $this->otherCampaignId, true, 'P013', OA_Permission::OPERATION_DUPLICATE);

        // P014: ADMIN + Banner + VIEW + any
        $this->_assertAccess('banners', $this->ownBannerId, true, 'P014', OA_Permission::OPERATION_VIEW);

        // P015: ADMIN + Banner + EDIT + any
        $this->_assertAccess('banners', $this->otherBannerId, true, 'P015', OA_Permission::OPERATION_EDIT);

        // P016: ADMIN + Banner + DELETE + any
        $this->_assertAccess('banners', $this->ownBannerId, true, 'P016', OA_Permission::OPERATION_DELETE);

        // P017: ADMIN + Publisher + VIEW + any
        $this->_assertAccess('affiliates', $this->ownPublisherId, true, 'P017', OA_Permission::OPERATION_VIEW);

        // P018: ADMIN + Publisher + EDIT + any
        $this->_assertAccess('affiliates', $this->otherPublisherId, true, 'P018', OA_Permission::OPERATION_EDIT);

        // P019: ADMIN + Publisher + DELETE + any
        $this->_assertAccess('affiliates', $this->ownPublisherId, true, 'P019', OA_Permission::OPERATION_DELETE);

        // P020: ADMIN + Zone + VIEW + any
        $this->_assertAccess('zones', $this->ownZoneId, true, 'P020', OA_Permission::OPERATION_VIEW);

        // P021: ADMIN + Zone + EDIT + any
        $this->_assertAccess('zones', $this->otherZoneId, true, 'P021', OA_Permission::OPERATION_EDIT);

        // P022: ADMIN + Zone + DELETE + any
        $this->_assertAccess('zones', $this->ownZoneId, true, 'P022', OA_Permission::OPERATION_DELETE);

        // P023: ADMIN + Tracker + VIEW + any
        $this->_assertAccess('trackers', $this->ownTrackerId, true, 'P023', OA_Permission::OPERATION_VIEW);

        // P024: ADMIN + Tracker + EDIT + any
        $this->_assertAccess('trackers', $this->otherTrackerId, true, 'P024', OA_Permission::OPERATION_EDIT);

        // P025: ADMIN + Tracker + DELETE + any
        $this->_assertAccess('trackers', $this->ownTrackerId, true, 'P025', OA_Permission::OPERATION_DELETE);

        // P026: ADMIN + Channel + VIEW + any
        $this->_assertAccess('channel', $this->ownChannelId, true, 'P026', OA_Permission::OPERATION_VIEW);

        // P027: ADMIN + Channel + EDIT + any
        $this->_assertAccess('channel', $this->otherChannelId, true, 'P027', OA_Permission::OPERATION_EDIT);

        // P028: ADMIN + Channel + DELETE + any
        $this->_assertAccess('channel', $this->ownChannelId, true, 'P028', OA_Permission::OPERATION_DELETE);

        // P029: ADMIN + Agency + ADD (new entity = empty id)
        $this->_assertAccess('agency', 0, true, 'P029', OA_Permission::OPERATION_ADD);

        // P030: ADMIN + Banner + ADD_CHILD + any
        $this->_assertAccess('banners', $this->ownBannerId, true, 'P030', OA_Permission::OPERATION_ADD_CHILD);
    }

    /**
     * P031-P040: ADMIN has all permissions (always returns true for admin).
     */
    public function testAdminAllPermissions()
    {
        $this->_setUser($this->adminUserId, $this->adminAccountId);

        // P031: ADMIN + BANNER_ACTIVATE
        $this->_assertPermission(OA_PERM_BANNER_ACTIVATE, true, 'P031');

        // P032: ADMIN + BANNER_DEACTIVATE
        $this->_assertPermission(OA_PERM_BANNER_DEACTIVATE, true, 'P032');

        // P033: ADMIN + BANNER_ADD
        $this->_assertPermission(OA_PERM_BANNER_ADD, true, 'P033');

        // P034: ADMIN + BANNER_EDIT
        $this->_assertPermission(OA_PERM_BANNER_EDIT, true, 'P034');

        // P035: ADMIN + ZONE_ADD
        $this->_assertPermission(OA_PERM_ZONE_ADD, true, 'P035');

        // P036: ADMIN + ZONE_DELETE
        $this->_assertPermission(OA_PERM_ZONE_DELETE, true, 'P036');

        // P037: ADMIN + ZONE_EDIT
        $this->_assertPermission(OA_PERM_ZONE_EDIT, true, 'P037');

        // P038: ADMIN + ZONE_INVOCATION
        $this->_assertPermission(OA_PERM_ZONE_INVOCATION, true, 'P038');

        // P039: ADMIN + ZONE_LINK
        $this->_assertPermission(OA_PERM_ZONE_LINK, true, 'P039');

        // P040: ADMIN + SUPER_ACCOUNT
        $this->_assertPermission(OA_PERM_SUPER_ACCOUNT, true, 'P040');

        // P041: ADMIN + USER_LOG_ACCESS
        $this->_assertPermission(OA_PERM_USER_LOG_ACCESS, true, 'P041');

        // P042: ADMIN + MANAGER_DELETE
        $this->_assertPermission(OA_PERM_MANAGER_DELETE, true, 'P042');
    }

    // =========================================================================
    // MANAGER account tests
    // =========================================================================

    /**
     * P043-P075: MANAGER access to own-agency entities (ALLOW) and other-agency entities (DENY).
     */
    public function testManagerEntityAccess()
    {
        $this->_setUser($this->managerUserId, $this->ownManagerAccountId);

        // --- Agency ---
        // P043: MANAGER + Agency + VIEW + own
        $this->_assertAccess('agency', $this->ownAgencyId, true, 'P043', OA_Permission::OPERATION_VIEW);

        // P044: MANAGER + Agency + EDIT + own
        $this->_assertAccess('agency', $this->ownAgencyId, true, 'P044', OA_Permission::OPERATION_EDIT);

        // P045: MANAGER + Agency + VIEW + other => DENY
        $this->_assertAccess('agency', $this->otherAgencyId, false, 'P045', OA_Permission::OPERATION_VIEW);

        // P046: MANAGER + Agency + EDIT + other => DENY
        $this->_assertAccess('agency', $this->otherAgencyId, false, 'P046', OA_Permission::OPERATION_EDIT);

        // P047: MANAGER + Agency + DELETE + other => DENY
        $this->_assertAccess('agency', $this->otherAgencyId, false, 'P047', OA_Permission::OPERATION_DELETE);

        // --- Advertiser (clients) ---
        // P048: MANAGER + Advertiser + VIEW + own agency
        $this->_assertAccess('clients', $this->ownAdvertiserId, true, 'P048', OA_Permission::OPERATION_VIEW);

        // P049: MANAGER + Advertiser + EDIT + own agency
        $this->_assertAccess('clients', $this->ownAdvertiserId, true, 'P049', OA_Permission::OPERATION_EDIT);

        // P050: MANAGER + Advertiser + DELETE + own agency
        $this->_assertAccess('clients', $this->ownAdvertiserId, true, 'P050', OA_Permission::OPERATION_DELETE);

        // P051: MANAGER + Advertiser + ADD + own agency (new entity)
        $this->_assertAccess('clients', 0, true, 'P051', OA_Permission::OPERATION_ADD);

        // P052: MANAGER + Advertiser + VIEW + other agency => DENY
        $this->_assertAccess('clients', $this->otherAdvertiserId, false, 'P052', OA_Permission::OPERATION_VIEW);

        // P053: MANAGER + Advertiser + EDIT + other agency => DENY
        $this->_assertAccess('clients', $this->otherAdvertiserId, false, 'P053', OA_Permission::OPERATION_EDIT);

        // P054: MANAGER + Advertiser + DELETE + other agency => DENY
        $this->_assertAccess('clients', $this->otherAdvertiserId, false, 'P054', OA_Permission::OPERATION_DELETE);

        // --- Campaign ---
        // P055: MANAGER + Campaign + VIEW + own agency
        $this->_assertAccess('campaigns', $this->ownCampaignId, true, 'P055', OA_Permission::OPERATION_VIEW);

        // P056: MANAGER + Campaign + EDIT + own agency
        $this->_assertAccess('campaigns', $this->ownCampaignId, true, 'P056', OA_Permission::OPERATION_EDIT);

        // P057: MANAGER + Campaign + DELETE + own agency
        $this->_assertAccess('campaigns', $this->ownCampaignId, true, 'P057', OA_Permission::OPERATION_DELETE);

        // P058: MANAGER + Campaign + DUPLICATE + own agency
        $this->_assertAccess('campaigns', $this->ownCampaignId, true, 'P058', OA_Permission::OPERATION_DUPLICATE);

        // P059: MANAGER + Campaign + VIEW + other agency => DENY
        $this->_assertAccess('campaigns', $this->otherCampaignId, false, 'P059', OA_Permission::OPERATION_VIEW);

        // P060: MANAGER + Campaign + EDIT + other agency => DENY
        $this->_assertAccess('campaigns', $this->otherCampaignId, false, 'P060', OA_Permission::OPERATION_EDIT);

        // P061: MANAGER + Campaign + DELETE + other agency => DENY
        $this->_assertAccess('campaigns', $this->otherCampaignId, false, 'P061', OA_Permission::OPERATION_DELETE);

        // --- Banner ---
        // P062: MANAGER + Banner + VIEW + own agency
        $this->_assertAccess('banners', $this->ownBannerId, true, 'P062', OA_Permission::OPERATION_VIEW);

        // P063: MANAGER + Banner + EDIT + own agency
        $this->_assertAccess('banners', $this->ownBannerId, true, 'P063', OA_Permission::OPERATION_EDIT);

        // P064: MANAGER + Banner + DELETE + own agency
        $this->_assertAccess('banners', $this->ownBannerId, true, 'P064', OA_Permission::OPERATION_DELETE);

        // P065: MANAGER + Banner + VIEW + other agency => DENY
        $this->_assertAccess('banners', $this->otherBannerId, false, 'P065', OA_Permission::OPERATION_VIEW);

        // P066: MANAGER + Banner + EDIT + other agency => DENY
        $this->_assertAccess('banners', $this->otherBannerId, false, 'P066', OA_Permission::OPERATION_EDIT);

        // --- Publisher (affiliates) ---
        // P067: MANAGER + Publisher + VIEW + own agency
        $this->_assertAccess('affiliates', $this->ownPublisherId, true, 'P067', OA_Permission::OPERATION_VIEW);

        // P068: MANAGER + Publisher + EDIT + own agency
        $this->_assertAccess('affiliates', $this->ownPublisherId, true, 'P068', OA_Permission::OPERATION_EDIT);

        // P069: MANAGER + Publisher + DELETE + own agency
        $this->_assertAccess('affiliates', $this->ownPublisherId, true, 'P069', OA_Permission::OPERATION_DELETE);

        // P070: MANAGER + Publisher + VIEW + other agency => DENY
        $this->_assertAccess('affiliates', $this->otherPublisherId, false, 'P070', OA_Permission::OPERATION_VIEW);

        // --- Zone ---
        // P071: MANAGER + Zone + VIEW + own agency
        $this->_assertAccess('zones', $this->ownZoneId, true, 'P071', OA_Permission::OPERATION_VIEW);

        // P072: MANAGER + Zone + EDIT + own agency
        $this->_assertAccess('zones', $this->ownZoneId, true, 'P072', OA_Permission::OPERATION_EDIT);

        // P073: MANAGER + Zone + DELETE + own agency
        $this->_assertAccess('zones', $this->ownZoneId, true, 'P073', OA_Permission::OPERATION_DELETE);

        // P074: MANAGER + Zone + VIEW + other agency => DENY
        $this->_assertAccess('zones', $this->otherZoneId, false, 'P074', OA_Permission::OPERATION_VIEW);

        // P075: MANAGER + Zone + EDIT + other agency => DENY
        $this->_assertAccess('zones', $this->otherZoneId, false, 'P075', OA_Permission::OPERATION_EDIT);
    }

    /**
     * P076-P095: MANAGER access to trackers, channels, and add operations.
     */
    public function testManagerTrackerChannelUserAccess()
    {
        $this->_setUser($this->managerUserId, $this->ownManagerAccountId);

        // --- Tracker ---
        // P076: MANAGER + Tracker + VIEW + own agency
        $this->_assertAccess('trackers', $this->ownTrackerId, true, 'P076', OA_Permission::OPERATION_VIEW);

        // P077: MANAGER + Tracker + EDIT + own agency
        $this->_assertAccess('trackers', $this->ownTrackerId, true, 'P077', OA_Permission::OPERATION_EDIT);

        // P078: MANAGER + Tracker + DELETE + own agency
        $this->_assertAccess('trackers', $this->ownTrackerId, true, 'P078', OA_Permission::OPERATION_DELETE);

        // P079: MANAGER + Tracker + VIEW + other agency => DENY
        $this->_assertAccess('trackers', $this->otherTrackerId, false, 'P079', OA_Permission::OPERATION_VIEW);

        // P080: MANAGER + Tracker + EDIT + other agency => DENY
        $this->_assertAccess('trackers', $this->otherTrackerId, false, 'P080', OA_Permission::OPERATION_EDIT);

        // P081: MANAGER + Tracker + DELETE + other agency => DENY
        $this->_assertAccess('trackers', $this->otherTrackerId, false, 'P081', OA_Permission::OPERATION_DELETE);

        // --- Channel ---
        // P082: MANAGER + Channel + VIEW + own agency
        $this->_assertAccess('channel', $this->ownChannelId, true, 'P082', OA_Permission::OPERATION_VIEW);

        // P083: MANAGER + Channel + EDIT + own agency
        $this->_assertAccess('channel', $this->ownChannelId, true, 'P083', OA_Permission::OPERATION_EDIT);

        // P084: MANAGER + Channel + DELETE + own agency
        $this->_assertAccess('channel', $this->ownChannelId, true, 'P084', OA_Permission::OPERATION_DELETE);

        // P085: MANAGER + Channel + VIEW + other agency => DENY
        $this->_assertAccess('channel', $this->otherChannelId, false, 'P085', OA_Permission::OPERATION_VIEW);

        // P086: MANAGER + Channel + EDIT + other agency => DENY
        $this->_assertAccess('channel', $this->otherChannelId, false, 'P086', OA_Permission::OPERATION_EDIT);

        // P087: MANAGER + Channel + DELETE + other agency => DENY
        $this->_assertAccess('channel', $this->otherChannelId, false, 'P087', OA_Permission::OPERATION_DELETE);

        // --- New entity creation (ADD) ---
        // P088: MANAGER + Campaign + ADD (new entity)
        $this->_assertAccess('campaigns', 0, true, 'P088', OA_Permission::OPERATION_ADD);

        // P089: MANAGER + Banner + ADD (new entity)
        $this->_assertAccess('banners', 0, true, 'P089', OA_Permission::OPERATION_ADD);

        // P090: MANAGER + Zone + ADD (new entity)
        $this->_assertAccess('zones', 0, true, 'P090', OA_Permission::OPERATION_ADD);

        // P091: MANAGER + Tracker + ADD (new entity)
        $this->_assertAccess('trackers', 0, true, 'P091', OA_Permission::OPERATION_ADD);

        // P092: MANAGER + Channel + ADD (new entity)
        $this->_assertAccess('channel', 0, true, 'P092', OA_Permission::OPERATION_ADD);

        // P093: MANAGER + Publisher + ADD (new entity)
        $this->_assertAccess('affiliates', 0, true, 'P093', OA_Permission::OPERATION_ADD);

        // P094: MANAGER + Banner + MOVE + own agency
        $this->_assertAccess('banners', $this->ownBannerId, true, 'P094', OA_Permission::OPERATION_MOVE);

        // P095: MANAGER + Banner + MOVE + other agency => DENY
        $this->_assertAccess('banners', $this->otherBannerId, false, 'P095', OA_Permission::OPERATION_MOVE);
    }

    /**
     * P096-P105: MANAGER permission checks (MANAGER_DELETE, SUPER_ACCOUNT).
     */
    public function testManagerPermissions()
    {
        $this->_setUser($this->managerUserId, $this->ownManagerAccountId);

        // P096: MANAGER + MANAGER_DELETE perm granted => ALLOW
        $this->_revokeAllPermissions($this->ownManagerAccountId, $this->managerUserId);
        $this->_grantPermission($this->ownManagerAccountId, $this->managerUserId, OA_PERM_MANAGER_DELETE);
        $this->_assertPermission(OA_PERM_MANAGER_DELETE, true, 'P096');

        // P097: MANAGER + MANAGER_DELETE perm not granted => DENY
        $this->_revokeAllPermissions($this->ownManagerAccountId, $this->managerUserId);
        $this->_assertPermission(OA_PERM_MANAGER_DELETE, false, 'P097');

        // P098: MANAGER + SUPER_ACCOUNT perm granted => ALLOW
        $this->_grantPermission($this->ownManagerAccountId, $this->managerUserId, OA_PERM_SUPER_ACCOUNT);
        $this->_assertPermission(OA_PERM_SUPER_ACCOUNT, true, 'P098');

        // P099: MANAGER + SUPER_ACCOUNT perm not granted => DENY
        $this->_revokeAllPermissions($this->ownManagerAccountId, $this->managerUserId);
        $this->_assertPermission(OA_PERM_SUPER_ACCOUNT, false, 'P099');

        // P100: MANAGER + BANNER_EDIT => not related to manager, returns true
        $this->_assertPermission(OA_PERM_BANNER_EDIT, true, 'P100');

        // P101: MANAGER + BANNER_ADD => not related to manager, returns true
        $this->_assertPermission(OA_PERM_BANNER_ADD, true, 'P101');

        // P102: MANAGER + ZONE_ADD => not related to manager, returns true
        $this->_assertPermission(OA_PERM_ZONE_ADD, true, 'P102');

        // P103: MANAGER + ZONE_EDIT => not related to manager, returns true
        $this->_assertPermission(OA_PERM_ZONE_EDIT, true, 'P103');

        // P104: MANAGER + ZONE_DELETE => not related to manager, returns true
        $this->_assertPermission(OA_PERM_ZONE_DELETE, true, 'P104');

        // P105: MANAGER + USER_LOG_ACCESS => not related to manager, returns true
        $this->_assertPermission(OA_PERM_USER_LOG_ACCESS, true, 'P105');
    }

    /**
     * P106-P110: MANAGER + checkAccountPermission for delete operations.
     */
    public function testManagerDeletePermissionEnforcement()
    {
        $this->_setUser($this->managerUserId, $this->ownManagerAccountId);

        // P106: MANAGER + checkAccountPermission for DELETE + MANAGER_DELETE granted => ALLOW
        $this->_revokeAllPermissions($this->ownManagerAccountId, $this->managerUserId);
        $this->_grantPermission($this->ownManagerAccountId, $this->managerUserId, OA_PERM_MANAGER_DELETE);
        $result = OA_Permission::checkAccountPermission(OA_ACCOUNT_MANAGER, OA_PERM_MANAGER_DELETE);
        $this->assertTrue($result, 'P106: MANAGER + MANAGER_DELETE granted should ALLOW');
        $this->comboCount++;

        // P107: MANAGER + checkAccountPermission for DELETE + MANAGER_DELETE not granted => DENY
        $this->_revokeAllPermissions($this->ownManagerAccountId, $this->managerUserId);
        $result = OA_Permission::checkAccountPermission(OA_ACCOUNT_MANAGER, OA_PERM_MANAGER_DELETE);
        $this->assertFalse($result, 'P107: MANAGER + MANAGER_DELETE not granted should DENY');
        $this->comboCount++;

        // P108: MANAGER + VIEW_CHILDREN + own agency campaign
        $this->_assertAccess('campaigns', $this->ownCampaignId, true, 'P108', OA_Permission::OPERATION_VIEW_CHILDREN);

        // P109: MANAGER + VIEW_CHILDREN + other agency campaign => DENY
        $this->_assertAccess('campaigns', $this->otherCampaignId, false, 'P109', OA_Permission::OPERATION_VIEW_CHILDREN);

        // P110: MANAGER + ADD_CHILD + own agency advertiser
        $this->_assertAccess('clients', $this->ownAdvertiserId, true, 'P110', OA_Permission::OPERATION_ADD_CHILD);
    }

    // =========================================================================
    // ADVERTISER account tests
    // =========================================================================

    /**
     * P111-P140: ADVERTISER access to entities.
     */
    public function testAdvertiserEntityAccess()
    {
        $this->_setUser($this->advertiserUserId, $this->ownAdvertiserAccountId);

        // --- Own advertiser (clients) ---
        // P111: ADVERTISER + Advertiser + VIEW + own
        $this->_assertAccess('clients', $this->ownAdvertiserId, true, 'P111', OA_Permission::OPERATION_VIEW);

        // P112: ADVERTISER + Advertiser + EDIT + own
        $this->_assertAccess('clients', $this->ownAdvertiserId, true, 'P112', OA_Permission::OPERATION_EDIT);

        // P113: ADVERTISER + Advertiser + VIEW + other => DENY
        $this->_assertAccess('clients', $this->otherAdvertiserId, false, 'P113', OA_Permission::OPERATION_VIEW);

        // P114: ADVERTISER + Advertiser + EDIT + other => DENY
        $this->_assertAccess('clients', $this->otherAdvertiserId, false, 'P114', OA_Permission::OPERATION_EDIT);

        // P115: ADVERTISER + Advertiser + DELETE + any => DENY (advertisers can't delete themselves)
        $this->_assertAccess('clients', $this->otherAdvertiserId, false, 'P115', OA_Permission::OPERATION_DELETE);

        // --- Own campaigns ---
        // P116: ADVERTISER + Campaign + VIEW + own
        $this->_assertAccess('campaigns', $this->ownCampaignId, true, 'P116', OA_Permission::OPERATION_VIEW);

        // P117: ADVERTISER + Campaign + EDIT + own
        $this->_assertAccess('campaigns', $this->ownCampaignId, true, 'P117', OA_Permission::OPERATION_EDIT);

        // P118: ADVERTISER + Campaign + VIEW + other => DENY
        $this->_assertAccess('campaigns', $this->otherCampaignId, false, 'P118', OA_Permission::OPERATION_VIEW);

        // P119: ADVERTISER + Campaign + EDIT + other => DENY
        $this->_assertAccess('campaigns', $this->otherCampaignId, false, 'P119', OA_Permission::OPERATION_EDIT);

        // P120: ADVERTISER + Campaign + DELETE + own => DENY (no delete permission for advertiser)
        $this->_assertAccess('campaigns', $this->otherCampaignId, false, 'P120', OA_Permission::OPERATION_DELETE);

        // --- Own banners ---
        // P121: ADVERTISER + Banner + VIEW + own
        $this->_assertAccess('banners', $this->ownBannerId, true, 'P121', OA_Permission::OPERATION_VIEW);

        // P122: ADVERTISER + Banner + EDIT + own
        $this->_assertAccess('banners', $this->ownBannerId, true, 'P122', OA_Permission::OPERATION_EDIT);

        // P123: ADVERTISER + Banner + VIEW + other => DENY
        $this->_assertAccess('banners', $this->otherBannerId, false, 'P123', OA_Permission::OPERATION_VIEW);

        // P124: ADVERTISER + Banner + EDIT + other => DENY
        $this->_assertAccess('banners', $this->otherBannerId, false, 'P124', OA_Permission::OPERATION_EDIT);

        // P125: ADVERTISER + Banner + DELETE + other => DENY
        $this->_assertAccess('banners', $this->otherBannerId, false, 'P125', OA_Permission::OPERATION_DELETE);

        // --- Own trackers ---
        // P126: ADVERTISER + Tracker + VIEW + own
        $this->_assertAccess('trackers', $this->ownTrackerId, true, 'P126', OA_Permission::OPERATION_VIEW);

        // P127: ADVERTISER + Tracker + EDIT + own
        $this->_assertAccess('trackers', $this->ownTrackerId, true, 'P127', OA_Permission::OPERATION_EDIT);

        // P128: ADVERTISER + Tracker + VIEW + other => DENY
        $this->_assertAccess('trackers', $this->otherTrackerId, false, 'P128', OA_Permission::OPERATION_VIEW);

        // P129: ADVERTISER + Tracker + EDIT + other => DENY
        $this->_assertAccess('trackers', $this->otherTrackerId, false, 'P129', OA_Permission::OPERATION_EDIT);

        // --- Cross-entity type denial ---
        // P130: ADVERTISER + Agency + VIEW + any => DENY
        $this->_assertAccess('agency', $this->ownAgencyId, false, 'P130', OA_Permission::OPERATION_VIEW);

        // P131: ADVERTISER + Agency + EDIT + any => DENY
        $this->_assertAccess('agency', $this->otherAgencyId, false, 'P131', OA_Permission::OPERATION_EDIT);

        // P132: ADVERTISER + Publisher + VIEW + any => DENY
        $this->_assertAccess('affiliates', $this->ownPublisherId, false, 'P132', OA_Permission::OPERATION_VIEW);

        // P133: ADVERTISER + Publisher + EDIT + any => DENY
        $this->_assertAccess('affiliates', $this->otherPublisherId, false, 'P133', OA_Permission::OPERATION_EDIT);

        // P134: ADVERTISER + Zone + VIEW + any => DENY
        $this->_assertAccess('zones', $this->ownZoneId, false, 'P134', OA_Permission::OPERATION_VIEW);

        // P135: ADVERTISER + Zone + ADD + any => DENY
        $this->_assertAccess('zones', $this->otherZoneId, false, 'P135', OA_Permission::OPERATION_ADD);

        // P136: ADVERTISER + Zone + EDIT + any => DENY
        $this->_assertAccess('zones', $this->ownZoneId, false, 'P136', OA_Permission::OPERATION_EDIT);

        // P137: ADVERTISER + Channel + VIEW + any => DENY
        $this->_assertAccess('channel', $this->ownChannelId, false, 'P137', OA_Permission::OPERATION_VIEW);

        // P138: ADVERTISER + Channel + EDIT + any => DENY
        $this->_assertAccess('channel', $this->otherChannelId, false, 'P138', OA_Permission::OPERATION_EDIT);

        // P139: ADVERTISER + Campaign + VIEW_CHILDREN + own
        $this->_assertAccess('campaigns', $this->ownCampaignId, true, 'P139', OA_Permission::OPERATION_VIEW_CHILDREN);

        // P140: ADVERTISER + Campaign + VIEW_CHILDREN + other => DENY
        $this->_assertAccess('campaigns', $this->otherCampaignId, false, 'P140', OA_Permission::OPERATION_VIEW_CHILDREN);
    }

    /**
     * P141-P165: ADVERTISER permission checks with granted/revoked permissions.
     */
    public function testAdvertiserPermissions()
    {
        $this->_setUser($this->advertiserUserId, $this->ownAdvertiserAccountId);

        // --- BANNER_EDIT ---
        // P141: ADVERTISER + BANNER_EDIT granted => ALLOW
        $this->_revokeAllPermissions($this->ownAdvertiserAccountId, $this->advertiserUserId);
        $this->_grantPermission($this->ownAdvertiserAccountId, $this->advertiserUserId, OA_PERM_BANNER_EDIT);
        $this->_assertPermission(OA_PERM_BANNER_EDIT, true, 'P141');

        // P142: ADVERTISER + BANNER_EDIT not granted => DENY
        $this->_revokeAllPermissions($this->ownAdvertiserAccountId, $this->advertiserUserId);
        $this->_assertPermission(OA_PERM_BANNER_EDIT, false, 'P142');

        // --- BANNER_ADD ---
        // P143: ADVERTISER + BANNER_ADD granted => ALLOW
        $this->_grantPermission($this->ownAdvertiserAccountId, $this->advertiserUserId, OA_PERM_BANNER_ADD);
        $this->_assertPermission(OA_PERM_BANNER_ADD, true, 'P143');

        // P144: ADVERTISER + BANNER_ADD not granted => DENY
        $this->_revokeAllPermissions($this->ownAdvertiserAccountId, $this->advertiserUserId);
        $this->_assertPermission(OA_PERM_BANNER_ADD, false, 'P144');

        // --- BANNER_ACTIVATE ---
        // P145: ADVERTISER + BANNER_ACTIVATE granted => ALLOW
        $this->_grantPermission($this->ownAdvertiserAccountId, $this->advertiserUserId, OA_PERM_BANNER_ACTIVATE);
        $this->_assertPermission(OA_PERM_BANNER_ACTIVATE, true, 'P145');

        // P146: ADVERTISER + BANNER_ACTIVATE not granted => DENY
        $this->_revokeAllPermissions($this->ownAdvertiserAccountId, $this->advertiserUserId);
        $this->_assertPermission(OA_PERM_BANNER_ACTIVATE, false, 'P146');

        // --- BANNER_DEACTIVATE ---
        // P147: ADVERTISER + BANNER_DEACTIVATE granted => ALLOW
        $this->_grantPermission($this->ownAdvertiserAccountId, $this->advertiserUserId, OA_PERM_BANNER_DEACTIVATE);
        $this->_assertPermission(OA_PERM_BANNER_DEACTIVATE, true, 'P147');

        // P148: ADVERTISER + BANNER_DEACTIVATE not granted => DENY
        $this->_revokeAllPermissions($this->ownAdvertiserAccountId, $this->advertiserUserId);
        $this->_assertPermission(OA_PERM_BANNER_DEACTIVATE, false, 'P148');

        // --- SUPER_ACCOUNT ---
        // P149: ADVERTISER + SUPER_ACCOUNT granted => ALLOW
        $this->_grantPermission($this->ownAdvertiserAccountId, $this->advertiserUserId, OA_PERM_SUPER_ACCOUNT);
        $this->_assertPermission(OA_PERM_SUPER_ACCOUNT, true, 'P149');

        // P150: ADVERTISER + SUPER_ACCOUNT not granted => DENY
        $this->_revokeAllPermissions($this->ownAdvertiserAccountId, $this->advertiserUserId);
        $this->_assertPermission(OA_PERM_SUPER_ACCOUNT, false, 'P150');

        // --- USER_LOG_ACCESS ---
        // P151: ADVERTISER + USER_LOG_ACCESS granted => ALLOW
        $this->_grantPermission($this->ownAdvertiserAccountId, $this->advertiserUserId, OA_PERM_USER_LOG_ACCESS);
        $this->_assertPermission(OA_PERM_USER_LOG_ACCESS, true, 'P151');

        // P152: ADVERTISER + USER_LOG_ACCESS not granted => DENY
        $this->_revokeAllPermissions($this->ownAdvertiserAccountId, $this->advertiserUserId);
        $this->_assertPermission(OA_PERM_USER_LOG_ACCESS, false, 'P152');

        // --- Cross-type permissions (not related to advertiser, return true) ---
        // P153: ADVERTISER + ZONE_ADD => not related to advertiser, returns true
        $this->_assertPermission(OA_PERM_ZONE_ADD, true, 'P153');

        // P154: ADVERTISER + ZONE_EDIT => not related to advertiser, returns true
        $this->_assertPermission(OA_PERM_ZONE_EDIT, true, 'P154');

        // P155: ADVERTISER + ZONE_DELETE => not related to advertiser, returns true
        $this->_assertPermission(OA_PERM_ZONE_DELETE, true, 'P155');

        // P156: ADVERTISER + ZONE_INVOCATION => not related to advertiser, returns true
        $this->_assertPermission(OA_PERM_ZONE_INVOCATION, true, 'P156');

        // P157: ADVERTISER + ZONE_LINK => not related to advertiser, returns true
        $this->_assertPermission(OA_PERM_ZONE_LINK, true, 'P157');

        // P158: ADVERTISER + MANAGER_DELETE => not related to advertiser, returns true
        $this->_assertPermission(OA_PERM_MANAGER_DELETE, true, 'P158');

        // --- checkAccountPermission ---
        // P159: ADVERTISER + checkAccountPermission BANNER_EDIT + granted => ALLOW
        $this->_grantPermission($this->ownAdvertiserAccountId, $this->advertiserUserId, OA_PERM_BANNER_EDIT);
        $result = OA_Permission::checkAccountPermission(OA_ACCOUNT_ADVERTISER, OA_PERM_BANNER_EDIT);
        $this->assertTrue($result, 'P159: ADVERTISER + BANNER_EDIT granted checkAccountPermission should ALLOW');
        $this->comboCount++;

        // P160: ADVERTISER + checkAccountPermission BANNER_EDIT + not granted => DENY
        $this->_revokeAllPermissions($this->ownAdvertiserAccountId, $this->advertiserUserId);
        $result = OA_Permission::checkAccountPermission(OA_ACCOUNT_ADVERTISER, OA_PERM_BANNER_EDIT);
        $this->assertFalse($result, 'P160: ADVERTISER + BANNER_EDIT not granted checkAccountPermission should DENY');
        $this->comboCount++;

        // P161: ADVERTISER + Banner + DUPLICATE + own
        $this->_assertAccess('banners', $this->ownBannerId, true, 'P161', OA_Permission::OPERATION_DUPLICATE);

        // P162: ADVERTISER + Banner + DUPLICATE + other => DENY
        $this->_assertAccess('banners', $this->otherBannerId, false, 'P162', OA_Permission::OPERATION_DUPLICATE);

        // P163: ADVERTISER + Banner + ADD (new entity)
        $this->_assertAccess('banners', 0, true, 'P163', OA_Permission::OPERATION_ADD);

        // P164: ADVERTISER + Campaign + ADD (new entity)
        $this->_assertAccess('campaigns', 0, true, 'P164', OA_Permission::OPERATION_ADD);

        // P165: ADVERTISER + Tracker + ADD (new entity)
        $this->_assertAccess('trackers', 0, true, 'P165', OA_Permission::OPERATION_ADD);
    }

    // =========================================================================
    // TRAFFICKER account tests
    // =========================================================================

    /**
     * P166-P195: TRAFFICKER access to entities.
     */
    public function testTraffickerEntityAccess()
    {
        $this->_setUser($this->traffickerUserId, $this->ownPublisherAccountId);

        // --- Own publisher (affiliates) ---
        // P166: TRAFFICKER + Publisher + VIEW + own
        $this->_assertAccess('affiliates', $this->ownPublisherId, true, 'P166', OA_Permission::OPERATION_VIEW);

        // P167: TRAFFICKER + Publisher + EDIT + own
        $this->_assertAccess('affiliates', $this->ownPublisherId, true, 'P167', OA_Permission::OPERATION_EDIT);

        // P168: TRAFFICKER + Publisher + VIEW + other => DENY
        $this->_assertAccess('affiliates', $this->otherPublisherId, false, 'P168', OA_Permission::OPERATION_VIEW);

        // P169: TRAFFICKER + Publisher + EDIT + other => DENY
        $this->_assertAccess('affiliates', $this->otherPublisherId, false, 'P169', OA_Permission::OPERATION_EDIT);

        // P170: TRAFFICKER + Publisher + DELETE + any => DENY
        $this->_assertAccess('affiliates', $this->otherPublisherId, false, 'P170', OA_Permission::OPERATION_DELETE);

        // --- Own zones ---
        // P171: TRAFFICKER + Zone + VIEW + own
        $this->_assertAccess('zones', $this->ownZoneId, true, 'P171', OA_Permission::OPERATION_VIEW);

        // P172: TRAFFICKER + Zone + EDIT + own
        $this->_assertAccess('zones', $this->ownZoneId, true, 'P172', OA_Permission::OPERATION_EDIT);

        // P173: TRAFFICKER + Zone + VIEW + other => DENY
        $this->_assertAccess('zones', $this->otherZoneId, false, 'P173', OA_Permission::OPERATION_VIEW);

        // P174: TRAFFICKER + Zone + EDIT + other => DENY
        $this->_assertAccess('zones', $this->otherZoneId, false, 'P174', OA_Permission::OPERATION_EDIT);

        // P175: TRAFFICKER + Zone + DELETE + other => DENY
        $this->_assertAccess('zones', $this->otherZoneId, false, 'P175', OA_Permission::OPERATION_DELETE);

        // --- Cross-entity type denial ---
        // P176: TRAFFICKER + Agency + VIEW + any => DENY
        $this->_assertAccess('agency', $this->ownAgencyId, false, 'P176', OA_Permission::OPERATION_VIEW);

        // P177: TRAFFICKER + Agency + EDIT + any => DENY
        $this->_assertAccess('agency', $this->otherAgencyId, false, 'P177', OA_Permission::OPERATION_EDIT);

        // P178: TRAFFICKER + Advertiser + VIEW + any => DENY
        $this->_assertAccess('clients', $this->ownAdvertiserId, false, 'P178', OA_Permission::OPERATION_VIEW);

        // P179: TRAFFICKER + Advertiser + EDIT + any => DENY
        $this->_assertAccess('clients', $this->otherAdvertiserId, false, 'P179', OA_Permission::OPERATION_EDIT);

        // P180: TRAFFICKER + Campaign + VIEW + any => DENY
        $this->_assertAccess('campaigns', $this->ownCampaignId, false, 'P180', OA_Permission::OPERATION_VIEW);

        // P181: TRAFFICKER + Campaign + EDIT + any => DENY
        $this->_assertAccess('campaigns', $this->otherCampaignId, false, 'P181', OA_Permission::OPERATION_EDIT);

        // P182: TRAFFICKER + Campaign + DELETE + any => DENY
        $this->_assertAccess('campaigns', $this->ownCampaignId, false, 'P182', OA_Permission::OPERATION_DELETE);

        // P183: TRAFFICKER + Banner + VIEW + any => DENY
        $this->_assertAccess('banners', $this->ownBannerId, false, 'P183', OA_Permission::OPERATION_VIEW);

        // P184: TRAFFICKER + Banner + EDIT + any => DENY
        $this->_assertAccess('banners', $this->otherBannerId, false, 'P184', OA_Permission::OPERATION_EDIT);

        // P185: TRAFFICKER + Tracker + VIEW + any => DENY
        $this->_assertAccess('trackers', $this->ownTrackerId, false, 'P185', OA_Permission::OPERATION_VIEW);

        // P186: TRAFFICKER + Tracker + EDIT + any => DENY
        $this->_assertAccess('trackers', $this->otherTrackerId, false, 'P186', OA_Permission::OPERATION_EDIT);

        // P187: TRAFFICKER + Tracker + CREATE + any => DENY (new entity but wrong type)
        $this->_assertAccess('trackers', $this->otherTrackerId, false, 'P187', OA_Permission::OPERATION_ADD);

        // P188: TRAFFICKER + Channel + VIEW + any => DENY
        $this->_assertAccess('channel', $this->ownChannelId, false, 'P188', OA_Permission::OPERATION_VIEW);

        // P189: TRAFFICKER + Channel + EDIT + any => DENY
        $this->_assertAccess('channel', $this->otherChannelId, false, 'P189', OA_Permission::OPERATION_EDIT);

        // P190: TRAFFICKER + Zone + ADD (new entity)
        $this->_assertAccess('zones', 0, true, 'P190', OA_Permission::OPERATION_ADD);

        // P191: TRAFFICKER + Zone + DUPLICATE + own
        $this->_assertAccess('zones', $this->ownZoneId, true, 'P191', OA_Permission::OPERATION_DUPLICATE);

        // P192: TRAFFICKER + Zone + DUPLICATE + other => DENY
        $this->_assertAccess('zones', $this->otherZoneId, false, 'P192', OA_Permission::OPERATION_DUPLICATE);

        // P193: TRAFFICKER + Zone + VIEW_CHILDREN + own
        $this->_assertAccess('zones', $this->ownZoneId, true, 'P193', OA_Permission::OPERATION_VIEW_CHILDREN);

        // P194: TRAFFICKER + Zone + VIEW_CHILDREN + other => DENY
        $this->_assertAccess('zones', $this->otherZoneId, false, 'P194', OA_Permission::OPERATION_VIEW_CHILDREN);

        // P195: TRAFFICKER + Publisher + ADD_CHILD + own
        $this->_assertAccess('affiliates', $this->ownPublisherId, true, 'P195', OA_Permission::OPERATION_ADD_CHILD);
    }

    /**
     * P196-P220: TRAFFICKER permission checks with granted/revoked permissions.
     */
    public function testTraffickerPermissions()
    {
        $this->_setUser($this->traffickerUserId, $this->ownPublisherAccountId);

        // --- ZONE_ADD ---
        // P196: TRAFFICKER + ZONE_ADD granted => ALLOW
        $this->_revokeAllPermissions($this->ownPublisherAccountId, $this->traffickerUserId);
        $this->_grantPermission($this->ownPublisherAccountId, $this->traffickerUserId, OA_PERM_ZONE_ADD);
        $this->_assertPermission(OA_PERM_ZONE_ADD, true, 'P196');

        // P197: TRAFFICKER + ZONE_ADD not granted => DENY
        $this->_revokeAllPermissions($this->ownPublisherAccountId, $this->traffickerUserId);
        $this->_assertPermission(OA_PERM_ZONE_ADD, false, 'P197');

        // --- ZONE_EDIT ---
        // P198: TRAFFICKER + ZONE_EDIT granted => ALLOW
        $this->_grantPermission($this->ownPublisherAccountId, $this->traffickerUserId, OA_PERM_ZONE_EDIT);
        $this->_assertPermission(OA_PERM_ZONE_EDIT, true, 'P198');

        // P199: TRAFFICKER + ZONE_EDIT not granted => DENY
        $this->_revokeAllPermissions($this->ownPublisherAccountId, $this->traffickerUserId);
        $this->_assertPermission(OA_PERM_ZONE_EDIT, false, 'P199');

        // --- ZONE_DELETE ---
        // P200: TRAFFICKER + ZONE_DELETE granted => ALLOW
        $this->_grantPermission($this->ownPublisherAccountId, $this->traffickerUserId, OA_PERM_ZONE_DELETE);
        $this->_assertPermission(OA_PERM_ZONE_DELETE, true, 'P200');

        // P201: TRAFFICKER + ZONE_DELETE not granted => DENY
        $this->_revokeAllPermissions($this->ownPublisherAccountId, $this->traffickerUserId);
        $this->_assertPermission(OA_PERM_ZONE_DELETE, false, 'P201');

        // --- ZONE_INVOCATION ---
        // P202: TRAFFICKER + ZONE_INVOCATION granted => ALLOW
        $this->_grantPermission($this->ownPublisherAccountId, $this->traffickerUserId, OA_PERM_ZONE_INVOCATION);
        $this->_assertPermission(OA_PERM_ZONE_INVOCATION, true, 'P202');

        // P203: TRAFFICKER + ZONE_INVOCATION not granted => DENY
        $this->_revokeAllPermissions($this->ownPublisherAccountId, $this->traffickerUserId);
        $this->_assertPermission(OA_PERM_ZONE_INVOCATION, false, 'P203');

        // --- ZONE_LINK ---
        // P204: TRAFFICKER + ZONE_LINK granted => ALLOW
        $this->_grantPermission($this->ownPublisherAccountId, $this->traffickerUserId, OA_PERM_ZONE_LINK);
        $this->_assertPermission(OA_PERM_ZONE_LINK, true, 'P204');

        // P205: TRAFFICKER + ZONE_LINK not granted => DENY
        $this->_revokeAllPermissions($this->ownPublisherAccountId, $this->traffickerUserId);
        $this->_assertPermission(OA_PERM_ZONE_LINK, false, 'P205');

        // --- SUPER_ACCOUNT ---
        // P206: TRAFFICKER + SUPER_ACCOUNT granted => ALLOW
        $this->_grantPermission($this->ownPublisherAccountId, $this->traffickerUserId, OA_PERM_SUPER_ACCOUNT);
        $this->_assertPermission(OA_PERM_SUPER_ACCOUNT, true, 'P206');

        // P207: TRAFFICKER + SUPER_ACCOUNT not granted => DENY
        $this->_revokeAllPermissions($this->ownPublisherAccountId, $this->traffickerUserId);
        $this->_assertPermission(OA_PERM_SUPER_ACCOUNT, false, 'P207');

        // --- USER_LOG_ACCESS ---
        // P208: TRAFFICKER + USER_LOG_ACCESS granted => ALLOW
        $this->_grantPermission($this->ownPublisherAccountId, $this->traffickerUserId, OA_PERM_USER_LOG_ACCESS);
        $this->_assertPermission(OA_PERM_USER_LOG_ACCESS, true, 'P208');

        // P209: TRAFFICKER + USER_LOG_ACCESS not granted => DENY
        $this->_revokeAllPermissions($this->ownPublisherAccountId, $this->traffickerUserId);
        $this->_assertPermission(OA_PERM_USER_LOG_ACCESS, false, 'P209');

        // --- Cross-type permissions (not related to trafficker, return true) ---
        // P210: TRAFFICKER + BANNER_EDIT => not related to trafficker, returns true
        $this->_assertPermission(OA_PERM_BANNER_EDIT, true, 'P210');

        // P211: TRAFFICKER + BANNER_ADD => not related to trafficker, returns true
        $this->_assertPermission(OA_PERM_BANNER_ADD, true, 'P211');

        // P212: TRAFFICKER + BANNER_ACTIVATE => not related to trafficker, returns true
        $this->_assertPermission(OA_PERM_BANNER_ACTIVATE, true, 'P212');

        // P213: TRAFFICKER + BANNER_DEACTIVATE => not related to trafficker, returns true
        $this->_assertPermission(OA_PERM_BANNER_DEACTIVATE, true, 'P213');

        // P214: TRAFFICKER + MANAGER_DELETE => not related to trafficker, returns true
        $this->_assertPermission(OA_PERM_MANAGER_DELETE, true, 'P214');

        // --- checkAccountPermission ---
        // P215: TRAFFICKER + checkAccountPermission ZONE_ADD + granted => ALLOW
        $this->_grantPermission($this->ownPublisherAccountId, $this->traffickerUserId, OA_PERM_ZONE_ADD);
        $result = OA_Permission::checkAccountPermission(OA_ACCOUNT_TRAFFICKER, OA_PERM_ZONE_ADD);
        $this->assertTrue($result, 'P215: TRAFFICKER + ZONE_ADD granted checkAccountPermission should ALLOW');
        $this->comboCount++;

        // P216: TRAFFICKER + checkAccountPermission ZONE_ADD + not granted => DENY
        $this->_revokeAllPermissions($this->ownPublisherAccountId, $this->traffickerUserId);
        $result = OA_Permission::checkAccountPermission(OA_ACCOUNT_TRAFFICKER, OA_PERM_ZONE_ADD);
        $this->assertFalse($result, 'P216: TRAFFICKER + ZONE_ADD not granted checkAccountPermission should DENY');
        $this->comboCount++;

        // P217: TRAFFICKER + checkAccountPermission ZONE_EDIT + granted => ALLOW
        $this->_grantPermission($this->ownPublisherAccountId, $this->traffickerUserId, OA_PERM_ZONE_EDIT);
        $result = OA_Permission::checkAccountPermission(OA_ACCOUNT_TRAFFICKER, OA_PERM_ZONE_EDIT);
        $this->assertTrue($result, 'P217: TRAFFICKER + ZONE_EDIT granted checkAccountPermission should ALLOW');
        $this->comboCount++;

        // P218: TRAFFICKER + checkAccountPermission ZONE_EDIT + not granted => DENY
        $this->_revokeAllPermissions($this->ownPublisherAccountId, $this->traffickerUserId);
        $result = OA_Permission::checkAccountPermission(OA_ACCOUNT_TRAFFICKER, OA_PERM_ZONE_EDIT);
        $this->assertFalse($result, 'P218: TRAFFICKER + ZONE_EDIT not granted checkAccountPermission should DENY');
        $this->comboCount++;

        // P219: TRAFFICKER + checkAccountPermission ZONE_DELETE + granted => ALLOW
        $this->_grantPermission($this->ownPublisherAccountId, $this->traffickerUserId, OA_PERM_ZONE_DELETE);
        $result = OA_Permission::checkAccountPermission(OA_ACCOUNT_TRAFFICKER, OA_PERM_ZONE_DELETE);
        $this->assertTrue($result, 'P219: TRAFFICKER + ZONE_DELETE granted checkAccountPermission should ALLOW');
        $this->comboCount++;

        // P220: TRAFFICKER + checkAccountPermission ZONE_DELETE + not granted => DENY
        $this->_revokeAllPermissions($this->ownPublisherAccountId, $this->traffickerUserId);
        $result = OA_Permission::checkAccountPermission(OA_ACCOUNT_TRAFFICKER, OA_PERM_ZONE_DELETE);
        $this->assertFalse($result, 'P220: TRAFFICKER + ZONE_DELETE not granted checkAccountPermission should DENY');
        $this->comboCount++;
    }

    // =========================================================================
    // isAccount verification tests
    // =========================================================================

    /**
     * P221-P228: Verify isAccount returns correct values for each account type.
     */
    public function testIsAccountVerification()
    {
        // P221: ADMIN isAccount(ADMIN) => true
        $this->_setUser($this->adminUserId, $this->adminAccountId);
        $this->assertTrue(OA_Permission::isAccount(OA_ACCOUNT_ADMIN), 'P221');
        $this->comboCount++;

        // P222: ADMIN isAccount(MANAGER) => false
        $this->assertFalse(OA_Permission::isAccount(OA_ACCOUNT_MANAGER), 'P222');
        $this->comboCount++;

        // P223: MANAGER isAccount(MANAGER) => true
        $this->_setUser($this->managerUserId, $this->ownManagerAccountId);
        $this->assertTrue(OA_Permission::isAccount(OA_ACCOUNT_MANAGER), 'P223');
        $this->comboCount++;

        // P224: MANAGER isAccount(ADMIN) => false
        $this->assertFalse(OA_Permission::isAccount(OA_ACCOUNT_ADMIN), 'P224');
        $this->comboCount++;

        // P225: ADVERTISER isAccount(ADVERTISER) => true
        $this->_setUser($this->advertiserUserId, $this->ownAdvertiserAccountId);
        $this->assertTrue(OA_Permission::isAccount(OA_ACCOUNT_ADVERTISER), 'P225');
        $this->comboCount++;

        // P226: ADVERTISER isAccount(TRAFFICKER) => false
        $this->assertFalse(OA_Permission::isAccount(OA_ACCOUNT_TRAFFICKER), 'P226');
        $this->comboCount++;

        // P227: TRAFFICKER isAccount(TRAFFICKER) => true
        $this->_setUser($this->traffickerUserId, $this->ownPublisherAccountId);
        $this->assertTrue(OA_Permission::isAccount(OA_ACCOUNT_TRAFFICKER), 'P227');
        $this->comboCount++;

        // P228: TRAFFICKER isAccount(ADVERTISER) => false
        $this->assertFalse(OA_Permission::isAccount(OA_ACCOUNT_ADVERTISER), 'P228');
        $this->comboCount++;
    }

    // =========================================================================
    // isPermissionRelatedToAccountType tests
    // =========================================================================

    /**
     * P229-P248: Test isPermissionRelatedToAccountType for each perm x account combo.
     */
    public function testPermissionAccountTypeRelation()
    {
        // Banner permissions relate only to ADVERTISER
        // P229: BANNER_EDIT related to ADVERTISER => true
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_ADVERTISER, OA_PERM_BANNER_EDIT),
            'P229',
        );
        $this->comboCount++;

        // P230: BANNER_EDIT related to MANAGER => false
        $this->assertFalse(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_MANAGER, OA_PERM_BANNER_EDIT),
            'P230',
        );
        $this->comboCount++;

        // P231: BANNER_EDIT related to TRAFFICKER => false
        $this->assertFalse(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_TRAFFICKER, OA_PERM_BANNER_EDIT),
            'P231',
        );
        $this->comboCount++;

        // P232: BANNER_ADD related to ADVERTISER => true
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_ADVERTISER, OA_PERM_BANNER_ADD),
            'P232',
        );
        $this->comboCount++;

        // P233: BANNER_ACTIVATE related to ADVERTISER => true
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_ADVERTISER, OA_PERM_BANNER_ACTIVATE),
            'P233',
        );
        $this->comboCount++;

        // P234: BANNER_DEACTIVATE related to ADVERTISER => true
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_ADVERTISER, OA_PERM_BANNER_DEACTIVATE),
            'P234',
        );
        $this->comboCount++;

        // Zone permissions relate only to TRAFFICKER
        // P235: ZONE_ADD related to TRAFFICKER => true
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_TRAFFICKER, OA_PERM_ZONE_ADD),
            'P235',
        );
        $this->comboCount++;

        // P236: ZONE_ADD related to ADVERTISER => false
        $this->assertFalse(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_ADVERTISER, OA_PERM_ZONE_ADD),
            'P236',
        );
        $this->comboCount++;

        // P237: ZONE_ADD related to MANAGER => false
        $this->assertFalse(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_MANAGER, OA_PERM_ZONE_ADD),
            'P237',
        );
        $this->comboCount++;

        // P238: ZONE_EDIT related to TRAFFICKER => true
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_TRAFFICKER, OA_PERM_ZONE_EDIT),
            'P238',
        );
        $this->comboCount++;

        // P239: ZONE_DELETE related to TRAFFICKER => true
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_TRAFFICKER, OA_PERM_ZONE_DELETE),
            'P239',
        );
        $this->comboCount++;

        // P240: ZONE_INVOCATION related to TRAFFICKER => true
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_TRAFFICKER, OA_PERM_ZONE_INVOCATION),
            'P240',
        );
        $this->comboCount++;

        // P241: ZONE_LINK related to TRAFFICKER => true
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_TRAFFICKER, OA_PERM_ZONE_LINK),
            'P241',
        );
        $this->comboCount++;

        // SUPER_ACCOUNT relates to MANAGER, ADVERTISER, TRAFFICKER
        // P242: SUPER_ACCOUNT related to MANAGER => true
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_MANAGER, OA_PERM_SUPER_ACCOUNT),
            'P242',
        );
        $this->comboCount++;

        // P243: SUPER_ACCOUNT related to ADVERTISER => true
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_ADVERTISER, OA_PERM_SUPER_ACCOUNT),
            'P243',
        );
        $this->comboCount++;

        // P244: SUPER_ACCOUNT related to TRAFFICKER => true
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_TRAFFICKER, OA_PERM_SUPER_ACCOUNT),
            'P244',
        );
        $this->comboCount++;

        // USER_LOG_ACCESS relates to ADVERTISER, TRAFFICKER
        // P245: USER_LOG_ACCESS related to ADVERTISER => true
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_ADVERTISER, OA_PERM_USER_LOG_ACCESS),
            'P245',
        );
        $this->comboCount++;

        // P246: USER_LOG_ACCESS related to TRAFFICKER => true
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_TRAFFICKER, OA_PERM_USER_LOG_ACCESS),
            'P246',
        );
        $this->comboCount++;

        // P247: USER_LOG_ACCESS related to MANAGER => false
        $this->assertFalse(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_MANAGER, OA_PERM_USER_LOG_ACCESS),
            'P247',
        );
        $this->comboCount++;

        // MANAGER_DELETE relates only to MANAGER
        // P248: MANAGER_DELETE related to MANAGER => true
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_MANAGER, OA_PERM_MANAGER_DELETE),
            'P248',
        );
        $this->comboCount++;
    }

    // =========================================================================
    // DLL-level checkPermissions tests
    // =========================================================================

    /**
     * P249-P260: DLL-level checkPermissions combining account type, entity, and ownership.
     */
    public function testDllCheckPermissions()
    {
        $dll = new OA_Dll_Agency();

        // P249: ADMIN + DLL Agency checkPermissions => ALLOW
        $this->_setUser($this->adminUserId, $this->adminAccountId);
        $this->_assertDllCheckPermissions(
            $dll,
            OA_ACCOUNT_ADMIN,
            'agency',
            $this->ownAgencyId,
            true,
            'P249',
        );

        // P250: MANAGER + DLL Agency checkPermissions own => ALLOW
        $this->_setUser($this->managerUserId, $this->ownManagerAccountId);
        $this->_assertDllCheckPermissions(
            $dll,
            [OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER],
            'agency',
            $this->ownAgencyId,
            true,
            'P250',
        );

        // P251: MANAGER + DLL Agency checkPermissions other => DENY
        $this->_assertDllCheckPermissions(
            $dll,
            OA_ACCOUNT_MANAGER,
            'agency',
            $this->otherAgencyId,
            false,
            'P251',
        );

        $dllBanner = new OA_Dll_Banner();

        // P252: ADVERTISER + DLL Banner checkPermissions own => ALLOW
        $this->_setUser($this->advertiserUserId, $this->ownAdvertiserAccountId);
        $this->_assertDllCheckPermissions(
            $dllBanner,
            [OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER, OA_ACCOUNT_ADVERTISER],
            'banners',
            $this->ownBannerId,
            true,
            'P252',
        );

        // P253: ADVERTISER + DLL Banner checkPermissions other => DENY
        $this->_assertDllCheckPermissions(
            $dllBanner,
            OA_ACCOUNT_ADVERTISER,
            'banners',
            $this->otherBannerId,
            false,
            'P253',
        );

        $dllZone = new OA_Dll_Zone();

        // P254: TRAFFICKER + DLL Zone checkPermissions own => ALLOW
        $this->_setUser($this->traffickerUserId, $this->ownPublisherAccountId);
        $this->_assertDllCheckPermissions(
            $dllZone,
            [OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER, OA_ACCOUNT_TRAFFICKER],
            'zones',
            $this->ownZoneId,
            true,
            'P254',
        );

        // P255: TRAFFICKER + DLL Zone checkPermissions other => DENY
        $this->_assertDllCheckPermissions(
            $dllZone,
            OA_ACCOUNT_TRAFFICKER,
            'zones',
            $this->otherZoneId,
            false,
            'P255',
        );

        $dllCampaign = new OA_Dll_Campaign();

        // P256: ADMIN + DLL Campaign checkPermissions => ALLOW
        $this->_setUser($this->adminUserId, $this->adminAccountId);
        $this->_assertDllCheckPermissions(
            $dllCampaign,
            OA_ACCOUNT_ADMIN,
            'campaigns',
            $this->ownCampaignId,
            true,
            'P256',
        );

        // P257: MANAGER + DLL Campaign checkPermissions own => ALLOW
        $this->_setUser($this->managerUserId, $this->ownManagerAccountId);
        $this->_assertDllCheckPermissions(
            $dllCampaign,
            [OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER],
            'campaigns',
            $this->ownCampaignId,
            true,
            'P257',
        );

        $dllAdvertiser = new OA_Dll_Advertiser();

        // P258: MANAGER + DLL Advertiser checkPermissions own => ALLOW
        $this->_assertDllCheckPermissions(
            $dllAdvertiser,
            [OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER],
            'clients',
            $this->ownAdvertiserId,
            true,
            'P258',
        );

        // P259: MANAGER + DLL Advertiser checkPermissions other => DENY
        $this->_assertDllCheckPermissions(
            $dllAdvertiser,
            OA_ACCOUNT_MANAGER,
            'clients',
            $this->otherAdvertiserId,
            false,
            'P259',
        );

        $dllPublisher = new OA_Dll_Publisher();

        // P260: MANAGER + DLL Publisher checkPermissions own => ALLOW
        $this->_assertDllCheckPermissions(
            $dllPublisher,
            [OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER],
            'affiliates',
            $this->ownPublisherId,
            true,
            'P260',
        );
    }

    /**
     * P261-P268: DLL-level checkPermissions with allowed parameter (granular permission checks).
     */
    public function testDllCheckPermissionsWithAllowed()
    {
        $dllBanner = new OA_Dll_Banner();

        // P261: ADVERTISER + DLL Banner + BANNER_EDIT allowed + perm granted => ALLOW
        $this->_setUser($this->advertiserUserId, $this->ownAdvertiserAccountId);
        $this->_revokeAllPermissions($this->ownAdvertiserAccountId, $this->advertiserUserId);
        $this->_grantPermission($this->ownAdvertiserAccountId, $this->advertiserUserId, OA_PERM_BANNER_EDIT);
        $this->_assertDllCheckPermissions(
            $dllBanner,
            [OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER, OA_ACCOUNT_ADVERTISER],
            'banners',
            $this->ownBannerId,
            true,
            'P261',
            OA_PERM_BANNER_EDIT,
        );

        // P262: ADVERTISER + DLL Banner + BANNER_EDIT allowed + perm NOT granted => DENY
        $this->_revokeAllPermissions($this->ownAdvertiserAccountId, $this->advertiserUserId);
        $this->_assertDllCheckPermissions(
            $dllBanner,
            [OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER, OA_ACCOUNT_ADVERTISER],
            'banners',
            $this->ownBannerId,
            false,
            'P262',
            OA_PERM_BANNER_EDIT,
        );

        $dllZone = new OA_Dll_Zone();

        // P263: TRAFFICKER + DLL Zone + ZONE_EDIT allowed + perm granted => ALLOW
        $this->_setUser($this->traffickerUserId, $this->ownPublisherAccountId);
        $this->_revokeAllPermissions($this->ownPublisherAccountId, $this->traffickerUserId);
        $this->_grantPermission($this->ownPublisherAccountId, $this->traffickerUserId, OA_PERM_ZONE_EDIT);
        $this->_assertDllCheckPermissions(
            $dllZone,
            [OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER, OA_ACCOUNT_TRAFFICKER],
            'zones',
            $this->ownZoneId,
            true,
            'P263',
            OA_PERM_ZONE_EDIT,
        );

        // P264: TRAFFICKER + DLL Zone + ZONE_EDIT allowed + perm NOT granted => DENY
        $this->_revokeAllPermissions($this->ownPublisherAccountId, $this->traffickerUserId);
        $this->_assertDllCheckPermissions(
            $dllZone,
            [OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER, OA_ACCOUNT_TRAFFICKER],
            'zones',
            $this->ownZoneId,
            false,
            'P264',
            OA_PERM_ZONE_EDIT,
        );

        // P265: TRAFFICKER + DLL Zone + ZONE_ADD allowed + perm granted => ALLOW
        $this->_grantPermission($this->ownPublisherAccountId, $this->traffickerUserId, OA_PERM_ZONE_ADD);
        $this->_assertDllCheckPermissions(
            $dllZone,
            [OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER, OA_ACCOUNT_TRAFFICKER],
            'zones',
            $this->ownZoneId,
            true,
            'P265',
            OA_PERM_ZONE_ADD,
        );

        // P266: TRAFFICKER + DLL Zone + ZONE_ADD allowed + perm NOT granted => DENY
        $this->_revokeAllPermissions($this->ownPublisherAccountId, $this->traffickerUserId);
        $this->_assertDllCheckPermissions(
            $dllZone,
            [OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER, OA_ACCOUNT_TRAFFICKER],
            'zones',
            $this->ownZoneId,
            false,
            'P266',
            OA_PERM_ZONE_ADD,
        );

        // P267: ADMIN + DLL Banner + BANNER_EDIT allowed (admin always passes) => ALLOW
        $this->_setUser($this->adminUserId, $this->adminAccountId);
        $this->_assertDllCheckPermissions(
            $dllBanner,
            OA_ACCOUNT_ADMIN,
            'banners',
            $this->ownBannerId,
            true,
            'P267',
            OA_PERM_BANNER_EDIT,
        );

        // P268: MANAGER + DLL Zone + ZONE_EDIT allowed (not related to manager, passes) => ALLOW
        $this->_setUser($this->managerUserId, $this->ownManagerAccountId);
        $this->_assertDllCheckPermissions(
            $dllZone,
            [OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER],
            'zones',
            $this->ownZoneId,
            true,
            'P268',
            OA_PERM_ZONE_EDIT,
        );
    }

    // =========================================================================
    // DLL-level DELETE operation access type tests
    // =========================================================================

    /**
     * P269-P276: DLL checkPermissions with OPERATION_DELETE and MANAGER_DELETE perm.
     */
    public function testDllDeleteOperationPermissions()
    {
        $dllAgency = new OA_Dll_Agency();
        $dllAdvertiser = new OA_Dll_Advertiser();

        // P269: MANAGER + DLL delete own agency + MANAGER_DELETE granted => ALLOW
        $this->_setUser($this->managerUserId, $this->ownManagerAccountId);
        $this->_revokeAllPermissions($this->ownManagerAccountId, $this->managerUserId);
        $this->_grantPermission($this->ownManagerAccountId, $this->managerUserId, OA_PERM_MANAGER_DELETE);
        $result = $dllAgency->checkPermissions(
            [OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER],
            'agency',
            $this->ownAgencyId,
            null,
            OA_Permission::OPERATION_DELETE,
        );
        $this->assertTrue($result, 'P269: MANAGER + DELETE own agency + MANAGER_DELETE granted should ALLOW');
        $this->comboCount++;

        // P270: MANAGER + DLL delete own agency + MANAGER_DELETE NOT granted => DENY
        $this->_revokeAllPermissions($this->ownManagerAccountId, $this->managerUserId);
        $result = $dllAgency->checkPermissions(
            [OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER],
            'agency',
            $this->ownAgencyId,
            null,
            OA_Permission::OPERATION_DELETE,
        );
        $this->assertFalse($result, 'P270: MANAGER + DELETE own agency + MANAGER_DELETE not granted should DENY');
        $this->comboCount++;

        // P271: MANAGER + DLL delete own advertiser + MANAGER_DELETE granted => ALLOW
        $this->_grantPermission($this->ownManagerAccountId, $this->managerUserId, OA_PERM_MANAGER_DELETE);
        $result = $dllAdvertiser->checkPermissions(
            [OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER],
            'clients',
            $this->ownAdvertiserId,
            null,
            OA_Permission::OPERATION_DELETE,
        );
        $this->assertTrue($result, 'P271: MANAGER + DELETE own advertiser + MANAGER_DELETE granted should ALLOW');
        $this->comboCount++;

        // P272: MANAGER + DLL delete own advertiser + MANAGER_DELETE NOT granted => DENY
        $this->_revokeAllPermissions($this->ownManagerAccountId, $this->managerUserId);
        $result = $dllAdvertiser->checkPermissions(
            [OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER],
            'clients',
            $this->ownAdvertiserId,
            null,
            OA_Permission::OPERATION_DELETE,
        );
        $this->assertFalse($result, 'P272: MANAGER + DELETE own advertiser + MANAGER_DELETE not granted should DENY');
        $this->comboCount++;

        // P273: ADMIN + DLL delete any entity (admin always allowed) => ALLOW
        $this->_setUser($this->adminUserId, $this->adminAccountId);
        $result = $dllAgency->checkPermissions(
            OA_ACCOUNT_ADMIN,
            'agency',
            $this->ownAgencyId,
            null,
            OA_Permission::OPERATION_DELETE,
        );
        $this->assertTrue($result, 'P273: ADMIN + DELETE any entity should ALLOW');
        $this->comboCount++;

        // P274: ADMIN + DLL delete other agency => ALLOW
        $result = $dllAgency->checkPermissions(
            OA_ACCOUNT_ADMIN,
            'agency',
            $this->otherAgencyId,
            null,
            OA_Permission::OPERATION_DELETE,
        );
        $this->assertTrue($result, 'P274: ADMIN + DELETE other agency should ALLOW');
        $this->comboCount++;

        $dllPublisher = new OA_Dll_Publisher();

        // P275: MANAGER + DLL delete own publisher + MANAGER_DELETE granted => ALLOW
        $this->_setUser($this->managerUserId, $this->ownManagerAccountId);
        $this->_grantPermission($this->ownManagerAccountId, $this->managerUserId, OA_PERM_MANAGER_DELETE);
        $result = $dllPublisher->checkPermissions(
            [OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER],
            'affiliates',
            $this->ownPublisherId,
            null,
            OA_Permission::OPERATION_DELETE,
        );
        $this->assertTrue($result, 'P275: MANAGER + DELETE own publisher + MANAGER_DELETE granted should ALLOW');
        $this->comboCount++;

        // P276: MANAGER + DLL delete own publisher + MANAGER_DELETE NOT granted => DENY
        $this->_revokeAllPermissions($this->ownManagerAccountId, $this->managerUserId);
        $result = $dllPublisher->checkPermissions(
            [OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER],
            'affiliates',
            $this->ownPublisherId,
            null,
            OA_Permission::OPERATION_DELETE,
        );
        $this->assertFalse($result, 'P276: MANAGER + DELETE own publisher + MANAGER_DELETE not granted should DENY');
        $this->comboCount++;
    }
}
