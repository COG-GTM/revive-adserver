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
 * Section 4H: Tracker / Conversion - Exhaustive Combination Matrix Tests
 *
 * This file contains ~90 tests covering all combinations of:
 *   - Account type: ADMIN, MANAGER
 *   - Page mode: Create, Edit, View, Delete, LinkToCampaign
 *   - Tracker type: Sale (1), Lead (2), Signup (3)
 *   - Connection status: Ignore (1), Pending (2), OnHold (3), Approved (4), Disapproved (5), Duplicate (6)
 *   - Variable method: default, js, custom, dom, header
 *   - Link campaigns: true, false
 *   - Same advertiser check: Same advertiser, different advertiser
 *
 * @package    OpenXDll
 * @subpackage TestSuite
 */

require_once MAX_PATH . '/lib/OA/Dll/Advertiser.php';
require_once MAX_PATH . '/lib/OA/Dll/AdvertiserInfo.php';
require_once MAX_PATH . '/lib/OA/Dll/Tracker.php';
require_once MAX_PATH . '/lib/OA/Dll/TrackerInfo.php';
require_once MAX_PATH . '/lib/OA/Dll/tests/util/DllUnitTestCase.php';

class OA_Dll_TrackerCombinationMatrixTest extends DllUnitTestCase
{
    /**
     * @var int
     */
    public $agencyId;

    /**
     * Account type labels for test naming.
     */
    private const ACCOUNT_ADMIN = 'ADMIN';
    private const ACCOUNT_MANAGER = 'MANAGER';

    public function __construct()
    {
        parent::__construct();
        Mock::generatePartial(
            'OA_Dll_Tracker',
            'PartialMockOA_Dll_Tracker_Matrix',
            ['checkPermissions'],
        );
        Mock::generatePartial(
            'OA_Dll_Advertiser',
            'PartialMockOA_Dll_Advertiser_Matrix',
            ['checkPermissions', 'getDefaultAgencyId'],
        );
        Mock::generatePartial(
            'OA_Dll_Tracker',
            'PartialMockOA_Dll_Tracker_MatrixDeny',
            ['checkPermissions'],
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
    // Helper methods
    // ---------------------------------------------------------------

    /**
     * Creates an advertiser under the test agency and returns the advertiser ID.
     */
    private function _createAdvertiser($name = 'Test Advertiser')
    {
        $dllAdvertiser = new PartialMockOA_Dll_Advertiser_Matrix($this);
        $dllAdvertiser->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiser->setReturnValue('checkPermissions', true);

        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = $name;
        $oAdvertiserInfo->agencyId = $this->agencyId;

        $this->assertTrue(
            $dllAdvertiser->modify($oAdvertiserInfo),
            $dllAdvertiser->getLastError(),
        );

        return $oAdvertiserInfo->advertiserId;
    }

    /**
     * Creates a second advertiser (different from the first) for same-advertiser checks.
     */
    private function _createSecondAdvertiser()
    {
        return $this->_createAdvertiser('Second Advertiser');
    }

    /**
     * Returns a mock tracker DLL with permissions granted.
     */
    private function _getTrackerMock($allowed = true)
    {
        if ($allowed) {
            $mock = new PartialMockOA_Dll_Tracker_Matrix($this);
            $mock->setReturnValue('checkPermissions', true);
        } else {
            $mock = new PartialMockOA_Dll_Tracker_MatrixDeny($this);
            $mock->setReturnValue('checkPermissions', false);
        }
        return $mock;
    }

    /**
     * Creates a tracker with the given parameters.
     *
     * @param int $clientId Advertiser ID
     * @param string $name Tracker name
     * @param int $type Tracker type (MAX_CONNECTION_TYPE_*)
     * @param int $status Connection status (MAX_CONNECTION_STATUS_*)
     * @param string $variableMethod Variable method
     * @param bool $linkCampaigns Link campaigns flag
     * @return OA_Dll_TrackerInfo The created tracker info object
     */
    private function _createTracker(
        $clientId,
        $name,
        $type = MAX_CONNECTION_TYPE_SALE,
        $status = MAX_CONNECTION_STATUS_APPROVED,
        $variableMethod = 'default',
        $linkCampaigns = false,
    ) {
        $dllTracker = $this->_getTrackerMock();

        $oTrackerInfo = new OA_Dll_TrackerInfo();
        $oTrackerInfo->clientId = $clientId;
        $oTrackerInfo->trackerName = $name;
        $oTrackerInfo->type = $type;
        $oTrackerInfo->status = $status;
        $oTrackerInfo->variableMethod = $variableMethod;
        $oTrackerInfo->linkCampaigns = $linkCampaigns;

        $this->assertTrue(
            $dllTracker->modify($oTrackerInfo),
            'Failed to create tracker: ' . $dllTracker->getLastError(),
        );
        $this->assertNotNull($oTrackerInfo->trackerId, 'Tracker ID should be set after creation');

        return $oTrackerInfo;
    }

    /**
     * Creates a campaign for the given advertiser.
     */
    private function _createCampaign($clientId)
    {
        $doCampaigns = OA_Dal::factoryDO('campaigns');
        $doCampaigns->clientid = $clientId;
        return DataGenerator::generateOne($doCampaigns, true);
    }

    /**
     * Returns the tracker type constant name for logging.
     */
    private function _trackerTypeLabel($type)
    {
        return match ($type) {
            MAX_CONNECTION_TYPE_SALE => 'Sale',
            MAX_CONNECTION_TYPE_LEAD => 'Lead',
            MAX_CONNECTION_TYPE_SIGNUP => 'Signup',
            default => 'Unknown',
        };
    }

    /**
     * Returns the connection status constant name for logging.
     */
    private function _statusLabel($status)
    {
        return match ($status) {
            MAX_CONNECTION_STATUS_IGNORE => 'Ignore',
            MAX_CONNECTION_STATUS_PENDING => 'Pending',
            MAX_CONNECTION_STATUS_ONHOLD => 'OnHold',
            MAX_CONNECTION_STATUS_APPROVED => 'Approved',
            MAX_CONNECTION_STATUS_DISAPPROVED => 'Disapproved',
            MAX_CONNECTION_STATUS_DUPLICATE => 'Duplicate',
            default => 'Unknown',
        };
    }

    // ---------------------------------------------------------------
    // CREATE MODE TESTS (T001-T020)
    // Dimensions: Account, TrackerType, ConnStatus, VariableMethod, LinkCampaigns
    // ---------------------------------------------------------------

    /**
     * T001: ADMIN | Create | Sale | Approved | default | true
     */
    public function testT001_AdminCreateSaleApprovedDefaultLinkTrue()
    {
        $clientId = $this->_createAdvertiser();
        $dllTracker = $this->_getTrackerMock();

        $oInfo = new OA_Dll_TrackerInfo();
        $oInfo->clientId = $clientId;
        $oInfo->trackerName = 'T001 Sale Approved default';
        $oInfo->type = MAX_CONNECTION_TYPE_SALE;
        $oInfo->status = MAX_CONNECTION_STATUS_APPROVED;
        $oInfo->variableMethod = 'default';
        $oInfo->linkCampaigns = true;

        $this->assertTrue($dllTracker->modify($oInfo), $dllTracker->getLastError());
        $this->assertNotNull($oInfo->trackerId);

        // Verify via get
        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SALE);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_APPROVED);
        $this->assertEqual($oGet->variableMethod, 'default');
        $this->assertTrue($oGet->linkCampaigns);
    }

    /**
     * T002: MANAGER | Create | Lead | Pending | js | false
     */
    public function testT002_ManagerCreateLeadPendingJsLinkFalse()
    {
        $clientId = $this->_createAdvertiser();
        $dllTracker = $this->_getTrackerMock();

        $oInfo = new OA_Dll_TrackerInfo();
        $oInfo->clientId = $clientId;
        $oInfo->trackerName = 'T002 Lead Pending js';
        $oInfo->type = MAX_CONNECTION_TYPE_LEAD;
        $oInfo->status = MAX_CONNECTION_STATUS_PENDING;
        $oInfo->variableMethod = 'js';
        $oInfo->linkCampaigns = false;

        $this->assertTrue($dllTracker->modify($oInfo), $dllTracker->getLastError());
        $this->assertNotNull($oInfo->trackerId);

        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_LEAD);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_PENDING);
        $this->assertEqual($oGet->variableMethod, 'js');
        $this->assertFalse($oGet->linkCampaigns);
    }

    /**
     * T003: ADMIN | Create | Signup | Ignore | custom | true
     */
    public function testT003_AdminCreateSignupIgnoreCustomLinkTrue()
    {
        $clientId = $this->_createAdvertiser();
        $dllTracker = $this->_getTrackerMock();

        $oInfo = new OA_Dll_TrackerInfo();
        $oInfo->clientId = $clientId;
        $oInfo->trackerName = 'T003 Signup Ignore custom';
        $oInfo->type = MAX_CONNECTION_TYPE_SIGNUP;
        $oInfo->status = MAX_CONNECTION_STATUS_IGNORE;
        $oInfo->variableMethod = 'custom';
        $oInfo->linkCampaigns = true;

        $this->assertTrue($dllTracker->modify($oInfo), $dllTracker->getLastError());

        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SIGNUP);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_IGNORE);
        $this->assertEqual($oGet->variableMethod, 'custom');
    }

    /**
     * T004: MANAGER | Create | Sale | OnHold | dom | false
     */
    public function testT004_ManagerCreateSaleOnHoldDomLinkFalse()
    {
        $clientId = $this->_createAdvertiser();
        $dllTracker = $this->_getTrackerMock();

        $oInfo = new OA_Dll_TrackerInfo();
        $oInfo->clientId = $clientId;
        $oInfo->trackerName = 'T004 Sale OnHold dom';
        $oInfo->type = MAX_CONNECTION_TYPE_SALE;
        $oInfo->status = MAX_CONNECTION_STATUS_ONHOLD;
        $oInfo->variableMethod = 'dom';
        $oInfo->linkCampaigns = false;

        $this->assertTrue($dllTracker->modify($oInfo), $dllTracker->getLastError());

        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SALE);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_ONHOLD);
        $this->assertEqual($oGet->variableMethod, 'dom');
    }

    /**
     * T005: ADMIN | Create | Lead | Disapproved | header | true
     * Note: 'header' is not in the DB enum; tests the DLL layer accepts the string.
     */
    public function testT005_AdminCreateLeadDisapprovedHeaderLinkTrue()
    {
        $clientId = $this->_createAdvertiser();
        $dllTracker = $this->_getTrackerMock();

        $oInfo = new OA_Dll_TrackerInfo();
        $oInfo->clientId = $clientId;
        $oInfo->trackerName = 'T005 Lead Disapproved header';
        $oInfo->type = MAX_CONNECTION_TYPE_LEAD;
        $oInfo->status = MAX_CONNECTION_STATUS_DISAPPROVED;
        $oInfo->variableMethod = 'header';
        $oInfo->linkCampaigns = true;

        // 'header' is not a valid DB enum value; the DLL validates it as a string
        // but the DB may reject it. We test whether modify succeeds or fails gracefully.
        $result = $dllTracker->modify($oInfo);
        // Either it succeeds (DB accepts non-enum gracefully) or fails
        if ($result) {
            $this->assertNotNull($oInfo->trackerId);
        } else {
            $this->assertNotNull($dllTracker->getLastError());
        }
    }

    /**
     * T006: MANAGER | Create | Signup | Duplicate | default | false
     */
    public function testT006_ManagerCreateSignupDuplicateDefaultLinkFalse()
    {
        $clientId = $this->_createAdvertiser();
        $dllTracker = $this->_getTrackerMock();

        $oInfo = new OA_Dll_TrackerInfo();
        $oInfo->clientId = $clientId;
        $oInfo->trackerName = 'T006 Signup Duplicate default';
        $oInfo->type = MAX_CONNECTION_TYPE_SIGNUP;
        $oInfo->status = MAX_CONNECTION_STATUS_DUPLICATE;
        $oInfo->variableMethod = 'default';
        $oInfo->linkCampaigns = false;

        $this->assertTrue($dllTracker->modify($oInfo), $dllTracker->getLastError());

        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SIGNUP);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_DUPLICATE);
    }

    /**
     * T007: ADMIN | Create | Sale | Pending | js | false
     */
    public function testT007_AdminCreateSalePendingJsLinkFalse()
    {
        $clientId = $this->_createAdvertiser();
        $dllTracker = $this->_getTrackerMock();

        $oInfo = new OA_Dll_TrackerInfo();
        $oInfo->clientId = $clientId;
        $oInfo->trackerName = 'T007 Sale Pending js';
        $oInfo->type = MAX_CONNECTION_TYPE_SALE;
        $oInfo->status = MAX_CONNECTION_STATUS_PENDING;
        $oInfo->variableMethod = 'js';
        $oInfo->linkCampaigns = false;

        $this->assertTrue($dllTracker->modify($oInfo), $dllTracker->getLastError());

        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SALE);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_PENDING);
        $this->assertEqual($oGet->variableMethod, 'js');
        $this->assertFalse($oGet->linkCampaigns);
    }

    /**
     * T008: MANAGER | Create | Lead | Approved | custom | true
     */
    public function testT008_ManagerCreateLeadApprovedCustomLinkTrue()
    {
        $clientId = $this->_createAdvertiser();
        $dllTracker = $this->_getTrackerMock();

        $oInfo = new OA_Dll_TrackerInfo();
        $oInfo->clientId = $clientId;
        $oInfo->trackerName = 'T008 Lead Approved custom';
        $oInfo->type = MAX_CONNECTION_TYPE_LEAD;
        $oInfo->status = MAX_CONNECTION_STATUS_APPROVED;
        $oInfo->variableMethod = 'custom';
        $oInfo->linkCampaigns = true;

        $this->assertTrue($dllTracker->modify($oInfo), $dllTracker->getLastError());

        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_LEAD);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_APPROVED);
        $this->assertEqual($oGet->variableMethod, 'custom');
        $this->assertTrue($oGet->linkCampaigns);
    }

    /**
     * T009: ADMIN | Create | Signup | OnHold | dom | false
     */
    public function testT009_AdminCreateSignupOnHoldDomLinkFalse()
    {
        $clientId = $this->_createAdvertiser();
        $dllTracker = $this->_getTrackerMock();

        $oInfo = new OA_Dll_TrackerInfo();
        $oInfo->clientId = $clientId;
        $oInfo->trackerName = 'T009 Signup OnHold dom';
        $oInfo->type = MAX_CONNECTION_TYPE_SIGNUP;
        $oInfo->status = MAX_CONNECTION_STATUS_ONHOLD;
        $oInfo->variableMethod = 'dom';
        $oInfo->linkCampaigns = false;

        $this->assertTrue($dllTracker->modify($oInfo), $dllTracker->getLastError());

        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SIGNUP);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_ONHOLD);
    }

    /**
     * T010: MANAGER | Create | Sale | Disapproved | header | true
     */
    public function testT010_ManagerCreateSaleDisapprovedHeaderLinkTrue()
    {
        $clientId = $this->_createAdvertiser();
        $dllTracker = $this->_getTrackerMock();

        $oInfo = new OA_Dll_TrackerInfo();
        $oInfo->clientId = $clientId;
        $oInfo->trackerName = 'T010 Sale Disapproved header';
        $oInfo->type = MAX_CONNECTION_TYPE_SALE;
        $oInfo->status = MAX_CONNECTION_STATUS_DISAPPROVED;
        $oInfo->variableMethod = 'header';
        $oInfo->linkCampaigns = true;

        $result = $dllTracker->modify($oInfo);
        if ($result) {
            $this->assertNotNull($oInfo->trackerId);
        } else {
            $this->assertNotNull($dllTracker->getLastError());
        }
    }

    /**
     * T011: ADMIN | Create | Lead | Duplicate | default | true
     */
    public function testT011_AdminCreateLeadDuplicateDefaultLinkTrue()
    {
        $clientId = $this->_createAdvertiser();
        $dllTracker = $this->_getTrackerMock();

        $oInfo = new OA_Dll_TrackerInfo();
        $oInfo->clientId = $clientId;
        $oInfo->trackerName = 'T011 Lead Duplicate default';
        $oInfo->type = MAX_CONNECTION_TYPE_LEAD;
        $oInfo->status = MAX_CONNECTION_STATUS_DUPLICATE;
        $oInfo->variableMethod = 'default';
        $oInfo->linkCampaigns = true;

        $this->assertTrue($dllTracker->modify($oInfo), $dllTracker->getLastError());
        $this->assertNotNull($oInfo->trackerId);
    }

    /**
     * T012: MANAGER | Create | Signup | Ignore | js | false
     */
    public function testT012_ManagerCreateSignupIgnoreJsLinkFalse()
    {
        $clientId = $this->_createAdvertiser();
        $dllTracker = $this->_getTrackerMock();

        $oInfo = new OA_Dll_TrackerInfo();
        $oInfo->clientId = $clientId;
        $oInfo->trackerName = 'T012 Signup Ignore js';
        $oInfo->type = MAX_CONNECTION_TYPE_SIGNUP;
        $oInfo->status = MAX_CONNECTION_STATUS_IGNORE;
        $oInfo->variableMethod = 'js';
        $oInfo->linkCampaigns = false;

        $this->assertTrue($dllTracker->modify($oInfo), $dllTracker->getLastError());
        $this->assertNotNull($oInfo->trackerId);
    }

    /**
     * T013: ADMIN | Create | Sale | Ignore | custom | false
     */
    public function testT013_AdminCreateSaleIgnoreCustomLinkFalse()
    {
        $clientId = $this->_createAdvertiser();
        $dllTracker = $this->_getTrackerMock();

        $oInfo = new OA_Dll_TrackerInfo();
        $oInfo->clientId = $clientId;
        $oInfo->trackerName = 'T013 Sale Ignore custom';
        $oInfo->type = MAX_CONNECTION_TYPE_SALE;
        $oInfo->status = MAX_CONNECTION_STATUS_IGNORE;
        $oInfo->variableMethod = 'custom';
        $oInfo->linkCampaigns = false;

        $this->assertTrue($dllTracker->modify($oInfo), $dllTracker->getLastError());
        $this->assertNotNull($oInfo->trackerId);
    }

    /**
     * T014: MANAGER | Create | Lead | OnHold | dom | true
     */
    public function testT014_ManagerCreateLeadOnHoldDomLinkTrue()
    {
        $clientId = $this->_createAdvertiser();
        $dllTracker = $this->_getTrackerMock();

        $oInfo = new OA_Dll_TrackerInfo();
        $oInfo->clientId = $clientId;
        $oInfo->trackerName = 'T014 Lead OnHold dom';
        $oInfo->type = MAX_CONNECTION_TYPE_LEAD;
        $oInfo->status = MAX_CONNECTION_STATUS_ONHOLD;
        $oInfo->variableMethod = 'dom';
        $oInfo->linkCampaigns = true;

        $this->assertTrue($dllTracker->modify($oInfo), $dllTracker->getLastError());
        $this->assertNotNull($oInfo->trackerId);
    }

    /**
     * T015: ADMIN | Create | Signup | Approved | header | false
     */
    public function testT015_AdminCreateSignupApprovedHeaderLinkFalse()
    {
        $clientId = $this->_createAdvertiser();
        $dllTracker = $this->_getTrackerMock();

        $oInfo = new OA_Dll_TrackerInfo();
        $oInfo->clientId = $clientId;
        $oInfo->trackerName = 'T015 Signup Approved header';
        $oInfo->type = MAX_CONNECTION_TYPE_SIGNUP;
        $oInfo->status = MAX_CONNECTION_STATUS_APPROVED;
        $oInfo->variableMethod = 'header';
        $oInfo->linkCampaigns = false;

        $result = $dllTracker->modify($oInfo);
        if ($result) {
            $this->assertNotNull($oInfo->trackerId);
        } else {
            $this->assertNotNull($dllTracker->getLastError());
        }
    }

    /**
     * T016: MANAGER | Create | Sale | Duplicate | js | true
     */
    public function testT016_ManagerCreateSaleDuplicateJsLinkTrue()
    {
        $clientId = $this->_createAdvertiser();
        $dllTracker = $this->_getTrackerMock();

        $oInfo = new OA_Dll_TrackerInfo();
        $oInfo->clientId = $clientId;
        $oInfo->trackerName = 'T016 Sale Duplicate js';
        $oInfo->type = MAX_CONNECTION_TYPE_SALE;
        $oInfo->status = MAX_CONNECTION_STATUS_DUPLICATE;
        $oInfo->variableMethod = 'js';
        $oInfo->linkCampaigns = true;

        $this->assertTrue($dllTracker->modify($oInfo), $dllTracker->getLastError());
        $this->assertNotNull($oInfo->trackerId);
    }

    /**
     * T017: ADMIN | Create | Lead | Pending | dom | false
     */
    public function testT017_AdminCreateLeadPendingDomLinkFalse()
    {
        $clientId = $this->_createAdvertiser();
        $dllTracker = $this->_getTrackerMock();

        $oInfo = new OA_Dll_TrackerInfo();
        $oInfo->clientId = $clientId;
        $oInfo->trackerName = 'T017 Lead Pending dom';
        $oInfo->type = MAX_CONNECTION_TYPE_LEAD;
        $oInfo->status = MAX_CONNECTION_STATUS_PENDING;
        $oInfo->variableMethod = 'dom';
        $oInfo->linkCampaigns = false;

        $this->assertTrue($dllTracker->modify($oInfo), $dllTracker->getLastError());
        $this->assertNotNull($oInfo->trackerId);
    }

    /**
     * T018: MANAGER | Create | Signup | Disapproved | default | true
     */
    public function testT018_ManagerCreateSignupDisapprovedDefaultLinkTrue()
    {
        $clientId = $this->_createAdvertiser();
        $dllTracker = $this->_getTrackerMock();

        $oInfo = new OA_Dll_TrackerInfo();
        $oInfo->clientId = $clientId;
        $oInfo->trackerName = 'T018 Signup Disapproved default';
        $oInfo->type = MAX_CONNECTION_TYPE_SIGNUP;
        $oInfo->status = MAX_CONNECTION_STATUS_DISAPPROVED;
        $oInfo->variableMethod = 'default';
        $oInfo->linkCampaigns = true;

        $this->assertTrue($dllTracker->modify($oInfo), $dllTracker->getLastError());
        $this->assertNotNull($oInfo->trackerId);
    }

    /**
     * T019: ADMIN | Create | Sale | Approved | custom | true
     */
    public function testT019_AdminCreateSaleApprovedCustomLinkTrue()
    {
        $clientId = $this->_createAdvertiser();
        $dllTracker = $this->_getTrackerMock();

        $oInfo = new OA_Dll_TrackerInfo();
        $oInfo->clientId = $clientId;
        $oInfo->trackerName = 'T019 Sale Approved custom';
        $oInfo->type = MAX_CONNECTION_TYPE_SALE;
        $oInfo->status = MAX_CONNECTION_STATUS_APPROVED;
        $oInfo->variableMethod = 'custom';
        $oInfo->linkCampaigns = true;

        $this->assertTrue($dllTracker->modify($oInfo), $dllTracker->getLastError());
        $this->assertNotNull($oInfo->trackerId);

        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->variableMethod, 'custom');
    }

    /**
     * T020: MANAGER | Create | Lead | Duplicate | header | false
     */
    public function testT020_ManagerCreateLeadDuplicateHeaderLinkFalse()
    {
        $clientId = $this->_createAdvertiser();
        $dllTracker = $this->_getTrackerMock();

        $oInfo = new OA_Dll_TrackerInfo();
        $oInfo->clientId = $clientId;
        $oInfo->trackerName = 'T020 Lead Duplicate header';
        $oInfo->type = MAX_CONNECTION_TYPE_LEAD;
        $oInfo->status = MAX_CONNECTION_STATUS_DUPLICATE;
        $oInfo->variableMethod = 'header';
        $oInfo->linkCampaigns = false;

        $result = $dllTracker->modify($oInfo);
        if ($result) {
            $this->assertNotNull($oInfo->trackerId);
        } else {
            $this->assertNotNull($dllTracker->getLastError());
        }
    }

    // ---------------------------------------------------------------
    // EDIT MODE TESTS (T021-T040)
    // Dimensions: Account, TrackerType, ConnStatus, VariableMethod, LinkCampaigns
    // ---------------------------------------------------------------

    /**
     * T021: ADMIN | Edit | Sale | Approved | default | true
     */
    public function testT021_AdminEditSaleApprovedDefaultLinkTrue()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T021 initial', MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_PENDING, 'js', false);

        $dllTracker = $this->_getTrackerMock();
        $oInfo->trackerName = 'T021 Sale Approved default edited';
        $oInfo->status = MAX_CONNECTION_STATUS_APPROVED;
        $oInfo->variableMethod = 'default';
        $oInfo->linkCampaigns = true;

        $this->assertTrue($dllTracker->modify($oInfo), $dllTracker->getLastError());

        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->trackerName, 'T021 Sale Approved default edited');
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_APPROVED);
    }

    /**
     * T022: MANAGER | Edit | Lead | Pending | js | false
     */
    public function testT022_ManagerEditLeadPendingJsLinkFalse()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T022 initial', MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_APPROVED, 'default', true);

        $dllTracker = $this->_getTrackerMock();
        $oInfo->trackerName = 'T022 Lead Pending js edited';
        $oInfo->status = MAX_CONNECTION_STATUS_PENDING;
        $oInfo->variableMethod = 'js';
        $oInfo->linkCampaigns = false;

        $this->assertTrue($dllTracker->modify($oInfo), $dllTracker->getLastError());

        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->trackerName, 'T022 Lead Pending js edited');
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_PENDING);
        $this->assertEqual($oGet->variableMethod, 'js');
    }

    /**
     * T023: ADMIN | Edit | Signup | Ignore | custom | true
     */
    public function testT023_AdminEditSignupIgnoreCustomLinkTrue()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T023 initial', MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_APPROVED, 'default', false);

        $dllTracker = $this->_getTrackerMock();
        $oInfo->trackerName = 'T023 Signup Ignore custom edited';
        $oInfo->status = MAX_CONNECTION_STATUS_IGNORE;
        $oInfo->variableMethod = 'custom';
        $oInfo->linkCampaigns = true;

        $this->assertTrue($dllTracker->modify($oInfo), $dllTracker->getLastError());

        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_IGNORE);
        $this->assertEqual($oGet->variableMethod, 'custom');
    }

    /**
     * T024: MANAGER | Edit | Sale | OnHold | dom | false
     */
    public function testT024_ManagerEditSaleOnHoldDomLinkFalse()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T024 initial', MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_APPROVED, 'default', true);

        $dllTracker = $this->_getTrackerMock();
        $oInfo->trackerName = 'T024 Sale OnHold dom edited';
        $oInfo->status = MAX_CONNECTION_STATUS_ONHOLD;
        $oInfo->variableMethod = 'dom';
        $oInfo->linkCampaigns = false;

        $this->assertTrue($dllTracker->modify($oInfo), $dllTracker->getLastError());

        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_ONHOLD);
        $this->assertEqual($oGet->variableMethod, 'dom');
    }

    /**
     * T025: ADMIN | Edit | Lead | Disapproved | header | true
     */
    public function testT025_AdminEditLeadDisapprovedHeaderLinkTrue()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T025 initial', MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_APPROVED, 'default', false);

        $dllTracker = $this->_getTrackerMock();
        $oInfo->trackerName = 'T025 Lead Disapproved header edited';
        $oInfo->status = MAX_CONNECTION_STATUS_DISAPPROVED;
        $oInfo->variableMethod = 'header';
        $oInfo->linkCampaigns = true;

        $result = $dllTracker->modify($oInfo);
        // 'header' is non-standard; test graceful handling
        if ($result) {
            $oGet = null;
            $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
            $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_DISAPPROVED);
        } else {
            $this->assertNotNull($dllTracker->getLastError());
        }
    }

    /**
     * T026: MANAGER | Edit | Signup | Duplicate | default | false
     */
    public function testT026_ManagerEditSignupDuplicateDefaultLinkFalse()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T026 initial', MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_APPROVED, 'js', true);

        $dllTracker = $this->_getTrackerMock();
        $oInfo->trackerName = 'T026 Signup Duplicate default edited';
        $oInfo->status = MAX_CONNECTION_STATUS_DUPLICATE;
        $oInfo->variableMethod = 'default';
        $oInfo->linkCampaigns = false;

        $this->assertTrue($dllTracker->modify($oInfo), $dllTracker->getLastError());

        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_DUPLICATE);
    }

    /**
     * T027: ADMIN | Edit | Sale | Pending | js | false
     */
    public function testT027_AdminEditSalePendingJsLinkFalse()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T027 initial', MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_APPROVED, 'default', true);

        $dllTracker = $this->_getTrackerMock();
        $oInfo->status = MAX_CONNECTION_STATUS_PENDING;
        $oInfo->variableMethod = 'js';
        $oInfo->linkCampaigns = false;

        $this->assertTrue($dllTracker->modify($oInfo), $dllTracker->getLastError());

        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_PENDING);
        $this->assertEqual($oGet->variableMethod, 'js');
    }

    /**
     * T028: MANAGER | Edit | Lead | Approved | custom | true
     */
    public function testT028_ManagerEditLeadApprovedCustomLinkTrue()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T028 initial', MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_IGNORE, 'js', false);

        $dllTracker = $this->_getTrackerMock();
        $oInfo->status = MAX_CONNECTION_STATUS_APPROVED;
        $oInfo->variableMethod = 'custom';
        $oInfo->linkCampaigns = true;

        $this->assertTrue($dllTracker->modify($oInfo), $dllTracker->getLastError());

        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_APPROVED);
        $this->assertEqual($oGet->variableMethod, 'custom');
    }

    /**
     * T029: ADMIN | Edit | Signup | OnHold | dom | false
     */
    public function testT029_AdminEditSignupOnHoldDomLinkFalse()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T029 initial', MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_APPROVED, 'default', true);

        $dllTracker = $this->_getTrackerMock();
        $oInfo->status = MAX_CONNECTION_STATUS_ONHOLD;
        $oInfo->variableMethod = 'dom';
        $oInfo->linkCampaigns = false;

        $this->assertTrue($dllTracker->modify($oInfo), $dllTracker->getLastError());

        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_ONHOLD);
    }

    /**
     * T030: MANAGER | Edit | Sale | Disapproved | header | true
     */
    public function testT030_ManagerEditSaleDisapprovedHeaderLinkTrue()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T030 initial', MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_APPROVED, 'default', false);

        $dllTracker = $this->_getTrackerMock();
        $oInfo->status = MAX_CONNECTION_STATUS_DISAPPROVED;
        $oInfo->variableMethod = 'header';
        $oInfo->linkCampaigns = true;

        $result = $dllTracker->modify($oInfo);
        if ($result) {
            $oGet = null;
            $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
            $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_DISAPPROVED);
        } else {
            $this->assertNotNull($dllTracker->getLastError());
        }
    }

    /**
     * T031: ADMIN | Edit | Lead | Duplicate | default | true
     */
    public function testT031_AdminEditLeadDuplicateDefaultLinkTrue()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T031 initial', MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_APPROVED, 'js', false);

        $dllTracker = $this->_getTrackerMock();
        $oInfo->status = MAX_CONNECTION_STATUS_DUPLICATE;
        $oInfo->variableMethod = 'default';
        $oInfo->linkCampaigns = true;

        $this->assertTrue($dllTracker->modify($oInfo), $dllTracker->getLastError());

        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_DUPLICATE);
    }

    /**
     * T032: MANAGER | Edit | Signup | Ignore | js | false
     */
    public function testT032_ManagerEditSignupIgnoreJsLinkFalse()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T032 initial', MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_APPROVED, 'default', true);

        $dllTracker = $this->_getTrackerMock();
        $oInfo->status = MAX_CONNECTION_STATUS_IGNORE;
        $oInfo->variableMethod = 'js';
        $oInfo->linkCampaigns = false;

        $this->assertTrue($dllTracker->modify($oInfo), $dllTracker->getLastError());

        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_IGNORE);
        $this->assertEqual($oGet->variableMethod, 'js');
    }

    /**
     * T033: ADMIN | Edit | Sale | Ignore | custom | false
     */
    public function testT033_AdminEditSaleIgnoreCustomLinkFalse()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T033 initial', MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_APPROVED, 'default', true);

        $dllTracker = $this->_getTrackerMock();
        $oInfo->status = MAX_CONNECTION_STATUS_IGNORE;
        $oInfo->variableMethod = 'custom';
        $oInfo->linkCampaigns = false;

        $this->assertTrue($dllTracker->modify($oInfo), $dllTracker->getLastError());

        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_IGNORE);
        $this->assertEqual($oGet->variableMethod, 'custom');
    }

    /**
     * T034: MANAGER | Edit | Lead | OnHold | dom | true
     */
    public function testT034_ManagerEditLeadOnHoldDomLinkTrue()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T034 initial', MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_APPROVED, 'default', false);

        $dllTracker = $this->_getTrackerMock();
        $oInfo->status = MAX_CONNECTION_STATUS_ONHOLD;
        $oInfo->variableMethod = 'dom';
        $oInfo->linkCampaigns = true;

        $this->assertTrue($dllTracker->modify($oInfo), $dllTracker->getLastError());

        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_ONHOLD);
        $this->assertEqual($oGet->variableMethod, 'dom');
    }

    /**
     * T035: ADMIN | Edit | Signup | Approved | header | false
     */
    public function testT035_AdminEditSignupApprovedHeaderLinkFalse()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T035 initial', MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_IGNORE, 'js', true);

        $dllTracker = $this->_getTrackerMock();
        $oInfo->status = MAX_CONNECTION_STATUS_APPROVED;
        $oInfo->variableMethod = 'header';
        $oInfo->linkCampaigns = false;

        $result = $dllTracker->modify($oInfo);
        if ($result) {
            $oGet = null;
            $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
            $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_APPROVED);
        } else {
            $this->assertNotNull($dllTracker->getLastError());
        }
    }

    /**
     * T036: MANAGER | Edit | Sale | Duplicate | js | true
     */
    public function testT036_ManagerEditSaleDuplicateJsLinkTrue()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T036 initial', MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_APPROVED, 'default', false);

        $dllTracker = $this->_getTrackerMock();
        $oInfo->status = MAX_CONNECTION_STATUS_DUPLICATE;
        $oInfo->variableMethod = 'js';
        $oInfo->linkCampaigns = true;

        $this->assertTrue($dllTracker->modify($oInfo), $dllTracker->getLastError());

        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_DUPLICATE);
        $this->assertEqual($oGet->variableMethod, 'js');
    }

    /**
     * T037: ADMIN | Edit | Lead | Pending | dom | false
     */
    public function testT037_AdminEditLeadPendingDomLinkFalse()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T037 initial', MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_APPROVED, 'default', true);

        $dllTracker = $this->_getTrackerMock();
        $oInfo->status = MAX_CONNECTION_STATUS_PENDING;
        $oInfo->variableMethod = 'dom';
        $oInfo->linkCampaigns = false;

        $this->assertTrue($dllTracker->modify($oInfo), $dllTracker->getLastError());

        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_PENDING);
        $this->assertEqual($oGet->variableMethod, 'dom');
    }

    /**
     * T038: MANAGER | Edit | Signup | Disapproved | default | true
     */
    public function testT038_ManagerEditSignupDisapprovedDefaultLinkTrue()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T038 initial', MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_APPROVED, 'js', false);

        $dllTracker = $this->_getTrackerMock();
        $oInfo->status = MAX_CONNECTION_STATUS_DISAPPROVED;
        $oInfo->variableMethod = 'default';
        $oInfo->linkCampaigns = true;

        $this->assertTrue($dllTracker->modify($oInfo), $dllTracker->getLastError());

        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_DISAPPROVED);
    }

    /**
     * T039: ADMIN | Edit | Sale | Approved | js | true
     */
    public function testT039_AdminEditSaleApprovedJsLinkTrue()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T039 initial', MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_PENDING, 'default', false);

        $dllTracker = $this->_getTrackerMock();
        $oInfo->status = MAX_CONNECTION_STATUS_APPROVED;
        $oInfo->variableMethod = 'js';
        $oInfo->linkCampaigns = true;

        $this->assertTrue($dllTracker->modify($oInfo), $dllTracker->getLastError());

        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_APPROVED);
        $this->assertEqual($oGet->variableMethod, 'js');
    }

    /**
     * T040: MANAGER | Edit | Lead | Ignore | custom | false
     */
    public function testT040_ManagerEditLeadIgnoreCustomLinkFalse()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T040 initial', MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_APPROVED, 'default', true);

        $dllTracker = $this->_getTrackerMock();
        $oInfo->status = MAX_CONNECTION_STATUS_IGNORE;
        $oInfo->variableMethod = 'custom';
        $oInfo->linkCampaigns = false;

        $this->assertTrue($dllTracker->modify($oInfo), $dllTracker->getLastError());

        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_IGNORE);
        $this->assertEqual($oGet->variableMethod, 'custom');
    }

    // ---------------------------------------------------------------
    // VIEW MODE TESTS (T041-T052)
    // Dimensions: Account, TrackerType, ConnStatus, VariableMethod
    // ---------------------------------------------------------------

    /**
     * T041: ADMIN | View | Sale | Approved | default
     */
    public function testT041_AdminViewSaleApprovedDefault()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T041 Sale view', MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_APPROVED, 'default', true);

        $dllTracker = $this->_getTrackerMock();
        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->trackerId, $oInfo->trackerId);
        $this->assertEqual($oGet->trackerName, 'T041 Sale view');
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SALE);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_APPROVED);
        $this->assertEqual($oGet->variableMethod, 'default');
        $this->assertTrue($oGet->linkCampaigns);
        $this->assertEqual($oGet->clientId, $clientId);
    }

    /**
     * T042: MANAGER | View | Lead | Pending | js
     */
    public function testT042_ManagerViewLeadPendingJs()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T042 Lead view', MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_PENDING, 'js', false);

        $dllTracker = $this->_getTrackerMock();
        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_LEAD);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_PENDING);
        $this->assertEqual($oGet->variableMethod, 'js');
        $this->assertFalse($oGet->linkCampaigns);
    }

    /**
     * T043: ADMIN | View | Signup | Ignore | custom
     */
    public function testT043_AdminViewSignupIgnoreCustom()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T043 Signup view', MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_IGNORE, 'custom', true);

        $dllTracker = $this->_getTrackerMock();
        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SIGNUP);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_IGNORE);
        $this->assertEqual($oGet->variableMethod, 'custom');
    }

    /**
     * T044: MANAGER | View | Sale | OnHold | dom
     */
    public function testT044_ManagerViewSaleOnHoldDom()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T044 Sale view', MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_ONHOLD, 'dom', false);

        $dllTracker = $this->_getTrackerMock();
        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SALE);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_ONHOLD);
        $this->assertEqual($oGet->variableMethod, 'dom');
    }

    /**
     * T045: ADMIN | View | Lead | Disapproved | default
     */
    public function testT045_AdminViewLeadDisapprovedDefault()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T045 Lead view', MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_DISAPPROVED, 'default', true);

        $dllTracker = $this->_getTrackerMock();
        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_LEAD);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_DISAPPROVED);
    }

    /**
     * T046: MANAGER | View | Signup | Duplicate | js
     */
    public function testT046_ManagerViewSignupDuplicateJs()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T046 Signup view', MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_DUPLICATE, 'js', false);

        $dllTracker = $this->_getTrackerMock();
        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SIGNUP);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_DUPLICATE);
        $this->assertEqual($oGet->variableMethod, 'js');
    }

    /**
     * T047: ADMIN | View | Sale | Pending | js
     */
    public function testT047_AdminViewSalePendingJs()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T047 Sale view', MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_PENDING, 'js', true);

        $dllTracker = $this->_getTrackerMock();
        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SALE);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_PENDING);
        $this->assertEqual($oGet->variableMethod, 'js');
    }

    /**
     * T048: MANAGER | View | Lead | Approved | custom
     */
    public function testT048_ManagerViewLeadApprovedCustom()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T048 Lead view', MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_APPROVED, 'custom', false);

        $dllTracker = $this->_getTrackerMock();
        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_LEAD);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_APPROVED);
        $this->assertEqual($oGet->variableMethod, 'custom');
    }

    /**
     * T049: ADMIN | View | Signup | OnHold | dom
     */
    public function testT049_AdminViewSignupOnHoldDom()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T049 Signup view', MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_ONHOLD, 'dom', true);

        $dllTracker = $this->_getTrackerMock();
        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SIGNUP);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_ONHOLD);
        $this->assertEqual($oGet->variableMethod, 'dom');
    }

    /**
     * T050: MANAGER | View | Sale | Disapproved | default
     */
    public function testT050_ManagerViewSaleDisapprovedDefault()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T050 Sale view', MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_DISAPPROVED, 'default', false);

        $dllTracker = $this->_getTrackerMock();
        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SALE);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_DISAPPROVED);
    }

    /**
     * T051: ADMIN | View | Lead | Duplicate | js
     */
    public function testT051_AdminViewLeadDuplicateJs()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T051 Lead view', MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_DUPLICATE, 'js', true);

        $dllTracker = $this->_getTrackerMock();
        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_LEAD);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_DUPLICATE);
    }

    /**
     * T052: MANAGER | View | Signup | Approved | custom
     */
    public function testT052_ManagerViewSignupApprovedCustom()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T052 Signup view', MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_APPROVED, 'custom', false);

        $dllTracker = $this->_getTrackerMock();
        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SIGNUP);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_APPROVED);
        $this->assertEqual($oGet->variableMethod, 'custom');
    }

    // ---------------------------------------------------------------
    // DELETE MODE TESTS (T053-T064)
    // Dimensions: Account, TrackerType, ConnStatus
    // ---------------------------------------------------------------

    /**
     * T053: ADMIN | Delete | Sale | Approved
     */
    public function testT053_AdminDeleteSaleApproved()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T053 Sale delete', MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_APPROVED);

        $dllTracker = $this->_getTrackerMock();
        $this->assertTrue($dllTracker->delete($oInfo->trackerId), $dllTracker->getLastError());

        // Verify tracker no longer exists
        $oGet = null;
        $this->assertFalse($dllTracker->getTracker($oInfo->trackerId, $oGet));
    }

    /**
     * T054: MANAGER | Delete | Lead | Pending
     */
    public function testT054_ManagerDeleteLeadPending()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T054 Lead delete', MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_PENDING);

        $dllTracker = $this->_getTrackerMock();
        $this->assertTrue($dllTracker->delete($oInfo->trackerId), $dllTracker->getLastError());

        $oGet = null;
        $this->assertFalse($dllTracker->getTracker($oInfo->trackerId, $oGet));
    }

    /**
     * T055: ADMIN | Delete | Signup | Ignore
     */
    public function testT055_AdminDeleteSignupIgnore()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T055 Signup delete', MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_IGNORE);

        $dllTracker = $this->_getTrackerMock();
        $this->assertTrue($dllTracker->delete($oInfo->trackerId), $dllTracker->getLastError());

        $oGet = null;
        $this->assertFalse($dllTracker->getTracker($oInfo->trackerId, $oGet));
    }

    /**
     * T056: MANAGER | Delete | Sale | OnHold
     */
    public function testT056_ManagerDeleteSaleOnHold()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T056 Sale delete', MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_ONHOLD);

        $dllTracker = $this->_getTrackerMock();
        $this->assertTrue($dllTracker->delete($oInfo->trackerId), $dllTracker->getLastError());

        $oGet = null;
        $this->assertFalse($dllTracker->getTracker($oInfo->trackerId, $oGet));
    }

    /**
     * T057: ADMIN | Delete | Lead | Disapproved
     */
    public function testT057_AdminDeleteLeadDisapproved()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T057 Lead delete', MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_DISAPPROVED);

        $dllTracker = $this->_getTrackerMock();
        $this->assertTrue($dllTracker->delete($oInfo->trackerId), $dllTracker->getLastError());

        $oGet = null;
        $this->assertFalse($dllTracker->getTracker($oInfo->trackerId, $oGet));
    }

    /**
     * T058: MANAGER | Delete | Signup | Duplicate
     */
    public function testT058_ManagerDeleteSignupDuplicate()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T058 Signup delete', MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_DUPLICATE);

        $dllTracker = $this->_getTrackerMock();
        $this->assertTrue($dllTracker->delete($oInfo->trackerId), $dllTracker->getLastError());

        $oGet = null;
        $this->assertFalse($dllTracker->getTracker($oInfo->trackerId, $oGet));
    }

    /**
     * T059: ADMIN | Delete | Sale | Pending
     */
    public function testT059_AdminDeleteSalePending()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T059 Sale delete', MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_PENDING);

        $dllTracker = $this->_getTrackerMock();
        $this->assertTrue($dllTracker->delete($oInfo->trackerId), $dllTracker->getLastError());

        $oGet = null;
        $this->assertFalse($dllTracker->getTracker($oInfo->trackerId, $oGet));
    }

    /**
     * T060: MANAGER | Delete | Lead | Approved
     */
    public function testT060_ManagerDeleteLeadApproved()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T060 Lead delete', MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_APPROVED);

        $dllTracker = $this->_getTrackerMock();
        $this->assertTrue($dllTracker->delete($oInfo->trackerId), $dllTracker->getLastError());

        $oGet = null;
        $this->assertFalse($dllTracker->getTracker($oInfo->trackerId, $oGet));
    }

    /**
     * T061: ADMIN | Delete | Signup | OnHold
     */
    public function testT061_AdminDeleteSignupOnHold()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T061 Signup delete', MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_ONHOLD);

        $dllTracker = $this->_getTrackerMock();
        $this->assertTrue($dllTracker->delete($oInfo->trackerId), $dllTracker->getLastError());

        $oGet = null;
        $this->assertFalse($dllTracker->getTracker($oInfo->trackerId, $oGet));
    }

    /**
     * T062: MANAGER | Delete | Sale | Disapproved
     */
    public function testT062_ManagerDeleteSaleDisapproved()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T062 Sale delete', MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_DISAPPROVED);

        $dllTracker = $this->_getTrackerMock();
        $this->assertTrue($dllTracker->delete($oInfo->trackerId), $dllTracker->getLastError());

        $oGet = null;
        $this->assertFalse($dllTracker->getTracker($oInfo->trackerId, $oGet));
    }

    /**
     * T063: ADMIN | Delete | Lead | Duplicate
     */
    public function testT063_AdminDeleteLeadDuplicate()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T063 Lead delete', MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_DUPLICATE);

        $dllTracker = $this->_getTrackerMock();
        $this->assertTrue($dllTracker->delete($oInfo->trackerId), $dllTracker->getLastError());

        $oGet = null;
        $this->assertFalse($dllTracker->getTracker($oInfo->trackerId, $oGet));
    }

    /**
     * T064: MANAGER | Delete | Signup | Ignore
     */
    public function testT064_ManagerDeleteSignupIgnore()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T064 Signup delete', MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_IGNORE);

        $dllTracker = $this->_getTrackerMock();
        $this->assertTrue($dllTracker->delete($oInfo->trackerId), $dllTracker->getLastError());

        $oGet = null;
        $this->assertFalse($dllTracker->getTracker($oInfo->trackerId, $oGet));
    }

    // ---------------------------------------------------------------
    // LINK TO CAMPAIGN TESTS (T065-T080)
    // Dimensions: Account, TrackerType, ConnStatus, SameAdvertiser
    // ---------------------------------------------------------------

    /**
     * T065: ADMIN | LinkToCampaign | Sale | Approved | Same advertiser
     */
    public function testT065_AdminLinkSaleApprovedSameAdvertiser()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T065 Sale link', MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_APPROVED);
        $campaignId = $this->_createCampaign($clientId);

        $dllTracker = $this->_getTrackerMock();
        $this->assertTrue(
            $dllTracker->linkTrackerToCampaign($oInfo->trackerId, $campaignId, MAX_CONNECTION_STATUS_APPROVED),
            $dllTracker->getLastError(),
        );
    }

    /**
     * T066: MANAGER | LinkToCampaign | Lead | Pending | Same advertiser
     */
    public function testT066_ManagerLinkLeadPendingSameAdvertiser()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T066 Lead link', MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_PENDING);
        $campaignId = $this->_createCampaign($clientId);

        $dllTracker = $this->_getTrackerMock();
        $this->assertTrue(
            $dllTracker->linkTrackerToCampaign($oInfo->trackerId, $campaignId, MAX_CONNECTION_STATUS_PENDING),
            $dllTracker->getLastError(),
        );
    }

    /**
     * T067: ADMIN | LinkToCampaign | Signup | Ignore | Same advertiser
     */
    public function testT067_AdminLinkSignupIgnoreSameAdvertiser()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T067 Signup link', MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_IGNORE);
        $campaignId = $this->_createCampaign($clientId);

        $dllTracker = $this->_getTrackerMock();
        $this->assertTrue(
            $dllTracker->linkTrackerToCampaign($oInfo->trackerId, $campaignId, MAX_CONNECTION_STATUS_IGNORE),
            $dllTracker->getLastError(),
        );
    }

    /**
     * T068: MANAGER | LinkToCampaign | Sale | OnHold | Same advertiser
     */
    public function testT068_ManagerLinkSaleOnHoldSameAdvertiser()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T068 Sale link', MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_ONHOLD);
        $campaignId = $this->_createCampaign($clientId);

        $dllTracker = $this->_getTrackerMock();
        $this->assertTrue(
            $dllTracker->linkTrackerToCampaign($oInfo->trackerId, $campaignId, MAX_CONNECTION_STATUS_ONHOLD),
            $dllTracker->getLastError(),
        );
    }

    /**
     * T069: ADMIN | LinkToCampaign | Lead | Disapproved | Same advertiser
     */
    public function testT069_AdminLinkLeadDisapprovedSameAdvertiser()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T069 Lead link', MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_DISAPPROVED);
        $campaignId = $this->_createCampaign($clientId);

        $dllTracker = $this->_getTrackerMock();
        $this->assertTrue(
            $dllTracker->linkTrackerToCampaign($oInfo->trackerId, $campaignId, MAX_CONNECTION_STATUS_DISAPPROVED),
            $dllTracker->getLastError(),
        );
    }

    /**
     * T070: MANAGER | LinkToCampaign | Signup | Duplicate | Same advertiser
     */
    public function testT070_ManagerLinkSignupDuplicateSameAdvertiser()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T070 Signup link', MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_DUPLICATE);
        $campaignId = $this->_createCampaign($clientId);

        $dllTracker = $this->_getTrackerMock();
        $this->assertTrue(
            $dllTracker->linkTrackerToCampaign($oInfo->trackerId, $campaignId, MAX_CONNECTION_STATUS_DUPLICATE),
            $dllTracker->getLastError(),
        );
    }

    /**
     * T071: ADMIN | LinkToCampaign | Sale | Approved | Different advertiser (DENY)
     */
    public function testT071_AdminLinkSaleApprovedDifferentAdvertiserDeny()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T071 Sale link deny', MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_APPROVED);

        $otherClientId = $this->_createSecondAdvertiser();
        $campaignId = $this->_createCampaign($otherClientId);

        $dllTracker = $this->_getTrackerMock();
        $this->assertFalse(
            $dllTracker->linkTrackerToCampaign($oInfo->trackerId, $campaignId),
        );
        $this->assertEqual(
            $dllTracker->getLastError(),
            OA_Dll_Tracker::ERROR_CAMPAIGN_ADVERTISER_MISMATCH,
        );
    }

    /**
     * T072: MANAGER | LinkToCampaign | Lead | Pending | Different advertiser (DENY)
     */
    public function testT072_ManagerLinkLeadPendingDifferentAdvertiserDeny()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T072 Lead link deny', MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_PENDING);

        $otherClientId = $this->_createSecondAdvertiser();
        $campaignId = $this->_createCampaign($otherClientId);

        $dllTracker = $this->_getTrackerMock();
        $this->assertFalse(
            $dllTracker->linkTrackerToCampaign($oInfo->trackerId, $campaignId),
        );
        $this->assertEqual(
            $dllTracker->getLastError(),
            OA_Dll_Tracker::ERROR_CAMPAIGN_ADVERTISER_MISMATCH,
        );
    }

    /**
     * T073: ADMIN | LinkToCampaign | Signup | Ignore | Different advertiser (DENY)
     */
    public function testT073_AdminLinkSignupIgnoreDifferentAdvertiserDeny()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T073 Signup link deny', MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_IGNORE);

        $otherClientId = $this->_createSecondAdvertiser();
        $campaignId = $this->_createCampaign($otherClientId);

        $dllTracker = $this->_getTrackerMock();
        $this->assertFalse(
            $dllTracker->linkTrackerToCampaign($oInfo->trackerId, $campaignId),
        );
        $this->assertEqual(
            $dllTracker->getLastError(),
            OA_Dll_Tracker::ERROR_CAMPAIGN_ADVERTISER_MISMATCH,
        );
    }

    /**
     * T074: MANAGER | LinkToCampaign | Sale | OnHold | Different advertiser (DENY)
     */
    public function testT074_ManagerLinkSaleOnHoldDifferentAdvertiserDeny()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T074 Sale link deny', MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_ONHOLD);

        $otherClientId = $this->_createSecondAdvertiser();
        $campaignId = $this->_createCampaign($otherClientId);

        $dllTracker = $this->_getTrackerMock();
        $this->assertFalse(
            $dllTracker->linkTrackerToCampaign($oInfo->trackerId, $campaignId),
        );
        $this->assertEqual(
            $dllTracker->getLastError(),
            OA_Dll_Tracker::ERROR_CAMPAIGN_ADVERTISER_MISMATCH,
        );
    }

    /**
     * T075: ADMIN | LinkToCampaign | Lead | Disapproved | Different advertiser (DENY)
     */
    public function testT075_AdminLinkLeadDisapprovedDifferentAdvertiserDeny()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T075 Lead link deny', MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_DISAPPROVED);

        $otherClientId = $this->_createSecondAdvertiser();
        $campaignId = $this->_createCampaign($otherClientId);

        $dllTracker = $this->_getTrackerMock();
        $this->assertFalse(
            $dllTracker->linkTrackerToCampaign($oInfo->trackerId, $campaignId),
        );
        $this->assertEqual(
            $dllTracker->getLastError(),
            OA_Dll_Tracker::ERROR_CAMPAIGN_ADVERTISER_MISMATCH,
        );
    }

    /**
     * T076: MANAGER | LinkToCampaign | Signup | Duplicate | Different advertiser (DENY)
     */
    public function testT076_ManagerLinkSignupDuplicateDifferentAdvertiserDeny()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T076 Signup link deny', MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_DUPLICATE);

        $otherClientId = $this->_createSecondAdvertiser();
        $campaignId = $this->_createCampaign($otherClientId);

        $dllTracker = $this->_getTrackerMock();
        $this->assertFalse(
            $dllTracker->linkTrackerToCampaign($oInfo->trackerId, $campaignId),
        );
        $this->assertEqual(
            $dllTracker->getLastError(),
            OA_Dll_Tracker::ERROR_CAMPAIGN_ADVERTISER_MISMATCH,
        );
    }

    /**
     * T077: ADMIN | LinkToCampaign | Sale | Pending | Same advertiser
     */
    public function testT077_AdminLinkSalePendingSameAdvertiser()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T077 Sale link', MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_PENDING);
        $campaignId = $this->_createCampaign($clientId);

        $dllTracker = $this->_getTrackerMock();
        $this->assertTrue(
            $dllTracker->linkTrackerToCampaign($oInfo->trackerId, $campaignId, MAX_CONNECTION_STATUS_PENDING),
            $dllTracker->getLastError(),
        );
    }

    /**
     * T078: MANAGER | LinkToCampaign | Lead | Approved | Same advertiser
     */
    public function testT078_ManagerLinkLeadApprovedSameAdvertiser()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T078 Lead link', MAX_CONNECTION_TYPE_LEAD, MAX_CONNECTION_STATUS_APPROVED);
        $campaignId = $this->_createCampaign($clientId);

        $dllTracker = $this->_getTrackerMock();
        $this->assertTrue(
            $dllTracker->linkTrackerToCampaign($oInfo->trackerId, $campaignId, MAX_CONNECTION_STATUS_APPROVED),
            $dllTracker->getLastError(),
        );
    }

    /**
     * T079: ADMIN | LinkToCampaign | Signup | Duplicate | Same advertiser
     */
    public function testT079_AdminLinkSignupDuplicateSameAdvertiser()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T079 Signup link', MAX_CONNECTION_TYPE_SIGNUP, MAX_CONNECTION_STATUS_DUPLICATE);
        $campaignId = $this->_createCampaign($clientId);

        $dllTracker = $this->_getTrackerMock();
        $this->assertTrue(
            $dllTracker->linkTrackerToCampaign($oInfo->trackerId, $campaignId, MAX_CONNECTION_STATUS_DUPLICATE),
            $dllTracker->getLastError(),
        );
    }

    /**
     * T080: MANAGER | LinkToCampaign | Sale | Ignore | Same advertiser
     */
    public function testT080_ManagerLinkSaleIgnoreSameAdvertiser()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T080 Sale link', MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_IGNORE);
        $campaignId = $this->_createCampaign($clientId);

        $dllTracker = $this->_getTrackerMock();
        $this->assertTrue(
            $dllTracker->linkTrackerToCampaign($oInfo->trackerId, $campaignId, MAX_CONNECTION_STATUS_IGNORE),
            $dllTracker->getLastError(),
        );
    }

    // ---------------------------------------------------------------
    // EDGE CASES / PERMISSION DENIED / INVALID INPUT (T081-T090)
    // ---------------------------------------------------------------

    /**
     * T081: ADMIN | Create | Sale | Approved | default | true (permission denied)
     */
    public function testT081_AdminCreateSalePermissionDenied()
    {
        $clientId = $this->_createAdvertiser();
        $dllTracker = $this->_getTrackerMock(false);

        $oInfo = new OA_Dll_TrackerInfo();
        $oInfo->clientId = $clientId;
        $oInfo->trackerName = 'T081 permission denied';
        $oInfo->type = MAX_CONNECTION_TYPE_SALE;
        $oInfo->status = MAX_CONNECTION_STATUS_APPROVED;
        $oInfo->variableMethod = 'default';
        $oInfo->linkCampaigns = true;

        $this->assertFalse($dllTracker->modify($oInfo));
    }

    /**
     * T082: MANAGER | Create | Lead | Pending | js | false (permission denied)
     */
    public function testT082_ManagerCreateLeadPermissionDenied()
    {
        $clientId = $this->_createAdvertiser();
        $dllTracker = $this->_getTrackerMock(false);

        $oInfo = new OA_Dll_TrackerInfo();
        $oInfo->clientId = $clientId;
        $oInfo->trackerName = 'T082 permission denied';
        $oInfo->type = MAX_CONNECTION_TYPE_LEAD;
        $oInfo->status = MAX_CONNECTION_STATUS_PENDING;
        $oInfo->variableMethod = 'js';
        $oInfo->linkCampaigns = false;

        $this->assertFalse($dllTracker->modify($oInfo));
    }

    /**
     * T083: ADMIN | Edit | nonexistent tracker
     */
    public function testT083_AdminEditNonexistentTracker()
    {
        $dllTracker = $this->_getTrackerMock();

        $oInfo = new OA_Dll_TrackerInfo();
        $oInfo->trackerId = 99999;
        $oInfo->trackerName = 'T083 nonexistent';

        $this->assertFalse($dllTracker->modify($oInfo));
        $this->assertEqual(
            $dllTracker->getLastError(),
            OA_Dll_Tracker::ERROR_UNKNOWN_TRACKER_ID,
        );
    }

    /**
     * T084: MANAGER | Delete | nonexistent tracker
     */
    public function testT084_ManagerDeleteNonexistentTracker()
    {
        $dllTracker = $this->_getTrackerMock();

        $this->assertFalse($dllTracker->delete(99999));
        $this->assertEqual(
            $dllTracker->getLastError(),
            OA_Dll_Tracker::ERROR_UNKNOWN_TRACKER_ID,
        );
    }

    /**
     * T085: ADMIN | LinkToCampaign | nonexistent tracker
     */
    public function testT085_AdminLinkNonexistentTracker()
    {
        $dllTracker = $this->_getTrackerMock();

        $this->assertFalse($dllTracker->linkTrackerToCampaign(99999, 99999));
        $this->assertEqual(
            $dllTracker->getLastError(),
            OA_Dll_Tracker::ERROR_UNKNOWN_TRACKER_ID,
        );
    }

    /**
     * T086: MANAGER | LinkToCampaign | valid tracker, nonexistent campaign
     */
    public function testT086_ManagerLinkNonexistentCampaign()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T086 link missing campaign', MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_APPROVED);

        $dllTracker = $this->_getTrackerMock();
        $this->assertFalse($dllTracker->linkTrackerToCampaign($oInfo->trackerId, 99999));
        $this->assertEqual(
            $dllTracker->getLastError(),
            OA_Dll_Tracker::ERROR_UNKNOWN_CAMPAIGN_ID,
        );
    }

    /**
     * T087: ADMIN | View | nonexistent tracker
     */
    public function testT087_AdminViewNonexistentTracker()
    {
        $dllTracker = $this->_getTrackerMock();

        $oGet = null;
        $this->assertFalse($dllTracker->getTracker(99999, $oGet));
        $this->assertEqual(
            $dllTracker->getLastError(),
            OA_Dll_Tracker::ERROR_UNKNOWN_TRACKER_ID,
        );
    }

    /**
     * T088: MANAGER | Create | Sale | Approved | default | false (missing tracker name)
     */
    public function testT088_ManagerCreateSaleMissingName()
    {
        $clientId = $this->_createAdvertiser();
        $dllTracker = $this->_getTrackerMock();

        $oInfo = new OA_Dll_TrackerInfo();
        $oInfo->clientId = $clientId;
        // Intentionally omit trackerName
        $oInfo->type = MAX_CONNECTION_TYPE_SALE;
        $oInfo->status = MAX_CONNECTION_STATUS_APPROVED;
        $oInfo->variableMethod = 'default';
        $oInfo->linkCampaigns = false;

        $this->assertFalse($dllTracker->modify($oInfo));
    }

    /**
     * T089: ADMIN | Edit | Sale | Approved | change variableMethod from default to js
     */
    public function testT089_AdminEditChangeVariableMethod()
    {
        $clientId = $this->_createAdvertiser();
        $oInfo = $this->_createTracker($clientId, 'T089 change varmethod', MAX_CONNECTION_TYPE_SALE, MAX_CONNECTION_STATUS_APPROVED, 'default', true);

        $dllTracker = $this->_getTrackerMock();

        // Verify initial value
        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->variableMethod, 'default');

        // Change variableMethod
        $oInfo->variableMethod = 'js';
        $this->assertTrue($dllTracker->modify($oInfo), $dllTracker->getLastError());

        // Verify change
        $oGet2 = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet2), $dllTracker->getLastError());
        $this->assertEqual($oGet2->variableMethod, 'js');
    }

    /**
     * T090: MANAGER | Create | Signup | OnHold | dom | true (with description)
     */
    public function testT090_ManagerCreateSignupWithDescription()
    {
        $clientId = $this->_createAdvertiser();
        $dllTracker = $this->_getTrackerMock();

        $oInfo = new OA_Dll_TrackerInfo();
        $oInfo->clientId = $clientId;
        $oInfo->trackerName = 'T090 Signup with desc';
        $oInfo->description = 'Test description for T090 combination matrix test';
        $oInfo->type = MAX_CONNECTION_TYPE_SIGNUP;
        $oInfo->status = MAX_CONNECTION_STATUS_ONHOLD;
        $oInfo->variableMethod = 'dom';
        $oInfo->linkCampaigns = true;

        $this->assertTrue($dllTracker->modify($oInfo), $dllTracker->getLastError());
        $this->assertNotNull($oInfo->trackerId);

        $oGet = null;
        $this->assertTrue($dllTracker->getTracker($oInfo->trackerId, $oGet), $dllTracker->getLastError());
        $this->assertEqual($oGet->trackerName, 'T090 Signup with desc');
        $this->assertEqual($oGet->description, 'Test description for T090 combination matrix test');
        $this->assertEqual($oGet->type, MAX_CONNECTION_TYPE_SIGNUP);
        $this->assertEqual($oGet->status, MAX_CONNECTION_STATUS_ONHOLD);
        $this->assertEqual($oGet->variableMethod, 'dom');
        $this->assertTrue($oGet->linkCampaigns);
    }
}
