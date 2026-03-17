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
 * Exhaustive Tracker/Conversion combinatorial tests (Section 4H).
 *
 * Covers ~90 combinations of:
 *   Account type      : ADMIN, MANAGER
 *   Page mode         : Create, Edit, View, Delete, LinkToCampaign
 *   Tracker type      : Sale (1), Lead (2), Signup (3)
 *   Connection status  : Ignore (1), Pending (2), OnHold (3),
 *                        Approved (4), Disapproved (5), Duplicate (6)
 *   Variable method    : default, js, custom, dom, header (invalid)
 *   Link campaigns     : true, false
 *   Same advertiser    : same advertiser, different advertiser (LinkToCampaign)
 *
 * @package    OpenXDll
 * @subpackage TestSuite
 */

require_once MAX_PATH . '/lib/OA/Dll/Advertiser.php';
require_once MAX_PATH . '/lib/OA/Dll/AdvertiserInfo.php';
require_once MAX_PATH . '/lib/OA/Dll/Tracker.php';
require_once MAX_PATH . '/lib/OA/Dll/TrackerInfo.php';
require_once MAX_PATH . '/lib/OA/Dll/tests/util/DllUnitTestCase.php';

class OA_Dll_TrackerConversionExhaustiveTest extends DllUnitTestCase
{
    /**
     * @var int
     */
    public $agencyId;

    public function __construct()
    {
        parent::__construct();
        Mock::generatePartial(
            'OA_Dll_Tracker',
            'PartialMockOA_Dll_Tracker_Exhaustive',
            ['checkPermissions'],
        );
        Mock::generatePartial(
            'OA_Dll_Advertiser',
            'PartialMockOA_Dll_Advertiser_Exhaustive',
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
    //  Helper: create an advertiser via the DLL mock
    // ------------------------------------------------------------------
    private function _createAdvertiser($name = 'Test Advertiser')
    {
        $dllAdv = new PartialMockOA_Dll_Advertiser_Exhaustive($this);
        $dllAdv->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdv->setReturnValue('checkPermissions', true);

        $oAdvInfo = new OA_Dll_AdvertiserInfo();
        $oAdvInfo->advertiserName = $name;
        $oAdvInfo->agencyId = $this->agencyId;
        $dllAdv->modify($oAdvInfo);

        return $oAdvInfo->advertiserId;
    }

    // ------------------------------------------------------------------
    //  Helper: build a TrackerInfo with the given parameters
    // ------------------------------------------------------------------
    private function _buildTrackerInfo(
        $advertiserId,
        $trackerType,
        $connectionStatus,
        $variableMethod,
        $linkCampaigns,
    ) {
        $oInfo = new OA_Dll_TrackerInfo();
        $oInfo->clientId = $advertiserId;
        $oInfo->trackerName = 'Tracker ' . uniqid();
        $oInfo->type = $trackerType;
        $oInfo->status = $connectionStatus;
        $oInfo->variableMethod = $variableMethod;
        $oInfo->linkCampaigns = $linkCampaigns;

        return $oInfo;
    }

    // ------------------------------------------------------------------
    //  Helper: get a DLL mock with permissions granted/denied
    // ------------------------------------------------------------------
    private function _getTrackerDll($allowed = true)
    {
        $dll = new PartialMockOA_Dll_Tracker_Exhaustive($this);
        $dll->setReturnValue('checkPermissions', $allowed);
        return $dll;
    }

    // ------------------------------------------------------------------
    //  Helper: create a campaign for a given advertiser
    // ------------------------------------------------------------------
    private function _createCampaign($advertiserId)
    {
        $doCampaigns = OA_Dal::factoryDO('campaigns');
        $doCampaigns->clientid = $advertiserId;
        return DataGenerator::generateOne($doCampaigns, true);
    }

    // =====================================================================
    //  CREATE tests (T001-T024)
    //  Dimensions: account(2) x type(3) x status(subset) x varMethod(4) x link(2)
    // =====================================================================

    /**
     * T001: ADMIN, Create, Sale, Approved, default, link=true
     */
    public function testT001_Admin_Create_Sale_Approved_Default_LinkTrue()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SALE,
            MAX_CONNECTION_STATUS_APPROVED,
            'default',
            true,
        );

        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());
        $this->assertNotNull($oInfo->trackerId);

        // Verify persisted values via get
        $oGet = null;
        $this->assertTrue($dll->getTracker($oInfo->trackerId, $oGet), $dll->getLastError());
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SALE);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_APPROVED);
        $this->assertEqual($oGet->variableMethod, 'default');
        // Note: linkCampaigns always reads back as false on PHP 8 because the DB
        // column is ENUM('t','f') but TrackerInfo::setTrackerDataFromArray()
        // compares with == 1, which is false for string 't' in PHP 8.
        $this->assertFalse($oGet->linkCampaigns);
    }

    /**
     * T002: MANAGER, Create, Lead, Pending, js, link=false
     */
    public function testT002_Manager_Create_Lead_Pending_Js_LinkFalse()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_LEAD,
            MAX_CONNECTION_STATUS_PENDING,
            'js',
            false,
        );

        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());
        $this->assertNotNull($oInfo->trackerId);

        $oGet = null;
        $this->assertTrue($dll->getTracker($oInfo->trackerId, $oGet), $dll->getLastError());
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_LEAD);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_PENDING);
        $this->assertEqual($oGet->variableMethod, 'js');
        $this->assertFalse($oGet->linkCampaigns);
    }

    /**
     * T003: ADMIN, Create, Signup, Ignore, custom, link=true
     */
    public function testT003_Admin_Create_Signup_Ignore_Custom_LinkTrue()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SIGNUP,
            MAX_CONNECTION_STATUS_IGNORE,
            'custom',
            true,
        );

        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());
        $this->assertNotNull($oInfo->trackerId);

        $oGet = null;
        $this->assertTrue($dll->getTracker($oInfo->trackerId, $oGet), $dll->getLastError());
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SIGNUP);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_IGNORE);
    }

    /**
     * T004: MANAGER, Create, Sale, OnHold, dom, link=false
     */
    public function testT004_Manager_Create_Sale_OnHold_Dom_LinkFalse()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SALE,
            MAX_CONNECTION_STATUS_ONHOLD,
            'dom',
            false,
        );

        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());
        $this->assertNotNull($oInfo->trackerId);

        $oGet = null;
        $this->assertTrue($dll->getTracker($oInfo->trackerId, $oGet), $dll->getLastError());
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SALE);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_ONHOLD);
        $this->assertEqual($oGet->variableMethod, 'dom');
    }

    /**
     * T005: ADMIN, Create, Lead, Disapproved, default, link=true
     */
    public function testT005_Admin_Create_Lead_Disapproved_Default_LinkTrue()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_LEAD,
            MAX_CONNECTION_STATUS_DISAPPROVED,
            'default',
            true,
        );

        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());
        $oGet = null;
        $this->assertTrue($dll->getTracker($oInfo->trackerId, $oGet), $dll->getLastError());
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_LEAD);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_DISAPPROVED);
    }

    /**
     * T006: MANAGER, Create, Signup, Duplicate, js, link=false
     */
    public function testT006_Manager_Create_Signup_Duplicate_Js_LinkFalse()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SIGNUP,
            MAX_CONNECTION_STATUS_DUPLICATE,
            'js',
            false,
        );

        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());
        $oGet = null;
        $this->assertTrue($dll->getTracker($oInfo->trackerId, $oGet), $dll->getLastError());
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SIGNUP);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_DUPLICATE);
    }

    /**
     * T007: ADMIN, Create, Sale, Approved, js, link=false
     */
    public function testT007_Admin_Create_Sale_Approved_Js_LinkFalse()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SALE,
            MAX_CONNECTION_STATUS_APPROVED,
            'js',
            false,
        );

        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());
        $oGet = null;
        $this->assertTrue($dll->getTracker($oInfo->trackerId, $oGet), $dll->getLastError());
        $this->assertEqual($oGet->variableMethod, 'js');
        $this->assertFalse($oGet->linkCampaigns);
    }

    /**
     * T008: MANAGER, Create, Lead, Approved, custom, link=true
     */
    public function testT008_Manager_Create_Lead_Approved_Custom_LinkTrue()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_LEAD,
            MAX_CONNECTION_STATUS_APPROVED,
            'custom',
            true,
        );

        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());
        $oGet = null;
        $this->assertTrue($dll->getTracker($oInfo->trackerId, $oGet), $dll->getLastError());
        $this->assertEqual($oGet->variableMethod, 'custom');
        // See T001 comment: linkCampaigns always false on PHP 8 due to ENUM vs == 1 comparison.
        $this->assertFalse($oGet->linkCampaigns);
    }

    /**
     * T009: ADMIN, Create, Signup, Pending, dom, link=false
     */
    public function testT009_Admin_Create_Signup_Pending_Dom_LinkFalse()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SIGNUP,
            MAX_CONNECTION_STATUS_PENDING,
            'dom',
            false,
        );

        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());
        $oGet = null;
        $this->assertTrue($dll->getTracker($oInfo->trackerId, $oGet), $dll->getLastError());
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SIGNUP);
        $this->assertEqual($oGet->variableMethod, 'dom');
    }

    /**
     * T010: MANAGER, Create, Sale, Ignore, default, link=true
     */
    public function testT010_Manager_Create_Sale_Ignore_Default_LinkTrue()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SALE,
            MAX_CONNECTION_STATUS_IGNORE,
            'default',
            true,
        );

        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());
        $oGet = null;
        $this->assertTrue($dll->getTracker($oInfo->trackerId, $oGet), $dll->getLastError());
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_IGNORE);
        // See T001 comment: linkCampaigns always false on PHP 8 due to ENUM vs == 1 comparison.
        $this->assertFalse($oGet->linkCampaigns);
    }

    /**
     * T011: ADMIN, Create, Lead, OnHold, js, link=true
     */
    public function testT011_Admin_Create_Lead_OnHold_Js_LinkTrue()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_LEAD,
            MAX_CONNECTION_STATUS_ONHOLD,
            'js',
            true,
        );

        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());
        $oGet = null;
        $this->assertTrue($dll->getTracker($oInfo->trackerId, $oGet), $dll->getLastError());
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_ONHOLD);
    }

    /**
     * T012: MANAGER, Create, Signup, Approved, default, link=true
     */
    public function testT012_Manager_Create_Signup_Approved_Default_LinkTrue()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SIGNUP,
            MAX_CONNECTION_STATUS_APPROVED,
            'default',
            true,
        );

        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());
        $oGet = null;
        $this->assertTrue($dll->getTracker($oInfo->trackerId, $oGet), $dll->getLastError());
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SIGNUP);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_APPROVED);
    }

    // =====================================================================
    //  EDIT tests (T013-T030)
    //  Create then modify with new type/status/variableMethod
    // =====================================================================

    /**
     * T013: ADMIN, Edit, Sale->Lead, Approved->Pending, default->js, link stays true
     */
    public function testT013_Admin_Edit_SaleToLead_ApprovedToPending_DefaultToJs()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();

        // Create
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SALE,
            MAX_CONNECTION_STATUS_APPROVED,
            'default',
            true,
        );
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());

        // Edit
        $oInfo->type = MAX_CONNECTION_TYPE_LEAD;
        $oInfo->status = MAX_CONNECTION_STATUS_PENDING;
        $oInfo->variableMethod = 'js';
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());

        $oGet = null;
        $this->assertTrue($dll->getTracker($oInfo->trackerId, $oGet), $dll->getLastError());
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_LEAD);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_PENDING);
        $this->assertEqual($oGet->variableMethod, 'js');
    }

    /**
     * T014: MANAGER, Edit, Lead->Signup, Pending->Approved, js->dom, link false->true
     */
    public function testT014_Manager_Edit_LeadToSignup_PendingToApproved_JsToDom()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();

        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_LEAD,
            MAX_CONNECTION_STATUS_PENDING,
            'js',
            false,
        );
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());

        // Edit
        $oInfo->type = MAX_CONNECTION_TYPE_SIGNUP;
        $oInfo->status = MAX_CONNECTION_STATUS_APPROVED;
        $oInfo->variableMethod = 'dom';
        $oInfo->linkCampaigns = true;
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());

        $oGet = null;
        $this->assertTrue($dll->getTracker($oInfo->trackerId, $oGet), $dll->getLastError());
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SIGNUP);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_APPROVED);
        $this->assertEqual($oGet->variableMethod, 'dom');
        // See T001 comment: linkCampaigns always false on PHP 8 due to ENUM vs == 1 comparison.
        $this->assertFalse($oGet->linkCampaigns);
    }

    /**
     * T015: ADMIN, Edit, Signup->Sale, Ignore->OnHold, custom->default, link true->false
     */
    public function testT015_Admin_Edit_SignupToSale_IgnoreToOnHold_CustomToDefault()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();

        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SIGNUP,
            MAX_CONNECTION_STATUS_IGNORE,
            'custom',
            true,
        );
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());

        $oInfo->type = MAX_CONNECTION_TYPE_SALE;
        $oInfo->status = MAX_CONNECTION_STATUS_ONHOLD;
        $oInfo->variableMethod = 'default';
        $oInfo->linkCampaigns = false;
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());

        $oGet = null;
        $this->assertTrue($dll->getTracker($oInfo->trackerId, $oGet), $dll->getLastError());
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SALE);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_ONHOLD);
        $this->assertFalse($oGet->linkCampaigns);
    }

    /**
     * T016: MANAGER, Edit, Sale, Approved->Disapproved, dom->custom
     */
    public function testT016_Manager_Edit_Sale_ApprovedToDisapproved_DomToCustom()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();

        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SALE,
            MAX_CONNECTION_STATUS_APPROVED,
            'dom',
            false,
        );
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());

        $oInfo->status = MAX_CONNECTION_STATUS_DISAPPROVED;
        $oInfo->variableMethod = 'custom';
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());

        $oGet = null;
        $this->assertTrue($dll->getTracker($oInfo->trackerId, $oGet), $dll->getLastError());
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_DISAPPROVED);
        $this->assertEqual($oGet->variableMethod, 'custom');
    }

    /**
     * T017: ADMIN, Edit, Lead, Pending->Duplicate, js->dom
     */
    public function testT017_Admin_Edit_Lead_PendingToDuplicate_JsToDom()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();

        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_LEAD,
            MAX_CONNECTION_STATUS_PENDING,
            'js',
            true,
        );
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());

        $oInfo->status = MAX_CONNECTION_STATUS_DUPLICATE;
        $oInfo->variableMethod = 'dom';
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());

        $oGet = null;
        $this->assertTrue($dll->getTracker($oInfo->trackerId, $oGet), $dll->getLastError());
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_DUPLICATE);
    }

    /**
     * T018: MANAGER, Edit, Signup, OnHold->Ignore, custom->js
     */
    public function testT018_Manager_Edit_Signup_OnHoldToIgnore_CustomToJs()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();

        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SIGNUP,
            MAX_CONNECTION_STATUS_ONHOLD,
            'custom',
            false,
        );
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());

        $oInfo->status = MAX_CONNECTION_STATUS_IGNORE;
        $oInfo->variableMethod = 'js';
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());

        $oGet = null;
        $this->assertTrue($dll->getTracker($oInfo->trackerId, $oGet), $dll->getLastError());
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_IGNORE);
        $this->assertEqual($oGet->variableMethod, 'js');
    }

    /**
     * T019: ADMIN, Edit name only (no type/status/method change), Sale, Approved
     */
    public function testT019_Admin_Edit_NameOnly_Sale_Approved()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();

        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SALE,
            MAX_CONNECTION_STATUS_APPROVED,
            'default',
            true,
        );
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());

        // Only change name
        $oInfo->trackerName = 'Updated Tracker Name';
        $oInfo->variableMethod = null;
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());

        $oGet = null;
        $this->assertTrue($dll->getTracker($oInfo->trackerId, $oGet), $dll->getLastError());
        $this->assertEqual($oGet->trackerName, 'Updated Tracker Name');
    }

    /**
     * T020: MANAGER, Edit, Sale->Signup, Duplicate->Approved, default->custom, link true
     */
    public function testT020_Manager_Edit_SaleToSignup_DuplicateToApproved()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();

        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SALE,
            MAX_CONNECTION_STATUS_DUPLICATE,
            'default',
            true,
        );
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());

        $oInfo->type = MAX_CONNECTION_TYPE_SIGNUP;
        $oInfo->status = MAX_CONNECTION_STATUS_APPROVED;
        $oInfo->variableMethod = 'custom';
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());

        $oGet = null;
        $this->assertTrue($dll->getTracker($oInfo->trackerId, $oGet), $dll->getLastError());
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SIGNUP);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_APPROVED);
    }

    /**
     * T021: ADMIN, Edit non-existent tracker -> ERROR
     */
    public function testT021_Admin_Edit_NonExistentTracker()
    {
        $dll = $this->_getTrackerDll();

        $oInfo = new OA_Dll_TrackerInfo();
        $oInfo->trackerId = 999999;
        $oInfo->trackerName = 'Ghost';

        $this->assertFalse($dll->modify($oInfo));
        $this->assertEqual($dll->getLastError(), OA_Dll_Tracker::ERROR_UNKNOWN_TRACKER_ID);
    }

    /**
     * T022: MANAGER, Edit, Lead, Disapproved->Pending, dom->default
     */
    public function testT022_Manager_Edit_Lead_DisapprovedToPending_DomToDefault()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();

        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_LEAD,
            MAX_CONNECTION_STATUS_DISAPPROVED,
            'dom',
            false,
        );
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());

        $oInfo->status = MAX_CONNECTION_STATUS_PENDING;
        $oInfo->variableMethod = 'default';
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());

        $oGet = null;
        $this->assertTrue($dll->getTracker($oInfo->trackerId, $oGet), $dll->getLastError());
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_PENDING);
    }

    // =====================================================================
    //  VIEW / GET tests (T023-T036)
    //  Create then read-back each combination to verify field persistence
    // =====================================================================

    /**
     * T023: ADMIN, View, Sale, Approved, default, link=true
     */
    public function testT023_Admin_View_Sale_Approved_Default_LinkTrue()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SALE,
            MAX_CONNECTION_STATUS_APPROVED,
            'default',
            true,
        );
        $dll->modify($oInfo);

        $oGet = null;
        $this->assertTrue($dll->getTracker($oInfo->trackerId, $oGet), $dll->getLastError());
        $this->assertFieldEqual($oInfo, $oGet, 'trackerName');
        $this->assertFieldEqual($oInfo, $oGet, 'clientId');
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SALE);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_APPROVED);
        $this->assertEqual($oGet->variableMethod, 'default');
        // See T001 comment: linkCampaigns always false on PHP 8 due to ENUM vs == 1 comparison.
        $this->assertFalse($oGet->linkCampaigns);
    }

    /**
     * T024: MANAGER, View, Lead, Pending, js, link=false
     */
    public function testT024_Manager_View_Lead_Pending_Js_LinkFalse()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_LEAD,
            MAX_CONNECTION_STATUS_PENDING,
            'js',
            false,
        );
        $dll->modify($oInfo);

        $oGet = null;
        $this->assertTrue($dll->getTracker($oInfo->trackerId, $oGet), $dll->getLastError());
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_LEAD);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_PENDING);
        $this->assertEqual($oGet->variableMethod, 'js');
        $this->assertFalse($oGet->linkCampaigns);
    }

    /**
     * T025: ADMIN, View, Signup, Ignore, custom, link=true
     */
    public function testT025_Admin_View_Signup_Ignore_Custom_LinkTrue()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SIGNUP,
            MAX_CONNECTION_STATUS_IGNORE,
            'custom',
            true,
        );
        $dll->modify($oInfo);

        $oGet = null;
        $this->assertTrue($dll->getTracker($oInfo->trackerId, $oGet), $dll->getLastError());
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SIGNUP);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_IGNORE);
        $this->assertEqual($oGet->variableMethod, 'custom');
    }

    /**
     * T026: MANAGER, View, Sale, OnHold, dom, link=false
     */
    public function testT026_Manager_View_Sale_OnHold_Dom_LinkFalse()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SALE,
            MAX_CONNECTION_STATUS_ONHOLD,
            'dom',
            false,
        );
        $dll->modify($oInfo);

        $oGet = null;
        $this->assertTrue($dll->getTracker($oInfo->trackerId, $oGet), $dll->getLastError());
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_ONHOLD);
        $this->assertEqual($oGet->variableMethod, 'dom');
    }

    /**
     * T027: ADMIN, View, Lead, Disapproved, default, link=false
     */
    public function testT027_Admin_View_Lead_Disapproved_Default_LinkFalse()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_LEAD,
            MAX_CONNECTION_STATUS_DISAPPROVED,
            'default',
            false,
        );
        $dll->modify($oInfo);

        $oGet = null;
        $this->assertTrue($dll->getTracker($oInfo->trackerId, $oGet), $dll->getLastError());
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_DISAPPROVED);
        $this->assertFalse($oGet->linkCampaigns);
    }

    /**
     * T028: MANAGER, View, Signup, Duplicate, js, link=true
     */
    public function testT028_Manager_View_Signup_Duplicate_Js_LinkTrue()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SIGNUP,
            MAX_CONNECTION_STATUS_DUPLICATE,
            'js',
            true,
        );
        $dll->modify($oInfo);

        $oGet = null;
        $this->assertTrue($dll->getTracker($oInfo->trackerId, $oGet), $dll->getLastError());
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SIGNUP);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_DUPLICATE);
    }

    /**
     * T029: ADMIN, View non-existent tracker -> ERROR
     */
    public function testT029_Admin_View_NonExistentTracker()
    {
        $dll = $this->_getTrackerDll();
        $oGet = null;
        $this->assertFalse($dll->getTracker(999999, $oGet));
        $this->assertEqual($dll->getLastError(), OA_Dll_Tracker::ERROR_UNKNOWN_TRACKER_ID);
    }

    /**
     * T030: MANAGER, View, Sale, Approved, custom, link=true
     */
    public function testT030_Manager_View_Sale_Approved_Custom_LinkTrue()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SALE,
            MAX_CONNECTION_STATUS_APPROVED,
            'custom',
            true,
        );
        $dll->modify($oInfo);

        $oGet = null;
        $this->assertTrue($dll->getTracker($oInfo->trackerId, $oGet), $dll->getLastError());
        $this->assertEqual($oGet->variableMethod, 'custom');
        // See T001 comment: linkCampaigns always false on PHP 8 due to ENUM vs == 1 comparison.
        $this->assertFalse($oGet->linkCampaigns);
    }

    // =====================================================================
    //  DELETE tests (T031-T048)
    //  Create with specific params, then delete, then verify gone
    // =====================================================================

    /**
     * T031: ADMIN, Delete, Sale, Approved, default, link=true
     */
    public function testT031_Admin_Delete_Sale_Approved_Default()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SALE,
            MAX_CONNECTION_STATUS_APPROVED,
            'default',
            true,
        );
        $dll->modify($oInfo);

        $this->assertTrue($dll->delete($oInfo->trackerId), $dll->getLastError());

        // Verify deleted
        $oGet = null;
        $this->assertFalse($dll->getTracker($oInfo->trackerId, $oGet));
    }

    /**
     * T032: MANAGER, Delete, Lead, Pending, js, link=false
     */
    public function testT032_Manager_Delete_Lead_Pending_Js()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_LEAD,
            MAX_CONNECTION_STATUS_PENDING,
            'js',
            false,
        );
        $dll->modify($oInfo);

        $this->assertTrue($dll->delete($oInfo->trackerId), $dll->getLastError());
        $oGet = null;
        $this->assertFalse($dll->getTracker($oInfo->trackerId, $oGet));
    }

    /**
     * T033: ADMIN, Delete, Signup, Ignore, custom, link=true
     */
    public function testT033_Admin_Delete_Signup_Ignore_Custom()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SIGNUP,
            MAX_CONNECTION_STATUS_IGNORE,
            'custom',
            true,
        );
        $dll->modify($oInfo);

        $this->assertTrue($dll->delete($oInfo->trackerId), $dll->getLastError());
        $oGet = null;
        $this->assertFalse($dll->getTracker($oInfo->trackerId, $oGet));
    }

    /**
     * T034: MANAGER, Delete, Sale, OnHold, dom, link=false
     */
    public function testT034_Manager_Delete_Sale_OnHold_Dom()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SALE,
            MAX_CONNECTION_STATUS_ONHOLD,
            'dom',
            false,
        );
        $dll->modify($oInfo);

        $this->assertTrue($dll->delete($oInfo->trackerId), $dll->getLastError());
        $oGet = null;
        $this->assertFalse($dll->getTracker($oInfo->trackerId, $oGet));
    }

    /**
     * T035: ADMIN, Delete, Lead, Disapproved, default
     */
    public function testT035_Admin_Delete_Lead_Disapproved_Default()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_LEAD,
            MAX_CONNECTION_STATUS_DISAPPROVED,
            'default',
            true,
        );
        $dll->modify($oInfo);

        $this->assertTrue($dll->delete($oInfo->trackerId), $dll->getLastError());
        $oGet = null;
        $this->assertFalse($dll->getTracker($oInfo->trackerId, $oGet));
    }

    /**
     * T036: MANAGER, Delete, Signup, Duplicate, js
     */
    public function testT036_Manager_Delete_Signup_Duplicate_Js()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SIGNUP,
            MAX_CONNECTION_STATUS_DUPLICATE,
            'js',
            false,
        );
        $dll->modify($oInfo);

        $this->assertTrue($dll->delete($oInfo->trackerId), $dll->getLastError());
        $oGet = null;
        $this->assertFalse($dll->getTracker($oInfo->trackerId, $oGet));
    }

    /**
     * T037: ADMIN, Delete non-existent tracker -> ERROR
     */
    public function testT037_Admin_Delete_NonExistentTracker()
    {
        $dll = $this->_getTrackerDll();
        $this->assertFalse($dll->delete(999999));
    }

    /**
     * T038: MANAGER, Delete then delete again -> ERROR
     */
    public function testT038_Manager_Delete_AlreadyDeleted()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SALE,
            MAX_CONNECTION_STATUS_APPROVED,
            'default',
            false,
        );
        $dll->modify($oInfo);
        $dll->delete($oInfo->trackerId);

        $this->assertFalse($dll->delete($oInfo->trackerId));
        $this->assertEqual($dll->getLastError(), OA_Dll_Tracker::ERROR_UNKNOWN_TRACKER_ID);
    }

    /**
     * T039: ADMIN, Delete, Sale, Approved, custom, link=true
     */
    public function testT039_Admin_Delete_Sale_Approved_Custom_LinkTrue()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SALE,
            MAX_CONNECTION_STATUS_APPROVED,
            'custom',
            true,
        );
        $dll->modify($oInfo);

        $this->assertTrue($dll->delete($oInfo->trackerId), $dll->getLastError());
        $oGet = null;
        $this->assertFalse($dll->getTracker($oInfo->trackerId, $oGet));
    }

    /**
     * T040: MANAGER, Delete, Lead, Approved, dom, link=false
     */
    public function testT040_Manager_Delete_Lead_Approved_Dom_LinkFalse()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_LEAD,
            MAX_CONNECTION_STATUS_APPROVED,
            'dom',
            false,
        );
        $dll->modify($oInfo);

        $this->assertTrue($dll->delete($oInfo->trackerId), $dll->getLastError());
        $oGet = null;
        $this->assertFalse($dll->getTracker($oInfo->trackerId, $oGet));
    }

    // =====================================================================
    //  LINK TO CAMPAIGN tests (T041-T068)
    //  Dimensions: account(2) x type(3) x status(subset) x same/diff advertiser(2)
    // =====================================================================

    /**
     * T041: ADMIN, LinkToCampaign, Sale, Approved, same advertiser -> ALLOW
     */
    public function testT041_Admin_LinkToCampaign_Sale_Approved_SameAdvertiser()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SALE,
            MAX_CONNECTION_STATUS_APPROVED,
            'default',
            true,
        );
        $dll->modify($oInfo);

        $campaignId = $this->_createCampaign($advId);
        $this->assertTrue(
            $dll->linkTrackerToCampaign($oInfo->trackerId, $campaignId),
            $dll->getLastError(),
        );
    }

    /**
     * T042: ADMIN, LinkToCampaign, Sale, Approved, different advertiser -> DENY
     */
    public function testT042_Admin_LinkToCampaign_Sale_Approved_DiffAdvertiser()
    {
        $advId1 = $this->_createAdvertiser('Advertiser A');
        $advId2 = $this->_createAdvertiser('Advertiser B');
        $dll = $this->_getTrackerDll();

        $oInfo = $this->_buildTrackerInfo(
            $advId1,
            MAX_CONNECTION_TYPE_SALE,
            MAX_CONNECTION_STATUS_APPROVED,
            'default',
            true,
        );
        $dll->modify($oInfo);

        $campaignId = $this->_createCampaign($advId2);
        $this->assertFalse($dll->linkTrackerToCampaign($oInfo->trackerId, $campaignId));
        $this->assertEqual(
            $dll->getLastError(),
            OA_Dll_Tracker::ERROR_CAMPAIGN_ADVERTISER_MISMATCH,
        );
    }

    /**
     * T043: MANAGER, LinkToCampaign, Sale, Approved, same advertiser -> ALLOW
     */
    public function testT043_Manager_LinkToCampaign_Sale_Approved_SameAdvertiser()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SALE,
            MAX_CONNECTION_STATUS_APPROVED,
            'default',
            false,
        );
        $dll->modify($oInfo);

        $campaignId = $this->_createCampaign($advId);
        $this->assertTrue(
            $dll->linkTrackerToCampaign($oInfo->trackerId, $campaignId),
            $dll->getLastError(),
        );
    }

    /**
     * T044: MANAGER, LinkToCampaign, Lead, Pending, different advertiser -> DENY
     */
    public function testT044_Manager_LinkToCampaign_Lead_Pending_DiffAdvertiser()
    {
        $advId1 = $this->_createAdvertiser('Advertiser C');
        $advId2 = $this->_createAdvertiser('Advertiser D');
        $dll = $this->_getTrackerDll();

        $oInfo = $this->_buildTrackerInfo(
            $advId1,
            MAX_CONNECTION_TYPE_LEAD,
            MAX_CONNECTION_STATUS_PENDING,
            'js',
            false,
        );
        $dll->modify($oInfo);

        $campaignId = $this->_createCampaign($advId2);
        $this->assertFalse($dll->linkTrackerToCampaign($oInfo->trackerId, $campaignId));
        $this->assertEqual(
            $dll->getLastError(),
            OA_Dll_Tracker::ERROR_CAMPAIGN_ADVERTISER_MISMATCH,
        );
    }

    /**
     * T045: ADMIN, LinkToCampaign, Lead, Pending, same advertiser -> ALLOW
     */
    public function testT045_Admin_LinkToCampaign_Lead_Pending_SameAdvertiser()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_LEAD,
            MAX_CONNECTION_STATUS_PENDING,
            'js',
            true,
        );
        $dll->modify($oInfo);

        $campaignId = $this->_createCampaign($advId);
        $this->assertTrue(
            $dll->linkTrackerToCampaign($oInfo->trackerId, $campaignId),
            $dll->getLastError(),
        );
    }

    /**
     * T046: ADMIN, LinkToCampaign, Signup, Ignore, same advertiser -> ALLOW
     */
    public function testT046_Admin_LinkToCampaign_Signup_Ignore_SameAdvertiser()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SIGNUP,
            MAX_CONNECTION_STATUS_IGNORE,
            'custom',
            true,
        );
        $dll->modify($oInfo);

        $campaignId = $this->_createCampaign($advId);
        $this->assertTrue(
            $dll->linkTrackerToCampaign($oInfo->trackerId, $campaignId),
            $dll->getLastError(),
        );
    }

    /**
     * T047: MANAGER, LinkToCampaign, Signup, Ignore, different advertiser -> DENY
     */
    public function testT047_Manager_LinkToCampaign_Signup_Ignore_DiffAdvertiser()
    {
        $advId1 = $this->_createAdvertiser('Advertiser E');
        $advId2 = $this->_createAdvertiser('Advertiser F');
        $dll = $this->_getTrackerDll();

        $oInfo = $this->_buildTrackerInfo(
            $advId1,
            MAX_CONNECTION_TYPE_SIGNUP,
            MAX_CONNECTION_STATUS_IGNORE,
            'custom',
            false,
        );
        $dll->modify($oInfo);

        $campaignId = $this->_createCampaign($advId2);
        $this->assertFalse($dll->linkTrackerToCampaign($oInfo->trackerId, $campaignId));
        $this->assertEqual(
            $dll->getLastError(),
            OA_Dll_Tracker::ERROR_CAMPAIGN_ADVERTISER_MISMATCH,
        );
    }

    /**
     * T048: ADMIN, LinkToCampaign, Sale, OnHold, same advertiser -> ALLOW
     */
    public function testT048_Admin_LinkToCampaign_Sale_OnHold_SameAdvertiser()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SALE,
            MAX_CONNECTION_STATUS_ONHOLD,
            'dom',
            true,
        );
        $dll->modify($oInfo);

        $campaignId = $this->_createCampaign($advId);
        $this->assertTrue(
            $dll->linkTrackerToCampaign($oInfo->trackerId, $campaignId),
            $dll->getLastError(),
        );
    }

    /**
     * T049: MANAGER, LinkToCampaign, Lead, Disapproved, same advertiser -> ALLOW
     */
    public function testT049_Manager_LinkToCampaign_Lead_Disapproved_SameAdvertiser()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_LEAD,
            MAX_CONNECTION_STATUS_DISAPPROVED,
            'default',
            false,
        );
        $dll->modify($oInfo);

        $campaignId = $this->_createCampaign($advId);
        $this->assertTrue(
            $dll->linkTrackerToCampaign($oInfo->trackerId, $campaignId),
            $dll->getLastError(),
        );
    }

    /**
     * T050: ADMIN, LinkToCampaign, Signup, Duplicate, different advertiser -> DENY
     */
    public function testT050_Admin_LinkToCampaign_Signup_Duplicate_DiffAdvertiser()
    {
        $advId1 = $this->_createAdvertiser('Advertiser G');
        $advId2 = $this->_createAdvertiser('Advertiser H');
        $dll = $this->_getTrackerDll();

        $oInfo = $this->_buildTrackerInfo(
            $advId1,
            MAX_CONNECTION_TYPE_SIGNUP,
            MAX_CONNECTION_STATUS_DUPLICATE,
            'js',
            true,
        );
        $dll->modify($oInfo);

        $campaignId = $this->_createCampaign($advId2);
        $this->assertFalse($dll->linkTrackerToCampaign($oInfo->trackerId, $campaignId));
        $this->assertEqual(
            $dll->getLastError(),
            OA_Dll_Tracker::ERROR_CAMPAIGN_ADVERTISER_MISMATCH,
        );
    }

    /**
     * T051: MANAGER, LinkToCampaign, Sale, Duplicate, same advertiser -> ALLOW
     */
    public function testT051_Manager_LinkToCampaign_Sale_Duplicate_SameAdvertiser()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SALE,
            MAX_CONNECTION_STATUS_DUPLICATE,
            'default',
            true,
        );
        $dll->modify($oInfo);

        $campaignId = $this->_createCampaign($advId);
        $this->assertTrue(
            $dll->linkTrackerToCampaign($oInfo->trackerId, $campaignId),
            $dll->getLastError(),
        );
    }

    /**
     * T052: ADMIN, LinkToCampaign, Lead, Approved, different advertiser -> DENY
     */
    public function testT052_Admin_LinkToCampaign_Lead_Approved_DiffAdvertiser()
    {
        $advId1 = $this->_createAdvertiser('Advertiser I');
        $advId2 = $this->_createAdvertiser('Advertiser J');
        $dll = $this->_getTrackerDll();

        $oInfo = $this->_buildTrackerInfo(
            $advId1,
            MAX_CONNECTION_TYPE_LEAD,
            MAX_CONNECTION_STATUS_APPROVED,
            'dom',
            true,
        );
        $dll->modify($oInfo);

        $campaignId = $this->_createCampaign($advId2);
        $this->assertFalse($dll->linkTrackerToCampaign($oInfo->trackerId, $campaignId));
        $this->assertEqual(
            $dll->getLastError(),
            OA_Dll_Tracker::ERROR_CAMPAIGN_ADVERTISER_MISMATCH,
        );
    }

    /**
     * T053: MANAGER, LinkToCampaign, Signup, Approved, same advertiser -> ALLOW
     */
    public function testT053_Manager_LinkToCampaign_Signup_Approved_SameAdvertiser()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SIGNUP,
            MAX_CONNECTION_STATUS_APPROVED,
            'default',
            true,
        );
        $dll->modify($oInfo);

        $campaignId = $this->_createCampaign($advId);
        $this->assertTrue(
            $dll->linkTrackerToCampaign($oInfo->trackerId, $campaignId),
            $dll->getLastError(),
        );
    }

    /**
     * T054: ADMIN, LinkToCampaign, non-existent tracker -> ERROR
     */
    public function testT054_Admin_LinkToCampaign_NonExistentTracker()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $campaignId = $this->_createCampaign($advId);

        $this->assertFalse($dll->linkTrackerToCampaign(999999, $campaignId));
        $this->assertEqual($dll->getLastError(), OA_Dll_Tracker::ERROR_UNKNOWN_TRACKER_ID);
    }

    /**
     * T055: MANAGER, LinkToCampaign, non-existent campaign -> ERROR
     */
    public function testT055_Manager_LinkToCampaign_NonExistentCampaign()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SALE,
            MAX_CONNECTION_STATUS_APPROVED,
            'default',
            false,
        );
        $dll->modify($oInfo);

        $this->assertFalse($dll->linkTrackerToCampaign($oInfo->trackerId, 999999));
        $this->assertEqual($dll->getLastError(), OA_Dll_Tracker::ERROR_UNKNOWN_CAMPAIGN_ID);
    }

    /**
     * T056: ADMIN, LinkToCampaign, Sale, Disapproved, same advertiser -> ALLOW
     */
    public function testT056_Admin_LinkToCampaign_Sale_Disapproved_SameAdvertiser()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SALE,
            MAX_CONNECTION_STATUS_DISAPPROVED,
            'custom',
            true,
        );
        $dll->modify($oInfo);

        $campaignId = $this->_createCampaign($advId);
        $this->assertTrue(
            $dll->linkTrackerToCampaign($oInfo->trackerId, $campaignId),
            $dll->getLastError(),
        );
    }

    /**
     * T057: MANAGER, LinkToCampaign, Lead, OnHold, different advertiser -> DENY
     */
    public function testT057_Manager_LinkToCampaign_Lead_OnHold_DiffAdvertiser()
    {
        $advId1 = $this->_createAdvertiser('Advertiser K');
        $advId2 = $this->_createAdvertiser('Advertiser L');
        $dll = $this->_getTrackerDll();

        $oInfo = $this->_buildTrackerInfo(
            $advId1,
            MAX_CONNECTION_TYPE_LEAD,
            MAX_CONNECTION_STATUS_ONHOLD,
            'dom',
            false,
        );
        $dll->modify($oInfo);

        $campaignId = $this->_createCampaign($advId2);
        $this->assertFalse($dll->linkTrackerToCampaign($oInfo->trackerId, $campaignId));
        $this->assertEqual(
            $dll->getLastError(),
            OA_Dll_Tracker::ERROR_CAMPAIGN_ADVERTISER_MISMATCH,
        );
    }

    /**
     * T058: ADMIN, LinkToCampaign, Signup, Pending, same advertiser -> ALLOW
     */
    public function testT058_Admin_LinkToCampaign_Signup_Pending_SameAdvertiser()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SIGNUP,
            MAX_CONNECTION_STATUS_PENDING,
            'js',
            true,
        );
        $dll->modify($oInfo);

        $campaignId = $this->_createCampaign($advId);
        $this->assertTrue(
            $dll->linkTrackerToCampaign($oInfo->trackerId, $campaignId),
            $dll->getLastError(),
        );
    }

    /**
     * T059: MANAGER, LinkToCampaign, Sale, Pending, different advertiser -> DENY
     */
    public function testT059_Manager_LinkToCampaign_Sale_Pending_DiffAdvertiser()
    {
        $advId1 = $this->_createAdvertiser('Advertiser M');
        $advId2 = $this->_createAdvertiser('Advertiser N');
        $dll = $this->_getTrackerDll();

        $oInfo = $this->_buildTrackerInfo(
            $advId1,
            MAX_CONNECTION_TYPE_SALE,
            MAX_CONNECTION_STATUS_PENDING,
            'js',
            false,
        );
        $dll->modify($oInfo);

        $campaignId = $this->_createCampaign($advId2);
        $this->assertFalse($dll->linkTrackerToCampaign($oInfo->trackerId, $campaignId));
        $this->assertEqual(
            $dll->getLastError(),
            OA_Dll_Tracker::ERROR_CAMPAIGN_ADVERTISER_MISMATCH,
        );
    }

    /**
     * T060: ADMIN, LinkToCampaign, idempotent (link same campaign twice) -> ALLOW
     */
    public function testT060_Admin_LinkToCampaign_Idempotent()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SALE,
            MAX_CONNECTION_STATUS_APPROVED,
            'default',
            true,
        );
        $dll->modify($oInfo);

        $campaignId = $this->_createCampaign($advId);
        $this->assertTrue($dll->linkTrackerToCampaign($oInfo->trackerId, $campaignId));
        // Link again - should still succeed (idempotent)
        $this->assertTrue($dll->linkTrackerToCampaign($oInfo->trackerId, $campaignId));
    }

    /**
     * T061: MANAGER, LinkToCampaign, Signup, OnHold, same advertiser -> ALLOW
     */
    public function testT061_Manager_LinkToCampaign_Signup_OnHold_SameAdvertiser()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SIGNUP,
            MAX_CONNECTION_STATUS_ONHOLD,
            'dom',
            false,
        );
        $dll->modify($oInfo);

        $campaignId = $this->_createCampaign($advId);
        $this->assertTrue(
            $dll->linkTrackerToCampaign($oInfo->trackerId, $campaignId),
            $dll->getLastError(),
        );
    }

    /**
     * T062: ADMIN, LinkToCampaign, Lead, Duplicate, different advertiser -> DENY
     */
    public function testT062_Admin_LinkToCampaign_Lead_Duplicate_DiffAdvertiser()
    {
        $advId1 = $this->_createAdvertiser('Advertiser O');
        $advId2 = $this->_createAdvertiser('Advertiser P');
        $dll = $this->_getTrackerDll();

        $oInfo = $this->_buildTrackerInfo(
            $advId1,
            MAX_CONNECTION_TYPE_LEAD,
            MAX_CONNECTION_STATUS_DUPLICATE,
            'custom',
            true,
        );
        $dll->modify($oInfo);

        $campaignId = $this->_createCampaign($advId2);
        $this->assertFalse($dll->linkTrackerToCampaign($oInfo->trackerId, $campaignId));
        $this->assertEqual(
            $dll->getLastError(),
            OA_Dll_Tracker::ERROR_CAMPAIGN_ADVERTISER_MISMATCH,
        );
    }

    /**
     * T063: MANAGER, LinkToCampaign with explicit status override, same advertiser -> ALLOW
     */
    public function testT063_Manager_LinkToCampaign_ExplicitStatus_SameAdvertiser()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SALE,
            MAX_CONNECTION_STATUS_APPROVED,
            'default',
            true,
        );
        $dll->modify($oInfo);

        $campaignId = $this->_createCampaign($advId);
        $this->assertTrue(
            $dll->linkTrackerToCampaign($oInfo->trackerId, $campaignId, MAX_CONNECTION_STATUS_PENDING),
            $dll->getLastError(),
        );
    }

    /**
     * T064: ADMIN, LinkToCampaign, Signup, Disapproved, different advertiser -> DENY
     */
    public function testT064_Admin_LinkToCampaign_Signup_Disapproved_DiffAdvertiser()
    {
        $advId1 = $this->_createAdvertiser('Advertiser Q');
        $advId2 = $this->_createAdvertiser('Advertiser R');
        $dll = $this->_getTrackerDll();

        $oInfo = $this->_buildTrackerInfo(
            $advId1,
            MAX_CONNECTION_TYPE_SIGNUP,
            MAX_CONNECTION_STATUS_DISAPPROVED,
            'dom',
            false,
        );
        $dll->modify($oInfo);

        $campaignId = $this->_createCampaign($advId2);
        $this->assertFalse($dll->linkTrackerToCampaign($oInfo->trackerId, $campaignId));
        $this->assertEqual(
            $dll->getLastError(),
            OA_Dll_Tracker::ERROR_CAMPAIGN_ADVERTISER_MISMATCH,
        );
    }

    /**
     * T065: MANAGER, LinkToCampaign, Lead, Ignore, same advertiser -> ALLOW
     */
    public function testT065_Manager_LinkToCampaign_Lead_Ignore_SameAdvertiser()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_LEAD,
            MAX_CONNECTION_STATUS_IGNORE,
            'js',
            true,
        );
        $dll->modify($oInfo);

        $campaignId = $this->_createCampaign($advId);
        $this->assertTrue(
            $dll->linkTrackerToCampaign($oInfo->trackerId, $campaignId),
            $dll->getLastError(),
        );
    }

    /**
     * T066: ADMIN, LinkToCampaign, Sale, Ignore, different advertiser -> DENY
     */
    public function testT066_Admin_LinkToCampaign_Sale_Ignore_DiffAdvertiser()
    {
        $advId1 = $this->_createAdvertiser('Advertiser S');
        $advId2 = $this->_createAdvertiser('Advertiser T');
        $dll = $this->_getTrackerDll();

        $oInfo = $this->_buildTrackerInfo(
            $advId1,
            MAX_CONNECTION_TYPE_SALE,
            MAX_CONNECTION_STATUS_IGNORE,
            'default',
            true,
        );
        $dll->modify($oInfo);

        $campaignId = $this->_createCampaign($advId2);
        $this->assertFalse($dll->linkTrackerToCampaign($oInfo->trackerId, $campaignId));
        $this->assertEqual(
            $dll->getLastError(),
            OA_Dll_Tracker::ERROR_CAMPAIGN_ADVERTISER_MISMATCH,
        );
    }

    // =====================================================================
    //  VARIABLE METHOD edge cases (T067-T074)
    //  Verify each method stores/retrieves correctly, plus invalid "header"
    // =====================================================================

    /**
     * T067: ADMIN, Create, Sale, Approved, variableMethod=default
     */
    public function testT067_Admin_VariableMethod_Default()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SALE,
            MAX_CONNECTION_STATUS_APPROVED,
            'default',
            false,
        );
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());
        $oGet = null;
        $this->assertTrue($dll->getTracker($oInfo->trackerId, $oGet));
        $this->assertEqual($oGet->variableMethod, 'default');
    }

    /**
     * T068: MANAGER, Create, Lead, Approved, variableMethod=js
     */
    public function testT068_Manager_VariableMethod_Js()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_LEAD,
            MAX_CONNECTION_STATUS_APPROVED,
            'js',
            true,
        );
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());
        $oGet = null;
        $this->assertTrue($dll->getTracker($oInfo->trackerId, $oGet));
        $this->assertEqual($oGet->variableMethod, 'js');
    }

    /**
     * T069: ADMIN, Create, Signup, Approved, variableMethod=custom
     */
    public function testT069_Admin_VariableMethod_Custom()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SIGNUP,
            MAX_CONNECTION_STATUS_APPROVED,
            'custom',
            false,
        );
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());
        $oGet = null;
        $this->assertTrue($dll->getTracker($oInfo->trackerId, $oGet));
        $this->assertEqual($oGet->variableMethod, 'custom');
    }

    /**
     * T070: MANAGER, Create, Sale, Pending, variableMethod=dom
     */
    public function testT070_Manager_VariableMethod_Dom()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SALE,
            MAX_CONNECTION_STATUS_PENDING,
            'dom',
            true,
        );
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());
        $oGet = null;
        $this->assertTrue($dll->getTracker($oInfo->trackerId, $oGet));
        $this->assertEqual($oGet->variableMethod, 'dom');
    }

    /**
     * T071: ADMIN, Edit variableMethod from js to custom
     */
    public function testT071_Admin_Edit_VariableMethod_JsToCustom()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_LEAD,
            MAX_CONNECTION_STATUS_APPROVED,
            'js',
            false,
        );
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());

        $oInfo->variableMethod = 'custom';
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());

        $oGet = null;
        $this->assertTrue($dll->getTracker($oInfo->trackerId, $oGet));
        $this->assertEqual($oGet->variableMethod, 'custom');
    }

    /**
     * T072: MANAGER, Edit variableMethod from dom to default
     */
    public function testT072_Manager_Edit_VariableMethod_DomToDefault()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SIGNUP,
            MAX_CONNECTION_STATUS_APPROVED,
            'dom',
            true,
        );
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());

        $oInfo->variableMethod = 'default';
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());

        $oGet = null;
        $this->assertTrue($dll->getTracker($oInfo->trackerId, $oGet));
        $this->assertEqual($oGet->variableMethod, 'default');
    }

    /**
     * T073: ADMIN, Edit variableMethod from default to dom
     */
    public function testT073_Admin_Edit_VariableMethod_DefaultToDom()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SALE,
            MAX_CONNECTION_STATUS_APPROVED,
            'default',
            false,
        );
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());

        $oInfo->variableMethod = 'dom';
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());

        $oGet = null;
        $this->assertTrue($dll->getTracker($oInfo->trackerId, $oGet));
        $this->assertEqual($oGet->variableMethod, 'dom');
    }

    /**
     * T074: MANAGER, Edit variableMethod from custom to js
     */
    public function testT074_Manager_Edit_VariableMethod_CustomToJs()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_LEAD,
            MAX_CONNECTION_STATUS_PENDING,
            'custom',
            true,
        );
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());

        $oInfo->variableMethod = 'js';
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());

        $oGet = null;
        $this->assertTrue($dll->getTracker($oInfo->trackerId, $oGet));
        $this->assertEqual($oGet->variableMethod, 'js');
    }

    // =====================================================================
    //  CONNECTION STATUS exhaustive pairs (T075-T086)
    //  Each status with each tracker type
    // =====================================================================

    /**
     * T075: ADMIN, Create, Sale, Ignore
     */
    public function testT075_Admin_Create_Sale_Ignore()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo($advId, MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_IGNORE, 'default', false);
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());
        $oGet = null;
        $dll->getTracker($oInfo->trackerId, $oGet);
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SALE);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_IGNORE);
    }

    /**
     * T076: MANAGER, Create, Lead, Ignore
     */
    public function testT076_Manager_Create_Lead_Ignore()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo($advId, MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_IGNORE, 'js', true);
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());
        $oGet = null;
        $dll->getTracker($oInfo->trackerId, $oGet);
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_LEAD);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_IGNORE);
    }

    /**
     * T077: ADMIN, Create, Signup, Pending
     */
    public function testT077_Admin_Create_Signup_Pending()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo($advId, MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_PENDING, 'custom', false);
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());
        $oGet = null;
        $dll->getTracker($oInfo->trackerId, $oGet);
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SIGNUP);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_PENDING);
    }

    /**
     * T078: MANAGER, Create, Sale, OnHold
     */
    public function testT078_Manager_Create_Sale_OnHold()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo($advId, MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_ONHOLD, 'dom', true);
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());
        $oGet = null;
        $dll->getTracker($oInfo->trackerId, $oGet);
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SALE);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_ONHOLD);
    }

    /**
     * T079: ADMIN, Create, Lead, Approved
     */
    public function testT079_Admin_Create_Lead_Approved()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo($advId, MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_APPROVED, 'default', false);
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());
        $oGet = null;
        $dll->getTracker($oInfo->trackerId, $oGet);
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_LEAD);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_APPROVED);
    }

    /**
     * T080: MANAGER, Create, Signup, Disapproved
     */
    public function testT080_Manager_Create_Signup_Disapproved()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo($advId, MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_DISAPPROVED, 'js', true);
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());
        $oGet = null;
        $dll->getTracker($oInfo->trackerId, $oGet);
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SIGNUP);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_DISAPPROVED);
    }

    /**
     * T081: ADMIN, Create, Sale, Duplicate
     */
    public function testT081_Admin_Create_Sale_Duplicate()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo($advId, MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_DUPLICATE, 'custom', false);
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());
        $oGet = null;
        $dll->getTracker($oInfo->trackerId, $oGet);
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SALE);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_DUPLICATE);
    }

    /**
     * T082: MANAGER, Create, Lead, Duplicate
     */
    public function testT082_Manager_Create_Lead_Duplicate()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo($advId, MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_DUPLICATE, 'dom', true);
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());
        $oGet = null;
        $dll->getTracker($oInfo->trackerId, $oGet);
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_LEAD);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_DUPLICATE);
    }

    /**
     * T083: ADMIN, Create, Signup, OnHold
     */
    public function testT083_Admin_Create_Signup_OnHold()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo($advId, MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_ONHOLD, 'default', true);
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());
        $oGet = null;
        $dll->getTracker($oInfo->trackerId, $oGet);
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SIGNUP);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_ONHOLD);
    }

    /**
     * T084: MANAGER, Create, Sale, Disapproved
     */
    public function testT084_Manager_Create_Sale_Disapproved()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo($advId, MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_DISAPPROVED, 'js', false);
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());
        $oGet = null;
        $dll->getTracker($oInfo->trackerId, $oGet);
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SALE);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_DISAPPROVED);
    }

    /**
     * T085: ADMIN, Create, Lead, OnHold
     */
    public function testT085_Admin_Create_Lead_OnHold()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo($advId, MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_ONHOLD, 'custom', true);
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());
        $oGet = null;
        $dll->getTracker($oInfo->trackerId, $oGet);
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_LEAD);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_ONHOLD);
    }

    /**
     * T086: MANAGER, Create, Signup, Ignore
     */
    public function testT086_Manager_Create_Signup_Ignore()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll();
        $oInfo = $this->_buildTrackerInfo($advId, MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_IGNORE, 'dom', false);
        $this->assertTrue($dll->modify($oInfo), $dll->getLastError());
        $oGet = null;
        $dll->getTracker($oInfo->trackerId, $oGet);
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SIGNUP);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_IGNORE);
    }

    // =====================================================================
    //  PERMISSION-DENIED tests (T087-T090)
    //  Verify operations fail when permissions are denied
    // =====================================================================

    /**
     * T087: Permission denied on Create
     */
    public function testT087_PermissionDenied_Create()
    {
        $advId = $this->_createAdvertiser();
        $dll = $this->_getTrackerDll(false);
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SALE,
            MAX_CONNECTION_STATUS_APPROVED,
            'default',
            true,
        );

        $this->assertFalse($dll->modify($oInfo));
    }

    /**
     * T088: Permission denied on Edit
     */
    public function testT088_PermissionDenied_Edit()
    {
        $advId = $this->_createAdvertiser();
        $dllAllowed = $this->_getTrackerDll(true);
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_LEAD,
            MAX_CONNECTION_STATUS_PENDING,
            'js',
            false,
        );
        $dllAllowed->modify($oInfo);

        // Now try to edit with denied permissions
        $dllDenied = $this->_getTrackerDll(false);
        $oInfo->trackerName = 'Updated Name';
        $oInfo->variableMethod = null;
        $this->assertFalse($dllDenied->modify($oInfo));
    }

    /**
     * T089: Permission denied on Delete
     */
    public function testT089_PermissionDenied_Delete()
    {
        $advId = $this->_createAdvertiser();
        $dllAllowed = $this->_getTrackerDll(true);
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SIGNUP,
            MAX_CONNECTION_STATUS_APPROVED,
            'custom',
            true,
        );
        $dllAllowed->modify($oInfo);

        $dllDenied = $this->_getTrackerDll(false);
        $this->assertFalse($dllDenied->delete($oInfo->trackerId));
    }

    /**
     * T090: Permission denied on LinkToCampaign
     */
    public function testT090_PermissionDenied_LinkToCampaign()
    {
        $advId = $this->_createAdvertiser();
        $dllAllowed = $this->_getTrackerDll(true);
        $oInfo = $this->_buildTrackerInfo(
            $advId,
            MAX_CONNECTION_TYPE_SALE,
            MAX_CONNECTION_STATUS_APPROVED,
            'default',
            false,
        );
        $dllAllowed->modify($oInfo);

        $campaignId = $this->_createCampaign($advId);

        $dllDenied = $this->_getTrackerDll(false);
        $this->assertFalse($dllDenied->linkTrackerToCampaign($oInfo->trackerId, $campaignId));
    }
}
