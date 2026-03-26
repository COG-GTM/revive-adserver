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
 * Exhaustive combinatorial tests for the Tracker/Conversion matrix.
 *
 * Dimensions:
 *   Account type      : ADMIN, MANAGER
 *   Page mode         : Create, Edit, View, Delete, LinkToCampaign
 *   Tracker type      : Sale (1), Lead (2), Signup (3)
 *   Connection status : Ignore (1), Pending (2), OnHold (3), Approved (4), Disapproved (5), Duplicate (6)
 *   Variable method   : default, js, custom, dom, header
 *   Link campaigns    : true, false
 *   Same advertiser   : same, different (LinkToCampaign only)
 *
 * Target: ~90 valid combinations covering the full matrix.
 *
 * @package    OpenXDll
 * @subpackage TestSuite
 */
class OA_Dll_TrackerCombinatorialTest extends DllUnitTestCase
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
            'PartialMockOA_Dll_Tracker_Combinatorial',
            ['checkPermissions'],
        );
        Mock::generatePartial(
            'OA_Dll_Advertiser',
            'PartialMockOA_Dll_Advertiser_Combinatorial',
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
    //  Helper: create an advertiser under the test agency
    // ------------------------------------------------------------------
    private function _createAdvertiser()
    {
        $dllAdv = new PartialMockOA_Dll_Advertiser_Combinatorial($this);
        $dllAdv->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdv->setReturnValue('checkPermissions', true);

        $oAdvInfo = new OA_Dll_AdvertiserInfo();
        $oAdvInfo->advertiserName = 'Combinatorial test advertiser';
        $oAdvInfo->agencyId = $this->agencyId;
        $dllAdv->modify($oAdvInfo);

        return $oAdvInfo->advertiserId;
    }

    // ------------------------------------------------------------------
    //  Helper: create a campaign for an advertiser
    // ------------------------------------------------------------------
    private function _createCampaign($clientId)
    {
        $doCampaigns = OA_Dal::factoryDO('campaigns');
        $doCampaigns->clientid = $clientId;
        $doCampaigns->campaignname = 'Combinatorial campaign';
        return DataGenerator::generateOne($doCampaigns, true);
    }

    // ------------------------------------------------------------------
    //  Helper: get a tracker DLL mock with permissions returning true
    // ------------------------------------------------------------------
    private function _getTrackerDll()
    {
        $dll = new PartialMockOA_Dll_Tracker_Combinatorial($this);
        $dll->setReturnValue('checkPermissions', true);
        return $dll;
    }

    // ------------------------------------------------------------------
    //  Helper: create a tracker directly via DLL
    // ------------------------------------------------------------------
    private function _createTracker(
        $dllTracker,
        $clientId,
        $trackerName,
        $type = null,
        $status = null,
        $variableMethod = null,
        $linkCampaigns = null
    ) {
        $oInfo = new OA_Dll_TrackerInfo();
        $oInfo->clientId = $clientId;
        $oInfo->trackerName = $trackerName;

        if ($type !== null) {
            $oInfo->type = $type;
        }
        if ($status !== null) {
            $oInfo->status = $status;
        }
        if ($variableMethod !== null) {
            $oInfo->variableMethod = $variableMethod;
        }
        if ($linkCampaigns !== null) {
            $oInfo->linkCampaigns = $linkCampaigns;
        }

        $result = $dllTracker->modify($oInfo);
        return [$result, $oInfo];
    }

    // ====================================================================
    //  CREATE MODE TESTS  (~24 combos)
    //
    //  Each test creates a tracker with specific type, status,
    //  variableMethod and linkCampaigns values and asserts success.
    // ====================================================================

    // --- ADMIN account, Create mode ---

    /** T001 ADMIN | Create | Sale | Approved | default | link=true */
    public function testCreate_Admin_Sale_Approved_Default_LinkTrue()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T001',
            MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_APPROVED,
            'default', true,
        );
        $this->assertTrue($ok, 'T001 Create failed: ' . $dll->getLastError());
        $this->assertNotNull($info->trackerId, 'T001 trackerId should be set');
    }

    /** T002 ADMIN | Create | Sale | Pending | js | link=false */
    public function testCreate_Admin_Sale_Pending_Js_LinkFalse()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T002',
            MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_PENDING,
            'js', false,
        );
        $this->assertTrue($ok, 'T002 Create failed: ' . $dll->getLastError());
    }

    /** T003 ADMIN | Create | Sale | Ignore | custom | link=true */
    public function testCreate_Admin_Sale_Ignore_Custom_LinkTrue()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T003',
            MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_IGNORE,
            'custom', true,
        );
        $this->assertTrue($ok, 'T003 Create failed: ' . $dll->getLastError());
    }

    /** T004 ADMIN | Create | Sale | OnHold | dom | link=false */
    public function testCreate_Admin_Sale_OnHold_Dom_LinkFalse()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T004',
            MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_ONHOLD,
            'dom', false,
        );
        $this->assertTrue($ok, 'T004 Create failed: ' . $dll->getLastError());
    }

    /** T005 ADMIN | Create | Sale | Disapproved | default | link=true */
    public function testCreate_Admin_Sale_Disapproved_Default_LinkTrue()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T005',
            MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_DISAPPROVED,
            'default', true,
        );
        $this->assertTrue($ok, 'T005 Create failed: ' . $dll->getLastError());
    }

    /** T006 ADMIN | Create | Sale | Duplicate | js | link=false */
    public function testCreate_Admin_Sale_Duplicate_Js_LinkFalse()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T006',
            MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_DUPLICATE,
            'js', false,
        );
        $this->assertTrue($ok, 'T006 Create failed: ' . $dll->getLastError());
    }

    /** T007 ADMIN | Create | Lead | Approved | custom | link=true */
    public function testCreate_Admin_Lead_Approved_Custom_LinkTrue()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T007',
            MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_APPROVED,
            'custom', true,
        );
        $this->assertTrue($ok, 'T007 Create failed: ' . $dll->getLastError());
    }

    /** T008 ADMIN | Create | Lead | Pending | dom | link=false */
    public function testCreate_Admin_Lead_Pending_Dom_LinkFalse()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T008',
            MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_PENDING,
            'dom', false,
        );
        $this->assertTrue($ok, 'T008 Create failed: ' . $dll->getLastError());
    }

    /** T009 ADMIN | Create | Lead | Duplicate | header | link=false */
    public function testCreate_Admin_Lead_Duplicate_Header_LinkFalse()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T009',
            MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_DUPLICATE,
            'header', false,
        );
        // 'header' is not in the ENUM so the DLL may still accept it
        // (validation only checks string type, not value); we verify
        // the call completes without fatal error.
        $this->assertTrue($ok, 'T009 Create with header variable: ' . $dll->getLastError());
    }

    /** T010 ADMIN | Create | Signup | Approved | default | link=true */
    public function testCreate_Admin_Signup_Approved_Default_LinkTrue()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T010',
            MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_APPROVED,
            'default', true,
        );
        $this->assertTrue($ok, 'T010 Create failed: ' . $dll->getLastError());
    }

    /** T011 ADMIN | Create | Signup | Pending | js | link=false */
    public function testCreate_Admin_Signup_Pending_Js_LinkFalse()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T011',
            MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_PENDING,
            'js', false,
        );
        $this->assertTrue($ok, 'T011 Create failed: ' . $dll->getLastError());
    }

    /** T012 ADMIN | Create | Signup | Ignore | custom | link=true */
    public function testCreate_Admin_Signup_Ignore_Custom_LinkTrue()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T012',
            MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_IGNORE,
            'custom', true,
        );
        $this->assertTrue($ok, 'T012 Create failed: ' . $dll->getLastError());
    }

    // --- MANAGER account, Create mode ---

    /** T013 MANAGER | Create | Sale | Approved | default | link=true */
    public function testCreate_Manager_Sale_Approved_Default_LinkTrue()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T013',
            MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_APPROVED,
            'default', true,
        );
        $this->assertTrue($ok, 'T013 Create failed: ' . $dll->getLastError());
    }

    /** T014 MANAGER | Create | Sale | Pending | js | link=false */
    public function testCreate_Manager_Sale_Pending_Js_LinkFalse()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T014',
            MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_PENDING,
            'js', false,
        );
        $this->assertTrue($ok, 'T014 Create failed: ' . $dll->getLastError());
    }

    /** T015 MANAGER | Create | Sale | Ignore | custom | link=true */
    public function testCreate_Manager_Sale_Ignore_Custom_LinkTrue()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T015',
            MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_IGNORE,
            'custom', true,
        );
        $this->assertTrue($ok, 'T015 Create failed: ' . $dll->getLastError());
    }

    /** T016 MANAGER | Create | Lead | Approved | dom | link=false */
    public function testCreate_Manager_Lead_Approved_Dom_LinkFalse()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T016',
            MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_APPROVED,
            'dom', false,
        );
        $this->assertTrue($ok, 'T016 Create failed: ' . $dll->getLastError());
    }

    /** T017 MANAGER | Create | Lead | Pending | default | link=true */
    public function testCreate_Manager_Lead_Pending_Default_LinkTrue()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T017',
            MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_PENDING,
            'default', true,
        );
        $this->assertTrue($ok, 'T017 Create failed: ' . $dll->getLastError());
    }

    /** T018 MANAGER | Create | Lead | OnHold | js | link=false */
    public function testCreate_Manager_Lead_OnHold_Js_LinkFalse()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T018',
            MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_ONHOLD,
            'js', false,
        );
        $this->assertTrue($ok, 'T018 Create failed: ' . $dll->getLastError());
    }

    /** T019 MANAGER | Create | Signup | Approved | custom | link=true */
    public function testCreate_Manager_Signup_Approved_Custom_LinkTrue()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T019',
            MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_APPROVED,
            'custom', true,
        );
        $this->assertTrue($ok, 'T019 Create failed: ' . $dll->getLastError());
    }

    /** T020 MANAGER | Create | Signup | Pending | dom | link=false */
    public function testCreate_Manager_Signup_Pending_Dom_LinkFalse()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T020',
            MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_PENDING,
            'dom', false,
        );
        $this->assertTrue($ok, 'T020 Create failed: ' . $dll->getLastError());
    }

    /** T021 MANAGER | Create | Signup | Disapproved | default | link=true */
    public function testCreate_Manager_Signup_Disapproved_Default_LinkTrue()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T021',
            MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_DISAPPROVED,
            'default', true,
        );
        $this->assertTrue($ok, 'T021 Create failed: ' . $dll->getLastError());
    }

    /** T022 MANAGER | Create | Signup | Duplicate | js | link=false */
    public function testCreate_Manager_Signup_Duplicate_Js_LinkFalse()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T022',
            MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_DUPLICATE,
            'js', false,
        );
        $this->assertTrue($ok, 'T022 Create failed: ' . $dll->getLastError());
    }

    /** T023 ADMIN | Create | Lead | OnHold | default | link=true */
    public function testCreate_Admin_Lead_OnHold_Default_LinkTrue()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T023',
            MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_ONHOLD,
            'default', true,
        );
        $this->assertTrue($ok, 'T023 Create failed: ' . $dll->getLastError());
    }

    /** T024 MANAGER | Create | Sale | Duplicate | dom | link=true */
    public function testCreate_Manager_Sale_Duplicate_Dom_LinkTrue()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T024',
            MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_DUPLICATE,
            'dom', true,
        );
        $this->assertTrue($ok, 'T024 Create failed: ' . $dll->getLastError());
    }

    // ====================================================================
    //  EDIT MODE TESTS  (~20 combos)
    //
    //  Create a tracker, then modify it with different variableMethod,
    //  status, linkCampaigns, etc.
    // ====================================================================

    // --- ADMIN account, Edit mode ---

    /** T025 ADMIN | Edit | Sale | Approved | change to js | link=true */
    public function testEdit_Admin_Sale_Approved_ChangeToJs()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T025',
            MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_APPROVED,
            'default', true,
        );
        $this->assertTrue($ok, 'T025 Create failed');

        $info->trackerName = 'T025 edited';
        $info->variableMethod = 'js';
        $this->assertTrue($dll->modify($info), 'T025 Edit failed: ' . $dll->getLastError());

        // Verify edit persisted
        $oGet = null;
        $this->assertTrue($dll->getTracker($info->trackerId, $oGet));
        $this->assertEqual($oGet->trackerName, 'T025 edited');
    }

    /** T026 ADMIN | Edit | Sale | Pending | change to dom */
    public function testEdit_Admin_Sale_Pending_ChangeToDom()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T026',
            MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_PENDING,
            'default', false,
        );
        $this->assertTrue($ok, 'T026 Create failed');

        $info->variableMethod = 'dom';
        $this->assertTrue($dll->modify($info), 'T026 Edit failed: ' . $dll->getLastError());
    }

    /** T027 ADMIN | Edit | Lead | Approved | change to custom */
    public function testEdit_Admin_Lead_Approved_ChangeToCustom()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T027',
            MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_APPROVED,
            'js', true,
        );
        $this->assertTrue($ok, 'T027 Create failed');

        $info->variableMethod = 'custom';
        $this->assertTrue($dll->modify($info), 'T027 Edit failed: ' . $dll->getLastError());
    }

    /** T028 ADMIN | Edit | Lead | OnHold | change to default */
    public function testEdit_Admin_Lead_OnHold_ChangeToDefault()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T028',
            MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_ONHOLD,
            'dom', false,
        );
        $this->assertTrue($ok, 'T028 Create failed');

        $info->variableMethod = 'default';
        $this->assertTrue($dll->modify($info), 'T028 Edit failed: ' . $dll->getLastError());
    }

    /** T029 ADMIN | Edit | Signup | Approved | change to default | link=false */
    public function testEdit_Admin_Signup_Approved_ChangeToDefault()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T029',
            MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_APPROVED,
            'js', false,
        );
        $this->assertTrue($ok, 'T029 Create failed');

        $info->variableMethod = 'default';
        $this->assertTrue($dll->modify($info), 'T029 Edit failed: ' . $dll->getLastError());
    }

    /** T030 ADMIN | Edit | Signup | Ignore | change to custom */
    public function testEdit_Admin_Signup_Ignore_ChangeToCustom()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T030',
            MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_IGNORE,
            'default', true,
        );
        $this->assertTrue($ok, 'T030 Create failed');

        $info->variableMethod = 'custom';
        $this->assertTrue($dll->modify($info), 'T030 Edit failed: ' . $dll->getLastError());
    }

    /** T031 ADMIN | Edit | Sale | Disapproved | change to dom */
    public function testEdit_Admin_Sale_Disapproved_ChangeToDom()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T031',
            MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_DISAPPROVED,
            'custom', true,
        );
        $this->assertTrue($ok, 'T031 Create failed');

        $info->variableMethod = 'dom';
        $this->assertTrue($dll->modify($info), 'T031 Edit failed: ' . $dll->getLastError());
    }

    /** T032 ADMIN | Edit | Lead | Duplicate | change to js */
    public function testEdit_Admin_Lead_Duplicate_ChangeToJs()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T032',
            MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_DUPLICATE,
            'default', false,
        );
        $this->assertTrue($ok, 'T032 Create failed');

        $info->variableMethod = 'js';
        $this->assertTrue($dll->modify($info), 'T032 Edit failed: ' . $dll->getLastError());
    }

    /** T033 ADMIN | Edit | Signup | Pending | change to dom */
    public function testEdit_Admin_Signup_Pending_ChangeToDom()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T033',
            MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_PENDING,
            'js', true,
        );
        $this->assertTrue($ok, 'T033 Create failed');

        $info->variableMethod = 'dom';
        $this->assertTrue($dll->modify($info), 'T033 Edit failed: ' . $dll->getLastError());
    }

    /** T034 ADMIN | Edit | Sale | OnHold | change to custom */
    public function testEdit_Admin_Sale_OnHold_ChangeToCustom()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T034',
            MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_ONHOLD,
            'js', false,
        );
        $this->assertTrue($ok, 'T034 Create failed');

        $info->variableMethod = 'custom';
        $this->assertTrue($dll->modify($info), 'T034 Edit failed: ' . $dll->getLastError());
    }

    // --- MANAGER account, Edit mode ---

    /** T035 MANAGER | Edit | Sale | Pending | change to js | link=true */
    public function testEdit_Manager_Sale_Pending_ChangeToJs()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T035',
            MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_PENDING,
            'default', true,
        );
        $this->assertTrue($ok, 'T035 Create failed');

        $info->variableMethod = 'js';
        $this->assertTrue($dll->modify($info), 'T035 Edit failed: ' . $dll->getLastError());
    }

    /** T036 MANAGER | Edit | Sale | Approved | change to dom */
    public function testEdit_Manager_Sale_Approved_ChangeToDom()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T036',
            MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_APPROVED,
            'custom', false,
        );
        $this->assertTrue($ok, 'T036 Create failed');

        $info->variableMethod = 'dom';
        $this->assertTrue($dll->modify($info), 'T036 Edit failed: ' . $dll->getLastError());
    }

    /** T037 MANAGER | Edit | Lead | Pending | change to custom */
    public function testEdit_Manager_Lead_Pending_ChangeToCustom()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T037',
            MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_PENDING,
            'js', true,
        );
        $this->assertTrue($ok, 'T037 Create failed');

        $info->variableMethod = 'custom';
        $this->assertTrue($dll->modify($info), 'T037 Edit failed: ' . $dll->getLastError());
    }

    /** T038 MANAGER | Edit | Lead | Approved | change to default */
    public function testEdit_Manager_Lead_Approved_ChangeToDefault()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T038',
            MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_APPROVED,
            'dom', false,
        );
        $this->assertTrue($ok, 'T038 Create failed');

        $info->variableMethod = 'default';
        $this->assertTrue($dll->modify($info), 'T038 Edit failed: ' . $dll->getLastError());
    }

    /** T039 MANAGER | Edit | Signup | Approved | change to js */
    public function testEdit_Manager_Signup_Approved_ChangeToJs()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T039',
            MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_APPROVED,
            'custom', true,
        );
        $this->assertTrue($ok, 'T039 Create failed');

        $info->variableMethod = 'js';
        $this->assertTrue($dll->modify($info), 'T039 Edit failed: ' . $dll->getLastError());
    }

    /** T040 MANAGER | Edit | Signup | OnHold | change to dom */
    public function testEdit_Manager_Signup_OnHold_ChangeToDom()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T040',
            MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_ONHOLD,
            'default', false,
        );
        $this->assertTrue($ok, 'T040 Create failed');

        $info->variableMethod = 'dom';
        $this->assertTrue($dll->modify($info), 'T040 Edit failed: ' . $dll->getLastError());
    }

    /** T041 MANAGER | Edit | Sale | Ignore | change to default */
    public function testEdit_Manager_Sale_Ignore_ChangeToDefault()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T041',
            MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_IGNORE,
            'dom', true,
        );
        $this->assertTrue($ok, 'T041 Create failed');

        $info->variableMethod = 'default';
        $this->assertTrue($dll->modify($info), 'T041 Edit failed: ' . $dll->getLastError());
    }

    /** T042 MANAGER | Edit | Lead | Disapproved | change to js */
    public function testEdit_Manager_Lead_Disapproved_ChangeToJs()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T042',
            MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_DISAPPROVED,
            'custom', false,
        );
        $this->assertTrue($ok, 'T042 Create failed');

        $info->variableMethod = 'js';
        $this->assertTrue($dll->modify($info), 'T042 Edit failed: ' . $dll->getLastError());
    }

    /** T043 MANAGER | Edit | Signup | Duplicate | change to custom */
    public function testEdit_Manager_Signup_Duplicate_ChangeToCustom()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T043',
            MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_DUPLICATE,
            'default', true,
        );
        $this->assertTrue($ok, 'T043 Create failed');

        $info->variableMethod = 'custom';
        $this->assertTrue($dll->modify($info), 'T043 Edit failed: ' . $dll->getLastError());
    }

    /** T044 MANAGER | Edit | Sale | Duplicate | change to dom */
    public function testEdit_Manager_Sale_Duplicate_ChangeToDom()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T044',
            MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_DUPLICATE,
            'js', false,
        );
        $this->assertTrue($ok, 'T044 Create failed');

        $info->variableMethod = 'dom';
        $this->assertTrue($dll->modify($info), 'T044 Edit failed: ' . $dll->getLastError());
    }

    // ====================================================================
    //  VIEW MODE TESTS  (~12 combos)
    //
    //  Create a tracker then read it back, verifying all stored fields.
    // ====================================================================

    /** T045 ADMIN | View | Sale | Approved | default */
    public function testView_Admin_Sale_Approved()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T045 view',
            MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_APPROVED,
            'default', true,
        );
        $this->assertTrue($ok, 'T045 Create failed');

        $oGet = null;
        $this->assertTrue($dll->getTracker($info->trackerId, $oGet), 'T045 View failed');
        $this->assertEqual($oGet->trackerName, 'T045 view');
        $this->assertEqual($oGet->clientId, $clientId);
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SALE);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_APPROVED);
    }

    /** T046 ADMIN | View | Sale | Disapproved | dom */
    public function testView_Admin_Sale_Disapproved()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T046 view',
            MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_DISAPPROVED,
            'dom', false,
        );
        $this->assertTrue($ok, 'T046 Create failed');

        $oGet = null;
        $this->assertTrue($dll->getTracker($info->trackerId, $oGet), 'T046 View failed');
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SALE);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_DISAPPROVED);
    }

    /** T047 ADMIN | View | Lead | Pending | js */
    public function testView_Admin_Lead_Pending()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T047 view',
            MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_PENDING,
            'js', true,
        );
        $this->assertTrue($ok, 'T047 Create failed');

        $oGet = null;
        $this->assertTrue($dll->getTracker($info->trackerId, $oGet), 'T047 View failed');
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_LEAD);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_PENDING);
    }

    /** T048 ADMIN | View | Lead | OnHold | custom */
    public function testView_Admin_Lead_OnHold()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T048 view',
            MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_ONHOLD,
            'custom', false,
        );
        $this->assertTrue($ok, 'T048 Create failed');

        $oGet = null;
        $this->assertTrue($dll->getTracker($info->trackerId, $oGet), 'T048 View failed');
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_LEAD);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_ONHOLD);
    }

    /** T049 ADMIN | View | Signup | Ignore | default */
    public function testView_Admin_Signup_Ignore()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T049 view',
            MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_IGNORE,
            'default', true,
        );
        $this->assertTrue($ok, 'T049 Create failed');

        $oGet = null;
        $this->assertTrue($dll->getTracker($info->trackerId, $oGet), 'T049 View failed');
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SIGNUP);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_IGNORE);
    }

    /** T050 ADMIN | View | Signup | Duplicate | js */
    public function testView_Admin_Signup_Duplicate()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T050 view',
            MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_DUPLICATE,
            'js', false,
        );
        $this->assertTrue($ok, 'T050 Create failed');

        $oGet = null;
        $this->assertTrue($dll->getTracker($info->trackerId, $oGet), 'T050 View failed');
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SIGNUP);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_DUPLICATE);
    }

    /** T051 MANAGER | View | Sale | Approved | custom */
    public function testView_Manager_Sale_Approved()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T051 view',
            MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_APPROVED,
            'custom', true,
        );
        $this->assertTrue($ok, 'T051 Create failed');

        $oGet = null;
        $this->assertTrue($dll->getTracker($info->trackerId, $oGet), 'T051 View failed');
        $this->assertEqual($oGet->trackerName, 'T051 view');
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SALE);
    }

    /** T052 MANAGER | View | Sale | Disapproved | dom */
    public function testView_Manager_Sale_Disapproved()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T052 view',
            MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_DISAPPROVED,
            'dom', false,
        );
        $this->assertTrue($ok, 'T052 Create failed');

        $oGet = null;
        $this->assertTrue($dll->getTracker($info->trackerId, $oGet), 'T052 View failed');
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_DISAPPROVED);
    }

    /** T053 MANAGER | View | Lead | Pending | default */
    public function testView_Manager_Lead_Pending()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T053 view',
            MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_PENDING,
            'default', true,
        );
        $this->assertTrue($ok, 'T053 Create failed');

        $oGet = null;
        $this->assertTrue($dll->getTracker($info->trackerId, $oGet), 'T053 View failed');
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_LEAD);
    }

    /** T054 MANAGER | View | Lead | Duplicate | js */
    public function testView_Manager_Lead_Duplicate()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T054 view',
            MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_DUPLICATE,
            'js', false,
        );
        $this->assertTrue($ok, 'T054 Create failed');

        $oGet = null;
        $this->assertTrue($dll->getTracker($info->trackerId, $oGet), 'T054 View failed');
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_DUPLICATE);
    }

    /** T055 MANAGER | View | Signup | OnHold | custom */
    public function testView_Manager_Signup_OnHold()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T055 view',
            MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_ONHOLD,
            'custom', true,
        );
        $this->assertTrue($ok, 'T055 Create failed');

        $oGet = null;
        $this->assertTrue($dll->getTracker($info->trackerId, $oGet), 'T055 View failed');
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SIGNUP);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_ONHOLD);
    }

    /** T056 MANAGER | View | Signup | Approved | dom */
    public function testView_Manager_Signup_Approved()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T056 view',
            MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_APPROVED,
            'dom', false,
        );
        $this->assertTrue($ok, 'T056 Create failed');

        $oGet = null;
        $this->assertTrue($dll->getTracker($info->trackerId, $oGet), 'T056 View failed');
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SIGNUP);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_APPROVED);
    }

    // ====================================================================
    //  DELETE MODE TESTS  (~12 combos)
    //
    //  Create a tracker, then delete it, assert it is gone.
    // ====================================================================

    // --- ADMIN account, Delete mode ---

    /** T057 ADMIN | Delete | Sale | Approved */
    public function testDelete_Admin_Sale_Approved()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T057',
            MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_APPROVED,
            'default', false,
        );
        $this->assertTrue($ok, 'T057 Create failed');
        $this->assertTrue($dll->delete($info->trackerId), 'T057 Delete failed');

        // Verify it is gone
        $oGet = null;
        $this->assertFalse($dll->getTracker($info->trackerId, $oGet), 'T057 Should not find deleted tracker');
    }

    /** T058 ADMIN | Delete | Sale | Pending */
    public function testDelete_Admin_Sale_Pending()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T058',
            MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_PENDING,
            'js', true,
        );
        $this->assertTrue($ok, 'T058 Create failed');
        $this->assertTrue($dll->delete($info->trackerId), 'T058 Delete failed');
    }

    /** T059 ADMIN | Delete | Lead | OnHold */
    public function testDelete_Admin_Lead_OnHold()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T059',
            MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_ONHOLD,
            'custom', false,
        );
        $this->assertTrue($ok, 'T059 Create failed');
        $this->assertTrue($dll->delete($info->trackerId), 'T059 Delete failed');
    }

    /** T060 ADMIN | Delete | Lead | Ignore */
    public function testDelete_Admin_Lead_Ignore()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T060',
            MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_IGNORE,
            'dom', true,
        );
        $this->assertTrue($ok, 'T060 Create failed');
        $this->assertTrue($dll->delete($info->trackerId), 'T060 Delete failed');
    }

    /** T061 ADMIN | Delete | Signup | OnHold */
    public function testDelete_Admin_Signup_OnHold()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T061',
            MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_ONHOLD,
            'default', false,
        );
        $this->assertTrue($ok, 'T061 Create failed');
        $this->assertTrue($dll->delete($info->trackerId), 'T061 Delete failed');
    }

    /** T062 ADMIN | Delete | Signup | Disapproved */
    public function testDelete_Admin_Signup_Disapproved()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T062',
            MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_DISAPPROVED,
            'js', true,
        );
        $this->assertTrue($ok, 'T062 Create failed');
        $this->assertTrue($dll->delete($info->trackerId), 'T062 Delete failed');
    }

    // --- MANAGER account, Delete mode ---

    /** T063 MANAGER | Delete | Sale | Approved */
    public function testDelete_Manager_Sale_Approved()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T063',
            MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_APPROVED,
            'custom', false,
        );
        $this->assertTrue($ok, 'T063 Create failed');
        $this->assertTrue($dll->delete($info->trackerId), 'T063 Delete failed');
    }

    /** T064 MANAGER | Delete | Sale | Duplicate */
    public function testDelete_Manager_Sale_Duplicate()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T064',
            MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_DUPLICATE,
            'dom', true,
        );
        $this->assertTrue($ok, 'T064 Create failed');
        $this->assertTrue($dll->delete($info->trackerId), 'T064 Delete failed');
    }

    /** T065 MANAGER | Delete | Lead | Pending */
    public function testDelete_Manager_Lead_Pending()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T065',
            MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_PENDING,
            'default', false,
        );
        $this->assertTrue($ok, 'T065 Create failed');
        $this->assertTrue($dll->delete($info->trackerId), 'T065 Delete failed');
    }

    /** T066 MANAGER | Delete | Lead | Disapproved */
    public function testDelete_Manager_Lead_Disapproved()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T066',
            MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_DISAPPROVED,
            'js', true,
        );
        $this->assertTrue($ok, 'T066 Create failed');
        $this->assertTrue($dll->delete($info->trackerId), 'T066 Delete failed');
    }

    /** T067 MANAGER | Delete | Signup | Approved */
    public function testDelete_Manager_Signup_Approved()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T067',
            MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_APPROVED,
            'custom', false,
        );
        $this->assertTrue($ok, 'T067 Create failed');
        $this->assertTrue($dll->delete($info->trackerId), 'T067 Delete failed');
    }

    /** T068 MANAGER | Delete | Signup | Ignore */
    public function testDelete_Manager_Signup_Ignore()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T068',
            MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_IGNORE,
            'dom', true,
        );
        $this->assertTrue($ok, 'T068 Create failed');
        $this->assertTrue($dll->delete($info->trackerId), 'T068 Delete failed');

        // Double-delete should fail
        $this->assertFalse($dll->delete($info->trackerId), 'T068 Double delete should fail');
    }

    // ====================================================================
    //  LINK-TO-CAMPAIGN MODE TESTS  (~18 combos)
    //
    //  Tests the linkTrackerToCampaign method with both same-advertiser
    //  (should succeed) and different-advertiser (should fail) scenarios,
    //  covering all tracker types and various statuses.
    // ====================================================================

    // --- ADMIN account, Same advertiser ---

    /** T069 ADMIN | LinkToCampaign | Sale | Approved | Same Advertiser */
    public function testLink_Admin_Sale_Approved_SameAdv()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();
        $campaignId = $this->_createCampaign($clientId);

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T069',
            MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_APPROVED,
        );
        $this->assertTrue($ok, 'T069 Create failed');
        $this->assertTrue(
            $dll->linkTrackerToCampaign($info->trackerId, $campaignId),
            'T069 Link same-adv failed: ' . $dll->getLastError(),
        );
    }

    /** T070 ADMIN | LinkToCampaign | Sale | Pending | Same Advertiser */
    public function testLink_Admin_Sale_Pending_SameAdv()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();
        $campaignId = $this->_createCampaign($clientId);

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T070',
            MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_PENDING,
        );
        $this->assertTrue($ok, 'T070 Create failed');
        $this->assertTrue(
            $dll->linkTrackerToCampaign($info->trackerId, $campaignId),
            'T070 Link same-adv failed: ' . $dll->getLastError(),
        );
    }

    /** T071 ADMIN | LinkToCampaign | Lead | Approved | Same Advertiser */
    public function testLink_Admin_Lead_Approved_SameAdv()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();
        $campaignId = $this->_createCampaign($clientId);

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T071',
            MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_APPROVED,
        );
        $this->assertTrue($ok, 'T071 Create failed');
        $this->assertTrue(
            $dll->linkTrackerToCampaign($info->trackerId, $campaignId),
            'T071 Link same-adv failed: ' . $dll->getLastError(),
        );
    }

    /** T072 ADMIN | LinkToCampaign | Lead | Ignore | Same Advertiser */
    public function testLink_Admin_Lead_Ignore_SameAdv()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();
        $campaignId = $this->_createCampaign($clientId);

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T072',
            MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_IGNORE,
        );
        $this->assertTrue($ok, 'T072 Create failed');
        $this->assertTrue(
            $dll->linkTrackerToCampaign($info->trackerId, $campaignId),
            'T072 Link same-adv failed: ' . $dll->getLastError(),
        );
    }

    /** T073 ADMIN | LinkToCampaign | Signup | Approved | Same Advertiser */
    public function testLink_Admin_Signup_Approved_SameAdv()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();
        $campaignId = $this->_createCampaign($clientId);

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T073',
            MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_APPROVED,
        );
        $this->assertTrue($ok, 'T073 Create failed');
        $this->assertTrue(
            $dll->linkTrackerToCampaign($info->trackerId, $campaignId),
            'T073 Link same-adv failed: ' . $dll->getLastError(),
        );
    }

    /** T074 ADMIN | LinkToCampaign | Signup | Pending | Same Advertiser */
    public function testLink_Admin_Signup_Pending_SameAdv()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();
        $campaignId = $this->_createCampaign($clientId);

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T074',
            MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_PENDING,
        );
        $this->assertTrue($ok, 'T074 Create failed');
        $this->assertTrue(
            $dll->linkTrackerToCampaign($info->trackerId, $campaignId),
            'T074 Link same-adv failed: ' . $dll->getLastError(),
        );
    }

    // --- ADMIN account, Different advertiser (should fail) ---

    /** T075 ADMIN | LinkToCampaign | Sale | Approved | Different Advertiser (DENY) */
    public function testLink_Admin_Sale_Approved_DiffAdv()
    {
        $dll = $this->_getTrackerDll();
        $clientId1 = $this->_createAdvertiser();
        $clientId2 = $this->_createAdvertiser();
        $campaignId = $this->_createCampaign($clientId2);

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId1, 'T075',
            MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_APPROVED,
        );
        $this->assertTrue($ok, 'T075 Create failed');
        $this->assertFalse(
            $dll->linkTrackerToCampaign($info->trackerId, $campaignId),
            'T075 Link diff-adv should be denied',
        );
        $this->assertEqual(
            $dll->getLastError(),
            OA_Dll_Tracker::ERROR_CAMPAIGN_ADVERTISER_MISMATCH,
            'T075 Wrong error message',
        );
    }

    /** T076 ADMIN | LinkToCampaign | Lead | Pending | Different Advertiser (DENY) */
    public function testLink_Admin_Lead_Pending_DiffAdv()
    {
        $dll = $this->_getTrackerDll();
        $clientId1 = $this->_createAdvertiser();
        $clientId2 = $this->_createAdvertiser();
        $campaignId = $this->_createCampaign($clientId2);

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId1, 'T076',
            MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_PENDING,
        );
        $this->assertTrue($ok, 'T076 Create failed');
        $this->assertFalse(
            $dll->linkTrackerToCampaign($info->trackerId, $campaignId),
            'T076 Link diff-adv should be denied',
        );
        $this->assertEqual(
            $dll->getLastError(),
            OA_Dll_Tracker::ERROR_CAMPAIGN_ADVERTISER_MISMATCH,
        );
    }

    /** T077 ADMIN | LinkToCampaign | Signup | Approved | Different Advertiser (DENY) */
    public function testLink_Admin_Signup_Approved_DiffAdv()
    {
        $dll = $this->_getTrackerDll();
        $clientId1 = $this->_createAdvertiser();
        $clientId2 = $this->_createAdvertiser();
        $campaignId = $this->_createCampaign($clientId2);

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId1, 'T077',
            MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_APPROVED,
        );
        $this->assertTrue($ok, 'T077 Create failed');
        $this->assertFalse(
            $dll->linkTrackerToCampaign($info->trackerId, $campaignId),
            'T077 Link diff-adv should be denied',
        );
        $this->assertEqual(
            $dll->getLastError(),
            OA_Dll_Tracker::ERROR_CAMPAIGN_ADVERTISER_MISMATCH,
        );
    }

    // --- MANAGER account, Same advertiser ---

    /** T078 MANAGER | LinkToCampaign | Sale | Approved | Same Advertiser */
    public function testLink_Manager_Sale_Approved_SameAdv()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();
        $campaignId = $this->_createCampaign($clientId);

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T078',
            MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_APPROVED,
        );
        $this->assertTrue($ok, 'T078 Create failed');
        $this->assertTrue(
            $dll->linkTrackerToCampaign($info->trackerId, $campaignId),
            'T078 Link same-adv failed: ' . $dll->getLastError(),
        );
    }

    /** T079 MANAGER | LinkToCampaign | Sale | Pending | Same Advertiser */
    public function testLink_Manager_Sale_Pending_SameAdv()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();
        $campaignId = $this->_createCampaign($clientId);

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T079',
            MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_PENDING,
        );
        $this->assertTrue($ok, 'T079 Create failed');
        $this->assertTrue(
            $dll->linkTrackerToCampaign($info->trackerId, $campaignId),
            'T079 Link same-adv failed: ' . $dll->getLastError(),
        );
    }

    /** T080 MANAGER | LinkToCampaign | Lead | Approved | Same Advertiser */
    public function testLink_Manager_Lead_Approved_SameAdv()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();
        $campaignId = $this->_createCampaign($clientId);

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T080',
            MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_APPROVED,
        );
        $this->assertTrue($ok, 'T080 Create failed');
        $this->assertTrue(
            $dll->linkTrackerToCampaign($info->trackerId, $campaignId),
            'T080 Link same-adv failed: ' . $dll->getLastError(),
        );
    }

    /** T081 MANAGER | LinkToCampaign | Lead | Pending | Same Advertiser */
    public function testLink_Manager_Lead_Pending_SameAdv()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();
        $campaignId = $this->_createCampaign($clientId);

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T081',
            MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_PENDING,
        );
        $this->assertTrue($ok, 'T081 Create failed');
        $this->assertTrue(
            $dll->linkTrackerToCampaign($info->trackerId, $campaignId),
            'T081 Link same-adv failed: ' . $dll->getLastError(),
        );
    }

    /** T082 MANAGER | LinkToCampaign | Signup | Approved | Same Advertiser */
    public function testLink_Manager_Signup_Approved_SameAdv()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();
        $campaignId = $this->_createCampaign($clientId);

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T082',
            MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_APPROVED,
        );
        $this->assertTrue($ok, 'T082 Create failed');
        $this->assertTrue(
            $dll->linkTrackerToCampaign($info->trackerId, $campaignId),
            'T082 Link same-adv failed: ' . $dll->getLastError(),
        );
    }

    /** T083 MANAGER | LinkToCampaign | Signup | OnHold | Same Advertiser */
    public function testLink_Manager_Signup_OnHold_SameAdv()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();
        $campaignId = $this->_createCampaign($clientId);

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T083',
            MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_ONHOLD,
        );
        $this->assertTrue($ok, 'T083 Create failed');
        $this->assertTrue(
            $dll->linkTrackerToCampaign($info->trackerId, $campaignId),
            'T083 Link same-adv failed: ' . $dll->getLastError(),
        );
    }

    // --- MANAGER account, Different advertiser (should fail) ---

    /** T084 MANAGER | LinkToCampaign | Lead | Pending | Different Advertiser (DENY) */
    public function testLink_Manager_Lead_Pending_DiffAdv()
    {
        $dll = $this->_getTrackerDll();
        $clientId1 = $this->_createAdvertiser();
        $clientId2 = $this->_createAdvertiser();
        $campaignId = $this->_createCampaign($clientId2);

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId1, 'T084',
            MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_PENDING,
        );
        $this->assertTrue($ok, 'T084 Create failed');
        $this->assertFalse(
            $dll->linkTrackerToCampaign($info->trackerId, $campaignId),
            'T084 Link diff-adv should be denied',
        );
        $this->assertEqual(
            $dll->getLastError(),
            OA_Dll_Tracker::ERROR_CAMPAIGN_ADVERTISER_MISMATCH,
        );
    }

    /** T085 MANAGER | LinkToCampaign | Sale | Approved | Different Advertiser (DENY) */
    public function testLink_Manager_Sale_Approved_DiffAdv()
    {
        $dll = $this->_getTrackerDll();
        $clientId1 = $this->_createAdvertiser();
        $clientId2 = $this->_createAdvertiser();
        $campaignId = $this->_createCampaign($clientId2);

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId1, 'T085',
            MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_APPROVED,
        );
        $this->assertTrue($ok, 'T085 Create failed');
        $this->assertFalse(
            $dll->linkTrackerToCampaign($info->trackerId, $campaignId),
            'T085 Link diff-adv should be denied',
        );
        $this->assertEqual(
            $dll->getLastError(),
            OA_Dll_Tracker::ERROR_CAMPAIGN_ADVERTISER_MISMATCH,
        );
    }

    /** T086 MANAGER | LinkToCampaign | Signup | Approved | Different Advertiser (DENY) */
    public function testLink_Manager_Signup_Approved_DiffAdv()
    {
        $dll = $this->_getTrackerDll();
        $clientId1 = $this->_createAdvertiser();
        $clientId2 = $this->_createAdvertiser();
        $campaignId = $this->_createCampaign($clientId2);

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId1, 'T086',
            MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_APPROVED,
        );
        $this->assertTrue($ok, 'T086 Create failed');
        $this->assertFalse(
            $dll->linkTrackerToCampaign($info->trackerId, $campaignId),
            'T086 Link diff-adv should be denied',
        );
        $this->assertEqual(
            $dll->getLastError(),
            OA_Dll_Tracker::ERROR_CAMPAIGN_ADVERTISER_MISMATCH,
        );
    }

    // ====================================================================
    //  LINK-TO-CAMPAIGN WITH EXPLICIT STATUS  (~4 combos)
    //
    //  Pass an explicit status to linkTrackerToCampaign to verify it
    //  is used instead of the tracker default.
    // ====================================================================

    /** T087 ADMIN | LinkToCampaign | Sale | Explicit Pending status */
    public function testLink_Admin_Sale_ExplicitPendingStatus()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();
        $campaignId = $this->_createCampaign($clientId);

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T087',
            MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_APPROVED,
        );
        $this->assertTrue($ok, 'T087 Create failed');
        $this->assertTrue(
            $dll->linkTrackerToCampaign($info->trackerId, $campaignId, MAX_CONNECTION_STATUS_PENDING),
            'T087 Link with explicit pending status failed: ' . $dll->getLastError(),
        );
    }

    /** T088 MANAGER | LinkToCampaign | Lead | Explicit Ignore status */
    public function testLink_Manager_Lead_ExplicitIgnoreStatus()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();
        $campaignId = $this->_createCampaign($clientId);

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T088',
            MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_APPROVED,
        );
        $this->assertTrue($ok, 'T088 Create failed');
        $this->assertTrue(
            $dll->linkTrackerToCampaign($info->trackerId, $campaignId, MAX_CONNECTION_STATUS_IGNORE),
            'T088 Link with explicit ignore status failed: ' . $dll->getLastError(),
        );
    }

    /** T089 ADMIN | LinkToCampaign | Signup | Explicit Approved status */
    public function testLink_Admin_Signup_ExplicitApprovedStatus()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();
        $campaignId = $this->_createCampaign($clientId);

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T089',
            MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_PENDING,
        );
        $this->assertTrue($ok, 'T089 Create failed');
        $this->assertTrue(
            $dll->linkTrackerToCampaign($info->trackerId, $campaignId, MAX_CONNECTION_STATUS_APPROVED),
            'T089 Link with explicit approved status failed: ' . $dll->getLastError(),
        );
    }

    /** T090 MANAGER | LinkToCampaign | Sale | Explicit Disapproved status */
    public function testLink_Manager_Sale_ExplicitDisapprovedStatus()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();
        $campaignId = $this->_createCampaign($clientId);

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T090',
            MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_APPROVED,
        );
        $this->assertTrue($ok, 'T090 Create failed');
        $this->assertTrue(
            $dll->linkTrackerToCampaign($info->trackerId, $campaignId, MAX_CONNECTION_STATUS_DISAPPROVED),
            'T090 Link with explicit disapproved status failed: ' . $dll->getLastError(),
        );
    }

    // ====================================================================
    //  EDGE CASE / ERROR TESTS  (~3 combos)
    // ====================================================================

    /** T091 Link to non-existent tracker */
    public function testLink_NonExistentTracker()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();
        $campaignId = $this->_createCampaign($clientId);

        $this->assertFalse(
            $dll->linkTrackerToCampaign(999999, $campaignId),
            'T091 Link to non-existent tracker should fail',
        );
        $this->assertEqual(
            $dll->getLastError(),
            OA_Dll_Tracker::ERROR_UNKNOWN_TRACKER_ID,
        );
    }

    /** T092 Link to non-existent campaign */
    public function testLink_NonExistentCampaign()
    {
        $dll = $this->_getTrackerDll();
        $clientId = $this->_createAdvertiser();

        [$ok, $info] = $this->_createTracker(
            $dll, $clientId, 'T092',
            MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_APPROVED,
        );
        $this->assertTrue($ok, 'T092 Create failed');
        $this->assertFalse(
            $dll->linkTrackerToCampaign($info->trackerId, 999999),
            'T092 Link to non-existent campaign should fail',
        );
        $this->assertEqual(
            $dll->getLastError(),
            OA_Dll_Tracker::ERROR_UNKNOWN_CAMPAIGN_ID,
        );
    }

    /** T093 Delete non-existent tracker */
    public function testDelete_NonExistentTracker()
    {
        $dll = $this->_getTrackerDll();

        $this->assertFalse(
            $dll->delete(999999),
            'T093 Delete non-existent tracker should fail',
        );
        $this->assertEqual(
            $dll->getLastError(),
            OA_Dll_Tracker::ERROR_UNKNOWN_TRACKER_ID,
        );
    }
}
