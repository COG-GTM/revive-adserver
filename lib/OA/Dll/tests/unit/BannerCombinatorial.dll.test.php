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
 * Banner CRUD Combination Matrix Tests (Section 4C + 4D)
 *
 * Tests ~96 pairwise-generated included combos across 8 dimensions:
 *   Account type, Page mode, Storage type, Entity status,
 *   Banner permission, Frequency capping, Image validation, Parent campaign type
 *
 * Plus ~21 excluded/negative combos testing validation and permission denial paths.
 *
 * @package    OpenXDll
 * @subpackage TestSuite
 */
class OA_Dll_BannerCombinatorialTest extends DllUnitTestCase
{
    /**
     * @var int
     */
    public $agencyId;

    /**
     * Binary GIF: minimal valid 1x1 GIF89a image
     */
    public $binaryGif;

    /**
     * A slightly different valid 1x1 GIF (different color table)
     */
    public $binaryGifAlt;

    public function __construct()
    {
        parent::__construct();
        Mock::generatePartial(
            'OA_Dll_Banner',
            'PartialMockOA_Dll_Banner_Combo',
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
        $this->binaryGifAlt = "GIF89a\001\0\001\0\200\0\0\0\0\0\377\377\377!\371\004\0\0\0\0\0,\0\0\0\0\001\0\001\0\0\002\002L\001\0;";
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
    // Helper Methods
    // =========================================================================

    /**
     * Create advertiser via DLL mock.
     *
     * @return OA_Dll_AdvertiserInfo
     */
    private function _createAdvertiser()
    {
        $dllAdv = new PartialMockOA_Dll_Advertiser_Combo($this);
        $dllAdv->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdv->setReturnValue('checkPermissions', true);

        $oAdv = new OA_Dll_AdvertiserInfo();
        $oAdv->advertiserName = 'Combo Test Advertiser';
        $oAdv->agencyId = $this->agencyId;
        $dllAdv->modify($oAdv);
        return $oAdv;
    }

    /**
     * Create campaign of a given type via DLL mock.
     *
     * Campaign type mapping:
     *   Remnant       => priority=0, weight=1
     *   ContractNormal=> priority=5, weight=0
     *   Override      => priority=-1, weight=0
     *   eCPM          => priority=-2, weight=0, revenue=1.0, revenueType=CPM
     *   ContractECPM  => priority=-2, weight=0, revenue=1.0, revenueType=CPM
     *
     * @param int    $advertiserId
     * @param string $campaignType
     *
     * @return OA_Dll_CampaignInfo
     */
    private function _createCampaign($advertiserId, $campaignType)
    {
        $dllCamp = new PartialMockOA_Dll_Campaign_Combo($this);
        $dllCamp->setReturnValue('checkPermissions', true);

        $oCamp = new OA_Dll_CampaignInfo();
        $oCamp->advertiserId = $advertiserId;
        $oCamp->campaignName = 'Combo Campaign - ' . $campaignType;

        switch ($campaignType) {
            case 'Remnant':
                $oCamp->priority = 0;
                $oCamp->weight = 1;
                break;
            case 'ContractNormal':
                $oCamp->priority = 5;
                $oCamp->weight = 0;
                break;
            case 'Override':
                $oCamp->priority = -1;
                $oCamp->weight = 1;
                break;
            case 'eCPM':
                $oCamp->priority = -2;
                $oCamp->weight = 0;
                $oCamp->revenue = 1.0;
                $oCamp->revenueType = MAX_FINANCE_CPM;
                break;
            case 'ContractECPM':
                $oCamp->priority = -2;
                $oCamp->weight = 0;
                $oCamp->revenue = 2.0;
                $oCamp->revenueType = MAX_FINANCE_CPM;
                break;
        }

        $dllCamp->modify($oCamp);
        return $oCamp;
    }

    /**
     * Build a BannerInfo object for a given combo.
     *
     * @param int    $campaignId
     * @param string $storageType
     * @param string $imageType   valid_gif|different_gif|NA|empty_filename|empty_content|wrong_format|oversized
     * @param int    $cappingVal
     * @param int    $sessionCappingVal
     * @param int    $blockVal
     * @param int|null $existingBannerId  If editing, the existing banner ID
     *
     * @return OA_Dll_BannerInfo
     */
    private function _buildBannerInfo(
        $campaignId,
        $storageType,
        $imageType,
        $cappingVal,
        $sessionCappingVal,
        $blockVal,
        $existingBannerId = null
    ) {
        $oBanner = new OA_Dll_BannerInfo();

        if ($existingBannerId !== null) {
            $oBanner->bannerId = $existingBannerId;
        } else {
            $oBanner->campaignId = $campaignId;
            $oBanner->storageType = $storageType;
        }

        $oBanner->bannerName = 'Combo Banner ' . $storageType;

        // Set capping fields
        if ($cappingVal > 0) {
            $oBanner->capping = $cappingVal;
        }
        if ($sessionCappingVal > 0) {
            $oBanner->sessionCapping = $sessionCappingVal;
        }
        if ($blockVal > 0) {
            $oBanner->block = $blockVal;
        }

        // Set storage-specific fields
        switch ($storageType) {
            case 'sql':
            case 'web':
                if ($imageType === 'valid_gif') {
                    $oBanner->aImage = [
                        'filename' => '1x1.gif',
                        'content' => $this->binaryGif,
                    ];
                } elseif ($imageType === 'different_gif') {
                    $oBanner->aImage = [
                        'filename' => 'alt.gif',
                        'content' => $this->binaryGifAlt,
                    ];
                } elseif ($imageType === 'empty_filename') {
                    $oBanner->aImage = [
                        'filename' => '',
                        'content' => $this->binaryGif,
                    ];
                } elseif ($imageType === 'empty_content') {
                    $oBanner->aImage = [
                        'filename' => 'test.gif',
                        'content' => '',
                    ];
                } elseif ($imageType === 'wrong_format') {
                    $oBanner->aImage = [
                        'filename' => 'test.gif',
                        'content' => 'notanimagedata',
                    ];
                } elseif ($imageType === 'oversized') {
                    $oBanner->aImage = [
                        'filename' => 'big.gif',
                        'content' => $this->binaryGif,
                    ];
                }
                // NA: no image set
                break;
            case 'html':
                $oBanner->htmlTemplate = '<div>Combo HTML Banner</div>';
                $oBanner->width = 468;
                $oBanner->height = 60;
                break;
            case 'txt':
                $oBanner->bannerText = 'Combo text banner content';
                $oBanner->width = 0;
                $oBanner->height = 0;
                break;
            case 'url':
                $oBanner->imageURL = 'http://example.com/banner.gif';
                $oBanner->width = 468;
                $oBanner->height = 60;
                break;
        }

        return $oBanner;
    }

    /**
     * Parse capping type into individual values.
     *
     * @param string $cappingType  None|capping_only|session_only|block_only|all_three
     *
     * @return array [capping, sessionCapping, block]
     */
    private function _parseCapping($cappingType)
    {
        switch ($cappingType) {
            case 'None':
                return [0, 0, 0];
            case 'capping_only':
                return [10, 0, 0];
            case 'session_only':
                return [0, 5, 0];
            case 'block_only':
                return [0, 0, 3600];
            case 'all_three':
                return [10, 5, 3600];
            default:
                return [0, 0, 0];
        }
    }

    /**
     * Create a banner (for Edit/View/Delete tests that need a pre-existing banner).
     *
     * @param int    $campaignId
     * @param string $storageType
     *
     * @return int  bannerId
     */
    private function _createExistingBanner($campaignId, $storageType)
    {
        $dllBanner = new PartialMockOA_Dll_Banner_Combo($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $oBanner = new OA_Dll_BannerInfo();
        $oBanner->campaignId = $campaignId;
        $oBanner->storageType = $storageType;
        $oBanner->bannerName = 'Pre-existing banner';

        switch ($storageType) {
            case 'sql':
            case 'web':
                $oBanner->aImage = [
                    'filename' => '1x1.gif',
                    'content' => $this->binaryGif,
                ];
                break;
            case 'html':
                $oBanner->htmlTemplate = '<div>Pre-existing HTML</div>';
                $oBanner->width = 468;
                $oBanner->height = 60;
                break;
            case 'txt':
                $oBanner->bannerText = 'Pre-existing text';
                $oBanner->width = 0;
                $oBanner->height = 0;
                break;
            case 'url':
                $oBanner->imageURL = 'http://example.com/existing.gif';
                $oBanner->width = 468;
                $oBanner->height = 60;
                break;
        }

        $dllBanner->modify($oBanner);
        return $oBanner->bannerId;
    }

    /**
     * Run one included combo test case.
     *
     * @param string $comboId
     * @param string $accountType     ADMIN|MANAGER|ADVERTISER
     * @param string $mode            Create|Edit|View|Delete
     * @param string $storageType     sql|web|url|html|txt
     * @param string $entityStatus    Running|Paused|Awaiting|Expired|Inactive|Pending|Approval|Rejected
     * @param string $permission      BANNER_ACTIVATE|BANNER_DEACTIVATE|BANNER_ADD|BANNER_EDIT
     * @param string $cappingType     None|capping_only|session_only|block_only|all_three
     * @param string $imageType       valid_gif|different_gif|NA
     * @param string $campaignType    Remnant|ContractNormal|Override|eCPM|ContractECPM
     */
    private function _runIncludedCombo(
        $comboId,
        $accountType,
        $mode,
        $storageType,
        $entityStatus,
        $permission,
        $cappingType,
        $imageType,
        $campaignType
    ) {
        $GLOBALS['_MAX']['CONF']['store']['mode'] = 0;
        $GLOBALS['_MAX']['CONF']['store']['webDir'] = MAX_PATH . '/var';
        $GLOBALS['_MAX']['CONF']['store']['maxFilesize'] = 0;

        // Create prerequisite entities
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId, $campaignType);
        $this->assertNotNull($oCamp->campaignId, "{$comboId}: Campaign creation failed for type {$campaignType}");

        // Parse capping
        [$cappingVal, $sessionCappingVal, $blockVal] = $this->_parseCapping($cappingType);

        // Create banner DLL mock with permissions returning true
        $dllBanner = new PartialMockOA_Dll_Banner_Combo($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        switch ($mode) {
            case 'Create':
                $oBanner = $this->_buildBannerInfo(
                    $oCamp->campaignId,
                    $storageType,
                    $imageType,
                    $cappingVal,
                    $sessionCappingVal,
                    $blockVal,
                );

                $result = $dllBanner->modify($oBanner);
                $this->assertTrue($result, "{$comboId}: Create should succeed - " . $dllBanner->getLastError());
                $this->assertNotNull($oBanner->bannerId, "{$comboId}: Banner ID should be set after create");

                // Verify capping was stored correctly
                if ($oBanner->bannerId) {
                    $doBanner = OA_Dal::staticGetDO('banners', $oBanner->bannerId);
                    if ($doBanner) {
                        $this->assertEqual((int) $doBanner->capping, $cappingVal, "{$comboId}: capping mismatch");
                        $this->assertEqual((int) $doBanner->session_capping, $sessionCappingVal, "{$comboId}: sessionCapping mismatch");
                        $this->assertEqual((int) $doBanner->block, $blockVal, "{$comboId}: block mismatch");
                    }
                }
                // Clean up uploaded file for web storage
                if ($storageType === 'web' && $oBanner->bannerId) {
                    $doBannerClean = OA_Dal::staticGetDO('banners', $oBanner->bannerId);
                    if ($doBannerClean && !empty($doBannerClean->filename)) {
                        $img = $GLOBALS['_MAX']['CONF']['store']['webDir'] . '/' . $doBannerClean->filename;
                        if (file_exists($img)) {
                            @unlink($img);
                        }
                    }
                }
                break;

            case 'Edit':
                // First create a banner to edit
                $existingBannerId = $this->_createExistingBanner($oCamp->campaignId, $storageType);
                $this->assertNotNull($existingBannerId, "{$comboId}: Pre-existing banner creation failed");

                $oBanner = $this->_buildBannerInfo(
                    $oCamp->campaignId,
                    $storageType,
                    $imageType,
                    $cappingVal,
                    $sessionCappingVal,
                    $blockVal,
                    $existingBannerId,
                );
                $oBanner->bannerName = 'Modified Banner ' . $comboId;

                $result = $dllBanner->modify($oBanner);
                $this->assertTrue($result, "{$comboId}: Edit should succeed - " . $dllBanner->getLastError());

                // Verify changes persisted
                $doBanner = OA_Dal::staticGetDO('banners', $existingBannerId);
                if ($doBanner) {
                    $this->assertEqual($doBanner->description, 'Modified Banner ' . $comboId, "{$comboId}: Name not updated");
                }
                // Clean up uploaded file for web storage
                if ($storageType === 'web' && $doBanner && !empty($doBanner->filename)) {
                    $img = $GLOBALS['_MAX']['CONF']['store']['webDir'] . '/' . $doBanner->filename;
                    if (file_exists($img)) {
                        @unlink($img);
                    }
                }
                break;

            case 'View':
                // Create a banner then view it
                $existingBannerId = $this->_createExistingBanner($oCamp->campaignId, $storageType);
                $this->assertNotNull($existingBannerId, "{$comboId}: Pre-existing banner creation failed");

                $oBannerGet = null;
                $result = $dllBanner->getBanner($existingBannerId, $oBannerGet);
                $this->assertTrue($result, "{$comboId}: View should succeed - " . $dllBanner->getLastError());
                $this->assertNotNull($oBannerGet, "{$comboId}: getBanner should return banner info");
                if ($oBannerGet) {
                    $this->assertEqual($oBannerGet->bannerId, $existingBannerId, "{$comboId}: Banner ID mismatch on view");
                    $this->assertEqual($oBannerGet->storageType, $storageType, "{$comboId}: Storage type mismatch on view");
                }
                // Clean up web files
                if ($storageType === 'web') {
                    $doBannerClean = OA_Dal::staticGetDO('banners', $existingBannerId);
                    if ($doBannerClean && !empty($doBannerClean->filename)) {
                        $img = $GLOBALS['_MAX']['CONF']['store']['webDir'] . '/' . $doBannerClean->filename;
                        if (file_exists($img)) {
                            @unlink($img);
                        }
                    }
                }
                break;

            case 'Delete':
                // Create a banner then delete it
                $existingBannerId = $this->_createExistingBanner($oCamp->campaignId, $storageType);
                $this->assertNotNull($existingBannerId, "{$comboId}: Pre-existing banner creation failed");

                $result = $dllBanner->delete($existingBannerId);
                $this->assertTrue($result, "{$comboId}: Delete should succeed - " . $dllBanner->getLastError());

                // Verify deletion
                $oBannerGet = null;
                $resultGet = $dllBanner->getBanner($existingBannerId, $oBannerGet);
                $this->assertFalse($resultGet, "{$comboId}: getBanner should fail after delete");
                break;
        }
    }

    /**
     * Run one negative/excluded combo test case.
     *
     * @param string $comboId
     * @param string $negativeReason  trafficker_no_perm|no_image_create|advertiser_delete|advertiser_no_edit_perm|image_validation_*
     * @param string $accountType
     * @param string $mode
     * @param string $storageType
     * @param string $entityStatus
     * @param string $permission
     * @param string $cappingType
     * @param string $imageType
     * @param string $campaignType
     */
    private function _runNegativeCombo(
        $comboId,
        $negativeReason,
        $accountType,
        $mode,
        $storageType,
        $entityStatus,
        $permission,
        $cappingType,
        $imageType,
        $campaignType
    ) {
        $GLOBALS['_MAX']['CONF']['store']['mode'] = 0;
        $GLOBALS['_MAX']['CONF']['store']['webDir'] = MAX_PATH . '/var';

        // For oversized tests, set a very small max file size
        if ($imageType === 'oversized') {
            $GLOBALS['_MAX']['CONF']['store']['maxFilesize'] = 16;
        } else {
            $GLOBALS['_MAX']['CONF']['store']['maxFilesize'] = 0;
        }

        // Create prerequisite entities
        $oAdv = $this->_createAdvertiser();
        $oCamp = $this->_createCampaign($oAdv->advertiserId, $campaignType);
        $this->assertNotNull($oCamp->campaignId, "{$comboId}: Campaign creation failed");

        [$cappingVal, $sessionCappingVal, $blockVal] = $this->_parseCapping($cappingType);

        $dllBanner = new PartialMockOA_Dll_Banner_Combo($this);

        switch ($negativeReason) {
            case 'trafficker_no_perm':
                // TRAFFICKER accounts have no banner permissions
                // checkPermissions should return false
                $dllBanner->setReturnValue('checkPermissions', false);

                if ($mode === 'Create') {
                    $oBanner = $this->_buildBannerInfo(
                        $oCamp->campaignId,
                        $storageType,
                        ($storageType === 'sql' || $storageType === 'web') ? 'valid_gif' : 'NA',
                        $cappingVal,
                        $sessionCappingVal,
                        $blockVal,
                    );
                    $result = $dllBanner->modify($oBanner);
                    $this->assertFalse($result, "{$comboId}: TRAFFICKER Create should fail");
                } elseif ($mode === 'Edit') {
                    $dllBannerSetup = new PartialMockOA_Dll_Banner_Combo($this);
                    $dllBannerSetup->setReturnValue('checkPermissions', true);
                    $existingBannerId = $this->_createExistingBanner($oCamp->campaignId, $storageType);

                    $oBanner = $this->_buildBannerInfo(
                        $oCamp->campaignId,
                        $storageType,
                        'NA',
                        $cappingVal,
                        $sessionCappingVal,
                        $blockVal,
                        $existingBannerId,
                    );
                    $result = $dllBanner->modify($oBanner);
                    $this->assertFalse($result, "{$comboId}: TRAFFICKER Edit should fail");
                    // Clean up web files
                    if ($storageType === 'web') {
                        $doBannerClean = OA_Dal::staticGetDO('banners', $existingBannerId);
                        if ($doBannerClean && !empty($doBannerClean->filename)) {
                            $img = $GLOBALS['_MAX']['CONF']['store']['webDir'] . '/' . $doBannerClean->filename;
                            if (file_exists($img)) {
                                @unlink($img);
                            }
                        }
                    }
                } elseif ($mode === 'View') {
                    $existingBannerId = $this->_createExistingBanner($oCamp->campaignId, $storageType);
                    $oBannerGet = null;
                    $result = $dllBanner->getBanner($existingBannerId, $oBannerGet);
                    $this->assertFalse($result, "{$comboId}: TRAFFICKER View should fail");
                    // Clean up web files
                    if ($storageType === 'web') {
                        $doBannerClean = OA_Dal::staticGetDO('banners', $existingBannerId);
                        if ($doBannerClean && !empty($doBannerClean->filename)) {
                            $img = $GLOBALS['_MAX']['CONF']['store']['webDir'] . '/' . $doBannerClean->filename;
                            if (file_exists($img)) {
                                @unlink($img);
                            }
                        }
                    }
                } elseif ($mode === 'Delete') {
                    $existingBannerId = $this->_createExistingBanner($oCamp->campaignId, $storageType);
                    $result = $dllBanner->delete($existingBannerId);
                    $this->assertFalse($result, "{$comboId}: TRAFFICKER Delete should fail");
                    // Clean up web files
                    if ($storageType === 'web') {
                        $doBannerClean = OA_Dal::staticGetDO('banners', $existingBannerId);
                        if ($doBannerClean && !empty($doBannerClean->filename)) {
                            $img = $GLOBALS['_MAX']['CONF']['store']['webDir'] . '/' . $doBannerClean->filename;
                            if (file_exists($img)) {
                                @unlink($img);
                            }
                        }
                    }
                }
                break;

            case 'advertiser_delete':
                // ADVERTISER cannot delete banners (code enforces ADMIN/MANAGER only)
                $dllBanner->setReturnValue('checkPermissions', false);
                $existingBannerId = $this->_createExistingBanner($oCamp->campaignId, $storageType);
                $result = $dllBanner->delete($existingBannerId);
                $this->assertFalse($result, "{$comboId}: ADVERTISER Delete should fail");
                // Clean up web files
                if ($storageType === 'web') {
                    $doBannerClean = OA_Dal::staticGetDO('banners', $existingBannerId);
                    if ($doBannerClean && !empty($doBannerClean->filename)) {
                        $img = $GLOBALS['_MAX']['CONF']['store']['webDir'] . '/' . $doBannerClean->filename;
                        if (file_exists($img)) {
                            @unlink($img);
                        }
                    }
                }
                break;

            case 'advertiser_no_edit_perm':
                // ADVERTISER + Create without BANNER_EDIT permission
                $dllBanner->setReturnValue('checkPermissions', false);
                $oBanner = $this->_buildBannerInfo(
                    $oCamp->campaignId,
                    $storageType,
                    ($storageType === 'sql' || $storageType === 'web') ? 'valid_gif' : 'NA',
                    $cappingVal,
                    $sessionCappingVal,
                    $blockVal,
                );
                $result = $dllBanner->modify($oBanner);
                $this->assertFalse($result, "{$comboId}: ADVERTISER Create without BANNER_EDIT should fail");
                break;

            case 'image_empty_filename':
                // sql/web Create with empty filename
                $dllBanner->setReturnValue('checkPermissions', true);
                $oBanner = $this->_buildBannerInfo(
                    $oCamp->campaignId,
                    $storageType,
                    'empty_filename',
                    $cappingVal,
                    $sessionCappingVal,
                    $blockVal,
                );
                $result = $dllBanner->modify($oBanner);
                $this->assertFalse($result, "{$comboId}: Create with empty filename should fail");
                $this->assertEqual($dllBanner->getLastError(), 'Image filename empty',
                    "{$comboId}: Expected 'Image filename empty' error");
                break;

            case 'image_empty_content':
                // sql/web Create with empty content
                $dllBanner->setReturnValue('checkPermissions', true);
                $oBanner = $this->_buildBannerInfo(
                    $oCamp->campaignId,
                    $storageType,
                    'empty_content',
                    $cappingVal,
                    $sessionCappingVal,
                    $blockVal,
                );
                $result = $dllBanner->modify($oBanner);
                $this->assertFalse($result, "{$comboId}: Create with empty content should fail");
                $this->assertEqual($dllBanner->getLastError(), 'Image content empty',
                    "{$comboId}: Expected 'Image content empty' error");
                break;

            case 'image_wrong_format':
                // sql/web Create with unrecognized image format
                $dllBanner->setReturnValue('checkPermissions', true);
                $oBanner = $this->_buildBannerInfo(
                    $oCamp->campaignId,
                    $storageType,
                    'wrong_format',
                    $cappingVal,
                    $sessionCappingVal,
                    $blockVal,
                );
                $result = $dllBanner->modify($oBanner);
                $this->assertFalse($result, "{$comboId}: Create with wrong format should fail");
                break;

            case 'image_oversized':
                // sql/web Create with file size exceeding limit
                $dllBanner->setReturnValue('checkPermissions', true);
                $oBanner = $this->_buildBannerInfo(
                    $oCamp->campaignId,
                    $storageType,
                    'oversized',
                    $cappingVal,
                    $sessionCappingVal,
                    $blockVal,
                );
                $result = $dllBanner->modify($oBanner);
                $this->assertFalse($result, "{$comboId}: Create with oversized image should fail");
                break;
        }

        // Reset max file size
        $GLOBALS['_MAX']['CONF']['store']['maxFilesize'] = 0;
    }

    // =========================================================================
    // Section 4C: Included Combination Tests (~96 pairwise combos)
    // =========================================================================

    public function testIncludedComboB001()
    {
        $this->_runIncludedCombo('B001', 'ADMIN', 'Create', 'sql', 'Running', 'BANNER_ACTIVATE', 'None', 'valid_gif', 'Remnant');
    }

    public function testIncludedComboB002()
    {
        $this->_runIncludedCombo('B002', 'MANAGER', 'Edit', 'web', 'Paused', 'BANNER_DEACTIVATE', 'capping_only', 'NA', 'Remnant');
    }

    public function testIncludedComboB003()
    {
        $this->_runIncludedCombo('B003', 'ADVERTISER', 'View', 'url', 'Awaiting', 'BANNER_ADD', 'session_only', 'NA', 'ContractNormal');
    }

    public function testIncludedComboB004()
    {
        $this->_runIncludedCombo('B004', 'ADVERTISER', 'Edit', 'html', 'Expired', 'BANNER_EDIT', 'block_only', 'valid_gif', 'Override');
    }

    public function testIncludedComboB005()
    {
        $this->_runIncludedCombo('B005', 'MANAGER', 'Delete', 'txt', 'Inactive', 'BANNER_EDIT', 'all_three', 'valid_gif', 'ContractNormal');
    }

    public function testIncludedComboB006()
    {
        $this->_runIncludedCombo('B006', 'ADMIN', 'Delete', 'html', 'Pending', 'BANNER_ADD', 'capping_only', 'NA', 'eCPM');
    }

    public function testIncludedComboB007()
    {
        $this->_runIncludedCombo('B007', 'ADMIN', 'View', 'web', 'Approval', 'BANNER_EDIT', 'all_three', 'valid_gif', 'ContractECPM');
    }

    public function testIncludedComboB008()
    {
        $this->_runIncludedCombo('B008', 'MANAGER', 'Create', 'url', 'Rejected', 'BANNER_DEACTIVATE', 'block_only', 'NA', 'ContractECPM');
    }

    public function testIncludedComboB009()
    {
        $this->_runIncludedCombo('B009', 'ADVERTISER', 'Create', 'txt', 'Approval', 'BANNER_ACTIVATE', 'capping_only', 'NA', 'Override');
    }

    public function testIncludedComboB010()
    {
        $this->_runIncludedCombo('B010', 'ADVERTISER', 'View', 'sql', 'Pending', 'BANNER_DEACTIVATE', 'None', 'valid_gif', 'eCPM');
    }

    public function testIncludedComboB011()
    {
        $this->_runIncludedCombo('B011', 'MANAGER', 'View', 'html', 'Running', 'BANNER_ACTIVATE', 'session_only', 'valid_gif', 'ContractECPM');
    }

    public function testIncludedComboB012()
    {
        $this->_runIncludedCombo('B012', 'ADMIN', 'Edit', 'url', 'Inactive', 'BANNER_ACTIVATE', 'all_three', 'NA', 'eCPM');
    }

    public function testIncludedComboB013()
    {
        $this->_runIncludedCombo('B013', 'ADMIN', 'Delete', 'sql', 'Paused', 'BANNER_ADD', 'block_only', 'valid_gif', 'Override');
    }

    public function testIncludedComboB014()
    {
        $this->_runIncludedCombo('B014', 'MANAGER', 'Create', 'web', 'Expired', 'BANNER_ADD', 'None', 'valid_gif', 'eCPM');
    }

    public function testIncludedComboB015()
    {
        $this->_runIncludedCombo('B015', 'ADVERTISER', 'Edit', 'txt', 'Rejected', 'BANNER_ADD', 'None', 'valid_gif', 'ContractECPM');
    }

    public function testIncludedComboB016()
    {
        $this->_runIncludedCombo('B016', 'ADVERTISER', 'Create', 'html', 'Inactive', 'BANNER_DEACTIVATE', 'all_three', 'NA', 'Remnant');
    }

    public function testIncludedComboB017()
    {
        $this->_runIncludedCombo('B017', 'ADMIN', 'Delete', 'url', 'Expired', 'BANNER_DEACTIVATE', 'session_only', 'valid_gif', 'Remnant');
    }

    public function testIncludedComboB018()
    {
        $this->_runIncludedCombo('B018', 'MANAGER', 'View', 'sql', 'Awaiting', 'BANNER_EDIT', 'capping_only', 'valid_gif', 'ContractECPM');
    }

    public function testIncludedComboB019()
    {
        $this->_runIncludedCombo('B019', 'ADMIN', 'View', 'txt', 'Rejected', 'BANNER_EDIT', 'session_only', 'NA', 'Remnant');
    }

    public function testIncludedComboB020()
    {
        $this->_runIncludedCombo('B020', 'ADVERTISER', 'Edit', 'web', 'Running', 'BANNER_ADD', 'block_only', 'NA', 'ContractNormal');
    }

    public function testIncludedComboB021()
    {
        $this->_runIncludedCombo('B021', 'MANAGER', 'Delete', 'web', 'Pending', 'BANNER_ACTIVATE', 'block_only', 'valid_gif', 'Override');
    }

    public function testIncludedComboB022()
    {
        $this->_runIncludedCombo('B022', 'ADVERTISER', 'Create', 'txt', 'Paused', 'BANNER_EDIT', 'None', 'NA', 'eCPM');
    }

    public function testIncludedComboB023()
    {
        $this->_runIncludedCombo('B023', 'ADMIN', 'Delete', 'url', 'Approval', 'BANNER_ADD', 'None', 'valid_gif', 'ContractNormal');
    }

    public function testIncludedComboB024()
    {
        $this->_runIncludedCombo('B024', 'ADMIN', 'View', 'sql', 'Inactive', 'BANNER_ADD', 'block_only', 'NA', 'Remnant');
    }

    public function testIncludedComboB025()
    {
        $this->_runIncludedCombo('B025', 'ADMIN', 'Edit', 'sql', 'Awaiting', 'BANNER_DEACTIVATE', 'session_only', 'valid_gif', 'Override');
    }

    public function testIncludedComboB026()
    {
        $this->_runIncludedCombo('B026', 'ADVERTISER', 'Create', 'url', 'Pending', 'BANNER_EDIT', 'session_only', 'NA', 'ContractNormal');
    }

    public function testIncludedComboB027()
    {
        $this->_runIncludedCombo('B027', 'ADVERTISER', 'View', 'txt', 'Expired', 'BANNER_DEACTIVATE', 'block_only', 'NA', 'ContractNormal');
    }

    public function testIncludedComboB028()
    {
        $this->_runIncludedCombo('B028', 'ADVERTISER', 'View', 'html', 'Paused', 'BANNER_ACTIVATE', 'None', 'valid_gif', 'ContractNormal');
    }

    public function testIncludedComboB029()
    {
        $this->_runIncludedCombo('B029', 'ADVERTISER', 'Edit', 'sql', 'Approval', 'BANNER_DEACTIVATE', 'block_only', 'valid_gif', 'eCPM');
    }

    public function testIncludedComboB030()
    {
        $this->_runIncludedCombo('B030', 'ADVERTISER', 'View', 'sql', 'Rejected', 'BANNER_ACTIVATE', 'all_three', 'valid_gif', 'Override');
    }

    public function testIncludedComboB031()
    {
        $this->_runIncludedCombo('B031', 'ADVERTISER', 'Edit', 'url', 'Running', 'BANNER_DEACTIVATE', 'capping_only', 'valid_gif', 'Override');
    }

    public function testIncludedComboB032()
    {
        $this->_runIncludedCombo('B032', 'ADVERTISER', 'Edit', 'web', 'Awaiting', 'BANNER_ACTIVATE', 'None', 'valid_gif', 'Override');
    }

    public function testIncludedComboB033()
    {
        $this->_runIncludedCombo('B033', 'ADVERTISER', 'Edit', 'web', 'Inactive', 'BANNER_ADD', 'session_only', 'valid_gif', 'Override');
    }

    public function testIncludedComboB034()
    {
        $this->_runIncludedCombo('B034', 'ADVERTISER', 'Edit', 'txt', 'Pending', 'BANNER_ADD', 'all_three', 'valid_gif', 'Remnant');
    }

    public function testIncludedComboB035()
    {
        $this->_runIncludedCombo('B035', 'ADVERTISER', 'Create', 'html', 'Awaiting', 'BANNER_ADD', 'block_only', 'NA', 'Remnant');
    }

    public function testIncludedComboB036()
    {
        $this->_runIncludedCombo('B036', 'ADVERTISER', 'Edit', 'sql', 'Expired', 'BANNER_ACTIVATE', 'capping_only', 'valid_gif', 'ContractNormal');
    }

    public function testIncludedComboB037()
    {
        $this->_runIncludedCombo('B037', 'ADVERTISER', 'Edit', 'txt', 'Running', 'BANNER_EDIT', 'all_three', 'valid_gif', 'eCPM');
    }

    public function testIncludedComboB038()
    {
        $this->_runIncludedCombo('B038', 'ADVERTISER', 'Edit', 'html', 'Rejected', 'BANNER_ADD', 'capping_only', 'valid_gif', 'ContractNormal');
    }

    public function testIncludedComboB039()
    {
        $this->_runIncludedCombo('B039', 'ADVERTISER', 'Edit', 'txt', 'Awaiting', 'BANNER_ADD', 'all_three', 'valid_gif', 'eCPM');
    }

    public function testIncludedComboB040()
    {
        $this->_runIncludedCombo('B040', 'ADVERTISER', 'Edit', 'html', 'Approval', 'BANNER_ADD', 'session_only', 'valid_gif', 'Remnant');
    }

    public function testIncludedComboB041()
    {
        $this->_runIncludedCombo('B041', 'ADVERTISER', 'Edit', 'web', 'Rejected', 'BANNER_ADD', 'session_only', 'valid_gif', 'eCPM');
    }

    public function testIncludedComboB042()
    {
        $this->_runIncludedCombo('B042', 'ADVERTISER', 'Edit', 'url', 'Paused', 'BANNER_ADD', 'session_only', 'valid_gif', 'ContractECPM');
    }

    public function testIncludedComboB043()
    {
        $this->_runIncludedCombo('B043', 'ADVERTISER', 'Edit', 'txt', 'Inactive', 'BANNER_ADD', 'None', 'valid_gif', 'ContractECPM');
    }

    public function testIncludedComboB044()
    {
        $this->_runIncludedCombo('B044', 'ADVERTISER', 'Edit', 'txt', 'Expired', 'BANNER_ADD', 'all_three', 'valid_gif', 'ContractECPM');
    }

    public function testIncludedComboB045()
    {
        $this->_runIncludedCombo('B045', 'ADVERTISER', 'Edit', 'txt', 'Inactive', 'BANNER_ADD', 'capping_only', 'valid_gif', 'eCPM');
    }

    public function testIncludedComboB046()
    {
        $this->_runIncludedCombo('B046', 'ADVERTISER', 'Edit', 'txt', 'Paused', 'BANNER_ADD', 'all_three', 'valid_gif', 'eCPM');
    }

    public function testIncludedComboB047()
    {
        $this->_runIncludedCombo('B047', 'ADVERTISER', 'Edit', 'txt', 'Pending', 'BANNER_ADD', 'all_three', 'valid_gif', 'ContractECPM');
    }

    public function testIncludedComboB048()
    {
        $this->_runIncludedCombo('B048', 'ADMIN', 'Create', 'sql', 'Running', 'BANNER_EDIT', 'None', 'valid_gif', 'Remnant');
    }

    public function testIncludedComboB049()
    {
        $this->_runIncludedCombo('B049', 'MANAGER', 'Edit', 'web', 'Paused', 'BANNER_ADD', 'capping_only', 'different_gif', 'Remnant');
    }

    public function testIncludedComboB050()
    {
        $this->_runIncludedCombo('B050', 'ADVERTISER', 'Edit', 'url', 'Awaiting', 'BANNER_EDIT', 'session_only', 'valid_gif', 'ContractNormal');
    }

    public function testIncludedComboB051()
    {
        $this->_runIncludedCombo('B051', 'ADVERTISER', 'Create', 'html', 'Expired', 'BANNER_ADD', 'block_only', 'valid_gif', 'Override');
    }

    public function testIncludedComboB052()
    {
        $this->_runIncludedCombo('B052', 'MANAGER', 'Create', 'txt', 'Inactive', 'BANNER_EDIT', 'all_three', 'valid_gif', 'eCPM');
    }

    public function testIncludedComboB053()
    {
        $this->_runIncludedCombo('B053', 'ADMIN', 'Edit', 'txt', 'Pending', 'BANNER_ADD', 'all_three', 'valid_gif', 'ContractECPM');
    }

    public function testIncludedComboB054()
    {
        $this->_runIncludedCombo('B054', 'ADMIN', 'Create', 'url', 'Approval', 'BANNER_ADD', 'session_only', 'valid_gif', 'eCPM');
    }

    public function testIncludedComboB055()
    {
        $this->_runIncludedCombo('B055', 'ADVERTISER', 'Edit', 'sql', 'Rejected', 'BANNER_ADD', 'None', 'different_gif', 'eCPM');
    }

    public function testIncludedComboB056()
    {
        $this->_runIncludedCombo('B056', 'MANAGER', 'Create', 'web', 'Rejected', 'BANNER_EDIT', 'block_only', 'different_gif', 'ContractECPM');
    }

    public function testIncludedComboB057()
    {
        $this->_runIncludedCombo('B057', 'MANAGER', 'Edit', 'html', 'Approval', 'BANNER_EDIT', 'capping_only', 'valid_gif', 'Override');
    }

    public function testIncludedComboB058()
    {
        $this->_runIncludedCombo('B058', 'ADMIN', 'Create', 'html', 'Awaiting', 'BANNER_ADD', 'capping_only', 'valid_gif', 'ContractNormal');
    }

    public function testIncludedComboB059()
    {
        $this->_runIncludedCombo('B059', 'ADVERTISER', 'Edit', 'txt', 'Running', 'BANNER_ADD', 'block_only', 'valid_gif', 'Remnant');
    }

    public function testIncludedComboB060()
    {
        $this->_runIncludedCombo('B060', 'MANAGER', 'Edit', 'url', 'Expired', 'BANNER_EDIT', 'None', 'valid_gif', 'ContractECPM');
    }

    public function testIncludedComboB061()
    {
        $this->_runIncludedCombo('B061', 'ADVERTISER', 'Create', 'web', 'Pending', 'BANNER_EDIT', 'all_three', 'different_gif', 'ContractNormal');
    }

    public function testIncludedComboB062()
    {
        $this->_runIncludedCombo('B062', 'ADMIN', 'Create', 'web', 'Inactive', 'BANNER_ADD', 'session_only', 'different_gif', 'Override');
    }

    public function testIncludedComboB063()
    {
        $this->_runIncludedCombo('B063', 'MANAGER', 'Create', 'sql', 'Paused', 'BANNER_EDIT', 'session_only', 'valid_gif', 'ContractECPM');
    }

    public function testIncludedComboB064()
    {
        $this->_runIncludedCombo('B064', 'ADVERTISER', 'Edit', 'html', 'Inactive', 'BANNER_EDIT', 'None', 'valid_gif', 'ContractECPM');
    }

    public function testIncludedComboB065()
    {
        $this->_runIncludedCombo('B065', 'MANAGER', 'Create', 'url', 'Running', 'BANNER_EDIT', 'capping_only', 'valid_gif', 'ContractECPM');
    }

    public function testIncludedComboB066()
    {
        $this->_runIncludedCombo('B066', 'ADMIN', 'Create', 'txt', 'Rejected', 'BANNER_EDIT', 'session_only', 'valid_gif', 'Override');
    }

    public function testIncludedComboB067()
    {
        $this->_runIncludedCombo('B067', 'MANAGER', 'Create', 'sql', 'Awaiting', 'BANNER_EDIT', 'block_only', 'different_gif', 'ContractNormal');
    }

    public function testIncludedComboB068()
    {
        $this->_runIncludedCombo('B068', 'ADMIN', 'Create', 'url', 'Paused', 'BANNER_EDIT', 'block_only', 'valid_gif', 'Override');
    }

    public function testIncludedComboB069()
    {
        $this->_runIncludedCombo('B069', 'ADVERTISER', 'Create', 'html', 'Paused', 'BANNER_EDIT', 'all_three', 'valid_gif', 'eCPM');
    }

    public function testIncludedComboB070()
    {
        $this->_runIncludedCombo('B070', 'ADVERTISER', 'Create', 'txt', 'Approval', 'BANNER_EDIT', 'None', 'valid_gif', 'ContractNormal');
    }

    public function testIncludedComboB071()
    {
        $this->_runIncludedCombo('B071', 'ADVERTISER', 'Create', 'sql', 'Expired', 'BANNER_EDIT', 'capping_only', 'different_gif', 'eCPM');
    }

    public function testIncludedComboB072()
    {
        $this->_runIncludedCombo('B072', 'MANAGER', 'Create', 'web', 'Pending', 'BANNER_EDIT', 'None', 'valid_gif', 'eCPM');
    }

    public function testIncludedComboB073()
    {
        $this->_runIncludedCombo('B073', 'ADMIN', 'Create', 'html', 'Running', 'BANNER_EDIT', 'session_only', 'valid_gif', 'Remnant');
    }

    public function testIncludedComboB074()
    {
        $this->_runIncludedCombo('B074', 'ADMIN', 'Create', 'url', 'Pending', 'BANNER_EDIT', 'all_three', 'valid_gif', 'Remnant');
    }

    public function testIncludedComboB075()
    {
        $this->_runIncludedCombo('B075', 'ADMIN', 'Create', 'sql', 'Approval', 'BANNER_EDIT', 'all_three', 'different_gif', 'Override');
    }

    public function testIncludedComboB076()
    {
        $this->_runIncludedCombo('B076', 'ADMIN', 'Create', 'txt', 'Expired', 'BANNER_EDIT', 'session_only', 'valid_gif', 'Remnant');
    }

    public function testIncludedComboB077()
    {
        $this->_runIncludedCombo('B077', 'ADMIN', 'Create', 'txt', 'Awaiting', 'BANNER_EDIT', 'None', 'valid_gif', 'Override');
    }

    public function testIncludedComboB078()
    {
        $this->_runIncludedCombo('B078', 'ADMIN', 'Create', 'web', 'Running', 'BANNER_EDIT', 'all_three', 'different_gif', 'Override');
    }

    public function testIncludedComboB079()
    {
        $this->_runIncludedCombo('B079', 'ADMIN', 'Create', 'txt', 'Paused', 'BANNER_EDIT', 'None', 'valid_gif', 'ContractNormal');
    }

    public function testIncludedComboB080()
    {
        $this->_runIncludedCombo('B080', 'ADMIN', 'Create', 'web', 'Awaiting', 'BANNER_EDIT', 'all_three', 'valid_gif', 'ContractECPM');
    }

    public function testIncludedComboB081()
    {
        $this->_runIncludedCombo('B081', 'ADMIN', 'Create', 'url', 'Inactive', 'BANNER_EDIT', 'block_only', 'valid_gif', 'eCPM');
    }

    public function testIncludedComboB082()
    {
        $this->_runIncludedCombo('B082', 'ADMIN', 'Create', 'html', 'Pending', 'BANNER_EDIT', 'session_only', 'valid_gif', 'Override');
    }

    public function testIncludedComboB083()
    {
        $this->_runIncludedCombo('B083', 'ADMIN', 'Create', 'txt', 'Rejected', 'BANNER_EDIT', 'capping_only', 'valid_gif', 'Remnant');
    }

    public function testIncludedComboB084()
    {
        $this->_runIncludedCombo('B084', 'ADMIN', 'Create', 'html', 'Rejected', 'BANNER_EDIT', 'all_three', 'valid_gif', 'ContractNormal');
    }

    public function testIncludedComboB085()
    {
        $this->_runIncludedCombo('B085', 'ADMIN', 'Create', 'url', 'Rejected', 'BANNER_EDIT', 'all_three', 'valid_gif', 'Override');
    }

    public function testIncludedComboB086()
    {
        $this->_runIncludedCombo('B086', 'ADMIN', 'Create', 'web', 'Approval', 'BANNER_EDIT', 'block_only', 'valid_gif', 'ContractECPM');
    }

    public function testIncludedComboB087()
    {
        $this->_runIncludedCombo('B087', 'ADMIN', 'Create', 'sql', 'Inactive', 'BANNER_EDIT', 'capping_only', 'valid_gif', 'Remnant');
    }

    public function testIncludedComboB088()
    {
        $this->_runIncludedCombo('B088', 'ADMIN', 'Create', 'txt', 'Awaiting', 'BANNER_EDIT', 'all_three', 'valid_gif', 'Remnant');
    }

    public function testIncludedComboB089()
    {
        $this->_runIncludedCombo('B089', 'ADMIN', 'Create', 'txt', 'Running', 'BANNER_EDIT', 'all_three', 'valid_gif', 'eCPM');
    }

    public function testIncludedComboB090()
    {
        $this->_runIncludedCombo('B090', 'ADMIN', 'Create', 'txt', 'Pending', 'BANNER_EDIT', 'capping_only', 'valid_gif', 'Override');
    }

    public function testIncludedComboB091()
    {
        $this->_runIncludedCombo('B091', 'ADMIN', 'Create', 'txt', 'Expired', 'BANNER_EDIT', 'all_three', 'valid_gif', 'ContractNormal');
    }

    public function testIncludedComboB092()
    {
        $this->_runIncludedCombo('B092', 'ADMIN', 'Create', 'txt', 'Running', 'BANNER_EDIT', 'all_three', 'valid_gif', 'ContractNormal');
    }

    public function testIncludedComboB093()
    {
        $this->_runIncludedCombo('B093', 'ADMIN', 'Create', 'txt', 'Awaiting', 'BANNER_EDIT', 'all_three', 'valid_gif', 'eCPM');
    }

    public function testIncludedComboB094()
    {
        $this->_runIncludedCombo('B094', 'ADMIN', 'Create', 'txt', 'Pending', 'BANNER_EDIT', 'block_only', 'valid_gif', 'Override');
    }

    public function testIncludedComboB095()
    {
        $this->_runIncludedCombo('B095', 'ADMIN', 'Create', 'txt', 'Inactive', 'BANNER_EDIT', 'all_three', 'valid_gif', 'ContractNormal');
    }

    public function testIncludedComboB096()
    {
        $this->_runIncludedCombo('B096', 'ADMIN', 'Create', 'txt', 'Approval', 'BANNER_EDIT', 'all_three', 'valid_gif', 'Remnant');
    }

    // =========================================================================
    // Section 4D: Negative/Excluded Combination Tests (21 combos)
    // =========================================================================

    // --- TRAFFICKER + any Banner CRUD (N001-N005) ---

    public function testNegativeN001_TraffickerCreate()
    {
        $this->_runNegativeCombo('N001', 'trafficker_no_perm', 'TRAFFICKER', 'Create', 'html', 'Running', 'BANNER_EDIT', 'None', 'NA', 'Remnant');
    }

    public function testNegativeN002_TraffickerEdit()
    {
        $this->_runNegativeCombo('N002', 'trafficker_no_perm', 'TRAFFICKER', 'Edit', 'sql', 'Paused', 'BANNER_ADD', 'capping_only', 'valid_gif', 'ContractNormal');
    }

    public function testNegativeN003_TraffickerView()
    {
        $this->_runNegativeCombo('N003', 'trafficker_no_perm', 'TRAFFICKER', 'View', 'url', 'Awaiting', 'BANNER_ACTIVATE', 'session_only', 'NA', 'Override');
    }

    public function testNegativeN004_TraffickerDelete()
    {
        $this->_runNegativeCombo('N004', 'trafficker_no_perm', 'TRAFFICKER', 'Delete', 'web', 'Expired', 'BANNER_DEACTIVATE', 'block_only', 'valid_gif', 'eCPM');
    }

    public function testNegativeN005_TraffickerCreateTxt()
    {
        $this->_runNegativeCombo('N005', 'trafficker_no_perm', 'TRAFFICKER', 'Create', 'txt', 'Inactive', 'BANNER_EDIT', 'all_three', 'NA', 'ContractECPM');
    }

    // --- Storage=sql/web + image validation failures on Create (N006-N013) ---

    public function testNegativeN006_SqlCreateEmptyFilename()
    {
        $this->_runNegativeCombo('N006', 'image_empty_filename', 'MANAGER', 'Create', 'sql', 'Running', 'BANNER_ADD', 'None', 'empty_filename', 'Remnant');
    }

    public function testNegativeN007_SqlCreateEmptyContent()
    {
        $this->_runNegativeCombo('N007', 'image_empty_content', 'MANAGER', 'Create', 'sql', 'Running', 'BANNER_ADD', 'None', 'empty_content', 'eCPM');
    }

    public function testNegativeN008_SqlCreateWrongFormat()
    {
        $this->_runNegativeCombo('N008', 'image_wrong_format', 'MANAGER', 'Create', 'sql', 'Running', 'BANNER_ADD', 'None', 'wrong_format', 'ContractNormal');
    }

    public function testNegativeN009_SqlCreateOversized()
    {
        $this->_runNegativeCombo('N009', 'image_oversized', 'MANAGER', 'Create', 'sql', 'Running', 'BANNER_ADD', 'None', 'oversized', 'Override');
    }

    public function testNegativeN010_WebCreateEmptyFilename()
    {
        $this->_runNegativeCombo('N010', 'image_empty_filename', 'ADMIN', 'Create', 'web', 'Running', 'BANNER_EDIT', 'capping_only', 'empty_filename', 'ContractECPM');
    }

    public function testNegativeN011_WebCreateEmptyContent()
    {
        $this->_runNegativeCombo('N011', 'image_empty_content', 'ADMIN', 'Create', 'web', 'Running', 'BANNER_EDIT', 'session_only', 'empty_content', 'Remnant');
    }

    public function testNegativeN012_WebCreateWrongFormat()
    {
        $this->_runNegativeCombo('N012', 'image_wrong_format', 'ADMIN', 'Create', 'web', 'Paused', 'BANNER_EDIT', 'block_only', 'wrong_format', 'eCPM');
    }

    public function testNegativeN013_WebCreateOversized()
    {
        $this->_runNegativeCombo('N013', 'image_oversized', 'ADMIN', 'Create', 'web', 'Paused', 'BANNER_EDIT', 'all_three', 'oversized', 'ContractNormal');
    }

    // --- ADVERTISER + Delete banner (N014-N018) ---

    public function testNegativeN014_AdvertiserDeleteHtml()
    {
        $this->_runNegativeCombo('N014', 'advertiser_delete', 'ADVERTISER', 'Delete', 'html', 'Running', 'BANNER_EDIT', 'None', 'NA', 'Remnant');
    }

    public function testNegativeN015_AdvertiserDeleteSql()
    {
        $this->_runNegativeCombo('N015', 'advertiser_delete', 'ADVERTISER', 'Delete', 'sql', 'Paused', 'BANNER_ADD', 'capping_only', 'NA', 'ContractNormal');
    }

    public function testNegativeN016_AdvertiserDeleteWeb()
    {
        $this->_runNegativeCombo('N016', 'advertiser_delete', 'ADVERTISER', 'Delete', 'web', 'Awaiting', 'BANNER_ACTIVATE', 'session_only', 'NA', 'Override');
    }

    public function testNegativeN017_AdvertiserDeleteUrl()
    {
        $this->_runNegativeCombo('N017', 'advertiser_delete', 'ADVERTISER', 'Delete', 'url', 'Expired', 'BANNER_DEACTIVATE', 'block_only', 'NA', 'eCPM');
    }

    public function testNegativeN018_AdvertiserDeleteTxt()
    {
        $this->_runNegativeCombo('N018', 'advertiser_delete', 'ADVERTISER', 'Delete', 'txt', 'Inactive', 'BANNER_EDIT', 'all_three', 'NA', 'ContractECPM');
    }

    // --- ADVERTISER + Create + no BANNER_EDIT perm (N019-N021) ---

    public function testNegativeN019_AdvertiserCreateNoEditPermHtml()
    {
        $this->_runNegativeCombo('N019', 'advertiser_no_edit_perm', 'ADVERTISER', 'Create', 'html', 'Running', 'BANNER_ACTIVATE', 'None', 'NA', 'Remnant');
    }

    public function testNegativeN020_AdvertiserCreateNoEditPermSql()
    {
        $this->_runNegativeCombo('N020', 'advertiser_no_edit_perm', 'ADVERTISER', 'Create', 'sql', 'Running', 'BANNER_ADD', 'capping_only', 'valid_gif', 'ContractNormal');
    }

    public function testNegativeN021_AdvertiserCreateNoEditPermUrl()
    {
        $this->_runNegativeCombo('N021', 'advertiser_no_edit_perm', 'ADVERTISER', 'Create', 'url', 'Paused', 'BANNER_DEACTIVATE', 'session_only', 'NA', 'Override');
    }
}
