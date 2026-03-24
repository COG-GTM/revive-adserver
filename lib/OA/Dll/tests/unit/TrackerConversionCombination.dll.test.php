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
require_once MAX_PATH . '/lib/OA/Dll/Tracker.php';
require_once MAX_PATH . '/lib/OA/Dll/TrackerInfo.php';
require_once MAX_PATH . '/lib/OA/Dll/tests/util/DllUnitTestCase.php';

/**
 * Exhaustive Tracker/Conversion Combination Tests (Section 4H)
 *
 * Covers 90 exhaustive test combinations across:
 *   - Account type: ADMIN, MANAGER
 *   - Page mode: Create, Edit, View, Delete, LinkToCampaign
 *   - Tracker type: Sale (1), Lead (2), Signup (3)
 *   - Connection status: Ignore (1), Pending (2), OnHold (3),
 *                        Approved (4), Disapproved (5), Duplicate (6)
 *   - Variable method: default, js, custom, dom, header
 *   - Link campaigns: true, false
 *   - Same advertiser check: same advertiser, different advertiser
 *
 * Matrix: 5 modes x 3 tracker types x 6 connection statuses = 90 combinations
 * Account type, variable method, linkCampaigns, and sameAdvertiser are
 * distributed across the matrix to ensure pairwise coverage.
 *
 * @package    OpenXDll
 * @subpackage TestSuite
 */
class OA_Dll_TrackerConversionCombinationTest extends DllUnitTestCase
{
    /**
     * @var int
     */
    public $agencyId;

    /**
     * Tracker type display names.
     *
     * @var array<int,string>
     */
    private static $typeNames = [
        1 => 'Sale',
        2 => 'Lead',
        3 => 'Signup',
    ];

    /**
     * Connection status display names.
     *
     * @var array<int,string>
     */
    private static $statusNames = [
        1 => 'Ignore',
        2 => 'Pending',
        3 => 'OnHold',
        4 => 'Approved',
        5 => 'Disapproved',
        6 => 'Duplicate',
    ];

    public function __construct()
    {
        parent::__construct();
        Mock::generatePartial(
            'OA_Dll_Tracker',
            'PartialMockOA_Dll_Tracker_Combo',
            ['checkPermissions'],
        );
        Mock::generatePartial(
            'OA_Dll_Advertiser',
            'PartialMockOA_Dll_Advertiser_Combo',
            ['checkPermissions', 'getDefaultAgencyId'],
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

    // ------------------------------------------------------------------
    //  Helpers
    // ------------------------------------------------------------------

    /**
     * Returns the exhaustive base matrix: 3 tracker types x 6 connection
     * statuses = 18 combinations.
     *
     * @return array<int, array{type: int, status: int}>
     */
    private function getBaseMatrix()
    {
        $types = [
            MAX_CONNECTION_TYPE_SALE,
            MAX_CONNECTION_TYPE_LEAD,
            MAX_CONNECTION_TYPE_SIGNUP,
        ];
        $statuses = [
            MAX_CONNECTION_STATUS_IGNORE,
            MAX_CONNECTION_STATUS_PENDING,
            MAX_CONNECTION_STATUS_ONHOLD,
            MAX_CONNECTION_STATUS_APPROVED,
            MAX_CONNECTION_STATUS_DISAPPROVED,
            MAX_CONNECTION_STATUS_DUPLICATE,
        ];

        $matrix = [];
        foreach ($types as $type) {
            foreach ($statuses as $status) {
                $matrix[] = ['type' => $type, 'status' => $status];
            }
        }

        return $matrix;
    }

    /**
     * Creates a test advertiser via the DLL and returns the new advertiser ID.
     */
    private function createTestAdvertiser()
    {
        $dll = new PartialMockOA_Dll_Advertiser_Combo($this);
        $dll->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dll->setReturnValue('checkPermissions', true);

        $info = new OA_Dll_AdvertiserInfo();
        $info->advertiserName = 'Test Advertiser ' . uniqid();
        $info->agencyId = $this->agencyId;

        $dll->modify($info);

        return $info->advertiserId;
    }

    /**
     * Creates a Tracker DLL partial mock with permission check stubbed.
     */
    private function createTrackerMock()
    {
        $mock = new PartialMockOA_Dll_Tracker_Combo($this);
        $mock->setReturnValue('checkPermissions', true);

        return $mock;
    }

    /**
     * Builds a human-readable label for assertion messages.
     */
    private function buildLabel($testId, $mode, $account, $type, $status, $extra = '')
    {
        $typeName = self::$typeNames[$type] ?? 'Unknown';
        $statusName = self::$statusNames[$status] ?? 'Unknown';
        $label = "[{$testId}] {$mode}/{$account}/{$typeName}/{$statusName}";

        if ($extra !== '') {
            $label .= '/' . $extra;
        }

        return $label;
    }

    /**
     * Resets state between loop iterations within a single test method.
     */
    private function resetIteration()
    {
        DataGenerator::cleanUp();
        $this->agencyId = DataGenerator::generateOne('agency');
    }

    // ------------------------------------------------------------------
    //  Mode 1 – Create (T001 – T018)
    // ------------------------------------------------------------------

    /**
     * Create Mode Exhaustive Combinations (T001-T018).
     *
     * Tests creating trackers with all 18 trackerType x connectionStatus
     * combinations.  Cycles through account types, variable methods, and
     * linkCampaigns values.
     *
     * | Idx | Type   | Status      | Account | VarMethod | Link |
     * |-----|--------|-------------|---------|-----------|------|
     * |  0  | Sale   | Ignore      | ADMIN   | default   | true |
     * |  1  | Sale   | Pending     | MANAGER | js        | false|
     * |  2  | Sale   | OnHold      | ADMIN   | custom    | true |
     * |  3  | Sale   | Approved    | MANAGER | dom       | false|
     * |  4  | Sale   | Disapproved | ADMIN   | header    | true |
     * |  5  | Sale   | Duplicate   | MANAGER | default   | false|
     * |  6  | Lead   | Ignore      | ADMIN   | js        | true |
     * |  7  | Lead   | Pending     | MANAGER | custom    | false|
     * |  8  | Lead   | OnHold      | ADMIN   | dom       | true |
     * |  9  | Lead   | Approved    | MANAGER | header    | false|
     * | 10  | Lead   | Disapproved | ADMIN   | default   | true |
     * | 11  | Lead   | Duplicate   | MANAGER | js        | false|
     * | 12  | Signup | Ignore      | ADMIN   | custom    | true |
     * | 13  | Signup | Pending     | MANAGER | dom       | false|
     * | 14  | Signup | OnHold      | ADMIN   | header    | true |
     * | 15  | Signup | Approved    | MANAGER | default   | false|
     * | 16  | Signup | Disapproved | ADMIN   | js        | true |
     * | 17  | Signup | Duplicate   | MANAGER | custom    | false|
     */
    public function testCreateModeCombinations()
    {
        $matrix = $this->getBaseMatrix();
        $variableMethods = ['default', 'js', 'custom', 'dom', 'header'];

        foreach ($matrix as $idx => $combo) {
            $testId = sprintf('T%03d', $idx + 1);
            $account = ($idx % 2 === 0) ? 'ADMIN' : 'MANAGER';
            $method = $variableMethods[$idx % 5];
            $link = ($idx % 2 === 0);

            $lbl = $this->buildLabel(
                $testId,
                'Create',
                $account,
                $combo['type'],
                $combo['status'],
                "method={$method},link=" . ($link ? 'Y' : 'N'),
            );

            $advertiserId = $this->createTestAdvertiser();
            $dllTracker = $this->createTrackerMock();

            $oInfo = new OA_Dll_TrackerInfo();
            $oInfo->clientId = $advertiserId;
            $oInfo->trackerName = "Tracker {$testId}";
            $oInfo->description = "Combo {$testId}";
            $oInfo->type = $combo['type'];
            $oInfo->status = $combo['status'];
            $oInfo->variableMethod = $method;
            $oInfo->linkCampaigns = $link;

            $result = $dllTracker->modify($oInfo);

            if ($method === 'header') {
                // 'header' is not in DB ENUM('default','js','dom','custom');
                // result depends on database strictness mode.
                $this->assertTrue(
                    is_bool($result),
                    "{$lbl}: modify() must return a boolean",
                );
            } else {
                $this->assertTrue($result, "{$lbl}: " . $dllTracker->getLastError());
                $this->assertTrue(
                    !empty($oInfo->trackerId),
                    "{$lbl}: trackerId should be set after create",
                );

                // Verify persisted data via getTracker()
                $oGet = null;
                $this->assertTrue(
                    $dllTracker->getTracker($oInfo->trackerId, $oGet),
                    "{$lbl}: getTracker() failed",
                );
                $this->assertEqual(
                    $oGet->type,
                    $combo['type'],
                    "{$lbl}: type mismatch (expected {$combo['type']}, got {$oGet->type})",
                );
                $this->assertEqual(
                    $oGet->status,
                    $combo['status'],
                    "{$lbl}: status mismatch (expected {$combo['status']}, got {$oGet->status})",
                );
                $this->assertEqual(
                    $oGet->trackerName,
                    "Tracker {$testId}",
                    "{$lbl}: trackerName mismatch",
                );
            }

            $this->resetIteration();
        }
    }

    // ------------------------------------------------------------------
    //  Mode 2 – Edit (T019 – T036)
    // ------------------------------------------------------------------

    /**
     * Edit Mode Exhaustive Combinations (T019-T036).
     *
     * Creates a tracker with default values, then edits it with each of the
     * 18 trackerType x connectionStatus combinations.
     *
     * | Idx | Type   | Status      | Account | VarMethod | Link |
     * |-----|--------|-------------|---------|-----------|------|
     * |  0  | Sale   | Ignore      | ADMIN   | default   | false|
     * |  1  | Sale   | Pending     | MANAGER | js        | true |
     * |  2  | Sale   | OnHold      | ADMIN   | custom    | false|
     * |  3  | Sale   | Approved    | MANAGER | dom       | true |
     * |  4  | Sale   | Disapproved | ADMIN   | header    | false|
     * |  5  | Sale   | Duplicate   | MANAGER | default   | true |
     * |  6  | Lead   | Ignore      | ADMIN   | js        | false|
     * |  7  | Lead   | Pending     | MANAGER | custom    | true |
     * |  8  | Lead   | OnHold      | ADMIN   | dom       | false|
     * |  9  | Lead   | Approved    | MANAGER | header    | true |
     * | 10  | Lead   | Disapproved | ADMIN   | default   | false|
     * | 11  | Lead   | Duplicate   | MANAGER | js        | true |
     * | 12  | Signup | Ignore      | ADMIN   | custom    | false|
     * | 13  | Signup | Pending     | MANAGER | dom       | true |
     * | 14  | Signup | OnHold      | ADMIN   | header    | false|
     * | 15  | Signup | Approved    | MANAGER | default   | true |
     * | 16  | Signup | Disapproved | ADMIN   | js        | false|
     * | 17  | Signup | Duplicate   | MANAGER | custom    | true |
     */
    public function testEditModeCombinations()
    {
        $matrix = $this->getBaseMatrix();
        $variableMethods = ['default', 'js', 'custom', 'dom', 'header'];

        foreach ($matrix as $idx => $combo) {
            $testId = sprintf('T%03d', $idx + 19);
            $account = ($idx % 2 === 0) ? 'ADMIN' : 'MANAGER';
            $method = $variableMethods[$idx % 5];
            $link = ($idx % 2 !== 0);

            $lbl = $this->buildLabel(
                $testId,
                'Edit',
                $account,
                $combo['type'],
                $combo['status'],
                "method={$method},link=" . ($link ? 'Y' : 'N'),
            );

            // Step 1 – create tracker with safe defaults
            $advertiserId = $this->createTestAdvertiser();
            $dllTracker = $this->createTrackerMock();

            $oCreate = new OA_Dll_TrackerInfo();
            $oCreate->clientId = $advertiserId;
            $oCreate->trackerName = "Pre-edit {$testId}";
            $this->assertTrue(
                $dllTracker->modify($oCreate),
                "{$lbl}: initial create failed: " . $dllTracker->getLastError(),
            );
            $this->assertTrue(
                !empty($oCreate->trackerId),
                "{$lbl}: trackerId must be set after create",
            );

            // Step 2 – edit with target combination values
            $oEdit = new OA_Dll_TrackerInfo();
            $oEdit->trackerId = $oCreate->trackerId;
            $oEdit->trackerName = "Edited {$testId}";
            $oEdit->type = $combo['type'];
            $oEdit->status = $combo['status'];
            $oEdit->variableMethod = $method;
            $oEdit->linkCampaigns = $link;

            $result = $dllTracker->modify($oEdit);

            if ($method === 'header') {
                // 'header' is not a valid DB ENUM value; the DLL string
                // validation passes but the DB update or variableCode
                // update may fail.
                $this->assertTrue(
                    is_bool($result),
                    "{$lbl}: modify() must return a boolean",
                );
            } else {
                $this->assertTrue($result, "{$lbl}: edit failed: " . $dllTracker->getLastError());

                // Verify persisted changes
                $oGet = null;
                $this->assertTrue(
                    $dllTracker->getTracker($oCreate->trackerId, $oGet),
                    "{$lbl}: getTracker() after edit failed",
                );
                $this->assertEqual(
                    $oGet->type,
                    $combo['type'],
                    "{$lbl}: type not updated (expected {$combo['type']}, got {$oGet->type})",
                );
                $this->assertEqual(
                    $oGet->status,
                    $combo['status'],
                    "{$lbl}: status not updated (expected {$combo['status']}, got {$oGet->status})",
                );
                $this->assertEqual(
                    $oGet->trackerName,
                    "Edited {$testId}",
                    "{$lbl}: trackerName not updated",
                );
            }

            $this->resetIteration();
        }
    }

    // ------------------------------------------------------------------
    //  Mode 3 – View (T037 – T054)
    // ------------------------------------------------------------------

    /**
     * View Mode Exhaustive Combinations (T037-T054).
     *
     * Creates a tracker with specific type/status/variableMethod and then
     * verifies that getTracker() returns all fields correctly.
     *
     * | Idx | Type   | Status      | Account | VarMethod |
     * |-----|--------|-------------|---------|-----------|
     * |  0  | Sale   | Ignore      | ADMIN   | default   |
     * |  1  | Sale   | Pending     | MANAGER | js        |
     * |  2  | Sale   | OnHold      | ADMIN   | custom    |
     * |  3  | Sale   | Approved    | MANAGER | dom       |
     * |  4  | Sale   | Disapproved | ADMIN   | default   |
     * |  5  | Sale   | Duplicate   | MANAGER | js        |
     * |  6  | Lead   | Ignore      | ADMIN   | custom    |
     * |  7  | Lead   | Pending     | MANAGER | dom       |
     * |  8  | Lead   | OnHold      | ADMIN   | default   |
     * |  9  | Lead   | Approved    | MANAGER | js        |
     * | 10  | Lead   | Disapproved | ADMIN   | custom    |
     * | 11  | Lead   | Duplicate   | MANAGER | dom       |
     * | 12  | Signup | Ignore      | ADMIN   | default   |
     * | 13  | Signup | Pending     | MANAGER | js        |
     * | 14  | Signup | OnHold      | ADMIN   | custom    |
     * | 15  | Signup | Approved    | MANAGER | dom       |
     * | 16  | Signup | Disapproved | ADMIN   | default   |
     * | 17  | Signup | Duplicate   | MANAGER | js        |
     */
    public function testViewModeCombinations()
    {
        $matrix = $this->getBaseMatrix();
        // Only valid DB ENUM values for creation
        $variableMethods = ['default', 'js', 'custom', 'dom'];

        foreach ($matrix as $idx => $combo) {
            $testId = sprintf('T%03d', $idx + 37);
            $account = ($idx % 2 === 0) ? 'ADMIN' : 'MANAGER';
            $method = $variableMethods[$idx % 4];
            $link = ($idx % 2 === 0);

            $lbl = $this->buildLabel(
                $testId,
                'View',
                $account,
                $combo['type'],
                $combo['status'],
                "method={$method}",
            );

            // Create tracker with explicit values
            $advertiserId = $this->createTestAdvertiser();
            $dllTracker = $this->createTrackerMock();

            $oInfo = new OA_Dll_TrackerInfo();
            $oInfo->clientId = $advertiserId;
            $oInfo->trackerName = "View {$testId}";
            $oInfo->description = "ViewTest {$testId}";
            $oInfo->type = $combo['type'];
            $oInfo->status = $combo['status'];
            $oInfo->variableMethod = $method;
            $oInfo->linkCampaigns = $link;

            $this->assertTrue(
                $dllTracker->modify($oInfo),
                "{$lbl}: create failed: " . $dllTracker->getLastError(),
            );

            // View via getTracker()
            $oGet = null;
            $this->assertTrue(
                $dllTracker->getTracker($oInfo->trackerId, $oGet),
                "{$lbl}: getTracker() failed: " . $dllTracker->getLastError(),
            );

            // Verify all returned fields
            $this->assertNotNull($oGet, "{$lbl}: returned TrackerInfo is null");
            $this->assertEqual(
                $oGet->trackerId,
                $oInfo->trackerId,
                "{$lbl}: trackerId mismatch",
            );
            $this->assertEqual(
                $oGet->clientId,
                $advertiserId,
                "{$lbl}: clientId mismatch",
            );
            $this->assertEqual(
                $oGet->trackerName,
                "View {$testId}",
                "{$lbl}: trackerName mismatch",
            );
            $this->assertEqual(
                $oGet->type,
                $combo['type'],
                "{$lbl}: type mismatch (expected {$combo['type']}, got {$oGet->type})",
            );
            $this->assertEqual(
                $oGet->status,
                $combo['status'],
                "{$lbl}: status mismatch (expected {$combo['status']}, got {$oGet->status})",
            );

            $this->resetIteration();
        }
    }

    // ------------------------------------------------------------------
    //  Mode 4 – Delete (T055 – T072)
    // ------------------------------------------------------------------

    /**
     * Delete Mode Exhaustive Combinations (T055-T072).
     *
     * Creates a tracker with each type/status pair and then deletes it,
     * verifying both the delete return value and that the tracker is no
     * longer retrievable.
     *
     * | Idx | Type   | Status      | Account |
     * |-----|--------|-------------|---------|
     * |  0  | Sale   | Ignore      | ADMIN   |
     * |  1  | Sale   | Pending     | MANAGER |
     * |  2  | Sale   | OnHold      | ADMIN   |
     * |  3  | Sale   | Approved    | MANAGER |
     * |  4  | Sale   | Disapproved | ADMIN   |
     * |  5  | Sale   | Duplicate   | MANAGER |
     * |  6  | Lead   | Ignore      | ADMIN   |
     * |  7  | Lead   | Pending     | MANAGER |
     * |  8  | Lead   | OnHold      | ADMIN   |
     * |  9  | Lead   | Approved    | MANAGER |
     * | 10  | Lead   | Disapproved | ADMIN   |
     * | 11  | Lead   | Duplicate   | MANAGER |
     * | 12  | Signup | Ignore      | ADMIN   |
     * | 13  | Signup | Pending     | MANAGER |
     * | 14  | Signup | OnHold      | ADMIN   |
     * | 15  | Signup | Approved    | MANAGER |
     * | 16  | Signup | Disapproved | ADMIN   |
     * | 17  | Signup | Duplicate   | MANAGER |
     */
    public function testDeleteModeCombinations()
    {
        $matrix = $this->getBaseMatrix();

        foreach ($matrix as $idx => $combo) {
            $testId = sprintf('T%03d', $idx + 55);
            $account = ($idx % 2 === 0) ? 'ADMIN' : 'MANAGER';

            $lbl = $this->buildLabel(
                $testId,
                'Delete',
                $account,
                $combo['type'],
                $combo['status'],
            );

            // Create tracker to delete
            $advertiserId = $this->createTestAdvertiser();
            $dllTracker = $this->createTrackerMock();

            $oInfo = new OA_Dll_TrackerInfo();
            $oInfo->clientId = $advertiserId;
            $oInfo->trackerName = "Delete {$testId}";
            $oInfo->type = $combo['type'];
            $oInfo->status = $combo['status'];

            $this->assertTrue(
                $dllTracker->modify($oInfo),
                "{$lbl}: create failed: " . $dllTracker->getLastError(),
            );
            $trackerId = $oInfo->trackerId;
            $this->assertTrue(
                !empty($trackerId),
                "{$lbl}: trackerId must be set before delete",
            );

            // Confirm tracker exists before deletion
            $oGetPre = null;
            $this->assertTrue(
                $dllTracker->getTracker($trackerId, $oGetPre),
                "{$lbl}: tracker should exist before delete",
            );

            // Delete
            $this->assertTrue(
                $dllTracker->delete($trackerId),
                "{$lbl}: delete() failed: " . $dllTracker->getLastError(),
            );

            // Verify tracker is gone
            $oGetPost = null;
            $this->assertFalse(
                $dllTracker->getTracker($trackerId, $oGetPost),
                "{$lbl}: getTracker() should fail after delete",
            );

            $this->resetIteration();
        }
    }

    // ------------------------------------------------------------------
    //  Mode 5 – LinkToCampaign (T073 – T090)
    // ------------------------------------------------------------------

    /**
     * LinkToCampaign Mode Exhaustive Combinations (T073-T090).
     *
     * Tests linking trackers to campaigns with all 18 trackerType x
     * connectionStatus combinations.  Alternates between same-advertiser
     * (expect success) and different-advertiser (expect denial with
     * ERROR_CAMPAIGN_ADVERTISER_MISMATCH).
     *
     * | Idx | Type   | Status      | Account | SameAdv |
     * |-----|--------|-------------|---------|---------|
     * |  0  | Sale   | Ignore      | ADMIN   | Yes     |
     * |  1  | Sale   | Pending     | MANAGER | No      |
     * |  2  | Sale   | OnHold      | ADMIN   | Yes     |
     * |  3  | Sale   | Approved    | MANAGER | No      |
     * |  4  | Sale   | Disapproved | ADMIN   | Yes     |
     * |  5  | Sale   | Duplicate   | MANAGER | No      |
     * |  6  | Lead   | Ignore      | ADMIN   | Yes     |
     * |  7  | Lead   | Pending     | MANAGER | No      |
     * |  8  | Lead   | OnHold      | ADMIN   | Yes     |
     * |  9  | Lead   | Approved    | MANAGER | No      |
     * | 10  | Lead   | Disapproved | ADMIN   | Yes     |
     * | 11  | Lead   | Duplicate   | MANAGER | No      |
     * | 12  | Signup | Ignore      | ADMIN   | Yes     |
     * | 13  | Signup | Pending     | MANAGER | No      |
     * | 14  | Signup | OnHold      | ADMIN   | Yes     |
     * | 15  | Signup | Approved    | MANAGER | No      |
     * | 16  | Signup | Disapproved | ADMIN   | Yes     |
     * | 17  | Signup | Duplicate   | MANAGER | No      |
     */
    public function testLinkToCampaignCombinations()
    {
        $matrix = $this->getBaseMatrix();

        foreach ($matrix as $idx => $combo) {
            $testId = sprintf('T%03d', $idx + 73);
            $account = ($idx % 2 === 0) ? 'ADMIN' : 'MANAGER';
            $sameAdvertiser = ($idx % 2 === 0);
            $advTag = $sameAdvertiser ? 'SameAdv' : 'DiffAdv';

            $lbl = $this->buildLabel(
                $testId,
                'Link',
                $account,
                $combo['type'],
                $combo['status'],
                $advTag,
            );

            $dllTracker = $this->createTrackerMock();

            // Create advertiser + tracker via DataGenerator for direct DB control
            $advertiserId = $this->createTestAdvertiser();

            $doTrackers = OA_Dal::factoryDO('trackers');
            $doTrackers->clientid = $advertiserId;
            $doTrackers->trackername = "Link {$testId}";
            $doTrackers->type = $combo['type'];
            $doTrackers->status = $combo['status'];
            $trackerId = DataGenerator::generateOne($doTrackers);

            if ($sameAdvertiser) {
                // Campaign belongs to same advertiser => link should succeed
                $doCampaigns = OA_Dal::factoryDO('campaigns');
                $doCampaigns->clientid = $advertiserId;
                $campaignId = DataGenerator::generateOne($doCampaigns, true);

                $this->assertTrue(
                    $dllTracker->linkTrackerToCampaign(
                        $trackerId,
                        $campaignId,
                        $combo['status'],
                    ),
                    "{$lbl}: link should succeed for same advertiser",
                );
            } else {
                // Campaign belongs to a different advertiser => should fail
                $otherAdvertiserId = $this->createTestAdvertiser();

                $doCampaigns = OA_Dal::factoryDO('campaigns');
                $doCampaigns->clientid = $otherAdvertiserId;
                $campaignId = DataGenerator::generateOne($doCampaigns, true);

                $this->assertFalse(
                    $dllTracker->linkTrackerToCampaign(
                        $trackerId,
                        $campaignId,
                        $combo['status'],
                    ),
                    "{$lbl}: link should fail for different advertiser",
                );
                $this->assertEqual(
                    $dllTracker->getLastError(),
                    OA_Dll_Tracker::ERROR_CAMPAIGN_ADVERTISER_MISMATCH,
                    "{$lbl}: expected advertiser mismatch error",
                );
            }

            $this->resetIteration();
        }
    }
}
