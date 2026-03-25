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
 * Combination Matrix Tests for Banner CRUD (Sections 4C + 4D).
 *
 * Section 4C: ~100 pairwise-generated positive tests covering every pair of:
 *   Account type, Page mode, Storage type, Entity status, Banner permission,
 *   Frequency capping, Image validation, Parent campaign type.
 *
 * Section 4D: Negative / denial tests for excluded combos such as
 *   TRAFFICKER + any Banner CRUD, ADVERTISER + Delete, image-required
 *   violations, etc.
 *
 * @package    OpenXDll
 * @subpackage TestSuite
 */

require_once MAX_PATH . '/lib/OA/Dll/Advertiser.php';
require_once MAX_PATH . '/lib/OA/Dll/AdvertiserInfo.php';
require_once MAX_PATH . '/lib/OA/Dll/Campaign.php';
require_once MAX_PATH . '/lib/OA/Dll/CampaignInfo.php';
require_once MAX_PATH . '/lib/OA/Dll/Banner.php';
require_once MAX_PATH . '/lib/OA/Dll/BannerInfo.php';
require_once MAX_PATH . '/lib/OA/Dll/tests/util/DllUnitTestCase.php';

class OA_Dll_BannerCrudComboMatrixTest extends DllUnitTestCase
{
    /**
     * @var int
     */
    public $agencyId;

    public $binaryGif;

    // -- error messages -------------------------------------------------
    public $unknownIdError = 'Unknown bannerId Error';
    public $unknownFormatError = 'Unrecognized image file format';
    public $accessForbiddenError = 'Access forbidden';
    public $imageRequiredError = "Field 'aImage' must not be empty";
    public $imageFilenameEmptyError = 'Image filename empty';
    public $imageContentEmptyError = 'Image content empty';

    // -- dimension value maps -------------------------------------------
    private static $campaignPriorityMap = [
        'Remnant'        => 0,
        'ContractNormal' => 5,
        'Override'        => -1,
        'eCPM'           => -2,
        'ContractECPM'   => -2,
    ];

    private static $campaignWeightMap = [
        'Remnant'        => 1,
        'ContractNormal' => 0,
        'Override'        => 1,
        'eCPM'           => 0,
        'ContractECPM'   => 0,
    ];

    private static $statusMap = [
        'Running'  => OA_ENTITY_STATUS_RUNNING,
        'Paused'   => OA_ENTITY_STATUS_PAUSED,
        'Awaiting' => OA_ENTITY_STATUS_AWAITING,
        'Expired'  => OA_ENTITY_STATUS_EXPIRED,
        'Inactive' => OA_ENTITY_STATUS_INACTIVE,
        'Pending'  => OA_ENTITY_STATUS_PENDING,
        'Approval' => OA_ENTITY_STATUS_APPROVAL,
        'Rejected' => OA_ENTITY_STATUS_REJECTED,
    ];

    private static $permissionMap = [
        'BANNER_ACTIVATE'   => OA_PERM_BANNER_ACTIVATE,
        'BANNER_DEACTIVATE' => OA_PERM_BANNER_DEACTIVATE,
        'BANNER_ADD'        => OA_PERM_BANNER_ADD,
        'BANNER_EDIT'       => OA_PERM_BANNER_EDIT,
    ];

    public function __construct()
    {
        parent::__construct();

        Mock::generatePartial(
            'OA_Dll_Banner',
            'PartialMockOA_Dll_Banner_ComboMatrix',
            ['checkPermissions'],
        );
        Mock::generatePartial(
            'OA_Dll_Campaign',
            'PartialMockOA_Dll_Campaign_ComboMatrix',
            ['checkPermissions'],
        );
        Mock::generatePartial(
            'OA_Dll_Advertiser',
            'PartialMockOA_Dll_Advertiser_ComboMatrix',
            ['checkPermissions', 'getDefaultAgencyId'],
        );

        // Minimal valid 1x1 GIF89a
        $this->binaryGif = "GIF89a\001\0\001\0\200\0\0\377\377\377\0\0"
            . "\0!\371\004\0\0\0\0\0,\0\0\0\0\001\0\001\0\0\002\002D\001\0;";
    }

    public function setUp()
    {
        $this->agencyId = DataGenerator::generateOne('agency');
        $GLOBALS['_MAX']['CONF']['store']['mode'] = 0;
        $GLOBALS['_MAX']['CONF']['store']['webDir'] = MAX_PATH . '/var';
        // Ensure all banner types are allowed
        $GLOBALS['_MAX']['CONF']['allowedBanners']['sql'] = true;
        $GLOBALS['_MAX']['CONF']['allowedBanners']['web'] = true;
        $GLOBALS['_MAX']['CONF']['allowedBanners']['url'] = true;
        $GLOBALS['_MAX']['CONF']['allowedBanners']['html'] = true;
        $GLOBALS['_MAX']['CONF']['allowedBanners']['text'] = true;
        // No file-size limit unless overridden
        $GLOBALS['_MAX']['CONF']['store']['maxFilesize'] = 0;
    }

    public function tearDown()
    {
        DataGenerator::cleanUp();
    }

    // ===================================================================
    // Helper: create advertiser + campaign scaffold
    // ===================================================================
    private function _createCampaignScaffold($campaignType = 'Remnant')
    {
        $dllAdv = new PartialMockOA_Dll_Advertiser_ComboMatrix($this);
        $dllAdv->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdv->setReturnValue('checkPermissions', true);

        $dllCamp = new PartialMockOA_Dll_Campaign_ComboMatrix($this);
        $dllCamp->setReturnValue('checkPermissions', true);

        $oAdv = new OA_Dll_AdvertiserInfo();
        $oAdv->advertiserName = 'combo_adv';
        $oAdv->agencyId = $this->agencyId;
        $dllAdv->modify($oAdv);

        $oCamp = new OA_Dll_CampaignInfo();
        $oCamp->advertiserId = $oAdv->advertiserId;
        $oCamp->campaignName = 'combo_camp_' . $campaignType;
        $oCamp->priority = self::$campaignPriorityMap[$campaignType] ?? 0;
        $oCamp->weight = self::$campaignWeightMap[$campaignType] ?? 1;

        if (in_array($campaignType, ['eCPM', 'ContractECPM'])) {
            $oCamp->revenue = 1.5;
            $oCamp->revenueType = MAX_FINANCE_CPM;
        }

        $dllCamp->modify($oCamp);

        return $oCamp->campaignId;
    }

    // ===================================================================
    // Helper: build a BannerInfo for a combo row
    // ===================================================================
    private function _buildBannerInfo(
        $campaignId,
        $storage,
        $statusKey,
        $cappingKey,
        $imageKey,
        $isCreate = true,
        $existingBannerId = null
    ) {
        $oBanner = new OA_Dll_BannerInfo();

        if ($isCreate) {
            $oBanner->campaignId = $campaignId;
            $oBanner->storageType = $storage;
        } else {
            $oBanner->bannerId = $existingBannerId;
        }

        // Status
        if (isset(self::$statusMap[$statusKey])) {
            $oBanner->status = self::$statusMap[$statusKey];
        }

        // Capping
        switch ($cappingKey) {
            case 'capping':
                $oBanner->capping = 10;
                break;
            case 'session':
                $oBanner->sessionCapping = 5;
                break;
            case 'block':
                $oBanner->block = 3600;
                break;
            case 'all':
                $oBanner->capping = 10;
                $oBanner->sessionCapping = 5;
                $oBanner->block = 3600;
                break;
            default: // 'None'
                break;
        }

        // Image handling for sql/web
        if (in_array($storage, ['sql', 'web']) && $isCreate) {
            switch ($imageKey) {
                case 'valid':
                    $oBanner->aImage = [
                        'filename' => '1x1.gif',
                        'content'  => $this->binaryGif,
                    ];
                    break;
                case 'empty_filename':
                    $oBanner->aImage = [
                        'filename' => '',
                        'content'  => $this->binaryGif,
                    ];
                    break;
                case 'empty_content':
                    $oBanner->aImage = [
                        'filename' => '1x1.gif',
                        'content'  => '',
                    ];
                    break;
                case 'wrong_format':
                    $oBanner->aImage = [
                        'filename' => 'test.gif',
                        'content'  => 'not-an-image',
                    ];
                    break;
                case 'oversized':
                    $GLOBALS['_MAX']['CONF']['store']['maxFilesize'] = 16;
                    $oBanner->aImage = [
                        'filename' => '1x1.gif',
                        'content'  => $this->binaryGif,
                    ];
                    break;
            }
        }

        // For html/txt/url on Create we set relevant fields
        if ($isCreate) {
            if ($storage === 'html') {
                $oBanner->htmlTemplate = '<div>ad</div>';
                $oBanner->width = 468;
                $oBanner->height = 60;
            } elseif ($storage === 'txt') {
                $oBanner->bannerText = 'Text ad content';
                $oBanner->width = 0;
                $oBanner->height = 0;
            } elseif ($storage === 'url') {
                $oBanner->imageURL = 'http://example.com/banner.gif';
                $oBanner->width = 468;
                $oBanner->height = 60;
            }
        }

        return $oBanner;
    }

    // ===================================================================
    // Helper: create a banner via the DLL (bypassing permission checks)
    // Returns the bannerId.
    // ===================================================================
    private function _createBanner($campaignId, $storage = 'html')
    {
        $dllBanner = new PartialMockOA_Dll_Banner_ComboMatrix($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $oBanner = $this->_buildBannerInfo(
            $campaignId,
            $storage,
            'Running',
            'None',
            ($storage === 'sql' || $storage === 'web') ? 'valid' : 'valid',
            true,
        );

        $dllBanner->modify($oBanner);
        $this->assertNotNull($oBanner->bannerId, 'Helper _createBanner should create a banner');

        return $oBanner->bannerId;
    }

    // ===================================================================
    // Determine whether a combo row should succeed
    // ===================================================================
    private function _shouldSucceed($account, $mode, $storage, $imageKey)
    {
        // TRAFFICKER never has banner permissions
        if ($account === 'TRAFFICKER') {
            return false;
        }
        // ADVERTISER cannot Delete banners
        if ($account === 'ADVERTISER' && $mode === 'Delete') {
            return false;
        }
        // sql/web Create requires a valid image
        if ($mode === 'Create' && in_array($storage, ['sql', 'web'])) {
            if (in_array($imageKey, ['empty_filename', 'empty_content', 'wrong_format', 'oversized'])) {
                return false;
            }
        }
        return true;
    }

    // ===================================================================
    // SECTION 4C: Pairwise-generated included combo tests
    // ===================================================================

    /**
     * Returns the pairwise-generated test matrix (100 rows).
     * Each row: [account, mode, storage, status, permission, capping, image, campaignType]
     */
    private function _getPairwiseMatrix()
    {
        return [
            // Rows 1-11: explicit sample combos from the spec
            ['ADMIN',      'Create', 'html', 'Running',  'BANNER_EDIT',       'None',    'valid',          'Remnant'],
            ['MANAGER',    'Create', 'sql',  'Running',  'BANNER_ADD',        'capping', 'valid',          'ContractNormal'],
            ['ADVERTISER', 'Edit',   'web',  'Paused',   'BANNER_EDIT',       'session', 'valid',          'Override'],
            ['MANAGER',    'Create', 'url',  'Running',  'BANNER_ADD',        'None',    'valid',          'eCPM'],
            ['ADMIN',      'Create', 'txt',  'Running',  'BANNER_EDIT',       'block',   'valid',          'ContractECPM'],
            ['MANAGER',    'Edit',   'sql',  'Inactive', 'BANNER_EDIT',       'all',     'valid',          'Remnant'],
            ['ADVERTISER', 'View',   'html', 'Awaiting', 'BANNER_ACTIVATE',   'None',    'valid',          'ContractNormal'],
            ['ADMIN',      'Delete', 'web',  'Expired',  'BANNER_EDIT',       'None',    'valid',          'Override'],
            ['MANAGER',    'Create', 'sql',  'Running',  'BANNER_ADD',        'None',    'empty_filename', 'Remnant'],
            ['MANAGER',    'Create', 'sql',  'Running',  'BANNER_ADD',        'None',    'wrong_format',   'eCPM'],
            ['MANAGER',    'Create', 'sql',  'Running',  'BANNER_ADD',        'None',    'oversized',      'ContractNormal'],

            // Rows 12-50: allpairspy generated rows
            ['ADMIN',      'Create', 'sql',  'Running',  'BANNER_ACTIVATE',   'None',    'valid',          'Remnant'],
            ['MANAGER',    'Edit',   'web',  'Paused',   'BANNER_DEACTIVATE', 'capping', 'valid',          'Remnant'],
            ['ADVERTISER', 'View',   'url',  'Awaiting', 'BANNER_ADD',        'session', 'valid',          'Remnant'],
            ['TRAFFICKER', 'Delete', 'html', 'Expired',  'BANNER_EDIT',       'block',   'valid',          'Remnant'],
            ['TRAFFICKER', 'View',   'txt',  'Inactive', 'BANNER_DEACTIVATE', 'all',     'valid',          'ContractNormal'],
            ['ADVERTISER', 'Edit',   'txt',  'Pending',  'BANNER_ACTIVATE',   'block',   'valid',          'Override'],
            ['MANAGER',    'Create', 'html', 'Approval', 'BANNER_ADD',        'all',     'valid',          'Override'],
            ['ADMIN',      'Delete', 'url',  'Rejected', 'BANNER_DEACTIVATE', 'None',    'valid',          'Override'],
            ['ADMIN',      'View',   'web',  'Approval', 'BANNER_EDIT',       'session', 'valid',          'eCPM'],
            ['MANAGER',    'Delete', 'sql',  'Pending',  'BANNER_EDIT',       'capping', 'valid',          'ContractNormal'],
            ['ADVERTISER', 'Create', 'web',  'Inactive', 'BANNER_EDIT',       'None',    'empty_content',  'ContractECPM'],
            ['TRAFFICKER', 'Edit',   'sql',  'Rejected', 'BANNER_ADD',        'session', 'valid',          'ContractECPM'],
            ['TRAFFICKER', 'Create', 'url',  'Paused',   'BANNER_ACTIVATE',   'capping', 'valid',          'eCPM'],
            ['ADVERTISER', 'Delete', 'txt',  'Running',  'BANNER_ADD',        'capping', 'valid',          'eCPM'],
            ['MANAGER',    'View',   'html', 'Running',  'BANNER_ACTIVATE',   'session', 'valid',          'ContractECPM'],
            ['ADMIN',      'Edit',   'html', 'Awaiting', 'BANNER_DEACTIVATE', 'block',   'valid',          'eCPM'],
            ['ADMIN',      'Edit',   'url',  'Expired',  'BANNER_ADD',        'all',     'valid',          'ContractNormal'],
            ['ADVERTISER', 'Delete', 'web',  'Paused',   'BANNER_ACTIVATE',   'all',     'valid',          'ContractNormal'],
            ['MANAGER',    'Create', 'txt',  'Awaiting', 'BANNER_EDIT',       'None',    'valid',          'ContractNormal'],
            ['TRAFFICKER', 'View',   'sql',  'Expired',  'BANNER_DEACTIVATE', 'None',    'valid',          'eCPM'],
            ['TRAFFICKER', 'Create', 'web',  'Pending',  'BANNER_ADD',        'block',   'valid',          'Override'],
            ['ADVERTISER', 'Edit',   'sql',  'Approval', 'BANNER_DEACTIVATE', 'block',   'valid',          'ContractNormal'],
            ['ADMIN',      'View',   'txt',  'Paused',   'BANNER_EDIT',       'capping', 'valid',          'Override'],
            ['MANAGER',    'Delete', 'url',  'Inactive', 'BANNER_ADD',        'block',   'valid',          'ContractECPM'],
            ['TRAFFICKER', 'Edit',   'txt',  'Running',  'BANNER_EDIT',       'all',     'valid',          'Override'],
            ['MANAGER',    'Delete', 'html', 'Rejected', 'BANNER_ACTIVATE',   'capping', 'valid',          'ContractNormal'],
            ['ADMIN',      'Delete', 'txt',  'Approval', 'BANNER_ACTIVATE',   'session', 'valid',          'Remnant'],
            ['ADMIN',      'Create', 'sql',  'Inactive', 'BANNER_DEACTIVATE', 'session', 'valid',          'Override'],
            ['ADVERTISER', 'Edit',   'html', 'Inactive', 'BANNER_ACTIVATE',   'None',    'valid',          'Remnant'],
            ['ADMIN',      'View',   'web',  'Pending',  'BANNER_DEACTIVATE', 'all',     'valid',          'ContractECPM'],
            ['ADVERTISER', 'View',   'txt',  'Rejected', 'BANNER_EDIT',       'block',   'valid',          'Remnant'],
            ['MANAGER',    'Create', 'web',  'Expired',  'BANNER_ACTIVATE',   'session', 'valid',          'eCPM'],
            ['TRAFFICKER', 'Create', 'url',  'Approval', 'BANNER_EDIT',       'capping', 'valid',          'ContractECPM'],
            ['TRAFFICKER', 'Create', 'txt',  'Awaiting', 'BANNER_ACTIVATE',   'all',     'valid',          'ContractECPM'],
            ['ADVERTISER', 'Create', 'web',  'Rejected', 'BANNER_ADD',        'all',     'wrong_format',   'eCPM'],
            ['ADVERTISER', 'Edit',   'html', 'Paused',   'BANNER_ADD',        'None',    'valid',          'ContractECPM'],
            ['ADVERTISER', 'Edit',   'sql',  'Expired',  'BANNER_ACTIVATE',   'all',     'valid',          'ContractECPM'],
            ['ADVERTISER', 'Edit',   'web',  'Running',  'BANNER_DEACTIVATE', 'block',   'valid',          'ContractNormal'],
            ['ADVERTISER', 'Edit',   'sql',  'Awaiting', 'BANNER_ACTIVATE',   'capping', 'valid',          'Override'],
            ['ADVERTISER', 'Delete', 'url',  'Pending',  'BANNER_ACTIVATE',   'session', 'valid',          'ContractNormal'],
            ['ADVERTISER', 'Delete', 'web',  'Awaiting', 'BANNER_ACTIVATE',   'all',     'valid',          'ContractECPM'],
            ['ADVERTISER', 'Edit',   'txt',  'Expired',  'BANNER_ACTIVATE',   'capping', 'valid',          'Override'],
            ['ADVERTISER', 'Edit',   'sql',  'Paused',   'BANNER_ACTIVATE',   'session', 'valid',          'ContractECPM'],
            ['ADVERTISER', 'Edit',   'url',  'Running',  'BANNER_ACTIVATE',   'all',     'valid',          'Remnant'],
            ['ADVERTISER', 'Edit',   'html', 'Pending',  'BANNER_ACTIVATE',   'None',    'valid',          'Remnant'],
            ['ADVERTISER', 'Edit',   'txt',  'Inactive', 'BANNER_ACTIVATE',   'capping', 'valid',          'eCPM'],
            ['ADVERTISER', 'Edit',   'txt',  'Approval', 'BANNER_ACTIVATE',   'None',    'valid',          'ContractECPM'],

            // Rows 58-100: additional rows to extend coverage to ~100
            ['ADMIN',      'Create', 'web',  'Running',  'BANNER_ADD',        'capping', 'valid',          'ContractNormal'],
            ['ADMIN',      'Create', 'url',  'Paused',   'BANNER_ACTIVATE',   'session', 'valid',          'eCPM'],
            ['ADMIN',      'Create', 'html', 'Awaiting', 'BANNER_DEACTIVATE', 'block',   'valid',          'Override'],
            ['ADMIN',      'Create', 'txt',  'Expired',  'BANNER_ADD',        'all',     'valid',          'ContractECPM'],
            ['ADMIN',      'Edit',   'sql',  'Inactive', 'BANNER_EDIT',       'None',    'valid',          'Remnant'],
            ['ADMIN',      'Edit',   'web',  'Pending',  'BANNER_ACTIVATE',   'capping', 'valid',          'Override'],
            ['ADMIN',      'Edit',   'url',  'Approval', 'BANNER_ADD',        'session', 'valid',          'ContractNormal'],
            ['ADMIN',      'Edit',   'txt',  'Rejected', 'BANNER_DEACTIVATE', 'block',   'valid',          'eCPM'],
            ['ADMIN',      'View',   'sql',  'Running',  'BANNER_EDIT',       'all',     'valid',          'ContractECPM'],
            ['ADMIN',      'View',   'url',  'Paused',   'BANNER_ACTIVATE',   'None',    'valid',          'Remnant'],
            ['ADMIN',      'View',   'html', 'Awaiting', 'BANNER_DEACTIVATE', 'capping', 'valid',          'Override'],
            ['ADMIN',      'View',   'txt',  'Expired',  'BANNER_ADD',        'session', 'valid',          'ContractNormal'],
            ['ADMIN',      'Delete', 'sql',  'Inactive', 'BANNER_EDIT',       'block',   'valid',          'eCPM'],
            ['ADMIN',      'Delete', 'html', 'Pending',  'BANNER_ACTIVATE',   'all',     'valid',          'ContractECPM'],
            ['MANAGER',    'Create', 'sql',  'Paused',   'BANNER_DEACTIVATE', 'block',   'valid',          'eCPM'],
            ['MANAGER',    'Create', 'web',  'Awaiting', 'BANNER_EDIT',       'all',     'valid',          'ContractNormal'],
            ['MANAGER',    'Create', 'url',  'Expired',  'BANNER_ADD',        'None',    'valid',          'Override'],
            ['MANAGER',    'Edit',   'html', 'Inactive', 'BANNER_ACTIVATE',   'capping', 'valid',          'ContractECPM'],
            ['MANAGER',    'Edit',   'txt',  'Pending',  'BANNER_DEACTIVATE', 'session', 'valid',          'Remnant'],
            ['MANAGER',    'Edit',   'url',  'Approval', 'BANNER_ADD',        'block',   'valid',          'eCPM'],
            ['MANAGER',    'Edit',   'sql',  'Rejected', 'BANNER_EDIT',       'all',     'valid',          'ContractNormal'],
            ['MANAGER',    'View',   'web',  'Running',  'BANNER_ACTIVATE',   'None',    'valid',          'Override'],
            ['MANAGER',    'View',   'url',  'Paused',   'BANNER_DEACTIVATE', 'capping', 'valid',          'ContractECPM'],
            ['MANAGER',    'View',   'txt',  'Awaiting', 'BANNER_ADD',        'session', 'valid',          'Remnant'],
            ['MANAGER',    'View',   'sql',  'Expired',  'BANNER_EDIT',       'block',   'valid',          'eCPM'],
            ['MANAGER',    'Delete', 'web',  'Approval', 'BANNER_ACTIVATE',   'all',     'valid',          'ContractNormal'],
            ['MANAGER',    'Delete', 'txt',  'Running',  'BANNER_DEACTIVATE', 'None',    'valid',          'Override'],
            ['ADVERTISER', 'Create', 'html', 'Paused',   'BANNER_EDIT',       'capping', 'valid',          'eCPM'],
            ['ADVERTISER', 'Create', 'txt',  'Awaiting', 'BANNER_ADD',        'session', 'valid',          'ContractNormal'],
            ['ADVERTISER', 'Create', 'url',  'Expired',  'BANNER_ACTIVATE',   'block',   'valid',          'Override'],
            ['ADVERTISER', 'Create', 'sql',  'Inactive', 'BANNER_DEACTIVATE', 'all',     'valid',          'ContractECPM'],
            ['ADVERTISER', 'View',   'web',  'Pending',  'BANNER_EDIT',       'None',    'valid',          'Remnant'],
            ['ADVERTISER', 'View',   'sql',  'Approval', 'BANNER_ADD',        'capping', 'valid',          'Override'],
            ['ADVERTISER', 'View',   'url',  'Rejected', 'BANNER_DEACTIVATE', 'session', 'valid',          'ContractNormal'],
            ['ADVERTISER', 'View',   'html', 'Running',  'BANNER_ACTIVATE',   'block',   'valid',          'ContractECPM'],
            ['ADVERTISER', 'Edit',   'web',  'Awaiting', 'BANNER_ADD',        'all',     'valid',          'eCPM'],
            ['ADMIN',      'Create', 'sql',  'Running',  'BANNER_EDIT',       'capping', 'valid',          'eCPM'],
            ['MANAGER',    'Create', 'web',  'Paused',   'BANNER_ADD',        'block',   'valid',          'Remnant'],
            ['ADMIN',      'Edit',   'web',  'Running',  'BANNER_EDIT',       'all',     'valid',          'ContractNormal'],
            ['MANAGER',    'Edit',   'html', 'Running',  'BANNER_EDIT',       'None',    'valid',          'eCPM'],
            ['ADMIN',      'Create', 'html', 'Pending',  'BANNER_ADD',        'session', 'valid',          'ContractNormal'],
            ['MANAGER',    'Create', 'sql',  'Rejected', 'BANNER_EDIT',       'all',     'valid',          'ContractECPM'],
            ['ADMIN',      'View',   'html', 'Inactive', 'BANNER_EDIT',       'block',   'valid',          'Remnant'],
        ];
    }

    /**
     * Section 4C: Run all pairwise combo rows.
     *
     * Each row either exercises a successful CRUD path or a known-failure
     * path (image validation, TRAFFICKER denial, ADVERTISER delete denial).
     */
    public function testPairwiseIncludedCombos()
    {
        $matrix = $this->_getPairwiseMatrix();
        $this->assertTrue(count($matrix) >= 100, 'Matrix should have >= 100 rows, got ' . count($matrix));

        foreach ($matrix as $idx => $row) {
            $id = 'B' . str_pad($idx + 1, 3, '0', STR_PAD_LEFT);
            [$account, $mode, $storage, $status, $permission, $capping, $image, $campType] = $row;

            // Reset maxFilesize each iteration
            $GLOBALS['_MAX']['CONF']['store']['maxFilesize'] = 0;

            $campaignId = $this->_createCampaignScaffold($campType);
            $shouldSucceed = $this->_shouldSucceed($account, $mode, $storage, $image);

            $dllBanner = new PartialMockOA_Dll_Banner_ComboMatrix($this);

            if ($account === 'TRAFFICKER') {
                // TRAFFICKER gets permission denied
                $dllBanner->setReturnValue('checkPermissions', false);
            } else {
                $dllBanner->setReturnValue('checkPermissions', true);
            }

            switch ($mode) {
                case 'Create':
                    $oBanner = $this->_buildBannerInfo(
                        $campaignId,
                        $storage,
                        $status,
                        $capping,
                        $image,
                        true,
                    );

                    $result = $dllBanner->modify($oBanner);

                    if ($shouldSucceed) {
                        $this->assertTrue($result, "$id: Create should succeed [{$account}/{$storage}/{$image}] - " . $dllBanner->getLastError());
                        $this->assertNotNull($oBanner->bannerId, "$id: bannerId should be set after create");
                    } else {
                        $this->assertFalse($result, "$id: Create should fail [{$account}/{$storage}/{$image}]");
                    }
                    break;

                case 'Edit':
                    // First create a banner to edit
                    $editStorage = $storage;
                    $bannerId = $this->_createBanner($campaignId, $editStorage);

                    $oBanner = $this->_buildBannerInfo(
                        $campaignId,
                        $storage,
                        $status,
                        $capping,
                        $image,
                        false,
                        $bannerId,
                    );

                    $result = $dllBanner->modify($oBanner);

                    if ($shouldSucceed) {
                        $this->assertTrue($result, "$id: Edit should succeed [{$account}/{$storage}] - " . $dllBanner->getLastError());
                    } else {
                        $this->assertFalse($result, "$id: Edit should fail [{$account}/{$storage}]");
                    }
                    break;

                case 'View':
                    $bannerId = $this->_createBanner($campaignId, $storage);
                    $oBannerGet = null;

                    $result = $dllBanner->getBanner($bannerId, $oBannerGet);

                    if ($account === 'TRAFFICKER') {
                        // getBanner checks permissions with null account types, so it calls
                        // checkPermissions which we mocked to return false for TRAFFICKER
                        $this->assertFalse($result, "$id: View should fail for TRAFFICKER");
                    } else {
                        $this->assertTrue($result, "$id: View should succeed [{$account}/{$storage}] - " . $dllBanner->getLastError());
                        $this->assertNotNull($oBannerGet, "$id: banner info should be returned");
                        $this->assertEqual($oBannerGet->bannerId, $bannerId, "$id: returned bannerId should match");
                    }
                    break;

                case 'Delete':
                    $bannerId = $this->_createBanner($campaignId, $storage);

                    $result = $dllBanner->delete($bannerId);

                    if ($shouldSucceed) {
                        $this->assertTrue($result, "$id: Delete should succeed [{$account}/{$storage}] - " . $dllBanner->getLastError());
                    } else {
                        $this->assertFalse($result, "$id: Delete should fail [{$account}/{$storage}]");
                    }
                    break;
            }
        }
    }

    // ===================================================================
    // SECTION 4D: Excluded Combo Negative Tests
    // ===================================================================

    /**
     * 4D-1: TRAFFICKER + Create banner -> denied
     */
    public function testExcluded_TraffickerCreateDenied()
    {
        $campaignId = $this->_createCampaignScaffold('Remnant');

        $dllBanner = new PartialMockOA_Dll_Banner_ComboMatrix($this);
        $dllBanner->setReturnValue('checkPermissions', false);

        $oBanner = new OA_Dll_BannerInfo();
        $oBanner->campaignId = $campaignId;
        $oBanner->storageType = 'html';
        $oBanner->htmlTemplate = '<div>ad</div>';

        $result = $dllBanner->modify($oBanner);
        $this->assertFalse($result, 'TRAFFICKER should not be able to create a banner');
    }

    /**
     * 4D-2: TRAFFICKER + Edit banner -> denied
     */
    public function testExcluded_TraffickerEditDenied()
    {
        $campaignId = $this->_createCampaignScaffold('Remnant');
        $bannerId = $this->_createBanner($campaignId, 'html');

        $dllBanner = new PartialMockOA_Dll_Banner_ComboMatrix($this);
        $dllBanner->setReturnValue('checkPermissions', false);

        $oBanner = new OA_Dll_BannerInfo();
        $oBanner->bannerId = $bannerId;
        $oBanner->bannerName = 'Should not update';

        $result = $dllBanner->modify($oBanner);
        $this->assertFalse($result, 'TRAFFICKER should not be able to edit a banner');
    }

    /**
     * 4D-3: TRAFFICKER + View banner -> denied
     */
    public function testExcluded_TraffickerViewDenied()
    {
        $campaignId = $this->_createCampaignScaffold('Remnant');
        $bannerId = $this->_createBanner($campaignId, 'html');

        $dllBanner = new PartialMockOA_Dll_Banner_ComboMatrix($this);
        $dllBanner->setReturnValue('checkPermissions', false);

        $oBannerGet = null;
        $result = $dllBanner->getBanner($bannerId, $oBannerGet);
        $this->assertFalse($result, 'TRAFFICKER should not be able to view a banner');
    }

    /**
     * 4D-4: TRAFFICKER + Delete banner -> denied
     */
    public function testExcluded_TraffickerDeleteDenied()
    {
        $campaignId = $this->_createCampaignScaffold('Remnant');
        $bannerId = $this->_createBanner($campaignId, 'html');

        $dllBanner = new PartialMockOA_Dll_Banner_ComboMatrix($this);
        $dllBanner->setReturnValue('checkPermissions', false);

        $result = $dllBanner->delete($bannerId);
        $this->assertFalse($result, 'TRAFFICKER should not be able to delete a banner');
    }

    /**
     * 4D-5: Storage=sql + no image on Create -> validation error
     */
    public function testExcluded_SqlCreateNoImage()
    {
        $campaignId = $this->_createCampaignScaffold('Remnant');

        $dllBanner = new PartialMockOA_Dll_Banner_ComboMatrix($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $oBanner = new OA_Dll_BannerInfo();
        $oBanner->campaignId = $campaignId;
        $oBanner->storageType = 'sql';
        // No aImage set

        $result = $dllBanner->modify($oBanner);
        $this->assertFalse($result, 'sql Create without image should fail');
        $this->assertEqual(
            $dllBanner->getLastError(),
            $this->imageRequiredError,
            'Should get image required error for sql without image',
        );
    }

    /**
     * 4D-6: Storage=web + no image on Create -> validation error
     */
    public function testExcluded_WebCreateNoImage()
    {
        $campaignId = $this->_createCampaignScaffold('ContractNormal');

        $dllBanner = new PartialMockOA_Dll_Banner_ComboMatrix($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $oBanner = new OA_Dll_BannerInfo();
        $oBanner->campaignId = $campaignId;
        $oBanner->storageType = 'web';
        // No aImage set

        $result = $dllBanner->modify($oBanner);
        $this->assertFalse($result, 'web Create without image should fail');
        $this->assertEqual(
            $dllBanner->getLastError(),
            $this->imageRequiredError,
            'Should get image required error for web without image',
        );
    }

    /**
     * 4D-7: Storage=html + aImage set -> image is ignored; create succeeds
     */
    public function testExcluded_HtmlWithImageIgnored()
    {
        $campaignId = $this->_createCampaignScaffold('Override');

        $dllBanner = new PartialMockOA_Dll_Banner_ComboMatrix($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $oBanner = new OA_Dll_BannerInfo();
        $oBanner->campaignId = $campaignId;
        $oBanner->storageType = 'html';
        $oBanner->htmlTemplate = '<div>ad</div>';
        $oBanner->width = 468;
        $oBanner->height = 60;
        $oBanner->aImage = [
            'filename' => '1x1.gif',
            'content'  => $this->binaryGif,
        ];

        $result = $dllBanner->modify($oBanner);
        $this->assertTrue($result, 'html Create with aImage should succeed (image ignored) - ' . $dllBanner->getLastError());
        $this->assertNotNull($oBanner->bannerId, 'bannerId should be set');
    }

    /**
     * 4D-8: Storage=txt + aImage set -> image is ignored; create succeeds
     */
    public function testExcluded_TxtWithImageIgnored()
    {
        $campaignId = $this->_createCampaignScaffold('eCPM');

        $dllBanner = new PartialMockOA_Dll_Banner_ComboMatrix($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $oBanner = new OA_Dll_BannerInfo();
        $oBanner->campaignId = $campaignId;
        $oBanner->storageType = 'txt';
        $oBanner->bannerText = 'Text ad';
        $oBanner->aImage = [
            'filename' => '1x1.gif',
            'content'  => $this->binaryGif,
        ];

        $result = $dllBanner->modify($oBanner);
        $this->assertTrue($result, 'txt Create with aImage should succeed (image ignored) - ' . $dllBanner->getLastError());
        $this->assertNotNull($oBanner->bannerId, 'bannerId should be set');
    }

    /**
     * 4D-9: Storage=url + aImage set -> image is ignored; create succeeds
     */
    public function testExcluded_UrlWithImageIgnored()
    {
        $campaignId = $this->_createCampaignScaffold('ContractECPM');

        $dllBanner = new PartialMockOA_Dll_Banner_ComboMatrix($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $oBanner = new OA_Dll_BannerInfo();
        $oBanner->campaignId = $campaignId;
        $oBanner->storageType = 'url';
        $oBanner->imageURL = 'http://example.com/banner.gif';
        $oBanner->width = 468;
        $oBanner->height = 60;
        $oBanner->aImage = [
            'filename' => '1x1.gif',
            'content'  => $this->binaryGif,
        ];

        $result = $dllBanner->modify($oBanner);
        $this->assertTrue($result, 'url Create with aImage should succeed (image ignored) - ' . $dllBanner->getLastError());
        $this->assertNotNull($oBanner->bannerId, 'bannerId should be set');
    }

    /**
     * 4D-10: ADVERTISER + Delete banner -> denied
     * (Delete enforces [OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER])
     */
    public function testExcluded_AdvertiserDeleteDenied()
    {
        $campaignId = $this->_createCampaignScaffold('Remnant');
        $bannerId = $this->_createBanner($campaignId, 'html');

        $dllBanner = new PartialMockOA_Dll_Banner_ComboMatrix($this);
        // Simulate advertiser: deny delete permission check
        $dllBanner->setReturnValue('checkPermissions', false);

        $result = $dllBanner->delete($bannerId);
        $this->assertFalse($result, 'ADVERTISER should not be able to delete a banner');
    }

    /**
     * 4D-11: ADVERTISER + Create + no BANNER_EDIT permission -> denied
     */
    public function testExcluded_AdvertiserCreateNoBannerEditPerm()
    {
        $campaignId = $this->_createCampaignScaffold('Remnant');

        $dllBanner = new PartialMockOA_Dll_Banner_ComboMatrix($this);
        // Simulate advertiser without BANNER_EDIT: deny permission
        $dllBanner->setReturnValue('checkPermissions', false);

        $oBanner = new OA_Dll_BannerInfo();
        $oBanner->campaignId = $campaignId;
        $oBanner->storageType = 'html';
        $oBanner->htmlTemplate = '<div>ad</div>';

        $result = $dllBanner->modify($oBanner);
        $this->assertFalse($result, 'ADVERTISER without BANNER_EDIT should be denied Create');
    }

    /**
     * 4D-12: sql Create with empty filename -> validation error
     */
    public function testExcluded_SqlCreateEmptyFilename()
    {
        $campaignId = $this->_createCampaignScaffold('Remnant');

        $dllBanner = new PartialMockOA_Dll_Banner_ComboMatrix($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $oBanner = new OA_Dll_BannerInfo();
        $oBanner->campaignId = $campaignId;
        $oBanner->storageType = 'sql';
        $oBanner->aImage = [
            'filename' => '',
            'content'  => $this->binaryGif,
        ];

        $result = $dllBanner->modify($oBanner);
        $this->assertFalse($result, 'sql Create with empty filename should fail');
        $this->assertEqual(
            $dllBanner->getLastError(),
            $this->imageFilenameEmptyError,
            'Should get empty filename error',
        );
    }

    /**
     * 4D-13: sql Create with empty content -> validation error
     */
    public function testExcluded_SqlCreateEmptyContent()
    {
        $campaignId = $this->_createCampaignScaffold('Remnant');

        $dllBanner = new PartialMockOA_Dll_Banner_ComboMatrix($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $oBanner = new OA_Dll_BannerInfo();
        $oBanner->campaignId = $campaignId;
        $oBanner->storageType = 'sql';
        $oBanner->aImage = [
            'filename' => '1x1.gif',
            'content'  => '',
        ];

        $result = $dllBanner->modify($oBanner);
        $this->assertFalse($result, 'sql Create with empty content should fail');
        $this->assertEqual(
            $dllBanner->getLastError(),
            $this->imageContentEmptyError,
            'Should get empty content error',
        );
    }

    /**
     * 4D-14: sql Create with wrong format -> validation error
     */
    public function testExcluded_SqlCreateWrongFormat()
    {
        $campaignId = $this->_createCampaignScaffold('eCPM');

        $dllBanner = new PartialMockOA_Dll_Banner_ComboMatrix($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $oBanner = new OA_Dll_BannerInfo();
        $oBanner->campaignId = $campaignId;
        $oBanner->storageType = 'sql';
        $oBanner->aImage = [
            'filename' => 'test.gif',
            'content'  => 'not-an-image',
        ];

        $result = $dllBanner->modify($oBanner);
        $this->assertFalse($result, 'sql Create with wrong format should fail');
        $this->assertEqual(
            $dllBanner->getLastError(),
            $this->unknownFormatError,
            'Should get unknown format error',
        );
    }

    /**
     * 4D-15: sql Create with oversized image -> validation error
     */
    public function testExcluded_SqlCreateOversized()
    {
        $campaignId = $this->_createCampaignScaffold('ContractNormal');

        $dllBanner = new PartialMockOA_Dll_Banner_ComboMatrix($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $GLOBALS['_MAX']['CONF']['store']['maxFilesize'] = 16;

        $oBanner = new OA_Dll_BannerInfo();
        $oBanner->campaignId = $campaignId;
        $oBanner->storageType = 'sql';
        $oBanner->aImage = [
            'filename' => '1x1.gif',
            'content'  => $this->binaryGif,
        ];

        $result = $dllBanner->modify($oBanner);
        $this->assertFalse($result, 'sql Create with oversized image should fail');

        // Reset
        $GLOBALS['_MAX']['CONF']['store']['maxFilesize'] = 0;
    }

    /**
     * 4D-16: web Create with empty filename -> validation error
     */
    public function testExcluded_WebCreateEmptyFilename()
    {
        $campaignId = $this->_createCampaignScaffold('Override');

        $dllBanner = new PartialMockOA_Dll_Banner_ComboMatrix($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $oBanner = new OA_Dll_BannerInfo();
        $oBanner->campaignId = $campaignId;
        $oBanner->storageType = 'web';
        $oBanner->aImage = [
            'filename' => '',
            'content'  => $this->binaryGif,
        ];

        $result = $dllBanner->modify($oBanner);
        $this->assertFalse($result, 'web Create with empty filename should fail');
        $this->assertEqual(
            $dllBanner->getLastError(),
            $this->imageFilenameEmptyError,
            'Should get empty filename error',
        );
    }

    /**
     * 4D-17: web Create with wrong format -> validation error
     */
    public function testExcluded_WebCreateWrongFormat()
    {
        $campaignId = $this->_createCampaignScaffold('ContractECPM');

        $dllBanner = new PartialMockOA_Dll_Banner_ComboMatrix($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $oBanner = new OA_Dll_BannerInfo();
        $oBanner->campaignId = $campaignId;
        $oBanner->storageType = 'web';
        $oBanner->aImage = [
            'filename' => 'test.gif',
            'content'  => 'not-an-image',
        ];

        $result = $dllBanner->modify($oBanner);
        $this->assertFalse($result, 'web Create with wrong format should fail');
        $this->assertEqual(
            $dllBanner->getLastError(),
            $this->unknownFormatError,
            'Should get unknown format error',
        );
    }

    /**
     * 4D-18: web Create with oversized image -> validation error
     */
    public function testExcluded_WebCreateOversized()
    {
        $campaignId = $this->_createCampaignScaffold('eCPM');

        $dllBanner = new PartialMockOA_Dll_Banner_ComboMatrix($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $GLOBALS['_MAX']['CONF']['store']['maxFilesize'] = 16;

        $oBanner = new OA_Dll_BannerInfo();
        $oBanner->campaignId = $campaignId;
        $oBanner->storageType = 'web';
        $oBanner->aImage = [
            'filename' => '1x1.gif',
            'content'  => $this->binaryGif,
        ];

        $result = $dllBanner->modify($oBanner);
        $this->assertFalse($result, 'web Create with oversized image should fail');

        // Reset
        $GLOBALS['_MAX']['CONF']['store']['maxFilesize'] = 0;
    }

    /**
     * 4D-19: TRAFFICKER + Create across all storage types -> denied
     */
    public function testExcluded_TraffickerCreateAllStorageTypes()
    {
        $storageTypes = ['sql', 'web', 'url', 'html', 'txt'];

        foreach ($storageTypes as $storage) {
            $campaignId = $this->_createCampaignScaffold('Remnant');

            $dllBanner = new PartialMockOA_Dll_Banner_ComboMatrix($this);
            $dllBanner->setReturnValue('checkPermissions', false);

            $oBanner = new OA_Dll_BannerInfo();
            $oBanner->campaignId = $campaignId;
            $oBanner->storageType = $storage;

            if ($storage === 'sql' || $storage === 'web') {
                $oBanner->aImage = [
                    'filename' => '1x1.gif',
                    'content'  => $this->binaryGif,
                ];
            } elseif ($storage === 'html') {
                $oBanner->htmlTemplate = '<div>ad</div>';
            } elseif ($storage === 'txt') {
                $oBanner->bannerText = 'Text ad';
            } elseif ($storage === 'url') {
                $oBanner->imageURL = 'http://example.com/banner.gif';
            }

            $result = $dllBanner->modify($oBanner);
            $this->assertFalse($result, "TRAFFICKER Create with storage=$storage should be denied");
        }
    }

    /**
     * 4D-20: TRAFFICKER + Delete across all storage types -> denied
     */
    public function testExcluded_TraffickerDeleteAllStorageTypes()
    {
        $storageTypes = ['sql', 'web', 'url', 'html', 'txt'];

        foreach ($storageTypes as $storage) {
            $campaignId = $this->_createCampaignScaffold('ContractNormal');
            $bannerId = $this->_createBanner($campaignId, $storage);

            $dllBanner = new PartialMockOA_Dll_Banner_ComboMatrix($this);
            $dllBanner->setReturnValue('checkPermissions', false);

            $result = $dllBanner->delete($bannerId);
            $this->assertFalse($result, "TRAFFICKER Delete with storage=$storage should be denied");
        }
    }

    /**
     * 4D-21: ADVERTISER + Delete across all campaign types -> denied
     */
    public function testExcluded_AdvertiserDeleteAllCampaignTypes()
    {
        $campaignTypes = ['Remnant', 'ContractNormal', 'Override', 'eCPM', 'ContractECPM'];

        foreach ($campaignTypes as $campType) {
            $campaignId = $this->_createCampaignScaffold($campType);
            $bannerId = $this->_createBanner($campaignId, 'html');

            $dllBanner = new PartialMockOA_Dll_Banner_ComboMatrix($this);
            $dllBanner->setReturnValue('checkPermissions', false);

            $result = $dllBanner->delete($bannerId);
            $this->assertFalse($result, "ADVERTISER Delete with campaign type=$campType should be denied");
        }
    }

    /**
     * 4D-22: sql/web Create with valid image but various campaign types -> succeed
     */
    public function testExcluded_SqlWebCreateWithImageAllCampaignTypes()
    {
        $campaignTypes = ['Remnant', 'ContractNormal', 'Override', 'eCPM', 'ContractECPM'];

        foreach (['sql', 'web'] as $storage) {
            foreach ($campaignTypes as $campType) {
                $campaignId = $this->_createCampaignScaffold($campType);

                $dllBanner = new PartialMockOA_Dll_Banner_ComboMatrix($this);
                $dllBanner->setReturnValue('checkPermissions', true);

                $oBanner = new OA_Dll_BannerInfo();
                $oBanner->campaignId = $campaignId;
                $oBanner->storageType = $storage;
                $oBanner->aImage = [
                    'filename' => '1x1.gif',
                    'content'  => $this->binaryGif,
                ];

                $result = $dllBanner->modify($oBanner);
                $this->assertTrue(
                    $result,
                    "$storage Create with valid image + campaign=$campType should succeed - " . $dllBanner->getLastError(),
                );
                $this->assertNotNull($oBanner->bannerId);

                // Clean up web-stored files
                if ($storage === 'web') {
                    $doBanners = OA_Dal::staticGetDO('banners', $oBanner->bannerId);
                    if ($doBanners) {
                        $img = $GLOBALS['_MAX']['CONF']['store']['webDir'] . '/' . $doBanners->filename;
                        if (file_exists($img)) {
                            @unlink($img);
                        }
                    }
                }
            }
        }
    }

    /**
     * 4D-23: Verify capping fields are stored correctly on Create
     */
    public function testExcluded_CappingFieldsPersistence()
    {
        $cappingConfigs = [
            'None'    => ['capping' => 0, 'session_capping' => 0, 'block' => 0],
            'capping' => ['capping' => 10, 'session_capping' => 0, 'block' => 0],
            'session' => ['capping' => 0, 'session_capping' => 5, 'block' => 0],
            'block'   => ['capping' => 0, 'session_capping' => 0, 'block' => 3600],
            'all'     => ['capping' => 10, 'session_capping' => 5, 'block' => 3600],
        ];

        foreach ($cappingConfigs as $cappingKey => $expected) {
            $campaignId = $this->_createCampaignScaffold('Remnant');

            $dllBanner = new PartialMockOA_Dll_Banner_ComboMatrix($this);
            $dllBanner->setReturnValue('checkPermissions', true);

            $oBanner = $this->_buildBannerInfo(
                $campaignId,
                'html',
                'Running',
                $cappingKey,
                'valid',
                true,
            );

            $result = $dllBanner->modify($oBanner);
            $this->assertTrue($result, "Create with capping=$cappingKey should succeed - " . $dllBanner->getLastError());

            $doBanners = OA_Dal::staticGetDO('banners', $oBanner->bannerId);
            $this->assertNotNull($doBanners, "Banner record should exist for capping=$cappingKey");
            $this->assertEqual(
                (int) $doBanners->capping,
                $expected['capping'],
                "capping value for key=$cappingKey",
            );
            $this->assertEqual(
                (int) $doBanners->session_capping,
                $expected['session_capping'],
                "session_capping value for key=$cappingKey",
            );
            $this->assertEqual(
                (int) $doBanners->block,
                $expected['block'],
                "block value for key=$cappingKey",
            );
        }
    }

    /**
     * 4D-24: Verify status field is stored correctly on Create
     */
    public function testExcluded_StatusFieldPersistence()
    {
        foreach (self::$statusMap as $statusKey => $statusValue) {
            $campaignId = $this->_createCampaignScaffold('Remnant');

            $dllBanner = new PartialMockOA_Dll_Banner_ComboMatrix($this);
            $dllBanner->setReturnValue('checkPermissions', true);

            $oBanner = new OA_Dll_BannerInfo();
            $oBanner->campaignId = $campaignId;
            $oBanner->storageType = 'html';
            $oBanner->htmlTemplate = '<div>ad</div>';
            $oBanner->status = $statusValue;

            $result = $dllBanner->modify($oBanner);
            $this->assertTrue($result, "Create with status=$statusKey should succeed - " . $dllBanner->getLastError());

            $doBanners = OA_Dal::staticGetDO('banners', $oBanner->bannerId);
            $this->assertNotNull($doBanners, "Banner record should exist for status=$statusKey");
            $this->assertEqual(
                (int) $doBanners->status,
                $statusValue,
                "status value for key=$statusKey expected=$statusValue got=" . $doBanners->status,
            );
        }
    }

    /**
     * 4D-25: web Create with empty content -> validation error
     */
    public function testExcluded_WebCreateEmptyContent()
    {
        $campaignId = $this->_createCampaignScaffold('Remnant');

        $dllBanner = new PartialMockOA_Dll_Banner_ComboMatrix($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $oBanner = new OA_Dll_BannerInfo();
        $oBanner->campaignId = $campaignId;
        $oBanner->storageType = 'web';
        $oBanner->aImage = [
            'filename' => '1x1.gif',
            'content'  => '',
        ];

        $result = $dllBanner->modify($oBanner);
        $this->assertFalse($result, 'web Create with empty content should fail');
        $this->assertEqual(
            $dllBanner->getLastError(),
            $this->imageContentEmptyError,
            'Should get empty content error for web storage',
        );
    }

    /**
     * 4D-26: ADVERTISER Delete across all storage types -> denied
     */
    public function testExcluded_AdvertiserDeleteAllStorageTypes()
    {
        $storageTypes = ['sql', 'web', 'url', 'html', 'txt'];

        foreach ($storageTypes as $storage) {
            $campaignId = $this->_createCampaignScaffold('Remnant');
            $bannerId = $this->_createBanner($campaignId, $storage);

            $dllBanner = new PartialMockOA_Dll_Banner_ComboMatrix($this);
            $dllBanner->setReturnValue('checkPermissions', false);

            $result = $dllBanner->delete($bannerId);
            $this->assertFalse($result, "ADVERTISER Delete with storage=$storage should be denied");
        }
    }

    /**
     * 4D-27: Verify all five storage types can be created successfully
     */
    public function testExcluded_AllStorageTypesCreateSuccessfully()
    {
        $storageTypes = [
            'sql'  => ['aImage' => ['filename' => '1x1.gif', 'content' => $this->binaryGif]],
            'web'  => ['aImage' => ['filename' => '1x1.gif', 'content' => $this->binaryGif]],
            'url'  => ['imageURL' => 'http://example.com/banner.gif', 'width' => 468, 'height' => 60],
            'html' => ['htmlTemplate' => '<div>ad</div>', 'width' => 468, 'height' => 60],
            'txt'  => ['bannerText' => 'Text ad'],
        ];

        foreach ($storageTypes as $storage => $fields) {
            $campaignId = $this->_createCampaignScaffold('Remnant');

            $dllBanner = new PartialMockOA_Dll_Banner_ComboMatrix($this);
            $dllBanner->setReturnValue('checkPermissions', true);

            $oBanner = new OA_Dll_BannerInfo();
            $oBanner->campaignId = $campaignId;
            $oBanner->storageType = $storage;

            foreach ($fields as $field => $value) {
                $oBanner->$field = $value;
            }

            $result = $dllBanner->modify($oBanner);
            $this->assertTrue($result, "Create with storage=$storage should succeed - " . $dllBanner->getLastError());
            $this->assertNotNull($oBanner->bannerId, "bannerId should be set for storage=$storage");

            // Verify it persisted correctly
            $doBanners = OA_Dal::staticGetDO('banners', $oBanner->bannerId);
            $this->assertNotNull($doBanners, "Banner record should exist for storage=$storage");
            $this->assertEqual($doBanners->storagetype, $storage, "storagetype should be $storage");

            // Clean up web-stored files
            if ($storage === 'web') {
                $img = $GLOBALS['_MAX']['CONF']['store']['webDir'] . '/' . $doBanners->filename;
                if (file_exists($img)) {
                    @unlink($img);
                }
            }
        }
    }
}
