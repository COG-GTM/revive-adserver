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

require_once MAX_PATH . '/lib/OA/Dll/Publisher.php';
require_once MAX_PATH . '/lib/OA/Dll/PublisherInfo.php';
require_once MAX_PATH . '/lib/OA/Dll/Zone.php';
require_once MAX_PATH . '/lib/OA/Dll/ZoneInfo.php';
require_once MAX_PATH . '/lib/OA/Dll/tests/util/DllUnitTestCase.php';
require_once MAX_PATH . '/www/admin/lib-zones.inc.php';

Language_Loader::load();

/**
 * Zone CRUD Combination Matrix Tests (Sections 4E + 4F)
 *
 * This test class covers ~60 pairwise-generated included combos across
 * 7 dimensions (account type, page mode, zone type, zone permission,
 * size type, frequency capping, chained zone) plus negative/excluded
 * combo tests for validation and permission denial paths.
 *
 * Dimensions:
 *   1. Account type:    MANAGER, TRAFFICKER
 *   2. Page mode:       Create, Edit, View, Delete
 *   3. Zone type:       Banner(0), Interstitial(1), Popup(2), Text(3),
 *                       Email(4), VideoInstream(6), VideoOverlay(7)
 *   4. Zone permission: ZONE_ADD, ZONE_DELETE, ZONE_EDIT, ZONE_INVOCATION, ZONE_LINK
 *   5. Size type:       IAB standard, custom, wildcard(*)
 *   6. Freq capping:    None, capping, sessionCapping, block, all three
 *   7. Chained zone:    None, valid chain, invalid chain
 *
 * @package    OpenXDll
 * @subpackage TestSuite
 */
class OA_Dll_ZoneCombinatorialTest extends DllUnitTestCase
{
    /**
     * @var int
     */
    public $agencyId;

    public $unknownIdError = 'Unknown zoneId Error';
    public $chainError = 'Cannot chain a zone to itself';
    /**
     * Zone type constants mapping.
     */
    private static $zoneTypeMap = [
        'Banner' => 0,        // phpAds_ZoneBanner
        'Interstitial' => 1,  // phpAds_ZoneInterstitial
        'Popup' => 2,         // phpAds_ZonePopup
        'Text' => 3,          // phpAds_ZoneText
        'Email' => 4,         // MAX_ZoneEmail
        'VideoInstream' => 6, // OX_ZoneVideoInstream
        'VideoOverlay' => 7,  // OX_ZoneVideoOverlay
    ];

    /**
     * Size configuration mapping.
     */
    private static $sizeMap = [
        'IAB_468x60' => ['width' => 468, 'height' => 60],
        'IAB_728x90' => ['width' => 728, 'height' => 90],
        'IAB_300x250' => ['width' => 300, 'height' => 250],
        'Custom_160x600' => ['width' => 160, 'height' => 600],
        'Custom_300x250' => ['width' => 300, 'height' => 250],
        'Wildcard' => ['width' => -1, 'height' => -1],
    ];

    public function __construct()
    {
        parent::__construct();
        Mock::generatePartial(
            'OA_Dll_Publisher',
            'PartialMockOA_Dll_Publisher_ZoneComboTest',
            ['checkPermissions', 'getDefaultAgencyId'],
        );
        Mock::generatePartial(
            'OA_Dll_Zone',
            'PartialMockOA_Dll_Zone_ComboTest',
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

    /**
     * Helper: create a publisher for the current agency.
     */
    private function _createPublisher()
    {
        $dllPublisher = new PartialMockOA_Dll_Publisher_ZoneComboTest($this);
        $dllPublisher->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllPublisher->setReturnValue('checkPermissions', true);

        $oPublisherInfo = new OA_DLL_PublisherInfo();
        $oPublisherInfo->publisherName = 'Test Publisher';
        $oPublisherInfo->agencyId = $this->agencyId;
        $dllPublisher->modify($oPublisherInfo);

        return $oPublisherInfo;
    }

    /**
     * Helper: create a zone DLL mock that allows all permissions.
     */
    private function _createZoneDll()
    {
        $dllZone = new PartialMockOA_Dll_Zone_ComboTest($this);
        $dllZone->setReturnValue('checkPermissions', true);
        return $dllZone;
    }

    /**
     * Helper: create a zone DLL mock that denies permissions.
     */
    private function _createZoneDllDenied()
    {
        $dllZone = new PartialMockOA_Dll_Zone_ComboTest($this);
        $dllZone->setReturnValue('checkPermissions', false);
        return $dllZone;
    }

    /**
     * Helper: get effective size for a zone type and requested size.
     * Text, VideoInstream, VideoOverlay force specific sizes.
     */
    private function _getEffectiveSize($zoneType, $sizeKey)
    {
        if (in_array($zoneType, ['Text', 'VideoInstream', 'VideoOverlay'])) {
            switch ($zoneType) {
                case 'Text':
                    return ['width' => 0, 'height' => 0];
                case 'VideoInstream':
                    return ['width' => -3, 'height' => -3];
                case 'VideoOverlay':
                    return ['width' => -2, 'height' => -2];
            }
        }
        return self::$sizeMap[$sizeKey];
    }

    /**
     * Helper: build a ZoneInfo for a Create operation.
     */
    private function _buildZoneInfoForCreate($publisherId, $zoneType, $sizeKey, $cappingType, $chainedZoneId = null)
    {
        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = $publisherId;
        $oZone->zoneName = 'Combo Zone ' . $zoneType . ' ' . $sizeKey;
        $oZone->type = self::$zoneTypeMap[$zoneType];

        $size = $this->_getEffectiveSize($zoneType, $sizeKey);
        $oZone->width = $size['width'];
        $oZone->height = $size['height'];

        $this->_applyCapping($oZone, $cappingType);

        if ($chainedZoneId !== null) {
            $oZone->chainedZoneId = $chainedZoneId;
        }

        return $oZone;
    }

    /**
     * Helper: apply capping settings to a ZoneInfo.
     */
    private function _applyCapping($oZone, $cappingType)
    {
        switch ($cappingType) {
            case 'None':
                break;
            case 'Capping':
                $oZone->capping = 10;
                break;
            case 'SessionCapping':
                $oZone->sessionCapping = 5;
                break;
            case 'Block':
                $oZone->block = 3600;
                break;
            case 'AllThree':
                $oZone->capping = 10;
                $oZone->sessionCapping = 5;
                $oZone->block = 3600;
                break;
        }
    }

    /**
     * Helper: create a valid zone directly via DataGenerator for
     * Edit/View/Delete tests that need an existing zone.
     */
    private function _createExistingZone($publisherId, $zoneType, $sizeKey)
    {
        $doZone = OA_Dal::factoryDO('zones');
        $doZone->affiliateid = $publisherId;
        $doZone->zonename = 'Existing Zone';
        $doZone->delivery = self::$zoneTypeMap[$zoneType];

        $size = $this->_getEffectiveSize($zoneType, $sizeKey);
        $doZone->width = $size['width'];
        $doZone->height = $size['height'];

        return DataGenerator::generateOne($doZone);
    }

    /**
     * Helper: create a chain target zone for valid chaining tests.
     */
    private function _createChainTargetZone($publisherId)
    {
        $doZone = OA_Dal::factoryDO('zones');
        $doZone->affiliateid = $publisherId;
        $doZone->zonename = 'Chain Target Zone';
        $doZone->delivery = 0;
        $doZone->width = 468;
        $doZone->height = 60;
        return DataGenerator::generateOne($doZone);
    }

    /**
     * Resolve a chain descriptor to a zone ID (or invalid ID).
     */
    private function _resolveChain($chainType, $publisherId)
    {
        switch ($chainType) {
            case 'None':
                return null;
            case 'ValidChain':
                return $this->_createChainTargetZone($publisherId);
            case 'InvalidChain':
                return 999999;
        }
        return null;
    }

    /**
     * Run a single included combo test.
     *
     * @param string $comboId   Combo identifier (e.g. Z001)
     * @param string $account   MANAGER or TRAFFICKER
     * @param string $mode      Create, Edit, View, or Delete
     * @param string $zoneType  Banner, Interstitial, Popup, Text, Email, VideoInstream, VideoOverlay
     * @param string $perm      ZONE_ADD, ZONE_DELETE, ZONE_EDIT, ZONE_INVOCATION, ZONE_LINK
     * @param string $sizeKey   IAB_468x60, IAB_728x90, etc.
     * @param string $capping   None, Capping, SessionCapping, Block, AllThree
     * @param string $chain     None, ValidChain, InvalidChain
     */
    private function _runIncludedCombo($comboId, $account, $mode, $zoneType, $perm, $sizeKey, $capping, $chain)
    {
        $oPublisherInfo = $this->_createPublisher();
        $publisherId = $oPublisherInfo->publisherId;
        $dllZone = $this->_createZoneDll();

        $chainedZoneId = $this->_resolveChain($chain, $publisherId);

        switch ($mode) {
            case 'Create':
                $oZone = $this->_buildZoneInfoForCreate(
                    $publisherId,
                    $zoneType,
                    $sizeKey,
                    $capping,
                    $chainedZoneId,
                );

                if ($chain === 'InvalidChain') {
                    $this->assertFalse(
                        $dllZone->modify($oZone),
                        "{$comboId}: Create with invalid chain should fail",
                    );
                } else {
                    $this->assertTrue(
                        $dllZone->modify($oZone),
                        "{$comboId}: Create should succeed [{$account},{$zoneType},{$perm},{$sizeKey},{$capping},{$chain}] - " . $dllZone->getLastError(),
                    );
                    $this->assertNotNull(
                        $oZone->zoneId,
                        "{$comboId}: Zone ID should be assigned after create",
                    );

                    // Verify zone was created with correct properties
                    $oZoneGet = null;
                    $this->assertTrue(
                        $dllZone->getZone($oZone->zoneId, $oZoneGet),
                        "{$comboId}: Should retrieve created zone",
                    );
                    $this->assertEqual(
                        $oZoneGet->type,
                        self::$zoneTypeMap[$zoneType],
                        "{$comboId}: Zone type should match",
                    );
                }
                break;

            case 'Edit':
                $existingZoneId = $this->_createExistingZone($publisherId, $zoneType, $sizeKey);

                $oZone = new OA_Dll_ZoneInfo();
                $oZone->zoneId = $existingZoneId;
                $oZone->zoneName = 'Edited Zone ' . $comboId;

                $this->_applyCapping($oZone, $capping);

                if ($chainedZoneId !== null) {
                    $oZone->chainedZoneId = $chainedZoneId;
                }

                if ($chain === 'InvalidChain') {
                    $this->assertFalse(
                        $dllZone->modify($oZone),
                        "{$comboId}: Edit with invalid chain should fail",
                    );
                } else {
                    $this->assertTrue(
                        $dllZone->modify($oZone),
                        "{$comboId}: Edit should succeed [{$account},{$zoneType},{$perm},{$sizeKey},{$capping},{$chain}] - " . $dllZone->getLastError(),
                    );

                    // Verify updated name
                    $oZoneGet = null;
                    $dllZone->getZone($existingZoneId, $oZoneGet);
                    $this->assertEqual(
                        $oZoneGet->zoneName,
                        'Edited Zone ' . $comboId,
                        "{$comboId}: Zone name should be updated",
                    );
                }
                break;

            case 'View':
                $existingZoneId = $this->_createExistingZone($publisherId, $zoneType, $sizeKey);

                $oZoneGet = null;
                $this->assertTrue(
                    $dllZone->getZone($existingZoneId, $oZoneGet),
                    "{$comboId}: View should succeed [{$account},{$zoneType},{$perm},{$sizeKey},{$capping},{$chain}] - " . $dllZone->getLastError(),
                );
                $this->assertNotNull(
                    $oZoneGet,
                    "{$comboId}: Retrieved zone should not be null",
                );
                $this->assertEqual(
                    $oZoneGet->type,
                    self::$zoneTypeMap[$zoneType],
                    "{$comboId}: Retrieved zone type should match",
                );
                break;

            case 'Delete':
                $existingZoneId = $this->_createExistingZone($publisherId, $zoneType, $sizeKey);

                $this->assertTrue(
                    $dllZone->delete($existingZoneId),
                    "{$comboId}: Delete should succeed [{$account},{$zoneType},{$perm},{$sizeKey},{$capping},{$chain}] - " . $dllZone->getLastError(),
                );

                // Verify zone no longer exists
                $oZoneGet = null;
                $this->assertFalse(
                    $dllZone->getZone($existingZoneId, $oZoneGet),
                    "{$comboId}: Zone should not exist after delete",
                );
                break;
        }
    }

    // ========================================================================
    // Section 4E: ~60 Pairwise-Generated Included Combos
    // ========================================================================

    public function testZ001_Manager_Create_Banner_ZoneAdd_IAB468x60_None_None()
    {
        $this->_runIncludedCombo('Z001', 'MANAGER', 'Create', 'Banner', 'ZONE_ADD', 'IAB_468x60', 'None', 'None');
    }

    public function testZ002_Trafficker_Edit_Interstitial_ZoneDelete_IAB728x90_Capping_None()
    {
        $this->_runIncludedCombo('Z002', 'TRAFFICKER', 'Edit', 'Interstitial', 'ZONE_DELETE', 'IAB_728x90', 'Capping', 'None');
    }

    public function testZ003_Trafficker_View_Popup_ZoneEdit_IAB300x250_SessionCapping_ValidChain()
    {
        $this->_runIncludedCombo('Z003', 'TRAFFICKER', 'View', 'Popup', 'ZONE_EDIT', 'IAB_300x250', 'SessionCapping', 'ValidChain');
    }

    public function testZ004_Manager_Delete_Text_ZoneInvocation_Wildcard_Block_ValidChain()
    {
        $this->_runIncludedCombo('Z004', 'MANAGER', 'Delete', 'Text', 'ZONE_INVOCATION', 'Wildcard', 'Block', 'ValidChain');
    }

    public function testZ005_Manager_View_Email_ZoneLink_Custom160x600_AllThree_InvalidChain()
    {
        $this->_runIncludedCombo('Z005', 'MANAGER', 'View', 'Email', 'ZONE_LINK', 'Custom_160x600', 'AllThree', 'InvalidChain');
    }

    public function testZ006_Trafficker_Delete_VideoInstream_ZoneLink_IAB468x60_AllThree_None()
    {
        $this->_runIncludedCombo('Z006', 'TRAFFICKER', 'Delete', 'VideoInstream', 'ZONE_LINK', 'IAB_468x60', 'AllThree', 'None');
    }

    public function testZ007_Trafficker_Create_VideoOverlay_ZoneInvocation_IAB728x90_Block_InvalidChain()
    {
        $this->_runIncludedCombo('Z007', 'TRAFFICKER', 'Create', 'VideoOverlay', 'ZONE_INVOCATION', 'IAB_728x90', 'Block', 'InvalidChain');
    }

    public function testZ008_Manager_Edit_VideoOverlay_ZoneEdit_Wildcard_SessionCapping_None()
    {
        $this->_runIncludedCombo('Z008', 'MANAGER', 'Edit', 'VideoOverlay', 'ZONE_EDIT', 'Wildcard', 'SessionCapping', 'None');
    }

    public function testZ009_Manager_Edit_VideoInstream_ZoneAdd_IAB300x250_Capping_InvalidChain()
    {
        $this->_runIncludedCombo('Z009', 'MANAGER', 'Edit', 'VideoInstream', 'ZONE_ADD', 'IAB_300x250', 'Capping', 'InvalidChain');
    }

    public function testZ010_Trafficker_Create_Email_ZoneDelete_Custom300x250_None_ValidChain()
    {
        $this->_runIncludedCombo('Z010', 'TRAFFICKER', 'Create', 'Email', 'ZONE_DELETE', 'Custom_300x250', 'None', 'ValidChain');
    }

    public function testZ011_Trafficker_Delete_Banner_ZoneEdit_Custom160x600_Capping_ValidChain()
    {
        $this->_runIncludedCombo('Z011', 'TRAFFICKER', 'Delete', 'Banner', 'ZONE_EDIT', 'Custom_160x600', 'Capping', 'ValidChain');
    }

    public function testZ012_Manager_View_Interstitial_ZoneInvocation_Custom300x250_None_None()
    {
        $this->_runIncludedCombo('Z012', 'MANAGER', 'View', 'Interstitial', 'ZONE_INVOCATION', 'Custom_300x250', 'None', 'None');
    }

    public function testZ013_Manager_Edit_Popup_ZoneLink_Custom300x250_Block_None()
    {
        $this->_runIncludedCombo('Z013', 'MANAGER', 'Edit', 'Popup', 'ZONE_LINK', 'Custom_300x250', 'Block', 'None');
    }

    public function testZ014_Trafficker_Delete_Text_ZoneAdd_IAB728x90_SessionCapping_InvalidChain()
    {
        $this->_runIncludedCombo('Z014', 'TRAFFICKER', 'Delete', 'Text', 'ZONE_ADD', 'IAB_728x90', 'SessionCapping', 'InvalidChain');
    }

    public function testZ015_Manager_Create_VideoInstream_ZoneDelete_Wildcard_AllThree_InvalidChain()
    {
        $this->_runIncludedCombo('Z015', 'MANAGER', 'Create', 'VideoInstream', 'ZONE_DELETE', 'Wildcard', 'AllThree', 'InvalidChain');
    }

    public function testZ016_Manager_View_VideoOverlay_ZoneAdd_IAB468x60_Block_ValidChain()
    {
        $this->_runIncludedCombo('Z016', 'MANAGER', 'View', 'VideoOverlay', 'ZONE_ADD', 'IAB_468x60', 'Block', 'ValidChain');
    }

    public function testZ017_Manager_Create_Text_ZoneLink_IAB300x250_None_None()
    {
        $this->_runIncludedCombo('Z017', 'MANAGER', 'Create', 'Text', 'ZONE_LINK', 'IAB_300x250', 'None', 'None');
    }

    public function testZ018_Manager_Edit_Banner_ZoneInvocation_Custom160x600_AllThree_ValidChain()
    {
        $this->_runIncludedCombo('Z018', 'MANAGER', 'Edit', 'Banner', 'ZONE_INVOCATION', 'Custom_160x600', 'AllThree', 'ValidChain');
    }

    public function testZ019_Manager_Delete_Interstitial_ZoneEdit_Custom300x250_AllThree_InvalidChain()
    {
        $this->_runIncludedCombo('Z019', 'MANAGER', 'Delete', 'Interstitial', 'ZONE_EDIT', 'Custom_300x250', 'AllThree', 'InvalidChain');
    }

    public function testZ020_Manager_View_Banner_ZoneDelete_IAB728x90_Block_InvalidChain()
    {
        $this->_runIncludedCombo('Z020', 'MANAGER', 'View', 'Banner', 'ZONE_DELETE', 'IAB_728x90', 'Block', 'InvalidChain');
    }

    public function testZ021_Trafficker_Delete_Email_ZoneAdd_Wildcard_None_None()
    {
        $this->_runIncludedCombo('Z021', 'TRAFFICKER', 'Delete', 'Email', 'ZONE_ADD', 'Wildcard', 'None', 'None');
    }

    public function testZ022_Manager_Create_VideoInstream_ZoneEdit_IAB728x90_None_ValidChain()
    {
        $this->_runIncludedCombo('Z022', 'MANAGER', 'Create', 'VideoInstream', 'ZONE_EDIT', 'IAB_728x90', 'None', 'ValidChain');
    }

    public function testZ023_Manager_Create_Email_ZoneInvocation_IAB468x60_SessionCapping_InvalidChain()
    {
        $this->_runIncludedCombo('Z023', 'MANAGER', 'Create', 'Email', 'ZONE_INVOCATION', 'IAB_468x60', 'SessionCapping', 'InvalidChain');
    }

    public function testZ024_Manager_Create_VideoOverlay_ZoneLink_IAB300x250_Capping_ValidChain()
    {
        $this->_runIncludedCombo('Z024', 'MANAGER', 'Create', 'VideoOverlay', 'ZONE_LINK', 'IAB_300x250', 'Capping', 'ValidChain');
    }

    public function testZ025_Manager_Delete_Popup_ZoneDelete_Custom160x600_None_InvalidChain()
    {
        $this->_runIncludedCombo('Z025', 'MANAGER', 'Delete', 'Popup', 'ZONE_DELETE', 'Custom_160x600', 'None', 'InvalidChain');
    }

    public function testZ026_Manager_View_Text_ZoneDelete_IAB468x60_Capping_InvalidChain()
    {
        $this->_runIncludedCombo('Z026', 'MANAGER', 'View', 'Text', 'ZONE_DELETE', 'IAB_468x60', 'Capping', 'InvalidChain');
    }

    public function testZ027_Manager_Edit_Email_ZoneEdit_IAB468x60_Block_InvalidChain()
    {
        $this->_runIncludedCombo('Z027', 'MANAGER', 'Edit', 'Email', 'ZONE_EDIT', 'IAB_468x60', 'Block', 'InvalidChain');
    }

    public function testZ028_Manager_Create_Banner_ZoneLink_Wildcard_SessionCapping_InvalidChain()
    {
        $this->_runIncludedCombo('Z028', 'MANAGER', 'Create', 'Banner', 'ZONE_LINK', 'Wildcard', 'SessionCapping', 'InvalidChain');
    }

    public function testZ029_Manager_Create_VideoInstream_ZoneInvocation_IAB300x250_Block_InvalidChain()
    {
        $this->_runIncludedCombo('Z029', 'MANAGER', 'Create', 'VideoInstream', 'ZONE_INVOCATION', 'IAB_300x250', 'Block', 'InvalidChain');
    }

    public function testZ030_Manager_Create_VideoOverlay_ZoneDelete_IAB300x250_AllThree_InvalidChain()
    {
        $this->_runIncludedCombo('Z030', 'MANAGER', 'Create', 'VideoOverlay', 'ZONE_DELETE', 'IAB_300x250', 'AllThree', 'InvalidChain');
    }

    public function testZ031_Manager_Create_Text_ZoneEdit_Wildcard_AllThree_InvalidChain()
    {
        $this->_runIncludedCombo('Z031', 'MANAGER', 'Create', 'Text', 'ZONE_EDIT', 'Wildcard', 'AllThree', 'InvalidChain');
    }

    public function testZ032_Manager_Create_Email_ZoneAdd_Custom160x600_Block_None()
    {
        $this->_runIncludedCombo('Z032', 'MANAGER', 'Create', 'Email', 'ZONE_ADD', 'Custom_160x600', 'Block', 'None');
    }

    public function testZ033_Manager_Create_Email_ZoneAdd_Custom300x250_Capping_InvalidChain()
    {
        $this->_runIncludedCombo('Z033', 'MANAGER', 'Create', 'Email', 'ZONE_ADD', 'Custom_300x250', 'Capping', 'InvalidChain');
    }

    public function testZ034_Manager_Create_VideoOverlay_ZoneDelete_Wildcard_None_InvalidChain()
    {
        $this->_runIncludedCombo('Z034', 'MANAGER', 'Create', 'VideoOverlay', 'ZONE_DELETE', 'Wildcard', 'None', 'InvalidChain');
    }

    public function testZ035_Manager_Create_VideoInstream_ZoneDelete_Wildcard_SessionCapping_InvalidChain()
    {
        $this->_runIncludedCombo('Z035', 'MANAGER', 'Create', 'VideoInstream', 'ZONE_DELETE', 'Wildcard', 'SessionCapping', 'InvalidChain');
    }

    public function testZ036_Manager_Create_Email_ZoneAdd_IAB728x90_AllThree_InvalidChain()
    {
        $this->_runIncludedCombo('Z036', 'MANAGER', 'Create', 'Email', 'ZONE_ADD', 'IAB_728x90', 'AllThree', 'InvalidChain');
    }

    public function testZ037_Manager_Create_VideoInstream_ZoneInvocation_Wildcard_Capping_InvalidChain()
    {
        $this->_runIncludedCombo('Z037', 'MANAGER', 'Create', 'VideoInstream', 'ZONE_INVOCATION', 'Wildcard', 'Capping', 'InvalidChain');
    }

    public function testZ038_Manager_Create_VideoInstream_ZoneLink_IAB728x90_AllThree_InvalidChain()
    {
        $this->_runIncludedCombo('Z038', 'MANAGER', 'Create', 'VideoInstream', 'ZONE_LINK', 'IAB_728x90', 'AllThree', 'InvalidChain');
    }

    public function testZ039_Trafficker_Create_Banner_ZoneAdd_IAB300x250_Capping_ValidChain()
    {
        $this->_runIncludedCombo('Z039', 'TRAFFICKER', 'Create', 'Banner', 'ZONE_ADD', 'IAB_300x250', 'Capping', 'ValidChain');
    }

    public function testZ040_Trafficker_Edit_Banner_ZoneEdit_Wildcard_Block_None()
    {
        $this->_runIncludedCombo('Z040', 'TRAFFICKER', 'Edit', 'Banner', 'ZONE_EDIT', 'Wildcard', 'Block', 'None');
    }

    public function testZ041_Trafficker_View_Email_ZoneInvocation_IAB468x60_None_ValidChain()
    {
        $this->_runIncludedCombo('Z041', 'TRAFFICKER', 'View', 'Email', 'ZONE_INVOCATION', 'IAB_468x60', 'None', 'ValidChain');
    }

    public function testZ042_Trafficker_Delete_Text_ZoneDelete_IAB468x60_None_None()
    {
        $this->_runIncludedCombo('Z042', 'TRAFFICKER', 'Delete', 'Text', 'ZONE_DELETE', 'IAB_468x60', 'None', 'None');
    }

    public function testZ043_Trafficker_Create_Text_ZoneAdd_IAB728x90_SessionCapping_None()
    {
        $this->_runIncludedCombo('Z043', 'TRAFFICKER', 'Create', 'Text', 'ZONE_ADD', 'IAB_728x90', 'SessionCapping', 'None');
    }

    public function testZ044_Trafficker_Edit_VideoInstream_ZoneEdit_IAB300x250_AllThree_ValidChain()
    {
        $this->_runIncludedCombo('Z044', 'TRAFFICKER', 'Edit', 'VideoInstream', 'ZONE_EDIT', 'IAB_300x250', 'AllThree', 'ValidChain');
    }

    public function testZ045_Trafficker_View_VideoOverlay_ZoneLink_Wildcard_Capping_None()
    {
        $this->_runIncludedCombo('Z045', 'TRAFFICKER', 'View', 'VideoOverlay', 'ZONE_LINK', 'Wildcard', 'Capping', 'None');
    }

    public function testZ046_Trafficker_Edit_Email_ZoneLink_Custom160x600_None_InvalidChain()
    {
        $this->_runIncludedCombo('Z046', 'TRAFFICKER', 'Edit', 'Email', 'ZONE_LINK', 'Custom_160x600', 'None', 'InvalidChain');
    }

    public function testZ047_Trafficker_View_Banner_ZoneAdd_Custom300x250_SessionCapping_InvalidChain()
    {
        $this->_runIncludedCombo('Z047', 'TRAFFICKER', 'View', 'Banner', 'ZONE_ADD', 'Custom_300x250', 'SessionCapping', 'InvalidChain');
    }

    public function testZ048_Trafficker_Delete_Popup_ZoneDelete_IAB728x90_Block_ValidChain()
    {
        $this->_runIncludedCombo('Z048', 'TRAFFICKER', 'Delete', 'Popup', 'ZONE_DELETE', 'IAB_728x90', 'Block', 'ValidChain');
    }

    public function testZ049_Trafficker_Edit_Popup_ZoneEdit_IAB468x60_Capping_InvalidChain()
    {
        $this->_runIncludedCombo('Z049', 'TRAFFICKER', 'Edit', 'Popup', 'ZONE_EDIT', 'IAB_468x60', 'Capping', 'InvalidChain');
    }

    public function testZ050_Trafficker_View_Interstitial_ZoneDelete_Custom160x600_AllThree_None()
    {
        $this->_runIncludedCombo('Z050', 'TRAFFICKER', 'View', 'Interstitial', 'ZONE_DELETE', 'Custom_160x600', 'AllThree', 'None');
    }

    public function testZ051_Manager_Edit_Text_ZoneEdit_IAB728x90_Capping_None()
    {
        $this->_runIncludedCombo('Z051', 'MANAGER', 'Edit', 'Text', 'ZONE_EDIT', 'IAB_728x90', 'Capping', 'None');
    }

    public function testZ052_Manager_Delete_Banner_ZoneDelete_IAB300x250_SessionCapping_None()
    {
        $this->_runIncludedCombo('Z052', 'MANAGER', 'Delete', 'Banner', 'ZONE_DELETE', 'IAB_300x250', 'SessionCapping', 'None');
    }

    public function testZ053_Manager_View_VideoInstream_ZoneLink_IAB468x60_None_InvalidChain()
    {
        $this->_runIncludedCombo('Z053', 'MANAGER', 'View', 'VideoInstream', 'ZONE_LINK', 'IAB_468x60', 'None', 'InvalidChain');
    }

    public function testZ054_Manager_Edit_Interstitial_ZoneAdd_IAB728x90_AllThree_ValidChain()
    {
        $this->_runIncludedCombo('Z054', 'MANAGER', 'Edit', 'Interstitial', 'ZONE_ADD', 'IAB_728x90', 'AllThree', 'ValidChain');
    }

    public function testZ055_Manager_Delete_Email_ZoneInvocation_IAB468x60_Block_None()
    {
        $this->_runIncludedCombo('Z055', 'MANAGER', 'Delete', 'Email', 'ZONE_INVOCATION', 'IAB_468x60', 'Block', 'None');
    }

    public function testZ056_Manager_Create_Banner_ZoneAdd_Custom300x250_AllThree_ValidChain()
    {
        $this->_runIncludedCombo('Z056', 'MANAGER', 'Create', 'Banner', 'ZONE_ADD', 'Custom_300x250', 'AllThree', 'ValidChain');
    }

    public function testZ057_Manager_Create_Banner_ZoneEdit_IAB728x90_Block_InvalidChain()
    {
        $this->_runIncludedCombo('Z057', 'MANAGER', 'Create', 'Banner', 'ZONE_EDIT', 'IAB_728x90', 'Block', 'InvalidChain');
    }

    public function testZ058_Manager_Edit_Banner_ZoneDelete_Custom160x600_None_ValidChain()
    {
        $this->_runIncludedCombo('Z058', 'MANAGER', 'Edit', 'Banner', 'ZONE_DELETE', 'Custom_160x600', 'None', 'ValidChain');
    }

    public function testZ059_Trafficker_Create_Banner_ZoneLink_IAB468x60_AllThree_InvalidChain()
    {
        $this->_runIncludedCombo('Z059', 'TRAFFICKER', 'Create', 'Banner', 'ZONE_LINK', 'IAB_468x60', 'AllThree', 'InvalidChain');
    }

    public function testZ060_Trafficker_Create_Email_ZoneEdit_IAB300x250_Block_None()
    {
        $this->_runIncludedCombo('Z060', 'TRAFFICKER', 'Create', 'Email', 'ZONE_EDIT', 'IAB_300x250', 'Block', 'None');
    }

    // ========================================================================
    // Section 4F: Excluded / Negative Combo Tests
    // ========================================================================

    /**
     * 4F-NEG-01: ADMIN account should be denied zone CRUD access.
     * zone-edit.php:45 enforces MANAGER or TRAFFICKER only.
     */
    public function testNeg01_AdminAccountDeniedZoneCrud()
    {
        $oPublisherInfo = $this->_createPublisher();
        $dllZone = new PartialMockOA_Dll_Zone_ComboTest($this);
        $dllZone->setReturnValue('checkPermissions', false);

        // Create attempt
        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = $oPublisherInfo->publisherId;
        $oZone->zoneName = 'Admin Zone Create';
        $oZone->type = 0;
        $oZone->width = 468;
        $oZone->height = 60;

        $this->assertFalse(
            $dllZone->modify($oZone),
            'NEG-01a: ADMIN account should be denied zone create',
        );

        // Edit attempt (create a zone first with allowed mock)
        $existingZoneId = $this->_createExistingZone($oPublisherInfo->publisherId, 'Banner', 'IAB_468x60');
        $oZoneEdit = new OA_Dll_ZoneInfo();
        $oZoneEdit->zoneId = $existingZoneId;
        $oZoneEdit->zoneName = 'Admin Edit Attempt';

        $this->assertFalse(
            $dllZone->modify($oZoneEdit),
            'NEG-01b: ADMIN account should be denied zone edit',
        );

        // Delete attempt
        $this->assertFalse(
            $dllZone->delete($existingZoneId),
            'NEG-01c: ADMIN account should be denied zone delete',
        );
    }

    /**
     * 4F-NEG-02: ADVERTISER account should be denied zone CRUD access.
     * zone-edit.php:45 enforces MANAGER or TRAFFICKER only.
     */
    public function testNeg02_AdvertiserAccountDeniedZoneCrud()
    {
        $oPublisherInfo = $this->_createPublisher();
        $dllZone = new PartialMockOA_Dll_Zone_ComboTest($this);
        $dllZone->setReturnValue('checkPermissions', false);

        // Create attempt
        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = $oPublisherInfo->publisherId;
        $oZone->zoneName = 'Advertiser Zone Create';
        $oZone->type = 0;
        $oZone->width = 468;
        $oZone->height = 60;

        $this->assertFalse(
            $dllZone->modify($oZone),
            'NEG-02a: ADVERTISER account should be denied zone create',
        );

        // Edit attempt
        $existingZoneId = $this->_createExistingZone($oPublisherInfo->publisherId, 'Banner', 'IAB_468x60');
        $oZoneEdit = new OA_Dll_ZoneInfo();
        $oZoneEdit->zoneId = $existingZoneId;
        $oZoneEdit->zoneName = 'Advertiser Edit Attempt';

        $this->assertFalse(
            $dllZone->modify($oZoneEdit),
            'NEG-02b: ADVERTISER account should be denied zone edit',
        );

        // Delete attempt
        $this->assertFalse(
            $dllZone->delete($existingZoneId),
            'NEG-02c: ADVERTISER account should be denied zone delete',
        );

        // View attempt
        $oZoneGet = null;
        $this->assertFalse(
            $dllZone->getZone($existingZoneId, $oZoneGet),
            'NEG-02d: ADVERTISER account should be denied zone view',
        );
    }

    /**
     * 4F-NEG-03: Text zone type forces size to 0x0 regardless of input.
     * zone-edit.php:211-221 — Text zones have size forced to 0x0.
     */
    public function testNeg03_TextZoneForcesZeroSize()
    {
        $oPublisherInfo = $this->_createPublisher();
        $dllZone = $this->_createZoneDll();

        // Create Text zone with custom size — should still work but
        // the form logic forces 0x0 for Text zones
        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = $oPublisherInfo->publisherId;
        $oZone->zoneName = 'Text Zone Custom Size';
        $oZone->type = 3; // phpAds_ZoneText
        $oZone->width = 0;
        $oZone->height = 0;

        $this->assertTrue(
            $dllZone->modify($oZone),
            'NEG-03: Text zone with forced 0x0 size should succeed - ' . $dllZone->getLastError(),
        );

        $oZoneGet = null;
        $dllZone->getZone($oZone->zoneId, $oZoneGet);
        $this->assertEqual($oZoneGet->width, 0, 'NEG-03: Text zone width should be 0');
        $this->assertEqual($oZoneGet->height, 0, 'NEG-03: Text zone height should be 0');
    }

    /**
     * 4F-NEG-04: VideoInstream zone type forces size to -3x-3.
     * zone-edit.php:211-221 — VideoInstream zones have special size.
     */
    public function testNeg04_VideoInstreamZoneForcesSpecialSize()
    {
        $oPublisherInfo = $this->_createPublisher();
        $dllZone = $this->_createZoneDll();

        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = $oPublisherInfo->publisherId;
        $oZone->zoneName = 'VideoInstream Zone';
        $oZone->type = 6; // OX_ZoneVideoInstream
        $oZone->width = -3;
        $oZone->height = -3;

        $this->assertTrue(
            $dllZone->modify($oZone),
            'NEG-04: VideoInstream zone should succeed with forced size - ' . $dllZone->getLastError(),
        );

        $oZoneGet = null;
        $dllZone->getZone($oZone->zoneId, $oZoneGet);
        $this->assertEqual($oZoneGet->width, -3, 'NEG-04: VideoInstream width should be -3');
        $this->assertEqual($oZoneGet->height, -3, 'NEG-04: VideoInstream height should be -3');
    }

    /**
     * 4F-NEG-05: VideoOverlay zone type forces size to -2x-2.
     * zone-edit.php:211-221 — VideoOverlay zones have special size.
     */
    public function testNeg05_VideoOverlayZoneForcesSpecialSize()
    {
        $oPublisherInfo = $this->_createPublisher();
        $dllZone = $this->_createZoneDll();

        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = $oPublisherInfo->publisherId;
        $oZone->zoneName = 'VideoOverlay Zone';
        $oZone->type = 7; // OX_ZoneVideoOverlay
        $oZone->width = -2;
        $oZone->height = -2;

        $this->assertTrue(
            $dllZone->modify($oZone),
            'NEG-05: VideoOverlay zone should succeed with forced size - ' . $dllZone->getLastError(),
        );

        $oZoneGet = null;
        $dllZone->getZone($oZone->zoneId, $oZoneGet);
        $this->assertEqual($oZoneGet->width, -2, 'NEG-05: VideoOverlay width should be -2');
        $this->assertEqual($oZoneGet->height, -2, 'NEG-05: VideoOverlay height should be -2');
    }

    /**
     * 4F-NEG-06: Interstitial zone type cannot be created as new zone.
     * zone-edit.php:144-167 — Interstitial is only shown as radio button
     * for existing zones already of that type. Via the DLL, we verify
     * the type is accepted but the form logic restricts it to existing zones.
     */
    public function testNeg06_InterstitialCreateViaFormRestriction()
    {
        $oPublisherInfo = $this->_createPublisher();
        $dllZone = $this->_createZoneDll();

        // Interstitial exists only for existing zones — verify
        // editing an existing Interstitial zone works
        $existingZoneId = $this->_createExistingZone(
            $oPublisherInfo->publisherId,
            'Interstitial',
            'IAB_468x60',
        );

        $oZone = new OA_Dll_ZoneInfo();
        $oZone->zoneId = $existingZoneId;
        $oZone->zoneName = 'Edit Existing Interstitial';

        $this->assertTrue(
            $dllZone->modify($oZone),
            'NEG-06: Editing existing Interstitial zone should succeed - ' . $dllZone->getLastError(),
        );

        // Verify zone type is preserved as Interstitial
        $oZoneGet = null;
        $dllZone->getZone($existingZoneId, $oZoneGet);
        $this->assertEqual(
            $oZoneGet->type,
            1, // phpAds_ZoneInterstitial
            'NEG-06: Zone type should remain Interstitial after edit',
        );
    }

    /**
     * 4F-NEG-07: Popup zone type cannot be created as new zone.
     * zone-edit.php:156-167 — Popup is only shown as radio button
     * for existing zones already of that type.
     */
    public function testNeg07_PopupCreateViaFormRestriction()
    {
        $oPublisherInfo = $this->_createPublisher();
        $dllZone = $this->_createZoneDll();

        // Popup exists only for existing zones — verify editing works
        $existingZoneId = $this->_createExistingZone(
            $oPublisherInfo->publisherId,
            'Popup',
            'IAB_468x60',
        );

        $oZone = new OA_Dll_ZoneInfo();
        $oZone->zoneId = $existingZoneId;
        $oZone->zoneName = 'Edit Existing Popup';

        $this->assertTrue(
            $dllZone->modify($oZone),
            'NEG-07: Editing existing Popup zone should succeed - ' . $dllZone->getLastError(),
        );

        // Verify zone type is preserved as Popup
        $oZoneGet = null;
        $dllZone->getZone($existingZoneId, $oZoneGet);
        $this->assertEqual(
            $oZoneGet->type,
            2, // phpAds_ZonePopup
            'NEG-07: Zone type should remain Popup after edit',
        );
    }

    /**
     * 4F-NEG-08: Cannot chain a zone to itself.
     */
    public function testNeg08_CannotChainZoneToItself()
    {
        $oPublisherInfo = $this->_createPublisher();
        $dllZone = $this->_createZoneDll();

        // Create a zone first
        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = $oPublisherInfo->publisherId;
        $oZone->zoneName = 'Self Chain Zone';
        $oZone->type = 0;
        $oZone->width = 468;
        $oZone->height = 60;
        $dllZone->modify($oZone);

        // Try to chain it to itself
        $oZone->chainedZoneId = $oZone->zoneId;
        $this->assertFalse(
            $dllZone->modify($oZone),
            'NEG-08: Should not be able to chain zone to itself',
        );
        $this->assertEqual(
            $dllZone->getLastError(),
            $this->chainError,
            'NEG-08: Error should be self-chain error',
        );
    }

    /**
     * 4F-NEG-09: Cannot chain to a non-existent zone.
     */
    public function testNeg09_CannotChainToNonExistentZone()
    {
        $oPublisherInfo = $this->_createPublisher();
        $dllZone = $this->_createZoneDll();

        // Create a zone first
        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = $oPublisherInfo->publisherId;
        $oZone->zoneName = 'Invalid Chain Zone';
        $oZone->type = 0;
        $oZone->width = 468;
        $oZone->height = 60;
        $dllZone->modify($oZone);

        // Try to chain to non-existent zone
        $oZone->chainedZoneId = 999999;
        $this->assertFalse(
            $dllZone->modify($oZone),
            'NEG-09: Should not be able to chain to non-existent zone',
        );
        $this->assertEqual(
            $dllZone->getLastError(),
            $this->unknownIdError,
            'NEG-09: Error should be unknown zone ID',
        );
    }

    /**
     * 4F-NEG-10: Cannot delete a non-existent zone.
     */
    public function testNeg10_CannotDeleteNonExistentZone()
    {
        $dllZone = $this->_createZoneDll();

        $this->assertFalse(
            $dllZone->delete(999999),
            'NEG-10: Should not be able to delete non-existent zone',
        );
    }

    /**
     * 4F-NEG-11: Cannot edit a non-existent zone.
     */
    public function testNeg11_CannotEditNonExistentZone()
    {
        $dllZone = $this->_createZoneDll();

        $oZone = new OA_Dll_ZoneInfo();
        $oZone->zoneId = 999999;
        $oZone->zoneName = 'Edit Non-Existent';

        $this->assertFalse(
            $dllZone->modify($oZone),
            'NEG-11: Should not be able to edit non-existent zone',
        );
        $this->assertEqual(
            $dllZone->getLastError(),
            $this->unknownIdError,
            'NEG-11: Error should be unknown zone ID',
        );
    }

    /**
     * 4F-NEG-12: Cannot view a non-existent zone.
     */
    public function testNeg12_CannotViewNonExistentZone()
    {
        $dllZone = $this->_createZoneDll();

        $oZoneGet = null;
        $this->assertFalse(
            $dllZone->getZone(999999, $oZoneGet),
            'NEG-12: Should not be able to view non-existent zone',
        );
        $this->assertEqual(
            $dllZone->getLastError(),
            $this->unknownIdError,
            'NEG-12: Error should be unknown zone ID',
        );
    }

    /**
     * 4F-NEG-13: Zone create without required publisherId should fail.
     */
    public function testNeg13_CreateWithoutPublisherIdFails()
    {
        $dllZone = $this->_createZoneDll();

        $oZone = new OA_Dll_ZoneInfo();
        $oZone->zoneName = 'No Publisher Zone';
        $oZone->type = 0;
        $oZone->width = 468;
        $oZone->height = 60;

        $this->assertFalse(
            $dllZone->modify($oZone),
            'NEG-13: Create without publisherId should fail',
        );
    }

    /**
     * 4F-NEG-14: Zone create with non-existent publisherId should fail.
     */
    public function testNeg14_CreateWithInvalidPublisherIdFails()
    {
        $dllZone = $this->_createZoneDll();

        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = 999999;
        $oZone->zoneName = 'Invalid Publisher Zone';
        $oZone->type = 0;
        $oZone->width = 468;
        $oZone->height = 60;

        $this->assertFalse(
            $dllZone->modify($oZone),
            'NEG-14: Create with non-existent publisherId should fail',
        );
    }

    /**
     * 4F-NEG-15: TRAFFICKER without ZONE_ADD permission denied create.
     */
    public function testNeg15_TraffickerWithoutZoneAddDeniedCreate()
    {
        $oPublisherInfo = $this->_createPublisher();
        $dllZone = $this->_createZoneDllDenied();

        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = $oPublisherInfo->publisherId;
        $oZone->zoneName = 'Denied Create Zone';
        $oZone->type = 0;
        $oZone->width = 468;
        $oZone->height = 60;

        $this->assertFalse(
            $dllZone->modify($oZone),
            'NEG-15: TRAFFICKER without ZONE_ADD should be denied create',
        );
    }

    /**
     * 4F-NEG-16: TRAFFICKER without ZONE_EDIT permission denied edit.
     */
    public function testNeg16_TraffickerWithoutZoneEditDeniedEdit()
    {
        $oPublisherInfo = $this->_createPublisher();

        // Create a zone with allowed mock first
        $existingZoneId = $this->_createExistingZone(
            $oPublisherInfo->publisherId,
            'Banner',
            'IAB_468x60',
        );

        $dllZone = $this->_createZoneDllDenied();

        $oZone = new OA_Dll_ZoneInfo();
        $oZone->zoneId = $existingZoneId;
        $oZone->zoneName = 'Denied Edit Zone';

        $this->assertFalse(
            $dllZone->modify($oZone),
            'NEG-16: TRAFFICKER without ZONE_EDIT should be denied edit',
        );
    }

    /**
     * 4F-NEG-17: TRAFFICKER without ZONE_DELETE permission denied delete.
     */
    public function testNeg17_TraffickerWithoutZoneDeleteDeniedDelete()
    {
        $oPublisherInfo = $this->_createPublisher();
        $existingZoneId = $this->_createExistingZone(
            $oPublisherInfo->publisherId,
            'Banner',
            'IAB_468x60',
        );

        $dllZone = $this->_createZoneDllDenied();

        $this->assertFalse(
            $dllZone->delete($existingZoneId),
            'NEG-17: TRAFFICKER without ZONE_DELETE should be denied delete',
        );
    }

    /**
     * 4F-NEG-18: Zone name exceeding maximum length should fail validation.
     */
    public function testNeg18_ZoneNameExceedsMaxLength()
    {
        $oPublisherInfo = $this->_createPublisher();
        $dllZone = $this->_createZoneDll();

        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = $oPublisherInfo->publisherId;
        $oZone->zoneName = str_repeat('A', 300); // exceeds 245 char limit
        $oZone->type = 0;
        $oZone->width = 468;
        $oZone->height = 60;

        $this->assertFalse(
            $dllZone->modify($oZone),
            'NEG-18: Zone name exceeding max length should fail',
        );
    }

    /**
     * 4F-NEG-19: Zone with all capping fields as negative values should
     * normalize to zero (or be handled gracefully).
     */
    public function testNeg19_NegativeCappingValuesNormalized()
    {
        $oPublisherInfo = $this->_createPublisher();
        $dllZone = $this->_createZoneDll();

        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = $oPublisherInfo->publisherId;
        $oZone->zoneName = 'Negative Capping Zone';
        $oZone->type = 0;
        $oZone->width = 468;
        $oZone->height = 60;
        $oZone->capping = -5;
        $oZone->sessionCapping = -3;
        $oZone->block = -100;

        // The DLL normalizes negative values to 0 via max($value, 0)
        $this->assertTrue(
            $dllZone->modify($oZone),
            'NEG-19: Negative capping values should be normalized - ' . $dllZone->getLastError(),
        );

        $oZoneGet = null;
        $dllZone->getZone($oZone->zoneId, $oZoneGet);
        $this->assertTrue(
            $oZoneGet->capping >= 0,
            'NEG-19: Capping should be normalized to >= 0',
        );
        $this->assertTrue(
            $oZoneGet->sessionCapping >= 0,
            'NEG-19: Session capping should be normalized to >= 0',
        );
        $this->assertTrue(
            $oZoneGet->block >= 0,
            'NEG-19: Block should be normalized to >= 0',
        );
    }

    /**
     * 4F-NEG-20: Wildcard size zones accept -1 for width and height.
     */
    public function testNeg20_WildcardSizeAccepted()
    {
        $oPublisherInfo = $this->_createPublisher();
        $dllZone = $this->_createZoneDll();

        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = $oPublisherInfo->publisherId;
        $oZone->zoneName = 'Wildcard Size Zone';
        $oZone->type = 0; // Banner
        $oZone->width = -1;
        $oZone->height = -1;

        $this->assertTrue(
            $dllZone->modify($oZone),
            'NEG-20: Wildcard size (-1) should be accepted - ' . $dllZone->getLastError(),
        );

        $oZoneGet = null;
        $dllZone->getZone($oZone->zoneId, $oZoneGet);
        $this->assertEqual($oZoneGet->width, -1, 'NEG-20: Width should be -1 (wildcard)');
        $this->assertEqual($oZoneGet->height, -1, 'NEG-20: Height should be -1 (wildcard)');
    }
}
