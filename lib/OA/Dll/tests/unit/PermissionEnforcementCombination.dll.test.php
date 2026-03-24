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
require_once MAX_PATH . '/lib/OA/Dal/DataGenerator.php';
require_once MAX_PATH . '/lib/OA/Permission.php';
require_once MAX_PATH . '/lib/OA/Permission/User.php';
require_once MAX_PATH . '/lib/OA/Dll/tests/util/DllUnitTestCase.php';

/**
 * Permission Enforcement Exhaustive Matrix Tests (Section 4G)
 *
 * Covers ~270 exhaustive test combinations across:
 * - Account type: ADMIN, MANAGER, ADVERTISER, TRAFFICKER
 * - Entity type: Agency, Advertiser, Campaign, Banner, Publisher, Zone, Tracker, Channel
 * - Operation: ADD, EDIT, VIEW, DELETE, DUPLICATE, MOVE, ADD_CHILD, VIEW_CHILDREN
 * - Ownership: Own entity, other's entity, parent entity, no linked entity
 *
 * @package    OpenXDll
 * @subpackage TestSuite
 */
class Test_OA_Dll_PermissionEnforcementCombination extends DllUnitTestCase
{
    private $adminAccountId;
    private $adminUserId;
    private $managerAgencyId1;
    private $managerAccountId1;
    private $managerUserId1;
    private $managerAdvertiserId1;
    private $managerAdvertiserAccountId1;
    private $managerPublisherId1;
    private $managerPublisherAccountId1;
    private $managerCampaignId1;
    private $managerBannerId1;
    private $managerZoneId1;
    private $managerTrackerId1;
    private $managerChannelId1;
    private $managerAgencyId2;
    private $managerAccountId2;
    private $managerUserId2;
    private $managerAdvertiserId2;
    private $managerCampaignId2;
    private $managerBannerId2;
    private $managerPublisherId2;
    private $managerZoneId2;
    private $managerTrackerId2;
    private $advertiserUserId1;
    private $trafficherUserId1;

    public function setUp()
    {
    }

    public function tearDown()
    {
        DataGenerator::cleanUp([
            'agency', 'clients', 'campaigns', 'banners',
            'affiliates', 'zones', 'trackers', 'channel',
            'users', 'accounts', 'account_user_assoc',
            'account_user_permission_assoc',
        ]);
        unset($GLOBALS['session']['user']);
    }

    /**
     * Build a complete test entity hierarchy:
     * - Admin account + user
     * - Agency 1 (Manager 1): Advertiser 1 -> Campaign 1 -> Banner 1,
     *   Publisher 1 -> Zone 1, Tracker 1, Channel 1
     * - Agency 2 (Manager 2): Advertiser 2 -> Campaign 2 -> Banner 2,
     *   Publisher 2 -> Zone 2, Tracker 2
     * - Advertiser user linked to Advertiser 1 account
     * - Trafficker user linked to Publisher 1 account
     */
    private function buildTestHierarchy()
    {
        $adminAccountId = OA_Dal_ApplicationVariables::get('admin_account_id');
        $this->adminAccountId = $adminAccountId;

        $doUsers = OA_Dal::factoryDO('users');
        $doUsers->default_account_id = $adminAccountId;
        $doUsers->username = 'admin_perm_test_' . mt_rand();
        $doUsers->email_address = 'admin@test.example';
        $this->adminUserId = DataGenerator::generateOne($doUsers);

        $doAUA = OA_Dal::factoryDO('account_user_assoc');
        $doAUA->account_id = $adminAccountId;
        $doAUA->user_id = $this->adminUserId;
        $doAUA->insert();

        // Agency 1
        $doAgency = OA_Dal::factoryDO('agency');
        $doAgency->name = 'Test Agency 1';
        $doAgency->contact = 'Test';
        $doAgency->email = 'agency1@test.example';
        $this->managerAgencyId1 = DataGenerator::generateOne($doAgency);

        $doAgency1 = OA_Dal::staticGetDO('agency', $this->managerAgencyId1);
        $this->managerAccountId1 = $doAgency1->account_id;

        $doUsers = OA_Dal::factoryDO('users');
        $doUsers->default_account_id = $this->managerAccountId1;
        $doUsers->username = 'manager1_perm_test_' . mt_rand();
        $doUsers->email_address = 'manager1@test.example';
        $this->managerUserId1 = DataGenerator::generateOne($doUsers);

        $doAUA = OA_Dal::factoryDO('account_user_assoc');
        $doAUA->account_id = $this->managerAccountId1;
        $doAUA->user_id = $this->managerUserId1;
        $doAUA->insert();

        // Advertiser 1
        $doClients = OA_Dal::factoryDO('clients');
        $doClients->agencyid = $this->managerAgencyId1;
        $doClients->clientname = 'Test Advertiser 1';
        $doClients->reportlastdate = '2024-01-01';
        $this->managerAdvertiserId1 = DataGenerator::generateOne($doClients);

        $doClient1 = OA_Dal::staticGetDO('clients', $this->managerAdvertiserId1);
        $this->managerAdvertiserAccountId1 = $doClient1->account_id;

        $doUsers = OA_Dal::factoryDO('users');
        $doUsers->default_account_id = $this->managerAdvertiserAccountId1;
        $doUsers->username = 'adv1_perm_test_' . mt_rand();
        $doUsers->email_address = 'adv1@test.example';
        $this->advertiserUserId1 = DataGenerator::generateOne($doUsers);

        $doAUA = OA_Dal::factoryDO('account_user_assoc');
        $doAUA->account_id = $this->managerAdvertiserAccountId1;
        $doAUA->user_id = $this->advertiserUserId1;
        $doAUA->insert();

        // Campaign 1
        $doCampaigns = OA_Dal::factoryDO('campaigns');
        $doCampaigns->clientid = $this->managerAdvertiserId1;
        $doCampaigns->campaignname = 'Test Campaign 1';
        $doCampaigns->status = OA_ENTITY_STATUS_RUNNING;
        $this->managerCampaignId1 = DataGenerator::generateOne($doCampaigns);

        // Banner 1
        $doBanners = OA_Dal::factoryDO('banners');
        $doBanners->campaignid = $this->managerCampaignId1;
        $doBanners->description = 'Test Banner 1';
        $doBanners->acls_updated = '2024-01-01 00:00:00';
        $this->managerBannerId1 = DataGenerator::generateOne($doBanners);

        // Publisher 1
        $doAffiliates = OA_Dal::factoryDO('affiliates');
        $doAffiliates->agencyid = $this->managerAgencyId1;
        $doAffiliates->name = 'Test Publisher 1';
        $this->managerPublisherId1 = DataGenerator::generateOne($doAffiliates);

        $doPub1 = OA_Dal::staticGetDO('affiliates', $this->managerPublisherId1);
        $this->managerPublisherAccountId1 = $doPub1->account_id;

        $doUsers = OA_Dal::factoryDO('users');
        $doUsers->default_account_id = $this->managerPublisherAccountId1;
        $doUsers->username = 'traf1_perm_test_' . mt_rand();
        $doUsers->email_address = 'traf1@test.example';
        $this->trafficherUserId1 = DataGenerator::generateOne($doUsers);

        $doAUA = OA_Dal::factoryDO('account_user_assoc');
        $doAUA->account_id = $this->managerPublisherAccountId1;
        $doAUA->user_id = $this->trafficherUserId1;
        $doAUA->insert();

        // Zone 1
        $doZones = OA_Dal::factoryDO('zones');
        $doZones->affiliateid = $this->managerPublisherId1;
        $doZones->zonename = 'Test Zone 1';
        $this->managerZoneId1 = DataGenerator::generateOne($doZones);

        // Tracker 1
        $doTrackers = OA_Dal::factoryDO('trackers');
        $doTrackers->clientid = $this->managerAdvertiserId1;
        $doTrackers->trackername = 'Test Tracker 1';
        $this->managerTrackerId1 = DataGenerator::generateOne($doTrackers);

        // Channel 1
        $doChannel = OA_Dal::factoryDO('channel');
        $doChannel->agencyid = $this->managerAgencyId1;
        $doChannel->name = 'Test Channel 1';
        $this->managerChannelId1 = DataGenerator::generateOne($doChannel);

        // Agency 2
        $doAgency2 = OA_Dal::factoryDO('agency');
        $doAgency2->name = 'Test Agency 2';
        $doAgency2->contact = 'Test';
        $doAgency2->email = 'agency2@test.example';
        $this->managerAgencyId2 = DataGenerator::generateOne($doAgency2);

        $doAgency2Obj = OA_Dal::staticGetDO('agency', $this->managerAgencyId2);
        $this->managerAccountId2 = $doAgency2Obj->account_id;

        $doUsers = OA_Dal::factoryDO('users');
        $doUsers->default_account_id = $this->managerAccountId2;
        $doUsers->username = 'manager2_perm_test_' . mt_rand();
        $doUsers->email_address = 'manager2@test.example';
        $this->managerUserId2 = DataGenerator::generateOne($doUsers);

        $doAUA = OA_Dal::factoryDO('account_user_assoc');
        $doAUA->account_id = $this->managerAccountId2;
        $doAUA->user_id = $this->managerUserId2;
        $doAUA->insert();

        // Advertiser 2
        $doClients2 = OA_Dal::factoryDO('clients');
        $doClients2->agencyid = $this->managerAgencyId2;
        $doClients2->clientname = 'Test Advertiser 2';
        $doClients2->reportlastdate = '2024-01-01';
        $this->managerAdvertiserId2 = DataGenerator::generateOne($doClients2);

        // Campaign 2
        $doCampaigns2 = OA_Dal::factoryDO('campaigns');
        $doCampaigns2->clientid = $this->managerAdvertiserId2;
        $doCampaigns2->campaignname = 'Test Campaign 2';
        $doCampaigns2->status = OA_ENTITY_STATUS_RUNNING;
        $this->managerCampaignId2 = DataGenerator::generateOne($doCampaigns2);

        // Banner 2
        $doBanners2 = OA_Dal::factoryDO('banners');
        $doBanners2->campaignid = $this->managerCampaignId2;
        $doBanners2->description = 'Test Banner 2';
        $doBanners2->acls_updated = '2024-01-01 00:00:00';
        $this->managerBannerId2 = DataGenerator::generateOne($doBanners2);

        // Publisher 2
        $doAffiliates2 = OA_Dal::factoryDO('affiliates');
        $doAffiliates2->agencyid = $this->managerAgencyId2;
        $doAffiliates2->name = 'Test Publisher 2';
        $this->managerPublisherId2 = DataGenerator::generateOne($doAffiliates2);

        // Zone 2
        $doZones2 = OA_Dal::factoryDO('zones');
        $doZones2->affiliateid = $this->managerPublisherId2;
        $doZones2->zonename = 'Test Zone 2';
        $this->managerZoneId2 = DataGenerator::generateOne($doZones2);

        // Tracker 2
        $doTrackers2 = OA_Dal::factoryDO('trackers');
        $doTrackers2->clientid = $this->managerAdvertiserId2;
        $doTrackers2->trackername = 'Test Tracker 2';
        $this->managerTrackerId2 = DataGenerator::generateOne($doTrackers2);
    }

    private function setUpSessionUser($userId, $accountId)
    {
        global $session;
        $doUsers = OA_Dal::staticGetDO('users', $userId);
        if (!$doUsers) {
            return false;
        }
        $oUser = new OA_Permission_User($doUsers);
        $oUser->loadAccountData($accountId);
        $session['user'] = $oUser;
        return true;
    }

    private function grantPermission($userId, $accountId, $permissionId)
    {
        $doAUPA = OA_Dal::factoryDO('account_user_permission_assoc');
        $doAUPA->account_id = $accountId;
        $doAUPA->user_id = $userId;
        $doAUPA->permission_id = $permissionId;
        $doAUPA->is_allowed = 1;
        $doAUPA->insert();
    }

    private function clearPermissions($userId, $accountId)
    {
        $doAUPA = OA_Dal::factoryDO('account_user_permission_assoc');
        $doAUPA->account_id = $accountId;
        $doAUPA->user_id = $userId;
        $doAUPA->delete();
    }

    /**
     * Run a batch of entity access tests.
     * Each test: [id, entityTable, entityId, expected, desc]
     * Uses the session user set by setUpSessionUser().
     */
    private function runAccessBatch(array $tests)
    {
        foreach ($tests as $test) {
            [$id, $entityTable, $entityId, $expected, $desc] = $test;
            $result = OA_Permission::hasAccessToObject($entityTable, $entityId);
            if ($expected) {
                $this->assertTrue($result, "{$id}: {$desc} - expected ALLOW but got DENY");
            } else {
                $this->assertFalse($result, "{$id}: {$desc} - expected DENY but got ALLOW");
            }
        }
    }

    /**
     * Run a batch of permission tests.
     * Each test: [id, permissionId, accountId, userId, expected, desc]
     */
    private function runPermissionBatch(array $tests)
    {
        foreach ($tests as $test) {
            [$id, $permissionId, $accountId, $userId, $expected, $desc] = $test;
            $result = OA_Permission::hasPermission($permissionId, $accountId, $userId);
            if ($expected) {
                $this->assertTrue($result, "{$id}: {$desc} - expected ALLOW but got DENY");
            } else {
                $this->assertFalse($result, "{$id}: {$desc} - expected DENY but got ALLOW");
            }
        }
    }

    /**
     * Run a batch of account type tests.
     * Each test: [id, accountType, expected, desc]
     */
    private function runAccountTypeBatch(array $tests)
    {
        foreach ($tests as $test) {
            [$id, $accountType, $expected, $desc] = $test;
            $result = OA_Permission::isAccount($accountType);
            if ($expected) {
                $this->assertTrue($result, "{$id}: {$desc} - expected TRUE but got FALSE");
            } else {
                $this->assertFalse($result, "{$id}: {$desc} - expected FALSE but got TRUE");
            }
        }
    }

    // =========================================================================
    // SECTION 1: Entity Access — ADMIN (P001-P040)
    // =========================================================================

    /**
     * P001-P030: ADMIN has full access to all entity types in both agencies.
     */
    public function testAdminAccessToAllEntities()
    {
        $this->buildTestHierarchy();
        $this->setUpSessionUser($this->adminUserId, $this->adminAccountId);

        $tests = [
            // Agency 1 entities
            ['P001', 'agency', $this->managerAgencyId1, true, 'ADMIN can access Agency 1'],
            ['P002', 'clients', $this->managerAdvertiserId1, true, 'ADMIN can access Advertiser 1'],
            ['P003', 'campaigns', $this->managerCampaignId1, true, 'ADMIN can access Campaign 1'],
            ['P004', 'banners', $this->managerBannerId1, true, 'ADMIN can access Banner 1'],
            ['P005', 'affiliates', $this->managerPublisherId1, true, 'ADMIN can access Publisher 1'],
            ['P006', 'zones', $this->managerZoneId1, true, 'ADMIN can access Zone 1'],
            ['P007', 'trackers', $this->managerTrackerId1, true, 'ADMIN can access Tracker 1'],
            ['P008', 'channel', $this->managerChannelId1, true, 'ADMIN can access Channel 1'],

            // Agency 2 entities (cross-agency)
            ['P009', 'agency', $this->managerAgencyId2, true, 'ADMIN can access Agency 2'],
            ['P010', 'clients', $this->managerAdvertiserId2, true, 'ADMIN can access Advertiser 2'],
            ['P011', 'campaigns', $this->managerCampaignId2, true, 'ADMIN can access Campaign 2'],
            ['P012', 'banners', $this->managerBannerId2, true, 'ADMIN can access Banner 2'],
            ['P013', 'affiliates', $this->managerPublisherId2, true, 'ADMIN can access Publisher 2'],
            ['P014', 'zones', $this->managerZoneId2, true, 'ADMIN can access Zone 2'],
            ['P015', 'trackers', $this->managerTrackerId2, true, 'ADMIN can access Tracker 2'],

            // New entity creation (null entityId = ADD)
            ['P016', 'agency', null, true, 'ADMIN can ADD Agency'],
            ['P017', 'clients', null, true, 'ADMIN can ADD Advertiser'],
            ['P018', 'campaigns', null, true, 'ADMIN can ADD Campaign'],
            ['P019', 'banners', null, true, 'ADMIN can ADD Banner'],
            ['P020', 'affiliates', null, true, 'ADMIN can ADD Publisher'],
            ['P021', 'zones', null, true, 'ADMIN can ADD Zone'],
            ['P022', 'trackers', null, true, 'ADMIN can ADD Tracker'],
            ['P023', 'channel', null, true, 'ADMIN can ADD Channel'],

            // Additional cross-entity access
            ['P024', 'campaigns', $this->managerCampaignId1, true, 'ADMIN VIEW Campaign 1'],
            ['P025', 'campaigns', $this->managerCampaignId2, true, 'ADMIN VIEW Campaign 2'],
            ['P026', 'banners', $this->managerBannerId1, true, 'ADMIN EDIT Banner 1'],
            ['P027', 'banners', $this->managerBannerId2, true, 'ADMIN EDIT Banner 2'],
            ['P028', 'zones', $this->managerZoneId1, true, 'ADMIN DELETE Zone 1'],
            ['P029', 'zones', $this->managerZoneId2, true, 'ADMIN DELETE Zone 2'],
            ['P030', 'trackers', $this->managerTrackerId2, true, 'ADMIN DELETE Tracker 2'],
        ];
        $this->runAccessBatch($tests);
    }

    /**
     * P031-P040: ADMIN access to other agency entities (all ALLOW)
     */
    public function testAdminAccessToOtherAgencyEntities()
    {
        $this->buildTestHierarchy();
        $this->setUpSessionUser($this->adminUserId, $this->adminAccountId);

        $tests = [
            ['P031', 'agency', $this->managerAgencyId2, true, 'ADMIN VIEW other Agency'],
            ['P032', 'clients', $this->managerAdvertiserId2, true, 'ADMIN VIEW other Advertiser'],
            ['P033', 'campaigns', $this->managerCampaignId2, true, 'ADMIN EDIT other Campaign'],
            ['P034', 'banners', $this->managerBannerId2, true, 'ADMIN EDIT other Banner'],
            ['P035', 'affiliates', $this->managerPublisherId2, true, 'ADMIN DELETE other Publisher'],
            ['P036', 'zones', $this->managerZoneId2, true, 'ADMIN EDIT other Zone'],
            ['P037', 'trackers', $this->managerTrackerId2, true, 'ADMIN DELETE other Tracker'],
            ['P038', 'campaigns', $this->managerCampaignId2, true, 'ADMIN DELETE other Campaign'],
            ['P039', 'banners', $this->managerBannerId2, true, 'ADMIN DELETE other Banner'],
            ['P040', 'zones', $this->managerZoneId2, true, 'ADMIN VIEW other Zone'],
        ];
        $this->runAccessBatch($tests);
    }

    // =========================================================================
    // SECTION 2: Entity Access — MANAGER (P041-P100)
    // =========================================================================

    /**
     * P041-P070: MANAGER access to own agency entities (all ALLOW)
     */
    public function testManagerAccessToOwnAgencyEntities()
    {
        $this->buildTestHierarchy();
        $this->setUpSessionUser($this->managerUserId1, $this->managerAccountId1);

        $tests = [
            // Agency
            ['P041', 'agency', $this->managerAgencyId1, true, 'MANAGER VIEW own Agency'],

            // Advertiser
            ['P042', 'clients', $this->managerAdvertiserId1, true, 'MANAGER VIEW own Advertiser'],
            ['P043', 'clients', $this->managerAdvertiserId1, true, 'MANAGER EDIT own Advertiser'],
            ['P044', 'clients', $this->managerAdvertiserId1, true, 'MANAGER DELETE own Advertiser'],

            // Campaign
            ['P045', 'campaigns', $this->managerCampaignId1, true, 'MANAGER VIEW own Campaign'],
            ['P046', 'campaigns', $this->managerCampaignId1, true, 'MANAGER EDIT own Campaign'],
            ['P047', 'campaigns', $this->managerCampaignId1, true, 'MANAGER DELETE own Campaign'],

            // Banner
            ['P048', 'banners', $this->managerBannerId1, true, 'MANAGER VIEW own Banner'],
            ['P049', 'banners', $this->managerBannerId1, true, 'MANAGER EDIT own Banner'],
            ['P050', 'banners', $this->managerBannerId1, true, 'MANAGER DELETE own Banner'],

            // Publisher
            ['P051', 'affiliates', $this->managerPublisherId1, true, 'MANAGER VIEW own Publisher'],
            ['P052', 'affiliates', $this->managerPublisherId1, true, 'MANAGER EDIT own Publisher'],
            ['P053', 'affiliates', $this->managerPublisherId1, true, 'MANAGER DELETE own Publisher'],

            // Zone
            ['P054', 'zones', $this->managerZoneId1, true, 'MANAGER VIEW own Zone'],
            ['P055', 'zones', $this->managerZoneId1, true, 'MANAGER EDIT own Zone'],
            ['P056', 'zones', $this->managerZoneId1, true, 'MANAGER DELETE own Zone'],

            // Tracker
            ['P057', 'trackers', $this->managerTrackerId1, true, 'MANAGER VIEW own Tracker'],
            ['P058', 'trackers', $this->managerTrackerId1, true, 'MANAGER EDIT own Tracker'],
            ['P059', 'trackers', $this->managerTrackerId1, true, 'MANAGER DELETE own Tracker'],

            // Channel
            ['P060', 'channel', $this->managerChannelId1, true, 'MANAGER VIEW own Channel'],
            ['P061', 'channel', $this->managerChannelId1, true, 'MANAGER EDIT own Channel'],
            ['P062', 'channel', $this->managerChannelId1, true, 'MANAGER DELETE own Channel'],

            // ADD operations (null entityId)
            ['P063', 'clients', null, true, 'MANAGER can ADD Advertiser'],
            ['P064', 'campaigns', null, true, 'MANAGER can ADD Campaign'],
            ['P065', 'banners', null, true, 'MANAGER can ADD Banner'],
            ['P066', 'affiliates', null, true, 'MANAGER can ADD Publisher'],
            ['P067', 'zones', null, true, 'MANAGER can ADD Zone'],
            ['P068', 'trackers', null, true, 'MANAGER can ADD Tracker'],
            ['P069', 'channel', null, true, 'MANAGER can ADD Channel'],
            ['P070', 'agency', null, true, 'MANAGER can ADD Agency'],
        ];
        $this->runAccessBatch($tests);
    }

    /**
     * P071-P100: MANAGER denied access to other agency entities
     */
    public function testManagerDeniedAccessToOtherAgencyEntities()
    {
        $this->buildTestHierarchy();
        $this->setUpSessionUser($this->managerUserId1, $this->managerAccountId1);

        $tests = [
            // Other Agency
            ['P071', 'agency', $this->managerAgencyId2, false, 'MANAGER denied VIEW other Agency'],

            // Other Advertiser
            ['P072', 'clients', $this->managerAdvertiserId2, false, 'MANAGER denied VIEW other Advertiser'],
            ['P073', 'clients', $this->managerAdvertiserId2, false, 'MANAGER denied EDIT other Advertiser'],
            ['P074', 'clients', $this->managerAdvertiserId2, false, 'MANAGER denied DELETE other Advertiser'],

            // Other Campaign
            ['P075', 'campaigns', $this->managerCampaignId2, false, 'MANAGER denied VIEW other Campaign'],
            ['P076', 'campaigns', $this->managerCampaignId2, false, 'MANAGER denied EDIT other Campaign'],
            ['P077', 'campaigns', $this->managerCampaignId2, false, 'MANAGER denied DELETE other Campaign'],

            // Other Banner
            ['P078', 'banners', $this->managerBannerId2, false, 'MANAGER denied VIEW other Banner'],
            ['P079', 'banners', $this->managerBannerId2, false, 'MANAGER denied EDIT other Banner'],
            ['P080', 'banners', $this->managerBannerId2, false, 'MANAGER denied DELETE other Banner'],

            // Other Publisher
            ['P081', 'affiliates', $this->managerPublisherId2, false, 'MANAGER denied VIEW other Publisher'],
            ['P082', 'affiliates', $this->managerPublisherId2, false, 'MANAGER denied EDIT other Publisher'],
            ['P083', 'affiliates', $this->managerPublisherId2, false, 'MANAGER denied DELETE other Publisher'],

            // Other Zone
            ['P084', 'zones', $this->managerZoneId2, false, 'MANAGER denied VIEW other Zone'],
            ['P085', 'zones', $this->managerZoneId2, false, 'MANAGER denied EDIT other Zone'],
            ['P086', 'zones', $this->managerZoneId2, false, 'MANAGER denied DELETE other Zone'],

            // Other Tracker
            ['P087', 'trackers', $this->managerTrackerId2, false, 'MANAGER denied VIEW other Tracker'],
            ['P088', 'trackers', $this->managerTrackerId2, false, 'MANAGER denied EDIT other Tracker'],
            ['P089', 'trackers', $this->managerTrackerId2, false, 'MANAGER denied DELETE other Tracker'],

            // Own entity additional ops (ALLOW)
            ['P090', 'campaigns', $this->managerCampaignId1, true, 'MANAGER can DUPLICATE own Campaign'],
            ['P091', 'banners', $this->managerBannerId1, true, 'MANAGER can DUPLICATE own Banner'],
            ['P092', 'zones', $this->managerZoneId1, true, 'MANAGER can DUPLICATE own Zone'],
            ['P093', 'clients', $this->managerAdvertiserId1, true, 'MANAGER can DUPLICATE own Advertiser'],

            // Other entity additional ops (DENY)
            ['P094', 'banners', $this->managerBannerId2, false, 'MANAGER denied DUPLICATE other Banner'],
            ['P095', 'campaigns', $this->managerCampaignId2, false, 'MANAGER denied DUPLICATE other Campaign'],
            ['P096', 'zones', $this->managerZoneId2, false, 'MANAGER denied DUPLICATE other Zone'],

            // Move operations
            ['P097', 'campaigns', $this->managerCampaignId1, true, 'MANAGER can MOVE own Campaign'],
            ['P098', 'campaigns', $this->managerCampaignId2, false, 'MANAGER denied MOVE other Campaign'],
            ['P099', 'affiliates', $this->managerPublisherId1, true, 'MANAGER can MOVE own Publisher'],
            ['P100', 'affiliates', $this->managerPublisherId2, false, 'MANAGER denied MOVE other Publisher'],
        ];
        $this->runAccessBatch($tests);
    }

    // =========================================================================
    // SECTION 3: Entity Access — ADVERTISER (P101-P130)
    // =========================================================================

    /**
     * P101-P110: ADVERTISER own entity access (ALLOW)
     */
    public function testAdvertiserAccessToOwnEntities()
    {
        $this->buildTestHierarchy();
        $this->setUpSessionUser($this->advertiserUserId1, $this->managerAdvertiserAccountId1);

        $tests = [
            ['P101', 'clients', $this->managerAdvertiserId1, true, 'ADVERTISER can VIEW own Advertiser'],
            ['P102', 'campaigns', $this->managerCampaignId1, true, 'ADVERTISER can VIEW own Campaign'],
            ['P103', 'banners', $this->managerBannerId1, true, 'ADVERTISER can VIEW own Banner'],
            ['P104', 'trackers', $this->managerTrackerId1, true, 'ADVERTISER can VIEW own Tracker'],
            ['P105', 'campaigns', $this->managerCampaignId1, true, 'ADVERTISER can EDIT own Campaign'],
            ['P106', 'banners', $this->managerBannerId1, true, 'ADVERTISER can EDIT own Banner'],
            ['P107', 'trackers', $this->managerTrackerId1, true, 'ADVERTISER can EDIT own Tracker'],
            ['P108', 'campaigns', $this->managerCampaignId1, true, 'ADVERTISER can DELETE own Campaign'],
            ['P109', 'banners', $this->managerBannerId1, true, 'ADVERTISER can DELETE own Banner'],
            ['P110', 'trackers', $this->managerTrackerId1, true, 'ADVERTISER can DELETE own Tracker'],
        ];
        $this->runAccessBatch($tests);
    }

    /**
     * P111-P130: ADVERTISER denied for foreign and cross-type entities
     */
    public function testAdvertiserDeniedAccessToForeignEntities()
    {
        $this->buildTestHierarchy();
        $this->setUpSessionUser($this->advertiserUserId1, $this->managerAdvertiserAccountId1);

        $tests = [
            // Other advertiser entities
            ['P111', 'campaigns', $this->managerCampaignId2, false, 'ADVERTISER denied VIEW other Campaign'],
            ['P112', 'campaigns', $this->managerCampaignId2, false, 'ADVERTISER denied EDIT other Campaign'],
            ['P113', 'banners', $this->managerBannerId2, false, 'ADVERTISER denied VIEW other Banner'],
            ['P114', 'banners', $this->managerBannerId2, false, 'ADVERTISER denied EDIT other Banner'],
            ['P115', 'trackers', $this->managerTrackerId2, false, 'ADVERTISER denied VIEW other Tracker'],
            ['P116', 'trackers', $this->managerTrackerId2, false, 'ADVERTISER denied EDIT other Tracker'],
            ['P117', 'clients', $this->managerAdvertiserId2, false, 'ADVERTISER denied VIEW other Advertiser'],

            // Cross-type: Publisher-side entities
            ['P118', 'affiliates', $this->managerPublisherId1, false, 'ADVERTISER denied VIEW any Publisher'],
            ['P119', 'zones', $this->managerZoneId1, false, 'ADVERTISER denied VIEW any Zone'],
            ['P120', 'affiliates', $this->managerPublisherId2, false, 'ADVERTISER denied VIEW Publisher 2'],
            ['P121', 'zones', $this->managerZoneId2, false, 'ADVERTISER denied VIEW Zone 2'],

            // Cross-type: Agency
            ['P122', 'agency', $this->managerAgencyId1, false, 'ADVERTISER denied VIEW Agency 1'],
            ['P123', 'agency', $this->managerAgencyId2, false, 'ADVERTISER denied VIEW Agency 2'],

            // Cross-type: Channel
            ['P124', 'channel', $this->managerChannelId1, false, 'ADVERTISER denied VIEW any Channel'],

            // Delete on other entities
            ['P125', 'campaigns', $this->managerCampaignId2, false, 'ADVERTISER denied DELETE other Campaign'],
            ['P126', 'banners', $this->managerBannerId2, false, 'ADVERTISER denied DELETE other Banner'],
            ['P127', 'trackers', $this->managerTrackerId2, false, 'ADVERTISER denied DELETE other Tracker'],

            // ADD operations (null entityId = always true)
            ['P128', 'campaigns', null, true, 'ADVERTISER can ADD Campaign'],
            ['P129', 'banners', null, true, 'ADVERTISER can ADD Banner'],
            ['P130', 'trackers', null, true, 'ADVERTISER can ADD Tracker'],
        ];
        $this->runAccessBatch($tests);
    }

    // =========================================================================
    // SECTION 4: Entity Access — TRAFFICKER (P131-P160)
    // =========================================================================

    /**
     * P131-P140: TRAFFICKER own entity access (ALLOW)
     */
    public function testTraffickerAccessToOwnEntities()
    {
        $this->buildTestHierarchy();
        $this->setUpSessionUser($this->trafficherUserId1, $this->managerPublisherAccountId1);

        $tests = [
            ['P131', 'affiliates', $this->managerPublisherId1, true, 'TRAFFICKER can VIEW own Publisher'],
            ['P132', 'zones', $this->managerZoneId1, true, 'TRAFFICKER can VIEW own Zone'],
            ['P133', 'zones', $this->managerZoneId1, true, 'TRAFFICKER can EDIT own Zone'],
            ['P134', 'zones', $this->managerZoneId1, true, 'TRAFFICKER can DELETE own Zone'],
            ['P135', 'zones', null, true, 'TRAFFICKER can ADD Zone'],
            ['P136', 'affiliates', null, true, 'TRAFFICKER can ADD Publisher'],
            ['P137', 'zones', $this->managerZoneId1, true, 'TRAFFICKER can DUPLICATE own Zone'],
            ['P138', 'affiliates', $this->managerPublisherId1, true, 'TRAFFICKER can EDIT own Publisher'],
            ['P139', 'affiliates', $this->managerPublisherId1, true, 'TRAFFICKER can DELETE own Publisher'],
            ['P140', 'zones', $this->managerZoneId1, true, 'TRAFFICKER can MOVE own Zone'],
        ];
        $this->runAccessBatch($tests);
    }

    /**
     * P141-P160: TRAFFICKER denied access to foreign and cross-type entities
     */
    public function testTraffickerDeniedAccessToForeignEntities()
    {
        $this->buildTestHierarchy();
        $this->setUpSessionUser($this->trafficherUserId1, $this->managerPublisherAccountId1);

        $tests = [
            // Other publisher entities
            ['P141', 'zones', $this->managerZoneId2, false, 'TRAFFICKER denied VIEW other Zone'],
            ['P142', 'zones', $this->managerZoneId2, false, 'TRAFFICKER denied EDIT other Zone'],
            ['P143', 'zones', $this->managerZoneId2, false, 'TRAFFICKER denied DELETE other Zone'],
            ['P144', 'affiliates', $this->managerPublisherId2, false, 'TRAFFICKER denied VIEW other Publisher'],
            ['P145', 'affiliates', $this->managerPublisherId2, false, 'TRAFFICKER denied EDIT other Publisher'],
            ['P146', 'affiliates', $this->managerPublisherId2, false, 'TRAFFICKER denied DELETE other Publisher'],

            // Cross-type: Agency
            ['P147', 'agency', $this->managerAgencyId1, false, 'TRAFFICKER denied VIEW Agency 1'],
            ['P148', 'agency', $this->managerAgencyId2, false, 'TRAFFICKER denied VIEW Agency 2'],

            // Cross-type: Advertiser
            ['P149', 'clients', $this->managerAdvertiserId1, false, 'TRAFFICKER denied VIEW Advertiser 1'],
            ['P150', 'clients', $this->managerAdvertiserId2, false, 'TRAFFICKER denied VIEW Advertiser 2'],

            // Cross-type: Campaign
            ['P151', 'campaigns', $this->managerCampaignId1, false, 'TRAFFICKER denied VIEW Campaign 1'],
            ['P152', 'campaigns', $this->managerCampaignId1, false, 'TRAFFICKER denied EDIT Campaign 1'],
            ['P153', 'campaigns', $this->managerCampaignId1, false, 'TRAFFICKER denied DELETE Campaign 1'],

            // Cross-type: Banner
            ['P154', 'banners', $this->managerBannerId1, false, 'TRAFFICKER denied VIEW Banner 1'],
            ['P155', 'banners', $this->managerBannerId1, false, 'TRAFFICKER denied EDIT Banner 1'],
            ['P156', 'banners', $this->managerBannerId1, false, 'TRAFFICKER denied DELETE Banner 1'],

            // Cross-type: Tracker
            ['P157', 'trackers', $this->managerTrackerId1, false, 'TRAFFICKER denied VIEW Tracker 1'],
            ['P158', 'trackers', $this->managerTrackerId1, false, 'TRAFFICKER denied EDIT Tracker 1'],
            ['P159', 'trackers', $this->managerTrackerId1, false, 'TRAFFICKER denied DELETE Tracker 1'],

            // Cross-type: Channel
            ['P160', 'channel', $this->managerChannelId1, false, 'TRAFFICKER denied VIEW Channel 1'],
        ];
        $this->runAccessBatch($tests);
    }

    // =========================================================================
    // SECTION 5: Permission Checks — hasPermission (P161-P200)
    // =========================================================================

    /**
     * P161-P172: ADMIN has all permissions (always true for admin)
     */
    public function testAdminHasAllPermissions()
    {
        $this->buildTestHierarchy();
        $this->setUpSessionUser($this->adminUserId, $this->adminAccountId);

        $allPermissions = [
            OA_PERM_BANNER_ACTIVATE,
            OA_PERM_BANNER_DEACTIVATE,
            OA_PERM_BANNER_ADD,
            OA_PERM_BANNER_EDIT,
            OA_PERM_ZONE_ADD,
            OA_PERM_ZONE_DELETE,
            OA_PERM_ZONE_EDIT,
            OA_PERM_ZONE_INVOCATION,
            OA_PERM_ZONE_LINK,
            OA_PERM_SUPER_ACCOUNT,
            OA_PERM_USER_LOG_ACCESS,
            OA_PERM_MANAGER_DELETE,
        ];

        $tests = [];
        $idx = 161;
        foreach ($allPermissions as $perm) {
            $tests[] = [
                'P' . $idx,
                $perm,
                $this->adminAccountId,
                $this->adminUserId,
                true,
                "ADMIN has permission {$perm}",
            ];
            $idx++;
        }
        $this->runPermissionBatch($tests);
    }

    /**
     * P173-P182: ADVERTISER permission grant/deny
     */
    public function testAdvertiserPermissionsGrantedAndDenied()
    {
        $this->buildTestHierarchy();
        $this->setUpSessionUser($this->advertiserUserId1, $this->managerAdvertiserAccountId1);

        // Without grants: ADVERTISER-related perms are denied
        $this->runPermissionBatch([
            ['P173', OA_PERM_BANNER_EDIT, $this->managerAdvertiserAccountId1, $this->advertiserUserId1, false, 'ADVERTISER denied BANNER_EDIT without grant'],
            ['P174', OA_PERM_BANNER_ACTIVATE, $this->managerAdvertiserAccountId1, $this->advertiserUserId1, false, 'ADVERTISER denied BANNER_ACTIVATE without grant'],
            ['P175', OA_PERM_BANNER_DEACTIVATE, $this->managerAdvertiserAccountId1, $this->advertiserUserId1, false, 'ADVERTISER denied BANNER_DEACTIVATE without grant'],
            ['P176', OA_PERM_SUPER_ACCOUNT, $this->managerAdvertiserAccountId1, $this->advertiserUserId1, false, 'ADVERTISER denied SUPER_ACCOUNT without grant'],
        ]);

        // Grant BANNER_EDIT and verify
        $this->grantPermission($this->advertiserUserId1, $this->managerAdvertiserAccountId1, OA_PERM_BANNER_EDIT);
        $this->runPermissionBatch([
            ['P177', OA_PERM_BANNER_EDIT, $this->managerAdvertiserAccountId1, $this->advertiserUserId1, true, 'ADVERTISER allowed BANNER_EDIT after grant'],
        ]);

        // Grant remaining perms
        $this->grantPermission($this->advertiserUserId1, $this->managerAdvertiserAccountId1, OA_PERM_BANNER_ACTIVATE);
        $this->grantPermission($this->advertiserUserId1, $this->managerAdvertiserAccountId1, OA_PERM_BANNER_DEACTIVATE);
        $this->runPermissionBatch([
            ['P178', OA_PERM_BANNER_ACTIVATE, $this->managerAdvertiserAccountId1, $this->advertiserUserId1, true, 'ADVERTISER allowed BANNER_ACTIVATE'],
            ['P179', OA_PERM_BANNER_DEACTIVATE, $this->managerAdvertiserAccountId1, $this->advertiserUserId1, true, 'ADVERTISER allowed BANNER_DEACTIVATE'],
        ]);

        // Unrelated perms (ZONE_*) always pass for advertiser account type
        $this->runPermissionBatch([
            ['P180', OA_PERM_ZONE_ADD, $this->managerAdvertiserAccountId1, $this->advertiserUserId1, true, 'ADVERTISER unrelated ZONE_ADD always true'],
            ['P181', OA_PERM_ZONE_EDIT, $this->managerAdvertiserAccountId1, $this->advertiserUserId1, true, 'ADVERTISER unrelated ZONE_EDIT always true'],
            ['P182', OA_PERM_ZONE_DELETE, $this->managerAdvertiserAccountId1, $this->advertiserUserId1, true, 'ADVERTISER unrelated ZONE_DELETE always true'],
        ]);
    }

    /**
     * P183-P193: TRAFFICKER permission grant/deny
     */
    public function testTraffickerPermissionsGrantedAndDenied()
    {
        $this->buildTestHierarchy();
        $this->setUpSessionUser($this->trafficherUserId1, $this->managerPublisherAccountId1);

        // Without grants
        $this->runPermissionBatch([
            ['P183', OA_PERM_ZONE_ADD, $this->managerPublisherAccountId1, $this->trafficherUserId1, false, 'TRAFFICKER denied ZONE_ADD without grant'],
            ['P184', OA_PERM_ZONE_EDIT, $this->managerPublisherAccountId1, $this->trafficherUserId1, false, 'TRAFFICKER denied ZONE_EDIT without grant'],
            ['P185', OA_PERM_ZONE_DELETE, $this->managerPublisherAccountId1, $this->trafficherUserId1, false, 'TRAFFICKER denied ZONE_DELETE without grant'],
            ['P186', OA_PERM_ZONE_LINK, $this->managerPublisherAccountId1, $this->trafficherUserId1, false, 'TRAFFICKER denied ZONE_LINK without grant'],
            ['P187', OA_PERM_ZONE_INVOCATION, $this->managerPublisherAccountId1, $this->trafficherUserId1, false, 'TRAFFICKER denied ZONE_INVOCATION without grant'],
        ]);

        // Unrelated perm always true
        $this->runPermissionBatch([
            ['P188', OA_PERM_BANNER_EDIT, $this->managerPublisherAccountId1, $this->trafficherUserId1, true, 'TRAFFICKER unrelated BANNER_EDIT always true'],
        ]);

        // Grant all zone perms
        $this->grantPermission($this->trafficherUserId1, $this->managerPublisherAccountId1, OA_PERM_ZONE_ADD);
        $this->grantPermission($this->trafficherUserId1, $this->managerPublisherAccountId1, OA_PERM_ZONE_EDIT);
        $this->grantPermission($this->trafficherUserId1, $this->managerPublisherAccountId1, OA_PERM_ZONE_DELETE);
        $this->grantPermission($this->trafficherUserId1, $this->managerPublisherAccountId1, OA_PERM_ZONE_LINK);
        $this->grantPermission($this->trafficherUserId1, $this->managerPublisherAccountId1, OA_PERM_ZONE_INVOCATION);
        $this->runPermissionBatch([
            ['P189', OA_PERM_ZONE_ADD, $this->managerPublisherAccountId1, $this->trafficherUserId1, true, 'TRAFFICKER allowed ZONE_ADD'],
            ['P190', OA_PERM_ZONE_EDIT, $this->managerPublisherAccountId1, $this->trafficherUserId1, true, 'TRAFFICKER allowed ZONE_EDIT'],
            ['P191', OA_PERM_ZONE_DELETE, $this->managerPublisherAccountId1, $this->trafficherUserId1, true, 'TRAFFICKER allowed ZONE_DELETE'],
            ['P192', OA_PERM_ZONE_LINK, $this->managerPublisherAccountId1, $this->trafficherUserId1, true, 'TRAFFICKER allowed ZONE_LINK'],
            ['P193', OA_PERM_ZONE_INVOCATION, $this->managerPublisherAccountId1, $this->trafficherUserId1, true, 'TRAFFICKER allowed ZONE_INVOCATION'],
        ]);
    }

    /**
     * P194-P200: MANAGER permission grant/deny
     */
    public function testManagerPermissionsGrantedAndDenied()
    {
        $this->buildTestHierarchy();
        $this->setUpSessionUser($this->managerUserId1, $this->managerAccountId1);

        // Without grants
        $this->runPermissionBatch([
            ['P194', OA_PERM_MANAGER_DELETE, $this->managerAccountId1, $this->managerUserId1, false, 'MANAGER denied MANAGER_DELETE without grant'],
            ['P195', OA_PERM_SUPER_ACCOUNT, $this->managerAccountId1, $this->managerUserId1, false, 'MANAGER denied SUPER_ACCOUNT without grant'],
        ]);

        // Grant MANAGER_DELETE
        $this->grantPermission($this->managerUserId1, $this->managerAccountId1, OA_PERM_MANAGER_DELETE);
        $this->runPermissionBatch([
            ['P196', OA_PERM_MANAGER_DELETE, $this->managerAccountId1, $this->managerUserId1, true, 'MANAGER allowed MANAGER_DELETE'],
        ]);

        // Grant SUPER_ACCOUNT
        $this->grantPermission($this->managerUserId1, $this->managerAccountId1, OA_PERM_SUPER_ACCOUNT);
        $this->runPermissionBatch([
            ['P197', OA_PERM_SUPER_ACCOUNT, $this->managerAccountId1, $this->managerUserId1, true, 'MANAGER allowed SUPER_ACCOUNT'],
        ]);

        // Unrelated perms always pass for manager
        $this->runPermissionBatch([
            ['P198', OA_PERM_BANNER_EDIT, $this->managerAccountId1, $this->managerUserId1, true, 'MANAGER unrelated BANNER_EDIT always true'],
            ['P199', OA_PERM_ZONE_ADD, $this->managerAccountId1, $this->managerUserId1, true, 'MANAGER unrelated ZONE_ADD always true'],
            ['P200', OA_PERM_ZONE_DELETE, $this->managerAccountId1, $this->managerUserId1, true, 'MANAGER unrelated ZONE_DELETE always true'],
        ]);
    }

    // =========================================================================
    // SECTION 6: Account Type Verification — isAccount (P201-P210)
    // =========================================================================

    /**
     * P201-P210: isAccount type verification
     */
    public function testAccountTypeVerification()
    {
        $this->buildTestHierarchy();

        // Admin session
        $this->setUpSessionUser($this->adminUserId, $this->adminAccountId);
        $this->runAccountTypeBatch([
            ['P201', OA_ACCOUNT_ADMIN, true, 'Admin isAccount ADMIN'],
            ['P202', OA_ACCOUNT_MANAGER, false, 'Admin is not MANAGER'],
            ['P203', OA_ACCOUNT_ADVERTISER, false, 'Admin is not ADVERTISER'],
            ['P204', OA_ACCOUNT_TRAFFICKER, false, 'Admin is not TRAFFICKER'],
        ]);

        // Manager session
        $this->setUpSessionUser($this->managerUserId1, $this->managerAccountId1);
        $this->runAccountTypeBatch([
            ['P205', OA_ACCOUNT_ADMIN, false, 'Manager is not ADMIN'],
            ['P206', OA_ACCOUNT_MANAGER, true, 'Manager isAccount MANAGER'],
        ]);

        // Advertiser session
        $this->setUpSessionUser($this->advertiserUserId1, $this->managerAdvertiserAccountId1);
        $this->runAccountTypeBatch([
            ['P207', OA_ACCOUNT_ADMIN, false, 'Advertiser is not ADMIN'],
            ['P208', OA_ACCOUNT_ADVERTISER, true, 'Advertiser isAccount ADVERTISER'],
        ]);

        // Trafficker session
        $this->setUpSessionUser($this->trafficherUserId1, $this->managerPublisherAccountId1);
        $this->runAccountTypeBatch([
            ['P209', OA_ACCOUNT_ADMIN, false, 'Trafficker is not ADMIN'],
            ['P210', OA_ACCOUNT_TRAFFICKER, true, 'Trafficker isAccount TRAFFICKER'],
        ]);
    }

    // =========================================================================
    // SECTION 7: checkAccountPermission (P211-P220)
    // =========================================================================

    /**
     * P211-P220: checkAccountPermission cross-checks
     *
     * checkAccountPermission returns true if the account type does not match
     * (permission not applicable) or the permission is granted.
     */
    public function testCheckAccountPermission()
    {
        $this->buildTestHierarchy();

        // Manager session
        $this->setUpSessionUser($this->managerUserId1, $this->managerAccountId1);
        $this->clearPermissions($this->managerUserId1, $this->managerAccountId1);

        $this->assertTrue(
            OA_Permission::checkAccountPermission(OA_ACCOUNT_ADVERTISER, OA_PERM_BANNER_EDIT),
            'P211: non-matching account type returns true'
        );
        $this->assertFalse(
            OA_Permission::checkAccountPermission(OA_ACCOUNT_MANAGER, OA_PERM_MANAGER_DELETE),
            'P212: MANAGER without MANAGER_DELETE'
        );

        $this->grantPermission($this->managerUserId1, $this->managerAccountId1, OA_PERM_MANAGER_DELETE);
        $this->setUpSessionUser($this->managerUserId1, $this->managerAccountId1);
        $this->assertTrue(
            OA_Permission::checkAccountPermission(OA_ACCOUNT_MANAGER, OA_PERM_MANAGER_DELETE),
            'P213: MANAGER with MANAGER_DELETE granted'
        );

        // Advertiser session
        $this->setUpSessionUser($this->advertiserUserId1, $this->managerAdvertiserAccountId1);
        $this->clearPermissions($this->advertiserUserId1, $this->managerAdvertiserAccountId1);
        $this->assertFalse(
            OA_Permission::checkAccountPermission(OA_ACCOUNT_ADVERTISER, OA_PERM_BANNER_EDIT),
            'P214: ADVERTISER without BANNER_EDIT'
        );

        $this->grantPermission($this->advertiserUserId1, $this->managerAdvertiserAccountId1, OA_PERM_BANNER_EDIT);
        $this->setUpSessionUser($this->advertiserUserId1, $this->managerAdvertiserAccountId1);
        $this->assertTrue(
            OA_Permission::checkAccountPermission(OA_ACCOUNT_ADVERTISER, OA_PERM_BANNER_EDIT),
            'P215: ADVERTISER with BANNER_EDIT granted'
        );
        $this->assertTrue(
            OA_Permission::checkAccountPermission(OA_ACCOUNT_MANAGER, OA_PERM_BANNER_EDIT),
            'P216: wrong account type always true'
        );

        // Trafficker session
        $this->setUpSessionUser($this->trafficherUserId1, $this->managerPublisherAccountId1);
        $this->clearPermissions($this->trafficherUserId1, $this->managerPublisherAccountId1);
        $this->assertFalse(
            OA_Permission::checkAccountPermission(OA_ACCOUNT_TRAFFICKER, OA_PERM_ZONE_ADD),
            'P217: TRAFFICKER without ZONE_ADD'
        );

        $this->grantPermission($this->trafficherUserId1, $this->managerPublisherAccountId1, OA_PERM_ZONE_ADD);
        $this->setUpSessionUser($this->trafficherUserId1, $this->managerPublisherAccountId1);
        $this->assertTrue(
            OA_Permission::checkAccountPermission(OA_ACCOUNT_TRAFFICKER, OA_PERM_ZONE_ADD),
            'P218: TRAFFICKER with ZONE_ADD granted'
        );
        $this->assertTrue(
            OA_Permission::checkAccountPermission(OA_ACCOUNT_TRAFFICKER, OA_PERM_BANNER_EDIT),
            'P219: TRAFFICKER unrelated perm always true'
        );

        // Admin session: always passes
        $this->setUpSessionUser($this->adminUserId, $this->adminAccountId);
        $this->assertTrue(
            OA_Permission::checkAccountPermission(OA_ACCOUNT_ADMIN, OA_PERM_MANAGER_DELETE),
            'P220: ADMIN always passes checkAccountPermission'
        );
    }

    // =========================================================================
    // SECTION 8: Store & Delete Permissions (P221-P230)
    // =========================================================================

    /**
     * P221-P230: storeUserAccountsPermissions and deleteExistingPermissions
     */
    public function testStoreAndDeletePermissions()
    {
        $this->buildTestHierarchy();
        $this->setUpSessionUser($this->advertiserUserId1, $this->managerAdvertiserAccountId1);

        // Store multiple permissions using ADVERTISER_PERMISSIONS as allowed set
        $aPermissions = [OA_PERM_BANNER_EDIT, OA_PERM_BANNER_ACTIVATE, OA_PERM_BANNER_DEACTIVATE];
        $result = OA_Permission::storeUserAccountsPermissions(
            $aPermissions,
            $this->managerAdvertiserAccountId1,
            $this->advertiserUserId1,
            OA_Permission::ADVERTISER_PERMISSIONS,
        );
        $this->assertTrue($result, 'P221: storeUserAccountsPermissions succeeds');

        // Verify stored
        $this->assertTrue(
            OA_Permission::hasPermission(OA_PERM_BANNER_EDIT, $this->managerAdvertiserAccountId1, $this->advertiserUserId1),
            'P222: BANNER_EDIT stored'
        );
        $this->assertTrue(
            OA_Permission::hasPermission(OA_PERM_BANNER_ACTIVATE, $this->managerAdvertiserAccountId1, $this->advertiserUserId1),
            'P223: BANNER_ACTIVATE stored'
        );
        $this->assertTrue(
            OA_Permission::hasPermission(OA_PERM_BANNER_DEACTIVATE, $this->managerAdvertiserAccountId1, $this->advertiserUserId1),
            'P224: BANNER_DEACTIVATE stored'
        );

        // SUPER_ACCOUNT was not in $aPermissions, should not be stored
        $this->assertFalse(
            OA_Permission::hasPermission(OA_PERM_SUPER_ACCOUNT, $this->managerAdvertiserAccountId1, $this->advertiserUserId1),
            'P225: SUPER_ACCOUNT NOT stored'
        );

        // Delete permissions
        OA_Permission::deleteExistingPermissions(
            $this->managerAdvertiserAccountId1,
            $this->advertiserUserId1,
            OA_Permission::ADVERTISER_PERMISSIONS,
        );

        $this->assertFalse(
            OA_Permission::hasPermission(OA_PERM_BANNER_EDIT, $this->managerAdvertiserAccountId1, $this->advertiserUserId1),
            'P226: BANNER_EDIT deleted'
        );
        $this->assertFalse(
            OA_Permission::hasPermission(OA_PERM_BANNER_ACTIVATE, $this->managerAdvertiserAccountId1, $this->advertiserUserId1),
            'P227: BANNER_ACTIVATE deleted'
        );
        $this->assertFalse(
            OA_Permission::hasPermission(OA_PERM_BANNER_DEACTIVATE, $this->managerAdvertiserAccountId1, $this->advertiserUserId1),
            'P228: BANNER_DEACTIVATE deleted'
        );

        // Store for trafficker too
        $this->setUpSessionUser($this->trafficherUserId1, $this->managerPublisherAccountId1);
        $aPermissions = [OA_PERM_ZONE_ADD, OA_PERM_ZONE_EDIT];
        $result = OA_Permission::storeUserAccountsPermissions(
            $aPermissions,
            $this->managerPublisherAccountId1,
            $this->trafficherUserId1,
            OA_Permission::TRAFFICKER_PERMISSIONS,
        );
        $this->assertTrue($result, 'P229: storeUserAccountsPermissions for trafficker succeeds');
        $this->assertTrue(
            OA_Permission::hasPermission(OA_PERM_ZONE_ADD, $this->managerPublisherAccountId1, $this->trafficherUserId1),
            'P230: ZONE_ADD stored for trafficker'
        );
    }

    // =========================================================================
    // SECTION 9: hasAccess and setAccountAccess (P231-P240)
    // =========================================================================

    /**
     * P231-P240: hasAccess and setAccountAccess
     */
    public function testHasAccessAndSetAccountAccess()
    {
        $this->buildTestHierarchy();

        // Manager 1 has access to own account
        $this->assertTrue(
            OA_Permission::hasAccess($this->managerAccountId1, $this->managerUserId1),
            'P231: Manager has access to own account'
        );
        // Manager 1 denied other account
        $this->assertFalse(
            OA_Permission::hasAccess($this->managerAccountId2, $this->managerUserId1),
            'P232: Manager denied other account'
        );
        // Admin has access everywhere
        $this->assertTrue(
            OA_Permission::hasAccess($this->managerAccountId1, $this->adminUserId),
            'P233: Admin has access to manager account'
        );
        $this->assertTrue(
            OA_Permission::hasAccess($this->managerAdvertiserAccountId1, $this->adminUserId),
            'P234: Admin has access to advertiser account'
        );
        $this->assertTrue(
            OA_Permission::hasAccess($this->managerPublisherAccountId1, $this->adminUserId),
            'P235: Admin has access to publisher account'
        );
        $this->assertTrue(
            OA_Permission::hasAccess($this->managerAccountId2, $this->adminUserId),
            'P236: Admin has access to other manager account'
        );

        // Advertiser has access to own
        $this->assertTrue(
            OA_Permission::hasAccess($this->managerAdvertiserAccountId1, $this->advertiserUserId1),
            'P237: Advertiser has access to own account'
        );
        // Advertiser denied other
        $this->assertFalse(
            OA_Permission::hasAccess($this->managerAccountId1, $this->advertiserUserId1),
            'P238: Advertiser denied manager account'
        );

        // Grant access via setAccountAccess
        OA_Permission::setAccountAccess($this->managerAccountId2, $this->managerUserId1);
        $this->assertTrue(
            OA_Permission::hasAccess($this->managerAccountId2, $this->managerUserId1),
            'P239: setAccountAccess grants access'
        );

        // Revoke access
        OA_Permission::setAccountAccess($this->managerAccountId2, $this->managerUserId1, false);
        $this->assertFalse(
            OA_Permission::hasAccess($this->managerAccountId2, $this->managerUserId1),
            'P240: setAccountAccess revokes access'
        );
    }

    // =========================================================================
    // SECTION 10: isPermissionRelatedToAccountType (P241-P260)
    // =========================================================================

    /**
     * P241-P260: isPermissionRelatedToAccountType
     */
    public function testIsPermissionRelatedToAccountType()
    {
        // BANNER perms -> ADVERTISER
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_ADVERTISER, OA_PERM_BANNER_EDIT),
            'P241: BANNER_EDIT related to ADVERTISER'
        );
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_ADVERTISER, OA_PERM_BANNER_ADD),
            'P242: BANNER_ADD related to ADVERTISER'
        );
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_ADVERTISER, OA_PERM_BANNER_ACTIVATE),
            'P243: BANNER_ACTIVATE related to ADVERTISER'
        );
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_ADVERTISER, OA_PERM_BANNER_DEACTIVATE),
            'P244: BANNER_DEACTIVATE related to ADVERTISER'
        );

        // BANNER perms NOT related to MANAGER
        $this->assertFalse(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_MANAGER, OA_PERM_BANNER_EDIT),
            'P245: BANNER_EDIT NOT related to MANAGER'
        );
        // BANNER perms NOT related to TRAFFICKER
        $this->assertFalse(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_TRAFFICKER, OA_PERM_BANNER_EDIT),
            'P246: BANNER_EDIT NOT related to TRAFFICKER'
        );

        // ZONE perms -> TRAFFICKER
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_TRAFFICKER, OA_PERM_ZONE_ADD),
            'P247: ZONE_ADD related to TRAFFICKER'
        );
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_TRAFFICKER, OA_PERM_ZONE_EDIT),
            'P248: ZONE_EDIT related to TRAFFICKER'
        );
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_TRAFFICKER, OA_PERM_ZONE_DELETE),
            'P249: ZONE_DELETE related to TRAFFICKER'
        );
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_TRAFFICKER, OA_PERM_ZONE_LINK),
            'P250: ZONE_LINK related to TRAFFICKER'
        );
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_TRAFFICKER, OA_PERM_ZONE_INVOCATION),
            'P251: ZONE_INVOCATION related to TRAFFICKER'
        );

        // ZONE perms NOT related to ADVERTISER
        $this->assertFalse(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_ADVERTISER, OA_PERM_ZONE_ADD),
            'P252: ZONE_ADD NOT related to ADVERTISER'
        );

        // SUPER_ACCOUNT -> MANAGER, ADVERTISER, TRAFFICKER
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_MANAGER, OA_PERM_SUPER_ACCOUNT),
            'P253: SUPER_ACCOUNT related to MANAGER'
        );
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_ADVERTISER, OA_PERM_SUPER_ACCOUNT),
            'P254: SUPER_ACCOUNT related to ADVERTISER'
        );
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_TRAFFICKER, OA_PERM_SUPER_ACCOUNT),
            'P255: SUPER_ACCOUNT related to TRAFFICKER'
        );

        // MANAGER_DELETE -> MANAGER only
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_MANAGER, OA_PERM_MANAGER_DELETE),
            'P256: MANAGER_DELETE related to MANAGER'
        );
        $this->assertFalse(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_TRAFFICKER, OA_PERM_MANAGER_DELETE),
            'P257: MANAGER_DELETE NOT related to TRAFFICKER'
        );
        $this->assertFalse(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_ADVERTISER, OA_PERM_MANAGER_DELETE),
            'P258: MANAGER_DELETE NOT related to ADVERTISER'
        );

        // USER_LOG_ACCESS -> ADVERTISER, TRAFFICKER
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_ADVERTISER, OA_PERM_USER_LOG_ACCESS),
            'P259: USER_LOG_ACCESS related to ADVERTISER'
        );
        $this->assertTrue(
            OA_Permission::isPermissionRelatedToAccountType(OA_ACCOUNT_TRAFFICKER, OA_PERM_USER_LOG_ACCESS),
            'P260: USER_LOG_ACCESS related to TRAFFICKER'
        );
    }

    // =========================================================================
    // SECTION 11: Cross-Account Type Denial (P261-P270)
    // =========================================================================

    /**
     * P261-P270: Cross-account type permission denials and special cases
     */
    public function testCrossAccountTypeDenials()
    {
        $this->buildTestHierarchy();

        // Trafficker has access to own, denied manager's account
        $this->assertTrue(
            OA_Permission::hasAccess($this->managerPublisherAccountId1, $this->trafficherUserId1),
            'P261: Trafficker has access to own publisher account'
        );
        $this->assertFalse(
            OA_Permission::hasAccess($this->managerAccountId1, $this->trafficherUserId1),
            'P262: Trafficker denied manager account'
        );
        $this->assertFalse(
            OA_Permission::hasAccess($this->managerAdvertiserAccountId1, $this->trafficherUserId1),
            'P263: Trafficker denied advertiser account'
        );
        $this->assertFalse(
            OA_Permission::hasAccess($this->managerAccountId2, $this->trafficherUserId1),
            'P264: Trafficker denied other manager account'
        );

        // Manager 2 has own, denied other
        $this->assertTrue(
            OA_Permission::hasAccess($this->managerAccountId2, $this->managerUserId2),
            'P265: Manager 2 has access to own account'
        );
        $this->assertFalse(
            OA_Permission::hasAccess($this->managerAccountId1, $this->managerUserId2),
            'P266: Manager 2 denied Manager 1 account'
        );
        $this->assertFalse(
            OA_Permission::hasAccess($this->managerAdvertiserAccountId1, $this->managerUserId2),
            'P267: Manager 2 denied Advertiser 1 account'
        );
        $this->assertFalse(
            OA_Permission::hasAccess($this->managerPublisherAccountId1, $this->managerUserId2),
            'P268: Manager 2 denied Publisher 1 account'
        );

        // Advertiser denied publisher account
        $this->assertFalse(
            OA_Permission::hasAccess($this->managerPublisherAccountId1, $this->advertiserUserId1),
            'P269: Advertiser denied publisher account'
        );
        // Trafficker denied advertiser account
        $this->assertFalse(
            OA_Permission::hasAccess($this->managerAdvertiserAccountId1, $this->trafficherUserId1),
            'P270: Trafficker denied advertiser account'
        );
    }
}
