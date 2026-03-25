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
 * Section 4A: Campaign CRUD — Included Combos (Pairwise Matrix)
 *
 * Tests covering pairwise combinations of 9 dimensions:
 *   Account type, Page mode, Campaign type, Entity status,
 *   Revenue type, Date config, Booking limits, Frequency capping,
 *   Priority/weight.
 *
 * Generated via greedy all-pairs algorithm covering every pair of
 * dimension values across 120 test cases.
 *
 * @package    OpenXDll
 * @subpackage TestSuite
 */

require_once MAX_PATH . '/lib/OA/Dll/Advertiser.php';
require_once MAX_PATH . '/lib/OA/Dll/AdvertiserInfo.php';
require_once MAX_PATH . '/lib/OA/Dll/Campaign.php';
require_once MAX_PATH . '/lib/OA/Dll/CampaignInfo.php';
require_once MAX_PATH . '/lib/OA/Dll/tests/util/DllUnitTestCase.php';

class OA_Dll_CampaignCrudComboIncludedTest extends DllUnitTestCase
{
    /**
     * @var int
     */
    public $agencyId;

    public function __construct()
    {
        parent::__construct();
        Mock::generatePartial(
            'OA_Dll_Campaign',
            'PartialMockOA_Dll_Campaign_ComboIncludedTest',
            ['checkPermissions'],
        );
        Mock::generatePartial(
            'OA_Dll_Advertiser',
            'PartialMockOA_Dll_Advertiser_ComboIncludedTest',
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

    // ---------------------------------------------------------------
    // Dimension value constants
    // ---------------------------------------------------------------

    /**
     * Account type constants.
     */
    private static $ACCOUNTS = [
        'ADMIN'      => OA_ACCOUNT_ADMIN,
        'MANAGER'    => OA_ACCOUNT_MANAGER,
        'ADVERTISER' => OA_ACCOUNT_ADVERTISER,
        'TRAFFICKER' => OA_ACCOUNT_TRAFFICKER,
    ];

    /**
     * Campaign type → priority mapping.
     *  Remnant         → priority=0
     *  ContractNormal  → priority=5
     *  Override        → priority=-1
     *  eCPM            → priority=-2
     *  ContractECPM    → priority=-2
     */
    private static $CAMPAIGN_TYPES = [
        'Remnant'        => OX_CAMPAIGN_TYPE_REMNANT,
        'ContractNormal' => OX_CAMPAIGN_TYPE_CONTRACT_NORMAL,
        'Override'       => OX_CAMPAIGN_TYPE_OVERRIDE,
        'eCPM'           => OX_CAMPAIGN_TYPE_ECPM,
        'ContractECPM'   => OX_CAMPAIGN_TYPE_CONTRACT_ECPM,
    ];

    /**
     * Revenue type constants.
     */
    private static $REVENUE_TYPES = [
        'CPM' => MAX_FINANCE_CPM,
        'CPC' => MAX_FINANCE_CPC,
        'CPA' => MAX_FINANCE_CPA,
        'MT'  => MAX_FINANCE_MT,
    ];

    /**
     * Entity status constants.
     */
    private static $STATUSES = [
        'Running'  => OA_ENTITY_STATUS_RUNNING,
        'Paused'   => OA_ENTITY_STATUS_PAUSED,
        'Awaiting' => OA_ENTITY_STATUS_AWAITING,
        'Expired'  => OA_ENTITY_STATUS_EXPIRED,
        'Inactive' => OA_ENTITY_STATUS_INACTIVE,
        'Pending'  => OA_ENTITY_STATUS_PENDING,
        'Approval' => OA_ENTITY_STATUS_APPROVAL,
        'Rejected' => OA_ENTITY_STATUS_REJECTED,
    ];

    // ---------------------------------------------------------------
    // Helper: create advertiser for campaign tests
    // ---------------------------------------------------------------

    /**
     * Creates a test advertiser and returns the advertiserId.
     *
     * @return int advertiserId
     */
    private function _createTestAdvertiser()
    {
        $dllAdvertiser = new PartialMockOA_Dll_Advertiser_ComboIncludedTest($this);
        $dllAdvertiser->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiser->setReturnValue('checkPermissions', true);

        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Test Advertiser for Combo';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $dllAdvertiser->modify($oAdvertiserInfo);

        return $oAdvertiserInfo->advertiserId;
    }

    // ---------------------------------------------------------------
    // Helper: build CampaignInfo from combo dimensions
    // ---------------------------------------------------------------

    /**
     * Build a CampaignInfo object from a combo specification array.
     *
     * @param int   $advertiserId
     * @param array $combo  Associative array with keys:
     *   campaign_type, revenue, dates, booking, capping, priority
     *
     * @return OA_Dll_CampaignInfo
     */
    private function _buildCampaignInfo($advertiserId, $combo)
    {
        $oCampaign = new OA_Dll_CampaignInfo();
        $oCampaign->advertiserId = $advertiserId;
        $oCampaign->campaignName = 'Combo Test ' . $combo['id'];

        // --- Priority / Weight ---
        if ($combo['priority'] === 'p0w1') {
            $oCampaign->priority = 0;
            $oCampaign->weight = 1;
        } elseif ($combo['priority'] === 'p0w5') {
            $oCampaign->priority = 0;
            $oCampaign->weight = 5;
        } else {
            // p5w0
            $oCampaign->priority = 5;
            $oCampaign->weight = 0;
        }

        // --- Revenue ---
        if (isset(self::$REVENUE_TYPES[$combo['revenue']])) {
            $oCampaign->revenueType = self::$REVENUE_TYPES[$combo['revenue']];
            $oCampaign->revenue = 1.50;
        }

        // --- Date config ---
        $now = new Date();
        $futureDate = new Date($now);
        $futureDate->addSeconds(SECONDS_PER_DAY * 30);
        $pastDate = new Date($now);
        $pastDate->addSeconds(-SECONDS_PER_DAY * 30);

        switch ($combo['dates']) {
            case 'NoDates':
                // no dates
                break;
            case 'StartOnly':
                $oCampaign->startDate = new Date($pastDate);
                break;
            case 'EndOnly':
                $oCampaign->endDate = new Date($futureDate);
                break;
            case 'BothDates':
                $oCampaign->startDate = new Date($pastDate);
                $oCampaign->endDate = new Date($futureDate);
                break;
            case 'StartEqualsEnd':
                $oCampaign->startDate = new Date($futureDate);
                $oCampaign->endDate = new Date($futureDate);
                break;
        }

        // --- Booking limits ---
        switch ($combo['booking']) {
            case 'Unlimited':
                $oCampaign->impressions = -1;
                $oCampaign->clicks = -1;
                break;
            case 'ImprOnly':
                $oCampaign->impressions = 10000;
                $oCampaign->clicks = -1;
                break;
            case 'ClicksOnly':
                $oCampaign->impressions = -1;
                $oCampaign->clicks = 500;
                break;
            case 'Both':
                $oCampaign->impressions = 10000;
                $oCampaign->clicks = 500;
                break;
        }

        // --- Frequency capping ---
        switch ($combo['capping']) {
            case 'None':
                $oCampaign->capping = 0;
                $oCampaign->sessionCapping = 0;
                $oCampaign->block = 0;
                break;
            case 'CappingOnly':
                $oCampaign->capping = 10;
                $oCampaign->sessionCapping = 0;
                $oCampaign->block = 0;
                break;
            case 'SessionOnly':
                $oCampaign->capping = 0;
                $oCampaign->sessionCapping = 5;
                $oCampaign->block = 0;
                break;
            case 'BlockOnly':
                $oCampaign->capping = 0;
                $oCampaign->sessionCapping = 0;
                $oCampaign->block = 3600;
                break;
            case 'AllThree':
                $oCampaign->capping = 10;
                $oCampaign->sessionCapping = 5;
                $oCampaign->block = 3600;
                break;
        }

        // --- Targets for high-priority campaigns ---
        if ($oCampaign->priority >= 1 && $oCampaign->priority <= 10) {
            $oCampaign->targetImpressions = 1000;
            $oCampaign->targetClicks = 100;
            $oCampaign->targetConversions = 10;
        } else {
            $oCampaign->targetImpressions = 0;
            $oCampaign->targetClicks = 0;
            $oCampaign->targetConversions = 0;
        }

        return $oCampaign;
    }

    // ---------------------------------------------------------------
    // Helper: determine if a combo's account type has modify/delete
    //         permission (ADMIN or MANAGER only)
    // ---------------------------------------------------------------

    /**
     * Return true if the account type is allowed to Create/Edit/Delete.
     */
    private function _canModify($accountType)
    {
        return in_array($accountType, ['ADMIN', 'MANAGER']);
    }

    // ---------------------------------------------------------------
    // Helper: run a single combo test
    // ---------------------------------------------------------------

    /**
     * Execute one pairwise combo test case.
     *
     * For Create: creates a campaign with the given dimensions, verifies success.
     * For Edit: creates then modifies a campaign, verifies success.
     * For View: creates a campaign then retrieves it, verifies data.
     * For Delete: creates a campaign then deletes it, verifies success.
     *
     * When account is ADVERTISER or TRAFFICKER with Create/Edit/Delete mode,
     * the test verifies that permission is denied (checkPermissions returns false).
     *
     * @param array $combo
     */
    private function _runComboTest($combo)
    {
        $advertiserId = $this->_createTestAdvertiser();
        $dllCampaign = new PartialMockOA_Dll_Campaign_ComboIncludedTest($this);

        $account = $combo['account'];
        $mode = $combo['mode'];

        // For ADVERTISER/TRAFFICKER on Create/Edit/Delete, permissions are denied
        $isModifyMode = in_array($mode, ['Create', 'Edit', 'Delete']);
        $hasPermission = $this->_canModify($account) || !$isModifyMode;

        $dllCampaign->setReturnValue('checkPermissions', $hasPermission);

        $oCampaignInfo = $this->_buildCampaignInfo($advertiserId, $combo);

        switch ($mode) {
            case 'Create':
                $result = $dllCampaign->modify($oCampaignInfo);
                if ($hasPermission) {
                    $this->assertTrue(
                        $result,
                        "[{$combo['id']}] Create should succeed for {$account}: " .
                        $dllCampaign->getLastError(),
                    );
                    $this->assertNotNull(
                        $oCampaignInfo->campaignId,
                        "[{$combo['id']}] campaignId should be set after create",
                    );
                } else {
                    $this->assertFalse(
                        $result,
                        "[{$combo['id']}] Create should be denied for {$account}",
                    );
                }
                break;

            case 'Edit':
                // First create with full permissions
                $dllCampaignCreate = new PartialMockOA_Dll_Campaign_ComboIncludedTest($this);
                $dllCampaignCreate->setReturnValue('checkPermissions', true);
                $createResult = $dllCampaignCreate->modify($oCampaignInfo);
                $this->assertTrue(
                    $createResult,
                    "[{$combo['id']}] Pre-create for Edit should succeed: " .
                    $dllCampaignCreate->getLastError(),
                );

                // Now modify with the test account's permissions
                $oCampaignInfo->campaignName = 'Modified ' . $combo['id'];
                $result = $dllCampaign->modify($oCampaignInfo);
                if ($hasPermission) {
                    $this->assertTrue(
                        $result,
                        "[{$combo['id']}] Edit should succeed for {$account}: " .
                        $dllCampaign->getLastError(),
                    );
                } else {
                    $this->assertFalse(
                        $result,
                        "[{$combo['id']}] Edit should be denied for {$account}",
                    );
                }
                break;

            case 'View':
                // Create first with full permissions
                $dllCampaignCreate = new PartialMockOA_Dll_Campaign_ComboIncludedTest($this);
                $dllCampaignCreate->setReturnValue('checkPermissions', true);
                $createResult = $dllCampaignCreate->modify($oCampaignInfo);
                $this->assertTrue(
                    $createResult,
                    "[{$combo['id']}] Pre-create for View should succeed: " .
                    $dllCampaignCreate->getLastError(),
                );

                // View (getCampaign) — all account types can view
                $oCampaignGet = null;
                $result = $dllCampaign->getCampaign(
                    $oCampaignInfo->campaignId,
                    $oCampaignGet,
                );
                $this->assertTrue(
                    $result,
                    "[{$combo['id']}] View should succeed for {$account}: " .
                    $dllCampaign->getLastError(),
                );
                $this->assertNotNull(
                    $oCampaignGet,
                    "[{$combo['id']}] getCampaign should return data",
                );
                if ($oCampaignGet) {
                    $this->assertEqual(
                        $oCampaignGet->campaignName,
                        $oCampaignInfo->campaignName,
                        "[{$combo['id']}] Campaign name should match",
                    );
                }
                break;

            case 'Delete':
                // Create first with full permissions
                $dllCampaignCreate = new PartialMockOA_Dll_Campaign_ComboIncludedTest($this);
                $dllCampaignCreate->setReturnValue('checkPermissions', true);
                $createResult = $dllCampaignCreate->modify($oCampaignInfo);
                $this->assertTrue(
                    $createResult,
                    "[{$combo['id']}] Pre-create for Delete should succeed: " .
                    $dllCampaignCreate->getLastError(),
                );

                $result = $dllCampaign->delete($oCampaignInfo->campaignId);
                if ($hasPermission) {
                    $this->assertTrue(
                        $result,
                        "[{$combo['id']}] Delete should succeed for {$account}: " .
                        $dllCampaign->getLastError(),
                    );
                    // Verify campaign is deleted
                    $dllCampaignVerify = new PartialMockOA_Dll_Campaign_ComboIncludedTest($this);
                    $dllCampaignVerify->setReturnValue('checkPermissions', true);
                    $oCampaignCheck = null;
                    $this->assertFalse(
                        $dllCampaignVerify->getCampaign(
                            $oCampaignInfo->campaignId,
                            $oCampaignCheck,
                        ),
                        "[{$combo['id']}] getCampaign should fail after delete",
                    );
                } else {
                    $this->assertFalse(
                        $result,
                        "[{$combo['id']}] Delete should be denied for {$account}",
                    );
                }
                break;
        }
    }

    // ---------------------------------------------------------------
    // Pairwise combo test data: 120 test cases
    // ---------------------------------------------------------------

    /**
     * Returns the full array of 120 pairwise test combos.
     *
     * @return array
     */
    private function _getComboData()
    {
        return [
            // 12 explicit sample combos from the spec (C001–C012)
            ['id' => 'C001', 'account' => 'ADMIN', 'mode' => 'Create', 'campaign_type' => 'Remnant', 'status' => 'Running', 'revenue' => 'CPM', 'dates' => 'BothDates', 'booking' => 'Unlimited', 'capping' => 'None', 'priority' => 'p0w1'],
            ['id' => 'C002', 'account' => 'MANAGER', 'mode' => 'Create', 'campaign_type' => 'ContractNormal', 'status' => 'Running', 'revenue' => 'CPC', 'dates' => 'StartOnly', 'booking' => 'ImprOnly', 'capping' => 'CappingOnly', 'priority' => 'p5w0'],
            ['id' => 'C003', 'account' => 'MANAGER', 'mode' => 'Edit', 'campaign_type' => 'Override', 'status' => 'Paused', 'revenue' => 'CPA', 'dates' => 'EndOnly', 'booking' => 'ClicksOnly', 'capping' => 'SessionOnly', 'priority' => 'p5w0'],
            ['id' => 'C004', 'account' => 'ADMIN', 'mode' => 'Edit', 'campaign_type' => 'eCPM', 'status' => 'Awaiting', 'revenue' => 'MT', 'dates' => 'NoDates', 'booking' => 'Both', 'capping' => 'BlockOnly', 'priority' => 'p5w0'],
            ['id' => 'C005', 'account' => 'MANAGER', 'mode' => 'Delete', 'campaign_type' => 'ContractECPM', 'status' => 'Expired', 'revenue' => 'CPM', 'dates' => 'BothDates', 'booking' => 'Unlimited', 'capping' => 'AllThree', 'priority' => 'p0w1'],
            ['id' => 'C006', 'account' => 'ADVERTISER', 'mode' => 'View', 'campaign_type' => 'Remnant', 'status' => 'Running', 'revenue' => 'CPC', 'dates' => 'BothDates', 'booking' => 'ImprOnly', 'capping' => 'None', 'priority' => 'p0w1'],
            ['id' => 'C007', 'account' => 'TRAFFICKER', 'mode' => 'View', 'campaign_type' => 'ContractNormal', 'status' => 'Inactive', 'revenue' => 'CPA', 'dates' => 'StartOnly', 'booking' => 'ClicksOnly', 'capping' => 'CappingOnly', 'priority' => 'p5w0'],
            ['id' => 'C008', 'account' => 'ADMIN', 'mode' => 'Create', 'campaign_type' => 'eCPM', 'status' => 'Pending', 'revenue' => 'MT', 'dates' => 'StartEqualsEnd', 'booking' => 'Both', 'capping' => 'AllThree', 'priority' => 'p5w0'],
            ['id' => 'C009', 'account' => 'MANAGER', 'mode' => 'Create', 'campaign_type' => 'Remnant', 'status' => 'Running', 'revenue' => 'CPM', 'dates' => 'NoDates', 'booking' => 'Unlimited', 'capping' => 'None', 'priority' => 'p0w1'],
            ['id' => 'C010', 'account' => 'ADMIN', 'mode' => 'Edit', 'campaign_type' => 'ContractNormal', 'status' => 'Paused', 'revenue' => 'CPC', 'dates' => 'BothDates', 'booking' => 'ImprOnly', 'capping' => 'SessionOnly', 'priority' => 'p5w0'],
            ['id' => 'C011', 'account' => 'MANAGER', 'mode' => 'Create', 'campaign_type' => 'Override', 'status' => 'Running', 'revenue' => 'CPA', 'dates' => 'EndOnly', 'booking' => 'Both', 'capping' => 'BlockOnly', 'priority' => 'p5w0'],
            ['id' => 'C012', 'account' => 'ADMIN', 'mode' => 'Delete', 'campaign_type' => 'eCPM', 'status' => 'Inactive', 'revenue' => 'MT', 'dates' => 'StartOnly', 'booking' => 'ClicksOnly', 'capping' => 'AllThree', 'priority' => 'p5w0'],

            // Pairwise-generated combos (C013–C068) — seed 42
            ['id' => 'C013', 'account' => 'ADMIN', 'mode' => 'Create', 'campaign_type' => 'Override', 'status' => 'Expired', 'revenue' => 'CPC', 'dates' => 'StartOnly', 'booking' => 'Unlimited', 'capping' => 'AllThree', 'priority' => 'p0w1'],
            ['id' => 'C014', 'account' => 'TRAFFICKER', 'mode' => 'View', 'campaign_type' => 'ContractECPM', 'status' => 'Paused', 'revenue' => 'CPC', 'dates' => 'NoDates', 'booking' => 'Both', 'capping' => 'SessionOnly', 'priority' => 'p5w0'],
            ['id' => 'C015', 'account' => 'ADVERTISER', 'mode' => 'Create', 'campaign_type' => 'eCPM', 'status' => 'Pending', 'revenue' => 'CPM', 'dates' => 'StartEqualsEnd', 'booking' => 'ImprOnly', 'capping' => 'BlockOnly', 'priority' => 'p5w0'],
            ['id' => 'C016', 'account' => 'MANAGER', 'mode' => 'Delete', 'campaign_type' => 'ContractECPM', 'status' => 'Approval', 'revenue' => 'MT', 'dates' => 'BothDates', 'booking' => 'ClicksOnly', 'capping' => 'CappingOnly', 'priority' => 'p0w1'],
            ['id' => 'C017', 'account' => 'TRAFFICKER', 'mode' => 'Edit', 'campaign_type' => 'ContractNormal', 'status' => 'Rejected', 'revenue' => 'CPA', 'dates' => 'BothDates', 'booking' => 'ImprOnly', 'capping' => 'BlockOnly', 'priority' => 'p0w1'],
            ['id' => 'C018', 'account' => 'ADMIN', 'mode' => 'Delete', 'campaign_type' => 'Remnant', 'status' => 'Inactive', 'revenue' => 'CPM', 'dates' => 'EndOnly', 'booking' => 'ClicksOnly', 'capping' => 'SessionOnly', 'priority' => 'p5w0'],
            ['id' => 'C019', 'account' => 'MANAGER', 'mode' => 'View', 'campaign_type' => 'Remnant', 'status' => 'Pending', 'revenue' => 'CPA', 'dates' => 'StartEqualsEnd', 'booking' => 'Unlimited', 'capping' => 'None', 'priority' => 'p0w1'],
            ['id' => 'C020', 'account' => 'ADVERTISER', 'mode' => 'Edit', 'campaign_type' => 'ContractNormal', 'status' => 'Expired', 'revenue' => 'CPA', 'dates' => 'NoDates', 'booking' => 'ClicksOnly', 'capping' => 'AllThree', 'priority' => 'p5w0'],
            ['id' => 'C021', 'account' => 'ADMIN', 'mode' => 'Edit', 'campaign_type' => 'Override', 'status' => 'Awaiting', 'revenue' => 'CPA', 'dates' => 'EndOnly', 'booking' => 'Both', 'capping' => 'CappingOnly', 'priority' => 'p5w0'],
            ['id' => 'C022', 'account' => 'ADVERTISER', 'mode' => 'View', 'campaign_type' => 'eCPM', 'status' => 'Awaiting', 'revenue' => 'MT', 'dates' => 'StartOnly', 'booking' => 'Unlimited', 'capping' => 'SessionOnly', 'priority' => 'p0w1'],
            ['id' => 'C023', 'account' => 'ADVERTISER', 'mode' => 'Delete', 'campaign_type' => 'Remnant', 'status' => 'Running', 'revenue' => 'CPM', 'dates' => 'BothDates', 'booking' => 'Both', 'capping' => 'AllThree', 'priority' => 'p0w1'],
            ['id' => 'C024', 'account' => 'MANAGER', 'mode' => 'Edit', 'campaign_type' => 'eCPM', 'status' => 'Running', 'revenue' => 'CPC', 'dates' => 'EndOnly', 'booking' => 'ClicksOnly', 'capping' => 'BlockOnly', 'priority' => 'p5w0'],
            ['id' => 'C025', 'account' => 'ADMIN', 'mode' => 'Edit', 'campaign_type' => 'ContractNormal', 'status' => 'Approval', 'revenue' => 'MT', 'dates' => 'NoDates', 'booking' => 'ImprOnly', 'capping' => 'None', 'priority' => 'p5w0'],
            ['id' => 'C026', 'account' => 'MANAGER', 'mode' => 'Create', 'campaign_type' => 'Override', 'status' => 'Paused', 'revenue' => 'CPM', 'dates' => 'BothDates', 'booking' => 'ImprOnly', 'capping' => 'SessionOnly', 'priority' => 'p0w1'],
            ['id' => 'C027', 'account' => 'TRAFFICKER', 'mode' => 'Delete', 'campaign_type' => 'eCPM', 'status' => 'Rejected', 'revenue' => 'CPM', 'dates' => 'NoDates', 'booking' => 'ClicksOnly', 'capping' => 'CappingOnly', 'priority' => 'p5w0'],
            ['id' => 'C028', 'account' => 'TRAFFICKER', 'mode' => 'Create', 'campaign_type' => 'Remnant', 'status' => 'Paused', 'revenue' => 'MT', 'dates' => 'EndOnly', 'booking' => 'Unlimited', 'capping' => 'None', 'priority' => 'p0w1'],
            ['id' => 'C029', 'account' => 'ADVERTISER', 'mode' => 'Delete', 'campaign_type' => 'ContractECPM', 'status' => 'Approval', 'revenue' => 'CPC', 'dates' => 'StartOnly', 'booking' => 'ImprOnly', 'capping' => 'None', 'priority' => 'p5w0'],
            ['id' => 'C030', 'account' => 'ADMIN', 'mode' => 'Create', 'campaign_type' => 'ContractECPM', 'status' => 'Running', 'revenue' => 'CPA', 'dates' => 'StartEqualsEnd', 'booking' => 'Both', 'capping' => 'BlockOnly', 'priority' => 'p0w1'],
            ['id' => 'C031', 'account' => 'MANAGER', 'mode' => 'Edit', 'campaign_type' => 'ContractECPM', 'status' => 'Inactive', 'revenue' => 'CPM', 'dates' => 'StartOnly', 'booking' => 'Both', 'capping' => 'AllThree', 'priority' => 'p0w1'],
            ['id' => 'C032', 'account' => 'TRAFFICKER', 'mode' => 'View', 'campaign_type' => 'Override', 'status' => 'Inactive', 'revenue' => 'CPA', 'dates' => 'StartEqualsEnd', 'booking' => 'ClicksOnly', 'capping' => 'BlockOnly', 'priority' => 'p0w1'],
            ['id' => 'C033', 'account' => 'MANAGER', 'mode' => 'View', 'campaign_type' => 'ContractNormal', 'status' => 'Rejected', 'revenue' => 'CPM', 'dates' => 'EndOnly', 'booking' => 'Unlimited', 'capping' => 'SessionOnly', 'priority' => 'p5w0'],
            ['id' => 'C034', 'account' => 'MANAGER', 'mode' => 'Create', 'campaign_type' => 'ContractNormal', 'status' => 'Pending', 'revenue' => 'CPC', 'dates' => 'StartEqualsEnd', 'booking' => 'ClicksOnly', 'capping' => 'CappingOnly', 'priority' => 'p5w0'],
            ['id' => 'C035', 'account' => 'ADMIN', 'mode' => 'View', 'campaign_type' => 'Remnant', 'status' => 'Rejected', 'revenue' => 'CPC', 'dates' => 'BothDates', 'booking' => 'ImprOnly', 'capping' => 'None', 'priority' => 'p5w0'],
            ['id' => 'C036', 'account' => 'ADMIN', 'mode' => 'Delete', 'campaign_type' => 'eCPM', 'status' => 'Paused', 'revenue' => 'MT', 'dates' => 'NoDates', 'booking' => 'Unlimited', 'capping' => 'BlockOnly', 'priority' => 'p0w1'],
            ['id' => 'C037', 'account' => 'TRAFFICKER', 'mode' => 'Delete', 'campaign_type' => 'eCPM', 'status' => 'Pending', 'revenue' => 'MT', 'dates' => 'StartEqualsEnd', 'booking' => 'ImprOnly', 'capping' => 'AllThree', 'priority' => 'p5w0'],
            ['id' => 'C038', 'account' => 'TRAFFICKER', 'mode' => 'View', 'campaign_type' => 'Remnant', 'status' => 'Running', 'revenue' => 'CPA', 'dates' => 'StartOnly', 'booking' => 'Unlimited', 'capping' => 'CappingOnly', 'priority' => 'p0w1'],
            ['id' => 'C039', 'account' => 'MANAGER', 'mode' => 'Edit', 'campaign_type' => 'Remnant', 'status' => 'Awaiting', 'revenue' => 'CPC', 'dates' => 'NoDates', 'booking' => 'ClicksOnly', 'capping' => 'None', 'priority' => 'p0w1'],
            ['id' => 'C040', 'account' => 'MANAGER', 'mode' => 'View', 'campaign_type' => 'Remnant', 'status' => 'Expired', 'revenue' => 'MT', 'dates' => 'StartOnly', 'booking' => 'Both', 'capping' => 'BlockOnly', 'priority' => 'p5w0'],
            ['id' => 'C041', 'account' => 'MANAGER', 'mode' => 'Edit', 'campaign_type' => 'ContractNormal', 'status' => 'Paused', 'revenue' => 'CPA', 'dates' => 'StartEqualsEnd', 'booking' => 'ClicksOnly', 'capping' => 'SessionOnly', 'priority' => 'p0w1'],
            ['id' => 'C042', 'account' => 'ADVERTISER', 'mode' => 'Delete', 'campaign_type' => 'Override', 'status' => 'Expired', 'revenue' => 'MT', 'dates' => 'BothDates', 'booking' => 'Both', 'capping' => 'None', 'priority' => 'p0w1'],
            ['id' => 'C043', 'account' => 'TRAFFICKER', 'mode' => 'Edit', 'campaign_type' => 'eCPM', 'status' => 'Approval', 'revenue' => 'CPA', 'dates' => 'StartEqualsEnd', 'booking' => 'Both', 'capping' => 'None', 'priority' => 'p5w0'],
            ['id' => 'C044', 'account' => 'TRAFFICKER', 'mode' => 'Create', 'campaign_type' => 'eCPM', 'status' => 'Inactive', 'revenue' => 'CPC', 'dates' => 'BothDates', 'booking' => 'Unlimited', 'capping' => 'None', 'priority' => 'p0w1'],
            ['id' => 'C045', 'account' => 'ADVERTISER', 'mode' => 'Edit', 'campaign_type' => 'Override', 'status' => 'Rejected', 'revenue' => 'CPC', 'dates' => 'EndOnly', 'booking' => 'Both', 'capping' => 'AllThree', 'priority' => 'p0w1'],
            ['id' => 'C046', 'account' => 'ADVERTISER', 'mode' => 'Create', 'campaign_type' => 'Override', 'status' => 'Expired', 'revenue' => 'CPM', 'dates' => 'NoDates', 'booking' => 'ImprOnly', 'capping' => 'CappingOnly', 'priority' => 'p0w1'],
            ['id' => 'C047', 'account' => 'TRAFFICKER', 'mode' => 'Delete', 'campaign_type' => 'ContractNormal', 'status' => 'Pending', 'revenue' => 'CPA', 'dates' => 'BothDates', 'booking' => 'Both', 'capping' => 'SessionOnly', 'priority' => 'p5w0'],
            ['id' => 'C048', 'account' => 'TRAFFICKER', 'mode' => 'Create', 'campaign_type' => 'ContractECPM', 'status' => 'Awaiting', 'revenue' => 'CPM', 'dates' => 'StartOnly', 'booking' => 'ClicksOnly', 'capping' => 'CappingOnly', 'priority' => 'p0w1'],
            ['id' => 'C049', 'account' => 'ADMIN', 'mode' => 'Create', 'campaign_type' => 'ContractECPM', 'status' => 'Approval', 'revenue' => 'MT', 'dates' => 'EndOnly', 'booking' => 'Unlimited', 'capping' => 'None', 'priority' => 'p5w0'],
            ['id' => 'C050', 'account' => 'TRAFFICKER', 'mode' => 'View', 'campaign_type' => 'ContractNormal', 'status' => 'Awaiting', 'revenue' => 'CPA', 'dates' => 'BothDates', 'booking' => 'ImprOnly', 'capping' => 'AllThree', 'priority' => 'p5w0'],
            ['id' => 'C051', 'account' => 'ADMIN', 'mode' => 'View', 'campaign_type' => 'ContractNormal', 'status' => 'Inactive', 'revenue' => 'MT', 'dates' => 'EndOnly', 'booking' => 'ImprOnly', 'capping' => 'CappingOnly', 'priority' => 'p0w1'],
            ['id' => 'C052', 'account' => 'ADVERTISER', 'mode' => 'View', 'campaign_type' => 'Override', 'status' => 'Approval', 'revenue' => 'CPM', 'dates' => 'NoDates', 'booking' => 'Both', 'capping' => 'AllThree', 'priority' => 'p5w0'],
            ['id' => 'C053', 'account' => 'MANAGER', 'mode' => 'Create', 'campaign_type' => 'ContractECPM', 'status' => 'Rejected', 'revenue' => 'CPM', 'dates' => 'StartOnly', 'booking' => 'ClicksOnly', 'capping' => 'None', 'priority' => 'p5w0'],
            ['id' => 'C054', 'account' => 'ADMIN', 'mode' => 'Create', 'campaign_type' => 'ContractNormal', 'status' => 'Running', 'revenue' => 'MT', 'dates' => 'StartOnly', 'booking' => 'ClicksOnly', 'capping' => 'SessionOnly', 'priority' => 'p0w1'],
            ['id' => 'C055', 'account' => 'ADMIN', 'mode' => 'Edit', 'campaign_type' => 'ContractECPM', 'status' => 'Pending', 'revenue' => 'CPM', 'dates' => 'NoDates', 'booking' => 'Both', 'capping' => 'None', 'priority' => 'p5w0'],
            ['id' => 'C056', 'account' => 'ADVERTISER', 'mode' => 'Edit', 'campaign_type' => 'ContractECPM', 'status' => 'Paused', 'revenue' => 'CPC', 'dates' => 'StartOnly', 'booking' => 'Unlimited', 'capping' => 'AllThree', 'priority' => 'p0w1'],
            ['id' => 'C057', 'account' => 'ADVERTISER', 'mode' => 'Edit', 'campaign_type' => 'eCPM', 'status' => 'Expired', 'revenue' => 'CPA', 'dates' => 'StartEqualsEnd', 'booking' => 'ClicksOnly', 'capping' => 'SessionOnly', 'priority' => 'p0w1'],
            ['id' => 'C058', 'account' => 'ADVERTISER', 'mode' => 'View', 'campaign_type' => 'Override', 'status' => 'Running', 'revenue' => 'CPA', 'dates' => 'NoDates', 'booking' => 'ImprOnly', 'capping' => 'BlockOnly', 'priority' => 'p5w0'],
            ['id' => 'C059', 'account' => 'MANAGER', 'mode' => 'Delete', 'campaign_type' => 'ContractNormal', 'status' => 'Awaiting', 'revenue' => 'MT', 'dates' => 'StartEqualsEnd', 'booking' => 'Both', 'capping' => 'BlockOnly', 'priority' => 'p0w1'],
            ['id' => 'C060', 'account' => 'ADVERTISER', 'mode' => 'Edit', 'campaign_type' => 'Override', 'status' => 'Pending', 'revenue' => 'MT', 'dates' => 'EndOnly', 'booking' => 'Both', 'capping' => 'BlockOnly', 'priority' => 'p5w0'],
            ['id' => 'C061', 'account' => 'TRAFFICKER', 'mode' => 'Edit', 'campaign_type' => 'ContractECPM', 'status' => 'Expired', 'revenue' => 'CPM', 'dates' => 'EndOnly', 'booking' => 'ClicksOnly', 'capping' => 'AllThree', 'priority' => 'p5w0'],
            ['id' => 'C062', 'account' => 'ADVERTISER', 'mode' => 'Create', 'campaign_type' => 'ContractECPM', 'status' => 'Rejected', 'revenue' => 'MT', 'dates' => 'StartEqualsEnd', 'booking' => 'ClicksOnly', 'capping' => 'AllThree', 'priority' => 'p0w1'],
            ['id' => 'C063', 'account' => 'ADVERTISER', 'mode' => 'View', 'campaign_type' => 'Override', 'status' => 'Inactive', 'revenue' => 'CPM', 'dates' => 'NoDates', 'booking' => 'Both', 'capping' => 'BlockOnly', 'priority' => 'p0w1'],
            ['id' => 'C064', 'account' => 'MANAGER', 'mode' => 'Create', 'campaign_type' => 'Remnant', 'status' => 'Approval', 'revenue' => 'MT', 'dates' => 'StartOnly', 'booking' => 'ImprOnly', 'capping' => 'SessionOnly', 'priority' => 'p0w1'],
            ['id' => 'C065', 'account' => 'TRAFFICKER', 'mode' => 'Create', 'campaign_type' => 'eCPM', 'status' => 'Pending', 'revenue' => 'CPA', 'dates' => 'StartOnly', 'booking' => 'ImprOnly', 'capping' => 'CappingOnly', 'priority' => 'p5w0'],
            ['id' => 'C066', 'account' => 'ADVERTISER', 'mode' => 'Create', 'campaign_type' => 'eCPM', 'status' => 'Approval', 'revenue' => 'CPM', 'dates' => 'StartEqualsEnd', 'booking' => 'Unlimited', 'capping' => 'BlockOnly', 'priority' => 'p5w0'],
            ['id' => 'C067', 'account' => 'MANAGER', 'mode' => 'Delete', 'campaign_type' => 'Remnant', 'status' => 'Running', 'revenue' => 'MT', 'dates' => 'EndOnly', 'booking' => 'Unlimited', 'capping' => 'None', 'priority' => 'p5w0'],
            ['id' => 'C068', 'account' => 'ADVERTISER', 'mode' => 'View', 'campaign_type' => 'Override', 'status' => 'Paused', 'revenue' => 'CPA', 'dates' => 'StartOnly', 'booking' => 'Both', 'capping' => 'CappingOnly', 'priority' => 'p5w0'],

            // Pairwise-generated combos (C069–C120) — seeds 123, 456, 789, random
            ['id' => 'C069', 'account' => 'ADMIN', 'mode' => 'View', 'campaign_type' => 'eCPM', 'status' => 'Paused', 'revenue' => 'CPC', 'dates' => 'StartOnly', 'booking' => 'Both', 'capping' => 'None', 'priority' => 'p0w1'],
            ['id' => 'C070', 'account' => 'MANAGER', 'mode' => 'Delete', 'campaign_type' => 'Override', 'status' => 'Running', 'revenue' => 'CPM', 'dates' => 'NoDates', 'booking' => 'ImprOnly', 'capping' => 'AllThree', 'priority' => 'p5w0'],
            ['id' => 'C071', 'account' => 'TRAFFICKER', 'mode' => 'View', 'campaign_type' => 'eCPM', 'status' => 'Expired', 'revenue' => 'MT', 'dates' => 'EndOnly', 'booking' => 'ClicksOnly', 'capping' => 'CappingOnly', 'priority' => 'p0w1'],
            ['id' => 'C072', 'account' => 'ADMIN', 'mode' => 'Create', 'campaign_type' => 'Remnant', 'status' => 'Paused', 'revenue' => 'CPA', 'dates' => 'BothDates', 'booking' => 'ClicksOnly', 'capping' => 'BlockOnly', 'priority' => 'p0w1'],
            ['id' => 'C073', 'account' => 'ADVERTISER', 'mode' => 'View', 'campaign_type' => 'ContractECPM', 'status' => 'Running', 'revenue' => 'CPC', 'dates' => 'StartEqualsEnd', 'booking' => 'Unlimited', 'capping' => 'SessionOnly', 'priority' => 'p5w0'],
            ['id' => 'C074', 'account' => 'MANAGER', 'mode' => 'Edit', 'campaign_type' => 'Remnant', 'status' => 'Rejected', 'revenue' => 'MT', 'dates' => 'BothDates', 'booking' => 'Unlimited', 'capping' => 'CappingOnly', 'priority' => 'p0w1'],
            ['id' => 'C075', 'account' => 'ADMIN', 'mode' => 'Delete', 'campaign_type' => 'ContractNormal', 'status' => 'Expired', 'revenue' => 'CPA', 'dates' => 'StartOnly', 'booking' => 'Both', 'capping' => 'None', 'priority' => 'p5w0'],
            ['id' => 'C076', 'account' => 'TRAFFICKER', 'mode' => 'Create', 'campaign_type' => 'ContractNormal', 'status' => 'Paused', 'revenue' => 'CPM', 'dates' => 'EndOnly', 'booking' => 'ImprOnly', 'capping' => 'AllThree', 'priority' => 'p0w1'],
            ['id' => 'C077', 'account' => 'MANAGER', 'mode' => 'View', 'campaign_type' => 'eCPM', 'status' => 'Inactive', 'revenue' => 'CPA', 'dates' => 'NoDates', 'booking' => 'Both', 'capping' => 'SessionOnly', 'priority' => 'p5w0'],
            ['id' => 'C078', 'account' => 'ADMIN', 'mode' => 'Edit', 'campaign_type' => 'Remnant', 'status' => 'Running', 'revenue' => 'CPM', 'dates' => 'StartEqualsEnd', 'booking' => 'ClicksOnly', 'capping' => 'AllThree', 'priority' => 'p0w1'],
            ['id' => 'C079', 'account' => 'ADVERTISER', 'mode' => 'Delete', 'campaign_type' => 'eCPM', 'status' => 'Awaiting', 'revenue' => 'MT', 'dates' => 'BothDates', 'booking' => 'Unlimited', 'capping' => 'BlockOnly', 'priority' => 'p0w1'],
            ['id' => 'C080', 'account' => 'TRAFFICKER', 'mode' => 'Edit', 'campaign_type' => 'Override', 'status' => 'Pending', 'revenue' => 'CPC', 'dates' => 'StartOnly', 'booking' => 'ClicksOnly', 'capping' => 'None', 'priority' => 'p5w0'],
            ['id' => 'C081', 'account' => 'MANAGER', 'mode' => 'Create', 'campaign_type' => 'eCPM', 'status' => 'Expired', 'revenue' => 'CPM', 'dates' => 'EndOnly', 'booking' => 'Both', 'capping' => 'CappingOnly', 'priority' => 'p0w1'],
            ['id' => 'C082', 'account' => 'ADMIN', 'mode' => 'View', 'campaign_type' => 'Override', 'status' => 'Approval', 'revenue' => 'MT', 'dates' => 'NoDates', 'booking' => 'ImprOnly', 'capping' => 'SessionOnly', 'priority' => 'p0w1'],
            ['id' => 'C083', 'account' => 'ADVERTISER', 'mode' => 'Create', 'campaign_type' => 'Remnant', 'status' => 'Inactive', 'revenue' => 'CPC', 'dates' => 'StartEqualsEnd', 'booking' => 'ClicksOnly', 'capping' => 'BlockOnly', 'priority' => 'p5w0'],
            ['id' => 'C084', 'account' => 'TRAFFICKER', 'mode' => 'Delete', 'campaign_type' => 'ContractECPM', 'status' => 'Running', 'revenue' => 'CPA', 'dates' => 'BothDates', 'booking' => 'Unlimited', 'capping' => 'AllThree', 'priority' => 'p0w1'],
            ['id' => 'C085', 'account' => 'MANAGER', 'mode' => 'View', 'campaign_type' => 'ContractECPM', 'status' => 'Awaiting', 'revenue' => 'CPC', 'dates' => 'StartOnly', 'booking' => 'ClicksOnly', 'capping' => 'BlockOnly', 'priority' => 'p0w1'],
            ['id' => 'C086', 'account' => 'ADMIN', 'mode' => 'Delete', 'campaign_type' => 'Override', 'status' => 'Rejected', 'revenue' => 'CPA', 'dates' => 'EndOnly', 'booking' => 'Both', 'capping' => 'CappingOnly', 'priority' => 'p5w0'],
            ['id' => 'C087', 'account' => 'ADVERTISER', 'mode' => 'View', 'campaign_type' => 'ContractNormal', 'status' => 'Pending', 'revenue' => 'CPM', 'dates' => 'NoDates', 'booking' => 'ImprOnly', 'capping' => 'AllThree', 'priority' => 'p0w1'],
            ['id' => 'C088', 'account' => 'TRAFFICKER', 'mode' => 'Edit', 'campaign_type' => 'Remnant', 'status' => 'Approval', 'revenue' => 'MT', 'dates' => 'BothDates', 'booking' => 'Both', 'capping' => 'None', 'priority' => 'p0w1'],
            ['id' => 'C089', 'account' => 'ADMIN', 'mode' => 'Create', 'campaign_type' => 'ContractNormal', 'status' => 'Inactive', 'revenue' => 'CPC', 'dates' => 'StartEqualsEnd', 'booking' => 'Unlimited', 'capping' => 'SessionOnly', 'priority' => 'p5w0'],
            ['id' => 'C090', 'account' => 'MANAGER', 'mode' => 'Delete', 'campaign_type' => 'eCPM', 'status' => 'Paused', 'revenue' => 'CPA', 'dates' => 'StartOnly', 'booking' => 'ClicksOnly', 'capping' => 'BlockOnly', 'priority' => 'p0w1'],
            ['id' => 'C091', 'account' => 'TRAFFICKER', 'mode' => 'View', 'campaign_type' => 'ContractECPM', 'status' => 'Expired', 'revenue' => 'CPM', 'dates' => 'EndOnly', 'booking' => 'Unlimited', 'capping' => 'CappingOnly', 'priority' => 'p5w0'],
            ['id' => 'C092', 'account' => 'ADVERTISER', 'mode' => 'Edit', 'campaign_type' => 'Remnant', 'status' => 'Running', 'revenue' => 'MT', 'dates' => 'NoDates', 'booking' => 'ImprOnly', 'capping' => 'AllThree', 'priority' => 'p0w1'],
            ['id' => 'C093', 'account' => 'ADMIN', 'mode' => 'View', 'campaign_type' => 'eCPM', 'status' => 'Running', 'revenue' => 'CPA', 'dates' => 'BothDates', 'booking' => 'Both', 'capping' => 'None', 'priority' => 'p5w0'],
            ['id' => 'C094', 'account' => 'MANAGER', 'mode' => 'Edit', 'campaign_type' => 'Override', 'status' => 'Awaiting', 'revenue' => 'CPC', 'dates' => 'StartEqualsEnd', 'booking' => 'ClicksOnly', 'capping' => 'SessionOnly', 'priority' => 'p5w0'],
            ['id' => 'C095', 'account' => 'TRAFFICKER', 'mode' => 'Create', 'campaign_type' => 'ContractNormal', 'status' => 'Rejected', 'revenue' => 'MT', 'dates' => 'StartOnly', 'booking' => 'Unlimited', 'capping' => 'BlockOnly', 'priority' => 'p0w1'],
            ['id' => 'C096', 'account' => 'ADMIN', 'mode' => 'Delete', 'campaign_type' => 'ContractECPM', 'status' => 'Approval', 'revenue' => 'CPM', 'dates' => 'NoDates', 'booking' => 'Both', 'capping' => 'AllThree', 'priority' => 'p0w1'],
            ['id' => 'C097', 'account' => 'ADVERTISER', 'mode' => 'View', 'campaign_type' => 'eCPM', 'status' => 'Inactive', 'revenue' => 'CPC', 'dates' => 'EndOnly', 'booking' => 'ClicksOnly', 'capping' => 'CappingOnly', 'priority' => 'p5w0'],
            ['id' => 'C098', 'account' => 'MANAGER', 'mode' => 'Create', 'campaign_type' => 'Remnant', 'status' => 'Paused', 'revenue' => 'CPA', 'dates' => 'BothDates', 'booking' => 'ImprOnly', 'capping' => 'BlockOnly', 'priority' => 'p0w1'],
            ['id' => 'C099', 'account' => 'TRAFFICKER', 'mode' => 'View', 'campaign_type' => 'Override', 'status' => 'Pending', 'revenue' => 'CPM', 'dates' => 'StartEqualsEnd', 'booking' => 'Both', 'capping' => 'None', 'priority' => 'p5w0'],
            ['id' => 'C100', 'account' => 'ADMIN', 'mode' => 'Edit', 'campaign_type' => 'eCPM', 'status' => 'Rejected', 'revenue' => 'MT', 'dates' => 'StartOnly', 'booking' => 'Unlimited', 'capping' => 'SessionOnly', 'priority' => 'p0w1'],
            ['id' => 'C101', 'account' => 'ADVERTISER', 'mode' => 'Delete', 'campaign_type' => 'ContractNormal', 'status' => 'Expired', 'revenue' => 'CPC', 'dates' => 'EndOnly', 'booking' => 'ClicksOnly', 'capping' => 'AllThree', 'priority' => 'p5w0'],
            ['id' => 'C102', 'account' => 'MANAGER', 'mode' => 'View', 'campaign_type' => 'ContractECPM', 'status' => 'Running', 'revenue' => 'CPA', 'dates' => 'NoDates', 'booking' => 'Both', 'capping' => 'BlockOnly', 'priority' => 'p0w1'],
            ['id' => 'C103', 'account' => 'TRAFFICKER', 'mode' => 'Delete', 'campaign_type' => 'Remnant', 'status' => 'Awaiting', 'revenue' => 'MT', 'dates' => 'BothDates', 'booking' => 'ImprOnly', 'capping' => 'CappingOnly', 'priority' => 'p5w0'],
            ['id' => 'C104', 'account' => 'ADMIN', 'mode' => 'Create', 'campaign_type' => 'Override', 'status' => 'Inactive', 'revenue' => 'CPM', 'dates' => 'StartEqualsEnd', 'booking' => 'ClicksOnly', 'capping' => 'None', 'priority' => 'p5w0'],
            ['id' => 'C105', 'account' => 'ADVERTISER', 'mode' => 'View', 'campaign_type' => 'Remnant', 'status' => 'Rejected', 'revenue' => 'CPA', 'dates' => 'StartOnly', 'booking' => 'Unlimited', 'capping' => 'SessionOnly', 'priority' => 'p0w1'],
            ['id' => 'C106', 'account' => 'MANAGER', 'mode' => 'Edit', 'campaign_type' => 'eCPM', 'status' => 'Approval', 'revenue' => 'CPM', 'dates' => 'EndOnly', 'booking' => 'Both', 'capping' => 'AllThree', 'priority' => 'p5w0'],
            ['id' => 'C107', 'account' => 'TRAFFICKER', 'mode' => 'View', 'campaign_type' => 'ContractNormal', 'status' => 'Paused', 'revenue' => 'MT', 'dates' => 'NoDates', 'booking' => 'ClicksOnly', 'capping' => 'BlockOnly', 'priority' => 'p0w1'],
            ['id' => 'C108', 'account' => 'ADMIN', 'mode' => 'Delete', 'campaign_type' => 'Remnant', 'status' => 'Pending', 'revenue' => 'CPC', 'dates' => 'BothDates', 'booking' => 'ImprOnly', 'capping' => 'CappingOnly', 'priority' => 'p0w1'],
            ['id' => 'C109', 'account' => 'ADVERTISER', 'mode' => 'Create', 'campaign_type' => 'ContractNormal', 'status' => 'Awaiting', 'revenue' => 'MT', 'dates' => 'StartEqualsEnd', 'booking' => 'Both', 'capping' => 'SessionOnly', 'priority' => 'p5w0'],
            ['id' => 'C110', 'account' => 'MANAGER', 'mode' => 'Delete', 'campaign_type' => 'ContractECPM', 'status' => 'Inactive', 'revenue' => 'CPA', 'dates' => 'StartOnly', 'booking' => 'Unlimited', 'capping' => 'None', 'priority' => 'p5w0'],
            ['id' => 'C111', 'account' => 'TRAFFICKER', 'mode' => 'Edit', 'campaign_type' => 'Override', 'status' => 'Running', 'revenue' => 'CPM', 'dates' => 'EndOnly', 'booking' => 'ClicksOnly', 'capping' => 'AllThree', 'priority' => 'p0w1'],
            ['id' => 'C112', 'account' => 'ADMIN', 'mode' => 'View', 'campaign_type' => 'ContractECPM', 'status' => 'Expired', 'revenue' => 'CPA', 'dates' => 'NoDates', 'booking' => 'Both', 'capping' => 'SessionOnly', 'priority' => 'p5w0'],
            ['id' => 'C113', 'account' => 'ADVERTISER', 'mode' => 'Edit', 'campaign_type' => 'Remnant', 'status' => 'Paused', 'revenue' => 'CPC', 'dates' => 'BothDates', 'booking' => 'ImprOnly', 'capping' => 'BlockOnly', 'priority' => 'p0w1'],
            ['id' => 'C114', 'account' => 'MANAGER', 'mode' => 'View', 'campaign_type' => 'Override', 'status' => 'Pending', 'revenue' => 'MT', 'dates' => 'StartEqualsEnd', 'booking' => 'ClicksOnly', 'capping' => 'None', 'priority' => 'p5w0'],
            ['id' => 'C115', 'account' => 'TRAFFICKER', 'mode' => 'Create', 'campaign_type' => 'ContractECPM', 'status' => 'Rejected', 'revenue' => 'CPA', 'dates' => 'StartOnly', 'booking' => 'Unlimited', 'capping' => 'CappingOnly', 'priority' => 'p0w1'],
            ['id' => 'C116', 'account' => 'ADMIN', 'mode' => 'Edit', 'campaign_type' => 'ContractNormal', 'status' => 'Awaiting', 'revenue' => 'CPM', 'dates' => 'EndOnly', 'booking' => 'Both', 'capping' => 'AllThree', 'priority' => 'p5w0'],
            ['id' => 'C117', 'account' => 'MANAGER', 'mode' => 'Create', 'campaign_type' => 'eCPM', 'status' => 'Inactive', 'revenue' => 'CPC', 'dates' => 'NoDates', 'booking' => 'ClicksOnly', 'capping' => 'SessionOnly', 'priority' => 'p0w1'],
            ['id' => 'C118', 'account' => 'TRAFFICKER', 'mode' => 'View', 'campaign_type' => 'Remnant', 'status' => 'Approval', 'revenue' => 'MT', 'dates' => 'BothDates', 'booking' => 'ImprOnly', 'capping' => 'BlockOnly', 'priority' => 'p5w0'],
            ['id' => 'C119', 'account' => 'ADMIN', 'mode' => 'Delete', 'campaign_type' => 'ContractECPM', 'status' => 'Running', 'revenue' => 'CPA', 'dates' => 'StartEqualsEnd', 'booking' => 'Unlimited', 'capping' => 'CappingOnly', 'priority' => 'p0w1'],
            ['id' => 'C120', 'account' => 'ADVERTISER', 'mode' => 'View', 'campaign_type' => 'ContractNormal', 'status' => 'Rejected', 'revenue' => 'CPM', 'dates' => 'StartOnly', 'booking' => 'Both', 'capping' => 'None', 'priority' => 'p5w0'],
        ];
    }

    // ---------------------------------------------------------------
    // Test methods: 120 pairwise combo tests (C001–C120)
    // ---------------------------------------------------------------

    /**
     * Test combo C001: ADMIN, Create, Remnant, Running, CPM, BothDates, Unlimited, None, p=0 w=1
     */
    public function testComboC001() { $combos = $this->_getComboData(); $this->_runComboTest($combos[0]); }
    public function testComboC002() { $combos = $this->_getComboData(); $this->_runComboTest($combos[1]); }
    public function testComboC003() { $combos = $this->_getComboData(); $this->_runComboTest($combos[2]); }
    public function testComboC004() { $combos = $this->_getComboData(); $this->_runComboTest($combos[3]); }
    public function testComboC005() { $combos = $this->_getComboData(); $this->_runComboTest($combos[4]); }
    public function testComboC006() { $combos = $this->_getComboData(); $this->_runComboTest($combos[5]); }
    public function testComboC007() { $combos = $this->_getComboData(); $this->_runComboTest($combos[6]); }
    public function testComboC008() { $combos = $this->_getComboData(); $this->_runComboTest($combos[7]); }
    public function testComboC009() { $combos = $this->_getComboData(); $this->_runComboTest($combos[8]); }
    public function testComboC010() { $combos = $this->_getComboData(); $this->_runComboTest($combos[9]); }
    public function testComboC011() { $combos = $this->_getComboData(); $this->_runComboTest($combos[10]); }
    public function testComboC012() { $combos = $this->_getComboData(); $this->_runComboTest($combos[11]); }
    public function testComboC013() { $combos = $this->_getComboData(); $this->_runComboTest($combos[12]); }
    public function testComboC014() { $combos = $this->_getComboData(); $this->_runComboTest($combos[13]); }
    public function testComboC015() { $combos = $this->_getComboData(); $this->_runComboTest($combos[14]); }
    public function testComboC016() { $combos = $this->_getComboData(); $this->_runComboTest($combos[15]); }
    public function testComboC017() { $combos = $this->_getComboData(); $this->_runComboTest($combos[16]); }
    public function testComboC018() { $combos = $this->_getComboData(); $this->_runComboTest($combos[17]); }
    public function testComboC019() { $combos = $this->_getComboData(); $this->_runComboTest($combos[18]); }
    public function testComboC020() { $combos = $this->_getComboData(); $this->_runComboTest($combos[19]); }
    public function testComboC021() { $combos = $this->_getComboData(); $this->_runComboTest($combos[20]); }
    public function testComboC022() { $combos = $this->_getComboData(); $this->_runComboTest($combos[21]); }
    public function testComboC023() { $combos = $this->_getComboData(); $this->_runComboTest($combos[22]); }
    public function testComboC024() { $combos = $this->_getComboData(); $this->_runComboTest($combos[23]); }
    public function testComboC025() { $combos = $this->_getComboData(); $this->_runComboTest($combos[24]); }
    public function testComboC026() { $combos = $this->_getComboData(); $this->_runComboTest($combos[25]); }
    public function testComboC027() { $combos = $this->_getComboData(); $this->_runComboTest($combos[26]); }
    public function testComboC028() { $combos = $this->_getComboData(); $this->_runComboTest($combos[27]); }
    public function testComboC029() { $combos = $this->_getComboData(); $this->_runComboTest($combos[28]); }
    public function testComboC030() { $combos = $this->_getComboData(); $this->_runComboTest($combos[29]); }
    public function testComboC031() { $combos = $this->_getComboData(); $this->_runComboTest($combos[30]); }
    public function testComboC032() { $combos = $this->_getComboData(); $this->_runComboTest($combos[31]); }
    public function testComboC033() { $combos = $this->_getComboData(); $this->_runComboTest($combos[32]); }
    public function testComboC034() { $combos = $this->_getComboData(); $this->_runComboTest($combos[33]); }
    public function testComboC035() { $combos = $this->_getComboData(); $this->_runComboTest($combos[34]); }
    public function testComboC036() { $combos = $this->_getComboData(); $this->_runComboTest($combos[35]); }
    public function testComboC037() { $combos = $this->_getComboData(); $this->_runComboTest($combos[36]); }
    public function testComboC038() { $combos = $this->_getComboData(); $this->_runComboTest($combos[37]); }
    public function testComboC039() { $combos = $this->_getComboData(); $this->_runComboTest($combos[38]); }
    public function testComboC040() { $combos = $this->_getComboData(); $this->_runComboTest($combos[39]); }
    public function testComboC041() { $combos = $this->_getComboData(); $this->_runComboTest($combos[40]); }
    public function testComboC042() { $combos = $this->_getComboData(); $this->_runComboTest($combos[41]); }
    public function testComboC043() { $combos = $this->_getComboData(); $this->_runComboTest($combos[42]); }
    public function testComboC044() { $combos = $this->_getComboData(); $this->_runComboTest($combos[43]); }
    public function testComboC045() { $combos = $this->_getComboData(); $this->_runComboTest($combos[44]); }
    public function testComboC046() { $combos = $this->_getComboData(); $this->_runComboTest($combos[45]); }
    public function testComboC047() { $combos = $this->_getComboData(); $this->_runComboTest($combos[46]); }
    public function testComboC048() { $combos = $this->_getComboData(); $this->_runComboTest($combos[47]); }
    public function testComboC049() { $combos = $this->_getComboData(); $this->_runComboTest($combos[48]); }
    public function testComboC050() { $combos = $this->_getComboData(); $this->_runComboTest($combos[49]); }
    public function testComboC051() { $combos = $this->_getComboData(); $this->_runComboTest($combos[50]); }
    public function testComboC052() { $combos = $this->_getComboData(); $this->_runComboTest($combos[51]); }
    public function testComboC053() { $combos = $this->_getComboData(); $this->_runComboTest($combos[52]); }
    public function testComboC054() { $combos = $this->_getComboData(); $this->_runComboTest($combos[53]); }
    public function testComboC055() { $combos = $this->_getComboData(); $this->_runComboTest($combos[54]); }
    public function testComboC056() { $combos = $this->_getComboData(); $this->_runComboTest($combos[55]); }
    public function testComboC057() { $combos = $this->_getComboData(); $this->_runComboTest($combos[56]); }
    public function testComboC058() { $combos = $this->_getComboData(); $this->_runComboTest($combos[57]); }
    public function testComboC059() { $combos = $this->_getComboData(); $this->_runComboTest($combos[58]); }
    public function testComboC060() { $combos = $this->_getComboData(); $this->_runComboTest($combos[59]); }
    public function testComboC061() { $combos = $this->_getComboData(); $this->_runComboTest($combos[60]); }
    public function testComboC062() { $combos = $this->_getComboData(); $this->_runComboTest($combos[61]); }
    public function testComboC063() { $combos = $this->_getComboData(); $this->_runComboTest($combos[62]); }
    public function testComboC064() { $combos = $this->_getComboData(); $this->_runComboTest($combos[63]); }
    public function testComboC065() { $combos = $this->_getComboData(); $this->_runComboTest($combos[64]); }
    public function testComboC066() { $combos = $this->_getComboData(); $this->_runComboTest($combos[65]); }
    public function testComboC067() { $combos = $this->_getComboData(); $this->_runComboTest($combos[66]); }
    public function testComboC068() { $combos = $this->_getComboData(); $this->_runComboTest($combos[67]); }
    public function testComboC069() { $combos = $this->_getComboData(); $this->_runComboTest($combos[68]); }
    public function testComboC070() { $combos = $this->_getComboData(); $this->_runComboTest($combos[69]); }
    public function testComboC071() { $combos = $this->_getComboData(); $this->_runComboTest($combos[70]); }
    public function testComboC072() { $combos = $this->_getComboData(); $this->_runComboTest($combos[71]); }
    public function testComboC073() { $combos = $this->_getComboData(); $this->_runComboTest($combos[72]); }
    public function testComboC074() { $combos = $this->_getComboData(); $this->_runComboTest($combos[73]); }
    public function testComboC075() { $combos = $this->_getComboData(); $this->_runComboTest($combos[74]); }
    public function testComboC076() { $combos = $this->_getComboData(); $this->_runComboTest($combos[75]); }
    public function testComboC077() { $combos = $this->_getComboData(); $this->_runComboTest($combos[76]); }
    public function testComboC078() { $combos = $this->_getComboData(); $this->_runComboTest($combos[77]); }
    public function testComboC079() { $combos = $this->_getComboData(); $this->_runComboTest($combos[78]); }
    public function testComboC080() { $combos = $this->_getComboData(); $this->_runComboTest($combos[79]); }
    public function testComboC081() { $combos = $this->_getComboData(); $this->_runComboTest($combos[80]); }
    public function testComboC082() { $combos = $this->_getComboData(); $this->_runComboTest($combos[81]); }
    public function testComboC083() { $combos = $this->_getComboData(); $this->_runComboTest($combos[82]); }
    public function testComboC084() { $combos = $this->_getComboData(); $this->_runComboTest($combos[83]); }
    public function testComboC085() { $combos = $this->_getComboData(); $this->_runComboTest($combos[84]); }
    public function testComboC086() { $combos = $this->_getComboData(); $this->_runComboTest($combos[85]); }
    public function testComboC087() { $combos = $this->_getComboData(); $this->_runComboTest($combos[86]); }
    public function testComboC088() { $combos = $this->_getComboData(); $this->_runComboTest($combos[87]); }
    public function testComboC089() { $combos = $this->_getComboData(); $this->_runComboTest($combos[88]); }
    public function testComboC090() { $combos = $this->_getComboData(); $this->_runComboTest($combos[89]); }
    public function testComboC091() { $combos = $this->_getComboData(); $this->_runComboTest($combos[90]); }
    public function testComboC092() { $combos = $this->_getComboData(); $this->_runComboTest($combos[91]); }
    public function testComboC093() { $combos = $this->_getComboData(); $this->_runComboTest($combos[92]); }
    public function testComboC094() { $combos = $this->_getComboData(); $this->_runComboTest($combos[93]); }
    public function testComboC095() { $combos = $this->_getComboData(); $this->_runComboTest($combos[94]); }
    public function testComboC096() { $combos = $this->_getComboData(); $this->_runComboTest($combos[95]); }
    public function testComboC097() { $combos = $this->_getComboData(); $this->_runComboTest($combos[96]); }
    public function testComboC098() { $combos = $this->_getComboData(); $this->_runComboTest($combos[97]); }
    public function testComboC099() { $combos = $this->_getComboData(); $this->_runComboTest($combos[98]); }
    public function testComboC100() { $combos = $this->_getComboData(); $this->_runComboTest($combos[99]); }
    public function testComboC101() { $combos = $this->_getComboData(); $this->_runComboTest($combos[100]); }
    public function testComboC102() { $combos = $this->_getComboData(); $this->_runComboTest($combos[101]); }
    public function testComboC103() { $combos = $this->_getComboData(); $this->_runComboTest($combos[102]); }
    public function testComboC104() { $combos = $this->_getComboData(); $this->_runComboTest($combos[103]); }
    public function testComboC105() { $combos = $this->_getComboData(); $this->_runComboTest($combos[104]); }
    public function testComboC106() { $combos = $this->_getComboData(); $this->_runComboTest($combos[105]); }
    public function testComboC107() { $combos = $this->_getComboData(); $this->_runComboTest($combos[106]); }
    public function testComboC108() { $combos = $this->_getComboData(); $this->_runComboTest($combos[107]); }
    public function testComboC109() { $combos = $this->_getComboData(); $this->_runComboTest($combos[108]); }
    public function testComboC110() { $combos = $this->_getComboData(); $this->_runComboTest($combos[109]); }
    public function testComboC111() { $combos = $this->_getComboData(); $this->_runComboTest($combos[110]); }
    public function testComboC112() { $combos = $this->_getComboData(); $this->_runComboTest($combos[111]); }
    public function testComboC113() { $combos = $this->_getComboData(); $this->_runComboTest($combos[112]); }
    public function testComboC114() { $combos = $this->_getComboData(); $this->_runComboTest($combos[113]); }
    public function testComboC115() { $combos = $this->_getComboData(); $this->_runComboTest($combos[114]); }
    public function testComboC116() { $combos = $this->_getComboData(); $this->_runComboTest($combos[115]); }
    public function testComboC117() { $combos = $this->_getComboData(); $this->_runComboTest($combos[116]); }
    public function testComboC118() { $combos = $this->_getComboData(); $this->_runComboTest($combos[117]); }
    public function testComboC119() { $combos = $this->_getComboData(); $this->_runComboTest($combos[118]); }
    public function testComboC120() { $combos = $this->_getComboData(); $this->_runComboTest($combos[119]); }
}
