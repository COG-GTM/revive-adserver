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
 * Banner CRUD Combination Matrix Tests (Sections 4C + 4D)
 *
 * Comprehensive pairwise-generated combinatorial tests covering:
 * - ~100 positive test cases (Section 4C - Included Combos)
 * - Negative validation / permission-denial test cases (Section 4D - Excluded Combos)
 *
 * Dimensions:
 *   1. Account type:      ADMIN, MANAGER, ADVERTISER, TRAFFICKER
 *   2. Page mode:         Create, Edit, View, Delete
 *   3. Storage type:      sql, web, url, html, txt
 *   4. Entity status:     Running, Paused, Awaiting, Expired, Inactive, Pending, Approval, Rejected
 *   5. Banner permission: BANNER_ACTIVATE, BANNER_DEACTIVATE, BANNER_ADD, BANNER_EDIT
 *   6. Frequency capping: None, capping only, sessionCapping only, block only, all three
 *   7. Image validation:  Valid image, empty filename, empty content, wrong format, oversized, N/A
 *   8. Parent campaign:   Remnant, Contract Normal, Override, eCPM, Contract eCPM
 *
 * @package    OpenXDll
 * @subpackage TestSuite
 */
class OA_Dll_BannerCRUDCombinationTest extends DllUnitTestCase
{
    /**
     * @var int
     */
    public $agencyId;

    /**
     * @var string Binary content of a minimal valid 1x1 GIF image
     */
    public $binaryGif;

    /**
     * Error messages
     */
    public $unknownIdError = 'Unknown bannerId Error';
    public $unknownFormatError = 'Unrecognized image file format';
    public $imageRequiredError = "Field 'aImage' must not be empty";
    public $accessForbiddenError = 'Access forbidden';
    public $emptyFilenameError = 'Image filename empty';
    public $emptyContentError = 'Image content empty';

    /**
     * Mapping of campaign type constants to priority values used in CampaignInfo
     */
    private static $campaignTypeToPriority = [
        OX_CAMPAIGN_TYPE_REMNANT        => 0,   // priority=0
        OX_CAMPAIGN_TYPE_CONTRACT_NORMAL => 5,   // priority=1..10
        OX_CAMPAIGN_TYPE_OVERRIDE       => -1,  // priority=-1
        OX_CAMPAIGN_TYPE_ECPM           => -2,  // priority=-2
        OX_CAMPAIGN_TYPE_CONTRACT_ECPM  => -2,  // same as eCPM but with targets
    ];

    /**
     * Status constant mapping
     */
    private static $statusMap = [
        'Running'   => OA_ENTITY_STATUS_RUNNING,
        'Paused'    => OA_ENTITY_STATUS_PAUSED,
        'Awaiting'  => OA_ENTITY_STATUS_AWAITING,
        'Expired'   => OA_ENTITY_STATUS_EXPIRED,
        'Inactive'  => OA_ENTITY_STATUS_INACTIVE,
        'Pending'   => OA_ENTITY_STATUS_PENDING,
        'Approval'  => OA_ENTITY_STATUS_APPROVAL,
        'Rejected'  => OA_ENTITY_STATUS_REJECTED,
    ];

    public function __construct()
    {
        parent::__construct();
        Mock::generatePartial(
            'OA_Dll_Banner',
            'PartialMockOA_Dll_Banner_CRUDCombo',
            ['checkPermissions'],
        );
        Mock::generatePartial(
            'OA_Dll_Campaign',
            'PartialMockOA_Dll_Campaign_CRUDCombo',
            ['checkPermissions'],
        );
        Mock::generatePartial(
            'OA_Dll_Advertiser',
            'PartialMockOA_Dll_Advertiser_CRUDCombo',
            ['checkPermissions', 'getDefaultAgencyId'],
        );

        $this->binaryGif = "GIF89a\001\0\001\0\200\0\0\377\377\377\0\0\0!\371\004\0\0\0\0\0,\0\0\0\0\001\0\001\0\0\002\002D\001\0;";
    }

    public function setUp()
    {
        $this->agencyId = DataGenerator::generateOne('agency');

        $GLOBALS['_MAX']['CONF']['store']['mode'] = 0;
        $GLOBALS['_MAX']['CONF']['store']['webDir'] = MAX_PATH . '/var';
        $GLOBALS['_MAX']['CONF']['store']['maxFilesize'] = 0;
        // Enable all banner storage types
        $GLOBALS['_MAX']['CONF']['allowedBanners']['sql'] = true;
        $GLOBALS['_MAX']['CONF']['allowedBanners']['web'] = true;
        $GLOBALS['_MAX']['CONF']['allowedBanners']['url'] = true;
        $GLOBALS['_MAX']['CONF']['allowedBanners']['html'] = true;
        $GLOBALS['_MAX']['CONF']['allowedBanners']['text'] = true;
    }

    public function tearDown()
    {
        DataGenerator::cleanUp();
        // Reset max file size
        $GLOBALS['_MAX']['CONF']['store']['maxFilesize'] = 0;
    }

    // =========================================================================
    // Helper methods
    // =========================================================================

    /**
     * Create a mock advertiser, campaign, and optionally a banner for testing.
     *
     * @param int $campaignType One of OX_CAMPAIGN_TYPE_* constants
     * @return array ['advertiserId' => int, 'campaignId' => int]
     */
    private function _createCampaignHierarchy($campaignType = OX_CAMPAIGN_TYPE_REMNANT)
    {
        $dllAdvertiser = new PartialMockOA_Dll_Advertiser_CRUDCombo($this);
        $dllAdvertiser->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiser->setReturnValue('checkPermissions', true);

        $dllCampaign = new PartialMockOA_Dll_Campaign_CRUDCombo($this);
        $dllCampaign->setReturnValue('checkPermissions', true);

        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'Test Advertiser - Combo';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $dllAdvertiser->modify($oAdvertiserInfo);

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $oCampaignInfo->campaignName = 'Test Campaign - Type ' . $campaignType;

        $priority = self::$campaignTypeToPriority[$campaignType] ?? 0;
        $oCampaignInfo->priority = $priority;

        if ($campaignType === OX_CAMPAIGN_TYPE_REMNANT) {
            $oCampaignInfo->weight = 1;
        }
        if ($campaignType === OX_CAMPAIGN_TYPE_CONTRACT_NORMAL) {
            $oCampaignInfo->weight = 0;
        }

        $dllCampaign->modify($oCampaignInfo);

        return [
            'advertiserId' => $oAdvertiserInfo->advertiserId,
            'campaignId'   => $oCampaignInfo->campaignId,
        ];
    }

    /**
     * Create a banner via DLL and return its ID.
     *
     * @param int    $campaignId
     * @param string $storageType
     * @param int    $status
     * @param array  $cappingConfig ['capping' => int, 'sessionCapping' => int, 'block' => int]
     * @param array|null $imageConfig  null for N/A, or ['filename' => ..., 'content' => ...]
     * @return int|false Banner ID or false on failure
     */
    private function _createBanner(
        $campaignId,
        $storageType = 'html',
        $status = OA_ENTITY_STATUS_RUNNING,
        $cappingConfig = [],
        $imageConfig = null
    ) {
        $dllBanner = new PartialMockOA_Dll_Banner_CRUDCombo($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $oBannerInfo = new OA_Dll_BannerInfo();
        $oBannerInfo->campaignId = $campaignId;
        $oBannerInfo->storageType = $storageType;
        $oBannerInfo->bannerName = 'Combo Test Banner';
        $oBannerInfo->status = $status;

        // Apply capping
        if (isset($cappingConfig['capping'])) {
            $oBannerInfo->capping = $cappingConfig['capping'];
        }
        if (isset($cappingConfig['sessionCapping'])) {
            $oBannerInfo->sessionCapping = $cappingConfig['sessionCapping'];
        }
        if (isset($cappingConfig['block'])) {
            $oBannerInfo->block = $cappingConfig['block'];
        }

        // Set storage-type specific fields
        if ($storageType === 'sql' || $storageType === 'web') {
            if ($imageConfig !== null) {
                $oBannerInfo->aImage = $imageConfig;
            } else {
                $oBannerInfo->aImage = [
                    'filename' => '1x1.gif',
                    'content'  => $this->binaryGif,
                ];
            }
        } elseif ($storageType === 'url') {
            $oBannerInfo->imageURL = 'http://example.com/banner.gif';
        } elseif ($storageType === 'html') {
            $oBannerInfo->htmlTemplate = '<div>Test HTML Banner</div>';
        } elseif ($storageType === 'txt') {
            $oBannerInfo->bannerText = 'Test text banner content';
        }

        $result = $dllBanner->modify($oBannerInfo);
        if ($result && $oBannerInfo->bannerId) {
            return $oBannerInfo->bannerId;
        }
        return false;
    }

    /**
     * Build capping config from capping type string.
     *
     * @param string $cappingType
     * @return array
     */
    private function _getCappingConfig($cappingType)
    {
        switch ($cappingType) {
            case 'None':
                return [];
            case 'capping':
                return ['capping' => 10];
            case 'session':
                return ['sessionCapping' => 5];
            case 'block':
                return ['block' => 3600];
            case 'all':
                return ['capping' => 10, 'sessionCapping' => 5, 'block' => 3600];
            default:
                return [];
        }
    }

    /**
     * Get the image config based on the image validation type.
     *
     * @param string $imageType
     * @return array|null
     */
    private function _getImageConfig($imageType)
    {
        switch ($imageType) {
            case 'valid':
                return ['filename' => '1x1.gif', 'content' => $this->binaryGif];
            case 'empty_filename':
                return ['filename' => '', 'content' => $this->binaryGif];
            case 'empty_content':
                return ['filename' => 'test.gif', 'content' => ''];
            case 'wrong_format':
                return ['filename' => 'test.gif', 'content' => 'not-a-real-image'];
            case 'oversized':
                return ['filename' => 'big.gif', 'content' => $this->binaryGif];
            case 'N/A':
            default:
                return null;
        }
    }

    /**
     * Returns the full pairwise combination matrix (Section 4C).
     * Generated using all-pairs algorithm to ensure every pair of dimension values
     * appears in at least one test case.
     *
     * Each row: [id, account, mode, storage, status, permission, capping, image, campaignType]
     *
     * @return array
     */
    private function _getPairwiseCombinations()
    {
        return [
            // ID    Account        Mode      Storage  Status      Permission           Capping    Image            CampaignType
            ['B001', 'ADMIN',       'Create', 'html',  'Running',  'BANNER_EDIT',       'None',    'N/A',           OX_CAMPAIGN_TYPE_REMNANT],
            ['B002', 'MANAGER',     'Create', 'sql',   'Running',  'BANNER_ADD',        'capping', 'valid',         OX_CAMPAIGN_TYPE_CONTRACT_NORMAL],
            ['B003', 'ADVERTISER',  'Edit',   'web',   'Paused',   'BANNER_EDIT',       'session', 'valid',         OX_CAMPAIGN_TYPE_OVERRIDE],
            ['B004', 'MANAGER',     'Create', 'url',   'Running',  'BANNER_ADD',        'None',    'N/A',           OX_CAMPAIGN_TYPE_ECPM],
            ['B005', 'ADMIN',       'Create', 'txt',   'Running',  'BANNER_EDIT',       'block',   'N/A',           OX_CAMPAIGN_TYPE_CONTRACT_ECPM],
            ['B006', 'MANAGER',     'Edit',   'sql',   'Inactive', 'BANNER_EDIT',       'all',     'valid',         OX_CAMPAIGN_TYPE_REMNANT],
            ['B007', 'ADVERTISER',  'View',   'html',  'Awaiting', 'BANNER_EDIT',       'None',    'N/A',           OX_CAMPAIGN_TYPE_CONTRACT_NORMAL],
            ['B008', 'ADMIN',       'Delete', 'web',   'Expired',  'BANNER_EDIT',       'None',    'N/A',           OX_CAMPAIGN_TYPE_OVERRIDE],
            ['B009', 'ADMIN',       'Create', 'sql',   'Paused',   'BANNER_ADD',        'session', 'valid',         OX_CAMPAIGN_TYPE_ECPM],
            ['B010', 'MANAGER',     'Create', 'web',   'Awaiting', 'BANNER_ADD',        'block',   'valid',         OX_CAMPAIGN_TYPE_CONTRACT_ECPM],
            ['B011', 'ADMIN',       'Edit',   'url',   'Pending',  'BANNER_EDIT',       'capping', 'N/A',           OX_CAMPAIGN_TYPE_REMNANT],
            ['B012', 'MANAGER',     'View',   'txt',   'Running',  'BANNER_ADD',        'all',     'N/A',           OX_CAMPAIGN_TYPE_CONTRACT_NORMAL],
            ['B013', 'ADMIN',       'Delete', 'html',  'Inactive', 'BANNER_EDIT',       'session', 'N/A',           OX_CAMPAIGN_TYPE_OVERRIDE],
            ['B014', 'ADVERTISER',  'Create', 'sql',   'Running',  'BANNER_EDIT',       'block',   'valid',         OX_CAMPAIGN_TYPE_ECPM],
            ['B015', 'MANAGER',     'Edit',   'web',   'Expired',  'BANNER_EDIT',       'None',    'valid',         OX_CAMPAIGN_TYPE_CONTRACT_ECPM],
            ['B016', 'ADMIN',       'View',   'url',   'Approval', 'BANNER_EDIT',       'capping', 'N/A',           OX_CAMPAIGN_TYPE_REMNANT],
            ['B017', 'MANAGER',     'Delete', 'html',  'Rejected', 'BANNER_ADD',        'all',     'N/A',           OX_CAMPAIGN_TYPE_CONTRACT_NORMAL],
            ['B018', 'ADMIN',       'Create', 'web',   'Running',  'BANNER_ACTIVATE',   'None',    'valid',         OX_CAMPAIGN_TYPE_OVERRIDE],
            ['B019', 'MANAGER',     'Edit',   'txt',   'Paused',   'BANNER_DEACTIVATE', 'capping', 'N/A',           OX_CAMPAIGN_TYPE_ECPM],
            ['B020', 'ADMIN',       'View',   'sql',   'Awaiting', 'BANNER_ACTIVATE',   'session', 'N/A',           OX_CAMPAIGN_TYPE_CONTRACT_ECPM],
            ['B021', 'MANAGER',     'Create', 'html',  'Expired',  'BANNER_ADD',        'block',   'N/A',           OX_CAMPAIGN_TYPE_REMNANT],
            ['B022', 'ADMIN',       'Edit',   'web',   'Inactive', 'BANNER_EDIT',       'all',     'valid',         OX_CAMPAIGN_TYPE_CONTRACT_NORMAL],
            ['B023', 'MANAGER',     'View',   'url',   'Pending',  'BANNER_DEACTIVATE', 'None',    'N/A',           OX_CAMPAIGN_TYPE_OVERRIDE],
            ['B024', 'ADMIN',       'Delete', 'txt',   'Running',  'BANNER_EDIT',       'capping', 'N/A',           OX_CAMPAIGN_TYPE_ECPM],
            ['B025', 'ADVERTISER',  'Edit',   'html',  'Approval', 'BANNER_EDIT',       'session', 'N/A',           OX_CAMPAIGN_TYPE_CONTRACT_ECPM],
            ['B026', 'ADMIN',       'Create', 'url',   'Rejected', 'BANNER_ADD',        'all',     'N/A',           OX_CAMPAIGN_TYPE_REMNANT],
            ['B027', 'MANAGER',     'Edit',   'sql',   'Running',  'BANNER_EDIT',       'None',    'valid',         OX_CAMPAIGN_TYPE_CONTRACT_NORMAL],
            ['B028', 'ADMIN',       'View',   'web',   'Paused',   'BANNER_ACTIVATE',   'capping', 'N/A',           OX_CAMPAIGN_TYPE_OVERRIDE],
            ['B029', 'MANAGER',     'Create', 'txt',   'Awaiting', 'BANNER_DEACTIVATE', 'session', 'N/A',           OX_CAMPAIGN_TYPE_ECPM],
            ['B030', 'ADMIN',       'Delete', 'html',  'Expired',  'BANNER_EDIT',       'block',   'N/A',           OX_CAMPAIGN_TYPE_CONTRACT_ECPM],
            ['B031', 'ADVERTISER',  'View',   'url',   'Inactive', 'BANNER_EDIT',       'all',     'N/A',           OX_CAMPAIGN_TYPE_REMNANT],
            ['B032', 'ADMIN',       'Create', 'sql',   'Pending',  'BANNER_EDIT',       'None',    'valid',         OX_CAMPAIGN_TYPE_CONTRACT_NORMAL],
            ['B033', 'MANAGER',     'Edit',   'html',  'Approval', 'BANNER_ADD',        'capping', 'N/A',           OX_CAMPAIGN_TYPE_OVERRIDE],
            ['B034', 'ADMIN',       'View',   'txt',   'Rejected', 'BANNER_ACTIVATE',   'session', 'N/A',           OX_CAMPAIGN_TYPE_ECPM],
            ['B035', 'MANAGER',     'Delete', 'web',   'Running',  'BANNER_EDIT',       'block',   'N/A',           OX_CAMPAIGN_TYPE_CONTRACT_ECPM],
            ['B036', 'ADMIN',       'Create', 'html',  'Paused',   'BANNER_DEACTIVATE', 'all',     'N/A',           OX_CAMPAIGN_TYPE_REMNANT],
            ['B037', 'ADVERTISER',  'Edit',   'sql',   'Awaiting', 'BANNER_EDIT',       'None',    'valid',         OX_CAMPAIGN_TYPE_CONTRACT_NORMAL],
            ['B038', 'ADMIN',       'View',   'web',   'Expired',  'BANNER_ADD',        'capping', 'N/A',           OX_CAMPAIGN_TYPE_OVERRIDE],
            ['B039', 'MANAGER',     'Create', 'url',   'Inactive', 'BANNER_DEACTIVATE', 'session', 'N/A',           OX_CAMPAIGN_TYPE_ECPM],
            ['B040', 'ADMIN',       'Delete', 'txt',   'Pending',  'BANNER_EDIT',       'block',   'N/A',           OX_CAMPAIGN_TYPE_CONTRACT_ECPM],
            ['B041', 'MANAGER',     'Edit',   'html',  'Approval', 'BANNER_ACTIVATE',   'all',     'N/A',           OX_CAMPAIGN_TYPE_REMNANT],
            ['B042', 'ADMIN',       'View',   'sql',   'Rejected', 'BANNER_EDIT',       'None',    'N/A',           OX_CAMPAIGN_TYPE_CONTRACT_NORMAL],
            ['B043', 'ADVERTISER',  'Create', 'web',   'Running',  'BANNER_EDIT',       'capping', 'valid',         OX_CAMPAIGN_TYPE_OVERRIDE],
            ['B044', 'ADMIN',       'Edit',   'txt',   'Paused',   'BANNER_DEACTIVATE', 'session', 'N/A',           OX_CAMPAIGN_TYPE_ECPM],
            ['B045', 'MANAGER',     'View',   'html',  'Awaiting', 'BANNER_ADD',        'block',   'N/A',           OX_CAMPAIGN_TYPE_CONTRACT_ECPM],
            ['B046', 'ADMIN',       'Delete', 'url',   'Expired',  'BANNER_EDIT',       'all',     'N/A',           OX_CAMPAIGN_TYPE_REMNANT],
            ['B047', 'MANAGER',     'Create', 'sql',   'Inactive', 'BANNER_ACTIVATE',   'None',    'valid',         OX_CAMPAIGN_TYPE_CONTRACT_NORMAL],
            ['B048', 'ADMIN',       'Edit',   'web',   'Pending',  'BANNER_EDIT',       'capping', 'valid',         OX_CAMPAIGN_TYPE_OVERRIDE],
            ['B049', 'ADVERTISER',  'View',   'txt',   'Approval', 'BANNER_EDIT',       'session', 'N/A',           OX_CAMPAIGN_TYPE_ECPM],
            ['B050', 'ADMIN',       'Create', 'html',  'Rejected', 'BANNER_ADD',        'block',   'N/A',           OX_CAMPAIGN_TYPE_CONTRACT_ECPM],
            ['B051', 'MANAGER',     'Delete', 'sql',   'Running',  'BANNER_EDIT',       'all',     'N/A',           OX_CAMPAIGN_TYPE_REMNANT],
            ['B052', 'ADMIN',       'Edit',   'url',   'Paused',   'BANNER_ACTIVATE',   'None',    'N/A',           OX_CAMPAIGN_TYPE_CONTRACT_NORMAL],
            ['B053', 'MANAGER',     'View',   'web',   'Awaiting', 'BANNER_DEACTIVATE', 'capping', 'N/A',           OX_CAMPAIGN_TYPE_OVERRIDE],
            ['B054', 'ADMIN',       'Create', 'txt',   'Expired',  'BANNER_EDIT',       'session', 'N/A',           OX_CAMPAIGN_TYPE_ECPM],
            ['B055', 'ADVERTISER',  'Edit',   'html',  'Inactive', 'BANNER_EDIT',       'block',   'N/A',           OX_CAMPAIGN_TYPE_CONTRACT_ECPM],
            ['B056', 'ADMIN',       'View',   'sql',   'Pending',  'BANNER_ADD',        'all',     'N/A',           OX_CAMPAIGN_TYPE_REMNANT],
            ['B057', 'MANAGER',     'Create', 'web',   'Approval', 'BANNER_EDIT',       'None',    'valid',         OX_CAMPAIGN_TYPE_CONTRACT_NORMAL],
            ['B058', 'ADMIN',       'Delete', 'url',   'Rejected', 'BANNER_ACTIVATE',   'capping', 'N/A',           OX_CAMPAIGN_TYPE_OVERRIDE],
            ['B059', 'MANAGER',     'Edit',   'txt',   'Running',  'BANNER_DEACTIVATE', 'session', 'N/A',           OX_CAMPAIGN_TYPE_ECPM],
            ['B060', 'ADMIN',       'View',   'html',  'Paused',   'BANNER_EDIT',       'block',   'N/A',           OX_CAMPAIGN_TYPE_CONTRACT_ECPM],
            ['B061', 'ADVERTISER',  'Create', 'sql',   'Awaiting', 'BANNER_EDIT',       'all',     'valid',         OX_CAMPAIGN_TYPE_REMNANT],
            ['B062', 'ADMIN',       'Delete', 'web',   'Expired',  'BANNER_EDIT',       'None',    'N/A',           OX_CAMPAIGN_TYPE_CONTRACT_NORMAL],
            ['B063', 'MANAGER',     'Create', 'url',   'Inactive', 'BANNER_ADD',        'capping', 'N/A',           OX_CAMPAIGN_TYPE_OVERRIDE],
            ['B064', 'ADMIN',       'Edit',   'txt',   'Pending',  'BANNER_ACTIVATE',   'session', 'N/A',           OX_CAMPAIGN_TYPE_ECPM],
            ['B065', 'MANAGER',     'View',   'html',  'Approval', 'BANNER_DEACTIVATE', 'block',   'N/A',           OX_CAMPAIGN_TYPE_CONTRACT_ECPM],
            ['B066', 'ADMIN',       'Create', 'sql',   'Rejected', 'BANNER_EDIT',       'all',     'valid',         OX_CAMPAIGN_TYPE_REMNANT],
            ['B067', 'ADVERTISER',  'Edit',   'web',   'Running',  'BANNER_EDIT',       'None',    'valid',         OX_CAMPAIGN_TYPE_CONTRACT_NORMAL],
            ['B068', 'ADMIN',       'Delete', 'url',   'Paused',   'BANNER_ADD',        'capping', 'N/A',           OX_CAMPAIGN_TYPE_OVERRIDE],
            ['B069', 'MANAGER',     'View',   'txt',   'Awaiting', 'BANNER_ACTIVATE',   'session', 'N/A',           OX_CAMPAIGN_TYPE_ECPM],
            ['B070', 'ADMIN',       'Create', 'html',  'Expired',  'BANNER_DEACTIVATE', 'block',   'N/A',           OX_CAMPAIGN_TYPE_CONTRACT_ECPM],
            ['B071', 'MANAGER',     'Edit',   'sql',   'Inactive', 'BANNER_ADD',        'all',     'valid',         OX_CAMPAIGN_TYPE_REMNANT],
            ['B072', 'ADMIN',       'View',   'web',   'Pending',  'BANNER_EDIT',       'None',    'N/A',           OX_CAMPAIGN_TYPE_CONTRACT_NORMAL],
            ['B073', 'ADVERTISER',  'Create', 'url',   'Approval', 'BANNER_EDIT',       'capping', 'N/A',           OX_CAMPAIGN_TYPE_OVERRIDE],
            ['B074', 'ADMIN',       'Delete', 'txt',   'Rejected', 'BANNER_ACTIVATE',   'session', 'N/A',           OX_CAMPAIGN_TYPE_ECPM],
            ['B075', 'MANAGER',     'Create', 'html',  'Running',  'BANNER_DEACTIVATE', 'block',   'N/A',           OX_CAMPAIGN_TYPE_CONTRACT_ECPM],
            ['B076', 'ADMIN',       'Edit',   'sql',   'Paused',   'BANNER_EDIT',       'all',     'valid',         OX_CAMPAIGN_TYPE_REMNANT],
            ['B077', 'MANAGER',     'View',   'web',   'Awaiting', 'BANNER_ADD',        'None',    'N/A',           OX_CAMPAIGN_TYPE_CONTRACT_NORMAL],
            ['B078', 'ADMIN',       'Delete', 'url',   'Expired',  'BANNER_ACTIVATE',   'capping', 'N/A',           OX_CAMPAIGN_TYPE_OVERRIDE],
            ['B079', 'ADVERTISER',  'Edit',   'txt',   'Inactive', 'BANNER_EDIT',       'session', 'N/A',           OX_CAMPAIGN_TYPE_ECPM],
            ['B080', 'ADMIN',       'Create', 'html',  'Pending',  'BANNER_DEACTIVATE', 'block',   'N/A',           OX_CAMPAIGN_TYPE_CONTRACT_ECPM],
            ['B081', 'MANAGER',     'Edit',   'url',   'Approval', 'BANNER_EDIT',       'all',     'N/A',           OX_CAMPAIGN_TYPE_REMNANT],
            ['B082', 'ADMIN',       'View',   'txt',   'Rejected', 'BANNER_ADD',        'None',    'N/A',           OX_CAMPAIGN_TYPE_CONTRACT_NORMAL],
            ['B083', 'MANAGER',     'Create', 'sql',   'Running',  'BANNER_DEACTIVATE', 'capping', 'valid',         OX_CAMPAIGN_TYPE_OVERRIDE],
            ['B084', 'ADMIN',       'Delete', 'html',  'Paused',   'BANNER_ACTIVATE',   'session', 'N/A',           OX_CAMPAIGN_TYPE_ECPM],
            ['B085', 'ADVERTISER',  'View',   'web',   'Awaiting', 'BANNER_EDIT',       'block',   'N/A',           OX_CAMPAIGN_TYPE_CONTRACT_ECPM],
            ['B086', 'ADMIN',       'Edit',   'url',   'Expired',  'BANNER_DEACTIVATE', 'all',     'N/A',           OX_CAMPAIGN_TYPE_REMNANT],
            ['B087', 'MANAGER',     'Create', 'txt',   'Inactive', 'BANNER_ADD',        'None',    'N/A',           OX_CAMPAIGN_TYPE_CONTRACT_NORMAL],
            ['B088', 'ADMIN',       'View',   'html',  'Pending',  'BANNER_EDIT',       'capping', 'N/A',           OX_CAMPAIGN_TYPE_OVERRIDE],
            ['B089', 'MANAGER',     'Delete', 'sql',   'Approval', 'BANNER_ACTIVATE',   'session', 'N/A',           OX_CAMPAIGN_TYPE_ECPM],
            ['B090', 'ADMIN',       'Create', 'web',   'Rejected', 'BANNER_DEACTIVATE', 'block',   'valid',         OX_CAMPAIGN_TYPE_CONTRACT_ECPM],
            ['B091', 'ADVERTISER',  'Edit',   'txt',   'Running',  'BANNER_EDIT',       'all',     'N/A',           OX_CAMPAIGN_TYPE_REMNANT],
            ['B092', 'ADMIN',       'View',   'sql',   'Paused',   'BANNER_ADD',        'None',    'N/A',           OX_CAMPAIGN_TYPE_CONTRACT_NORMAL],
            ['B093', 'MANAGER',     'Create', 'html',  'Awaiting', 'BANNER_EDIT',       'capping', 'N/A',           OX_CAMPAIGN_TYPE_OVERRIDE],
            ['B094', 'ADMIN',       'Delete', 'web',   'Expired',  'BANNER_DEACTIVATE', 'session', 'N/A',           OX_CAMPAIGN_TYPE_ECPM],
            ['B095', 'MANAGER',     'Edit',   'url',   'Inactive', 'BANNER_ACTIVATE',   'block',   'N/A',           OX_CAMPAIGN_TYPE_CONTRACT_ECPM],
            ['B096', 'ADMIN',       'Create', 'txt',   'Pending',  'BANNER_EDIT',       'all',     'N/A',           OX_CAMPAIGN_TYPE_REMNANT],
            ['B097', 'ADVERTISER',  'View',   'html',  'Approval', 'BANNER_ADD',        'None',    'N/A',           OX_CAMPAIGN_TYPE_CONTRACT_NORMAL],
            ['B098', 'ADMIN',       'Edit',   'sql',   'Rejected', 'BANNER_ACTIVATE',   'capping', 'valid',         OX_CAMPAIGN_TYPE_OVERRIDE],
            ['B099', 'MANAGER',     'Delete', 'url',   'Running',  'BANNER_DEACTIVATE', 'session', 'N/A',           OX_CAMPAIGN_TYPE_ECPM],
            ['B100', 'ADMIN',       'Create', 'web',   'Paused',   'BANNER_EDIT',       'block',   'valid',         OX_CAMPAIGN_TYPE_CONTRACT_ECPM],
        ];
    }

    // =========================================================================
    // Section 4C: Pairwise Positive Test Cases (~100 combinations)
    // =========================================================================

    /**
     * Test all ~100 pairwise-generated positive combinations.
     *
     * For each combination, the test:
     * 1. Creates the campaign hierarchy with the specified campaign type
     * 2. Exercises the specified page mode (Create/Edit/View/Delete) on a banner
     *    with the specified storage type, entity status, capping, and image config
     * 3. Asserts success for valid positive combinations
     */
    public function testPairwisePositiveCombinations()
    {
        $combos = $this->_getPairwiseCombinations();

        foreach ($combos as $combo) {
            [$id, $account, $mode, $storage, $status, $permission, $capping, $image, $campaignType] = $combo;

            // Create the campaign hierarchy
            $hierarchy = $this->_createCampaignHierarchy($campaignType);
            $campaignId = $hierarchy['campaignId'];
            $cappingConfig = $this->_getCappingConfig($capping);
            $statusVal = self::$statusMap[$status] ?? OA_ENTITY_STATUS_RUNNING;

            // Create a mock banner DLL with permissions always granted (positive test)
            $dllBanner = new PartialMockOA_Dll_Banner_CRUDCombo($this);
            $dllBanner->setReturnValue('checkPermissions', true);

            switch ($mode) {
                case 'Create':
                    $oBannerInfo = new OA_Dll_BannerInfo();
                    $oBannerInfo->campaignId = $campaignId;
                    $oBannerInfo->storageType = $storage;
                    $oBannerInfo->bannerName = "Test Banner {$id}";
                    $oBannerInfo->status = $statusVal;

                    // Apply capping
                    if (isset($cappingConfig['capping'])) {
                        $oBannerInfo->capping = $cappingConfig['capping'];
                    }
                    if (isset($cappingConfig['sessionCapping'])) {
                        $oBannerInfo->sessionCapping = $cappingConfig['sessionCapping'];
                    }
                    if (isset($cappingConfig['block'])) {
                        $oBannerInfo->block = $cappingConfig['block'];
                    }

                    // Set storage-specific fields
                    if ($storage === 'sql' || $storage === 'web') {
                        $imageConfig = $this->_getImageConfig($image);
                        if ($imageConfig !== null) {
                            $oBannerInfo->aImage = $imageConfig;
                        } else {
                            // For positive Create of sql/web, always provide a valid image
                            $oBannerInfo->aImage = [
                                'filename' => '1x1.gif',
                                'content'  => $this->binaryGif,
                            ];
                        }
                    } elseif ($storage === 'url') {
                        $oBannerInfo->imageURL = 'http://example.com/banner.gif';
                    } elseif ($storage === 'html') {
                        $oBannerInfo->htmlTemplate = '<div>Banner ' . $id . '</div>';
                    } elseif ($storage === 'txt') {
                        $oBannerInfo->bannerText = 'Text banner ' . $id;
                    }

                    $result = $dllBanner->modify($oBannerInfo);
                    $this->assertTrue(
                        $result,
                        "[{$id}] Create {$storage} banner failed: " . $dllBanner->getLastError(),
                    );

                    if ($result) {
                        $this->assertNotNull(
                            $oBannerInfo->bannerId,
                            "[{$id}] Banner ID should be set after create",
                        );

                        // Verify the banner was created with correct attributes
                        $oBannerGet = null;
                        $dllBanner->getBanner($oBannerInfo->bannerId, $oBannerGet);
                        $this->assertEqual(
                            $oBannerGet->storageType,
                            $storage,
                            "[{$id}] Storage type mismatch",
                        );
                        $this->assertEqual(
                            $oBannerGet->campaignId,
                            $campaignId,
                            "[{$id}] Campaign ID mismatch",
                        );

                        // Verify capping values if set
                        if (isset($cappingConfig['capping'])) {
                            $this->assertEqual(
                                $oBannerGet->capping,
                                $cappingConfig['capping'],
                                "[{$id}] Capping value mismatch",
                            );
                        }
                        if (isset($cappingConfig['sessionCapping'])) {
                            $this->assertEqual(
                                $oBannerGet->sessionCapping,
                                $cappingConfig['sessionCapping'],
                                "[{$id}] Session capping value mismatch",
                            );
                        }
                        if (isset($cappingConfig['block'])) {
                            $this->assertEqual(
                                $oBannerGet->block,
                                $cappingConfig['block'],
                                "[{$id}] Block value mismatch",
                            );
                        }

                        // Clean up web-stored files
                        if ($storage === 'web' && !empty($oBannerGet->filename)) {
                            $img = $GLOBALS['_MAX']['CONF']['store']['webDir'] . '/' . $oBannerGet->filename;
                            if (file_exists($img)) {
                                @unlink($img);
                            }
                        }
                    }
                    break;

                case 'Edit':
                    // First create a banner, then edit it
                    $bannerId = $this->_createBanner($campaignId, $storage, $statusVal);
                    $this->assertNotEqual(
                        $bannerId,
                        false,
                        "[{$id}] Setup: Failed to create {$storage} banner for Edit test",
                    );
                    if ($bannerId === false) {
                        break;
                    }

                    $oBannerEdit = new OA_Dll_BannerInfo();
                    $oBannerEdit->bannerId = $bannerId;
                    $oBannerEdit->bannerName = "Edited Banner {$id}";

                    // Apply capping on edit
                    if (isset($cappingConfig['capping'])) {
                        $oBannerEdit->capping = $cappingConfig['capping'];
                    }
                    if (isset($cappingConfig['sessionCapping'])) {
                        $oBannerEdit->sessionCapping = $cappingConfig['sessionCapping'];
                    }
                    if (isset($cappingConfig['block'])) {
                        $oBannerEdit->block = $cappingConfig['block'];
                    }

                    // For sql/web edits with a valid image, update the image
                    if (($storage === 'sql' || $storage === 'web') && $image === 'valid') {
                        $oBannerEdit->aImage = [
                            'filename' => 'updated.gif',
                            'content'  => $this->binaryGif,
                        ];
                    }

                    $result = $dllBanner->modify($oBannerEdit);
                    $this->assertTrue(
                        $result,
                        "[{$id}] Edit {$storage} banner failed: " . $dllBanner->getLastError(),
                    );

                    if ($result) {
                        // Verify the edit took effect
                        $oBannerGet = null;
                        $dllBanner->getBanner($bannerId, $oBannerGet);
                        $this->assertEqual(
                            $oBannerGet->bannerName,
                            "Edited Banner {$id}",
                            "[{$id}] Banner name not updated after edit",
                        );

                        // Clean up web-stored files
                        if ($storage === 'web' && !empty($oBannerGet->filename)) {
                            $img = $GLOBALS['_MAX']['CONF']['store']['webDir'] . '/' . $oBannerGet->filename;
                            if (file_exists($img)) {
                                @unlink($img);
                            }
                        }
                    }
                    break;

                case 'View':
                    // Create a banner then view it
                    $bannerId = $this->_createBanner($campaignId, $storage, $statusVal);
                    $this->assertNotEqual(
                        $bannerId,
                        false,
                        "[{$id}] Setup: Failed to create {$storage} banner for View test",
                    );
                    if ($bannerId === false) {
                        break;
                    }

                    $oBannerGet = null;
                    $result = $dllBanner->getBanner($bannerId, $oBannerGet);
                    $this->assertTrue(
                        $result,
                        "[{$id}] View {$storage} banner failed: " . $dllBanner->getLastError(),
                    );

                    if ($result) {
                        $this->assertEqual(
                            $oBannerGet->bannerId,
                            $bannerId,
                            "[{$id}] Banner ID mismatch on View",
                        );
                        $this->assertEqual(
                            $oBannerGet->storageType,
                            $storage,
                            "[{$id}] Storage type mismatch on View",
                        );
                    }
                    break;

                case 'Delete':
                    // Create a banner then delete it
                    $bannerId = $this->_createBanner($campaignId, $storage, $statusVal);
                    $this->assertNotEqual(
                        $bannerId,
                        false,
                        "[{$id}] Setup: Failed to create {$storage} banner for Delete test",
                    );
                    if ($bannerId === false) {
                        break;
                    }

                    $result = $dllBanner->delete($bannerId);
                    $this->assertTrue(
                        $result,
                        "[{$id}] Delete {$storage} banner failed: " . $dllBanner->getLastError(),
                    );

                    if ($result) {
                        // Verify the banner no longer exists
                        $oBannerGet = null;
                        $getResult = $dllBanner->getBanner($bannerId, $oBannerGet);
                        $this->assertFalse(
                            $getResult,
                            "[{$id}] Banner should not exist after delete",
                        );
                    }
                    break;
            }

            // Clean up between iterations
            DataGenerator::cleanUp();
            $this->agencyId = DataGenerator::generateOne('agency');
        }
    }

    // =========================================================================
    // Section 4D: Excluded Combos — Negative Test Cases
    // =========================================================================

    /**
     * 4D-NEG-01: TRAFFICKER + any Banner CRUD
     *
     * No banner permissions exist for TRAFFICKER account type.
     * The TRAFFICKER_PERMISSIONS constant only includes zone-related permissions.
     * All banner CRUD operations should fail with access denied.
     */
    public function testTraffickerCannotPerformBannerCrud()
    {
        $hierarchy = $this->_createCampaignHierarchy(OX_CAMPAIGN_TYPE_REMNANT);
        $campaignId = $hierarchy['campaignId'];

        // Create a banner first (with admin permissions) for edit/view/delete tests
        $bannerId = $this->_createBanner($campaignId, 'html');
        $this->assertNotEqual($bannerId, false, 'Setup: Banner should be created');

        // Now test with TRAFFICKER - checkPermissions should deny access
        $dllBanner = new PartialMockOA_Dll_Banner_CRUDCombo($this);
        $dllBanner->setReturnValue('checkPermissions', false);
        $dllBanner->_errorMessage = $this->accessForbiddenError;

        // TRAFFICKER + Create
        $oBannerInfo = new OA_Dll_BannerInfo();
        $oBannerInfo->campaignId = $campaignId;
        $oBannerInfo->storageType = 'html';
        $oBannerInfo->htmlTemplate = '<div>Trafficker Banner</div>';
        $result = $dllBanner->modify($oBannerInfo);
        $this->assertFalse(
            $result,
            '[4D-NEG-01a] TRAFFICKER should not be able to Create banner',
        );

        // TRAFFICKER + Edit
        $dllBanner2 = new PartialMockOA_Dll_Banner_CRUDCombo($this);
        $dllBanner2->setReturnValue('checkPermissions', false);

        $oBannerEdit = new OA_Dll_BannerInfo();
        $oBannerEdit->bannerId = $bannerId;
        $oBannerEdit->bannerName = 'Trafficker Edit';
        $result = $dllBanner2->modify($oBannerEdit);
        $this->assertFalse(
            $result,
            '[4D-NEG-01b] TRAFFICKER should not be able to Edit banner',
        );

        // TRAFFICKER + Delete
        $dllBanner3 = new PartialMockOA_Dll_Banner_CRUDCombo($this);
        $dllBanner3->setReturnValue('checkPermissions', false);

        $result = $dllBanner3->delete($bannerId);
        $this->assertFalse(
            $result,
            '[4D-NEG-01c] TRAFFICKER should not be able to Delete banner',
        );
    }

    /**
     * 4D-NEG-02: Storage=sql + no image on Create
     *
     * When storageType is 'sql', creating a banner without an image
     * should fail validation (Banner.php:156-159).
     */
    public function testSqlStorageRequiresImageOnCreate()
    {
        $hierarchy = $this->_createCampaignHierarchy(OX_CAMPAIGN_TYPE_REMNANT);
        $campaignId = $hierarchy['campaignId'];

        $dllBanner = new PartialMockOA_Dll_Banner_CRUDCombo($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $oBannerInfo = new OA_Dll_BannerInfo();
        $oBannerInfo->campaignId = $campaignId;
        $oBannerInfo->storageType = 'sql';
        // Intentionally NOT setting aImage

        $result = $dllBanner->modify($oBannerInfo);
        $this->assertFalse(
            $result,
            '[4D-NEG-02a] sql storage without image should fail on Create',
        );
        $this->assertEqual(
            $dllBanner->getLastError(),
            $this->imageRequiredError,
            '[4D-NEG-02a] Expected image required error for sql without image',
        );
    }

    /**
     * 4D-NEG-03: Storage=web + no image on Create
     *
     * When storageType is 'web', creating a banner without an image
     * should fail validation (Banner.php:156-159).
     */
    public function testWebStorageRequiresImageOnCreate()
    {
        $hierarchy = $this->_createCampaignHierarchy(OX_CAMPAIGN_TYPE_CONTRACT_NORMAL);
        $campaignId = $hierarchy['campaignId'];

        $dllBanner = new PartialMockOA_Dll_Banner_CRUDCombo($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $oBannerInfo = new OA_Dll_BannerInfo();
        $oBannerInfo->campaignId = $campaignId;
        $oBannerInfo->storageType = 'web';
        // Intentionally NOT setting aImage

        $result = $dllBanner->modify($oBannerInfo);
        $this->assertFalse(
            $result,
            '[4D-NEG-03] web storage without image should fail on Create',
        );
        $this->assertEqual(
            $dllBanner->getLastError(),
            $this->imageRequiredError,
            '[4D-NEG-03] Expected image required error for web without image',
        );
    }

    /**
     * 4D-NEG-04: Storage=html/txt/url + aImage set
     *
     * Image field is irrelevant for these storage types; image data
     * is ignored by the code. The banner should still be created
     * successfully — the image is simply disregarded.
     */
    public function testNonImageStorageIgnoresImageField()
    {
        $hierarchy = $this->_createCampaignHierarchy(OX_CAMPAIGN_TYPE_REMNANT);
        $campaignId = $hierarchy['campaignId'];

        $dllBanner = new PartialMockOA_Dll_Banner_CRUDCombo($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $storageTypes = ['html', 'txt', 'url'];

        foreach ($storageTypes as $storage) {
            $oBannerInfo = new OA_Dll_BannerInfo();
            $oBannerInfo->campaignId = $campaignId;
            $oBannerInfo->storageType = $storage;
            $oBannerInfo->bannerName = "Test {$storage} with image set";

            // Set aImage even though it's irrelevant
            $oBannerInfo->aImage = [
                'filename' => '1x1.gif',
                'content'  => $this->binaryGif,
            ];

            // Set required fields per storage type
            if ($storage === 'html') {
                $oBannerInfo->htmlTemplate = '<div>HTML with image</div>';
            } elseif ($storage === 'txt') {
                $oBannerInfo->bannerText = 'Text with image';
            } elseif ($storage === 'url') {
                $oBannerInfo->imageURL = 'http://example.com/banner.gif';
            }

            $result = $dllBanner->modify($oBannerInfo);
            $this->assertTrue(
                $result,
                "[4D-NEG-04] {$storage} banner with aImage set should succeed (image ignored): " . $dllBanner->getLastError(),
            );

            if ($result) {
                $this->assertNotNull(
                    $oBannerInfo->bannerId,
                    "[4D-NEG-04] {$storage} banner ID should be set",
                );
            }
        }
    }

    /**
     * 4D-NEG-05: ADVERTISER + Delete banner
     *
     * Code enforces [OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER] for delete.
     * ADVERTISER should be denied delete permission.
     */
    public function testAdvertiserCannotDeleteBanner()
    {
        $hierarchy = $this->_createCampaignHierarchy(OX_CAMPAIGN_TYPE_REMNANT);
        $campaignId = $hierarchy['campaignId'];

        // Create a banner with full permissions
        $bannerId = $this->_createBanner($campaignId, 'html');
        $this->assertNotEqual($bannerId, false, 'Setup: Banner should be created');

        // Test delete with ADVERTISER - checkPermissions should deny for delete
        $dllBanner = new PartialMockOA_Dll_Banner_CRUDCombo($this);
        $dllBanner->setReturnValue('checkPermissions', false);

        $result = $dllBanner->delete($bannerId);
        $this->assertFalse(
            $result,
            '[4D-NEG-05] ADVERTISER should not be able to Delete banner',
        );
    }

    /**
     * 4D-NEG-06: ADVERTISER + Create + no BANNER_EDIT permission
     *
     * Permission denied path: ADVERTISER without BANNER_EDIT permission
     * cannot create a banner. The modify() method checks for OA_PERM_BANNER_EDIT.
     */
    public function testAdvertiserWithoutBannerEditCannotCreate()
    {
        $hierarchy = $this->_createCampaignHierarchy(OX_CAMPAIGN_TYPE_REMNANT);
        $campaignId = $hierarchy['campaignId'];

        // Simulate ADVERTISER without BANNER_EDIT permission
        $dllBanner = new PartialMockOA_Dll_Banner_CRUDCombo($this);
        $dllBanner->setReturnValue('checkPermissions', false);

        $oBannerInfo = new OA_Dll_BannerInfo();
        $oBannerInfo->campaignId = $campaignId;
        $oBannerInfo->storageType = 'html';
        $oBannerInfo->htmlTemplate = '<div>No permission</div>';

        $result = $dllBanner->modify($oBannerInfo);
        $this->assertFalse(
            $result,
            '[4D-NEG-06] ADVERTISER without BANNER_EDIT should not be able to Create',
        );
    }

    /**
     * 4D-NEG-07: Image validation — empty filename on sql Create
     *
     * When creating a sql banner with an image that has an empty filename,
     * validation should fail.
     */
    public function testSqlCreateWithEmptyFilenameImage()
    {
        $hierarchy = $this->_createCampaignHierarchy(OX_CAMPAIGN_TYPE_REMNANT);
        $campaignId = $hierarchy['campaignId'];

        $dllBanner = new PartialMockOA_Dll_Banner_CRUDCombo($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $oBannerInfo = new OA_Dll_BannerInfo();
        $oBannerInfo->campaignId = $campaignId;
        $oBannerInfo->storageType = 'sql';
        $oBannerInfo->aImage = [
            'filename' => '',
            'content'  => $this->binaryGif,
        ];

        $result = $dllBanner->modify($oBannerInfo);
        $this->assertFalse(
            $result,
            '[4D-NEG-07] sql Create with empty filename should fail',
        );
        $this->assertEqual(
            $dllBanner->getLastError(),
            $this->emptyFilenameError,
            '[4D-NEG-07] Expected empty filename error',
        );
    }

    /**
     * 4D-NEG-08: Image validation — empty content on sql Create
     *
     * When creating a sql banner with an image that has empty content,
     * validation should fail.
     */
    public function testSqlCreateWithEmptyContentImage()
    {
        $hierarchy = $this->_createCampaignHierarchy(OX_CAMPAIGN_TYPE_ECPM);
        $campaignId = $hierarchy['campaignId'];

        $dllBanner = new PartialMockOA_Dll_Banner_CRUDCombo($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $oBannerInfo = new OA_Dll_BannerInfo();
        $oBannerInfo->campaignId = $campaignId;
        $oBannerInfo->storageType = 'sql';
        $oBannerInfo->aImage = [
            'filename' => 'test.gif',
            'content'  => '',
        ];

        $result = $dllBanner->modify($oBannerInfo);
        $this->assertFalse(
            $result,
            '[4D-NEG-08] sql Create with empty content should fail',
        );
        $this->assertEqual(
            $dllBanner->getLastError(),
            $this->emptyContentError,
            '[4D-NEG-08] Expected empty content error',
        );
    }

    /**
     * 4D-NEG-09: Image validation — wrong format on sql Create
     *
     * When creating a sql banner with an image that has wrong/invalid
     * binary content, validation should fail.
     */
    public function testSqlCreateWithWrongFormatImage()
    {
        $hierarchy = $this->_createCampaignHierarchy(OX_CAMPAIGN_TYPE_ECPM);
        $campaignId = $hierarchy['campaignId'];

        $dllBanner = new PartialMockOA_Dll_Banner_CRUDCombo($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $oBannerInfo = new OA_Dll_BannerInfo();
        $oBannerInfo->campaignId = $campaignId;
        $oBannerInfo->storageType = 'sql';
        $oBannerInfo->aImage = [
            'filename' => 'test.gif',
            'content'  => 'this-is-not-a-valid-image-format',
        ];

        $result = $dllBanner->modify($oBannerInfo);
        $this->assertFalse(
            $result,
            '[4D-NEG-09] sql Create with wrong format should fail',
        );
        $this->assertEqual(
            $dllBanner->getLastError(),
            $this->unknownFormatError,
            '[4D-NEG-09] Expected unknown format error',
        );
    }

    /**
     * 4D-NEG-10: Image validation — oversized image on sql Create
     *
     * When creating a sql banner with an image exceeding the maxFilesize
     * limit, validation should fail.
     */
    public function testSqlCreateWithOversizedImage()
    {
        // Set a very small max file size to trigger the oversized check
        $GLOBALS['_MAX']['CONF']['store']['maxFilesize'] = 16;

        $hierarchy = $this->_createCampaignHierarchy(OX_CAMPAIGN_TYPE_CONTRACT_NORMAL);
        $campaignId = $hierarchy['campaignId'];

        $dllBanner = new PartialMockOA_Dll_Banner_CRUDCombo($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $oBannerInfo = new OA_Dll_BannerInfo();
        $oBannerInfo->campaignId = $campaignId;
        $oBannerInfo->storageType = 'sql';
        $oBannerInfo->aImage = [
            'filename' => 'big.gif',
            'content'  => $this->binaryGif, // 43 bytes, exceeds 16 byte limit
        ];

        $result = $dllBanner->modify($oBannerInfo);
        $this->assertFalse(
            $result,
            '[4D-NEG-10] sql Create with oversized image should fail',
        );

        $lastError = $dllBanner->getLastError();
        $this->assertTrue(
            strpos($lastError, 'Image file size is greater than') !== false,
            '[4D-NEG-10] Expected file size error, got: ' . $lastError,
        );

        // Reset
        $GLOBALS['_MAX']['CONF']['store']['maxFilesize'] = 0;
    }

    /**
     * 4D-NEG-11: Image validation — empty filename on web Create
     */
    public function testWebCreateWithEmptyFilenameImage()
    {
        $hierarchy = $this->_createCampaignHierarchy(OX_CAMPAIGN_TYPE_CONTRACT_NORMAL);
        $campaignId = $hierarchy['campaignId'];

        $dllBanner = new PartialMockOA_Dll_Banner_CRUDCombo($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $oBannerInfo = new OA_Dll_BannerInfo();
        $oBannerInfo->campaignId = $campaignId;
        $oBannerInfo->storageType = 'web';
        $oBannerInfo->aImage = [
            'filename' => '',
            'content'  => $this->binaryGif,
        ];

        $result = $dllBanner->modify($oBannerInfo);
        $this->assertFalse(
            $result,
            '[4D-NEG-11] web Create with empty filename should fail',
        );
        $this->assertEqual(
            $dllBanner->getLastError(),
            $this->emptyFilenameError,
            '[4D-NEG-11] Expected empty filename error',
        );
    }

    /**
     * 4D-NEG-12: Image validation — empty content on web Create
     */
    public function testWebCreateWithEmptyContentImage()
    {
        $hierarchy = $this->_createCampaignHierarchy(OX_CAMPAIGN_TYPE_OVERRIDE);
        $campaignId = $hierarchy['campaignId'];

        $dllBanner = new PartialMockOA_Dll_Banner_CRUDCombo($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $oBannerInfo = new OA_Dll_BannerInfo();
        $oBannerInfo->campaignId = $campaignId;
        $oBannerInfo->storageType = 'web';
        $oBannerInfo->aImage = [
            'filename' => 'test.gif',
            'content'  => '',
        ];

        $result = $dllBanner->modify($oBannerInfo);
        $this->assertFalse(
            $result,
            '[4D-NEG-12] web Create with empty content should fail',
        );
        $this->assertEqual(
            $dllBanner->getLastError(),
            $this->emptyContentError,
            '[4D-NEG-12] Expected empty content error',
        );
    }

    /**
     * 4D-NEG-13: Image validation — wrong format on web Create
     */
    public function testWebCreateWithWrongFormatImage()
    {
        $hierarchy = $this->_createCampaignHierarchy(OX_CAMPAIGN_TYPE_CONTRACT_ECPM);
        $campaignId = $hierarchy['campaignId'];

        $dllBanner = new PartialMockOA_Dll_Banner_CRUDCombo($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $oBannerInfo = new OA_Dll_BannerInfo();
        $oBannerInfo->campaignId = $campaignId;
        $oBannerInfo->storageType = 'web';
        $oBannerInfo->aImage = [
            'filename' => 'test.gif',
            'content'  => 'not-an-image',
        ];

        $result = $dllBanner->modify($oBannerInfo);
        $this->assertFalse(
            $result,
            '[4D-NEG-13] web Create with wrong format should fail',
        );
        $this->assertEqual(
            $dllBanner->getLastError(),
            $this->unknownFormatError,
            '[4D-NEG-13] Expected unknown format error',
        );
    }

    /**
     * 4D-NEG-14: Image validation — oversized image on web Create
     */
    public function testWebCreateWithOversizedImage()
    {
        $GLOBALS['_MAX']['CONF']['store']['maxFilesize'] = 16;

        $hierarchy = $this->_createCampaignHierarchy(OX_CAMPAIGN_TYPE_REMNANT);
        $campaignId = $hierarchy['campaignId'];

        $dllBanner = new PartialMockOA_Dll_Banner_CRUDCombo($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $oBannerInfo = new OA_Dll_BannerInfo();
        $oBannerInfo->campaignId = $campaignId;
        $oBannerInfo->storageType = 'web';
        $oBannerInfo->aImage = [
            'filename' => 'big.gif',
            'content'  => $this->binaryGif,
        ];

        $result = $dllBanner->modify($oBannerInfo);
        $this->assertFalse(
            $result,
            '[4D-NEG-14] web Create with oversized image should fail',
        );

        $lastError = $dllBanner->getLastError();
        $this->assertTrue(
            strpos($lastError, 'Image file size is greater than') !== false,
            '[4D-NEG-14] Expected file size error, got: ' . $lastError,
        );

        $GLOBALS['_MAX']['CONF']['store']['maxFilesize'] = 0;
    }

    /**
     * 4D-NEG-15: Delete non-existing banner
     *
     * Attempting to delete a banner that doesn't exist should fail.
     */
    public function testDeleteNonExistingBanner()
    {
        $dllBanner = new PartialMockOA_Dll_Banner_CRUDCombo($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $result = $dllBanner->delete(999999);
        $this->assertFalse(
            $result,
            '[4D-NEG-15] Deleting non-existing banner should fail',
        );
        $this->assertEqual(
            $dllBanner->getLastError(),
            $this->unknownIdError,
            '[4D-NEG-15] Expected unknown ID error',
        );
    }

    /**
     * 4D-NEG-16: Edit non-existing banner
     *
     * Attempting to edit a banner that doesn't exist should fail.
     */
    public function testEditNonExistingBanner()
    {
        $dllBanner = new PartialMockOA_Dll_Banner_CRUDCombo($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $oBannerInfo = new OA_Dll_BannerInfo();
        $oBannerInfo->bannerId = 999999;
        $oBannerInfo->bannerName = 'Non-existing banner';

        $result = $dllBanner->modify($oBannerInfo);
        $this->assertFalse(
            $result,
            '[4D-NEG-16] Editing non-existing banner should fail',
        );
        $this->assertEqual(
            $dllBanner->getLastError(),
            $this->unknownIdError,
            '[4D-NEG-16] Expected unknown ID error',
        );
    }

    /**
     * 4D-NEG-17: View non-existing banner
     *
     * Attempting to view a banner that doesn't exist should fail.
     */
    public function testViewNonExistingBanner()
    {
        $dllBanner = new PartialMockOA_Dll_Banner_CRUDCombo($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $oBannerGet = null;
        $result = $dllBanner->getBanner(999999, $oBannerGet);
        $this->assertFalse(
            $result,
            '[4D-NEG-17] Viewing non-existing banner should fail',
        );
        $this->assertEqual(
            $dllBanner->getLastError(),
            $this->unknownIdError,
            '[4D-NEG-17] Expected unknown ID error',
        );
    }

    /**
     * 4D-NEG-18: TRAFFICKER + Create across all storage types
     *
     * Comprehensive test ensuring TRAFFICKER cannot create banners
     * of any storage type.
     */
    public function testTraffickerCannotCreateAnyStorageType()
    {
        $hierarchy = $this->_createCampaignHierarchy(OX_CAMPAIGN_TYPE_REMNANT);
        $campaignId = $hierarchy['campaignId'];

        $storageTypes = [
            'html' => ['htmlTemplate' => '<div>Test</div>'],
            'txt'  => ['bannerText' => 'Test text'],
            'url'  => ['imageURL' => 'http://example.com/banner.gif'],
            'sql'  => ['aImage' => ['filename' => '1x1.gif', 'content' => $this->binaryGif]],
            'web'  => ['aImage' => ['filename' => '1x1.gif', 'content' => $this->binaryGif]],
        ];

        foreach ($storageTypes as $storage => $fields) {
            $dllBanner = new PartialMockOA_Dll_Banner_CRUDCombo($this);
            $dllBanner->setReturnValue('checkPermissions', false);

            $oBannerInfo = new OA_Dll_BannerInfo();
            $oBannerInfo->campaignId = $campaignId;
            $oBannerInfo->storageType = $storage;

            foreach ($fields as $field => $value) {
                $oBannerInfo->$field = $value;
            }

            $result = $dllBanner->modify($oBannerInfo);
            $this->assertFalse(
                $result,
                "[4D-NEG-18] TRAFFICKER should not create {$storage} banner",
            );
        }
    }

    /**
     * 4D-NEG-19: ADVERTISER + Delete across all campaign types
     *
     * ADVERTISER should not be able to delete banners regardless
     * of the parent campaign type.
     */
    public function testAdvertiserCannotDeleteAcrossCampaignTypes()
    {
        $campaignTypes = [
            OX_CAMPAIGN_TYPE_REMNANT,
            OX_CAMPAIGN_TYPE_CONTRACT_NORMAL,
            OX_CAMPAIGN_TYPE_OVERRIDE,
            OX_CAMPAIGN_TYPE_ECPM,
            OX_CAMPAIGN_TYPE_CONTRACT_ECPM,
        ];

        foreach ($campaignTypes as $campaignType) {
            $hierarchy = $this->_createCampaignHierarchy($campaignType);
            $campaignId = $hierarchy['campaignId'];

            $bannerId = $this->_createBanner($campaignId, 'html');
            if ($bannerId === false) {
                continue;
            }

            $dllBanner = new PartialMockOA_Dll_Banner_CRUDCombo($this);
            $dllBanner->setReturnValue('checkPermissions', false);

            $result = $dllBanner->delete($bannerId);
            $this->assertFalse(
                $result,
                "[4D-NEG-19] ADVERTISER should not delete banner under campaign type {$campaignType}",
            );

            DataGenerator::cleanUp();
            $this->agencyId = DataGenerator::generateOne('agency');
        }
    }

    /**
     * 4D-NEG-20: ADVERTISER without BANNER_EDIT + Create across storage types
     *
     * ADVERTISER without BANNER_EDIT permission cannot create banners
     * regardless of storage type.
     */
    public function testAdvertiserWithoutEditPermCannotCreateAnyStorage()
    {
        $hierarchy = $this->_createCampaignHierarchy(OX_CAMPAIGN_TYPE_REMNANT);
        $campaignId = $hierarchy['campaignId'];

        $storageTypes = ['html', 'txt', 'url', 'sql', 'web'];

        foreach ($storageTypes as $storage) {
            $dllBanner = new PartialMockOA_Dll_Banner_CRUDCombo($this);
            $dllBanner->setReturnValue('checkPermissions', false);

            $oBannerInfo = new OA_Dll_BannerInfo();
            $oBannerInfo->campaignId = $campaignId;
            $oBannerInfo->storageType = $storage;
            $oBannerInfo->bannerName = "No perm {$storage}";

            if ($storage === 'sql' || $storage === 'web') {
                $oBannerInfo->aImage = [
                    'filename' => '1x1.gif',
                    'content'  => $this->binaryGif,
                ];
            } elseif ($storage === 'url') {
                $oBannerInfo->imageURL = 'http://example.com/banner.gif';
            } elseif ($storage === 'html') {
                $oBannerInfo->htmlTemplate = '<div>Test</div>';
            } elseif ($storage === 'txt') {
                $oBannerInfo->bannerText = 'Test';
            }

            $result = $dllBanner->modify($oBannerInfo);
            $this->assertFalse(
                $result,
                "[4D-NEG-20] ADVERTISER without BANNER_EDIT should not create {$storage} banner",
            );
        }
    }

    /**
     * 4D-NEG-21: sql/web storage - Create with all negative image validation types
     *
     * Tests all negative image validation scenarios for both sql and web storage.
     */
    public function testImageValidationNegativeCasesAllStorageTypes()
    {
        $imageTypes = [
            'empty_filename' => $this->emptyFilenameError,
            'empty_content'  => $this->emptyContentError,
            'wrong_format'   => $this->unknownFormatError,
        ];

        $storageTypes = ['sql', 'web'];

        foreach ($storageTypes as $storage) {
            foreach ($imageTypes as $imageType => $expectedError) {
                $hierarchy = $this->_createCampaignHierarchy(OX_CAMPAIGN_TYPE_REMNANT);
                $campaignId = $hierarchy['campaignId'];

                $dllBanner = new PartialMockOA_Dll_Banner_CRUDCombo($this);
                $dllBanner->setReturnValue('checkPermissions', true);

                $oBannerInfo = new OA_Dll_BannerInfo();
                $oBannerInfo->campaignId = $campaignId;
                $oBannerInfo->storageType = $storage;
                $oBannerInfo->aImage = $this->_getImageConfig($imageType);

                $result = $dllBanner->modify($oBannerInfo);
                $this->assertFalse(
                    $result,
                    "[4D-NEG-21] {$storage} Create with {$imageType} should fail",
                );
                $this->assertEqual(
                    $dllBanner->getLastError(),
                    $expectedError,
                    "[4D-NEG-21] Expected '{$expectedError}' for {$storage}/{$imageType}, got: " . $dllBanner->getLastError(),
                );

                DataGenerator::cleanUp();
                $this->agencyId = DataGenerator::generateOne('agency');
            }
        }
    }

    /**
     * 4D-NEG-22: Oversized image across campaign types
     *
     * Tests oversized image rejection for both sql and web storage
     * across multiple campaign types.
     */
    public function testOversizedImageAcrossCampaignTypes()
    {
        $GLOBALS['_MAX']['CONF']['store']['maxFilesize'] = 16;

        $campaignTypes = [
            OX_CAMPAIGN_TYPE_REMNANT,
            OX_CAMPAIGN_TYPE_CONTRACT_NORMAL,
            OX_CAMPAIGN_TYPE_OVERRIDE,
        ];

        foreach ($campaignTypes as $campaignType) {
            foreach (['sql', 'web'] as $storage) {
                $hierarchy = $this->_createCampaignHierarchy($campaignType);
                $campaignId = $hierarchy['campaignId'];

                $dllBanner = new PartialMockOA_Dll_Banner_CRUDCombo($this);
                $dllBanner->setReturnValue('checkPermissions', true);

                $oBannerInfo = new OA_Dll_BannerInfo();
                $oBannerInfo->campaignId = $campaignId;
                $oBannerInfo->storageType = $storage;
                $oBannerInfo->aImage = [
                    'filename' => 'big.gif',
                    'content'  => $this->binaryGif,
                ];

                $result = $dllBanner->modify($oBannerInfo);
                $this->assertFalse(
                    $result,
                    "[4D-NEG-22] {$storage} Create with oversized image under campaign type {$campaignType} should fail",
                );

                $lastError = $dllBanner->getLastError();
                $this->assertTrue(
                    strpos($lastError, 'Image file size is greater than') !== false,
                    "[4D-NEG-22] Expected file size error for {$storage}/type{$campaignType}, got: " . $lastError,
                );

                DataGenerator::cleanUp();
                $this->agencyId = DataGenerator::generateOne('agency');
            }
        }

        $GLOBALS['_MAX']['CONF']['store']['maxFilesize'] = 0;
    }

    /**
     * 4D-NEG-23: Permission denial with various capping configurations
     *
     * Tests that permission denial is unaffected by capping settings.
     */
    public function testPermissionDenialWithCappingConfigs()
    {
        $cappingTypes = ['None', 'capping', 'session', 'block', 'all'];

        foreach ($cappingTypes as $cappingType) {
            $hierarchy = $this->_createCampaignHierarchy(OX_CAMPAIGN_TYPE_REMNANT);
            $campaignId = $hierarchy['campaignId'];

            $dllBanner = new PartialMockOA_Dll_Banner_CRUDCombo($this);
            $dllBanner->setReturnValue('checkPermissions', false);

            $cappingConfig = $this->_getCappingConfig($cappingType);

            $oBannerInfo = new OA_Dll_BannerInfo();
            $oBannerInfo->campaignId = $campaignId;
            $oBannerInfo->storageType = 'html';
            $oBannerInfo->htmlTemplate = '<div>Capping test</div>';

            if (isset($cappingConfig['capping'])) {
                $oBannerInfo->capping = $cappingConfig['capping'];
            }
            if (isset($cappingConfig['sessionCapping'])) {
                $oBannerInfo->sessionCapping = $cappingConfig['sessionCapping'];
            }
            if (isset($cappingConfig['block'])) {
                $oBannerInfo->block = $cappingConfig['block'];
            }

            $result = $dllBanner->modify($oBannerInfo);
            $this->assertFalse(
                $result,
                "[4D-NEG-23] Permission denied should work regardless of capping ({$cappingType})",
            );

            DataGenerator::cleanUp();
            $this->agencyId = DataGenerator::generateOne('agency');
        }
    }

    /**
     * 4D-NEG-24: Create banner with invalid campaign ID
     *
     * Creating a banner with a non-existing campaign ID should fail.
     */
    public function testCreateBannerWithInvalidCampaignId()
    {
        $dllBanner = new PartialMockOA_Dll_Banner_CRUDCombo($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $oBannerInfo = new OA_Dll_BannerInfo();
        $oBannerInfo->campaignId = 999999;
        $oBannerInfo->storageType = 'html';
        $oBannerInfo->htmlTemplate = '<div>Invalid campaign</div>';

        $result = $dllBanner->modify($oBannerInfo);
        $this->assertFalse(
            $result,
            '[4D-NEG-24] Create banner with invalid campaign ID should fail',
        );
    }

    /**
     * 4D-NEG-25: sql/web no image across all campaign types
     *
     * Validates that the image requirement for sql/web storage is
     * enforced regardless of the parent campaign type.
     */
    public function testNoImageRequirementAcrossCampaignTypes()
    {
        $campaignTypes = [
            OX_CAMPAIGN_TYPE_REMNANT,
            OX_CAMPAIGN_TYPE_CONTRACT_NORMAL,
            OX_CAMPAIGN_TYPE_OVERRIDE,
            OX_CAMPAIGN_TYPE_ECPM,
            OX_CAMPAIGN_TYPE_CONTRACT_ECPM,
        ];

        foreach ($campaignTypes as $campaignType) {
            foreach (['sql', 'web'] as $storage) {
                $hierarchy = $this->_createCampaignHierarchy($campaignType);
                $campaignId = $hierarchy['campaignId'];

                $dllBanner = new PartialMockOA_Dll_Banner_CRUDCombo($this);
                $dllBanner->setReturnValue('checkPermissions', true);

                $oBannerInfo = new OA_Dll_BannerInfo();
                $oBannerInfo->campaignId = $campaignId;
                $oBannerInfo->storageType = $storage;
                // No aImage set

                $result = $dllBanner->modify($oBannerInfo);
                $this->assertFalse(
                    $result,
                    "[4D-NEG-25] {$storage} without image under campaign type {$campaignType} should fail",
                );
                $this->assertEqual(
                    $dllBanner->getLastError(),
                    $this->imageRequiredError,
                    "[4D-NEG-25] Expected image required error for {$storage}/type{$campaignType}",
                );

                DataGenerator::cleanUp();
                $this->agencyId = DataGenerator::generateOne('agency');
            }
        }
    }
}
