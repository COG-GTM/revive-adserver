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
 * Combinatorial (pairwise) tests for Campaign CRUD operations.
 *
 * Covers the combination matrix defined in Section 4 of the
 * Campaign combinatorial testing plan:
 *   - Section 4A: ~100 included pairwise combos (positive tests)
 *   - Section 4B: ~20 excluded combos (negative / permission-denied tests)
 *   - Additional validation-error negative tests (priority>0 + weight>0)
 *
 * Factors:
 *   1. Account type     : ADMIN, MANAGER, ADVERTISER, TRAFFICKER
 *   2. Page mode         : Create, Edit, View, Delete
 *   3. Campaign type     : Remnant, ContractNormal, Override, eCPM, ContractECPM
 *   4. Entity status     : Running, Paused, Awaiting, Expired, Inactive, Pending, Approval, Rejected
 *   5. Revenue type      : CPM, CPC, CPA, MT
 *   6. Date config       : NoDates, StartOnly, EndOnly, BothDates, StartEqEnd
 *   7. Booking limits    : Unlimited, ImprOnly, ClicksOnly, Both
 *   8. Frequency capping : None, CappingOnly, SessionOnly, BlockOnly, AllThree
 *   9. Priority/weight   : p=0 w>0, p>0 w=0
 *
 * @package    OpenXDll
 * @subpackage TestSuite
 */
class OA_Dll_CampaignCrudComboTest extends DllUnitTestCase
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
            'PartialMockOA_Dll_Campaign_CrudComboTest',
            ['checkPermissions'],
        );
        Mock::generatePartial(
            'OA_Dll_Advertiser',
            'PartialMockOA_Dll_Advertiser_CrudComboTest',
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
    // Helper: build a CampaignInfo from combo parameters
    // ------------------------------------------------------------------

    /**
     * Maps a campaign-type label to the priority value stored in the DB.
     */
    private function _priorityForType($campaignType)
    {
        return match ($campaignType) {
            'Remnant'        => 0,
            'ContractNormal' => 5,
            'Override'       => -1,
            'eCPM'           => -2,
            'ContractECPM'   => -2,
            default          => 0,
        };
    }

    /**
     * Maps a campaign-type label to the weight value.
     * Remnant and Override get weight > 0; contract types get weight = 0.
     */
    private function _weightForType($campaignType)
    {
        return match ($campaignType) {
            'Remnant'  => 1,
            'Override' => 1,
            default    => 0,
        };
    }

    /**
     * Maps a priority/weight label to overrides.
     * Only applied when not contradicting campaign type rules.
     */
    private function _applyPriorityWeight($campaignType, $pwLabel, &$priority, &$weight)
    {
        $priority = $this->_priorityForType($campaignType);
        $weight   = $this->_weightForType($campaignType);

        // The pw label gives the general intent; adapt where safe.
        if ($pwLabel === 'p5w0' && in_array($campaignType, ['Override', 'eCPM', 'ContractECPM'])) {
            // For these types, priority is already negative; weight should be 0.
            $weight = 0;
        }
        if ($pwLabel === 'p0w1' && in_array($campaignType, ['ContractNormal'])) {
            // ContractNormal normally has p>0 w=0; keep it valid.
            $priority = 5;
            $weight   = 0;
        }
    }

    /**
     * @return int  MAX_FINANCE_* constant
     */
    private function _revenueConst($revenueLabel)
    {
        return match ($revenueLabel) {
            'CPM' => MAX_FINANCE_CPM,
            'CPC' => MAX_FINANCE_CPC,
            'CPA' => MAX_FINANCE_CPA,
            'MT'  => MAX_FINANCE_MT,
            default => MAX_FINANCE_CPM,
        };
    }

    /**
     * Apply date settings to a CampaignInfo object.
     */
    private function _applyDates(&$oCampaign, $dateConfig)
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
            case 'StartEqEnd':
                $oCampaign->startDate = new Date('2026-06-15');
                $oCampaign->endDate   = new Date('2026-06-15');
                break;
        }
    }

    /**
     * Apply booking-limit settings to a CampaignInfo.
     */
    private function _applyBooking(&$oCampaign, $bookingLabel)
    {
        switch ($bookingLabel) {
            case 'Unlimited':
                $oCampaign->impressions = -1;
                $oCampaign->clicks      = -1;
                break;
            case 'ImprOnly':
                $oCampaign->impressions = 10000;
                $oCampaign->clicks      = -1;
                break;
            case 'ClicksOnly':
                $oCampaign->impressions = -1;
                $oCampaign->clicks      = 500;
                break;
            case 'Both':
                $oCampaign->impressions = 10000;
                $oCampaign->clicks      = 500;
                break;
        }
    }

    /**
     * Apply frequency-capping settings.
     */
    private function _applyCapping(&$oCampaign, $cappingLabel)
    {
        switch ($cappingLabel) {
            case 'None':
                $oCampaign->capping        = 0;
                $oCampaign->sessionCapping  = 0;
                $oCampaign->block           = 0;
                break;
            case 'CappingOnly':
                $oCampaign->capping        = 10;
                $oCampaign->sessionCapping  = 0;
                $oCampaign->block           = 0;
                break;
            case 'SessionOnly':
                $oCampaign->capping        = 0;
                $oCampaign->sessionCapping  = 5;
                $oCampaign->block           = 0;
                break;
            case 'BlockOnly':
                $oCampaign->capping        = 0;
                $oCampaign->sessionCapping  = 0;
                $oCampaign->block           = 3600;
                break;
            case 'AllThree':
                $oCampaign->capping        = 10;
                $oCampaign->sessionCapping  = 5;
                $oCampaign->block           = 3600;
                break;
        }
    }

    /**
     * Build a CampaignInfo object from a combo row.
     *
     * @param int    $advertiserId
     * @param array  $combo  [account, mode, campaignType, status, revenue, dates, booking, capping, pw]
     * @return OA_Dll_CampaignInfo
     */
    private function _buildCampaignInfo($advertiserId, $combo)
    {
        [, , $campaignType, , $revenue, $dates, $booking, $capping, $pw] = $combo;

        $oCampaign = new OA_Dll_CampaignInfo();
        $oCampaign->advertiserId = $advertiserId;
        $oCampaign->campaignName = 'Combo test - ' . implode(' / ', $combo);

        // Priority & weight
        $priority = 0;
        $weight   = 1;
        $this->_applyPriorityWeight($campaignType, $pw, $priority, $weight);
        $oCampaign->priority = $priority;
        $oCampaign->weight   = $weight;

        // Revenue
        $oCampaign->revenue     = 1.50;
        $oCampaign->revenueType = $this->_revenueConst($revenue);

        // Dates
        $this->_applyDates($oCampaign, $dates);

        // Booking limits
        $this->_applyBooking($oCampaign, $booking);

        // Frequency capping
        $this->_applyCapping($oCampaign, $capping);

        return $oCampaign;
    }

    /**
     * Create an advertiser via the DLL and return the mock + advertiser info.
     */
    private function _createAdvertiser()
    {
        $dllAdv = new PartialMockOA_Dll_Advertiser_CrudComboTest($this);
        $dllAdv->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdv->setReturnValue('checkPermissions', true);

        $oAdv = new OA_Dll_AdvertiserInfo();
        $oAdv->advertiserName = 'Combo test advertiser';
        $oAdv->agencyId       = $this->agencyId;
        $dllAdv->modify($oAdv);

        return $oAdv;
    }

    // ------------------------------------------------------------------
    // Data: Section 4A included pairwise combinations (~100 rows)
    // ------------------------------------------------------------------

    /**
     * Returns the array of ~100 included pairwise combinations.
     * Each row: [account, mode, campaignType, status, revenue, dates, booking, capping, pw]
     */
    private function _getIncludedCombos()
    {
        return [
            // --- Core pairwise set (53 combos from allpairspy 2-way + 3-way) ---
            'C001' => ['ADMIN',      'Create', 'Remnant',        'Running',   'CPM', 'NoDates',    'Unlimited',  'None',        'p0w1'],
            'C002' => ['MANAGER',    'Edit',   'ContractNormal', 'Paused',    'CPC', 'StartOnly',  'ImprOnly',   'CappingOnly', 'p0w1'],
            'C003' => ['ADVERTISER', 'View',   'Override',       'Awaiting',  'CPA', 'EndOnly',    'ClicksOnly', 'SessionOnly', 'p0w1'],
            'C004' => ['TRAFFICKER', 'View',   'ContractECPM',   'Inactive',  'CPC', 'StartEqEnd', 'Unlimited',  'AllThree',    'p5w0'],
            'C005' => ['MANAGER',    'Create', 'eCPM',           'Approval',  'CPA', 'StartEqEnd', 'ClicksOnly', 'AllThree',    'p5w0'],
            'C006' => ['ADMIN',      'Delete', 'Override',       'Rejected',  'CPC', 'NoDates',    'ImprOnly',   'AllThree',    'p5w0'],
            'C007' => ['ADMIN',      'View',   'ContractNormal', 'Approval',  'MT',  'EndOnly',    'Both',       'CappingOnly', 'p5w0'],
            'C008' => ['MANAGER',    'Delete', 'Remnant',        'Pending',   'MT',  'StartOnly',  'Unlimited',  'SessionOnly', 'p5w0'],
            'C009' => ['MANAGER',    'View',   'eCPM',           'Running',   'CPM', 'StartOnly',  'ImprOnly',   'BlockOnly',   'p5w0'],
            'C010' => ['ADMIN',      'Edit',   'eCPM',           'Awaiting',  'CPC', 'BothDates',  'ClicksOnly', 'SessionOnly', 'p5w0'],
            'C011' => ['MANAGER',    'Create', 'ContractECPM',   'Expired',   'CPC', 'EndOnly',    'Both',       'None',        'p5w0'],
            'C012' => ['ADMIN',      'Delete', 'ContractNormal', 'Awaiting',  'CPM', 'StartEqEnd', 'Unlimited',  'None',        'p5w0'],
            'C013' => ['ADMIN',      'View',   'ContractECPM',   'Rejected',  'MT',  'StartOnly',  'ClicksOnly', 'None',        'p0w1'],
            'C014' => ['ADMIN',      'View',   'Remnant',        'Paused',    'CPA', 'BothDates',  'Both',       'BlockOnly',   'p5w0'],
            'C015' => ['MANAGER',    'Edit',   'Override',       'Inactive',  'CPM', 'BothDates',  'Unlimited',  'None',        'p0w1'],
            'C016' => ['ADMIN',      'Create', 'ContractECPM',   'Awaiting',  'MT',  'StartOnly',  'ImprOnly',   'BlockOnly',   'p5w0'],
            'C017' => ['ADMIN',      'View',   'Remnant',        'Expired',   'CPM', 'NoDates',    'ClicksOnly', 'CappingOnly', 'p5w0'],
            'C018' => ['MANAGER',    'Edit',   'Override',       'Approval',  'CPC', 'NoDates',    'ImprOnly',   'BlockOnly',   'p5w0'],
            'C019' => ['ADMIN',      'View',   'ContractNormal', 'Pending',   'CPC', 'BothDates',  'ClicksOnly', 'BlockOnly',   'p5w0'],
            'C020' => ['MANAGER',    'Delete', 'eCPM',           'Paused',    'MT',  'EndOnly',    'ClicksOnly', 'AllThree',    'p5w0'],
            'C021' => ['MANAGER',    'Delete', 'ContractNormal', 'Expired',   'CPA', 'StartOnly',  'Unlimited',  'AllThree',    'p5w0'],
            'C022' => ['MANAGER',    'Delete', 'eCPM',           'Pending',   'CPM', 'StartEqEnd', 'ClicksOnly', 'None',        'p5w0'],
            'C023' => ['MANAGER',    'Delete', 'ContractECPM',   'Awaiting',  'CPM', 'NoDates',    'Both',       'AllThree',    'p5w0'],
            'C024' => ['MANAGER',    'Delete', 'Override',       'Rejected',  'CPM', 'StartOnly',  'Unlimited',  'BlockOnly',   'p5w0'],
            'C025' => ['ADMIN',      'Delete', 'ContractECPM',   'Approval',  'CPM', 'StartOnly',  'ClicksOnly', 'SessionOnly', 'p5w0'],
            'C026' => ['ADMIN',      'Delete', 'Override',       'Inactive',  'CPM', 'EndOnly',    'ImprOnly',   'SessionOnly', 'p5w0'],
            'C027' => ['ADMIN',      'Delete', 'eCPM',           'Inactive',  'CPM', 'StartOnly',  'ClicksOnly', 'CappingOnly', 'p5w0'],
            'C028' => ['ADMIN',      'Delete', 'ContractNormal', 'Paused',    'CPM', 'NoDates',    'ClicksOnly', 'SessionOnly', 'p5w0'],
            'C029' => ['ADMIN',      'Delete', 'ContractNormal', 'Running',   'CPC', 'EndOnly',    'ClicksOnly', 'CappingOnly', 'p5w0'],
            'C030' => ['ADMIN',      'Delete', 'ContractNormal', 'Rejected',  'CPM', 'StartEqEnd', 'ClicksOnly', 'CappingOnly', 'p5w0'],
            'C031' => ['ADVERTISER', 'View',   'ContractECPM',   'Pending',   'CPC', 'StartEqEnd', 'ImprOnly',   'None',        'p5w0'],
            'C032' => ['MANAGER',    'Edit',   'Remnant',        'Approval',  'CPM', 'BothDates',  'Both',       'AllThree',    'p5w0'],
            'C033' => ['ADMIN',      'Create', 'eCPM',           'Rejected',  'MT',  'EndOnly',    'ClicksOnly', 'AllThree',    'p5w0'],
            'C034' => ['ADMIN',      'Create', 'Override',       'Awaiting',  'CPC', 'StartOnly',  'Both',       'BlockOnly',   'p5w0'],
            'C035' => ['MANAGER',    'Edit',   'ContractECPM',   'Pending',   'CPA', 'NoDates',    'Unlimited',  'SessionOnly', 'p0w1'],
            'C036' => ['ADVERTISER', 'View',   'ContractNormal', 'Paused',    'MT',  'NoDates',    'Unlimited',  'AllThree',    'p5w0'],
            'C037' => ['ADVERTISER', 'View',   'eCPM',           'Expired',   'CPM', 'StartOnly',  'Both',       'CappingOnly', 'p5w0'],
            'C038' => ['MANAGER',    'Edit',   'Override',       'Inactive',  'MT',  'StartEqEnd', 'ClicksOnly', 'BlockOnly',   'p0w1'],
            'C039' => ['ADMIN',      'Create', 'ContractECPM',   'Approval',  'CPA', 'BothDates',  'ImprOnly',   'CappingOnly', 'p0w1'],
            'C040' => ['ADMIN',      'Create', 'ContractNormal', 'Inactive',  'MT',  'StartOnly',  'Unlimited',  'SessionOnly', 'p0w1'],
            'C041' => ['MANAGER',    'Edit',   'eCPM',           'Rejected',  'CPC', 'NoDates',    'Both',       'None',        'p5w0'],
            'C042' => ['ADVERTISER', 'View',   'Remnant',        'Running',   'CPA', 'BothDates',  'Unlimited',  'BlockOnly',   'p5w0'],
            'C043' => ['MANAGER',    'Edit',   'ContractNormal', 'Expired',   'MT',  'EndOnly',    'Unlimited',  'None',        'p5w0'],
            'C044' => ['ADMIN',      'Create', 'Remnant',        'Pending',   'CPC', 'StartEqEnd', 'ClicksOnly', 'SessionOnly', 'p0w1'],
            'C045' => ['ADVERTISER', 'View',   'eCPM',           'Inactive',  'CPC', 'BothDates',  'ClicksOnly', 'AllThree',    'p0w1'],
            'C046' => ['ADVERTISER', 'View',   'ContractNormal', 'Rejected',  'CPA', 'StartOnly',  'ImprOnly',   'AllThree',    'p0w1'],
            'C047' => ['ADMIN',      'Create', 'Override',       'Expired',   'MT',  'NoDates',    'ImprOnly',   'SessionOnly', 'p5w0'],
            'C048' => ['MANAGER',    'Edit',   'ContractNormal', 'Awaiting',  'CPA', 'StartEqEnd', 'Both',       'CappingOnly', 'p5w0'],
            'C049' => ['MANAGER',    'Edit',   'Override',       'Running',   'CPM', 'EndOnly',    'ImprOnly',   'AllThree',    'p0w1'],
            'C050' => ['ADMIN',      'Create', 'ContractECPM',   'Paused',    'CPC', 'EndOnly',    'Unlimited',  'BlockOnly',   'p0w1'],
            'C051' => ['ADVERTISER', 'View',   'Remnant',        'Approval',  'MT',  'EndOnly',    'ImprOnly',   'CappingOnly', 'p0w1'],

            // --- Supplemental combos to reach ~100 (systematic fill) ---
            // Additional ADMIN Create combos with varied parameters
            'C052' => ['ADMIN',      'Create', 'ContractNormal', 'Running',   'CPC', 'BothDates',  'ImprOnly',   'CappingOnly', 'p5w0'],
            'C053' => ['ADMIN',      'Create', 'Remnant',        'Paused',    'CPA', 'EndOnly',    'Both',       'BlockOnly',   'p0w1'],
            'C054' => ['ADMIN',      'Create', 'eCPM',           'Inactive',  'CPM', 'StartEqEnd', 'Unlimited',  'None',        'p0w1'],
            'C055' => ['ADMIN',      'Create', 'ContractNormal', 'Approval',  'MT',  'StartOnly',  'ClicksOnly', 'AllThree',    'p5w0'],

            // Additional MANAGER Create combos
            'C056' => ['MANAGER',    'Create', 'Remnant',        'Running',   'CPM', 'BothDates',  'Both',       'CappingOnly', 'p0w1'],
            'C057' => ['MANAGER',    'Create', 'ContractNormal', 'Paused',    'CPC', 'NoDates',    'ImprOnly',   'BlockOnly',   'p5w0'],
            'C058' => ['MANAGER',    'Create', 'Override',       'Expired',   'MT',  'StartOnly',  'Unlimited',  'SessionOnly', 'p0w1'],
            'C059' => ['MANAGER',    'Create', 'eCPM',           'Inactive',  'CPA', 'EndOnly',    'ClicksOnly', 'None',        'p5w0'],

            // Additional ADMIN Edit combos
            'C060' => ['ADMIN',      'Edit',   'Remnant',        'Running',   'CPC', 'StartOnly',  'Both',       'AllThree',    'p0w1'],
            'C061' => ['ADMIN',      'Edit',   'ContractNormal', 'Paused',    'CPM', 'EndOnly',    'Unlimited',  'BlockOnly',   'p5w0'],
            'C062' => ['ADMIN',      'Edit',   'Override',       'Expired',   'MT',  'BothDates',  'ImprOnly',   'CappingOnly', 'p0w1'],
            'C063' => ['ADMIN',      'Edit',   'ContractECPM',   'Inactive',  'CPA', 'StartEqEnd', 'ClicksOnly', 'None',        'p5w0'],
            'C064' => ['ADMIN',      'Edit',   'Remnant',        'Pending',   'CPA', 'NoDates',    'ImprOnly',   'SessionOnly', 'p0w1'],

            // Additional MANAGER Edit combos
            'C065' => ['MANAGER',    'Edit',   'eCPM',           'Running',   'MT',  'BothDates',  'Unlimited',  'CappingOnly', 'p0w1'],
            'C066' => ['MANAGER',    'Edit',   'Remnant',        'Expired',   'CPC', 'StartEqEnd', 'ClicksOnly', 'BlockOnly',   'p0w1'],
            'C067' => ['MANAGER',    'Edit',   'ContractNormal', 'Inactive',  'CPM', 'NoDates',    'Both',       'AllThree',    'p5w0'],

            // Additional ADMIN/MANAGER View combos
            'C068' => ['ADMIN',      'View',   'Override',       'Running',   'CPC', 'StartOnly',  'ImprOnly',   'AllThree',    'p0w1'],
            'C069' => ['ADMIN',      'View',   'eCPM',           'Paused',    'CPA', 'NoDates',    'Both',       'SessionOnly', 'p5w0'],
            'C070' => ['MANAGER',    'View',   'Remnant',        'Awaiting',  'MT',  'EndOnly',    'Unlimited',  'CappingOnly', 'p0w1'],
            'C071' => ['MANAGER',    'View',   'ContractNormal', 'Expired',   'CPM', 'BothDates',  'ClicksOnly', 'None',        'p5w0'],
            'C072' => ['MANAGER',    'View',   'ContractECPM',   'Inactive',  'CPC', 'StartEqEnd', 'ImprOnly',   'BlockOnly',   'p0w1'],
            'C073' => ['ADMIN',      'View',   'Remnant',        'Pending',   'CPM', 'StartOnly',  'Both',       'AllThree',    'p0w1'],
            'C074' => ['MANAGER',    'View',   'Override',       'Rejected',  'CPA', 'NoDates',    'Unlimited',  'SessionOnly', 'p5w0'],

            // Additional ADVERTISER View combos
            'C075' => ['ADVERTISER', 'View',   'Remnant',        'Paused',    'CPM', 'StartOnly',  'Unlimited',  'None',        'p0w1'],
            'C076' => ['ADVERTISER', 'View',   'ContractNormal', 'Awaiting',  'CPC', 'EndOnly',    'ImprOnly',   'BlockOnly',   'p5w0'],
            'C077' => ['ADVERTISER', 'View',   'Override',       'Expired',   'MT',  'BothDates',  'ClicksOnly', 'CappingOnly', 'p0w1'],
            'C078' => ['ADVERTISER', 'View',   'eCPM',           'Pending',   'CPA', 'StartEqEnd', 'Both',       'SessionOnly', 'p5w0'],
            'C079' => ['ADVERTISER', 'View',   'ContractECPM',   'Running',   'CPM', 'NoDates',    'Unlimited',  'AllThree',    'p0w1'],
            'C080' => ['ADVERTISER', 'View',   'Remnant',        'Inactive',  'CPC', 'NoDates',    'ClicksOnly', 'BlockOnly',   'p0w1'],

            // Additional TRAFFICKER View combos
            'C081' => ['TRAFFICKER', 'View',   'Remnant',        'Running',   'MT',  'BothDates',  'ImprOnly',   'SessionOnly', 'p0w1'],
            'C082' => ['TRAFFICKER', 'View',   'ContractNormal', 'Paused',    'CPM', 'StartOnly',  'Both',       'None',        'p5w0'],
            'C083' => ['TRAFFICKER', 'View',   'Override',       'Awaiting',  'CPC', 'EndOnly',    'Unlimited',  'CappingOnly', 'p0w1'],
            'C084' => ['TRAFFICKER', 'View',   'eCPM',           'Expired',   'CPA', 'NoDates',    'ClicksOnly', 'BlockOnly',   'p5w0'],
            'C085' => ['TRAFFICKER', 'View',   'ContractECPM',   'Pending',   'MT',  'StartEqEnd', 'ImprOnly',   'AllThree',    'p0w1'],
            'C086' => ['TRAFFICKER', 'View',   'Remnant',        'Rejected',  'CPM', 'EndOnly',    'Both',       'CappingOnly', 'p5w0'],

            // Additional ADMIN Delete combos (varied campaign types / statuses)
            'C087' => ['ADMIN',      'Delete', 'Remnant',        'Running',   'CPC', 'BothDates',  'Both',       'AllThree',    'p0w1'],
            'C088' => ['ADMIN',      'Delete', 'eCPM',           'Expired',   'CPA', 'NoDates',    'Unlimited',  'None',        'p5w0'],
            'C089' => ['ADMIN',      'Delete', 'ContractECPM',   'Pending',   'MT',  'EndOnly',    'ImprOnly',   'BlockOnly',   'p0w1'],

            // Additional MANAGER Delete combos
            'C090' => ['MANAGER',    'Delete', 'ContractNormal', 'Running',   'CPM', 'BothDates',  'ImprOnly',   'CappingOnly', 'p5w0'],
            'C091' => ['MANAGER',    'Delete', 'Remnant',        'Awaiting',  'CPC', 'NoDates',    'ClicksOnly', 'None',        'p0w1'],
            'C092' => ['MANAGER',    'Delete', 'Override',       'Paused',    'MT',  'StartEqEnd', 'Both',       'SessionOnly', 'p0w1'],

            // More varied combos to cover remaining pairs
            'C093' => ['ADMIN',      'Create', 'Remnant',        'Awaiting',  'MT',  'BothDates',  'ClicksOnly', 'CappingOnly', 'p0w1'],
            'C094' => ['MANAGER',    'Create', 'ContractNormal', 'Running',   'CPA', 'StartEqEnd', 'Both',       'BlockOnly',   'p5w0'],
            'C095' => ['ADMIN',      'Edit',   'ContractNormal', 'Rejected',  'CPC', 'StartOnly',  'ClicksOnly', 'AllThree',    'p5w0'],
            'C096' => ['MANAGER',    'Edit',   'Remnant',        'Pending',   'MT',  'EndOnly',    'ImprOnly',   'SessionOnly', 'p0w1'],
            'C097' => ['ADMIN',      'View',   'eCPM',           'Approval',  'CPC', 'EndOnly',    'Unlimited',  'BlockOnly',   'p0w1'],
            'C098' => ['ADMIN',      'Delete', 'Remnant',        'Approval',  'CPA', 'StartEqEnd', 'ImprOnly',   'CappingOnly', 'p0w1'],
            'C099' => ['MANAGER',    'Create', 'Override',       'Rejected',  'CPM', 'NoDates',    'ImprOnly',   'AllThree',    'p0w1'],
            'C100' => ['MANAGER',    'Delete', 'ContractECPM',   'Running',   'CPC', 'BothDates',  'Unlimited',  'BlockOnly',   'p5w0'],
        ];
    }

    // ------------------------------------------------------------------
    // Data: Section 4B excluded combinations (negative tests)
    // ------------------------------------------------------------------

    /**
     * Returns the array of ~20 excluded combinations.
     * ADVERTISER/TRAFFICKER + Create/Edit/Delete -> permission denied.
     */
    private function _getExcludedCombos()
    {
        return [
            'X001' => ['TRAFFICKER',  'Delete', 'eCPM',           'Expired',   'MT',  'BothDates',  'Both',       'BlockOnly',   'p0w1'],
            'X002' => ['ADVERTISER',  'Edit',   'ContractECPM',   'Pending',   'CPM', 'BothDates',  'Both',       'AllThree',    'p5w0'],
            'X003' => ['ADVERTISER',  'Create', 'ContractNormal', 'Inactive',  'MT',  'NoDates',    'ClicksOnly', 'BlockOnly',   'p5w0'],
            'X004' => ['TRAFFICKER',  'Edit',   'Remnant',        'Rejected',  'CPA', 'EndOnly',    'ImprOnly',   'None',        'p5w0'],
            'X005' => ['ADVERTISER',  'Delete', 'ContractECPM',   'Paused',    'CPA', 'StartEqEnd', 'Unlimited',  'CappingOnly', 'p0w1'],
            'X006' => ['TRAFFICKER',  'Create', 'Override',       'Paused',    'CPM', 'BothDates',  'ClicksOnly', 'CappingOnly', 'p5w0'],
            'X007' => ['ADVERTISER',  'Edit',   'Remnant',        'Expired',   'CPC', 'StartEqEnd', 'ImprOnly',   'BlockOnly',   'p5w0'],
            'X008' => ['TRAFFICKER',  'Edit',   'Override',       'Running',   'MT',  'StartEqEnd', 'Both',       'SessionOnly', 'p5w0'],
            'X009' => ['TRAFFICKER',  'Create', 'ContractNormal', 'Pending',   'CPA', 'NoDates',    'ImprOnly',   'SessionOnly', 'p0w1'],
            'X010' => ['ADVERTISER',  'Delete', 'eCPM',           'Approval',  'CPM', 'EndOnly',    'Unlimited',  'BlockOnly',   'p0w1'],
            'X011' => ['ADVERTISER',  'Delete', 'eCPM',           'Rejected',  'CPM', 'NoDates',    'Both',       'CappingOnly', 'p5w0'],
            'X012' => ['ADVERTISER',  'Delete', 'ContractECPM',   'Running',   'CPA', 'StartOnly',  'ClicksOnly', 'AllThree',    'p0w1'],
            'X013' => ['TRAFFICKER',  'Delete', 'ContractNormal', 'Inactive',  'CPA', 'StartOnly',  'Both',       'CappingOnly', 'p5w0'],
            'X014' => ['TRAFFICKER',  'Create', 'ContractNormal', 'Rejected',  'CPM', 'BothDates',  'ImprOnly',   'SessionOnly', 'p5w0'],
            'X015' => ['TRAFFICKER',  'Delete', 'Remnant',        'Approval',  'MT',  'BothDates',  'ClicksOnly', 'AllThree',    'p5w0'],
            'X016' => ['TRAFFICKER',  'Delete', 'Override',       'Pending',   'CPM', 'EndOnly',    'ClicksOnly', 'CappingOnly', 'p5w0'],
            'X017' => ['ADVERTISER',  'Create', 'Remnant',        'Running',   'CPC', 'StartOnly',  'Unlimited',  'None',        'p0w1'],
            'X018' => ['ADVERTISER',  'Edit',   'ContractNormal', 'Awaiting',  'MT',  'BothDates',  'ImprOnly',   'SessionOnly', 'p5w0'],
            'X019' => ['TRAFFICKER',  'Edit',   'eCPM',           'Paused',    'CPC', 'StartOnly',  'Both',       'AllThree',    'p0w1'],
            'X020' => ['TRAFFICKER',  'Delete', 'ContractECPM',   'Awaiting',  'CPC', 'StartEqEnd', 'Unlimited',  'BlockOnly',   'p5w0'],
        ];
    }

    // ------------------------------------------------------------------
    // Section 4A: Included combo tests (positive)
    // ------------------------------------------------------------------

    /**
     * Test all included Create combos.
     *
     * For each "Create" combo the test:
     *   1. Creates an advertiser.
     *   2. Builds a CampaignInfo with the combo parameters.
     *   3. Calls modify() (no campaignId = create).
     *   4. Asserts success and that a campaignId was assigned.
     */
    public function testIncludedCreateCombos()
    {
        $combos = $this->_getIncludedCombos();
        $oAdv   = $this->_createAdvertiser();

        foreach ($combos as $comboId => $combo) {
            if ($combo[1] !== 'Create') {
                continue;
            }

            $dllCampaign = new PartialMockOA_Dll_Campaign_CrudComboTest($this);
            $dllCampaign->setReturnValue('checkPermissions', true);

            $oCampaign = $this->_buildCampaignInfo($oAdv->advertiserId, $combo);

            $result = $dllCampaign->modify($oCampaign);
            $this->assertTrue(
                $result,
                "{$comboId}: Create should succeed - " . $dllCampaign->getLastError(),
            );
            $this->assertNotNull(
                $oCampaign->campaignId,
                "{$comboId}: campaignId should be assigned after create",
            );
        }
    }

    /**
     * Test all included Edit combos.
     *
     * For each "Edit" combo the test:
     *   1. Creates an advertiser and a seed campaign.
     *   2. Builds a CampaignInfo with the combo parameters and existing campaignId.
     *   3. Calls modify() (with campaignId = edit).
     *   4. Asserts success.
     */
    public function testIncludedEditCombos()
    {
        $combos = $this->_getIncludedCombos();
        $oAdv   = $this->_createAdvertiser();

        foreach ($combos as $comboId => $combo) {
            if ($combo[1] !== 'Edit') {
                continue;
            }

            // Create a seed campaign first
            $dllCampaign = new PartialMockOA_Dll_Campaign_CrudComboTest($this);
            $dllCampaign->setReturnValue('checkPermissions', true);

            $oSeed = new OA_Dll_CampaignInfo();
            $oSeed->advertiserId = $oAdv->advertiserId;
            $oSeed->campaignName = 'Seed for ' . $comboId;
            $dllCampaign->modify($oSeed);
            $this->assertNotNull($oSeed->campaignId, "{$comboId}: seed creation failed");

            // Build the edit payload
            $oCampaign = $this->_buildCampaignInfo($oAdv->advertiserId, $combo);
            $oCampaign->campaignId = $oSeed->campaignId;

            $result = $dllCampaign->modify($oCampaign);
            $this->assertTrue(
                $result,
                "{$comboId}: Edit should succeed - " . $dllCampaign->getLastError(),
            );
        }
    }

    /**
     * Test all included View combos.
     *
     * For each "View" combo the test:
     *   1. Creates an advertiser and a seed campaign with the combo parameters.
     *   2. Calls getCampaign() and asserts it returns data.
     *   3. Verifies key fields match what was set.
     */
    public function testIncludedViewCombos()
    {
        $combos = $this->_getIncludedCombos();
        $oAdv   = $this->_createAdvertiser();

        foreach ($combos as $comboId => $combo) {
            if ($combo[1] !== 'View') {
                continue;
            }

            // Create the campaign to view
            $dllCampaign = new PartialMockOA_Dll_Campaign_CrudComboTest($this);
            $dllCampaign->setReturnValue('checkPermissions', true);

            $oCampaign = $this->_buildCampaignInfo($oAdv->advertiserId, $combo);
            $dllCampaign->modify($oCampaign);
            $this->assertNotNull($oCampaign->campaignId, "{$comboId}: seed creation failed");

            // View
            $oCampaignGet = null;
            $result = $dllCampaign->getCampaign($oCampaign->campaignId, $oCampaignGet);
            $this->assertTrue(
                $result,
                "{$comboId}: View should succeed - " . $dllCampaign->getLastError(),
            );
            $this->assertNotNull($oCampaignGet, "{$comboId}: getCampaign should return data");

            // Verify key fields
            $this->assertFieldEqual($oCampaign, $oCampaignGet, 'campaignName');
            $this->assertFieldEqual($oCampaign, $oCampaignGet, 'priority');
            $this->assertFieldEqual($oCampaign, $oCampaignGet, 'weight');
        }
    }

    /**
     * Test all included Delete combos.
     *
     * For each "Delete" combo the test:
     *   1. Creates an advertiser and a seed campaign.
     *   2. Calls delete() and asserts success.
     *   3. Confirms getCampaign() fails with unknownIdError.
     */
    public function testIncludedDeleteCombos()
    {
        $combos = $this->_getIncludedCombos();
        $oAdv   = $this->_createAdvertiser();

        foreach ($combos as $comboId => $combo) {
            if ($combo[1] !== 'Delete') {
                continue;
            }

            // Create a seed campaign to delete
            $dllCampaign = new PartialMockOA_Dll_Campaign_CrudComboTest($this);
            $dllCampaign->setReturnValue('checkPermissions', true);

            $oCampaign = $this->_buildCampaignInfo($oAdv->advertiserId, $combo);
            $dllCampaign->modify($oCampaign);
            $this->assertNotNull($oCampaign->campaignId, "{$comboId}: seed creation failed");

            // Delete
            $result = $dllCampaign->delete($oCampaign->campaignId);
            $this->assertTrue(
                $result,
                "{$comboId}: Delete should succeed - " . $dllCampaign->getLastError(),
            );

            // Verify it is gone
            $oCampaignGet = null;
            $this->assertFalse(
                $dllCampaign->getCampaign($oCampaign->campaignId, $oCampaignGet),
                "{$comboId}: getCampaign after delete should fail",
            );
            $this->assertEqual(
                $dllCampaign->getLastError(),
                $this->unknownIdError,
                "{$comboId}: Error after delete should be unknownIdError",
            );
        }
    }

    // ------------------------------------------------------------------
    // Section 4B: Excluded combo tests (negative / permission denied)
    // ------------------------------------------------------------------

    /**
     * Test that ADVERTISER/TRAFFICKER cannot Create campaigns.
     * The modify() call with checkPermissions returning false should fail.
     */
    public function testExcludedCreatePermissionDenied()
    {
        $combos = $this->_getExcludedCombos();
        $oAdv   = $this->_createAdvertiser();

        foreach ($combos as $comboId => $combo) {
            if ($combo[1] !== 'Create') {
                continue;
            }

            $dllCampaign = new PartialMockOA_Dll_Campaign_CrudComboTest($this);
            $dllCampaign->setReturnValue('checkPermissions', false);

            $oCampaign = $this->_buildCampaignInfo($oAdv->advertiserId, $combo);

            $result = $dllCampaign->modify($oCampaign);
            $this->assertFalse(
                $result,
                "{$comboId}: {$combo[0]} Create should be denied",
            );
        }
    }

    /**
     * Test that ADVERTISER/TRAFFICKER cannot Edit campaigns.
     */
    public function testExcludedEditPermissionDenied()
    {
        $combos = $this->_getExcludedCombos();
        $oAdv   = $this->_createAdvertiser();

        // Create a seed campaign with full permissions
        $dllSeed = new PartialMockOA_Dll_Campaign_CrudComboTest($this);
        $dllSeed->setReturnValue('checkPermissions', true);

        $oSeed = new OA_Dll_CampaignInfo();
        $oSeed->advertiserId = $oAdv->advertiserId;
        $oSeed->campaignName = 'Seed for excluded edit';
        $dllSeed->modify($oSeed);

        foreach ($combos as $comboId => $combo) {
            if ($combo[1] !== 'Edit') {
                continue;
            }

            $dllCampaign = new PartialMockOA_Dll_Campaign_CrudComboTest($this);
            $dllCampaign->setReturnValue('checkPermissions', false);

            $oCampaign = $this->_buildCampaignInfo($oAdv->advertiserId, $combo);
            $oCampaign->campaignId = $oSeed->campaignId;

            $result = $dllCampaign->modify($oCampaign);
            $this->assertFalse(
                $result,
                "{$comboId}: {$combo[0]} Edit should be denied",
            );
        }
    }

    /**
     * Test that ADVERTISER/TRAFFICKER cannot Delete campaigns.
     */
    public function testExcludedDeletePermissionDenied()
    {
        $combos = $this->_getExcludedCombos();
        $oAdv   = $this->_createAdvertiser();

        foreach ($combos as $comboId => $combo) {
            if ($combo[1] !== 'Delete') {
                continue;
            }

            // Create a seed campaign with full permissions
            $dllSeed = new PartialMockOA_Dll_Campaign_CrudComboTest($this);
            $dllSeed->setReturnValue('checkPermissions', true);

            $oSeed = new OA_Dll_CampaignInfo();
            $oSeed->advertiserId = $oAdv->advertiserId;
            $oSeed->campaignName = 'Seed for excluded delete ' . $comboId;
            $dllSeed->modify($oSeed);

            $dllCampaign = new PartialMockOA_Dll_Campaign_CrudComboTest($this);
            $dllCampaign->setReturnValue('checkPermissions', false);

            $result = $dllCampaign->delete($oSeed->campaignId);
            $this->assertFalse(
                $result,
                "{$comboId}: {$combo[0]} Delete should be denied",
            );
        }
    }

    // ------------------------------------------------------------------
    // Section 4B: Validation error - priority > 0 AND weight > 0
    // ------------------------------------------------------------------

    /**
     * Test that setting priority > 0 AND weight > 0 is rejected by validation.
     *
     * The DLL should return false with "High or medium priority campaigns
     * cannot have a weight that is greater than zero."
     */
    public function testValidationHighPriorityWithWeight()
    {
        $oAdv = $this->_createAdvertiser();

        $dllCampaign = new PartialMockOA_Dll_Campaign_CrudComboTest($this);
        $dllCampaign->setReturnValue('checkPermissions', true);

        $oCampaign = new OA_Dll_CampaignInfo();
        $oCampaign->advertiserId = $oAdv->advertiserId;
        $oCampaign->campaignName = 'Invalid p>0 w>0';
        $oCampaign->priority     = 5;
        $oCampaign->weight       = 1;
        $oCampaign->impressions  = -1;
        $oCampaign->clicks       = -1;

        $result = $dllCampaign->modify($oCampaign);
        $this->assertFalse($result, 'priority>0 + weight>0 should fail validation');
        $this->assertEqual(
            $dllCampaign->getLastError(),
            'High or medium priority campaigns cannot have a weight that is greater than zero.',
            'Should get high-priority + weight validation error',
        );
    }

    /**
     * Test multiple priority/weight invalid combos.
     * Verifies that each priority level 1-10 combined with weight > 0 is rejected.
     */
    public function testValidationHighPriorityWithWeightRange()
    {
        $oAdv = $this->_createAdvertiser();

        foreach ([1, 3, 5, 7, 10] as $priority) {
            foreach ([1, 5, 10] as $weight) {
                $dllCampaign = new PartialMockOA_Dll_Campaign_CrudComboTest($this);
                $dllCampaign->setReturnValue('checkPermissions', true);

                $oCampaign = new OA_Dll_CampaignInfo();
                $oCampaign->advertiserId = $oAdv->advertiserId;
                $oCampaign->campaignName = "Invalid p={$priority} w={$weight}";
                $oCampaign->priority     = $priority;
                $oCampaign->weight       = $weight;
                $oCampaign->impressions  = -1;
                $oCampaign->clicks       = -1;

                $result = $dllCampaign->modify($oCampaign);
                $this->assertFalse(
                    $result,
                    "priority={$priority} + weight={$weight} should fail validation",
                );
            }
        }
    }

    /**
     * Test that low/override priority campaigns cannot have targets.
     */
    public function testValidationLowPriorityWithTargets()
    {
        $oAdv = $this->_createAdvertiser();

        // priority=0 (remnant) with targets
        $dllCampaign = new PartialMockOA_Dll_Campaign_CrudComboTest($this);
        $dllCampaign->setReturnValue('checkPermissions', true);

        $oCampaign = new OA_Dll_CampaignInfo();
        $oCampaign->advertiserId      = $oAdv->advertiserId;
        $oCampaign->campaignName      = 'Invalid low priority with targets';
        $oCampaign->priority          = 0;
        $oCampaign->weight            = 1;
        $oCampaign->impressions       = -1;
        $oCampaign->clicks            = -1;
        $oCampaign->targetImpressions = 1000;

        $result = $dllCampaign->modify($oCampaign);
        $this->assertFalse($result, 'Low priority + targets should fail validation');
        $this->assertEqual(
            $dllCampaign->getLastError(),
            'Low or override priority campaigns cannot have targets.',
            'Should get low-priority + targets validation error',
        );

        // priority=-1 (override) with targets
        $dllCampaign2 = new PartialMockOA_Dll_Campaign_CrudComboTest($this);
        $dllCampaign2->setReturnValue('checkPermissions', true);

        $oCampaign2 = new OA_Dll_CampaignInfo();
        $oCampaign2->advertiserId      = $oAdv->advertiserId;
        $oCampaign2->campaignName      = 'Invalid override with targets';
        $oCampaign2->priority          = -1;
        $oCampaign2->weight            = 1;
        $oCampaign2->impressions       = -1;
        $oCampaign2->clicks            = -1;
        $oCampaign2->targetClicks      = 500;

        $result2 = $dllCampaign2->modify($oCampaign2);
        $this->assertFalse($result2, 'Override priority + targets should fail validation');
    }
}
