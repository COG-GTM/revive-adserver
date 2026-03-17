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
require_once MAX_PATH . '/lib/OA/Dll/Banner.php';
require_once MAX_PATH . '/lib/OA/Dll/BannerInfo.php';
require_once MAX_PATH . '/lib/OA/Dll/tests/util/DllUnitTestCase.php';

/**
 * Pairwise combinatorial tests for Banner CRUD operations.
 *
 * Covers ~100 included combinations (Section 4C) and excluded/negative
 * combinations (Section 4D) of the combinatorial testing plan.
 *
 * Parameters under test:
 *   - Account type:       ADMIN, MANAGER, ADVERTISER, TRAFFICKER
 *   - Page mode:          Create, Edit, View, Delete
 *   - Storage type:       sql, web, url, html, txt
 *   - Entity status:      Running, Paused, Awaiting, Expired, Inactive, Pending, Approval, Rejected
 *   - Banner permission:  BANNER_ACTIVATE, BANNER_DEACTIVATE, BANNER_ADD, BANNER_EDIT
 *   - Frequency capping:  None, capping only, sessionCapping only, block only, all three
 *   - Image validation:   Valid image, empty filename, empty content, wrong format, oversized
 *   - Parent campaign:    Remnant, Contract Normal, Override, eCPM, Contract eCPM
 *
 * @package    OpenXDll
 * @subpackage TestSuite
 */
class OA_Dll_BannerCrudComboTest extends DllUnitTestCase
{
    /**
     * @var int
     */
    public $agencyId;

    /**
     * @var string
     */
    public $binaryGif;

    public function __construct()
    {
        parent::__construct();
        Mock::generatePartial(
            'OA_Dll_Banner',
            'PartialMockOA_Dll_Banner_Combo',
            ['checkPermissions'],
        );
        Mock::generatePartial(
            'OA_Dll_Banner',
            'PartialMockOA_Dll_Banner_ComboDeny',
            ['checkPermissions'],
        );
        Mock::generatePartial(
            'OA_Dll_Campaign',
            'PartialMockOA_Dll_Campaign_Combo',
            ['checkPermissions'],
        );
        Mock::generatePartial(
            'OA_Dll_Advertiser',
            'PartialMockOA_Dll_Advertiser_Combo',
            ['checkPermissions', 'getDefaultAgencyId'],
        );

        $this->binaryGif = "GIF89a\001\0\001\0\200\0\0\377\377\377\0\0\0!\371\004\0\0\0\0\0,\0\0\0\0\001\0\001\0\0\002\002D\001\0;";
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
    // Helper: create advertiser
    // ---------------------------------------------------------------
    private function _createAdvertiser($comboId)
    {
        $dllAdv = new PartialMockOA_Dll_Advertiser_Combo($this);
        $dllAdv->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdv->setReturnValue('checkPermissions', true);

        $oAdv = new OA_Dll_AdvertiserInfo();
        $oAdv->advertiserName = "Adv {$comboId}";
        $oAdv->agencyId = $this->agencyId;
        $this->assertTrue($dllAdv->modify($oAdv), "{$comboId}: Advertiser create failed");

        return $oAdv;
    }

    // ---------------------------------------------------------------
    // Helper: create campaign with given type
    // ---------------------------------------------------------------
    private function _createCampaign($advertiserId, $campaignType, $comboId)
    {
        $dllCamp = new PartialMockOA_Dll_Campaign_Combo($this);
        $dllCamp->setReturnValue('checkPermissions', true);

        $oCamp = new OA_Dll_CampaignInfo();
        $oCamp->advertiserId = $advertiserId;

        switch ($campaignType) {
            case 'Remnant':
                $oCamp->priority = 0;
                $oCamp->weight = 1;
                break;
            case 'ContractNormal':
                $oCamp->priority = 5;
                $oCamp->weight = 0;
                $oCamp->targetImpressions = 1000;
                break;
            case 'Override':
                $oCamp->priority = -1;
                $oCamp->weight = 1;
                break;
            case 'eCPM':
                $oCamp->priority = -2;
                $oCamp->weight = 1;
                break;
            case 'ContractECPM':
                $oCamp->priority = 5;
                $oCamp->weight = 0;
                $oCamp->targetImpressions = 1000;
                $oCamp->revenue = 1.5;
                $oCamp->revenueType = MAX_FINANCE_CPM;
                break;
        }

        $this->assertTrue(
            $dllCamp->modify($oCamp),
            "{$comboId}: Campaign ({$campaignType}) create failed: " . $dllCamp->getLastError(),
        );

        return $oCamp;
    }

    // ---------------------------------------------------------------
    // Helper: create a "base" banner (always succeeds)
    // ---------------------------------------------------------------
    private function _createBaseBanner($dllBanner, $campaignId, $storageType, $comboId)
    {
        $oB = new OA_Dll_BannerInfo();
        $oB->campaignId = $campaignId;
        $oB->storageType = $storageType;
        $oB->bannerName = "Base {$comboId}";

        switch ($storageType) {
            case 'sql':
            case 'web':
                $oB->aImage = ['filename' => '1x1.gif', 'content' => $this->binaryGif];
                break;
            case 'url':
                $oB->imageURL = 'http://example.com/banner.gif';
                break;
            case 'html':
                $oB->htmlTemplate = '<div>Base</div>';
                $oB->width = 468;
                $oB->height = 60;
                break;
            case 'txt':
                $oB->bannerText = 'Base text banner';
                break;
        }

        $this->assertTrue(
            $dllBanner->modify($oB),
            "{$comboId}: Base banner ({$storageType}) create failed: " . $dllBanner->getLastError(),
        );

        return $oB;
    }

    // ---------------------------------------------------------------
    // Helper: apply capping settings to a BannerInfo
    // ---------------------------------------------------------------
    private function _applyCapping($oB, $cappingType)
    {
        switch ($cappingType) {
            case 'none':
                break;
            case 'capping':
                $oB->capping = 5;
                break;
            case 'session':
                $oB->sessionCapping = 3;
                break;
            case 'block':
                $oB->block = 3600;
                break;
            case 'all':
                $oB->capping = 5;
                $oB->sessionCapping = 3;
                $oB->block = 3600;
                break;
        }
    }

    // ---------------------------------------------------------------
    // Helper: apply image payload to a BannerInfo
    // ---------------------------------------------------------------
    private function _applyImage($oB, $imageType)
    {
        switch ($imageType) {
            case 'valid':
                $oB->aImage = ['filename' => '1x1.gif', 'content' => $this->binaryGif];
                break;
            case 'emptyFilename':
                $oB->aImage = ['filename' => '', 'content' => $this->binaryGif];
                break;
            case 'emptyContent':
                $oB->aImage = ['filename' => 'test.gif', 'content' => ''];
                break;
            case 'wrongFormat':
                $oB->aImage = ['filename' => 'test.gif', 'content' => 'foobar'];
                break;
            case 'oversized':
                $GLOBALS['_MAX']['CONF']['store']['maxFilesize'] = 16;
                $oB->aImage = ['filename' => 'bigger.gif', 'content' => $this->binaryGif];
                break;
        }
    }

    // ---------------------------------------------------------------
    // Helper: should the CRUD operation succeed for this combo?
    // ---------------------------------------------------------------
    private function _expectsSuccess($storageType, $pageMode, $imageType)
    {
        if ($storageType !== 'sql' && $storageType !== 'web') {
            return true;
        }
        if ($pageMode === 'View' || $pageMode === 'Delete') {
            return true;
        }
        // Create/Edit with sql/web: only valid or N/A images succeed
        return $imageType === 'valid' || $imageType === 'N/A';
    }

    // ---------------------------------------------------------------
    // Section 4C: 100 pairwise-generated included combinations
    //
    // Constraints applied:
    //   - No TRAFFICKER (excluded → Section 4D)
    //   - No ADVERTISER + Delete (excluded → Section 4D)
    //   - Create + sql/web always uses valid image (other image
    //     scenarios on Create are in Section 4D)
    //   - Edit + sql/web may use any image validation scenario
    //   - View / Delete / non-image storage → image = N/A
    //
    // Each row: [id, account, mode, storage, status, permission,
    //            capping, image, campaignType]
    // ---------------------------------------------------------------
    private function _getIncludedCombinations()
    {
        return [
            // --- ADMIN block (B001-B034) ---
            ['B001', 'ADMIN',      'Create', 'html', OA_ENTITY_STATUS_RUNNING,  OA_PERM_BANNER_EDIT,       'none',    'N/A',           'Remnant'],
            ['B002', 'ADMIN',      'Create', 'sql',  OA_ENTITY_STATUS_PAUSED,   OA_PERM_BANNER_ADD,        'capping', 'valid',         'ContractNormal'],
            ['B003', 'ADMIN',      'Create', 'web',  OA_ENTITY_STATUS_AWAITING, OA_PERM_BANNER_ACTIVATE,   'session', 'valid',         'Override'],
            ['B004', 'ADMIN',      'Create', 'url',  OA_ENTITY_STATUS_EXPIRED,  OA_PERM_BANNER_DEACTIVATE, 'block',   'N/A',           'eCPM'],
            ['B005', 'ADMIN',      'Create', 'txt',  OA_ENTITY_STATUS_INACTIVE, OA_PERM_BANNER_EDIT,       'all',     'N/A',           'ContractECPM'],
            ['B006', 'ADMIN',      'Create', 'html', OA_ENTITY_STATUS_PENDING,  OA_PERM_BANNER_ADD,        'none',    'N/A',           'Remnant'],
            ['B007', 'ADMIN',      'Create', 'sql',  OA_ENTITY_STATUS_APPROVAL, OA_PERM_BANNER_ACTIVATE,   'capping', 'valid',         'Override'],
            ['B008', 'ADMIN',      'Create', 'web',  OA_ENTITY_STATUS_REJECTED, OA_PERM_BANNER_DEACTIVATE, 'session', 'valid',         'ContractECPM'],
            ['B009', 'ADMIN',      'Edit',   'url',  OA_ENTITY_STATUS_RUNNING,  OA_PERM_BANNER_ADD,        'block',   'N/A',           'ContractNormal'],
            ['B010', 'ADMIN',      'Edit',   'html', OA_ENTITY_STATUS_PAUSED,   OA_PERM_BANNER_EDIT,       'all',     'N/A',           'eCPM'],
            ['B011', 'ADMIN',      'Edit',   'txt',  OA_ENTITY_STATUS_AWAITING, OA_PERM_BANNER_ACTIVATE,   'none',    'N/A',           'Remnant'],
            ['B012', 'ADMIN',      'Edit',   'sql',  OA_ENTITY_STATUS_EXPIRED,  OA_PERM_BANNER_DEACTIVATE, 'capping', 'valid',         'ContractECPM'],
            ['B013', 'ADMIN',      'Edit',   'web',  OA_ENTITY_STATUS_INACTIVE, OA_PERM_BANNER_ADD,        'session', 'valid',         'Override'],
            ['B014', 'ADMIN',      'Edit',   'sql',  OA_ENTITY_STATUS_PENDING,  OA_PERM_BANNER_EDIT,       'block',   'emptyFilename', 'ContractNormal'],
            ['B015', 'ADMIN',      'Edit',   'web',  OA_ENTITY_STATUS_APPROVAL, OA_PERM_BANNER_ACTIVATE,   'all',     'emptyContent',  'eCPM'],
            ['B016', 'ADMIN',      'Edit',   'sql',  OA_ENTITY_STATUS_REJECTED, OA_PERM_BANNER_DEACTIVATE, 'none',    'wrongFormat',   'Remnant'],
            ['B017', 'ADMIN',      'Edit',   'web',  OA_ENTITY_STATUS_RUNNING,  OA_PERM_BANNER_ADD,        'capping', 'oversized',     'ContractECPM'],
            ['B018', 'ADMIN',      'View',   'html', OA_ENTITY_STATUS_PAUSED,   OA_PERM_BANNER_EDIT,       'session', 'N/A',           'Override'],
            ['B019', 'ADMIN',      'View',   'sql',  OA_ENTITY_STATUS_AWAITING, OA_PERM_BANNER_ACTIVATE,   'block',   'N/A',           'ContractNormal'],
            ['B020', 'ADMIN',      'View',   'web',  OA_ENTITY_STATUS_EXPIRED,  OA_PERM_BANNER_DEACTIVATE, 'all',     'N/A',           'eCPM'],
            ['B021', 'ADMIN',      'View',   'url',  OA_ENTITY_STATUS_INACTIVE, OA_PERM_BANNER_ADD,        'none',    'N/A',           'Remnant'],
            ['B022', 'ADMIN',      'View',   'txt',  OA_ENTITY_STATUS_PENDING,  OA_PERM_BANNER_EDIT,       'capping', 'N/A',           'ContractECPM'],
            ['B023', 'ADMIN',      'View',   'html', OA_ENTITY_STATUS_APPROVAL, OA_PERM_BANNER_ACTIVATE,   'session', 'N/A',           'Override'],
            ['B024', 'ADMIN',      'View',   'url',  OA_ENTITY_STATUS_REJECTED, OA_PERM_BANNER_DEACTIVATE, 'block',   'N/A',           'ContractNormal'],
            ['B025', 'ADMIN',      'Delete', 'txt',  OA_ENTITY_STATUS_RUNNING,  OA_PERM_BANNER_ADD,        'all',     'N/A',           'eCPM'],
            ['B026', 'ADMIN',      'Delete', 'html', OA_ENTITY_STATUS_PAUSED,   OA_PERM_BANNER_EDIT,       'none',    'N/A',           'Remnant'],
            ['B027', 'ADMIN',      'Delete', 'sql',  OA_ENTITY_STATUS_AWAITING, OA_PERM_BANNER_DEACTIVATE, 'capping', 'N/A',           'ContractECPM'],
            ['B028', 'ADMIN',      'Delete', 'web',  OA_ENTITY_STATUS_EXPIRED,  OA_PERM_BANNER_ACTIVATE,   'session', 'N/A',           'Override'],
            ['B029', 'ADMIN',      'Delete', 'url',  OA_ENTITY_STATUS_INACTIVE, OA_PERM_BANNER_ADD,        'block',   'N/A',           'ContractNormal'],
            ['B030', 'ADMIN',      'Delete', 'txt',  OA_ENTITY_STATUS_PENDING,  OA_PERM_BANNER_DEACTIVATE, 'all',     'N/A',           'eCPM'],
            ['B031', 'ADMIN',      'Delete', 'html', OA_ENTITY_STATUS_APPROVAL, OA_PERM_BANNER_EDIT,       'none',    'N/A',           'Remnant'],
            ['B032', 'ADMIN',      'Delete', 'web',  OA_ENTITY_STATUS_REJECTED, OA_PERM_BANNER_ACTIVATE,   'capping', 'N/A',           'ContractECPM'],
            ['B033', 'ADMIN',      'Edit',   'sql',  OA_ENTITY_STATUS_RUNNING,  OA_PERM_BANNER_EDIT,       'session', 'oversized',     'Override'],
            ['B034', 'ADMIN',      'Edit',   'web',  OA_ENTITY_STATUS_PAUSED,   OA_PERM_BANNER_ADD,        'block',   'wrongFormat',   'ContractNormal'],

            // --- MANAGER block (B035-B067) ---
            ['B035', 'MANAGER',    'Create', 'sql',  OA_ENTITY_STATUS_RUNNING,  OA_PERM_BANNER_ADD,        'capping', 'valid',         'ContractNormal'],
            ['B036', 'MANAGER',    'Create', 'web',  OA_ENTITY_STATUS_PAUSED,   OA_PERM_BANNER_EDIT,       'session', 'valid',         'Override'],
            ['B037', 'MANAGER',    'Create', 'url',  OA_ENTITY_STATUS_AWAITING, OA_PERM_BANNER_ACTIVATE,   'block',   'N/A',           'eCPM'],
            ['B038', 'MANAGER',    'Create', 'html', OA_ENTITY_STATUS_EXPIRED,  OA_PERM_BANNER_DEACTIVATE, 'all',     'N/A',           'ContractECPM'],
            ['B039', 'MANAGER',    'Create', 'txt',  OA_ENTITY_STATUS_INACTIVE, OA_PERM_BANNER_ADD,        'none',    'N/A',           'Remnant'],
            ['B040', 'MANAGER',    'Create', 'html', OA_ENTITY_STATUS_PENDING,  OA_PERM_BANNER_EDIT,       'capping', 'N/A',           'ContractNormal'],
            ['B041', 'MANAGER',    'Create', 'sql',  OA_ENTITY_STATUS_APPROVAL, OA_PERM_BANNER_DEACTIVATE, 'session', 'valid',         'eCPM'],
            ['B042', 'MANAGER',    'Create', 'web',  OA_ENTITY_STATUS_REJECTED, OA_PERM_BANNER_ACTIVATE,   'block',   'valid',         'Remnant'],
            ['B043', 'MANAGER',    'Edit',   'url',  OA_ENTITY_STATUS_RUNNING,  OA_PERM_BANNER_EDIT,       'all',     'N/A',           'Override'],
            ['B044', 'MANAGER',    'Edit',   'html', OA_ENTITY_STATUS_PAUSED,   OA_PERM_BANNER_ADD,        'none',    'N/A',           'ContractECPM'],
            ['B045', 'MANAGER',    'Edit',   'txt',  OA_ENTITY_STATUS_AWAITING, OA_PERM_BANNER_ACTIVATE,   'capping', 'N/A',           'Remnant'],
            ['B046', 'MANAGER',    'Edit',   'sql',  OA_ENTITY_STATUS_EXPIRED,  OA_PERM_BANNER_DEACTIVATE, 'session', 'valid',         'ContractNormal'],
            ['B047', 'MANAGER',    'Edit',   'web',  OA_ENTITY_STATUS_INACTIVE, OA_PERM_BANNER_EDIT,       'block',   'valid',         'eCPM'],
            ['B048', 'MANAGER',    'Edit',   'sql',  OA_ENTITY_STATUS_PENDING,  OA_PERM_BANNER_ADD,        'all',     'emptyFilename', 'Override'],
            ['B049', 'MANAGER',    'Edit',   'web',  OA_ENTITY_STATUS_APPROVAL, OA_PERM_BANNER_ACTIVATE,   'none',    'emptyContent',  'ContractECPM'],
            ['B050', 'MANAGER',    'Edit',   'sql',  OA_ENTITY_STATUS_REJECTED, OA_PERM_BANNER_DEACTIVATE, 'capping', 'wrongFormat',   'Remnant'],
            ['B051', 'MANAGER',    'Edit',   'web',  OA_ENTITY_STATUS_RUNNING,  OA_PERM_BANNER_EDIT,       'session', 'oversized',     'ContractNormal'],
            ['B052', 'MANAGER',    'View',   'txt',  OA_ENTITY_STATUS_PAUSED,   OA_PERM_BANNER_ADD,        'block',   'N/A',           'eCPM'],
            ['B053', 'MANAGER',    'View',   'html', OA_ENTITY_STATUS_AWAITING, OA_PERM_BANNER_EDIT,       'all',     'N/A',           'ContractECPM'],
            ['B054', 'MANAGER',    'View',   'sql',  OA_ENTITY_STATUS_EXPIRED,  OA_PERM_BANNER_ACTIVATE,   'none',    'N/A',           'Remnant'],
            ['B055', 'MANAGER',    'View',   'web',  OA_ENTITY_STATUS_INACTIVE, OA_PERM_BANNER_DEACTIVATE, 'capping', 'N/A',           'Override'],
            ['B056', 'MANAGER',    'View',   'url',  OA_ENTITY_STATUS_PENDING,  OA_PERM_BANNER_ADD,        'session', 'N/A',           'ContractNormal'],
            ['B057', 'MANAGER',    'View',   'html', OA_ENTITY_STATUS_APPROVAL, OA_PERM_BANNER_DEACTIVATE, 'block',   'N/A',           'eCPM'],
            ['B058', 'MANAGER',    'View',   'url',  OA_ENTITY_STATUS_REJECTED, OA_PERM_BANNER_EDIT,       'all',     'N/A',           'Remnant'],
            ['B059', 'MANAGER',    'Delete', 'sql',  OA_ENTITY_STATUS_RUNNING,  OA_PERM_BANNER_ACTIVATE,   'none',    'N/A',           'ContractECPM'],
            ['B060', 'MANAGER',    'Delete', 'web',  OA_ENTITY_STATUS_PAUSED,   OA_PERM_BANNER_DEACTIVATE, 'capping', 'N/A',           'Override'],
            ['B061', 'MANAGER',    'Delete', 'html', OA_ENTITY_STATUS_AWAITING, OA_PERM_BANNER_ADD,        'session', 'N/A',           'ContractNormal'],
            ['B062', 'MANAGER',    'Delete', 'url',  OA_ENTITY_STATUS_EXPIRED,  OA_PERM_BANNER_EDIT,       'block',   'N/A',           'eCPM'],
            ['B063', 'MANAGER',    'Delete', 'txt',  OA_ENTITY_STATUS_INACTIVE, OA_PERM_BANNER_ACTIVATE,   'all',     'N/A',           'Remnant'],
            ['B064', 'MANAGER',    'Delete', 'html', OA_ENTITY_STATUS_PENDING,  OA_PERM_BANNER_DEACTIVATE, 'none',    'N/A',           'ContractECPM'],
            ['B065', 'MANAGER',    'Delete', 'web',  OA_ENTITY_STATUS_APPROVAL, OA_PERM_BANNER_EDIT,       'capping', 'N/A',           'Override'],
            ['B066', 'MANAGER',    'Delete', 'sql',  OA_ENTITY_STATUS_REJECTED, OA_PERM_BANNER_ADD,        'session', 'N/A',           'eCPM'],
            ['B067', 'MANAGER',    'Edit',   'web',  OA_ENTITY_STATUS_AWAITING, OA_PERM_BANNER_DEACTIVATE, 'all',     'emptyFilename', 'Remnant'],

            // --- ADVERTISER block (B068-B100) — no Delete ---
            ['B068', 'ADVERTISER', 'Create', 'html', OA_ENTITY_STATUS_RUNNING,  OA_PERM_BANNER_EDIT,       'none',    'N/A',           'Remnant'],
            ['B069', 'ADVERTISER', 'Create', 'sql',  OA_ENTITY_STATUS_PAUSED,   OA_PERM_BANNER_ADD,        'capping', 'valid',         'ContractNormal'],
            ['B070', 'ADVERTISER', 'Create', 'web',  OA_ENTITY_STATUS_AWAITING, OA_PERM_BANNER_ACTIVATE,   'session', 'valid',         'Override'],
            ['B071', 'ADVERTISER', 'Create', 'url',  OA_ENTITY_STATUS_EXPIRED,  OA_PERM_BANNER_DEACTIVATE, 'block',   'N/A',           'eCPM'],
            ['B072', 'ADVERTISER', 'Create', 'txt',  OA_ENTITY_STATUS_INACTIVE, OA_PERM_BANNER_EDIT,       'all',     'N/A',           'ContractECPM'],
            ['B073', 'ADVERTISER', 'Create', 'html', OA_ENTITY_STATUS_PENDING,  OA_PERM_BANNER_ADD,        'none',    'N/A',           'Override'],
            ['B074', 'ADVERTISER', 'Create', 'sql',  OA_ENTITY_STATUS_APPROVAL, OA_PERM_BANNER_ACTIVATE,   'capping', 'valid',         'eCPM'],
            ['B075', 'ADVERTISER', 'Create', 'web',  OA_ENTITY_STATUS_REJECTED, OA_PERM_BANNER_DEACTIVATE, 'session', 'valid',         'Remnant'],
            ['B076', 'ADVERTISER', 'Create', 'url',  OA_ENTITY_STATUS_RUNNING,  OA_PERM_BANNER_EDIT,       'block',   'N/A',           'ContractECPM'],
            ['B077', 'ADVERTISER', 'Create', 'txt',  OA_ENTITY_STATUS_PAUSED,   OA_PERM_BANNER_ADD,        'all',     'N/A',           'ContractNormal'],
            ['B078', 'ADVERTISER', 'Edit',   'html', OA_ENTITY_STATUS_AWAITING, OA_PERM_BANNER_EDIT,       'none',    'N/A',           'Override'],
            ['B079', 'ADVERTISER', 'Edit',   'sql',  OA_ENTITY_STATUS_EXPIRED,  OA_PERM_BANNER_ACTIVATE,   'capping', 'valid',         'eCPM'],
            ['B080', 'ADVERTISER', 'Edit',   'web',  OA_ENTITY_STATUS_INACTIVE, OA_PERM_BANNER_DEACTIVATE, 'session', 'valid',         'ContractECPM'],
            ['B081', 'ADVERTISER', 'Edit',   'url',  OA_ENTITY_STATUS_PENDING,  OA_PERM_BANNER_ADD,        'block',   'N/A',           'Remnant'],
            ['B082', 'ADVERTISER', 'Edit',   'txt',  OA_ENTITY_STATUS_APPROVAL, OA_PERM_BANNER_EDIT,       'all',     'N/A',           'ContractNormal'],
            ['B083', 'ADVERTISER', 'Edit',   'sql',  OA_ENTITY_STATUS_REJECTED, OA_PERM_BANNER_ACTIVATE,   'none',    'emptyFilename', 'Override'],
            ['B084', 'ADVERTISER', 'Edit',   'web',  OA_ENTITY_STATUS_RUNNING,  OA_PERM_BANNER_DEACTIVATE, 'capping', 'emptyContent',  'eCPM'],
            ['B085', 'ADVERTISER', 'Edit',   'sql',  OA_ENTITY_STATUS_PAUSED,   OA_PERM_BANNER_ADD,        'session', 'wrongFormat',   'ContractECPM'],
            ['B086', 'ADVERTISER', 'Edit',   'web',  OA_ENTITY_STATUS_AWAITING, OA_PERM_BANNER_EDIT,       'block',   'oversized',     'Remnant'],
            ['B087', 'ADVERTISER', 'View',   'html', OA_ENTITY_STATUS_EXPIRED,  OA_PERM_BANNER_DEACTIVATE, 'all',     'N/A',           'ContractNormal'],
            ['B088', 'ADVERTISER', 'View',   'sql',  OA_ENTITY_STATUS_INACTIVE, OA_PERM_BANNER_ADD,        'none',    'N/A',           'Override'],
            ['B089', 'ADVERTISER', 'View',   'web',  OA_ENTITY_STATUS_PENDING,  OA_PERM_BANNER_EDIT,       'capping', 'N/A',           'eCPM'],
            ['B090', 'ADVERTISER', 'View',   'url',  OA_ENTITY_STATUS_APPROVAL, OA_PERM_BANNER_ACTIVATE,   'session', 'N/A',           'ContractECPM'],
            ['B091', 'ADVERTISER', 'View',   'txt',  OA_ENTITY_STATUS_REJECTED, OA_PERM_BANNER_DEACTIVATE, 'block',   'N/A',           'Remnant'],
            ['B092', 'ADVERTISER', 'View',   'html', OA_ENTITY_STATUS_RUNNING,  OA_PERM_BANNER_EDIT,       'all',     'N/A',           'ContractNormal'],
            ['B093', 'ADVERTISER', 'View',   'url',  OA_ENTITY_STATUS_PAUSED,   OA_PERM_BANNER_ADD,        'none',    'N/A',           'Override'],
            ['B094', 'ADVERTISER', 'View',   'txt',  OA_ENTITY_STATUS_AWAITING, OA_PERM_BANNER_ACTIVATE,   'capping', 'N/A',           'eCPM'],
            ['B095', 'ADVERTISER', 'Edit',   'web',  OA_ENTITY_STATUS_EXPIRED,  OA_PERM_BANNER_EDIT,       'session', 'wrongFormat',   'ContractECPM'],
            ['B096', 'ADVERTISER', 'Edit',   'sql',  OA_ENTITY_STATUS_INACTIVE, OA_PERM_BANNER_DEACTIVATE, 'all',     'oversized',     'Remnant'],
            ['B097', 'ADMIN',      'Edit',   'web',  OA_ENTITY_STATUS_AWAITING, OA_PERM_BANNER_EDIT,       'none',    'emptyContent',  'ContractNormal'],
            ['B098', 'MANAGER',    'Edit',   'sql',  OA_ENTITY_STATUS_INACTIVE, OA_PERM_BANNER_ADD,        'block',   'emptyFilename', 'eCPM'],
            ['B099', 'ADMIN',      'Edit',   'web',  OA_ENTITY_STATUS_PENDING,  OA_PERM_BANNER_DEACTIVATE, 'capping', 'oversized',     'Override'],
            ['B100', 'MANAGER',    'Edit',   'sql',  OA_ENTITY_STATUS_APPROVAL, OA_PERM_BANNER_ACTIVATE,   'session', 'wrongFormat',   'ContractECPM'],
        ];
    }

    // ---------------------------------------------------------------
    // Runner for a single included combo
    // ---------------------------------------------------------------
    private function _runIncludedCombo($combo)
    {
        [$comboId, $accountType, $pageMode, $storageType, $status,
         $permission, $cappingType, $imageType, $campaignType] = $combo;

        // Reset store configuration
        $GLOBALS['_MAX']['CONF']['store']['mode'] = 0;
        $GLOBALS['_MAX']['CONF']['store']['webDir'] = MAX_PATH . '/var';
        $GLOBALS['_MAX']['CONF']['store']['maxFilesize'] = 0;

        // Allow all banner types
        $GLOBALS['_MAX']['CONF']['allowedBanners']['sql'] = true;
        $GLOBALS['_MAX']['CONF']['allowedBanners']['web'] = true;
        $GLOBALS['_MAX']['CONF']['allowedBanners']['url'] = true;
        $GLOBALS['_MAX']['CONF']['allowedBanners']['html'] = true;
        $GLOBALS['_MAX']['CONF']['allowedBanners']['text'] = true;

        // Create mocks (permissions always pass for included combos)
        $dllBanner = new PartialMockOA_Dll_Banner_Combo($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        // Set up hierarchy: advertiser → campaign → (banner)
        $oAdv  = $this->_createAdvertiser($comboId);
        $oCamp = $this->_createCampaign($oAdv->advertiserId, $campaignType, $comboId);

        $expectSuccess = $this->_expectsSuccess($storageType, $pageMode, $imageType);

        switch ($pageMode) {
            // ----- CREATE -------------------------------------------------
            case 'Create':
                $oB = new OA_Dll_BannerInfo();
                $oB->campaignId  = $oCamp->campaignId;
                $oB->storageType = $storageType;
                $oB->bannerName  = "Banner {$comboId}";
                $oB->status      = $status;
                $this->_applyCapping($oB, $cappingType);

                switch ($storageType) {
                    case 'sql':
                    case 'web':
                        $this->_applyImage($oB, $imageType);
                        break;
                    case 'url':
                        $oB->imageURL = 'http://example.com/ad.gif';
                        break;
                    case 'html':
                        $oB->htmlTemplate = '<div>' . $comboId . '</div>';
                        $oB->width  = 468;
                        $oB->height = 60;
                        break;
                    case 'txt':
                        $oB->bannerText = 'Text ' . $comboId;
                        break;
                }

                $result = $dllBanner->modify($oB);

                if ($expectSuccess) {
                    $this->assertTrue($result,
                        "{$comboId} [{$accountType}, Create, {$storageType}, {$campaignType}]: " . $dllBanner->getLastError());
                    $this->assertNotNull($oB->bannerId, "{$comboId}: bannerId should be set after Create");

                    // Verify stored data
                    if ($oB->bannerId) {
                        $doBanner = OA_Dal::staticGetDO('banners', $oB->bannerId);
                        $this->assertEqual($doBanner->storagetype, $storageType,
                            "{$comboId}: storageType mismatch");
                        $this->_verifyCapping($doBanner, $cappingType, $comboId);
                    }
                } else {
                    $this->assertFalse($result,
                        "{$comboId}: Create should fail (image={$imageType})");
                }
                break;

            // ----- EDIT ---------------------------------------------------
            case 'Edit':
                $oBase = $this->_createBaseBanner($dllBanner, $oCamp->campaignId, $storageType, $comboId);

                $oEdit = new OA_Dll_BannerInfo();
                $oEdit->bannerId   = $oBase->bannerId;
                $oEdit->bannerName = "Edited {$comboId}";
                $oEdit->status     = $status;
                $this->_applyCapping($oEdit, $cappingType);

                if (($storageType === 'sql' || $storageType === 'web') && $imageType !== 'N/A') {
                    $this->_applyImage($oEdit, $imageType);
                }

                $result = $dllBanner->modify($oEdit);

                if ($expectSuccess) {
                    $this->assertTrue($result,
                        "{$comboId} [{$accountType}, Edit, {$storageType}, {$campaignType}]: " . $dllBanner->getLastError());

                    $doBanner = OA_Dal::staticGetDO('banners', $oBase->bannerId);
                    $this->assertEqual($doBanner->description, "Edited {$comboId}",
                        "{$comboId}: bannerName not updated");
                    $this->_verifyCapping($doBanner, $cappingType, $comboId);
                } else {
                    $this->assertFalse($result,
                        "{$comboId}: Edit should fail (image={$imageType})");
                }
                break;

            // ----- VIEW ---------------------------------------------------
            case 'View':
                $oBase = $this->_createBaseBanner($dllBanner, $oCamp->campaignId, $storageType, $comboId);

                $oBannerGet = null;
                $result = $dllBanner->getBanner($oBase->bannerId, $oBannerGet);

                $this->assertTrue($result,
                    "{$comboId} [{$accountType}, View, {$storageType}, {$campaignType}]: " . $dllBanner->getLastError());
                $this->assertNotNull($oBannerGet, "{$comboId}: getBanner returned null");
                $this->assertEqual($oBannerGet->bannerId, $oBase->bannerId,
                    "{$comboId}: bannerId mismatch on View");
                $this->assertEqual($oBannerGet->storageType, $storageType,
                    "{$comboId}: storageType mismatch on View");
                break;

            // ----- DELETE -------------------------------------------------
            case 'Delete':
                $oBase = $this->_createBaseBanner($dllBanner, $oCamp->campaignId, $storageType, $comboId);

                $result = $dllBanner->delete($oBase->bannerId);
                $this->assertTrue($result,
                    "{$comboId} [{$accountType}, Delete, {$storageType}, {$campaignType}]: " . $dllBanner->getLastError());

                // Confirm deletion
                $oBannerGet = null;
                $resultGet = $dllBanner->getBanner($oBase->bannerId, $oBannerGet);
                $this->assertFalse($resultGet,
                    "{$comboId}: Banner should not exist after Delete");
                break;
        }

        // Reset maxFilesize
        $GLOBALS['_MAX']['CONF']['store']['maxFilesize'] = 0;

        // Clean up uploaded web files
        if ($storageType === 'web') {
            $webDir = $GLOBALS['_MAX']['CONF']['store']['webDir'];
            foreach (glob($webDir . '/*.gif') as $f) {
                @unlink($f);
            }
        }
    }

    // ---------------------------------------------------------------
    // Helper: verify capping on the stored DataObject
    // ---------------------------------------------------------------
    private function _verifyCapping($doBanner, $cappingType, $comboId)
    {
        switch ($cappingType) {
            case 'none':
                $this->assertEqual((int) $doBanner->capping, 0,
                    "{$comboId}: capping should be 0");
                $this->assertEqual((int) $doBanner->session_capping, 0,
                    "{$comboId}: session_capping should be 0");
                $this->assertEqual((int) $doBanner->block, 0,
                    "{$comboId}: block should be 0");
                break;
            case 'capping':
                $this->assertEqual((int) $doBanner->capping, 5,
                    "{$comboId}: capping should be 5");
                break;
            case 'session':
                $this->assertEqual((int) $doBanner->session_capping, 3,
                    "{$comboId}: session_capping should be 3");
                break;
            case 'block':
                $this->assertEqual((int) $doBanner->block, 3600,
                    "{$comboId}: block should be 3600");
                break;
            case 'all':
                $this->assertEqual((int) $doBanner->capping, 5,
                    "{$comboId}: capping should be 5");
                $this->assertEqual((int) $doBanner->session_capping, 3,
                    "{$comboId}: session_capping should be 3");
                $this->assertEqual((int) $doBanner->block, 3600,
                    "{$comboId}: block should be 3600");
                break;
        }
    }

    // ===============================================================
    // TEST: All 100 included pairwise combinations (Section 4C)
    // ===============================================================
    public function testIncludedCombinations()
    {
        $combos = $this->_getIncludedCombinations();

        foreach ($combos as $combo) {
            $this->_runIncludedCombo($combo);
        }
    }

    // ===============================================================
    // Section 4D — Excluded / negative combinations
    // ===============================================================

    /**
     * E001-E004: TRAFFICKER + any Banner CRUD operation → Access forbidden
     */
    public function testExcludedTraffickerCreateDenied()
    {
        $this->_assertTraffickerDenied('Create');
    }

    public function testExcludedTraffickerEditDenied()
    {
        $this->_assertTraffickerDenied('Edit');
    }

    public function testExcludedTraffickerViewDenied()
    {
        $this->_assertTraffickerDenied('View');
    }

    public function testExcludedTraffickerDeleteDenied()
    {
        $this->_assertTraffickerDenied('Delete');
    }

    private function _assertTraffickerDenied($mode)
    {
        $GLOBALS['_MAX']['CONF']['store']['mode'] = 0;
        $GLOBALS['_MAX']['CONF']['store']['webDir'] = MAX_PATH . '/var';
        $GLOBALS['_MAX']['CONF']['allowedBanners']['html'] = true;

        // Banner DLL mock returns false for permission checks
        $dllBannerDeny = new PartialMockOA_Dll_Banner_ComboDeny($this);
        $dllBannerDeny->setReturnValue('checkPermissions', false);

        // We still need a valid banner for Edit/View/Delete, so use an
        // unrestricted mock to create one first.
        $dllBannerAllow = new PartialMockOA_Dll_Banner_Combo($this);
        $dllBannerAllow->setReturnValue('checkPermissions', true);

        $oAdv  = $this->_createAdvertiser("TRAFF_{$mode}");
        $oCamp = $this->_createCampaign($oAdv->advertiserId, 'Remnant', "TRAFF_{$mode}");

        switch ($mode) {
            case 'Create':
                $oB = new OA_Dll_BannerInfo();
                $oB->campaignId  = $oCamp->campaignId;
                $oB->storageType = 'html';
                $oB->htmlTemplate = '<div>Trafficker test</div>';
                $result = $dllBannerDeny->modify($oB);
                $this->assertFalse($result, "TRAFFICKER + Create should be denied");
                $this->assertEqual($dllBannerDeny->getLastError(), 'Access forbidden');
                break;

            case 'Edit':
                $oBase = $this->_createBaseBanner($dllBannerAllow, $oCamp->campaignId, 'html', "TRAFF_Edit");
                $oEdit = new OA_Dll_BannerInfo();
                $oEdit->bannerId = $oBase->bannerId;
                $oEdit->bannerName = 'Trafficker edited';
                $result = $dllBannerDeny->modify($oEdit);
                $this->assertFalse($result, "TRAFFICKER + Edit should be denied");
                $this->assertEqual($dllBannerDeny->getLastError(), 'Access forbidden');
                break;

            case 'View':
                $oBase = $this->_createBaseBanner($dllBannerAllow, $oCamp->campaignId, 'html', "TRAFF_View");
                $oBannerGet = null;
                $result = $dllBannerDeny->getBanner($oBase->bannerId, $oBannerGet);
                $this->assertFalse($result, "TRAFFICKER + View should be denied");
                break;

            case 'Delete':
                $oBase = $this->_createBaseBanner($dllBannerAllow, $oCamp->campaignId, 'html', "TRAFF_Del");
                $result = $dllBannerDeny->delete($oBase->bannerId);
                $this->assertFalse($result, "TRAFFICKER + Delete should be denied");
                $this->assertEqual($dllBannerDeny->getLastError(), 'Access forbidden');
                break;
        }
    }

    /**
     * E005: ADVERTISER + Delete → Access forbidden
     */
    public function testExcludedAdvertiserDeleteDenied()
    {
        $GLOBALS['_MAX']['CONF']['store']['mode'] = 0;
        $GLOBALS['_MAX']['CONF']['store']['webDir'] = MAX_PATH . '/var';
        $GLOBALS['_MAX']['CONF']['allowedBanners']['html'] = true;

        $dllBannerDeny  = new PartialMockOA_Dll_Banner_ComboDeny($this);
        $dllBannerAllow = new PartialMockOA_Dll_Banner_Combo($this);
        $dllBannerDeny->setReturnValue('checkPermissions', false);
        $dllBannerAllow->setReturnValue('checkPermissions', true);

        $oAdv  = $this->_createAdvertiser('ADV_DEL');
        $oCamp = $this->_createCampaign($oAdv->advertiserId, 'Remnant', 'ADV_DEL');
        $oBase = $this->_createBaseBanner($dllBannerAllow, $oCamp->campaignId, 'html', 'ADV_DEL');

        $result = $dllBannerDeny->delete($oBase->bannerId);
        $this->assertFalse($result, "ADVERTISER + Delete should be denied");
        $this->assertEqual($dllBannerDeny->getLastError(), 'Access forbidden');
    }

    /**
     * E006-E007: sql/web + Create + no image → validation error
     */
    public function testExcludedSqlNoImageOnCreate()
    {
        $this->_assertNoImageOnCreate('sql');
    }

    public function testExcludedWebNoImageOnCreate()
    {
        $this->_assertNoImageOnCreate('web');
    }

    private function _assertNoImageOnCreate($storageType)
    {
        $GLOBALS['_MAX']['CONF']['store']['mode'] = 0;
        $GLOBALS['_MAX']['CONF']['store']['webDir'] = MAX_PATH . '/var';
        $GLOBALS['_MAX']['CONF']['allowedBanners']['sql'] = true;
        $GLOBALS['_MAX']['CONF']['allowedBanners']['web'] = true;

        $dllBanner = new PartialMockOA_Dll_Banner_Combo($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $oAdv  = $this->_createAdvertiser("NOIMG_{$storageType}");
        $oCamp = $this->_createCampaign($oAdv->advertiserId, 'Remnant', "NOIMG_{$storageType}");

        $oB = new OA_Dll_BannerInfo();
        $oB->campaignId  = $oCamp->campaignId;
        $oB->storageType = $storageType;
        // Deliberately no aImage
        $result = $dllBanner->modify($oB);
        $this->assertFalse($result,
            "{$storageType} + Create without image should fail");
        $this->assertEqual($dllBanner->getLastError(), "Field 'aImage' must not be empty");
    }

    /**
     * E008-E011: sql + Create + invalid image variants → validation error
     */
    public function testExcludedSqlCreateEmptyFilename()
    {
        $this->_assertCreateImageError('sql', 'emptyFilename', 'Image filename empty');
    }

    public function testExcludedSqlCreateEmptyContent()
    {
        $this->_assertCreateImageError('sql', 'emptyContent', 'Image content empty');
    }

    public function testExcludedSqlCreateWrongFormat()
    {
        $this->_assertCreateImageError('sql', 'wrongFormat', 'Unrecognized image file format');
    }

    public function testExcludedSqlCreateOversized()
    {
        $this->_assertCreateImageError('sql', 'oversized', 'Image file size is greater than 16 bytes');
    }

    /**
     * E012-E015: web + Create + invalid image variants → validation error
     */
    public function testExcludedWebCreateEmptyFilename()
    {
        $this->_assertCreateImageError('web', 'emptyFilename', 'Image filename empty');
    }

    public function testExcludedWebCreateEmptyContent()
    {
        $this->_assertCreateImageError('web', 'emptyContent', 'Image content empty');
    }

    public function testExcludedWebCreateWrongFormat()
    {
        $this->_assertCreateImageError('web', 'wrongFormat', 'Unrecognized image file format');
    }

    public function testExcludedWebCreateOversized()
    {
        $this->_assertCreateImageError('web', 'oversized', 'Image file size is greater than 16 bytes');
    }

    private function _assertCreateImageError($storageType, $imageType, $expectedError)
    {
        $GLOBALS['_MAX']['CONF']['store']['mode'] = 0;
        $GLOBALS['_MAX']['CONF']['store']['webDir'] = MAX_PATH . '/var';
        $GLOBALS['_MAX']['CONF']['store']['maxFilesize'] = 0;
        $GLOBALS['_MAX']['CONF']['allowedBanners']['sql'] = true;
        $GLOBALS['_MAX']['CONF']['allowedBanners']['web'] = true;

        $dllBanner = new PartialMockOA_Dll_Banner_Combo($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $oAdv  = $this->_createAdvertiser("IMG_{$storageType}_{$imageType}");
        $oCamp = $this->_createCampaign(
            $oAdv->advertiserId,
            'Remnant',
            "IMG_{$storageType}_{$imageType}",
        );

        $oB = new OA_Dll_BannerInfo();
        $oB->campaignId  = $oCamp->campaignId;
        $oB->storageType = $storageType;
        $this->_applyImage($oB, $imageType);

        $result = $dllBanner->modify($oB);
        $this->assertFalse($result,
            "{$storageType} + Create + {$imageType} should fail");
        $this->assertEqual($dllBanner->getLastError(), $expectedError,
            "{$storageType} + Create + {$imageType}: wrong error message");

        $GLOBALS['_MAX']['CONF']['store']['maxFilesize'] = 0;
    }

    /**
     * E016-E020: storage type not allowed → validation error
     */
    public function testExcludedSqlStorageDisabledOnCreate()
    {
        $this->_assertStorageDisabled('sql');
    }

    public function testExcludedWebStorageDisabledOnCreate()
    {
        $this->_assertStorageDisabled('web');
    }

    public function testExcludedUrlStorageDisabledOnCreate()
    {
        $this->_assertStorageDisabled('url');
    }

    public function testExcludedHtmlStorageDisabledOnCreate()
    {
        $this->_assertStorageDisabled('html');
    }

    public function testExcludedTxtStorageDisabledOnCreate()
    {
        $this->_assertStorageDisabled('txt');
    }

    private function _assertStorageDisabled($storageType)
    {
        $GLOBALS['_MAX']['CONF']['store']['mode'] = 0;
        $GLOBALS['_MAX']['CONF']['store']['webDir'] = MAX_PATH . '/var';

        // Disable ALL banner storage types
        $GLOBALS['_MAX']['CONF']['allowedBanners']['sql']  = false;
        $GLOBALS['_MAX']['CONF']['allowedBanners']['web']  = false;
        $GLOBALS['_MAX']['CONF']['allowedBanners']['url']  = false;
        $GLOBALS['_MAX']['CONF']['allowedBanners']['html'] = false;
        $GLOBALS['_MAX']['CONF']['allowedBanners']['text'] = false;

        $dllBanner = new PartialMockOA_Dll_Banner_Combo($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $oAdv  = $this->_createAdvertiser("DISABLED_{$storageType}");
        $oCamp = $this->_createCampaign($oAdv->advertiserId, 'Remnant', "DISABLED_{$storageType}");

        $oB = new OA_Dll_BannerInfo();
        $oB->campaignId  = $oCamp->campaignId;
        $oB->storageType = $storageType;
        $oB->htmlTemplate = '<div>test</div>';

        $result = $dllBanner->modify($oB);
        $this->assertFalse($result,
            "Create with disabled storage={$storageType} should fail");

        // Restore
        $GLOBALS['_MAX']['CONF']['allowedBanners']['sql']  = true;
        $GLOBALS['_MAX']['CONF']['allowedBanners']['web']  = true;
        $GLOBALS['_MAX']['CONF']['allowedBanners']['url']  = true;
        $GLOBALS['_MAX']['CONF']['allowedBanners']['html'] = true;
        $GLOBALS['_MAX']['CONF']['allowedBanners']['text'] = true;
    }

    /**
     * E021: Delete non-existent banner → error
     */
    public function testExcludedDeleteNonExistent()
    {
        $dllBanner = new PartialMockOA_Dll_Banner_Combo($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $result = $dllBanner->delete(999999);
        $this->assertFalse($result, "Delete non-existent banner should fail");
        $this->assertEqual($dllBanner->getLastError(), 'Unknown bannerId Error');
    }

    /**
     * E022: View non-existent banner → error
     */
    public function testExcludedViewNonExistent()
    {
        $dllBanner = new PartialMockOA_Dll_Banner_Combo($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $oBannerGet = null;
        $result = $dllBanner->getBanner(999999, $oBannerGet);
        $this->assertFalse($result, "View non-existent banner should fail");
        $this->assertEqual($dllBanner->getLastError(), 'Unknown bannerId Error');
    }

    /**
     * E023: Edit non-existent banner → error
     */
    public function testExcludedEditNonExistent()
    {
        $dllBanner = new PartialMockOA_Dll_Banner_Combo($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $oB = new OA_Dll_BannerInfo();
        $oB->bannerId = 999999;
        $oB->bannerName = 'Ghost';
        $result = $dllBanner->modify($oB);
        $this->assertFalse($result, "Edit non-existent banner should fail");
        $this->assertEqual($dllBanner->getLastError(), 'Unknown bannerId Error');
    }

    /**
     * E024: Create banner with non-existent campaign → error
     */
    public function testExcludedCreateWithNonExistentCampaign()
    {
        $GLOBALS['_MAX']['CONF']['allowedBanners']['html'] = true;

        $dllBanner = new PartialMockOA_Dll_Banner_Combo($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $oB = new OA_Dll_BannerInfo();
        $oB->campaignId  = 999999;
        $oB->storageType = 'html';
        $oB->htmlTemplate = '<div>orphan</div>';
        $result = $dllBanner->modify($oB);
        $this->assertFalse($result, "Create with non-existent campaign should fail");
        $this->assertEqual($dllBanner->getLastError(), 'Unknown campaignId Error');
    }
}
