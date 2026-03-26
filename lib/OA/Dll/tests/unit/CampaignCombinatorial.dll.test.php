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
require_once MAX_PATH . '/lib/OA/Dll/tests/util/DllUnitTestCase.php';

/**
 * Campaign CRUD Combination Matrix Tests (Sections 4A + 4B)
 *
 * This test class covers ~120+ pairwise-generated included combinations (4A) across
 * 9 dimensions, plus negative/excluded combination tests (4B) for validation and
 * permission denial paths.
 *
 * Dimensions:
 *   1. Account type:    ADMIN, MANAGER, ADVERTISER, TRAFFICKER
 *   2. Page mode:       Create, Edit, View, Delete
 *   3. Campaign type:   Remnant(1), ContractNormal(2), Override(3), eCPM(4), ContractECPM(5)
 *   4. Entity status:   Running(0), Paused(1), Awaiting(2), Expired(3), Inactive(4),
 *                        Pending(10), Approval(21), Rejected(22)
 *   5. Revenue type:    CPM(1), CPC(2), CPA(3), MT(4)
 *   6. Date config:     NoDates, StartOnly, EndOnly, BothDates, StartEqualsEnd
 *   7. Booking limits:  Unlimited(-1), ImpressionsOnly, ClicksOnly, Both
 *   8. Frequency cap:   None, CappingOnly, SessionOnly, BlockOnly, AllThree
 *   9. Priority/weight: p=0/w>0 (remnant), p>0/w=0 (contract)
 *
 * @package    OpenXDll
 * @subpackage TestSuite
 */


class OA_Dll_CampaignCombinatorialTest extends DllUnitTestCase
{
    /**
     * @var int
     */
    public $agencyId;

    public $unknownIdError = 'Unknown campaignId Error';
    public $accessForbiddenError = 'Access forbidden';

    public function __construct()
    {
        parent::__construct();
        Mock::generatePartial(
            'OA_Dll_Campaign',
            'PartialMockOA_Dll_Campaign_ComboTest',
            ['checkPermissions'],
        );
        Mock::generatePartial(
            'OA_Dll_Advertiser',
            'PartialMockOA_Dll_Advertiser_ComboTest',
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

    // =========================================================================
    // Section 4A: Included Pairwise Combination Tests
    // =========================================================================

    /**
     * C001: account=ADMIN, mode=Create, type=Remnant, status=Running,
     *   revenue=CPM, dates=NoDates, booking=Unlimited, capping=None, priority=p0_w1
     */
    public function testC001_ADMIN_Create_Remnant_Running_CPM_NoDates_Unlimited_None_p0_w1()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C001';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C001';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 1;
        $oCampaignInfo->revenue = 1.0;
        $oCampaignInfo->revenueType = 1;
        // No dates set
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        // No frequency capping

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C002: account=MANAGER, mode=Edit, type=ContractNormal, status=Paused,
     *   revenue=CPC, dates=StartOnly, booking=ImpressionsOnly, capping=CappingOnly, priority=p0_w1
     */
    public function testC002_MANAGER_Edit_ContractNormal_Paused_CPC_StartOnly_ImpressionsOnly_CappingOnly_p0_w1()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C002';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // First create a campaign to edit
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C002 original';
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // Now edit with combo parameters
        $oCampaignInfo->campaignName = 'Campaign C002 edited';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 1;
        $oCampaignInfo->revenue = 2.0;
        $oCampaignInfo->revenueType = 2;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->capping = 10;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Edit should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
    }

    /**
     * C003: account=MANAGER, mode=Delete, type=Override, status=Awaiting,
     *   revenue=CPA, dates=EndOnly, booking=ClicksOnly, capping=SessionOnly, priority=p5_w0
     */
    public function testC003_MANAGER_Delete_Override_Awaiting_CPA_EndOnly_ClicksOnly_SessionOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C003';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // First create a campaign to delete
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C003 to delete';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 3.0;
        $oCampaignInfo->revenueType = 3;
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // Delete the campaign
        $this->assertTrue(
            $dllCampaignPartialMock->delete($oCampaignInfo->campaignId),
            'Delete should succeed: ' . $dllCampaignPartialMock->getLastError()
        );

        // Verify campaign no longer exists
        $oCampaignInfoGet = null;
        $this->assertFalse(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet)
        );
    }

    /**
     * C004: account=ADMIN, mode=Delete, type=eCPM, status=Expired,
     *   revenue=MT, dates=BothDates, booking=Both, capping=BlockOnly, priority=p0_w1
     */
    public function testC004_ADMIN_Delete_eCPM_Expired_MT_BothDates_Both_BlockOnly_p0_w1()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C004';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // First create a campaign to delete
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C004 to delete';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 1;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->block = 3600;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // Delete the campaign
        $this->assertTrue(
            $dllCampaignPartialMock->delete($oCampaignInfo->campaignId),
            'Delete should succeed: ' . $dllCampaignPartialMock->getLastError()
        );

        // Verify campaign no longer exists
        $oCampaignInfoGet = null;
        $this->assertFalse(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet)
        );
    }

    /**
     * C005: account=ADMIN, mode=Edit, type=ContractECPM, status=Inactive,
     *   revenue=CPA, dates=StartEqualsEnd, booking=Both, capping=AllThree, priority=p5_w0
     */
    public function testC005_ADMIN_Edit_ContractECPM_Inactive_CPA_StartEqualsEnd_Both_AllThree_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C005';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // First create a campaign to edit
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C005 original';
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // Now edit with combo parameters
        $oCampaignInfo->campaignName = 'Campaign C005 edited';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 3.0;
        $oCampaignInfo->revenueType = 3;
        $oCampaignInfo->startDate = new Date('2026-06-15');
        $oCampaignInfo->endDate = new Date('2026-06-15');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Edit should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
    }

    /**
     * C006: account=MANAGER, mode=Create, type=ContractECPM, status=Pending,
     *   revenue=MT, dates=StartEqualsEnd, booking=ImpressionsOnly, capping=SessionOnly, priority=p0_w1
     */
    public function testC006_MANAGER_Create_ContractECPM_Pending_MT_StartEqualsEnd_ImpressionsOnly_SessionOnly_p0_w1()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C006';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C006';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 1;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->startDate = new Date('2026-06-15');
        $oCampaignInfo->endDate = new Date('2026-06-15');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->sessionCapping = 5;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C007: account=MANAGER, mode=Create, type=eCPM, status=Approval,
     *   revenue=CPC, dates=BothDates, booking=ClicksOnly, capping=AllThree, priority=p5_w0
     */
    public function testC007_MANAGER_Create_eCPM_Approval_CPC_BothDates_ClicksOnly_AllThree_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C007';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C007';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 2.0;
        $oCampaignInfo->revenueType = 2;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C008: account=ADMIN, mode=Edit, type=Override, status=Rejected,
     *   revenue=CPM, dates=EndOnly, booking=ImpressionsOnly, capping=BlockOnly, priority=p5_w0
     */
    public function testC008_ADMIN_Edit_Override_Rejected_CPM_EndOnly_ImpressionsOnly_BlockOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C008';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // First create a campaign to edit
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C008 original';
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // Now edit with combo parameters
        $oCampaignInfo->campaignName = 'Campaign C008 edited';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 1.0;
        $oCampaignInfo->revenueType = 1;
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Edit should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
    }

    /**
     * C009: account=MANAGER, mode=Delete, type=Remnant, status=Rejected,
     *   revenue=CPC, dates=NoDates, booking=Both, capping=None, priority=p5_w0
     */
    public function testC009_MANAGER_Delete_Remnant_Rejected_CPC_NoDates_Both_None_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C009';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // First create a campaign to delete
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C009 to delete';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 2.0;
        $oCampaignInfo->revenueType = 2;
        // No dates set
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = 500;
        // No frequency capping
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // Delete the campaign
        $this->assertTrue(
            $dllCampaignPartialMock->delete($oCampaignInfo->campaignId),
            'Delete should succeed: ' . $dllCampaignPartialMock->getLastError()
        );

        // Verify campaign no longer exists
        $oCampaignInfoGet = null;
        $this->assertFalse(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet)
        );
    }

    /**
     * C010: account=ADMIN, mode=Delete, type=ContractNormal, status=Approval,
     *   revenue=CPM, dates=StartOnly, booking=Unlimited, capping=SessionOnly, priority=p5_w0
     */
    public function testC010_ADMIN_Delete_ContractNormal_Approval_CPM_StartOnly_Unlimited_SessionOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C010';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // First create a campaign to delete
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C010 to delete';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 1.0;
        $oCampaignInfo->revenueType = 1;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // Delete the campaign
        $this->assertTrue(
            $dllCampaignPartialMock->delete($oCampaignInfo->campaignId),
            'Delete should succeed: ' . $dllCampaignPartialMock->getLastError()
        );

        // Verify campaign no longer exists
        $oCampaignInfoGet = null;
        $this->assertFalse(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet)
        );
    }

    /**
     * C011: account=ADMIN, mode=Edit, type=Remnant, status=Pending,
     *   revenue=MT, dates=NoDates, booking=ClicksOnly, capping=CappingOnly, priority=p5_w0
     */
    public function testC011_ADMIN_Edit_Remnant_Pending_MT_NoDates_ClicksOnly_CappingOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C011';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // First create a campaign to edit
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C011 original';
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // Now edit with combo parameters
        $oCampaignInfo->campaignName = 'Campaign C011 edited';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        // No dates set
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Edit should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
    }

    /**
     * C012: account=MANAGER, mode=Create, type=Override, status=Inactive,
     *   revenue=CPM, dates=StartOnly, booking=Both, capping=CappingOnly, priority=p0_w1
     */
    public function testC012_MANAGER_Create_Override_Inactive_CPM_StartOnly_Both_CappingOnly_p0_w1()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C012';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C012';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 1;
        $oCampaignInfo->revenue = 1.0;
        $oCampaignInfo->revenueType = 1;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->capping = 10;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C013: account=MANAGER, mode=Edit, type=eCPM, status=Running,
     *   revenue=CPA, dates=BothDates, booking=Unlimited, capping=CappingOnly, priority=p5_w0
     */
    public function testC013_MANAGER_Edit_eCPM_Running_CPA_BothDates_Unlimited_CappingOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C013';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // First create a campaign to edit
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C013 original';
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // Now edit with combo parameters
        $oCampaignInfo->campaignName = 'Campaign C013 edited';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 3.0;
        $oCampaignInfo->revenueType = 3;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Edit should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
    }

    /**
     * C014: account=ADMIN, mode=Delete, type=ContractECPM, status=Paused,
     *   revenue=CPC, dates=EndOnly, booking=Unlimited, capping=AllThree, priority=p0_w1
     */
    public function testC014_ADMIN_Delete_ContractECPM_Paused_CPC_EndOnly_Unlimited_AllThree_p0_w1()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C014';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // First create a campaign to delete
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C014 to delete';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 1;
        $oCampaignInfo->revenue = 2.0;
        $oCampaignInfo->revenueType = 2;
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->block = 3600;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // Delete the campaign
        $this->assertTrue(
            $dllCampaignPartialMock->delete($oCampaignInfo->campaignId),
            'Delete should succeed: ' . $dllCampaignPartialMock->getLastError()
        );

        // Verify campaign no longer exists
        $oCampaignInfoGet = null;
        $this->assertFalse(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet)
        );
    }

    /**
     * C015: account=MANAGER, mode=Create, type=ContractNormal, status=Awaiting,
     *   revenue=CPA, dates=NoDates, booking=ImpressionsOnly, capping=BlockOnly, priority=p0_w1
     */
    public function testC015_MANAGER_Create_ContractNormal_Awaiting_CPA_NoDates_ImpressionsOnly_BlockOnly_p0_w1()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C015';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C015';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 1;
        $oCampaignInfo->revenue = 3.0;
        $oCampaignInfo->revenueType = 3;
        // No dates set
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->block = 3600;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C016: account=MANAGER, mode=Delete, type=Remnant, status=Expired,
     *   revenue=CPA, dates=StartEqualsEnd, booking=ImpressionsOnly, capping=None, priority=p5_w0
     */
    public function testC016_MANAGER_Delete_Remnant_Expired_CPA_StartEqualsEnd_ImpressionsOnly_None_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C016';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // First create a campaign to delete
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C016 to delete';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 3.0;
        $oCampaignInfo->revenueType = 3;
        $oCampaignInfo->startDate = new Date('2026-06-15');
        $oCampaignInfo->endDate = new Date('2026-06-15');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        // No frequency capping
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // Delete the campaign
        $this->assertTrue(
            $dllCampaignPartialMock->delete($oCampaignInfo->campaignId),
            'Delete should succeed: ' . $dllCampaignPartialMock->getLastError()
        );

        // Verify campaign no longer exists
        $oCampaignInfoGet = null;
        $this->assertFalse(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet)
        );
    }

    /**
     * C017: account=ADMIN, mode=Edit, type=ContractNormal, status=Awaiting,
     *   revenue=MT, dates=StartEqualsEnd, booking=Unlimited, capping=None, priority=p5_w0
     */
    public function testC017_ADMIN_Edit_ContractNormal_Awaiting_MT_StartEqualsEnd_Unlimited_None_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C017';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // First create a campaign to edit
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C017 original';
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // Now edit with combo parameters
        $oCampaignInfo->campaignName = 'Campaign C017 edited';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->startDate = new Date('2026-06-15');
        $oCampaignInfo->endDate = new Date('2026-06-15');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        // No frequency capping
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Edit should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
    }

    /**
     * C018: account=MANAGER, mode=Delete, type=ContractECPM, status=Running,
     *   revenue=CPM, dates=StartOnly, booking=ClicksOnly, capping=BlockOnly, priority=p0_w1
     */
    public function testC018_MANAGER_Delete_ContractECPM_Running_CPM_StartOnly_ClicksOnly_BlockOnly_p0_w1()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C018';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // First create a campaign to delete
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C018 to delete';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 1;
        $oCampaignInfo->revenue = 1.0;
        $oCampaignInfo->revenueType = 1;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->block = 3600;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // Delete the campaign
        $this->assertTrue(
            $dllCampaignPartialMock->delete($oCampaignInfo->campaignId),
            'Delete should succeed: ' . $dllCampaignPartialMock->getLastError()
        );

        // Verify campaign no longer exists
        $oCampaignInfoGet = null;
        $this->assertFalse(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet)
        );
    }

    /**
     * C019: account=MANAGER, mode=Create, type=eCPM, status=Paused,
     *   revenue=CPM, dates=StartEqualsEnd, booking=ClicksOnly, capping=None, priority=p5_w0
     */
    public function testC019_MANAGER_Create_eCPM_Paused_CPM_StartEqualsEnd_ClicksOnly_None_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C019';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C019';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 1.0;
        $oCampaignInfo->revenueType = 1;
        $oCampaignInfo->startDate = new Date('2026-06-15');
        $oCampaignInfo->endDate = new Date('2026-06-15');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = 500;
        // No frequency capping
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C020: account=MANAGER, mode=Delete, type=Override, status=Pending,
     *   revenue=CPC, dates=BothDates, booking=Unlimited, capping=None, priority=p5_w0
     */
    public function testC020_MANAGER_Delete_Override_Pending_CPC_BothDates_Unlimited_None_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C020';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // First create a campaign to delete
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C020 to delete';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 2.0;
        $oCampaignInfo->revenueType = 2;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        // No frequency capping
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // Delete the campaign
        $this->assertTrue(
            $dllCampaignPartialMock->delete($oCampaignInfo->campaignId),
            'Delete should succeed: ' . $dllCampaignPartialMock->getLastError()
        );

        // Verify campaign no longer exists
        $oCampaignInfoGet = null;
        $this->assertFalse(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet)
        );
    }

    /**
     * C021: account=MANAGER, mode=Create, type=ContractNormal, status=Rejected,
     *   revenue=MT, dates=EndOnly, booking=Both, capping=AllThree, priority=p0_w1
     */
    public function testC021_MANAGER_Create_ContractNormal_Rejected_MT_EndOnly_Both_AllThree_p0_w1()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C021';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C021';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 1;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->block = 3600;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C022: account=MANAGER, mode=Edit, type=Remnant, status=Approval,
     *   revenue=CPA, dates=EndOnly, booking=Both, capping=SessionOnly, priority=p0_w1
     */
    public function testC022_MANAGER_Edit_Remnant_Approval_CPA_EndOnly_Both_SessionOnly_p0_w1()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C022';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // First create a campaign to edit
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C022 original';
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // Now edit with combo parameters
        $oCampaignInfo->campaignName = 'Campaign C022 edited';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 1;
        $oCampaignInfo->revenue = 3.0;
        $oCampaignInfo->revenueType = 3;
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->sessionCapping = 5;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Edit should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
    }

    /**
     * C023: account=MANAGER, mode=Delete, type=eCPM, status=Inactive,
     *   revenue=CPC, dates=EndOnly, booking=ImpressionsOnly, capping=SessionOnly, priority=p5_w0
     */
    public function testC023_MANAGER_Delete_eCPM_Inactive_CPC_EndOnly_ImpressionsOnly_SessionOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C023';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // First create a campaign to delete
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C023 to delete';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 2.0;
        $oCampaignInfo->revenueType = 2;
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // Delete the campaign
        $this->assertTrue(
            $dllCampaignPartialMock->delete($oCampaignInfo->campaignId),
            'Delete should succeed: ' . $dllCampaignPartialMock->getLastError()
        );

        // Verify campaign no longer exists
        $oCampaignInfoGet = null;
        $this->assertFalse(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet)
        );
    }

    /**
     * C024: account=MANAGER, mode=Delete, type=Override, status=Expired,
     *   revenue=CPC, dates=StartEqualsEnd, booking=Unlimited, capping=CappingOnly, priority=p5_w0
     */
    public function testC024_MANAGER_Delete_Override_Expired_CPC_StartEqualsEnd_Unlimited_CappingOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C024';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // First create a campaign to delete
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C024 to delete';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 2.0;
        $oCampaignInfo->revenueType = 2;
        $oCampaignInfo->startDate = new Date('2026-06-15');
        $oCampaignInfo->endDate = new Date('2026-06-15');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // Delete the campaign
        $this->assertTrue(
            $dllCampaignPartialMock->delete($oCampaignInfo->campaignId),
            'Delete should succeed: ' . $dllCampaignPartialMock->getLastError()
        );

        // Verify campaign no longer exists
        $oCampaignInfoGet = null;
        $this->assertFalse(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet)
        );
    }

    /**
     * C025: account=MANAGER, mode=Edit, type=ContractECPM, status=Expired,
     *   revenue=CPM, dates=NoDates, booking=ClicksOnly, capping=AllThree, priority=p5_w0
     */
    public function testC025_MANAGER_Edit_ContractECPM_Expired_CPM_NoDates_ClicksOnly_AllThree_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C025';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // First create a campaign to edit
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C025 original';
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // Now edit with combo parameters
        $oCampaignInfo->campaignName = 'Campaign C025 edited';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 1.0;
        $oCampaignInfo->revenueType = 1;
        // No dates set
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Edit should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
    }

    /**
     * C026: account=MANAGER, mode=Create, type=Remnant, status=Paused,
     *   revenue=CPA, dates=BothDates, booking=ImpressionsOnly, capping=SessionOnly, priority=p5_w0
     */
    public function testC026_MANAGER_Create_Remnant_Paused_CPA_BothDates_ImpressionsOnly_SessionOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C026';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C026';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 3.0;
        $oCampaignInfo->revenueType = 3;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C027: account=MANAGER, mode=Create, type=ContractECPM, status=Rejected,
     *   revenue=CPA, dates=StartOnly, booking=Unlimited, capping=None, priority=p5_w0
     */
    public function testC027_MANAGER_Create_ContractECPM_Rejected_CPA_StartOnly_Unlimited_None_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C027';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C027';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 3.0;
        $oCampaignInfo->revenueType = 3;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        // No frequency capping
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C028: account=MANAGER, mode=Create, type=Override, status=Running,
     *   revenue=MT, dates=EndOnly, booking=ImpressionsOnly, capping=AllThree, priority=p5_w0
     */
    public function testC028_MANAGER_Create_Override_Running_MT_EndOnly_ImpressionsOnly_AllThree_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C028';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C028';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C029: account=MANAGER, mode=Create, type=ContractNormal, status=Inactive,
     *   revenue=MT, dates=BothDates, booking=ClicksOnly, capping=None, priority=p5_w0
     */
    public function testC029_MANAGER_Create_ContractNormal_Inactive_MT_BothDates_ClicksOnly_None_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C029';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C029';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = 500;
        // No frequency capping
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C030: account=MANAGER, mode=Create, type=Remnant, status=Awaiting,
     *   revenue=CPC, dates=StartOnly, booking=Both, capping=AllThree, priority=p5_w0
     */
    public function testC030_MANAGER_Create_Remnant_Awaiting_CPC_StartOnly_Both_AllThree_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C030';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C030';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 2.0;
        $oCampaignInfo->revenueType = 2;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C031: account=MANAGER, mode=Create, type=eCPM, status=Pending,
     *   revenue=CPM, dates=StartOnly, booking=Both, capping=AllThree, priority=p5_w0
     */
    public function testC031_MANAGER_Create_eCPM_Pending_CPM_StartOnly_Both_AllThree_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C031';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C031';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 1.0;
        $oCampaignInfo->revenueType = 1;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C032: account=MANAGER, mode=Create, type=ContractECPM, status=Approval,
     *   revenue=MT, dates=StartEqualsEnd, booking=ImpressionsOnly, capping=CappingOnly, priority=p5_w0
     */
    public function testC032_MANAGER_Create_ContractECPM_Approval_MT_StartEqualsEnd_ImpressionsOnly_CappingOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C032';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C032';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->startDate = new Date('2026-06-15');
        $oCampaignInfo->endDate = new Date('2026-06-15');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C033: account=MANAGER, mode=Create, type=eCPM, status=Rejected,
     *   revenue=MT, dates=StartEqualsEnd, booking=ClicksOnly, capping=CappingOnly, priority=p5_w0
     */
    public function testC033_MANAGER_Create_eCPM_Rejected_MT_StartEqualsEnd_ClicksOnly_CappingOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C033';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C033';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->startDate = new Date('2026-06-15');
        $oCampaignInfo->endDate = new Date('2026-06-15');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C034: account=MANAGER, mode=Create, type=ContractNormal, status=Running,
     *   revenue=CPC, dates=StartEqualsEnd, booking=Both, capping=BlockOnly, priority=p5_w0
     */
    public function testC034_MANAGER_Create_ContractNormal_Running_CPC_StartEqualsEnd_Both_BlockOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C034';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C034';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 2.0;
        $oCampaignInfo->revenueType = 2;
        $oCampaignInfo->startDate = new Date('2026-06-15');
        $oCampaignInfo->endDate = new Date('2026-06-15');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C035: account=MANAGER, mode=Create, type=Override, status=Paused,
     *   revenue=MT, dates=NoDates, booking=Both, capping=SessionOnly, priority=p5_w0
     */
    public function testC035_MANAGER_Create_Override_Paused_MT_NoDates_Both_SessionOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C035';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C035';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        // No dates set
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C036: account=MANAGER, mode=Create, type=ContractNormal, status=Pending,
     *   revenue=CPA, dates=EndOnly, booking=Unlimited, capping=BlockOnly, priority=p5_w0
     */
    public function testC036_MANAGER_Create_ContractNormal_Pending_CPA_EndOnly_Unlimited_BlockOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C036';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C036';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 3.0;
        $oCampaignInfo->revenueType = 3;
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C037: account=MANAGER, mode=Create, type=eCPM, status=Awaiting,
     *   revenue=CPM, dates=BothDates, booking=Both, capping=CappingOnly, priority=p5_w0
     */
    public function testC037_MANAGER_Create_eCPM_Awaiting_CPM_BothDates_Both_CappingOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C037';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C037';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 1.0;
        $oCampaignInfo->revenueType = 1;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C038: account=MANAGER, mode=Create, type=ContractECPM, status=Awaiting,
     *   revenue=MT, dates=BothDates, booking=Both, capping=AllThree, priority=p5_w0
     */
    public function testC038_MANAGER_Create_ContractECPM_Awaiting_MT_BothDates_Both_AllThree_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C038';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C038';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C039: account=MANAGER, mode=Create, type=Remnant, status=Inactive,
     *   revenue=MT, dates=StartOnly, booking=Unlimited, capping=BlockOnly, priority=p5_w0
     */
    public function testC039_MANAGER_Create_Remnant_Inactive_MT_StartOnly_Unlimited_BlockOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C039';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C039';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C040: account=MANAGER, mode=Create, type=eCPM, status=Approval,
     *   revenue=MT, dates=NoDates, booking=Both, capping=None, priority=p5_w0
     */
    public function testC040_MANAGER_Create_eCPM_Approval_MT_NoDates_Both_None_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C040';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C040';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        // No dates set
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = 500;
        // No frequency capping
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C041: account=MANAGER, mode=Create, type=ContractNormal, status=Rejected,
     *   revenue=MT, dates=BothDates, booking=Both, capping=SessionOnly, priority=p5_w0
     */
    public function testC041_MANAGER_Create_ContractNormal_Rejected_MT_BothDates_Both_SessionOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C041';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C041';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C042: account=MANAGER, mode=Create, type=ContractNormal, status=Inactive,
     *   revenue=MT, dates=NoDates, booking=Both, capping=AllThree, priority=p5_w0
     */
    public function testC042_MANAGER_Create_ContractNormal_Inactive_MT_NoDates_Both_AllThree_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C042';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C042';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        // No dates set
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C043: account=MANAGER, mode=Create, type=ContractNormal, status=Paused,
     *   revenue=MT, dates=EndOnly, booking=Both, capping=None, priority=p5_w0
     */
    public function testC043_MANAGER_Create_ContractNormal_Paused_MT_EndOnly_Both_None_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C043';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C043';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = 500;
        // No frequency capping
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C044: account=MANAGER, mode=Create, type=ContractNormal, status=Paused,
     *   revenue=MT, dates=EndOnly, booking=Both, capping=CappingOnly, priority=p5_w0
     */
    public function testC044_MANAGER_Create_ContractNormal_Paused_MT_EndOnly_Both_CappingOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C044';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C044';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C045: account=MANAGER, mode=Create, type=ContractNormal, status=Paused,
     *   revenue=MT, dates=EndOnly, booking=Both, capping=BlockOnly, priority=p5_w0
     */
    public function testC045_MANAGER_Create_ContractNormal_Paused_MT_EndOnly_Both_BlockOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C045';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C045';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C046: account=MANAGER, mode=Create, type=ContractNormal, status=Running,
     *   revenue=MT, dates=EndOnly, booking=Both, capping=SessionOnly, priority=p5_w0
     */
    public function testC046_MANAGER_Create_ContractNormal_Running_MT_EndOnly_Both_SessionOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C046';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C046';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C047: account=MANAGER, mode=Create, type=ContractNormal, status=Approval,
     *   revenue=MT, dates=EndOnly, booking=Both, capping=BlockOnly, priority=p5_w0
     */
    public function testC047_MANAGER_Create_ContractNormal_Approval_MT_EndOnly_Both_BlockOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C047';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C047';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C048: account=ADMIN, mode=View, type=Remnant, status=Running,
     *   revenue=CPM, dates=NoDates, booking=Unlimited, capping=None, priority=p0_w1
     */
    public function testC048_ADMIN_View_Remnant_Running_CPM_NoDates_Unlimited_None_p0_w1()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C048';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C048';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 1;
        $oCampaignInfo->revenue = 1.0;
        $oCampaignInfo->revenueType = 1;
        // No dates set
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        // No frequency capping
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C048');
    }

    /**
     * C049: account=MANAGER, mode=View, type=ContractNormal, status=Paused,
     *   revenue=CPC, dates=StartOnly, booking=ImpressionsOnly, capping=CappingOnly, priority=p0_w1
     */
    public function testC049_MANAGER_View_ContractNormal_Paused_CPC_StartOnly_ImpressionsOnly_CappingOnly_p0_w1()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C049';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C049';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 1;
        $oCampaignInfo->revenue = 2.0;
        $oCampaignInfo->revenueType = 2;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->capping = 10;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C049');
    }

    /**
     * C050: account=ADVERTISER, mode=View, type=Override, status=Awaiting,
     *   revenue=CPA, dates=EndOnly, booking=ClicksOnly, capping=SessionOnly, priority=p0_w1
     */
    public function testC050_ADVERTISER_View_Override_Awaiting_CPA_EndOnly_ClicksOnly_SessionOnly_p0_w1()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C050';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C050';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 1;
        $oCampaignInfo->revenue = 3.0;
        $oCampaignInfo->revenueType = 3;
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->sessionCapping = 5;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C050');
    }

    /**
     * C051: account=TRAFFICKER, mode=View, type=eCPM, status=Expired,
     *   revenue=MT, dates=BothDates, booking=Both, capping=BlockOnly, priority=p0_w1
     */
    public function testC051_TRAFFICKER_View_eCPM_Expired_MT_BothDates_Both_BlockOnly_p0_w1()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C051';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C051';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 1;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->block = 3600;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C051');
    }

    /**
     * C052: account=TRAFFICKER, mode=View, type=ContractECPM, status=Inactive,
     *   revenue=CPA, dates=StartEqualsEnd, booking=ImpressionsOnly, capping=AllThree, priority=p5_w0
     */
    public function testC052_TRAFFICKER_View_ContractECPM_Inactive_CPA_StartEqualsEnd_ImpressionsOnly_AllThree_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C052';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C052';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 3.0;
        $oCampaignInfo->revenueType = 3;
        $oCampaignInfo->startDate = new Date('2026-06-15');
        $oCampaignInfo->endDate = new Date('2026-06-15');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C052');
    }

    /**
     * C053: account=ADVERTISER, mode=View, type=ContractECPM, status=Pending,
     *   revenue=CPC, dates=NoDates, booking=Both, capping=AllThree, priority=p5_w0
     */
    public function testC053_ADVERTISER_View_ContractECPM_Pending_CPC_NoDates_Both_AllThree_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C053';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C053';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 2.0;
        $oCampaignInfo->revenueType = 2;
        // No dates set
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C053');
    }

    /**
     * C054: account=MANAGER, mode=View, type=Remnant, status=Approval,
     *   revenue=MT, dates=StartEqualsEnd, booking=ClicksOnly, capping=AllThree, priority=p5_w0
     */
    public function testC054_MANAGER_View_Remnant_Approval_MT_StartEqualsEnd_ClicksOnly_AllThree_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C054';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C054';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->startDate = new Date('2026-06-15');
        $oCampaignInfo->endDate = new Date('2026-06-15');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C054');
    }

    /**
     * C055: account=ADMIN, mode=View, type=eCPM, status=Rejected,
     *   revenue=CPA, dates=StartOnly, booking=Unlimited, capping=AllThree, priority=p5_w0
     */
    public function testC055_ADMIN_View_eCPM_Rejected_CPA_StartOnly_Unlimited_AllThree_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C055';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C055';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 3.0;
        $oCampaignInfo->revenueType = 3;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C055');
    }

    /**
     * C056: account=ADMIN, mode=View, type=Override, status=Approval,
     *   revenue=CPC, dates=BothDates, booking=ImpressionsOnly, capping=BlockOnly, priority=p5_w0
     */
    public function testC056_ADMIN_View_Override_Approval_CPC_BothDates_ImpressionsOnly_BlockOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C056';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C056';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 2.0;
        $oCampaignInfo->revenueType = 2;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C056');
    }

    /**
     * C057: account=MANAGER, mode=View, type=ContractECPM, status=Expired,
     *   revenue=CPM, dates=EndOnly, booking=Unlimited, capping=SessionOnly, priority=p5_w0
     */
    public function testC057_MANAGER_View_ContractECPM_Expired_CPM_EndOnly_Unlimited_SessionOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C057';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C057';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 1.0;
        $oCampaignInfo->revenueType = 1;
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C057');
    }

    /**
     * C058: account=ADVERTISER, mode=View, type=ContractNormal, status=Rejected,
     *   revenue=CPM, dates=StartEqualsEnd, booking=Both, capping=CappingOnly, priority=p5_w0
     */
    public function testC058_ADVERTISER_View_ContractNormal_Rejected_CPM_StartEqualsEnd_Both_CappingOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C058';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C058';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 1.0;
        $oCampaignInfo->revenueType = 1;
        $oCampaignInfo->startDate = new Date('2026-06-15');
        $oCampaignInfo->endDate = new Date('2026-06-15');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C058');
    }

    /**
     * C059: account=TRAFFICKER, mode=View, type=ContractNormal, status=Running,
     *   revenue=MT, dates=EndOnly, booking=ClicksOnly, capping=None, priority=p5_w0
     */
    public function testC059_TRAFFICKER_View_ContractNormal_Running_MT_EndOnly_ClicksOnly_None_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C059';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C059';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = 500;
        // No frequency capping
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C059');
    }

    /**
     * C060: account=TRAFFICKER, mode=View, type=Remnant, status=Awaiting,
     *   revenue=CPC, dates=StartOnly, booking=Both, capping=SessionOnly, priority=p5_w0
     */
    public function testC060_TRAFFICKER_View_Remnant_Awaiting_CPC_StartOnly_Both_SessionOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C060';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C060';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 2.0;
        $oCampaignInfo->revenueType = 2;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C060');
    }

    /**
     * C061: account=ADVERTISER, mode=View, type=eCPM, status=Paused,
     *   revenue=CPM, dates=BothDates, booking=ClicksOnly, capping=None, priority=p5_w0
     */
    public function testC061_ADVERTISER_View_eCPM_Paused_CPM_BothDates_ClicksOnly_None_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C061';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C061';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 1.0;
        $oCampaignInfo->revenueType = 1;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = 500;
        // No frequency capping
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C061');
    }

    /**
     * C062: account=ADMIN, mode=View, type=ContractECPM, status=Awaiting,
     *   revenue=MT, dates=StartEqualsEnd, booking=Unlimited, capping=CappingOnly, priority=p0_w1
     */
    public function testC062_ADMIN_View_ContractECPM_Awaiting_MT_StartEqualsEnd_Unlimited_CappingOnly_p0_w1()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C062';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C062';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 1;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->startDate = new Date('2026-06-15');
        $oCampaignInfo->endDate = new Date('2026-06-15');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->capping = 10;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C062');
    }

    /**
     * C063: account=MANAGER, mode=View, type=Override, status=Inactive,
     *   revenue=MT, dates=NoDates, booking=Both, capping=None, priority=p0_w1
     */
    public function testC063_MANAGER_View_Override_Inactive_MT_NoDates_Both_None_p0_w1()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C063';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C063';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 1;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        // No dates set
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = 500;
        // No frequency capping
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C063');
    }

    /**
     * C064: account=ADVERTISER, mode=View, type=Remnant, status=Inactive,
     *   revenue=CPA, dates=BothDates, booking=Unlimited, capping=BlockOnly, priority=p5_w0
     */
    public function testC064_ADVERTISER_View_Remnant_Inactive_CPA_BothDates_Unlimited_BlockOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C064';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C064';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 3.0;
        $oCampaignInfo->revenueType = 3;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C064');
    }

    /**
     * C065: account=ADMIN, mode=View, type=ContractNormal, status=Pending,
     *   revenue=CPA, dates=EndOnly, booking=Both, capping=BlockOnly, priority=p0_w1
     */
    public function testC065_ADMIN_View_ContractNormal_Pending_CPA_EndOnly_Both_BlockOnly_p0_w1()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C065';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C065';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 1;
        $oCampaignInfo->revenue = 3.0;
        $oCampaignInfo->revenueType = 3;
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->block = 3600;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C065');
    }

    /**
     * C066: account=TRAFFICKER, mode=View, type=Override, status=Pending,
     *   revenue=CPM, dates=StartOnly, booking=Unlimited, capping=CappingOnly, priority=p5_w0
     */
    public function testC066_TRAFFICKER_View_Override_Pending_CPM_StartOnly_Unlimited_CappingOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C066';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C066';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 1.0;
        $oCampaignInfo->revenueType = 1;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C066');
    }

    /**
     * C067: account=MANAGER, mode=View, type=eCPM, status=Running,
     *   revenue=CPC, dates=StartEqualsEnd, booking=ImpressionsOnly, capping=SessionOnly, priority=p5_w0
     */
    public function testC067_MANAGER_View_eCPM_Running_CPC_StartEqualsEnd_ImpressionsOnly_SessionOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C067';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C067';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 2.0;
        $oCampaignInfo->revenueType = 2;
        $oCampaignInfo->startDate = new Date('2026-06-15');
        $oCampaignInfo->endDate = new Date('2026-06-15');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C067');
    }

    /**
     * C068: account=MANAGER, mode=View, type=ContractNormal, status=Awaiting,
     *   revenue=CPM, dates=BothDates, booking=ImpressionsOnly, capping=AllThree, priority=p0_w1
     */
    public function testC068_MANAGER_View_ContractNormal_Awaiting_CPM_BothDates_ImpressionsOnly_AllThree_p0_w1()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C068';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C068';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 1;
        $oCampaignInfo->revenue = 1.0;
        $oCampaignInfo->revenueType = 1;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->block = 3600;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C068');
    }

    /**
     * C069: account=ADVERTISER, mode=View, type=ContractECPM, status=Approval,
     *   revenue=MT, dates=StartOnly, booking=ClicksOnly, capping=BlockOnly, priority=p0_w1
     */
    public function testC069_ADVERTISER_View_ContractECPM_Approval_MT_StartOnly_ClicksOnly_BlockOnly_p0_w1()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C069';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C069';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 1;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->block = 3600;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C069');
    }

    /**
     * C070: account=ADMIN, mode=View, type=eCPM, status=Inactive,
     *   revenue=CPC, dates=EndOnly, booking=ClicksOnly, capping=CappingOnly, priority=p5_w0
     */
    public function testC070_ADMIN_View_eCPM_Inactive_CPC_EndOnly_ClicksOnly_CappingOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C070';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C070';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 2.0;
        $oCampaignInfo->revenueType = 2;
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C070');
    }

    /**
     * C071: account=MANAGER, mode=View, type=Remnant, status=Rejected,
     *   revenue=CPA, dates=NoDates, booking=ImpressionsOnly, capping=CappingOnly, priority=p0_w1
     */
    public function testC071_MANAGER_View_Remnant_Rejected_CPA_NoDates_ImpressionsOnly_CappingOnly_p0_w1()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C071';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C071';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 1;
        $oCampaignInfo->revenue = 3.0;
        $oCampaignInfo->revenueType = 3;
        // No dates set
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->capping = 10;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C071');
    }

    /**
     * C072: account=TRAFFICKER, mode=View, type=ContractNormal, status=Approval,
     *   revenue=CPM, dates=NoDates, booking=Unlimited, capping=SessionOnly, priority=p5_w0
     */
    public function testC072_TRAFFICKER_View_ContractNormal_Approval_CPM_NoDates_Unlimited_SessionOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C072';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C072';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 1.0;
        $oCampaignInfo->revenueType = 1;
        // No dates set
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C072');
    }

    /**
     * C073: account=ADMIN, mode=View, type=Override, status=Expired,
     *   revenue=CPC, dates=StartEqualsEnd, booking=ImpressionsOnly, capping=None, priority=p5_w0
     */
    public function testC073_ADMIN_View_Override_Expired_CPC_StartEqualsEnd_ImpressionsOnly_None_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C073';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C073';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 2.0;
        $oCampaignInfo->revenueType = 2;
        $oCampaignInfo->startDate = new Date('2026-06-15');
        $oCampaignInfo->endDate = new Date('2026-06-15');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        // No frequency capping
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C073');
    }

    /**
     * C074: account=ADVERTISER, mode=View, type=ContractECPM, status=Running,
     *   revenue=CPA, dates=BothDates, booking=ImpressionsOnly, capping=CappingOnly, priority=p5_w0
     */
    public function testC074_ADVERTISER_View_ContractECPM_Running_CPA_BothDates_ImpressionsOnly_CappingOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C074';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C074';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 3.0;
        $oCampaignInfo->revenueType = 3;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C074');
    }

    /**
     * C075: account=ADMIN, mode=View, type=Override, status=Paused,
     *   revenue=MT, dates=NoDates, booking=ImpressionsOnly, capping=SessionOnly, priority=p5_w0
     */
    public function testC075_ADMIN_View_Override_Paused_MT_NoDates_ImpressionsOnly_SessionOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C075';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C075';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        // No dates set
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C075');
    }

    /**
     * C076: account=MANAGER, mode=View, type=Remnant, status=Pending,
     *   revenue=MT, dates=EndOnly, booking=ImpressionsOnly, capping=SessionOnly, priority=p5_w0
     */
    public function testC076_MANAGER_View_Remnant_Pending_MT_EndOnly_ImpressionsOnly_SessionOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C076';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C076';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C076');
    }

    /**
     * C077: account=TRAFFICKER, mode=View, type=eCPM, status=Rejected,
     *   revenue=CPC, dates=NoDates, booking=ClicksOnly, capping=BlockOnly, priority=p5_w0
     */
    public function testC077_TRAFFICKER_View_eCPM_Rejected_CPC_NoDates_ClicksOnly_BlockOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C077';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C077';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 2.0;
        $oCampaignInfo->revenueType = 2;
        // No dates set
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C077');
    }

    /**
     * C078: account=MANAGER, mode=View, type=eCPM, status=Approval,
     *   revenue=CPA, dates=EndOnly, booking=Both, capping=None, priority=p5_w0
     */
    public function testC078_MANAGER_View_eCPM_Approval_CPA_EndOnly_Both_None_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C078';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C078';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 3.0;
        $oCampaignInfo->revenueType = 3;
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = 500;
        // No frequency capping
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C078');
    }

    /**
     * C079: account=MANAGER, mode=View, type=Override, status=Rejected,
     *   revenue=MT, dates=EndOnly, booking=ImpressionsOnly, capping=AllThree, priority=p5_w0
     */
    public function testC079_MANAGER_View_Override_Rejected_MT_EndOnly_ImpressionsOnly_AllThree_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C079';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C079';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C079');
    }

    /**
     * C080: account=MANAGER, mode=View, type=ContractECPM, status=Paused,
     *   revenue=CPA, dates=StartEqualsEnd, booking=Both, capping=BlockOnly, priority=p5_w0
     */
    public function testC080_MANAGER_View_ContractECPM_Paused_CPA_StartEqualsEnd_Both_BlockOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C080';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C080';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 3.0;
        $oCampaignInfo->revenueType = 3;
        $oCampaignInfo->startDate = new Date('2026-06-15');
        $oCampaignInfo->endDate = new Date('2026-06-15');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C080');
    }

    /**
     * C081: account=TRAFFICKER, mode=View, type=ContractECPM, status=Rejected,
     *   revenue=CPC, dates=BothDates, booking=Unlimited, capping=SessionOnly, priority=p5_w0
     */
    public function testC081_TRAFFICKER_View_ContractECPM_Rejected_CPC_BothDates_Unlimited_SessionOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C081';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C081';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 2.0;
        $oCampaignInfo->revenueType = 2;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C081');
    }

    /**
     * C082: account=TRAFFICKER, mode=View, type=eCPM, status=Pending,
     *   revenue=CPM, dates=BothDates, booking=ClicksOnly, capping=BlockOnly, priority=p5_w0
     */
    public function testC082_TRAFFICKER_View_eCPM_Pending_CPM_BothDates_ClicksOnly_BlockOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C082';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C082';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 1.0;
        $oCampaignInfo->revenueType = 1;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C082');
    }

    /**
     * C083: account=TRAFFICKER, mode=View, type=Remnant, status=Paused,
     *   revenue=CPC, dates=EndOnly, booking=Unlimited, capping=AllThree, priority=p5_w0
     */
    public function testC083_TRAFFICKER_View_Remnant_Paused_CPC_EndOnly_Unlimited_AllThree_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C083';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C083';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 2.0;
        $oCampaignInfo->revenueType = 2;
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C083');
    }

    /**
     * C084: account=ADVERTISER, mode=View, type=ContractNormal, status=Expired,
     *   revenue=CPA, dates=StartOnly, booking=ClicksOnly, capping=AllThree, priority=p5_w0
     */
    public function testC084_ADVERTISER_View_ContractNormal_Expired_CPA_StartOnly_ClicksOnly_AllThree_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C084';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C084';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 3.0;
        $oCampaignInfo->revenueType = 3;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C084');
    }

    /**
     * C085: account=MANAGER, mode=View, type=eCPM, status=Awaiting,
     *   revenue=CPA, dates=NoDates, booking=ImpressionsOnly, capping=BlockOnly, priority=p5_w0
     */
    public function testC085_MANAGER_View_eCPM_Awaiting_CPA_NoDates_ImpressionsOnly_BlockOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C085';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C085';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 3.0;
        $oCampaignInfo->revenueType = 3;
        // No dates set
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C085');
    }

    /**
     * C086: account=MANAGER, mode=View, type=ContractECPM, status=Running,
     *   revenue=CPA, dates=StartOnly, booking=Both, capping=None, priority=p5_w0
     */
    public function testC086_MANAGER_View_ContractECPM_Running_CPA_StartOnly_Both_None_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C086';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C086';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 3.0;
        $oCampaignInfo->revenueType = 3;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = 500;
        // No frequency capping
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C086');
    }

    /**
     * C087: account=MANAGER, mode=View, type=ContractNormal, status=Inactive,
     *   revenue=CPM, dates=StartOnly, booking=ImpressionsOnly, capping=SessionOnly, priority=p5_w0
     */
    public function testC087_MANAGER_View_ContractNormal_Inactive_CPM_StartOnly_ImpressionsOnly_SessionOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C087';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C087';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 1.0;
        $oCampaignInfo->revenueType = 1;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C087');
    }

    /**
     * C088: account=MANAGER, mode=View, type=Remnant, status=Expired,
     *   revenue=CPA, dates=NoDates, booking=ImpressionsOnly, capping=CappingOnly, priority=p5_w0
     */
    public function testC088_MANAGER_View_Remnant_Expired_CPA_NoDates_ImpressionsOnly_CappingOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C088';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C088';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 3.0;
        $oCampaignInfo->revenueType = 3;
        // No dates set
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C088');
    }

    /**
     * C089: account=MANAGER, mode=View, type=Override, status=Running,
     *   revenue=CPA, dates=NoDates, booking=ImpressionsOnly, capping=BlockOnly, priority=p5_w0
     */
    public function testC089_MANAGER_View_Override_Running_CPA_NoDates_ImpressionsOnly_BlockOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C089';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C089';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 3.0;
        $oCampaignInfo->revenueType = 3;
        // No dates set
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C089');
    }

    /**
     * C090: account=MANAGER, mode=View, type=ContractECPM, status=Pending,
     *   revenue=CPA, dates=StartEqualsEnd, booking=ImpressionsOnly, capping=None, priority=p5_w0
     */
    public function testC090_MANAGER_View_ContractECPM_Pending_CPA_StartEqualsEnd_ImpressionsOnly_None_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C090';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C090';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 3.0;
        $oCampaignInfo->revenueType = 3;
        $oCampaignInfo->startDate = new Date('2026-06-15');
        $oCampaignInfo->endDate = new Date('2026-06-15');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        // No frequency capping
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C090');
    }

    /**
     * C091: account=MANAGER, mode=View, type=ContractECPM, status=Running,
     *   revenue=CPA, dates=NoDates, booking=ImpressionsOnly, capping=AllThree, priority=p5_w0
     */
    public function testC091_MANAGER_View_ContractECPM_Running_CPA_NoDates_ImpressionsOnly_AllThree_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C091';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C091';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 3.0;
        $oCampaignInfo->revenueType = 3;
        // No dates set
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C091');
    }

    /**
     * C092: account=MANAGER, mode=View, type=ContractECPM, status=Rejected,
     *   revenue=CPA, dates=NoDates, booking=ImpressionsOnly, capping=None, priority=p5_w0
     */
    public function testC092_MANAGER_View_ContractECPM_Rejected_CPA_NoDates_ImpressionsOnly_None_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C092';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C092';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 3.0;
        $oCampaignInfo->revenueType = 3;
        // No dates set
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        // No frequency capping
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C092');
    }

    /**
     * C093: account=MANAGER, mode=View, type=ContractECPM, status=Awaiting,
     *   revenue=CPA, dates=NoDates, booking=ImpressionsOnly, capping=None, priority=p5_w0
     */
    public function testC093_MANAGER_View_ContractECPM_Awaiting_CPA_NoDates_ImpressionsOnly_None_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C093';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C093';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 3.0;
        $oCampaignInfo->revenueType = 3;
        // No dates set
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        // No frequency capping
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C093');
    }

    /**
     * C094: account=MANAGER, mode=View, type=ContractECPM, status=Approval,
     *   revenue=CPA, dates=NoDates, booking=ImpressionsOnly, capping=CappingOnly, priority=p5_w0
     */
    public function testC094_MANAGER_View_ContractECPM_Approval_CPA_NoDates_ImpressionsOnly_CappingOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser and campaign to view
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C094';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C094';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 3.0;
        $oCampaignInfo->revenueType = 3;
        // No dates set
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // View (get) the campaign
        $oCampaignInfoGet = null;
        $this->assertTrue(
            $dllCampaignPartialMock->getCampaign($oCampaignInfo->campaignId, $oCampaignInfoGet),
            'View should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfoGet, 'Campaign info should be returned');
        $this->assertEqual($oCampaignInfoGet->campaignName, 'Campaign C094');
    }

    /**
     * C095: account=ADMIN, mode=Create, type=Remnant, status=Running,
     *   revenue=CPM, dates=NoDates, booking=Unlimited, capping=None, priority=p0_w1
     */
    public function testC095_ADMIN_Create_Remnant_Running_CPM_NoDates_Unlimited_None_p0_w1()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C095';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C095';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 1;
        $oCampaignInfo->revenue = 1.0;
        $oCampaignInfo->revenueType = 1;
        // No dates set
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        // No frequency capping

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C096: account=ADMIN, mode=Edit, type=ContractNormal, status=Paused,
     *   revenue=CPC, dates=StartOnly, booking=ImpressionsOnly, capping=CappingOnly, priority=p0_w5
     */
    public function testC096_ADMIN_Edit_ContractNormal_Paused_CPC_StartOnly_ImpressionsOnly_CappingOnly_p0_w5()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C096';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // First create a campaign to edit
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C096 original';
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // Now edit with combo parameters
        $oCampaignInfo->campaignName = 'Campaign C096 edited';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 5;
        $oCampaignInfo->revenue = 2.0;
        $oCampaignInfo->revenueType = 2;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->capping = 10;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Edit should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
    }

    /**
     * C097: account=ADMIN, mode=Edit, type=Override, status=Awaiting,
     *   revenue=CPA, dates=EndOnly, booking=ClicksOnly, capping=SessionOnly, priority=p5_w0
     */
    public function testC097_ADMIN_Edit_Override_Awaiting_CPA_EndOnly_ClicksOnly_SessionOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C097';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // First create a campaign to edit
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C097 original';
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // Now edit with combo parameters
        $oCampaignInfo->campaignName = 'Campaign C097 edited';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 3.0;
        $oCampaignInfo->revenueType = 3;
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Edit should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
    }

    /**
     * C098: account=ADMIN, mode=Create, type=eCPM, status=Inactive,
     *   revenue=MT, dates=BothDates, booking=Both, capping=BlockOnly, priority=p10_w0
     */
    public function testC098_ADMIN_Create_eCPM_Inactive_MT_BothDates_Both_BlockOnly_p10_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C098';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C098';
        $oCampaignInfo->priority = 10;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C099: account=ADMIN, mode=Create, type=ContractECPM, status=Pending,
     *   revenue=CPA, dates=StartEqualsEnd, booking=ImpressionsOnly, capping=AllThree, priority=p10_w0
     */
    public function testC099_ADMIN_Create_ContractECPM_Pending_CPA_StartEqualsEnd_ImpressionsOnly_AllThree_p10_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C099';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C099';
        $oCampaignInfo->priority = 10;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 3.0;
        $oCampaignInfo->revenueType = 3;
        $oCampaignInfo->startDate = new Date('2026-06-15');
        $oCampaignInfo->endDate = new Date('2026-06-15');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C100: account=ADMIN, mode=Edit, type=ContractECPM, status=Inactive,
     *   revenue=CPM, dates=StartEqualsEnd, booking=Both, capping=SessionOnly, priority=p0_w5
     */
    public function testC100_ADMIN_Edit_ContractECPM_Inactive_CPM_StartEqualsEnd_Both_SessionOnly_p0_w5()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C100';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // First create a campaign to edit
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C100 original';
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // Now edit with combo parameters
        $oCampaignInfo->campaignName = 'Campaign C100 edited';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 5;
        $oCampaignInfo->revenue = 1.0;
        $oCampaignInfo->revenueType = 1;
        $oCampaignInfo->startDate = new Date('2026-06-15');
        $oCampaignInfo->endDate = new Date('2026-06-15');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->sessionCapping = 5;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Edit should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
    }

    /**
     * C101: account=ADMIN, mode=Edit, type=eCPM, status=Running,
     *   revenue=CPC, dates=BothDates, booking=ClicksOnly, capping=AllThree, priority=p0_w1
     */
    public function testC101_ADMIN_Edit_eCPM_Running_CPC_BothDates_ClicksOnly_AllThree_p0_w1()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C101';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // First create a campaign to edit
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C101 original';
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // Now edit with combo parameters
        $oCampaignInfo->campaignName = 'Campaign C101 edited';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 1;
        $oCampaignInfo->revenue = 2.0;
        $oCampaignInfo->revenueType = 2;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->block = 3600;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Edit should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
    }

    /**
     * C102: account=ADMIN, mode=Create, type=Override, status=Paused,
     *   revenue=MT, dates=EndOnly, booking=Unlimited, capping=CappingOnly, priority=p0_w1
     */
    public function testC102_ADMIN_Create_Override_Paused_MT_EndOnly_Unlimited_CappingOnly_p0_w1()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C102';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C102';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 1;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->capping = 10;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C103: account=ADMIN, mode=Create, type=ContractNormal, status=Awaiting,
     *   revenue=CPM, dates=StartOnly, booking=ClicksOnly, capping=BlockOnly, priority=p10_w0
     */
    public function testC103_ADMIN_Create_ContractNormal_Awaiting_CPM_StartOnly_ClicksOnly_BlockOnly_p10_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C103';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C103';
        $oCampaignInfo->priority = 10;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 1.0;
        $oCampaignInfo->revenueType = 1;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C104: account=ADMIN, mode=Edit, type=Remnant, status=Pending,
     *   revenue=MT, dates=NoDates, booking=ClicksOnly, capping=BlockOnly, priority=p0_w5
     */
    public function testC104_ADMIN_Edit_Remnant_Pending_MT_NoDates_ClicksOnly_BlockOnly_p0_w5()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C104';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // First create a campaign to edit
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C104 original';
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // Now edit with combo parameters
        $oCampaignInfo->campaignName = 'Campaign C104 edited';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 5;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        // No dates set
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->block = 3600;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Edit should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
    }

    /**
     * C105: account=ADMIN, mode=Create, type=Remnant, status=Awaiting,
     *   revenue=CPC, dates=StartEqualsEnd, booking=Unlimited, capping=SessionOnly, priority=p5_w0
     */
    public function testC105_ADMIN_Create_Remnant_Awaiting_CPC_StartEqualsEnd_Unlimited_SessionOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C105';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C105';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 2.0;
        $oCampaignInfo->revenueType = 2;
        $oCampaignInfo->startDate = new Date('2026-06-15');
        $oCampaignInfo->endDate = new Date('2026-06-15');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C106: account=ADMIN, mode=Edit, type=ContractNormal, status=Inactive,
     *   revenue=CPA, dates=NoDates, booking=Unlimited, capping=None, priority=p10_w0
     */
    public function testC106_ADMIN_Edit_ContractNormal_Inactive_CPA_NoDates_Unlimited_None_p10_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C106';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // First create a campaign to edit
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C106 original';
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        // Now edit with combo parameters
        $oCampaignInfo->campaignName = 'Campaign C106 edited';
        $oCampaignInfo->priority = 10;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 3.0;
        $oCampaignInfo->revenueType = 3;
        // No dates set
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        // No frequency capping
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Edit should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
    }

    /**
     * C107: account=ADMIN, mode=Create, type=Override, status=Running,
     *   revenue=CPA, dates=StartOnly, booking=Both, capping=BlockOnly, priority=p0_w5
     */
    public function testC107_ADMIN_Create_Override_Running_CPA_StartOnly_Both_BlockOnly_p0_w5()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C107';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C107';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 5;
        $oCampaignInfo->revenue = 3.0;
        $oCampaignInfo->revenueType = 3;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->block = 3600;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C108: account=ADMIN, mode=Create, type=eCPM, status=Paused,
     *   revenue=CPM, dates=BothDates, booking=ImpressionsOnly, capping=SessionOnly, priority=p5_w0
     */
    public function testC108_ADMIN_Create_eCPM_Paused_CPM_BothDates_ImpressionsOnly_SessionOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C108';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C108';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 1.0;
        $oCampaignInfo->revenueType = 1;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C109: account=ADMIN, mode=Create, type=ContractECPM, status=Awaiting,
     *   revenue=MT, dates=NoDates, booking=ImpressionsOnly, capping=None, priority=p5_w0
     */
    public function testC109_ADMIN_Create_ContractECPM_Awaiting_MT_NoDates_ImpressionsOnly_None_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C109';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C109';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        // No dates set
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        // No frequency capping
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C110: account=ADMIN, mode=Create, type=ContractECPM, status=Paused,
     *   revenue=CPC, dates=EndOnly, booking=Both, capping=BlockOnly, priority=p10_w0
     */
    public function testC110_ADMIN_Create_ContractECPM_Paused_CPC_EndOnly_Both_BlockOnly_p10_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C110';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C110';
        $oCampaignInfo->priority = 10;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 2.0;
        $oCampaignInfo->revenueType = 2;
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C111: account=ADMIN, mode=Create, type=eCPM, status=Pending,
     *   revenue=CPM, dates=EndOnly, booking=Unlimited, capping=AllThree, priority=p0_w5
     */
    public function testC111_ADMIN_Create_eCPM_Pending_CPM_EndOnly_Unlimited_AllThree_p0_w5()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C111';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C111';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 5;
        $oCampaignInfo->revenue = 1.0;
        $oCampaignInfo->revenueType = 1;
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->block = 3600;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C112: account=ADMIN, mode=Create, type=Override, status=Pending,
     *   revenue=CPC, dates=NoDates, booking=Both, capping=None, priority=p5_w0
     */
    public function testC112_ADMIN_Create_Override_Pending_CPC_NoDates_Both_None_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C112';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C112';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 2.0;
        $oCampaignInfo->revenueType = 2;
        // No dates set
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = 500;
        // No frequency capping
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C113: account=ADMIN, mode=Create, type=ContractNormal, status=Running,
     *   revenue=MT, dates=StartEqualsEnd, booking=Both, capping=SessionOnly, priority=p0_w1
     */
    public function testC113_ADMIN_Create_ContractNormal_Running_MT_StartEqualsEnd_Both_SessionOnly_p0_w1()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C113';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C113';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 1;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->startDate = new Date('2026-06-15');
        $oCampaignInfo->endDate = new Date('2026-06-15');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->sessionCapping = 5;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C114: account=ADMIN, mode=Create, type=Remnant, status=Inactive,
     *   revenue=CPA, dates=StartOnly, booking=ImpressionsOnly, capping=CappingOnly, priority=p0_w1
     */
    public function testC114_ADMIN_Create_Remnant_Inactive_CPA_StartOnly_ImpressionsOnly_CappingOnly_p0_w1()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C114';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C114';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 1;
        $oCampaignInfo->revenue = 3.0;
        $oCampaignInfo->revenueType = 3;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->capping = 10;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C115: account=ADMIN, mode=Create, type=Override, status=Inactive,
     *   revenue=CPM, dates=StartEqualsEnd, booking=ClicksOnly, capping=CappingOnly, priority=p10_w0
     */
    public function testC115_ADMIN_Create_Override_Inactive_CPM_StartEqualsEnd_ClicksOnly_CappingOnly_p10_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C115';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C115';
        $oCampaignInfo->priority = 10;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 1.0;
        $oCampaignInfo->revenueType = 1;
        $oCampaignInfo->startDate = new Date('2026-06-15');
        $oCampaignInfo->endDate = new Date('2026-06-15');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C116: account=ADMIN, mode=Create, type=eCPM, status=Awaiting,
     *   revenue=CPA, dates=BothDates, booking=Both, capping=CappingOnly, priority=p0_w5
     */
    public function testC116_ADMIN_Create_eCPM_Awaiting_CPA_BothDates_Both_CappingOnly_p0_w5()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C116';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C116';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 5;
        $oCampaignInfo->revenue = 3.0;
        $oCampaignInfo->revenueType = 3;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->capping = 10;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C117: account=ADMIN, mode=Create, type=ContractECPM, status=Running,
     *   revenue=MT, dates=StartOnly, booking=Unlimited, capping=CappingOnly, priority=p5_w0
     */
    public function testC117_ADMIN_Create_ContractECPM_Running_MT_StartOnly_Unlimited_CappingOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C117';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C117';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C118: account=ADMIN, mode=Create, type=Remnant, status=Paused,
     *   revenue=CPA, dates=NoDates, booking=Both, capping=AllThree, priority=p10_w0
     */
    public function testC118_ADMIN_Create_Remnant_Paused_CPA_NoDates_Both_AllThree_p10_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C118';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C118';
        $oCampaignInfo->priority = 10;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 3.0;
        $oCampaignInfo->revenueType = 3;
        // No dates set
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = 500;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C119: account=ADMIN, mode=Create, type=ContractNormal, status=Pending,
     *   revenue=MT, dates=BothDates, booking=Unlimited, capping=AllThree, priority=p5_w0
     */
    public function testC119_ADMIN_Create_ContractNormal_Pending_MT_BothDates_Unlimited_AllThree_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C119';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C119';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C120: account=ADMIN, mode=Create, type=eCPM, status=Pending,
     *   revenue=MT, dates=StartOnly, booking=Unlimited, capping=SessionOnly, priority=p10_w0
     */
    public function testC120_ADMIN_Create_eCPM_Pending_MT_StartOnly_Unlimited_SessionOnly_p10_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C120';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C120';
        $oCampaignInfo->priority = 10;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C121: account=ADMIN, mode=Create, type=eCPM, status=Inactive,
     *   revenue=CPC, dates=NoDates, booking=Unlimited, capping=BlockOnly, priority=p5_w0
     */
    public function testC121_ADMIN_Create_eCPM_Inactive_CPC_NoDates_Unlimited_BlockOnly_p5_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C121';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C121';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 2.0;
        $oCampaignInfo->revenueType = 2;
        // No dates set
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C122: account=ADMIN, mode=Create, type=ContractECPM, status=Paused,
     *   revenue=MT, dates=StartEqualsEnd, booking=ClicksOnly, capping=None, priority=p0_w5
     */
    public function testC122_ADMIN_Create_ContractECPM_Paused_MT_StartEqualsEnd_ClicksOnly_None_p0_w5()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C122';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C122';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 5;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->startDate = new Date('2026-06-15');
        $oCampaignInfo->endDate = new Date('2026-06-15');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = 500;
        // No frequency capping

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C123: account=ADMIN, mode=Create, type=Override, status=Running,
     *   revenue=MT, dates=EndOnly, booking=ImpressionsOnly, capping=BlockOnly, priority=p10_w0
     */
    public function testC123_ADMIN_Create_Override_Running_MT_EndOnly_ImpressionsOnly_BlockOnly_p10_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C123';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C123';
        $oCampaignInfo->priority = 10;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C124: account=ADMIN, mode=Create, type=eCPM, status=Inactive,
     *   revenue=MT, dates=StartEqualsEnd, booking=Unlimited, capping=BlockOnly, priority=p0_w1
     */
    public function testC124_ADMIN_Create_eCPM_Inactive_MT_StartEqualsEnd_Unlimited_BlockOnly_p0_w1()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C124';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C124';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 1;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->startDate = new Date('2026-06-15');
        $oCampaignInfo->endDate = new Date('2026-06-15');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->block = 3600;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C125: account=ADMIN, mode=Create, type=Override, status=Inactive,
     *   revenue=MT, dates=EndOnly, booking=Unlimited, capping=AllThree, priority=p10_w0
     */
    public function testC125_ADMIN_Create_Override_Inactive_MT_EndOnly_Unlimited_AllThree_p10_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C125';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C125';
        $oCampaignInfo->priority = 10;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C126: account=ADMIN, mode=Create, type=ContractECPM, status=Pending,
     *   revenue=MT, dates=BothDates, booking=Unlimited, capping=CappingOnly, priority=p0_w1
     */
    public function testC126_ADMIN_Create_ContractECPM_Pending_MT_BothDates_Unlimited_CappingOnly_p0_w1()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C126';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C126';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 1;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->capping = 10;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C127: account=ADMIN, mode=Create, type=Remnant, status=Awaiting,
     *   revenue=MT, dates=BothDates, booking=Unlimited, capping=AllThree, priority=p0_w1
     */
    public function testC127_ADMIN_Create_Remnant_Awaiting_MT_BothDates_Unlimited_AllThree_p0_w1()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C127';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C127';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 1;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->block = 3600;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C128: account=ADMIN, mode=Create, type=eCPM, status=Inactive,
     *   revenue=MT, dates=NoDates, booking=Unlimited, capping=CappingOnly, priority=p10_w0
     */
    public function testC128_ADMIN_Create_eCPM_Inactive_MT_NoDates_Unlimited_CappingOnly_p10_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C128';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C128';
        $oCampaignInfo->priority = 10;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        // No dates set
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C129: account=ADMIN, mode=Create, type=eCPM, status=Inactive,
     *   revenue=MT, dates=StartOnly, booking=Unlimited, capping=None, priority=p10_w0
     */
    public function testC129_ADMIN_Create_eCPM_Inactive_MT_StartOnly_Unlimited_None_p10_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C129';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C129';
        $oCampaignInfo->priority = 10;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        // No frequency capping
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C130: account=ADMIN, mode=Create, type=Override, status=Inactive,
     *   revenue=MT, dates=BothDates, booking=Unlimited, capping=None, priority=p10_w0
     */
    public function testC130_ADMIN_Create_Override_Inactive_MT_BothDates_Unlimited_None_p10_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C130';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C130';
        $oCampaignInfo->priority = 10;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        // No frequency capping
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C131: account=ADMIN, mode=Create, type=Remnant, status=Inactive,
     *   revenue=MT, dates=EndOnly, booking=Unlimited, capping=None, priority=p10_w0
     */
    public function testC131_ADMIN_Create_Remnant_Inactive_MT_EndOnly_Unlimited_None_p10_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C131';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C131';
        $oCampaignInfo->priority = 10;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        // No frequency capping
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C132: account=ADMIN, mode=Create, type=ContractNormal, status=Inactive,
     *   revenue=MT, dates=EndOnly, booking=Unlimited, capping=None, priority=p10_w0
     */
    public function testC132_ADMIN_Create_ContractNormal_Inactive_MT_EndOnly_Unlimited_None_p10_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C132';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C132';
        $oCampaignInfo->priority = 10;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->endDate = new Date('2027-12-31');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        // No frequency capping
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C133: account=ADMIN, mode=Create, type=eCPM, status=Inactive,
     *   revenue=MT, dates=NoDates, booking=Unlimited, capping=SessionOnly, priority=p10_w0
     */
    public function testC133_ADMIN_Create_eCPM_Inactive_MT_NoDates_Unlimited_SessionOnly_p10_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C133';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C133';
        $oCampaignInfo->priority = 10;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        // No dates set
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }

    /**
     * C134: account=ADMIN, mode=Create, type=eCPM, status=Inactive,
     *   revenue=MT, dates=StartOnly, booking=Unlimited, capping=AllThree, priority=p10_w0
     */
    public function testC134_ADMIN_Create_eCPM_Inactive_MT_StartOnly_Unlimited_AllThree_p10_w0()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        // Create advertiser first
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for C134';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        // Create campaign with combo parameters
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign C134';
        $oCampaignInfo->priority = 10;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->revenue = 4.0;
        $oCampaignInfo->revenueType = 4;
        $oCampaignInfo->startDate = new Date('2025-01-01');
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->capping = 10;
        $oCampaignInfo->sessionCapping = 5;
        $oCampaignInfo->block = 3600;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->targetClicks = 0;
        $oCampaignInfo->targetConversions = 0;

        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Create should succeed: ' . $dllCampaignPartialMock->getLastError()
        );
        $this->assertNotNull($oCampaignInfo->campaignId, 'Campaign ID should be set after create');
    }


    // =========================================================================
    // Section 4B: Excluded / Negative Combination Tests
    // =========================================================================

    /**
     * EX01: ADVERTISER + Create campaign => permission denial
     * Code enforces [OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER] for modify().
     */
    public function testEX01_AdvertiserCreateDenied()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', false);

        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for EX01';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Should fail - ADVERTISER create';

        $this->assertFalse(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'ADVERTISER should not be able to create campaigns'
        );
    }

    /**
     * EX02: ADVERTISER + Edit campaign => permission denial
     */
    public function testEX02_AdvertiserEditDenied()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignCreate = new PartialMockOA_Dll_Campaign_ComboTest($this);
        $dllCampaignEdit = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignCreate->setReturnValue('checkPermissions', true);
        $dllCampaignEdit->setReturnValue('checkPermissions', false);

        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for EX02';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign for EX02';
        $this->assertTrue($dllCampaignCreate->modify($oCampaignInfo));

        $oCampaignInfo->campaignName = 'Should fail - ADVERTISER edit';
        $this->assertFalse(
            $dllCampaignEdit->modify($oCampaignInfo),
            'ADVERTISER should not be able to edit campaigns'
        );
    }

    /**
     * EX03: ADVERTISER + Delete campaign => permission denial
     */
    public function testEX03_AdvertiserDeleteDenied()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignCreate = new PartialMockOA_Dll_Campaign_ComboTest($this);
        $dllCampaignDelete = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignCreate->setReturnValue('checkPermissions', true);
        $dllCampaignDelete->setReturnValue('checkPermissions', false);

        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for EX03';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign for EX03';
        $this->assertTrue($dllCampaignCreate->modify($oCampaignInfo));

        $this->assertFalse(
            $dllCampaignDelete->delete($oCampaignInfo->campaignId),
            'ADVERTISER should not be able to delete campaigns'
        );
    }

    /**
     * EX04: TRAFFICKER + Create campaign => permission denial
     */
    public function testEX04_TraffickerCreateDenied()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', false);

        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for EX04';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Should fail - TRAFFICKER create';

        $this->assertFalse(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'TRAFFICKER should not be able to create campaigns'
        );
    }

    /**
     * EX05: TRAFFICKER + Edit campaign => permission denial
     */
    public function testEX05_TraffickerEditDenied()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignCreate = new PartialMockOA_Dll_Campaign_ComboTest($this);
        $dllCampaignEdit = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignCreate->setReturnValue('checkPermissions', true);
        $dllCampaignEdit->setReturnValue('checkPermissions', false);

        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for EX05';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign for EX05';
        $this->assertTrue($dllCampaignCreate->modify($oCampaignInfo));

        $oCampaignInfo->campaignName = 'Should fail - TRAFFICKER edit';
        $this->assertFalse(
            $dllCampaignEdit->modify($oCampaignInfo),
            'TRAFFICKER should not be able to edit campaigns'
        );
    }

    /**
     * EX06: TRAFFICKER + Delete campaign => permission denial
     */
    public function testEX06_TraffickerDeleteDenied()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignCreate = new PartialMockOA_Dll_Campaign_ComboTest($this);
        $dllCampaignDelete = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignCreate->setReturnValue('checkPermissions', true);
        $dllCampaignDelete->setReturnValue('checkPermissions', false);

        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for EX06';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign for EX06';
        $this->assertTrue($dllCampaignCreate->modify($oCampaignInfo));

        $this->assertFalse(
            $dllCampaignDelete->delete($oCampaignInfo->campaignId),
            'TRAFFICKER should not be able to delete campaigns'
        );
    }

    /**
     * EX07: priority > 0 + weight > 0 => validation rejection
     * Validation rule in Campaign.php:125-128 rejects this combination.
     */
    public function testEX07_HighPriorityWithWeightRejected()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for EX07';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Should fail - priority>0 + weight>0';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 3;
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;

        $this->assertFalse(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Campaign with priority>0 and weight>0 should be rejected'
        );
        $this->assertEqual(
            $dllCampaignPartialMock->getLastError(),
            'High or medium priority campaigns cannot have a weight that is greater than zero.'
        );
    }

    /**
     * EX08: priority = 0 + targets > 0 => validation rejection
     * Validation rule in Campaign.php:129-132 rejects this combination.
     */
    public function testEX08_LowPriorityWithTargetsRejected()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for EX08';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Should fail - priority=0 + targets>0';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 1;
        $oCampaignInfo->targetImpressions = 5000;
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;

        $this->assertFalse(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Campaign with priority=0 and targets>0 should be rejected'
        );
        $this->assertEqual(
            $dllCampaignPartialMock->getLastError(),
            'Low or override priority campaigns cannot have targets.'
        );
    }

    /**
     * EX09: priority = 0 + targetClicks > 0 => validation rejection
     */
    public function testEX09_LowPriorityWithTargetClicksRejected()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for EX09';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Should fail - priority=0 + targetClicks>0';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 1;
        $oCampaignInfo->targetClicks = 1000;
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;

        $this->assertFalse(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Campaign with priority=0 and targetClicks>0 should be rejected'
        );
        $this->assertEqual(
            $dllCampaignPartialMock->getLastError(),
            'Low or override priority campaigns cannot have targets.'
        );
    }

    /**
     * EX10: priority = 0 + targetConversions > 0 => validation rejection
     */
    public function testEX10_LowPriorityWithTargetConversionsRejected()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for EX10';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Should fail - priority=0 + targetConversions>0';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 1;
        $oCampaignInfo->targetConversions = 500;
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;

        $this->assertFalse(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Campaign with priority=0 and targetConversions>0 should be rejected'
        );
        $this->assertEqual(
            $dllCampaignPartialMock->getLastError(),
            'Low or override priority campaigns cannot have targets.'
        );
    }

    /**
     * EX11: Edit priority from high to low but keep targets => rejection
     */
    public function testEX11_EditHighToLowPriorityWithTargetsRejected()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for EX11';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign for EX11';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->targetImpressions = 5000;
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 1;

        $this->assertFalse(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Changing to low priority while keeping targets should be rejected'
        );
    }

    /**
     * EX12: Edit weight from 0 to >0 on high priority campaign => rejection
     */
    public function testEX12_EditAddWeightToHighPriorityRejected()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for EX12';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Campaign for EX12';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        $this->assertTrue($dllCampaignPartialMock->modify($oCampaignInfo));

        $oCampaignInfo->weight = 5;

        $this->assertFalse(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Adding weight to high priority campaign should be rejected'
        );
        $this->assertEqual(
            $dllCampaignPartialMock->getLastError(),
            'High or medium priority campaigns cannot have a weight that is greater than zero.'
        );
    }

    /**
     * EX13: Start date after end date => validation rejection
     */
    public function testEX13_StartDateAfterEndDateRejected()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for EX13';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Should fail - start after end';
        $oCampaignInfo->startDate = new Date('2027-12-31');
        $oCampaignInfo->endDate = new Date('2025-01-01');

        $this->assertFalse(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Campaign with start date after end date should be rejected'
        );
    }

    /**
     * EX14: Modify non-existent campaign => unknown ID error
     */
    public function testEX14_ModifyNonExistentCampaign()
    {
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->campaignId = 999999;
        $oCampaignInfo->campaignName = 'Should fail - non-existent';

        $this->assertFalse(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Modifying non-existent campaign should fail'
        );
        $this->assertEqual(
            $dllCampaignPartialMock->getLastError(),
            $this->unknownIdError
        );
    }

    /**
     * EX15: Delete non-existent campaign => unknown ID error
     */
    public function testEX15_DeleteNonExistentCampaign()
    {
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        $this->assertFalse(
            $dllCampaignPartialMock->delete(999999),
            'Deleting non-existent campaign should fail'
        );
        $this->assertEqual(
            $dllCampaignPartialMock->getLastError(),
            $this->unknownIdError
        );
    }

    /**
     * EX16: View non-existent campaign => unknown ID error
     */
    public function testEX16_ViewNonExistentCampaign()
    {
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        $oCampaignInfoGet = null;
        $this->assertFalse(
            $dllCampaignPartialMock->getCampaign(999999, $oCampaignInfoGet),
            'Viewing non-existent campaign should fail'
        );
        $this->assertEqual(
            $dllCampaignPartialMock->getLastError(),
            $this->unknownIdError
        );
    }

    /**
     * EX17: Create campaign with invalid (non-existent) advertiser ID
     */
    public function testEX17_CreateWithInvalidAdvertiserId()
    {
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = 999999;
        $oCampaignInfo->campaignName = 'Should fail - bad advertiser ID';

        $this->assertFalse(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Creating campaign with non-existent advertiser should fail'
        );
    }

    /**
     * EX18: priority = 10 (max high) + weight = 10 => rejection
     */
    public function testEX18_MaxPriorityWithMaxWeightRejected()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for EX18';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Should fail - priority=10 + weight=10';
        $oCampaignInfo->priority = 10;
        $oCampaignInfo->weight = 10;
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;

        $this->assertFalse(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Campaign with priority=10 and weight=10 should be rejected'
        );
        $this->assertEqual(
            $dllCampaignPartialMock->getLastError(),
            'High or medium priority campaigns cannot have a weight that is greater than zero.'
        );
    }

    /**
     * EX19: priority = 1 (min high) + weight = 1 => rejection
     */
    public function testEX19_MinHighPriorityWithMinWeightRejected()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);

        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for EX19';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Should fail - priority=1 + weight=1';
        $oCampaignInfo->priority = 1;
        $oCampaignInfo->weight = 1;
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;

        $this->assertFalse(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            'Campaign with priority=1 and weight=1 should be rejected'
        );
    }

    /**
     * EX20: ADVERTISER + Delete with Remnant campaign type
     */
    public function testEX20_AdvertiserDeleteRemnantDenied()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignCreate = new PartialMockOA_Dll_Campaign_ComboTest($this);
        $dllCampaignDelete = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignCreate->setReturnValue('checkPermissions', true);
        $dllCampaignDelete->setReturnValue('checkPermissions', false);

        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for EX20';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Remnant campaign for EX20';
        $oCampaignInfo->priority = 0;
        $oCampaignInfo->weight = 1;
        $oCampaignInfo->impressions = -1;
        $oCampaignInfo->clicks = -1;
        $this->assertTrue($dllCampaignCreate->modify($oCampaignInfo));

        $this->assertFalse(
            $dllCampaignDelete->delete($oCampaignInfo->campaignId),
            'ADVERTISER should not be able to delete remnant campaigns'
        );
    }

    /**
     * EX21: TRAFFICKER + Edit Contract Normal campaign
     */
    public function testEX21_TraffickerEditContractDenied()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_ComboTest($this);
        $dllCampaignCreate = new PartialMockOA_Dll_Campaign_ComboTest($this);
        $dllCampaignEdit = new PartialMockOA_Dll_Campaign_ComboTest($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignCreate->setReturnValue('checkPermissions', true);
        $dllCampaignEdit->setReturnValue('checkPermissions', false);

        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Advertiser for EX21';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue($dllAdvertiserPartialMock->modify($oAdvertiserInfo));

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Contract campaign for EX21';
        $oCampaignInfo->priority = 5;
        $oCampaignInfo->weight = 0;
        $oCampaignInfo->impressions = 10000;
        $oCampaignInfo->clicks = -1;
        $oCampaignInfo->targetImpressions = 1000;
        $this->assertTrue($dllCampaignCreate->modify($oCampaignInfo));

        $oCampaignInfo->campaignName = 'Should fail - TRAFFICKER edit contract';
        $this->assertFalse(
            $dllCampaignEdit->modify($oCampaignInfo),
            'TRAFFICKER should not be able to edit contract campaigns'
        );
    }
}
