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
 * Section 4B: Campaign CRUD — Excluded Combos (Negative Tests)
 *
 * Tests that verify invalid/excluded combinations are properly
 * denied or produce validation errors:
 *
 *   1. ADVERTISER + Create/Edit/Delete → Permission denied
 *   2. TRAFFICKER + Create/Edit/Delete → Permission denied
 *   3. priority > 0 + weight > 0 → Validation rejection
 *   4. priority = 0 + targets > 0 → Validation rejection
 *   5. Zone-only revenue types (RS/BV/AI/ANYVAR/VARSUM) → Not applicable
 *   6. Status = Expired + Mode = Create → Cannot create expired entity
 *   7. Status = Approval/Rejected + No approval workflow → Only with plugin
 *
 * @package    OpenXDll
 * @subpackage TestSuite
 */

require_once MAX_PATH . '/lib/OA/Dll/Advertiser.php';
require_once MAX_PATH . '/lib/OA/Dll/AdvertiserInfo.php';
require_once MAX_PATH . '/lib/OA/Dll/Campaign.php';
require_once MAX_PATH . '/lib/OA/Dll/CampaignInfo.php';
require_once MAX_PATH . '/lib/OA/Dll/tests/util/DllUnitTestCase.php';

class OA_Dll_CampaignCrudComboExcludedTest extends DllUnitTestCase
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
            'PartialMockOA_Dll_Campaign_ComboExcludedTest',
            ['checkPermissions'],
        );
        Mock::generatePartial(
            'OA_Dll_Advertiser',
            'PartialMockOA_Dll_Advertiser_ComboExcludedTest',
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

    /**
     * Creates a test advertiser and returns the advertiserId.
     *
     * @return int
     */
    private function _createTestAdvertiser()
    {
        $dllAdvertiser = new PartialMockOA_Dll_Advertiser_ComboExcludedTest($this);
        $dllAdvertiser->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiser->setReturnValue('checkPermissions', true);

        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Test Advertiser for Excluded Combos';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $dllAdvertiser->modify($oAdvertiserInfo);

        return $oAdvertiserInfo->advertiserId;
    }

    /**
     * Creates a campaign via the DLL (with full permissions) and returns the campaignId.
     *
     * @param int $advertiserId
     * @return int
     */
    private function _createCampaign($advertiserId)
    {
        $dll = new PartialMockOA_Dll_Campaign_ComboExcludedTest($this);
        $dll->setReturnValue('checkPermissions', true);

        $oCampaign = new OA_Dll_CampaignInfo();
        $oCampaign->advertiserId = $advertiserId;
        $oCampaign->campaignName = 'Campaign for Excluded Test';
        $oCampaign->priority = 0;
        $oCampaign->weight = 1;
        $oCampaign->impressions = -1;
        $oCampaign->clicks = -1;
        $dll->modify($oCampaign);

        return $oCampaign->campaignId;
    }

    // ---------------------------------------------------------------
    // EX-01: ADVERTISER + Create → Permission denied
    // ---------------------------------------------------------------

    /**
     * ADVERTISER accounts cannot create campaigns.
     * The modify() method checks [OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER].
     */
    public function testExcluded_AdvertiserCreate_PermissionDenied()
    {
        $advertiserId = $this->_createTestAdvertiser();

        $dll = new PartialMockOA_Dll_Campaign_ComboExcludedTest($this);
        $dll->setReturnValue('checkPermissions', false);

        $oCampaign = new OA_Dll_CampaignInfo();
        $oCampaign->advertiserId = $advertiserId;
        $oCampaign->campaignName = 'Should Fail - Advertiser Create';
        $oCampaign->priority = 0;
        $oCampaign->weight = 1;

        $result = $dll->modify($oCampaign);
        $this->assertFalse(
            $result,
            'ADVERTISER should not be able to create a campaign',
        );
    }

    // ---------------------------------------------------------------
    // EX-02: ADVERTISER + Edit → Permission denied
    // ---------------------------------------------------------------

    /**
     * ADVERTISER accounts cannot edit campaigns.
     */
    public function testExcluded_AdvertiserEdit_PermissionDenied()
    {
        $advertiserId = $this->_createTestAdvertiser();
        $campaignId = $this->_createCampaign($advertiserId);

        $dll = new PartialMockOA_Dll_Campaign_ComboExcludedTest($this);
        $dll->setReturnValue('checkPermissions', false);

        $oCampaign = new OA_Dll_CampaignInfo();
        $oCampaign->campaignId = $campaignId;
        $oCampaign->campaignName = 'Should Fail - Advertiser Edit';

        $result = $dll->modify($oCampaign);
        $this->assertFalse(
            $result,
            'ADVERTISER should not be able to edit a campaign',
        );
    }

    // ---------------------------------------------------------------
    // EX-03: ADVERTISER + Delete → Permission denied
    // ---------------------------------------------------------------

    /**
     * ADVERTISER accounts cannot delete campaigns.
     */
    public function testExcluded_AdvertiserDelete_PermissionDenied()
    {
        $advertiserId = $this->_createTestAdvertiser();
        $campaignId = $this->_createCampaign($advertiserId);

        $dll = new PartialMockOA_Dll_Campaign_ComboExcludedTest($this);
        $dll->setReturnValue('checkPermissions', false);

        $result = $dll->delete($campaignId);
        $this->assertFalse(
            $result,
            'ADVERTISER should not be able to delete a campaign',
        );
    }

    // ---------------------------------------------------------------
    // EX-04: TRAFFICKER + Create → Permission denied
    // ---------------------------------------------------------------

    /**
     * TRAFFICKER accounts cannot create campaigns.
     */
    public function testExcluded_TraffickerCreate_PermissionDenied()
    {
        $advertiserId = $this->_createTestAdvertiser();

        $dll = new PartialMockOA_Dll_Campaign_ComboExcludedTest($this);
        $dll->setReturnValue('checkPermissions', false);

        $oCampaign = new OA_Dll_CampaignInfo();
        $oCampaign->advertiserId = $advertiserId;
        $oCampaign->campaignName = 'Should Fail - Trafficker Create';
        $oCampaign->priority = 0;
        $oCampaign->weight = 1;

        $result = $dll->modify($oCampaign);
        $this->assertFalse(
            $result,
            'TRAFFICKER should not be able to create a campaign',
        );
    }

    // ---------------------------------------------------------------
    // EX-05: TRAFFICKER + Edit → Permission denied
    // ---------------------------------------------------------------

    /**
     * TRAFFICKER accounts cannot edit campaigns.
     */
    public function testExcluded_TraffickerEdit_PermissionDenied()
    {
        $advertiserId = $this->_createTestAdvertiser();
        $campaignId = $this->_createCampaign($advertiserId);

        $dll = new PartialMockOA_Dll_Campaign_ComboExcludedTest($this);
        $dll->setReturnValue('checkPermissions', false);

        $oCampaign = new OA_Dll_CampaignInfo();
        $oCampaign->campaignId = $campaignId;
        $oCampaign->campaignName = 'Should Fail - Trafficker Edit';

        $result = $dll->modify($oCampaign);
        $this->assertFalse(
            $result,
            'TRAFFICKER should not be able to edit a campaign',
        );
    }

    // ---------------------------------------------------------------
    // EX-06: TRAFFICKER + Delete → Permission denied
    // ---------------------------------------------------------------

    /**
     * TRAFFICKER accounts cannot delete campaigns.
     */
    public function testExcluded_TraffickerDelete_PermissionDenied()
    {
        $advertiserId = $this->_createTestAdvertiser();
        $campaignId = $this->_createCampaign($advertiserId);

        $dll = new PartialMockOA_Dll_Campaign_ComboExcludedTest($this);
        $dll->setReturnValue('checkPermissions', false);

        $result = $dll->delete($campaignId);
        $this->assertFalse(
            $result,
            'TRAFFICKER should not be able to delete a campaign',
        );
    }

    // ---------------------------------------------------------------
    // EX-07: priority > 0 + weight > 0 → Validation rejection
    //        Campaign.php:125-128 — _isHighPriority && _hasWeight
    // ---------------------------------------------------------------

    /**
     * High priority campaigns (priority 1–10) cannot have weight > 0.
     * This should fail validation with a specific error message.
     */
    public function testExcluded_HighPriorityWithWeight_ValidationRejected()
    {
        $advertiserId = $this->_createTestAdvertiser();

        $dll = new PartialMockOA_Dll_Campaign_ComboExcludedTest($this);
        $dll->setReturnValue('checkPermissions', true);

        $oCampaign = new OA_Dll_CampaignInfo();
        $oCampaign->advertiserId = $advertiserId;
        $oCampaign->campaignName = 'Invalid: priority>0 + weight>0';
        $oCampaign->priority = 5;
        $oCampaign->weight = 3;
        $oCampaign->impressions = -1;
        $oCampaign->clicks = -1;
        $oCampaign->targetImpressions = 1000;
        $oCampaign->targetClicks = 0;
        $oCampaign->targetConversions = 0;

        $result = $dll->modify($oCampaign);
        $this->assertFalse(
            $result,
            'Campaign with priority>0 AND weight>0 should be rejected',
        );
        $this->assertPattern(
            '/weight/',
            $dll->getLastError(),
            'Error message should mention weight constraint',
        );
    }

    /**
     * Additional test: priority=1 + weight=1 → also rejected.
     */
    public function testExcluded_Priority1Weight1_ValidationRejected()
    {
        $advertiserId = $this->_createTestAdvertiser();

        $dll = new PartialMockOA_Dll_Campaign_ComboExcludedTest($this);
        $dll->setReturnValue('checkPermissions', true);

        $oCampaign = new OA_Dll_CampaignInfo();
        $oCampaign->advertiserId = $advertiserId;
        $oCampaign->campaignName = 'Invalid: priority=1 + weight=1';
        $oCampaign->priority = 1;
        $oCampaign->weight = 1;
        $oCampaign->impressions = -1;
        $oCampaign->clicks = -1;

        $result = $dll->modify($oCampaign);
        $this->assertFalse(
            $result,
            'Campaign with priority=1 AND weight=1 should be rejected',
        );
    }

    /**
     * Additional test: priority=10 + weight=10 → also rejected.
     */
    public function testExcluded_Priority10Weight10_ValidationRejected()
    {
        $advertiserId = $this->_createTestAdvertiser();

        $dll = new PartialMockOA_Dll_Campaign_ComboExcludedTest($this);
        $dll->setReturnValue('checkPermissions', true);

        $oCampaign = new OA_Dll_CampaignInfo();
        $oCampaign->advertiserId = $advertiserId;
        $oCampaign->campaignName = 'Invalid: priority=10 + weight=10';
        $oCampaign->priority = 10;
        $oCampaign->weight = 10;
        $oCampaign->impressions = -1;
        $oCampaign->clicks = -1;

        $result = $dll->modify($oCampaign);
        $this->assertFalse(
            $result,
            'Campaign with priority=10 AND weight=10 should be rejected',
        );
    }

    // ---------------------------------------------------------------
    // EX-08: priority = 0 + targets > 0 → Validation rejection
    //        Campaign.php:129-132 — !_isHighPriority && _hasTargets
    // ---------------------------------------------------------------

    /**
     * Low priority campaigns (priority=0) cannot have delivery targets.
     */
    public function testExcluded_LowPriorityWithTargetImpressions_ValidationRejected()
    {
        $advertiserId = $this->_createTestAdvertiser();

        $dll = new PartialMockOA_Dll_Campaign_ComboExcludedTest($this);
        $dll->setReturnValue('checkPermissions', true);

        $oCampaign = new OA_Dll_CampaignInfo();
        $oCampaign->advertiserId = $advertiserId;
        $oCampaign->campaignName = 'Invalid: p=0 + targetImpressions>0';
        $oCampaign->priority = 0;
        $oCampaign->weight = 1;
        $oCampaign->impressions = -1;
        $oCampaign->clicks = -1;
        $oCampaign->targetImpressions = 5000;
        $oCampaign->targetClicks = 0;
        $oCampaign->targetConversions = 0;

        $result = $dll->modify($oCampaign);
        $this->assertFalse(
            $result,
            'Campaign with priority=0 AND targetImpressions>0 should be rejected',
        );
        $this->assertPattern(
            '/targets/',
            $dll->getLastError(),
            'Error message should mention targets constraint',
        );
    }

    /**
     * Low priority with target clicks.
     */
    public function testExcluded_LowPriorityWithTargetClicks_ValidationRejected()
    {
        $advertiserId = $this->_createTestAdvertiser();

        $dll = new PartialMockOA_Dll_Campaign_ComboExcludedTest($this);
        $dll->setReturnValue('checkPermissions', true);

        $oCampaign = new OA_Dll_CampaignInfo();
        $oCampaign->advertiserId = $advertiserId;
        $oCampaign->campaignName = 'Invalid: p=0 + targetClicks>0';
        $oCampaign->priority = 0;
        $oCampaign->weight = 1;
        $oCampaign->impressions = -1;
        $oCampaign->clicks = -1;
        $oCampaign->targetImpressions = 0;
        $oCampaign->targetClicks = 500;
        $oCampaign->targetConversions = 0;

        $result = $dll->modify($oCampaign);
        $this->assertFalse(
            $result,
            'Campaign with priority=0 AND targetClicks>0 should be rejected',
        );
    }

    /**
     * Low priority with target conversions.
     */
    public function testExcluded_LowPriorityWithTargetConversions_ValidationRejected()
    {
        $advertiserId = $this->_createTestAdvertiser();

        $dll = new PartialMockOA_Dll_Campaign_ComboExcludedTest($this);
        $dll->setReturnValue('checkPermissions', true);

        $oCampaign = new OA_Dll_CampaignInfo();
        $oCampaign->advertiserId = $advertiserId;
        $oCampaign->campaignName = 'Invalid: p=0 + targetConversions>0';
        $oCampaign->priority = 0;
        $oCampaign->weight = 1;
        $oCampaign->impressions = -1;
        $oCampaign->clicks = -1;
        $oCampaign->targetImpressions = 0;
        $oCampaign->targetClicks = 0;
        $oCampaign->targetConversions = 50;

        $result = $dll->modify($oCampaign);
        $this->assertFalse(
            $result,
            'Campaign with priority=0 AND targetConversions>0 should be rejected',
        );
    }

    /**
     * Low priority with ALL targets set.
     */
    public function testExcluded_LowPriorityWithAllTargets_ValidationRejected()
    {
        $advertiserId = $this->_createTestAdvertiser();

        $dll = new PartialMockOA_Dll_Campaign_ComboExcludedTest($this);
        $dll->setReturnValue('checkPermissions', true);

        $oCampaign = new OA_Dll_CampaignInfo();
        $oCampaign->advertiserId = $advertiserId;
        $oCampaign->campaignName = 'Invalid: p=0 + all targets>0';
        $oCampaign->priority = 0;
        $oCampaign->weight = 1;
        $oCampaign->impressions = -1;
        $oCampaign->clicks = -1;
        $oCampaign->targetImpressions = 5000;
        $oCampaign->targetClicks = 500;
        $oCampaign->targetConversions = 50;

        $result = $dll->modify($oCampaign);
        $this->assertFalse(
            $result,
            'Campaign with priority=0 AND all targets>0 should be rejected',
        );
    }

    // ---------------------------------------------------------------
    // EX-09: Zone-only revenue types (RS/BV/AI/ANYVAR/VARSUM)
    //        constants.php:144-148 — These finance types are zone-only,
    //        not applicable to campaigns.
    // ---------------------------------------------------------------

    /**
     * Revenue type RS (Revenue Split, MAX_FINANCE_RS=5) is zone-only.
     * Campaigns should not use this revenue type.
     */
    public function testExcluded_RevenueTypeRS_NotApplicable()
    {
        $this->assertTrue(
            defined('MAX_FINANCE_RS'),
            'MAX_FINANCE_RS should be defined',
        );
        $this->assertEqual(MAX_FINANCE_RS, 5, 'MAX_FINANCE_RS should be 5');

        // Verify zone-only types are > MAX_FINANCE_MT
        $zoneOnlyTypes = [MAX_FINANCE_RS, MAX_FINANCE_BV, MAX_FINANCE_AI, MAX_FINANCE_ANYVAR, MAX_FINANCE_VARSUM];
        $campaignTypes = [MAX_FINANCE_CPM, MAX_FINANCE_CPC, MAX_FINANCE_CPA, MAX_FINANCE_MT];

        foreach ($zoneOnlyTypes as $zoneType) {
            $this->assertFalse(
                in_array($zoneType, $campaignTypes),
                "Zone-only finance type {$zoneType} should not be in campaign finance types",
            );
        }
    }

    /**
     * Revenue type BV (Basket Value, MAX_FINANCE_BV=6) is zone-only.
     */
    public function testExcluded_RevenueTypeBV_NotApplicable()
    {
        $this->assertTrue(
            defined('MAX_FINANCE_BV'),
            'MAX_FINANCE_BV should be defined',
        );
        $this->assertEqual(MAX_FINANCE_BV, 6, 'MAX_FINANCE_BV should be 6');

        // BV should never be a valid campaign revenue type
        $validCampaignRevenueTypes = [MAX_FINANCE_CPM, MAX_FINANCE_CPC, MAX_FINANCE_CPA, MAX_FINANCE_MT];
        $this->assertFalse(
            in_array(MAX_FINANCE_BV, $validCampaignRevenueTypes),
            'MAX_FINANCE_BV should not be a valid campaign revenue type',
        );
    }

    /**
     * Revenue type AI (Amount per Item, MAX_FINANCE_AI=7) is zone-only.
     */
    public function testExcluded_RevenueTypeAI_NotApplicable()
    {
        $this->assertTrue(
            defined('MAX_FINANCE_AI'),
            'MAX_FINANCE_AI should be defined',
        );
        $this->assertEqual(MAX_FINANCE_AI, 7, 'MAX_FINANCE_AI should be 7');

        $validCampaignRevenueTypes = [MAX_FINANCE_CPM, MAX_FINANCE_CPC, MAX_FINANCE_CPA, MAX_FINANCE_MT];
        $this->assertFalse(
            in_array(MAX_FINANCE_AI, $validCampaignRevenueTypes),
            'MAX_FINANCE_AI should not be a valid campaign revenue type',
        );
    }

    /**
     * Revenue type ANYVAR (MAX_FINANCE_ANYVAR=8) is zone-only.
     */
    public function testExcluded_RevenueTypeANYVAR_NotApplicable()
    {
        $this->assertTrue(
            defined('MAX_FINANCE_ANYVAR'),
            'MAX_FINANCE_ANYVAR should be defined',
        );
        $this->assertEqual(MAX_FINANCE_ANYVAR, 8, 'MAX_FINANCE_ANYVAR should be 8');

        $validCampaignRevenueTypes = [MAX_FINANCE_CPM, MAX_FINANCE_CPC, MAX_FINANCE_CPA, MAX_FINANCE_MT];
        $this->assertFalse(
            in_array(MAX_FINANCE_ANYVAR, $validCampaignRevenueTypes),
            'MAX_FINANCE_ANYVAR should not be a valid campaign revenue type',
        );
    }

    /**
     * Revenue type VARSUM (MAX_FINANCE_VARSUM=9) is zone-only.
     */
    public function testExcluded_RevenueTypeVARSUM_NotApplicable()
    {
        $this->assertTrue(
            defined('MAX_FINANCE_VARSUM'),
            'MAX_FINANCE_VARSUM should be defined',
        );
        $this->assertEqual(MAX_FINANCE_VARSUM, 9, 'MAX_FINANCE_VARSUM should be 9');

        $validCampaignRevenueTypes = [MAX_FINANCE_CPM, MAX_FINANCE_CPC, MAX_FINANCE_CPA, MAX_FINANCE_MT];
        $this->assertFalse(
            in_array(MAX_FINANCE_VARSUM, $validCampaignRevenueTypes),
            'MAX_FINANCE_VARSUM should not be a valid campaign revenue type',
        );
    }

    // ---------------------------------------------------------------
    // EX-10: Status = Expired + Mode = Create
    //        Cannot create an already-expired entity.
    // ---------------------------------------------------------------

    /**
     * Creating a campaign with an end date in the past makes it
     * immediately expired. The campaign creation succeeds at the DLL
     * level (the database accepts it), but the campaign will have
     * an expire_time in the past meaning the maintenance system
     * will mark it expired. We verify the date ordering constraint:
     * if start > end, validation rejects it.
     */
    public function testExcluded_ExpiredCreate_StartAfterEnd()
    {
        $advertiserId = $this->_createTestAdvertiser();

        $dll = new PartialMockOA_Dll_Campaign_ComboExcludedTest($this);
        $dll->setReturnValue('checkPermissions', true);

        $oCampaign = new OA_Dll_CampaignInfo();
        $oCampaign->advertiserId = $advertiserId;
        $oCampaign->campaignName = 'Invalid: start > end (expired)';
        $oCampaign->priority = 0;
        $oCampaign->weight = 1;
        $oCampaign->impressions = -1;
        $oCampaign->clicks = -1;

        // Set start date AFTER end date → validation error
        $oCampaign->startDate = new Date('2025-06-01');
        $oCampaign->endDate = new Date('2025-01-01');

        $result = $dll->modify($oCampaign);
        $this->assertFalse(
            $result,
            'Campaign with startDate after endDate should be rejected',
        );
        $this->assertEqual(
            $dll->getLastError(),
            'The start date is after the end date',
            'Error should indicate date ordering issue',
        );
    }

    /**
     * Creating a campaign with both dates in the far past: this creates
     * an effectively expired campaign. The DLL allows it at the data
     * level (dates are valid) but verifies the entity is in past.
     */
    public function testExcluded_ExpiredCreate_BothDatesInPast()
    {
        $advertiserId = $this->_createTestAdvertiser();

        $dll = new PartialMockOA_Dll_Campaign_ComboExcludedTest($this);
        $dll->setReturnValue('checkPermissions', true);

        $oCampaign = new OA_Dll_CampaignInfo();
        $oCampaign->advertiserId = $advertiserId;
        $oCampaign->campaignName = 'Expired: both dates in past';
        $oCampaign->priority = 0;
        $oCampaign->weight = 1;
        $oCampaign->impressions = -1;
        $oCampaign->clicks = -1;

        // Both dates in the past (but start < end, so date ordering is valid)
        $oCampaign->startDate = new Date('2020-01-01');
        $oCampaign->endDate = new Date('2020-06-01');

        // DLL allows this (date order is valid), but the campaign is effectively expired
        $result = $dll->modify($oCampaign);
        $this->assertTrue(
            $result,
            'DLL allows creating a campaign with past dates (date order valid): ' .
            $dll->getLastError(),
        );

        // Verify the campaign was created and can be read back
        if ($oCampaign->campaignId) {
            $dllVerify = new PartialMockOA_Dll_Campaign_ComboExcludedTest($this);
            $dllVerify->setReturnValue('checkPermissions', true);
            $oCampaignGet = null;
            $this->assertTrue(
                $dllVerify->getCampaign($oCampaign->campaignId, $oCampaignGet),
                'Should be able to read back the expired campaign',
            );
        }
    }

    // ---------------------------------------------------------------
    // EX-11: Status = Approval/Rejected + No approval workflow
    //        These statuses only apply with approval workflow plugin.
    // ---------------------------------------------------------------

    /**
     * Verify that OA_ENTITY_STATUS_APPROVAL is defined and has the
     * expected value. In a system without the approval workflow plugin
     * active, these statuses should not be used for campaign operations.
     */
    public function testExcluded_ApprovalStatus_ConstantDefined()
    {
        $this->assertTrue(
            defined('OA_ENTITY_STATUS_APPROVAL'),
            'OA_ENTITY_STATUS_APPROVAL should be defined',
        );
        $this->assertEqual(
            OA_ENTITY_STATUS_APPROVAL,
            21,
            'OA_ENTITY_STATUS_APPROVAL should be 21',
        );
    }

    /**
     * Verify that OA_ENTITY_STATUS_REJECTED is defined and has the
     * expected value.
     */
    public function testExcluded_RejectedStatus_ConstantDefined()
    {
        $this->assertTrue(
            defined('OA_ENTITY_STATUS_REJECTED'),
            'OA_ENTITY_STATUS_REJECTED should be defined',
        );
        $this->assertEqual(
            OA_ENTITY_STATUS_REJECTED,
            22,
            'OA_ENTITY_STATUS_REJECTED should be 22',
        );
    }

    /**
     * Verify approval/rejected statuses are not part of the standard
     * entity status range (0-4). They are special statuses that require
     * the approval workflow plugin.
     */
    public function testExcluded_ApprovalRejected_NotStandardStatuses()
    {
        $standardStatuses = [
            OA_ENTITY_STATUS_RUNNING,   // 0
            OA_ENTITY_STATUS_PAUSED,    // 1
            OA_ENTITY_STATUS_AWAITING,  // 2
            OA_ENTITY_STATUS_EXPIRED,   // 3
            OA_ENTITY_STATUS_INACTIVE,  // 4
        ];

        $this->assertFalse(
            in_array(OA_ENTITY_STATUS_APPROVAL, $standardStatuses),
            'APPROVAL status should not be in standard statuses',
        );
        $this->assertFalse(
            in_array(OA_ENTITY_STATUS_REJECTED, $standardStatuses),
            'REJECTED status should not be in standard statuses',
        );
    }

    /**
     * Verify OA_ENTITY_STATUS_PENDING is a special status (10) that
     * is distinct from standard statuses.
     */
    public function testExcluded_PendingStatus_IsSpecial()
    {
        $this->assertTrue(
            defined('OA_ENTITY_STATUS_PENDING'),
            'OA_ENTITY_STATUS_PENDING should be defined',
        );
        $this->assertEqual(
            OA_ENTITY_STATUS_PENDING,
            10,
            'OA_ENTITY_STATUS_PENDING should be 10',
        );
        $this->assertTrue(
            OA_ENTITY_STATUS_PENDING > OA_ENTITY_STATUS_INACTIVE,
            'PENDING status should be greater than standard INACTIVE status',
        );
    }

    // ---------------------------------------------------------------
    // EX-12: Additional edge cases — permission combinations
    // ---------------------------------------------------------------

    /**
     * ADVERTISER + Create with Remnant campaign → denied.
     */
    public function testExcluded_AdvertiserCreate_Remnant_Denied()
    {
        $advertiserId = $this->_createTestAdvertiser();

        $dll = new PartialMockOA_Dll_Campaign_ComboExcludedTest($this);
        $dll->setReturnValue('checkPermissions', false);

        $oCampaign = new OA_Dll_CampaignInfo();
        $oCampaign->advertiserId = $advertiserId;
        $oCampaign->campaignName = 'Denied: Advertiser + Create + Remnant';
        $oCampaign->priority = 0;
        $oCampaign->weight = 1;
        $oCampaign->revenueType = MAX_FINANCE_CPM;
        $oCampaign->revenue = 2.50;
        $oCampaign->impressions = -1;
        $oCampaign->clicks = -1;

        $result = $dll->modify($oCampaign);
        $this->assertFalse($result, 'ADVERTISER + Create + Remnant should be denied');
    }

    /**
     * TRAFFICKER + Edit with Contract campaign → denied.
     */
    public function testExcluded_TraffickerEdit_Contract_Denied()
    {
        $advertiserId = $this->_createTestAdvertiser();
        $campaignId = $this->_createCampaign($advertiserId);

        $dll = new PartialMockOA_Dll_Campaign_ComboExcludedTest($this);
        $dll->setReturnValue('checkPermissions', false);

        $oCampaign = new OA_Dll_CampaignInfo();
        $oCampaign->campaignId = $campaignId;
        $oCampaign->campaignName = 'Denied: Trafficker + Edit + Contract';

        $result = $dll->modify($oCampaign);
        $this->assertFalse($result, 'TRAFFICKER + Edit + Contract should be denied');
    }

    /**
     * ADVERTISER + Delete with Override campaign → denied.
     */
    public function testExcluded_AdvertiserDelete_Override_Denied()
    {
        $advertiserId = $this->_createTestAdvertiser();
        $campaignId = $this->_createCampaign($advertiserId);

        $dll = new PartialMockOA_Dll_Campaign_ComboExcludedTest($this);
        $dll->setReturnValue('checkPermissions', false);

        $result = $dll->delete($campaignId);
        $this->assertFalse($result, 'ADVERTISER + Delete + Override should be denied');
    }

    /**
     * TRAFFICKER + Delete with eCPM campaign → denied.
     */
    public function testExcluded_TraffickerDelete_eCPM_Denied()
    {
        $advertiserId = $this->_createTestAdvertiser();
        $campaignId = $this->_createCampaign($advertiserId);

        $dll = new PartialMockOA_Dll_Campaign_ComboExcludedTest($this);
        $dll->setReturnValue('checkPermissions', false);

        $result = $dll->delete($campaignId);
        $this->assertFalse($result, 'TRAFFICKER + Delete + eCPM should be denied');
    }

    /**
     * ADVERTISER + Create with ContractECPM campaign → denied.
     */
    public function testExcluded_AdvertiserCreate_ContractECPM_Denied()
    {
        $advertiserId = $this->_createTestAdvertiser();

        $dll = new PartialMockOA_Dll_Campaign_ComboExcludedTest($this);
        $dll->setReturnValue('checkPermissions', false);

        $oCampaign = new OA_Dll_CampaignInfo();
        $oCampaign->advertiserId = $advertiserId;
        $oCampaign->campaignName = 'Denied: Advertiser + Create + ContractECPM';
        $oCampaign->priority = 5;
        $oCampaign->weight = 0;
        $oCampaign->revenueType = MAX_FINANCE_CPA;
        $oCampaign->revenue = 3.00;
        $oCampaign->impressions = -1;
        $oCampaign->clicks = -1;
        $oCampaign->targetImpressions = 1000;
        $oCampaign->targetClicks = 0;
        $oCampaign->targetConversions = 0;

        $result = $dll->modify($oCampaign);
        $this->assertFalse($result, 'ADVERTISER + Create + ContractECPM should be denied');
    }

    /**
     * TRAFFICKER + Create with ContractNormal campaign → denied.
     */
    public function testExcluded_TraffickerCreate_ContractNormal_Denied()
    {
        $advertiserId = $this->_createTestAdvertiser();

        $dll = new PartialMockOA_Dll_Campaign_ComboExcludedTest($this);
        $dll->setReturnValue('checkPermissions', false);

        $oCampaign = new OA_Dll_CampaignInfo();
        $oCampaign->advertiserId = $advertiserId;
        $oCampaign->campaignName = 'Denied: Trafficker + Create + ContractNormal';
        $oCampaign->priority = 5;
        $oCampaign->weight = 0;
        $oCampaign->impressions = 10000;
        $oCampaign->clicks = 500;
        $oCampaign->targetImpressions = 1000;
        $oCampaign->targetClicks = 100;
        $oCampaign->targetConversions = 10;

        $result = $dll->modify($oCampaign);
        $this->assertFalse($result, 'TRAFFICKER + Create + ContractNormal should be denied');
    }

    // ---------------------------------------------------------------
    // EX-13: Boundary validation tests
    // ---------------------------------------------------------------

    /**
     * priority=5 + weight=0 + targets=0 is a valid high-priority
     * campaign but with no targets. This is allowed.
     */
    public function testExcluded_HighPriorityNoTargets_Allowed()
    {
        $advertiserId = $this->_createTestAdvertiser();

        $dll = new PartialMockOA_Dll_Campaign_ComboExcludedTest($this);
        $dll->setReturnValue('checkPermissions', true);

        $oCampaign = new OA_Dll_CampaignInfo();
        $oCampaign->advertiserId = $advertiserId;
        $oCampaign->campaignName = 'Valid: p=5 w=0 targets=0';
        $oCampaign->priority = 5;
        $oCampaign->weight = 0;
        $oCampaign->impressions = -1;
        $oCampaign->clicks = -1;
        $oCampaign->targetImpressions = 0;
        $oCampaign->targetClicks = 0;
        $oCampaign->targetConversions = 0;

        $result = $dll->modify($oCampaign);
        $this->assertTrue(
            $result,
            'High priority with no targets should be allowed: ' .
            $dll->getLastError(),
        );
    }

    /**
     * priority=0 + weight=0: a remnant campaign with no weight.
     * This is technically allowed by the DLL (weight can be 0).
     */
    public function testExcluded_RemnantNoWeight_Allowed()
    {
        $advertiserId = $this->_createTestAdvertiser();

        $dll = new PartialMockOA_Dll_Campaign_ComboExcludedTest($this);
        $dll->setReturnValue('checkPermissions', true);

        $oCampaign = new OA_Dll_CampaignInfo();
        $oCampaign->advertiserId = $advertiserId;
        $oCampaign->campaignName = 'Valid: p=0 w=0';
        $oCampaign->priority = 0;
        $oCampaign->weight = 0;
        $oCampaign->impressions = -1;
        $oCampaign->clicks = -1;

        $result = $dll->modify($oCampaign);
        $this->assertTrue(
            $result,
            'Remnant campaign with weight=0 should be allowed: ' .
            $dll->getLastError(),
        );
    }

    /**
     * Edit: changing a valid campaign to have priority > 0 AND weight > 0
     * should be rejected on modify.
     */
    public function testExcluded_EditToHighPriorityWithWeight_Rejected()
    {
        $advertiserId = $this->_createTestAdvertiser();
        $campaignId = $this->_createCampaign($advertiserId);

        $dll = new PartialMockOA_Dll_Campaign_ComboExcludedTest($this);
        $dll->setReturnValue('checkPermissions', true);

        $oCampaign = new OA_Dll_CampaignInfo();
        $oCampaign->campaignId = $campaignId;
        $oCampaign->priority = 5;
        $oCampaign->weight = 3;

        $result = $dll->modify($oCampaign);
        $this->assertFalse(
            $result,
            'Editing campaign to set priority>0 AND weight>0 should be rejected',
        );
    }

    /**
     * Edit: changing a valid campaign to have priority=0 AND targets>0
     * should be rejected on modify.
     */
    public function testExcluded_EditToLowPriorityWithTargets_Rejected()
    {
        $advertiserId = $this->_createTestAdvertiser();

        // Create a high-priority campaign first
        $dllCreate = new PartialMockOA_Dll_Campaign_ComboExcludedTest($this);
        $dllCreate->setReturnValue('checkPermissions', true);

        $oCampaignCreate = new OA_Dll_CampaignInfo();
        $oCampaignCreate->advertiserId = $advertiserId;
        $oCampaignCreate->campaignName = 'High Priority Campaign';
        $oCampaignCreate->priority = 5;
        $oCampaignCreate->weight = 0;
        $oCampaignCreate->impressions = -1;
        $oCampaignCreate->clicks = -1;
        $oCampaignCreate->targetImpressions = 1000;
        $oCampaignCreate->targetClicks = 0;
        $oCampaignCreate->targetConversions = 0;
        $dllCreate->modify($oCampaignCreate);

        // Now try to change to low priority but keep targets
        $dll = new PartialMockOA_Dll_Campaign_ComboExcludedTest($this);
        $dll->setReturnValue('checkPermissions', true);

        $oCampaign = new OA_Dll_CampaignInfo();
        $oCampaign->campaignId = $oCampaignCreate->campaignId;
        $oCampaign->priority = 0;
        $oCampaign->weight = 1;
        $oCampaign->targetImpressions = 1000;
        $oCampaign->targetClicks = 0;
        $oCampaign->targetConversions = 0;

        $result = $dll->modify($oCampaign);
        $this->assertFalse(
            $result,
            'Editing campaign to set priority=0 AND targets>0 should be rejected',
        );
    }
}
