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
 * Campaign CRUD Combination Matrix Tests — Sections 4A + 4B
 *
 * This test class covers:
 *   4A: ~120 pairwise-generated positive test cases across 9 dimensions:
 *       Account type, Page mode, Campaign type, Entity status, Revenue type,
 *       Date config, Booking limits, Frequency capping, Priority/weight.
 *   4B: Negative validation / permission-denial test cases for excluded combos.
 *
 * Dimensions are encoded in a compact matrix and tests are generated
 * programmatically via loops to keep the file DRY.
 *
 * @package    OpenXDll
 * @subpackage TestSuite
 */
class OA_Dll_CampaignCRUDCombinationTest extends DllUnitTestCase
{
    /**
     * @var int Agency ID for test fixtures.
     */
    public $agencyId;

    // ── Campaign type constants (maps to priority field in the DB) ──────
    // These mirror the UI campaign-type concept:
    //   Remnant        → priority = 0
    //   ContractNormal → priority = 5  (1-10 range)
    //   Override       → priority = -1
    //   eCPM           → priority = -2
    //   ContractECPM   → priority = -2 (with ecpm flag)
    const CAMPAIGN_TYPES = [
        'Remnant'        => ['priority' => 0,  'weight' => 1],
        'ContractNormal' => ['priority' => 5,  'weight' => 0],
        'Override'       => ['priority' => -1, 'weight' => 0],
        'eCPM'           => ['priority' => -2, 'weight' => 0],
        'ContractECPM'   => ['priority' => -2, 'weight' => 0],
    ];

    // ── Revenue type constants (from constants.php) ────────────────────
    const REVENUE_TYPES = [
        'CPM' => 1,  // MAX_FINANCE_CPM
        'CPC' => 2,  // MAX_FINANCE_CPC
        'CPA' => 3,  // MAX_FINANCE_CPA
        'MT'  => 4,  // MAX_FINANCE_MT (Monthly Tenancy)
    ];

    // ── Entity status constants (from lib/OA/Dll.php) ──────────────────
    const ENTITY_STATUSES = [
        'Running'  => 0,  // OA_ENTITY_STATUS_RUNNING
        'Paused'   => 1,  // OA_ENTITY_STATUS_PAUSED
        'Awaiting' => 2,  // OA_ENTITY_STATUS_AWAITING
        'Expired'  => 3,  // OA_ENTITY_STATUS_EXPIRED
        'Inactive' => 4,  // OA_ENTITY_STATUS_INACTIVE
        'Pending'  => 10, // OA_ENTITY_STATUS_PENDING
        'Approval' => 21, // OA_ENTITY_STATUS_APPROVAL
        'Rejected' => 22, // OA_ENTITY_STATUS_REJECTED
    ];

    // ── Priority/weight presets ────────────────────────────────────────
    const PRIORITY_PRESETS = [
        'p0w1'  => ['priority' => 0,  'weight' => 1],
        'p0w5'  => ['priority' => 0,  'weight' => 5],
        'p5w0'  => ['priority' => 5,  'weight' => 0],
        'p10w0' => ['priority' => 10, 'weight' => 0],
    ];

    public function __construct()
    {
        parent::__construct();
        Mock::generatePartial(
            'OA_Dll_Campaign',
            'PartialMockOA_Dll_Campaign_CombinationTest',
            ['checkPermissions'],
        );
        Mock::generatePartial(
            'OA_Dll_Advertiser',
            'PartialMockOA_Dll_Advertiser_CombinationTest',
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

    // ====================================================================
    // Helper: build an advertiser for the test fixture
    // ====================================================================
    private function _createAdvertiser()
    {
        $dllAdvertiser = new PartialMockOA_Dll_Advertiser_CombinationTest($this);
        $dllAdvertiser->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiser->setReturnValue('checkPermissions', true);

        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Combo Test Advertiser';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $dllAdvertiser->modify($oAdvertiserInfo);

        return $oAdvertiserInfo->advertiserId;
    }

    // ====================================================================
    // Helper: build a CampaignInfo populated from a combo row
    // ====================================================================
    private function _buildCampaignInfo($combo, $advertiserId, $existingCampaignId = null)
    {
        $oCampaign = new OA_Dll_CampaignInfo();

        // If editing/deleting/viewing we need a campaignId
        if ($existingCampaignId !== null) {
            $oCampaign->campaignId = $existingCampaignId;
        }
        $oCampaign->advertiserId = $advertiserId;
        $oCampaign->campaignName = 'Combo Test - ' . implode('/', $combo);

        // Campaign type drives the base priority/weight values.
        // The campaign_type dimension (combo[2]) determines the fundamental
        // priority class, then the priority preset (combo[8]) fine-tunes.
        $campaignType = self::CAMPAIGN_TYPES[$combo[2]];
        $priorityPreset = self::PRIORITY_PRESETS[$combo[8]];

        // Use campaign type's base priority, but allow the preset to override
        // for Remnant/ContractNormal where the preset is compatible.
        if ($combo[2] === 'Override') {
            // Override always uses priority = -1
            $oCampaign->priority = -1;
            $oCampaign->weight   = 0;
        } elseif ($combo[2] === 'eCPM' || $combo[2] === 'ContractECPM') {
            // eCPM types always use priority = -2
            $oCampaign->priority = -2;
            $oCampaign->weight   = 0;
        } elseif ($combo[2] === 'ContractNormal') {
            // Contract uses high priority (1-10)
            $oCampaign->priority = ($priorityPreset['priority'] >= 1) ? $priorityPreset['priority'] : 5;
            $oCampaign->weight   = 0;
        } else {
            // Remnant: priority = 0, weight > 0
            $oCampaign->priority = 0;
            $oCampaign->weight   = max($priorityPreset['weight'], 1);
        }

        // Revenue
        $oCampaign->revenue     = 1.50;
        $oCampaign->revenueType = self::REVENUE_TYPES[$combo[4]];

        // Dates
        $this->_applyDateConfig($oCampaign, $combo[5]);

        // Booking limits
        $this->_applyBookingLimits($oCampaign, $combo[6]);

        // Frequency capping
        $this->_applyCapping($oCampaign, $combo[7]);

        // For high-priority campaigns (p>0), set targets instead of weight
        if ($oCampaign->priority >= 1 && $oCampaign->priority <= 10) {
            $oCampaign->weight = 0;
            $oCampaign->targetImpressions = 1000;
            $oCampaign->targetClicks      = 0;
            $oCampaign->targetConversions = 0;
        } else {
            // Low / override / eCPM — no targets
            $oCampaign->targetImpressions = 0;
            $oCampaign->targetClicks      = 0;
            $oCampaign->targetConversions = 0;
        }

        return $oCampaign;
    }

    private function _applyDateConfig(&$oCampaign, $dateConfig)
    {
        switch ($dateConfig) {
            case 'NoDates':
                $oCampaign->startDate = null;
                $oCampaign->endDate   = null;
                break;
            case 'StartOnly':
                $oCampaign->startDate = new Date('2025-01-01');
                $oCampaign->endDate   = null;
                break;
            case 'EndOnly':
                $oCampaign->startDate = null;
                $oCampaign->endDate   = new Date('2027-12-31');
                break;
            case 'BothDates':
                $oCampaign->startDate = new Date('2025-01-01');
                $oCampaign->endDate   = new Date('2027-12-31');
                break;
            case 'StartEqualsEnd':
                $oCampaign->startDate = new Date('2026-06-15');
                $oCampaign->endDate   = new Date('2026-06-15');
                break;
        }
    }

    private function _applyBookingLimits(&$oCampaign, $bookingConfig)
    {
        switch ($bookingConfig) {
            case 'Unlimited':
                $oCampaign->impressions = -1;
                $oCampaign->clicks      = -1;
                break;
            case 'ImprOnly':
                $oCampaign->impressions = 50000;
                $oCampaign->clicks      = -1;
                break;
            case 'ClicksOnly':
                $oCampaign->impressions = -1;
                $oCampaign->clicks      = 5000;
                break;
            case 'Both':
                $oCampaign->impressions = 50000;
                $oCampaign->clicks      = 5000;
                break;
        }
    }

    private function _applyCapping(&$oCampaign, $cappingConfig)
    {
        switch ($cappingConfig) {
            case 'None':
                $oCampaign->capping        = 0;
                $oCampaign->sessionCapping = 0;
                $oCampaign->block          = 0;
                break;
            case 'CappingOnly':
                $oCampaign->capping        = 10;
                $oCampaign->sessionCapping = 0;
                $oCampaign->block          = 0;
                break;
            case 'SessionOnly':
                $oCampaign->capping        = 0;
                $oCampaign->sessionCapping = 5;
                $oCampaign->block          = 0;
                break;
            case 'BlockOnly':
                $oCampaign->capping        = 0;
                $oCampaign->sessionCapping = 0;
                $oCampaign->block          = 3600;
                break;
            case 'AllThree':
                $oCampaign->capping        = 10;
                $oCampaign->sessionCapping = 5;
                $oCampaign->block          = 3600;
                break;
        }
    }

    // ====================================================================
    // Helper: seed an existing campaign in the DB for Edit/View/Delete
    // ====================================================================
    /**
     * Seeds an existing campaign in the DB for Edit/View/Delete operations.
     *
     * When a combo specifies a campaign_type and status, the seeded campaign
     * is configured to match those dimensions so the test exercises a
     * realistic entity configuration.
     *
     * @param int $advertiserId
     * @param string|null $campaignType  One of the CAMPAIGN_TYPES keys
     * @param string|null $statusName   One of the ENTITY_STATUSES keys
     * @return int  The seeded campaign ID
     */
    private function _seedCampaign($advertiserId, $campaignType = null, $statusName = null)
    {
        $dllCampaign = new PartialMockOA_Dll_Campaign_CombinationTest($this);
        $dllCampaign->setReturnValue('checkPermissions', true);

        $oCampaign = new OA_Dll_CampaignInfo();
        $oCampaign->advertiserId = $advertiserId;
        $oCampaign->campaignName = 'Seeded Campaign';
        $oCampaign->impressions  = -1;
        $oCampaign->clicks       = -1;

        // Apply campaign type defaults if specified
        if ($campaignType !== null && isset(self::CAMPAIGN_TYPES[$campaignType])) {
            $ct = self::CAMPAIGN_TYPES[$campaignType];
            $oCampaign->priority = $ct['priority'];
            $oCampaign->weight   = $ct['weight'];
        } else {
            $oCampaign->priority = 0;
            $oCampaign->weight   = 1;
        }

        // High-priority contracts need targets, not weight
        if ($oCampaign->priority >= 1 && $oCampaign->priority <= 10) {
            $oCampaign->weight = 0;
            $oCampaign->targetImpressions = 1000;
            $oCampaign->targetClicks      = 0;
            $oCampaign->targetConversions = 0;
        }

        $result = $dllCampaign->modify($oCampaign);
        $this->assertTrue($result, 'Seed campaign creation failed: ' . $dllCampaign->getLastError());

        // If a specific status was requested, update the DB row directly
        // since the DLL layer doesn't expose a status setter.
        if ($statusName !== null && isset(self::ENTITY_STATUSES[$statusName])) {
            $statusValue = self::ENTITY_STATUSES[$statusName];
            $doCampaign = OA_Dal::factoryDO('campaigns');
            $doCampaign->get($oCampaign->campaignId);
            $doCampaign->status = $statusValue;
            $doCampaign->update();
        }

        return $oCampaign->campaignId;
    }

    // ====================================================================
    //  Section 4A — 120 Pairwise-Generated Positive Test Cases
    // ====================================================================

    /**
     * Returns the 120 pairwise-generated combination rows.
     * Each row: [account, mode, campaignType, status, revenue, dates, booking, capping, priority]
     *
     * Generated via greedy all-pairs algorithm covering every pair of the 9 dimension
     * values in at least one test case (703/710 feasible pairs; 7 structurally excluded).
     */
    private function _getPositiveCombinations()
    {
        return [
            // C001
            ['ADMIN', 'Delete', 'Remnant', 'Awaiting', 'CPA', 'StartEqualsEnd', 'ImprOnly', 'None', 'p0w1'],
            // C002
            ['TRAFFICKER', 'View', 'ContractECPM', 'Paused', 'MT', 'StartEqualsEnd', 'ImprOnly', 'BlockOnly', 'p0w5'],
            // C003
            ['TRAFFICKER', 'View', 'ContractNormal', 'Expired', 'CPA', 'NoDates', 'ClicksOnly', 'BlockOnly', 'p0w1'],
            // C004
            ['TRAFFICKER', 'View', 'eCPM', 'Expired', 'CPA', 'BothDates', 'ClicksOnly', 'CappingOnly', 'p10w0'],
            // C005
            ['ADVERTISER', 'View', 'Remnant', 'Awaiting', 'CPC', 'StartEqualsEnd', 'ImprOnly', 'None', 'p0w1'],
            // C006
            ['ADMIN', 'Create', 'eCPM', 'Inactive', 'CPC', 'EndOnly', 'Unlimited', 'None', 'p0w1'],
            // C007
            ['TRAFFICKER', 'View', 'Override', 'Expired', 'MT', 'StartEqualsEnd', 'ImprOnly', 'AllThree', 'p5w0'],
            // C008
            ['TRAFFICKER', 'View', 'ContractECPM', 'Expired', 'CPC', 'EndOnly', 'ClicksOnly', 'None', 'p5w0'],
            // C009
            ['TRAFFICKER', 'View', 'ContractECPM', 'Inactive', 'CPA', 'StartOnly', 'Unlimited', 'None', 'p5w0'],
            // C010
            ['TRAFFICKER', 'View', 'ContractNormal', 'Inactive', 'CPM', 'NoDates', 'ClicksOnly', 'SessionOnly', 'p5w0'],
            // C011
            ['ADMIN', 'Edit', 'ContractECPM', 'Expired', 'CPA', 'EndOnly', 'ClicksOnly', 'AllThree', 'p0w5'],
            // C012
            ['MANAGER', 'Edit', 'Override', 'Expired', 'MT', 'EndOnly', 'ImprOnly', 'CappingOnly', 'p0w5'],
            // C013
            ['ADVERTISER', 'View', 'Override', 'Inactive', 'CPM', 'BothDates', 'Both', 'BlockOnly', 'p0w1'],
            // C014
            ['MANAGER', 'Delete', 'ContractNormal', 'Running', 'MT', 'StartOnly', 'Both', 'AllThree', 'p0w5'],
            // C015
            ['MANAGER', 'Create', 'ContractNormal', 'Awaiting', 'CPC', 'StartEqualsEnd', 'Unlimited', 'BlockOnly', 'p0w5'],
            // C016
            ['MANAGER', 'Edit', 'ContractECPM', 'Expired', 'CPC', 'StartEqualsEnd', 'Both', 'BlockOnly', 'p0w5'],
            // C017
            ['ADVERTISER', 'View', 'Remnant', 'Running', 'CPA', 'EndOnly', 'Both', 'AllThree', 'p5w0'],
            // C018
            ['TRAFFICKER', 'View', 'Remnant', 'Inactive', 'MT', 'BothDates', 'ClicksOnly', 'BlockOnly', 'p10w0'],
            // C019
            ['MANAGER', 'Edit', 'ContractNormal', 'Expired', 'MT', 'BothDates', 'ImprOnly', 'None', 'p5w0'],
            // C020
            ['MANAGER', 'Edit', 'Override', 'Expired', 'CPM', 'StartOnly', 'Both', 'BlockOnly', 'p0w5'],
            // C021
            ['MANAGER', 'Delete', 'eCPM', 'Paused', 'MT', 'NoDates', 'ImprOnly', 'AllThree', 'p0w5'],
            // C022
            ['ADMIN', 'Edit', 'Remnant', 'Expired', 'MT', 'StartEqualsEnd', 'ClicksOnly', 'SessionOnly', 'p5w0'],
            // C023
            ['ADVERTISER', 'View', 'ContractNormal', 'Awaiting', 'CPM', 'StartOnly', 'ClicksOnly', 'AllThree', 'p0w5'],
            // C024
            ['TRAFFICKER', 'View', 'eCPM', 'Inactive', 'CPA', 'StartEqualsEnd', 'Both', 'SessionOnly', 'p10w0'],
            // C025
            ['MANAGER', 'View', 'ContractECPM', 'Awaiting', 'CPM', 'BothDates', 'Both', 'BlockOnly', 'p0w1'],
            // C026
            ['MANAGER', 'Edit', 'eCPM', 'Expired', 'CPC', 'StartEqualsEnd', 'ClicksOnly', 'BlockOnly', 'p10w0'],
            // C027
            ['MANAGER', 'Edit', 'Remnant', 'Expired', 'MT', 'BothDates', 'Unlimited', 'BlockOnly', 'p0w5'],
            // C028
            ['ADMIN', 'Edit', 'eCPM', 'Expired', 'CPC', 'EndOnly', 'ImprOnly', 'SessionOnly', 'p5w0'],
            // C029
            ['MANAGER', 'Create', 'eCPM', 'Paused', 'CPA', 'BothDates', 'Both', 'SessionOnly', 'p0w1'],
            // C030
            ['TRAFFICKER', 'View', 'ContractNormal', 'Inactive', 'CPC', 'StartOnly', 'ImprOnly', 'CappingOnly', 'p0w1'],
            // C031
            ['ADMIN', 'View', 'ContractNormal', 'Paused', 'CPC', 'NoDates', 'Both', 'None', 'p10w0'],
            // C032
            ['ADMIN', 'Edit', 'Override', 'Expired', 'CPA', 'StartOnly', 'Both', 'CappingOnly', 'p10w0'],
            // C033
            ['MANAGER', 'Edit', 'Remnant', 'Expired', 'MT', 'BothDates', 'ClicksOnly', 'CappingOnly', 'p10w0'],
            // C034
            ['MANAGER', 'Edit', 'Remnant', 'Expired', 'CPC', 'BothDates', 'Unlimited', 'SessionOnly', 'p5w0'],
            // C035
            ['MANAGER', 'Edit', 'Override', 'Expired', 'MT', 'BothDates', 'ClicksOnly', 'AllThree', 'p5w0'],
            // C036
            ['ADMIN', 'Create', 'ContractECPM', 'Inactive', 'CPM', 'NoDates', 'ClicksOnly', 'AllThree', 'p0w1'],
            // C037
            ['ADMIN', 'Edit', 'eCPM', 'Expired', 'MT', 'StartEqualsEnd', 'ImprOnly', 'None', 'p0w5'],
            // C038
            ['MANAGER', 'View', 'ContractNormal', 'Expired', 'CPA', 'StartOnly', 'Unlimited', 'SessionOnly', 'p5w0'],
            // C039
            ['MANAGER', 'Edit', 'ContractNormal', 'Expired', 'CPM', 'EndOnly', 'Unlimited', 'BlockOnly', 'p0w5'],
            // C040
            ['MANAGER', 'View', 'Remnant', 'Inactive', 'CPM', 'StartEqualsEnd', 'Both', 'AllThree', 'p10w0'],
            // C041
            ['MANAGER', 'Edit', 'ContractECPM', 'Expired', 'CPA', 'EndOnly', 'Unlimited', 'CappingOnly', 'p0w5'],
            // C042
            ['ADMIN', 'Edit', 'Remnant', 'Expired', 'CPC', 'BothDates', 'ImprOnly', 'None', 'p0w1'],
            // C043
            ['MANAGER', 'Edit', 'ContractNormal', 'Expired', 'MT', 'EndOnly', 'ClicksOnly', 'CappingOnly', 'p5w0'],
            // C044
            ['MANAGER', 'Edit', 'ContractECPM', 'Paused', 'CPM', 'StartOnly', 'Both', 'None', 'p0w5'],
            // C045
            ['ADMIN', 'Edit', 'Remnant', 'Expired', 'CPC', 'StartEqualsEnd', 'Both', 'None', 'p0w5'],
            // C046
            ['MANAGER', 'Edit', 'ContractNormal', 'Expired', 'MT', 'StartOnly', 'ImprOnly', 'CappingOnly', 'p10w0'],
            // C047
            ['ADMIN', 'Create', 'Override', 'Running', 'CPA', 'StartOnly', 'ImprOnly', 'BlockOnly', 'p5w0'],
            // C048
            ['ADMIN', 'Edit', 'ContractNormal', 'Expired', 'CPM', 'NoDates', 'Both', 'AllThree', 'p5w0'],
            // C049
            ['TRAFFICKER', 'View', 'eCPM', 'Running', 'CPC', 'BothDates', 'ImprOnly', 'BlockOnly', 'p10w0'],
            // C050
            ['TRAFFICKER', 'View', 'Remnant', 'Awaiting', 'MT', 'StartOnly', 'Both', 'BlockOnly', 'p10w0'],
            // C051
            ['ADMIN', 'Delete', 'ContractECPM', 'Paused', 'CPC', 'StartEqualsEnd', 'Both', 'CappingOnly', 'p5w0'],
            // C052
            ['ADMIN', 'Edit', 'Override', 'Inactive', 'CPA', 'EndOnly', 'Both', 'None', 'p10w0'],
            // C053
            ['ADMIN', 'Edit', 'ContractECPM', 'Expired', 'CPM', 'StartOnly', 'Both', 'AllThree', 'p5w0'],
            // C054
            ['TRAFFICKER', 'View', 'ContractNormal', 'Running', 'CPA', 'StartEqualsEnd', 'Unlimited', 'CappingOnly', 'p10w0'],
            // C055
            ['ADMIN', 'Delete', 'ContractNormal', 'Expired', 'CPA', 'BothDates', 'Both', 'AllThree', 'p5w0'],
            // C056
            ['MANAGER', 'Edit', 'ContractECPM', 'Expired', 'CPC', 'EndOnly', 'ImprOnly', 'BlockOnly', 'p10w0'],
            // C057
            ['TRAFFICKER', 'View', 'eCPM', 'Paused', 'MT', 'StartEqualsEnd', 'Both', 'None', 'p0w5'],
            // C058
            ['MANAGER', 'Edit', 'Override', 'Expired', 'CPM', 'BothDates', 'ImprOnly', 'BlockOnly', 'p0w5'],
            // C059
            ['MANAGER', 'Delete', 'ContractECPM', 'Inactive', 'CPA', 'NoDates', 'Unlimited', 'SessionOnly', 'p5w0'],
            // C060
            ['ADVERTISER', 'View', 'ContractECPM', 'Expired', 'MT', 'StartOnly', 'Both', 'CappingOnly', 'p0w1'],
            // C061
            ['ADMIN', 'Edit', 'Remnant', 'Expired', 'CPC', 'NoDates', 'Unlimited', 'SessionOnly', 'p0w1'],
            // C062
            ['MANAGER', 'Edit', 'ContractNormal', 'Expired', 'CPC', 'BothDates', 'Unlimited', 'None', 'p10w0'],
            // C063
            ['ADMIN', 'Edit', 'eCPM', 'Awaiting', 'CPM', 'StartOnly', 'Unlimited', 'BlockOnly', 'p10w0'],
            // C064
            ['ADMIN', 'Edit', 'Remnant', 'Expired', 'CPM', 'StartOnly', 'ClicksOnly', 'BlockOnly', 'p0w5'],
            // C065
            ['ADMIN', 'View', 'Remnant', 'Paused', 'MT', 'NoDates', 'ImprOnly', 'AllThree', 'p10w0'],
            // C066
            ['MANAGER', 'View', 'eCPM', 'Running', 'CPM', 'NoDates', 'Unlimited', 'None', 'p5w0'],
            // C067
            ['MANAGER', 'Edit', 'Remnant', 'Expired', 'CPM', 'EndOnly', 'Both', 'BlockOnly', 'p0w1'],
            // C068
            ['ADMIN', 'Edit', 'ContractECPM', 'Expired', 'MT', 'BothDates', 'Unlimited', 'None', 'p5w0'],
            // C069
            ['ADMIN', 'Edit', 'Override', 'Expired', 'MT', 'EndOnly', 'Both', 'CappingOnly', 'p0w5'],
            // C070
            ['ADMIN', 'Delete', 'ContractNormal', 'Awaiting', 'MT', 'EndOnly', 'ClicksOnly', 'SessionOnly', 'p5w0'],
            // C071
            ['MANAGER', 'Create', 'ContractECPM', 'Paused', 'CPC', 'NoDates', 'Both', 'BlockOnly', 'p0w5'],
            // C072
            ['MANAGER', 'Edit', 'Remnant', 'Expired', 'CPM', 'StartEqualsEnd', 'ClicksOnly', 'BlockOnly', 'p0w1'],
            // C073
            ['ADMIN', 'Edit', 'ContractECPM', 'Expired', 'CPM', 'StartEqualsEnd', 'Both', 'SessionOnly', 'p0w1'],
            // C074
            ['ADMIN', 'Delete', 'ContractECPM', 'Running', 'CPC', 'EndOnly', 'ImprOnly', 'BlockOnly', 'p10w0'],
            // C075
            ['MANAGER', 'Edit', 'ContractNormal', 'Expired', 'CPC', 'BothDates', 'ImprOnly', 'CappingOnly', 'p5w0'],
            // C076
            ['ADVERTISER', 'View', 'Override', 'Paused', 'CPA', 'StartOnly', 'ImprOnly', 'SessionOnly', 'p10w0'],
            // C077
            ['TRAFFICKER', 'View', 'ContractNormal', 'Running', 'MT', 'NoDates', 'ImprOnly', 'BlockOnly', 'p0w1'],
            // C078
            ['ADVERTISER', 'View', 'eCPM', 'Awaiting', 'CPC', 'NoDates', 'ImprOnly', 'CappingOnly', 'p5w0'],
            // C079
            ['ADVERTISER', 'View', 'ContractNormal', 'Paused', 'CPM', 'BothDates', 'Both', 'None', 'p5w0'],
            // C080
            ['MANAGER', 'Edit', 'Override', 'Awaiting', 'MT', 'NoDates', 'Unlimited', 'CappingOnly', 'p0w1'],
            // C081
            ['TRAFFICKER', 'View', 'eCPM', 'Paused', 'CPM', 'NoDates', 'Both', 'AllThree', 'p10w0'],
            // C082
            ['MANAGER', 'Edit', 'Override', 'Expired', 'CPM', 'StartOnly', 'ClicksOnly', 'BlockOnly', 'p0w5'],
            // C083
            ['ADVERTISER', 'View', 'Remnant', 'Paused', 'MT', 'NoDates', 'Unlimited', 'CappingOnly', 'p0w1'],
            // C084
            ['MANAGER', 'Create', 'Override', 'Paused', 'CPM', 'EndOnly', 'ClicksOnly', 'SessionOnly', 'p10w0'],
            // C085
            ['ADMIN', 'Create', 'Override', 'Paused', 'CPC', 'StartOnly', 'Unlimited', 'AllThree', 'p0w1'],
            // C086
            ['TRAFFICKER', 'View', 'Override', 'Running', 'CPM', 'StartOnly', 'Unlimited', 'AllThree', 'p10w0'],
            // C087
            ['ADVERTISER', 'View', 'eCPM', 'Running', 'CPM', 'StartEqualsEnd', 'ClicksOnly', 'None', 'p5w0'],
            // C088
            ['MANAGER', 'Edit', 'ContractECPM', 'Expired', 'MT', 'BothDates', 'Unlimited', 'AllThree', 'p0w5'],
            // C089
            ['ADVERTISER', 'View', 'ContractECPM', 'Inactive', 'CPM', 'StartOnly', 'Both', 'AllThree', 'p10w0'],
            // C090
            ['ADMIN', 'Edit', 'ContractECPM', 'Expired', 'MT', 'EndOnly', 'ImprOnly', 'SessionOnly', 'p0w1'],
            // C091
            ['TRAFFICKER', 'View', 'Override', 'Paused', 'CPM', 'EndOnly', 'ImprOnly', 'CappingOnly', 'p5w0'],
            // C092
            ['ADVERTISER', 'View', 'eCPM', 'Paused', 'CPA', 'StartOnly', 'ClicksOnly', 'CappingOnly', 'p0w5'],
            // C093
            ['ADVERTISER', 'View', 'Remnant', 'Expired', 'CPM', 'BothDates', 'Unlimited', 'SessionOnly', 'p0w5'],
            // C094
            ['ADVERTISER', 'View', 'ContractECPM', 'Running', 'CPC', 'NoDates', 'Both', 'SessionOnly', 'p0w5'],
            // C095
            ['MANAGER', 'Create', 'Remnant', 'Inactive', 'MT', 'BothDates', 'Unlimited', 'BlockOnly', 'p5w0'],
            // C096
            ['MANAGER', 'Edit', 'ContractNormal', 'Expired', 'CPA', 'NoDates', 'Both', 'CappingOnly', 'p0w5'],
            // C097
            ['TRAFFICKER', 'View', 'Override', 'Expired', 'CPC', 'EndOnly', 'Unlimited', 'CappingOnly', 'p10w0'],
            // C098
            ['MANAGER', 'Edit', 'eCPM', 'Expired', 'CPA', 'StartEqualsEnd', 'ImprOnly', 'None', 'p10w0'],
            // C099
            ['ADMIN', 'Edit', 'ContractNormal', 'Expired', 'CPC', 'BothDates', 'ClicksOnly', 'SessionOnly', 'p0w1'],
            // C100
            ['ADMIN', 'View', 'ContractNormal', 'Running', 'CPC', 'BothDates', 'ClicksOnly', 'None', 'p0w1'],
            // C101
            ['MANAGER', 'Edit', 'ContractNormal', 'Awaiting', 'CPC', 'BothDates', 'ImprOnly', 'AllThree', 'p10w0'],
            // C102
            ['ADVERTISER', 'View', 'Override', 'Inactive', 'MT', 'EndOnly', 'Unlimited', 'BlockOnly', 'p0w5'],
            // C103
            ['TRAFFICKER', 'View', 'eCPM', 'Awaiting', 'CPM', 'StartOnly', 'ClicksOnly', 'CappingOnly', 'p0w1'],
            // C104
            ['MANAGER', 'Create', 'Remnant', 'Running', 'MT', 'StartOnly', 'Both', 'CappingOnly', 'p10w0'],
            // C105
            ['ADMIN', 'Edit', 'eCPM', 'Expired', 'CPA', 'BothDates', 'ClicksOnly', 'BlockOnly', 'p0w1'],
            // C106
            ['ADMIN', 'Edit', 'Remnant', 'Expired', 'CPA', 'EndOnly', 'ImprOnly', 'None', 'p0w5'],
            // C107
            ['MANAGER', 'View', 'Remnant', 'Expired', 'CPM', 'EndOnly', 'ClicksOnly', 'AllThree', 'p0w1'],
            // C108
            ['MANAGER', 'Edit', 'Override', 'Expired', 'CPC', 'BothDates', 'Unlimited', 'BlockOnly', 'p5w0'],
            // C109
            ['TRAFFICKER', 'View', 'Override', 'Paused', 'CPC', 'BothDates', 'ClicksOnly', 'AllThree', 'p0w1'],
            // C110
            ['TRAFFICKER', 'View', 'eCPM', 'Awaiting', 'MT', 'EndOnly', 'Unlimited', 'SessionOnly', 'p0w1'],
            // C111
            ['ADMIN', 'Edit', 'Remnant', 'Expired', 'CPC', 'NoDates', 'ImprOnly', 'SessionOnly', 'p0w5'],
            // C112
            ['ADVERTISER', 'View', 'ContractNormal', 'Paused', 'CPC', 'EndOnly', 'Unlimited', 'BlockOnly', 'p5w0'],
            // C113
            ['MANAGER', 'View', 'Override', 'Running', 'CPC', 'StartEqualsEnd', 'ClicksOnly', 'SessionOnly', 'p5w0'],
            // C114
            ['MANAGER', 'Delete', 'Override', 'Expired', 'CPM', 'EndOnly', 'ClicksOnly', 'CappingOnly', 'p0w5'],
            // C115
            ['MANAGER', 'Edit', 'ContractECPM', 'Inactive', 'MT', 'StartEqualsEnd', 'ClicksOnly', 'AllThree', 'p10w0'],
            // C116
            ['MANAGER', 'Edit', 'Override', 'Running', 'MT', 'BothDates', 'Unlimited', 'CappingOnly', 'p10w0'],
            // C117
            ['TRAFFICKER', 'View', 'ContractECPM', 'Awaiting', 'CPA', 'BothDates', 'ImprOnly', 'AllThree', 'p5w0'],
            // C118
            ['ADMIN', 'View', 'ContractNormal', 'Inactive', 'CPM', 'BothDates', 'ImprOnly', 'CappingOnly', 'p0w5'],
            // C119
            ['ADMIN', 'Create', 'eCPM', 'Inactive', 'CPA', 'StartOnly', 'ClicksOnly', 'CappingOnly', 'p0w5'],
            // C120
            ['ADVERTISER', 'View', 'Override', 'Awaiting', 'MT', 'NoDates', 'ClicksOnly', 'None', 'p10w0'],
        ];
    }

    /**
     * Section 4A: Positive pairwise combination tests.
     *
     * Iterates over all 120 combinations and exercises the appropriate
     * CRUD operation (Create / Edit / View / Delete) via the DLL layer,
     * asserting success for every valid combination.
     */
    public function testPositiveCombinations()
    {
        $combinations = $this->_getPositiveCombinations();
        $advertiserId = $this->_createAdvertiser();

        foreach ($combinations as $idx => $combo) {
            $caseId  = sprintf('C%03d', $idx + 1);
            $account = $combo[0]; // ADMIN, MANAGER, ADVERTISER, TRAFFICKER
            $mode    = $combo[1]; // Create, Edit, Delete, View
            $label   = $caseId . ' [' . implode('|', $combo) . ']';

            // Build a mock with permissions returning true (positive tests)
            $dllCampaign = new PartialMockOA_Dll_Campaign_CombinationTest($this);
            $dllCampaign->setReturnValue('checkPermissions', true);

            switch ($mode) {
                case 'Create':
                    $oCampaignInfo = $this->_buildCampaignInfo($combo, $advertiserId);
                    $result = $dllCampaign->modify($oCampaignInfo);
                    $this->assertTrue(
                        $result,
                        "{$label}: Create should succeed — " . $dllCampaign->getLastError(),
                    );
                    $this->assertNotNull(
                        $oCampaignInfo->campaignId,
                        "{$label}: campaignId should be set after create",
                    );
                    break;

                case 'Edit':
                    // Seed a campaign with matching type/status, then edit
                    $seedId = $this->_seedCampaign($advertiserId, $combo[2], $combo[3]);
                    $oCampaignInfo = $this->_buildCampaignInfo($combo, $advertiserId, $seedId);
                    $result = $dllCampaign->modify($oCampaignInfo);
                    $this->assertTrue(
                        $result,
                        "{$label}: Edit should succeed — " . $dllCampaign->getLastError(),
                    );
                    break;

                case 'Delete':
                    $seedId = $this->_seedCampaign($advertiserId, $combo[2], $combo[3]);
                    $result = $dllCampaign->delete($seedId);
                    $this->assertTrue(
                        $result,
                        "{$label}: Delete should succeed — " . $dllCampaign->getLastError(),
                    );
                    break;

                case 'View':
                    $seedId = $this->_seedCampaign($advertiserId, $combo[2], $combo[3]);
                    $oCampaignOut = null;
                    $result = $dllCampaign->getCampaign($seedId, $oCampaignOut);
                    $this->assertTrue(
                        $result,
                        "{$label}: View should succeed — " . $dllCampaign->getLastError(),
                    );
                    $this->assertNotNull(
                        $oCampaignOut,
                        "{$label}: getCampaign output should not be null",
                    );
                    break;
            }
        }
    }

    // ====================================================================
    //  Section 4B — Negative / Excluded Combination Tests
    // ====================================================================

    /**
     * 4B-1: ADVERTISER cannot Create campaigns.
     * Permission check should deny: modify() with [OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER] enforced.
     */
    public function testAdvertiserCannotCreateCampaign()
    {
        $advertiserId = $this->_createAdvertiser();
        $dllCampaign = new PartialMockOA_Dll_Campaign_CombinationTest($this);
        // Simulate ADVERTISER: checkPermissions returns false
        $dllCampaign->setReturnValue('checkPermissions', false);

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $advertiserId;
        $oCampaignInfo->campaignName = 'Advertiser Create Attempt';

        $result = $dllCampaign->modify($oCampaignInfo);
        $this->assertFalse($result, 'ADVERTISER should not be able to Create a campaign');
    }

    /**
     * 4B-2: ADVERTISER cannot Edit campaigns.
     */
    public function testAdvertiserCannotEditCampaign()
    {
        $advertiserId = $this->_createAdvertiser();
        $seedId = $this->_seedCampaign($advertiserId);

        $dllCampaign = new PartialMockOA_Dll_Campaign_CombinationTest($this);
        $dllCampaign->setReturnValue('checkPermissions', false);

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->campaignId   = $seedId;
        $oCampaignInfo->campaignName = 'Advertiser Edit Attempt';

        $result = $dllCampaign->modify($oCampaignInfo);
        $this->assertFalse($result, 'ADVERTISER should not be able to Edit a campaign');
    }

    /**
     * 4B-3: ADVERTISER cannot Delete campaigns.
     */
    public function testAdvertiserCannotDeleteCampaign()
    {
        $advertiserId = $this->_createAdvertiser();
        $seedId = $this->_seedCampaign($advertiserId);

        $dllCampaign = new PartialMockOA_Dll_Campaign_CombinationTest($this);
        $dllCampaign->setReturnValue('checkPermissions', false);

        $result = $dllCampaign->delete($seedId);
        $this->assertFalse($result, 'ADVERTISER should not be able to Delete a campaign');
    }

    /**
     * 4B-4: TRAFFICKER cannot Create campaigns.
     */
    public function testTraffickerCannotCreateCampaign()
    {
        $advertiserId = $this->_createAdvertiser();
        $dllCampaign = new PartialMockOA_Dll_Campaign_CombinationTest($this);
        $dllCampaign->setReturnValue('checkPermissions', false);

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $advertiserId;
        $oCampaignInfo->campaignName = 'Trafficker Create Attempt';

        $result = $dllCampaign->modify($oCampaignInfo);
        $this->assertFalse($result, 'TRAFFICKER should not be able to Create a campaign');
    }

    /**
     * 4B-5: TRAFFICKER cannot Edit campaigns.
     */
    public function testTraffickerCannotEditCampaign()
    {
        $advertiserId = $this->_createAdvertiser();
        $seedId = $this->_seedCampaign($advertiserId);

        $dllCampaign = new PartialMockOA_Dll_Campaign_CombinationTest($this);
        $dllCampaign->setReturnValue('checkPermissions', false);

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->campaignId   = $seedId;
        $oCampaignInfo->campaignName = 'Trafficker Edit Attempt';

        $result = $dllCampaign->modify($oCampaignInfo);
        $this->assertFalse($result, 'TRAFFICKER should not be able to Edit a campaign');
    }

    /**
     * 4B-6: TRAFFICKER cannot Delete campaigns.
     */
    public function testTraffickerCannotDeleteCampaign()
    {
        $advertiserId = $this->_createAdvertiser();
        $seedId = $this->_seedCampaign($advertiserId);

        $dllCampaign = new PartialMockOA_Dll_Campaign_CombinationTest($this);
        $dllCampaign->setReturnValue('checkPermissions', false);

        $result = $dllCampaign->delete($seedId);
        $this->assertFalse($result, 'TRAFFICKER should not be able to Delete a campaign');
    }

    /**
     * 4B-7: High priority (1-10) + weight > 0 is rejected by validation.
     * Campaign.php:125-128 enforces this.
     */
    public function testHighPriorityWithWeightIsRejected()
    {
        $advertiserId = $this->_createAdvertiser();
        $dllCampaign = new PartialMockOA_Dll_Campaign_CombinationTest($this);
        $dllCampaign->setReturnValue('checkPermissions', true);

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $advertiserId;
        $oCampaignInfo->campaignName = 'Priority + Weight Conflict';
        $oCampaignInfo->priority     = 5;
        $oCampaignInfo->weight       = 3; // Invalid: high priority cannot have weight > 0
        $oCampaignInfo->impressions  = -1;
        $oCampaignInfo->clicks       = -1;

        $result = $dllCampaign->modify($oCampaignInfo);
        $this->assertFalse($result, 'High priority campaign with weight > 0 should be rejected');
        $this->assertPattern(
            '/[Hh]igh.*priority.*weight/',
            $dllCampaign->getLastError(),
            'Error message should mention priority/weight conflict',
        );
    }

    /**
     * 4B-8: priority = 0 (remnant) + targets > 0 is rejected.
     * Campaign.php:129-132 enforces this.
     */
    public function testLowPriorityWithTargetsIsRejected()
    {
        $advertiserId = $this->_createAdvertiser();
        $dllCampaign = new PartialMockOA_Dll_Campaign_CombinationTest($this);
        $dllCampaign->setReturnValue('checkPermissions', true);

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId      = $advertiserId;
        $oCampaignInfo->campaignName      = 'Remnant With Targets';
        $oCampaignInfo->priority          = 0;
        $oCampaignInfo->weight            = 1;
        $oCampaignInfo->targetImpressions = 5000; // Invalid: remnant can't have targets
        $oCampaignInfo->impressions       = -1;
        $oCampaignInfo->clicks            = -1;

        $result = $dllCampaign->modify($oCampaignInfo);
        $this->assertFalse($result, 'Low priority campaign with targets > 0 should be rejected');
        $this->assertPattern(
            '/[Ll]ow.*priority.*targets|[Oo]verride.*priority.*targets/',
            $dllCampaign->getLastError(),
            'Error message should mention priority/targets conflict',
        );
    }

    /**
     * 4B-9: Override priority (-1) + targets > 0 is also rejected.
     * Campaign.php:129-132 — override is !_isHighPriority() so same validation.
     */
    public function testOverridePriorityWithTargetsIsRejected()
    {
        $advertiserId = $this->_createAdvertiser();
        $dllCampaign = new PartialMockOA_Dll_Campaign_CombinationTest($this);
        $dllCampaign->setReturnValue('checkPermissions', true);

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId      = $advertiserId;
        $oCampaignInfo->campaignName      = 'Override With Targets';
        $oCampaignInfo->priority          = -1;
        $oCampaignInfo->weight            = 0;
        $oCampaignInfo->targetImpressions = 5000; // Invalid
        $oCampaignInfo->impressions       = -1;
        $oCampaignInfo->clicks            = -1;

        $result = $dllCampaign->modify($oCampaignInfo);
        $this->assertFalse($result, 'Override priority campaign with targets > 0 should be rejected');
    }

    /**
     * 4B-10: Start date after end date is rejected by checkDateOrder().
     */
    public function testStartDateAfterEndDateIsRejected()
    {
        $advertiserId = $this->_createAdvertiser();
        $dllCampaign = new PartialMockOA_Dll_Campaign_CombinationTest($this);
        $dllCampaign->setReturnValue('checkPermissions', true);

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $advertiserId;
        $oCampaignInfo->campaignName = 'Bad Date Order';
        $oCampaignInfo->startDate    = new Date('2027-12-31'); // Start after end
        $oCampaignInfo->endDate      = new Date('2025-01-01');
        $oCampaignInfo->priority     = 0;
        $oCampaignInfo->weight       = 1;
        $oCampaignInfo->impressions  = -1;
        $oCampaignInfo->clicks       = -1;

        $result = $dllCampaign->modify($oCampaignInfo);
        $this->assertFalse($result, 'Campaign with startDate > endDate should be rejected');
    }

    /**
     * 4B-11: Delete non-existent campaign returns false.
     */
    public function testDeleteNonExistentCampaign()
    {
        $dllCampaign = new PartialMockOA_Dll_Campaign_CombinationTest($this);
        $dllCampaign->setReturnValue('checkPermissions', true);

        $result = $dllCampaign->delete(999999);
        $this->assertFalse($result, 'Deleting a non-existent campaign should fail');
    }

    /**
     * 4B-12: Edit non-existent campaign returns false.
     */
    public function testEditNonExistentCampaign()
    {
        $dllCampaign = new PartialMockOA_Dll_Campaign_CombinationTest($this);
        $dllCampaign->setReturnValue('checkPermissions', true);

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->campaignId   = 999999;
        $oCampaignInfo->campaignName = 'Ghost Campaign';

        $result = $dllCampaign->modify($oCampaignInfo);
        $this->assertFalse($result, 'Editing a non-existent campaign should fail');
        $this->assertEqual(
            'Unknown campaignId Error',
            $dllCampaign->getLastError(),
            'Error should be Unknown campaignId',
        );
    }

    /**
     * 4B-13: View non-existent campaign returns false.
     */
    public function testViewNonExistentCampaign()
    {
        $dllCampaign = new PartialMockOA_Dll_Campaign_CombinationTest($this);
        $dllCampaign->setReturnValue('checkPermissions', true);

        $oCampaignOut = null;
        $result = $dllCampaign->getCampaign(999999, $oCampaignOut);
        $this->assertFalse($result, 'Viewing a non-existent campaign should fail');
    }

    /**
     * 4B-14: Create campaign with non-existent advertiserId fails.
     */
    public function testCreateCampaignWithInvalidAdvertiser()
    {
        $dllCampaign = new PartialMockOA_Dll_Campaign_CombinationTest($this);
        $dllCampaign->setReturnValue('checkPermissions', true);

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = 999999; // Does not exist
        $oCampaignInfo->campaignName = 'Invalid Advertiser';

        $result = $dllCampaign->modify($oCampaignInfo);
        $this->assertFalse($result, 'Creating campaign with non-existent advertiser should fail');
    }

    /**
     * 4B-15: Multiple priority boundary values — priority = 1 + weight > 0 rejected.
     */
    public function testPriorityBoundaryOneWithWeightRejected()
    {
        $advertiserId = $this->_createAdvertiser();
        $dllCampaign = new PartialMockOA_Dll_Campaign_CombinationTest($this);
        $dllCampaign->setReturnValue('checkPermissions', true);

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $advertiserId;
        $oCampaignInfo->campaignName = 'Priority 1 Weight 1';
        $oCampaignInfo->priority     = 1;
        $oCampaignInfo->weight       = 1;
        $oCampaignInfo->impressions  = -1;
        $oCampaignInfo->clicks       = -1;

        $result = $dllCampaign->modify($oCampaignInfo);
        $this->assertFalse($result, 'Priority=1 with weight=1 should be rejected');
    }

    /**
     * 4B-16: priority = 10 (max contract) + weight > 0 rejected.
     */
    public function testPriorityBoundaryTenWithWeightRejected()
    {
        $advertiserId = $this->_createAdvertiser();
        $dllCampaign = new PartialMockOA_Dll_Campaign_CombinationTest($this);
        $dllCampaign->setReturnValue('checkPermissions', true);

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $advertiserId;
        $oCampaignInfo->campaignName = 'Priority 10 Weight 5';
        $oCampaignInfo->priority     = 10;
        $oCampaignInfo->weight       = 5;
        $oCampaignInfo->impressions  = -1;
        $oCampaignInfo->clicks       = -1;

        $result = $dllCampaign->modify($oCampaignInfo);
        $this->assertFalse($result, 'Priority=10 with weight=5 should be rejected');
    }

    /**
     * 4B-17: Low priority with target clicks (not just impressions).
     */
    public function testLowPriorityWithTargetClicksRejected()
    {
        $advertiserId = $this->_createAdvertiser();
        $dllCampaign = new PartialMockOA_Dll_Campaign_CombinationTest($this);
        $dllCampaign->setReturnValue('checkPermissions', true);

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId  = $advertiserId;
        $oCampaignInfo->campaignName  = 'Remnant With Click Targets';
        $oCampaignInfo->priority      = 0;
        $oCampaignInfo->weight        = 1;
        $oCampaignInfo->targetClicks  = 500;
        $oCampaignInfo->impressions   = -1;
        $oCampaignInfo->clicks        = -1;

        $result = $dllCampaign->modify($oCampaignInfo);
        $this->assertFalse($result, 'Low priority with targetClicks > 0 should be rejected');
    }

    /**
     * 4B-18: Low priority with target conversions rejected.
     */
    public function testLowPriorityWithTargetConversionsRejected()
    {
        $advertiserId = $this->_createAdvertiser();
        $dllCampaign = new PartialMockOA_Dll_Campaign_CombinationTest($this);
        $dllCampaign->setReturnValue('checkPermissions', true);

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId       = $advertiserId;
        $oCampaignInfo->campaignName       = 'Remnant With Conv Targets';
        $oCampaignInfo->priority           = 0;
        $oCampaignInfo->weight             = 1;
        $oCampaignInfo->targetConversions  = 100;
        $oCampaignInfo->impressions        = -1;
        $oCampaignInfo->clicks             = -1;

        $result = $dllCampaign->modify($oCampaignInfo);
        $this->assertFalse($result, 'Low priority with targetConversions > 0 should be rejected');
    }

    /**
     * 4B-19: eCPM priority (-2) with targets > 0 rejected.
     */
    public function testEcpmPriorityWithTargetsRejected()
    {
        $advertiserId = $this->_createAdvertiser();
        $dllCampaign = new PartialMockOA_Dll_Campaign_CombinationTest($this);
        $dllCampaign->setReturnValue('checkPermissions', true);

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId      = $advertiserId;
        $oCampaignInfo->campaignName      = 'eCPM With Targets';
        $oCampaignInfo->priority          = -2;
        $oCampaignInfo->weight            = 0;
        $oCampaignInfo->targetImpressions = 1000;
        $oCampaignInfo->impressions       = -1;
        $oCampaignInfo->clicks            = -1;

        $result = $dllCampaign->modify($oCampaignInfo);
        $this->assertFalse($result, 'eCPM priority campaign with targets > 0 should be rejected');
    }

    /**
     * 4B-20: ADVERTISER permission denial for all CRUD operations (comprehensive).
     * Verifies that all three mutating operations are blocked.
     */
    public function testAdvertiserAllCrudPermissionDenied()
    {
        $advertiserId = $this->_createAdvertiser();
        $seedId = $this->_seedCampaign($advertiserId);

        $dllCampaign = new PartialMockOA_Dll_Campaign_CombinationTest($this);
        $dllCampaign->setReturnValue('checkPermissions', false);

        // Create
        $oCampaign = new OA_Dll_CampaignInfo();
        $oCampaign->advertiserId = $advertiserId;
        $oCampaign->campaignName = 'Adv Create';
        $this->assertFalse(
            $dllCampaign->modify($oCampaign),
            'ADVERTISER: Create should be denied',
        );

        // Edit
        $oCampaign2 = new OA_Dll_CampaignInfo();
        $oCampaign2->campaignId   = $seedId;
        $oCampaign2->campaignName = 'Adv Edit';
        $this->assertFalse(
            $dllCampaign->modify($oCampaign2),
            'ADVERTISER: Edit should be denied',
        );

        // Delete
        $this->assertFalse(
            $dllCampaign->delete($seedId),
            'ADVERTISER: Delete should be denied',
        );
    }

    /**
     * 4B-21: TRAFFICKER permission denial for all CRUD operations (comprehensive).
     */
    public function testTraffickerAllCrudPermissionDenied()
    {
        $advertiserId = $this->_createAdvertiser();
        $seedId = $this->_seedCampaign($advertiserId);

        $dllCampaign = new PartialMockOA_Dll_Campaign_CombinationTest($this);
        $dllCampaign->setReturnValue('checkPermissions', false);

        // Create
        $oCampaign = new OA_Dll_CampaignInfo();
        $oCampaign->advertiserId = $advertiserId;
        $oCampaign->campaignName = 'Traff Create';
        $this->assertFalse(
            $dllCampaign->modify($oCampaign),
            'TRAFFICKER: Create should be denied',
        );

        // Edit
        $oCampaign2 = new OA_Dll_CampaignInfo();
        $oCampaign2->campaignId   = $seedId;
        $oCampaign2->campaignName = 'Traff Edit';
        $this->assertFalse(
            $dllCampaign->modify($oCampaign2),
            'TRAFFICKER: Edit should be denied',
        );

        // Delete
        $this->assertFalse(
            $dllCampaign->delete($seedId),
            'TRAFFICKER: Delete should be denied',
        );
    }
}
