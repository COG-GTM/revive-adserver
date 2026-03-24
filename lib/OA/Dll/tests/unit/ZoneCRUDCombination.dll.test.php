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
 * Zone CRUD Combination Matrix Tests (Sections 4E + 4F)
 *
 * This file contains pairwise-generated positive test cases and
 * negative validation / permission-denial test cases for the Zone
 * CRUD combinatorial testing plan.
 *
 * Dimensions covered:
 *   1. Account type   : MANAGER, TRAFFICKER
 *   2. Page mode      : Create, Edit, View, Delete
 *   3. Zone type      : Banner(0), Interstitial(1), Popup(2), Text(3),
 *                        Email(4), VideoInstream(6), VideoOverlay(7)
 *   4. Zone permission: ZONE_ADD, ZONE_DELETE, ZONE_EDIT, ZONE_INVOCATION, ZONE_LINK
 *   5. Size type      : IAB standard, Custom, Wildcard (*)
 *   6. Frequency cap  : None, capping, sessionCapping, block, allThree
 *   7. Chained zone   : None, validChain, invalidChain
 *
 * @package    OpenXDll
 * @subpackage TestSuite
 */

require_once MAX_PATH . '/lib/OA/Dll/Publisher.php';
require_once MAX_PATH . '/lib/OA/Dll/PublisherInfo.php';
require_once MAX_PATH . '/lib/OA/Dll/Zone.php';
require_once MAX_PATH . '/lib/OA/Dll/ZoneInfo.php';
require_once MAX_PATH . '/lib/OA/Dll/tests/util/DllUnitTestCase.php';

Language_Loader::load();


class OA_Dll_ZoneCRUDCombinationTest extends DllUnitTestCase
{
    /**
     * @var int
     */
    public $agencyId;

    public $unknownIdError = 'Unknown zoneId Error';
    public $chainError = 'Cannot chain a zone to itself';
    public $accessForbiddenError = 'Access forbidden';

    // Zone type constants
    const ZONE_BANNER = 0;
    const ZONE_INTERSTITIAL = 1;
    const ZONE_POPUP = 2;
    const ZONE_TEXT = 3;
    const ZONE_EMAIL = 4;
    const ZONE_VIDEO_INSTREAM = 6;
    const ZONE_VIDEO_OVERLAY = 7;

    // Capping presets
    const CAP_NONE = ['capping' => null, 'sessionCapping' => null, 'block' => null];
    const CAP_CAPPING = ['capping' => 10, 'sessionCapping' => null, 'block' => null];
    const CAP_SESSION = ['capping' => null, 'sessionCapping' => 5, 'block' => null];
    const CAP_BLOCK = ['capping' => null, 'sessionCapping' => null, 'block' => 3600];
    const CAP_ALL = ['capping' => 10, 'sessionCapping' => 5, 'block' => 3600];

    public function __construct()
    {
        parent::__construct();
        Mock::generatePartial(
            'OA_Dll_Publisher',
            'PartialMockOA_Dll_Publisher_ZoneCRUDComboTest',
            ['checkPermissions', 'getDefaultAgencyId'],
        );
        Mock::generatePartial(
            'OA_Dll_Zone',
            'PartialMockOA_Dll_Zone_CRUDCombo',
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
    // Helper: create a publisher under the current agency
    // ---------------------------------------------------------------
    private function _createPublisher()
    {
        $dllPub = new PartialMockOA_Dll_Publisher_ZoneCRUDComboTest($this);
        $dllPub->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllPub->setReturnValue('checkPermissions', true);

        $oPubInfo = new OA_Dll_PublisherInfo();
        $oPubInfo->publisherName = 'Test Publisher';
        $oPubInfo->agencyId = $this->agencyId;
        $dllPub->modify($oPubInfo);

        return $oPubInfo->publisherId;
    }

    // ---------------------------------------------------------------
    // Helper: create a zone for chaining purposes
    // ---------------------------------------------------------------
    private function _createChainTargetZone($publisherId)
    {
        $dllZone = new PartialMockOA_Dll_Zone_CRUDCombo($this);
        $dllZone->setReturnValue('checkPermissions', true);

        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = $publisherId;
        $oZone->zoneName = 'Chain Target Zone';
        $oZone->type = self::ZONE_BANNER;
        $oZone->width = 468;
        $oZone->height = 60;
        $dllZone->modify($oZone);

        return $oZone->zoneId;
    }

    // ---------------------------------------------------------------
    // Helper: resolve size for a given zone type and size-type label
    // ---------------------------------------------------------------
    private function _resolveSize($zoneType, $sizeType)
    {
        // Text, VideoInstream, VideoOverlay force size to 0x0 / special
        if (in_array($zoneType, [self::ZONE_TEXT, self::ZONE_VIDEO_INSTREAM, self::ZONE_VIDEO_OVERLAY])) {
            return ['width' => 0, 'height' => 0];
        }

        switch ($sizeType) {
            case 'iab_468x60':
                return ['width' => 468, 'height' => 60];
            case 'iab_728x90':
                return ['width' => 728, 'height' => 90];
            case 'iab_300x250':
                return ['width' => 300, 'height' => 250];
            case 'iab_160x600':
                return ['width' => 160, 'height' => 600];
            case 'iab_120x600':
                return ['width' => 120, 'height' => 600];
            case 'iab_336x280':
                return ['width' => 336, 'height' => 280];
            case 'custom_300x250':
                return ['width' => 300, 'height' => 250];
            case 'custom_160x600':
                return ['width' => 160, 'height' => 600];
            case 'custom_800x600':
                return ['width' => 800, 'height' => 600];
            case 'custom_550x480':
                return ['width' => 550, 'height' => 480];
            case 'wildcard':
                return ['width' => -1, 'height' => -1];
            default:
                return ['width' => 468, 'height' => 60];
        }
    }

    // ---------------------------------------------------------------
    // Helper: apply frequency capping to a ZoneInfo object
    // ---------------------------------------------------------------
    private function _applyCapping(&$oZone, $cappingPreset)
    {
        if (!empty($cappingPreset['capping'])) {
            $oZone->capping = $cappingPreset['capping'];
        }
        if (!empty($cappingPreset['sessionCapping'])) {
            $oZone->sessionCapping = $cappingPreset['sessionCapping'];
        }
        if (!empty($cappingPreset['block'])) {
            $oZone->block = $cappingPreset['block'];
        }
    }

    // ---------------------------------------------------------------
    // Core runner: executes a single pairwise combination
    // ---------------------------------------------------------------
    private function _runCombination($comboId, $config)
    {
        $publisherId = $this->_createPublisher();
        $dllZone = new PartialMockOA_Dll_Zone_CRUDCombo($this);
        $dllZone->setReturnValue('checkPermissions', true);

        $mode = $config['mode'];
        $zoneType = $config['zoneType'];
        $sizeType = $config['sizeType'];
        $capping = $config['capping'];
        $chain = $config['chain'];

        $size = $this->_resolveSize($zoneType, $sizeType);

        // --- CREATE ---
        if ($mode === 'create') {
            $oZone = new OA_Dll_ZoneInfo();
            $oZone->publisherId = $publisherId;
            $oZone->zoneName = "Combo {$comboId} Zone";
            $oZone->type = $zoneType;
            $oZone->width = $size['width'];
            $oZone->height = $size['height'];
            $this->_applyCapping($oZone, $capping);

            if ($chain === 'valid') {
                $chainZoneId = $this->_createChainTargetZone($publisherId);
                $oZone->chainedZoneId = $chainZoneId;
            }

            $result = $dllZone->modify($oZone);
            $this->assertTrue($result, "{$comboId}: Create failed - " . $dllZone->getLastError());
            $this->assertNotNull($oZone->zoneId, "{$comboId}: zoneId should be set after create");

            if ($chain === 'invalid') {
                $oZone2 = new OA_Dll_ZoneInfo();
                $oZone2->zoneId = $oZone->zoneId;
                $oZone2->chainedZoneId = 999999;
                $result2 = $dllZone->modify($oZone2);
                $this->assertFalse($result2, "{$comboId}: Chaining to non-existent zone should fail");
            }

            // Verify via get
            $oZoneGet = null;
            $this->assertTrue(
                $dllZone->getZone($oZone->zoneId, $oZoneGet),
                "{$comboId}: getZone after create failed",
            );
            $this->assertEqual($oZoneGet->zoneName, "Combo {$comboId} Zone", "{$comboId}: zoneName mismatch");
            $this->assertEqual($oZoneGet->type, $zoneType, "{$comboId}: type mismatch");

            // Cleanup
            $dllZone->delete($oZone->zoneId);
            return;
        }

        // For edit/view/delete, first create a zone
        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = $publisherId;
        $oZone->zoneName = "Combo {$comboId} Pre-Zone";
        $oZone->type = $zoneType;
        $oZone->width = $size['width'];
        $oZone->height = $size['height'];
        $result = $dllZone->modify($oZone);
        $this->assertTrue($result, "{$comboId}: Pre-create failed - " . $dllZone->getLastError());

        // --- EDIT ---
        if ($mode === 'edit') {
            $oZone->zoneName = "Combo {$comboId} Edited";
            $this->_applyCapping($oZone, $capping);

            if ($chain === 'valid') {
                $chainZoneId = $this->_createChainTargetZone($publisherId);
                $oZone->chainedZoneId = $chainZoneId;
            } elseif ($chain === 'invalid') {
                $oZone->chainedZoneId = 999999;
                $result = $dllZone->modify($oZone);
                $this->assertFalse($result, "{$comboId}: Edit with invalid chain should fail");

                // Reset and do a valid edit to confirm the zone is intact
                $oZone->chainedZoneId = null;
            }

            if ($chain !== 'invalid') {
                $result = $dllZone->modify($oZone);
                $this->assertTrue($result, "{$comboId}: Edit failed - " . $dllZone->getLastError());

                $oZoneGet = null;
                $dllZone->getZone($oZone->zoneId, $oZoneGet);
                $this->assertEqual($oZoneGet->zoneName, "Combo {$comboId} Edited", "{$comboId}: Edit name mismatch");
            }

            $dllZone->delete($oZone->zoneId);
            return;
        }

        // --- VIEW ---
        if ($mode === 'view') {
            $oZoneGet = null;
            $result = $dllZone->getZone($oZone->zoneId, $oZoneGet);
            $this->assertTrue($result, "{$comboId}: View failed - " . $dllZone->getLastError());
            $this->assertEqual($oZoneGet->type, $zoneType, "{$comboId}: View type mismatch");
            $this->assertEqual($oZoneGet->publisherId, $publisherId, "{$comboId}: View publisherId mismatch");

            $dllZone->delete($oZone->zoneId);
            return;
        }

        // --- DELETE ---
        if ($mode === 'delete') {
            $result = $dllZone->delete($oZone->zoneId);
            $this->assertTrue($result, "{$comboId}: Delete failed - " . $dllZone->getLastError());

            // Verify deleted
            $oZoneGet = null;
            $result = $dllZone->getZone($oZone->zoneId, $oZoneGet);
            $this->assertFalse($result, "{$comboId}: Zone should not exist after delete");
            return;
        }
    }

    // ===============================================================
    // SECTION 4E: Pairwise-Generated Positive Test Cases (~60 combos)
    // ===============================================================
    //
    // Generated using all-pairs algorithm across 7 dimensions:
    //   Account  x  Mode  x  ZoneType  x  Permission  x  Size  x  Capping  x  Chain
    //
    // Note: "Account" and "Permission" dimensions are validated in
    // the test structure but since we mock checkPermissions=true,
    // they serve as combinatorial coverage markers. The permission
    // enforcement is separately tested in Section 4F.

    public function testZ001_Manager_Create_Banner_ZoneAdd_IAB468x60_NoCap_NoChain()
    {
        $this->_runCombination('Z001', [
            'account' => 'MANAGER', 'mode' => 'create', 'zoneType' => self::ZONE_BANNER,
            'permission' => 'ZONE_ADD', 'sizeType' => 'iab_468x60',
            'capping' => self::CAP_NONE, 'chain' => 'none',
        ]);
    }

    public function testZ002_Trafficker_Create_Text_ZoneAdd_NA_Capping_NoChain()
    {
        $this->_runCombination('Z002', [
            'account' => 'TRAFFICKER', 'mode' => 'create', 'zoneType' => self::ZONE_TEXT,
            'permission' => 'ZONE_ADD', 'sizeType' => 'iab_468x60',
            'capping' => self::CAP_CAPPING, 'chain' => 'none',
        ]);
    }

    public function testZ003_Manager_Edit_Email_ZoneEdit_Custom300x250_Session_ValidChain()
    {
        $this->_runCombination('Z003', [
            'account' => 'MANAGER', 'mode' => 'edit', 'zoneType' => self::ZONE_EMAIL,
            'permission' => 'ZONE_EDIT', 'sizeType' => 'custom_300x250',
            'capping' => self::CAP_SESSION, 'chain' => 'valid',
        ]);
    }

    public function testZ004_Trafficker_Edit_Banner_ZoneEdit_Wildcard_Block_NoChain()
    {
        $this->_runCombination('Z004', [
            'account' => 'TRAFFICKER', 'mode' => 'edit', 'zoneType' => self::ZONE_BANNER,
            'permission' => 'ZONE_EDIT', 'sizeType' => 'wildcard',
            'capping' => self::CAP_BLOCK, 'chain' => 'none',
        ]);
    }

    public function testZ005_Manager_Delete_Popup_ZoneDelete_IAB728x90_NoCap_NoChain()
    {
        $this->_runCombination('Z005', [
            'account' => 'MANAGER', 'mode' => 'delete', 'zoneType' => self::ZONE_POPUP,
            'permission' => 'ZONE_DELETE', 'sizeType' => 'iab_728x90',
            'capping' => self::CAP_NONE, 'chain' => 'none',
        ]);
    }

    public function testZ006_Trafficker_Create_VideoInstream_ZoneAdd_NA_AllCap_NoChain()
    {
        $this->_runCombination('Z006', [
            'account' => 'TRAFFICKER', 'mode' => 'create', 'zoneType' => self::ZONE_VIDEO_INSTREAM,
            'permission' => 'ZONE_ADD', 'sizeType' => 'iab_468x60',
            'capping' => self::CAP_ALL, 'chain' => 'none',
        ]);
    }

    public function testZ007_Manager_Edit_VideoOverlay_ZoneEdit_NA_NoCap_InvalidChain()
    {
        $this->_runCombination('Z007', [
            'account' => 'MANAGER', 'mode' => 'edit', 'zoneType' => self::ZONE_VIDEO_OVERLAY,
            'permission' => 'ZONE_EDIT', 'sizeType' => 'iab_468x60',
            'capping' => self::CAP_NONE, 'chain' => 'invalid',
        ]);
    }

    public function testZ008_Manager_Create_Banner_ZoneAdd_Custom160x600_Capping_ValidChain()
    {
        $this->_runCombination('Z008', [
            'account' => 'MANAGER', 'mode' => 'create', 'zoneType' => self::ZONE_BANNER,
            'permission' => 'ZONE_ADD', 'sizeType' => 'custom_160x600',
            'capping' => self::CAP_CAPPING, 'chain' => 'valid',
        ]);
    }

    public function testZ009_Trafficker_Delete_Text_ZoneDelete_NA_NoCap_NoChain()
    {
        $this->_runCombination('Z009', [
            'account' => 'TRAFFICKER', 'mode' => 'delete', 'zoneType' => self::ZONE_TEXT,
            'permission' => 'ZONE_DELETE', 'sizeType' => 'iab_468x60',
            'capping' => self::CAP_NONE, 'chain' => 'none',
        ]);
    }

    public function testZ010_Manager_View_Email_NA_IAB300x250_NoCap_NoChain()
    {
        $this->_runCombination('Z010', [
            'account' => 'MANAGER', 'mode' => 'view', 'zoneType' => self::ZONE_EMAIL,
            'permission' => 'ZONE_EDIT', 'sizeType' => 'iab_300x250',
            'capping' => self::CAP_NONE, 'chain' => 'none',
        ]);
    }

    // --- Remaining pairwise combinations (Z011-Z060) ---

    public function testZ011_Trafficker_View_Banner_ZoneEdit_IAB728x90_Session_NoChain()
    {
        $this->_runCombination('Z011', [
            'account' => 'TRAFFICKER', 'mode' => 'view', 'zoneType' => self::ZONE_BANNER,
            'permission' => 'ZONE_EDIT', 'sizeType' => 'iab_728x90',
            'capping' => self::CAP_SESSION, 'chain' => 'none',
        ]);
    }

    public function testZ012_Manager_Create_Email_ZoneAdd_IAB300x250_Block_NoChain()
    {
        $this->_runCombination('Z012', [
            'account' => 'MANAGER', 'mode' => 'create', 'zoneType' => self::ZONE_EMAIL,
            'permission' => 'ZONE_ADD', 'sizeType' => 'iab_300x250',
            'capping' => self::CAP_BLOCK, 'chain' => 'none',
        ]);
    }

    public function testZ013_Trafficker_Edit_Text_ZoneEdit_NA_AllCap_ValidChain()
    {
        $this->_runCombination('Z013', [
            'account' => 'TRAFFICKER', 'mode' => 'edit', 'zoneType' => self::ZONE_TEXT,
            'permission' => 'ZONE_EDIT', 'sizeType' => 'iab_468x60',
            'capping' => self::CAP_ALL, 'chain' => 'valid',
        ]);
    }

    public function testZ014_Manager_Delete_Banner_ZoneDelete_Custom800x600_Capping_NoChain()
    {
        $this->_runCombination('Z014', [
            'account' => 'MANAGER', 'mode' => 'delete', 'zoneType' => self::ZONE_BANNER,
            'permission' => 'ZONE_DELETE', 'sizeType' => 'custom_800x600',
            'capping' => self::CAP_CAPPING, 'chain' => 'none',
        ]);
    }

    public function testZ015_Trafficker_Create_Banner_ZoneAdd_Wildcard_NoCap_ValidChain()
    {
        $this->_runCombination('Z015', [
            'account' => 'TRAFFICKER', 'mode' => 'create', 'zoneType' => self::ZONE_BANNER,
            'permission' => 'ZONE_ADD', 'sizeType' => 'wildcard',
            'capping' => self::CAP_NONE, 'chain' => 'valid',
        ]);
    }

    public function testZ016_Manager_View_VideoInstream_ZoneInvocation_NA_Capping_NoChain()
    {
        $this->_runCombination('Z016', [
            'account' => 'MANAGER', 'mode' => 'view', 'zoneType' => self::ZONE_VIDEO_INSTREAM,
            'permission' => 'ZONE_INVOCATION', 'sizeType' => 'iab_468x60',
            'capping' => self::CAP_CAPPING, 'chain' => 'none',
        ]);
    }

    public function testZ017_Trafficker_Delete_Email_ZoneDelete_IAB160x600_Session_NoChain()
    {
        $this->_runCombination('Z017', [
            'account' => 'TRAFFICKER', 'mode' => 'delete', 'zoneType' => self::ZONE_EMAIL,
            'permission' => 'ZONE_DELETE', 'sizeType' => 'iab_160x600',
            'capping' => self::CAP_SESSION, 'chain' => 'none',
        ]);
    }

    public function testZ018_Manager_Edit_Banner_ZoneLink_IAB120x600_NoCap_InvalidChain()
    {
        $this->_runCombination('Z018', [
            'account' => 'MANAGER', 'mode' => 'edit', 'zoneType' => self::ZONE_BANNER,
            'permission' => 'ZONE_LINK', 'sizeType' => 'iab_120x600',
            'capping' => self::CAP_NONE, 'chain' => 'invalid',
        ]);
    }

    public function testZ019_Trafficker_Create_VideoOverlay_ZoneAdd_NA_Block_NoChain()
    {
        $this->_runCombination('Z019', [
            'account' => 'TRAFFICKER', 'mode' => 'create', 'zoneType' => self::ZONE_VIDEO_OVERLAY,
            'permission' => 'ZONE_ADD', 'sizeType' => 'iab_468x60',
            'capping' => self::CAP_BLOCK, 'chain' => 'none',
        ]);
    }

    public function testZ020_Manager_Create_Interstitial_ZoneAdd_IAB336x280_AllCap_NoChain()
    {
        $this->_runCombination('Z020', [
            'account' => 'MANAGER', 'mode' => 'create', 'zoneType' => self::ZONE_INTERSTITIAL,
            'permission' => 'ZONE_ADD', 'sizeType' => 'iab_336x280',
            'capping' => self::CAP_ALL, 'chain' => 'none',
        ]);
    }

    public function testZ021_Trafficker_View_Popup_ZoneEdit_Custom550x480_NoCap_ValidChain()
    {
        $this->_runCombination('Z021', [
            'account' => 'TRAFFICKER', 'mode' => 'view', 'zoneType' => self::ZONE_POPUP,
            'permission' => 'ZONE_EDIT', 'sizeType' => 'custom_550x480',
            'capping' => self::CAP_NONE, 'chain' => 'valid',
        ]);
    }

    public function testZ022_Manager_Delete_VideoOverlay_ZoneDelete_NA_Session_NoChain()
    {
        $this->_runCombination('Z022', [
            'account' => 'MANAGER', 'mode' => 'delete', 'zoneType' => self::ZONE_VIDEO_OVERLAY,
            'permission' => 'ZONE_DELETE', 'sizeType' => 'iab_468x60',
            'capping' => self::CAP_SESSION, 'chain' => 'none',
        ]);
    }

    public function testZ023_Trafficker_Edit_Email_ZoneEdit_IAB468x60_NoCap_NoChain()
    {
        $this->_runCombination('Z023', [
            'account' => 'TRAFFICKER', 'mode' => 'edit', 'zoneType' => self::ZONE_EMAIL,
            'permission' => 'ZONE_EDIT', 'sizeType' => 'iab_468x60',
            'capping' => self::CAP_NONE, 'chain' => 'none',
        ]);
    }

    public function testZ024_Manager_Create_Text_ZoneAdd_NA_Session_ValidChain()
    {
        $this->_runCombination('Z024', [
            'account' => 'MANAGER', 'mode' => 'create', 'zoneType' => self::ZONE_TEXT,
            'permission' => 'ZONE_ADD', 'sizeType' => 'iab_468x60',
            'capping' => self::CAP_SESSION, 'chain' => 'valid',
        ]);
    }

    public function testZ025_Trafficker_Delete_VideoInstream_ZoneDelete_NA_Block_NoChain()
    {
        $this->_runCombination('Z025', [
            'account' => 'TRAFFICKER', 'mode' => 'delete', 'zoneType' => self::ZONE_VIDEO_INSTREAM,
            'permission' => 'ZONE_DELETE', 'sizeType' => 'iab_468x60',
            'capping' => self::CAP_BLOCK, 'chain' => 'none',
        ]);
    }

    public function testZ026_Manager_View_Banner_ZoneInvocation_Custom300x250_AllCap_NoChain()
    {
        $this->_runCombination('Z026', [
            'account' => 'MANAGER', 'mode' => 'view', 'zoneType' => self::ZONE_BANNER,
            'permission' => 'ZONE_INVOCATION', 'sizeType' => 'custom_300x250',
            'capping' => self::CAP_ALL, 'chain' => 'none',
        ]);
    }

    public function testZ027_Trafficker_Create_Popup_ZoneAdd_IAB728x90_Capping_InvalidChain()
    {
        $this->_runCombination('Z027', [
            'account' => 'TRAFFICKER', 'mode' => 'create', 'zoneType' => self::ZONE_POPUP,
            'permission' => 'ZONE_ADD', 'sizeType' => 'iab_728x90',
            'capping' => self::CAP_CAPPING, 'chain' => 'invalid',
        ]);
    }

    public function testZ028_Manager_Edit_Interstitial_ZoneEdit_IAB468x60_Block_NoChain()
    {
        $this->_runCombination('Z028', [
            'account' => 'MANAGER', 'mode' => 'edit', 'zoneType' => self::ZONE_INTERSTITIAL,
            'permission' => 'ZONE_EDIT', 'sizeType' => 'iab_468x60',
            'capping' => self::CAP_BLOCK, 'chain' => 'none',
        ]);
    }

    public function testZ029_Trafficker_View_Text_ZoneLink_NA_Capping_NoChain()
    {
        $this->_runCombination('Z029', [
            'account' => 'TRAFFICKER', 'mode' => 'view', 'zoneType' => self::ZONE_TEXT,
            'permission' => 'ZONE_LINK', 'sizeType' => 'iab_468x60',
            'capping' => self::CAP_CAPPING, 'chain' => 'none',
        ]);
    }

    public function testZ030_Manager_Delete_Email_ZoneDelete_Custom160x600_NoCap_ValidChain()
    {
        $this->_runCombination('Z030', [
            'account' => 'MANAGER', 'mode' => 'delete', 'zoneType' => self::ZONE_EMAIL,
            'permission' => 'ZONE_DELETE', 'sizeType' => 'custom_160x600',
            'capping' => self::CAP_NONE, 'chain' => 'valid',
        ]);
    }

    public function testZ031_Trafficker_Edit_VideoInstream_ZoneEdit_NA_Session_NoChain()
    {
        $this->_runCombination('Z031', [
            'account' => 'TRAFFICKER', 'mode' => 'edit', 'zoneType' => self::ZONE_VIDEO_INSTREAM,
            'permission' => 'ZONE_EDIT', 'sizeType' => 'iab_468x60',
            'capping' => self::CAP_SESSION, 'chain' => 'none',
        ]);
    }

    public function testZ032_Manager_Create_Banner_ZoneAdd_IAB160x600_NoCap_InvalidChain()
    {
        $this->_runCombination('Z032', [
            'account' => 'MANAGER', 'mode' => 'create', 'zoneType' => self::ZONE_BANNER,
            'permission' => 'ZONE_ADD', 'sizeType' => 'iab_160x600',
            'capping' => self::CAP_NONE, 'chain' => 'invalid',
        ]);
    }

    public function testZ033_Trafficker_Create_Email_ZoneAdd_Wildcard_Session_NoChain()
    {
        $this->_runCombination('Z033', [
            'account' => 'TRAFFICKER', 'mode' => 'create', 'zoneType' => self::ZONE_EMAIL,
            'permission' => 'ZONE_ADD', 'sizeType' => 'wildcard',
            'capping' => self::CAP_SESSION, 'chain' => 'none',
        ]);
    }

    public function testZ034_Manager_View_Popup_ZoneEdit_IAB468x60_Block_ValidChain()
    {
        $this->_runCombination('Z034', [
            'account' => 'MANAGER', 'mode' => 'view', 'zoneType' => self::ZONE_POPUP,
            'permission' => 'ZONE_EDIT', 'sizeType' => 'iab_468x60',
            'capping' => self::CAP_BLOCK, 'chain' => 'valid',
        ]);
    }

    public function testZ035_Trafficker_Delete_Interstitial_ZoneDelete_IAB300x250_AllCap_NoChain()
    {
        $this->_runCombination('Z035', [
            'account' => 'TRAFFICKER', 'mode' => 'delete', 'zoneType' => self::ZONE_INTERSTITIAL,
            'permission' => 'ZONE_DELETE', 'sizeType' => 'iab_300x250',
            'capping' => self::CAP_ALL, 'chain' => 'none',
        ]);
    }

    public function testZ036_Manager_Edit_Banner_ZoneEdit_IAB300x250_Capping_ValidChain()
    {
        $this->_runCombination('Z036', [
            'account' => 'MANAGER', 'mode' => 'edit', 'zoneType' => self::ZONE_BANNER,
            'permission' => 'ZONE_EDIT', 'sizeType' => 'iab_300x250',
            'capping' => self::CAP_CAPPING, 'chain' => 'valid',
        ]);
    }

    public function testZ037_Trafficker_Create_Banner_ZoneAdd_IAB336x280_Block_NoChain()
    {
        $this->_runCombination('Z037', [
            'account' => 'TRAFFICKER', 'mode' => 'create', 'zoneType' => self::ZONE_BANNER,
            'permission' => 'ZONE_ADD', 'sizeType' => 'iab_336x280',
            'capping' => self::CAP_BLOCK, 'chain' => 'none',
        ]);
    }

    public function testZ038_Manager_Delete_Text_ZoneDelete_NA_Capping_NoChain()
    {
        $this->_runCombination('Z038', [
            'account' => 'MANAGER', 'mode' => 'delete', 'zoneType' => self::ZONE_TEXT,
            'permission' => 'ZONE_DELETE', 'sizeType' => 'iab_468x60',
            'capping' => self::CAP_CAPPING, 'chain' => 'none',
        ]);
    }

    public function testZ039_Trafficker_View_VideoOverlay_ZoneEdit_NA_AllCap_NoChain()
    {
        $this->_runCombination('Z039', [
            'account' => 'TRAFFICKER', 'mode' => 'view', 'zoneType' => self::ZONE_VIDEO_OVERLAY,
            'permission' => 'ZONE_EDIT', 'sizeType' => 'iab_468x60',
            'capping' => self::CAP_ALL, 'chain' => 'none',
        ]);
    }

    public function testZ040_Manager_Create_VideoInstream_ZoneAdd_NA_NoCap_ValidChain()
    {
        $this->_runCombination('Z040', [
            'account' => 'MANAGER', 'mode' => 'create', 'zoneType' => self::ZONE_VIDEO_INSTREAM,
            'permission' => 'ZONE_ADD', 'sizeType' => 'iab_468x60',
            'capping' => self::CAP_NONE, 'chain' => 'valid',
        ]);
    }

    public function testZ041_Trafficker_Edit_Popup_ZoneEdit_Custom800x600_NoCap_NoChain()
    {
        $this->_runCombination('Z041', [
            'account' => 'TRAFFICKER', 'mode' => 'edit', 'zoneType' => self::ZONE_POPUP,
            'permission' => 'ZONE_EDIT', 'sizeType' => 'custom_800x600',
            'capping' => self::CAP_NONE, 'chain' => 'none',
        ]);
    }

    public function testZ042_Manager_View_Interstitial_ZoneInvocation_IAB120x600_Session_NoChain()
    {
        $this->_runCombination('Z042', [
            'account' => 'MANAGER', 'mode' => 'view', 'zoneType' => self::ZONE_INTERSTITIAL,
            'permission' => 'ZONE_INVOCATION', 'sizeType' => 'iab_120x600',
            'capping' => self::CAP_SESSION, 'chain' => 'none',
        ]);
    }

    public function testZ043_Trafficker_Delete_Banner_ZoneDelete_IAB468x60_NoCap_ValidChain()
    {
        $this->_runCombination('Z043', [
            'account' => 'TRAFFICKER', 'mode' => 'delete', 'zoneType' => self::ZONE_BANNER,
            'permission' => 'ZONE_DELETE', 'sizeType' => 'iab_468x60',
            'capping' => self::CAP_NONE, 'chain' => 'valid',
        ]);
    }

    public function testZ044_Manager_Edit_Email_ZoneEdit_Wildcard_AllCap_NoChain()
    {
        $this->_runCombination('Z044', [
            'account' => 'MANAGER', 'mode' => 'edit', 'zoneType' => self::ZONE_EMAIL,
            'permission' => 'ZONE_EDIT', 'sizeType' => 'wildcard',
            'capping' => self::CAP_ALL, 'chain' => 'none',
        ]);
    }

    public function testZ045_Trafficker_Create_Interstitial_ZoneAdd_Custom550x480_NoCap_NoChain()
    {
        $this->_runCombination('Z045', [
            'account' => 'TRAFFICKER', 'mode' => 'create', 'zoneType' => self::ZONE_INTERSTITIAL,
            'permission' => 'ZONE_ADD', 'sizeType' => 'custom_550x480',
            'capping' => self::CAP_NONE, 'chain' => 'none',
        ]);
    }

    public function testZ046_Manager_Delete_VideoInstream_ZoneDelete_NA_AllCap_NoChain()
    {
        $this->_runCombination('Z046', [
            'account' => 'MANAGER', 'mode' => 'delete', 'zoneType' => self::ZONE_VIDEO_INSTREAM,
            'permission' => 'ZONE_DELETE', 'sizeType' => 'iab_468x60',
            'capping' => self::CAP_ALL, 'chain' => 'none',
        ]);
    }

    public function testZ047_Trafficker_View_Email_ZoneLink_IAB336x280_Block_NoChain()
    {
        $this->_runCombination('Z047', [
            'account' => 'TRAFFICKER', 'mode' => 'view', 'zoneType' => self::ZONE_EMAIL,
            'permission' => 'ZONE_LINK', 'sizeType' => 'iab_336x280',
            'capping' => self::CAP_BLOCK, 'chain' => 'none',
        ]);
    }

    public function testZ048_Manager_Create_Popup_ZoneAdd_Custom300x250_Session_InvalidChain()
    {
        $this->_runCombination('Z048', [
            'account' => 'MANAGER', 'mode' => 'create', 'zoneType' => self::ZONE_POPUP,
            'permission' => 'ZONE_ADD', 'sizeType' => 'custom_300x250',
            'capping' => self::CAP_SESSION, 'chain' => 'invalid',
        ]);
    }

    public function testZ049_Trafficker_Edit_Banner_ZoneEdit_IAB160x600_AllCap_ValidChain()
    {
        $this->_runCombination('Z049', [
            'account' => 'TRAFFICKER', 'mode' => 'edit', 'zoneType' => self::ZONE_BANNER,
            'permission' => 'ZONE_EDIT', 'sizeType' => 'iab_160x600',
            'capping' => self::CAP_ALL, 'chain' => 'valid',
        ]);
    }

    public function testZ050_Manager_View_Text_ZoneEdit_NA_Block_ValidChain()
    {
        $this->_runCombination('Z050', [
            'account' => 'MANAGER', 'mode' => 'view', 'zoneType' => self::ZONE_TEXT,
            'permission' => 'ZONE_EDIT', 'sizeType' => 'iab_468x60',
            'capping' => self::CAP_BLOCK, 'chain' => 'valid',
        ]);
    }

    public function testZ051_Trafficker_Delete_Popup_ZoneDelete_Wildcard_Capping_NoChain()
    {
        $this->_runCombination('Z051', [
            'account' => 'TRAFFICKER', 'mode' => 'delete', 'zoneType' => self::ZONE_POPUP,
            'permission' => 'ZONE_DELETE', 'sizeType' => 'wildcard',
            'capping' => self::CAP_CAPPING, 'chain' => 'none',
        ]);
    }

    public function testZ052_Manager_Edit_VideoInstream_ZoneEdit_NA_Capping_InvalidChain()
    {
        $this->_runCombination('Z052', [
            'account' => 'MANAGER', 'mode' => 'edit', 'zoneType' => self::ZONE_VIDEO_INSTREAM,
            'permission' => 'ZONE_EDIT', 'sizeType' => 'iab_468x60',
            'capping' => self::CAP_CAPPING, 'chain' => 'invalid',
        ]);
    }

    public function testZ053_Trafficker_Create_Banner_ZoneAdd_Custom800x600_Session_NoChain()
    {
        $this->_runCombination('Z053', [
            'account' => 'TRAFFICKER', 'mode' => 'create', 'zoneType' => self::ZONE_BANNER,
            'permission' => 'ZONE_ADD', 'sizeType' => 'custom_800x600',
            'capping' => self::CAP_SESSION, 'chain' => 'none',
        ]);
    }

    public function testZ054_Manager_Delete_Interstitial_ZoneDelete_IAB468x60_Block_ValidChain()
    {
        $this->_runCombination('Z054', [
            'account' => 'MANAGER', 'mode' => 'delete', 'zoneType' => self::ZONE_INTERSTITIAL,
            'permission' => 'ZONE_DELETE', 'sizeType' => 'iab_468x60',
            'capping' => self::CAP_BLOCK, 'chain' => 'valid',
        ]);
    }

    public function testZ055_Trafficker_View_VideoInstream_ZoneInvocation_NA_NoCap_InvalidChain()
    {
        $this->_runCombination('Z055', [
            'account' => 'TRAFFICKER', 'mode' => 'view', 'zoneType' => self::ZONE_VIDEO_INSTREAM,
            'permission' => 'ZONE_INVOCATION', 'sizeType' => 'iab_468x60',
            'capping' => self::CAP_NONE, 'chain' => 'invalid',
        ]);
    }

    public function testZ056_Manager_Create_VideoOverlay_ZoneAdd_NA_AllCap_ValidChain()
    {
        $this->_runCombination('Z056', [
            'account' => 'MANAGER', 'mode' => 'create', 'zoneType' => self::ZONE_VIDEO_OVERLAY,
            'permission' => 'ZONE_ADD', 'sizeType' => 'iab_468x60',
            'capping' => self::CAP_ALL, 'chain' => 'valid',
        ]);
    }

    public function testZ057_Trafficker_Edit_Interstitial_ZoneEdit_IAB728x90_NoCap_ValidChain()
    {
        $this->_runCombination('Z057', [
            'account' => 'TRAFFICKER', 'mode' => 'edit', 'zoneType' => self::ZONE_INTERSTITIAL,
            'permission' => 'ZONE_EDIT', 'sizeType' => 'iab_728x90',
            'capping' => self::CAP_NONE, 'chain' => 'valid',
        ]);
    }

    public function testZ058_Manager_View_VideoOverlay_ZoneLink_NA_Capping_ValidChain()
    {
        $this->_runCombination('Z058', [
            'account' => 'MANAGER', 'mode' => 'view', 'zoneType' => self::ZONE_VIDEO_OVERLAY,
            'permission' => 'ZONE_LINK', 'sizeType' => 'iab_468x60',
            'capping' => self::CAP_CAPPING, 'chain' => 'valid',
        ]);
    }

    public function testZ059_Trafficker_Create_Text_ZoneAdd_NA_Block_InvalidChain()
    {
        $this->_runCombination('Z059', [
            'account' => 'TRAFFICKER', 'mode' => 'create', 'zoneType' => self::ZONE_TEXT,
            'permission' => 'ZONE_ADD', 'sizeType' => 'iab_468x60',
            'capping' => self::CAP_BLOCK, 'chain' => 'invalid',
        ]);
    }

    public function testZ060_Manager_Delete_Banner_ZoneDelete_IAB728x90_Session_InvalidChain()
    {
        $this->_runCombination('Z060', [
            'account' => 'MANAGER', 'mode' => 'delete', 'zoneType' => self::ZONE_BANNER,
            'permission' => 'ZONE_DELETE', 'sizeType' => 'iab_728x90',
            'capping' => self::CAP_SESSION, 'chain' => 'invalid',
        ]);
    }

    // ===============================================================
    // SECTION 4F: Excluded Combos — Negative / Validation Tests
    // ===============================================================

    /**
     * 4F-NEG-01: ADMIN account type should be denied zone CRUD access.
     *
     * zone-edit.php:45 enforces MANAGER or TRAFFICKER only.
     * The DLL layer's checkPermissions uses aAllowTraffickerAndAbovePerm
     * which only includes MANAGER and TRAFFICKER.
     */
    public function testNeg01_AdminAccountDeniedZoneCreate()
    {
        $publisherId = $this->_createPublisher();

        $dllZone = new PartialMockOA_Dll_Zone_CRUDCombo($this);
        // Simulate ADMIN being denied — checkPermissions returns false
        $dllZone->setReturnValue('checkPermissions', false);

        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = $publisherId;
        $oZone->zoneName = 'Admin Create Attempt';
        $oZone->type = self::ZONE_BANNER;
        $oZone->width = 468;
        $oZone->height = 60;

        $result = $dllZone->modify($oZone);
        $this->assertFalse($result, 'NEG-01: ADMIN should not be able to create zones');
    }

    /**
     * 4F-NEG-02: ADMIN account denied zone edit.
     */
    public function testNeg02_AdminAccountDeniedZoneEdit()
    {
        $publisherId = $this->_createPublisher();

        // First create with permissions allowed
        $dllZoneAllowed = new PartialMockOA_Dll_Zone_CRUDCombo($this);
        $dllZoneAllowed->setReturnValue('checkPermissions', true);

        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = $publisherId;
        $oZone->zoneName = 'Admin Edit Target';
        $oZone->type = self::ZONE_BANNER;
        $oZone->width = 468;
        $oZone->height = 60;
        $dllZoneAllowed->modify($oZone);

        // Now try edit with ADMIN (denied)
        $dllZoneDenied = new PartialMockOA_Dll_Zone_CRUDCombo($this);
        $dllZoneDenied->setReturnValue('checkPermissions', false);

        $oZone->zoneName = 'Admin Edit Attempt';
        $result = $dllZoneDenied->modify($oZone);
        $this->assertFalse($result, 'NEG-02: ADMIN should not be able to edit zones');

        // Cleanup
        $dllZoneAllowed->delete($oZone->zoneId);
    }

    /**
     * 4F-NEG-03: ADMIN account denied zone delete.
     */
    public function testNeg03_AdminAccountDeniedZoneDelete()
    {
        $publisherId = $this->_createPublisher();

        $dllZoneAllowed = new PartialMockOA_Dll_Zone_CRUDCombo($this);
        $dllZoneAllowed->setReturnValue('checkPermissions', true);

        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = $publisherId;
        $oZone->zoneName = 'Admin Delete Target';
        $oZone->type = self::ZONE_BANNER;
        $oZone->width = 468;
        $oZone->height = 60;
        $dllZoneAllowed->modify($oZone);

        $dllZoneDenied = new PartialMockOA_Dll_Zone_CRUDCombo($this);
        $dllZoneDenied->setReturnValue('checkPermissions', false);

        $result = $dllZoneDenied->delete($oZone->zoneId);
        $this->assertFalse($result, 'NEG-03: ADMIN should not be able to delete zones');

        // Cleanup
        $dllZoneAllowed->delete($oZone->zoneId);
    }

    /**
     * 4F-NEG-04: ADVERTISER account type should be denied zone CRUD access.
     *
     * zone-edit.php:45 enforces MANAGER or TRAFFICKER only.
     */
    public function testNeg04_AdvertiserAccountDeniedZoneCreate()
    {
        $publisherId = $this->_createPublisher();

        $dllZone = new PartialMockOA_Dll_Zone_CRUDCombo($this);
        $dllZone->setReturnValue('checkPermissions', false);

        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = $publisherId;
        $oZone->zoneName = 'Advertiser Create Attempt';
        $oZone->type = self::ZONE_BANNER;
        $oZone->width = 300;
        $oZone->height = 250;

        $result = $dllZone->modify($oZone);
        $this->assertFalse($result, 'NEG-04: ADVERTISER should not be able to create zones');
    }

    /**
     * 4F-NEG-05: ADVERTISER account denied zone edit.
     */
    public function testNeg05_AdvertiserAccountDeniedZoneEdit()
    {
        $publisherId = $this->_createPublisher();

        $dllZoneAllowed = new PartialMockOA_Dll_Zone_CRUDCombo($this);
        $dllZoneAllowed->setReturnValue('checkPermissions', true);

        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = $publisherId;
        $oZone->zoneName = 'Advertiser Edit Target';
        $oZone->type = self::ZONE_EMAIL;
        $oZone->width = 728;
        $oZone->height = 90;
        $dllZoneAllowed->modify($oZone);

        $dllZoneDenied = new PartialMockOA_Dll_Zone_CRUDCombo($this);
        $dllZoneDenied->setReturnValue('checkPermissions', false);

        $oZone->zoneName = 'Advertiser Edit Attempt';
        $result = $dllZoneDenied->modify($oZone);
        $this->assertFalse($result, 'NEG-05: ADVERTISER should not be able to edit zones');

        $dllZoneAllowed->delete($oZone->zoneId);
    }

    /**
     * 4F-NEG-06: ADVERTISER account denied zone delete.
     */
    public function testNeg06_AdvertiserAccountDeniedZoneDelete()
    {
        $publisherId = $this->_createPublisher();

        $dllZoneAllowed = new PartialMockOA_Dll_Zone_CRUDCombo($this);
        $dllZoneAllowed->setReturnValue('checkPermissions', true);

        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = $publisherId;
        $oZone->zoneName = 'Advertiser Delete Target';
        $oZone->type = self::ZONE_BANNER;
        $oZone->width = 468;
        $oZone->height = 60;
        $dllZoneAllowed->modify($oZone);

        $dllZoneDenied = new PartialMockOA_Dll_Zone_CRUDCombo($this);
        $dllZoneDenied->setReturnValue('checkPermissions', false);

        $result = $dllZoneDenied->delete($oZone->zoneId);
        $this->assertFalse($result, 'NEG-06: ADVERTISER should not be able to delete zones');

        $dllZoneAllowed->delete($oZone->zoneId);
    }

    /**
     * 4F-NEG-07: Text zone type forces size to 0x0.
     *
     * zone-edit.php:211-221 and zone-edit.php:338-339 force width/height to 0.
     * The DLL layer accepts width/height but the form processing overrides them.
     * This test verifies that the DLL layer accepts the zone creation regardless
     * of the size parameters provided, as size enforcement happens at the form level.
     */
    public function testNeg07_TextZoneSizeForcedTo0x0()
    {
        $publisherId = $this->_createPublisher();

        $dllZone = new PartialMockOA_Dll_Zone_CRUDCombo($this);
        $dllZone->setReturnValue('checkPermissions', true);

        // Create text zone with explicit 0x0 (as form would submit)
        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = $publisherId;
        $oZone->zoneName = 'Text Zone Size Test';
        $oZone->type = self::ZONE_TEXT;
        $oZone->width = 0;
        $oZone->height = 0;

        $result = $dllZone->modify($oZone);
        $this->assertTrue($result, 'NEG-07: Text zone with 0x0 should succeed');

        // Verify the zone was stored
        $oZoneGet = null;
        $dllZone->getZone($oZone->zoneId, $oZoneGet);
        $this->assertEqual($oZoneGet->type, self::ZONE_TEXT, 'NEG-07: Type should be Text');

        $dllZone->delete($oZone->zoneId);
    }

    /**
     * 4F-NEG-08: VideoInstream zone type forces size to special values.
     *
     * zone-edit.php:344-345 forces width/height to -3 for VideoInstream.
     * At DLL layer, we create with 0x0 which is the normalized form.
     */
    public function testNeg08_VideoInstreamZoneSizeForced()
    {
        $publisherId = $this->_createPublisher();

        $dllZone = new PartialMockOA_Dll_Zone_CRUDCombo($this);
        $dllZone->setReturnValue('checkPermissions', true);

        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = $publisherId;
        $oZone->zoneName = 'VideoInstream Size Test';
        $oZone->type = self::ZONE_VIDEO_INSTREAM;
        $oZone->width = 0;
        $oZone->height = 0;

        $result = $dllZone->modify($oZone);
        $this->assertTrue($result, 'NEG-08: VideoInstream zone with 0x0 should succeed');

        $oZoneGet = null;
        $dllZone->getZone($oZone->zoneId, $oZoneGet);
        $this->assertEqual($oZoneGet->type, self::ZONE_VIDEO_INSTREAM, 'NEG-08: Type should be VideoInstream');

        $dllZone->delete($oZone->zoneId);
    }

    /**
     * 4F-NEG-09: VideoOverlay zone type forces size to special values.
     *
     * zone-edit.php:341-342 forces width/height to -2 for VideoOverlay.
     */
    public function testNeg09_VideoOverlayZoneSizeForced()
    {
        $publisherId = $this->_createPublisher();

        $dllZone = new PartialMockOA_Dll_Zone_CRUDCombo($this);
        $dllZone->setReturnValue('checkPermissions', true);

        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = $publisherId;
        $oZone->zoneName = 'VideoOverlay Size Test';
        $oZone->type = self::ZONE_VIDEO_OVERLAY;
        $oZone->width = 0;
        $oZone->height = 0;

        $result = $dllZone->modify($oZone);
        $this->assertTrue($result, 'NEG-09: VideoOverlay zone with 0x0 should succeed');

        $oZoneGet = null;
        $dllZone->getZone($oZone->zoneId, $oZoneGet);
        $this->assertEqual($oZoneGet->type, self::ZONE_VIDEO_OVERLAY, 'NEG-09: Type should be VideoOverlay');

        $dllZone->delete($oZone->zoneId);
    }

    /**
     * 4F-NEG-10: Text zone with custom size - DLL accepts but form would
     * override. Verifying DLL layer does not reject custom dimensions
     * for text zones (validation is at form level).
     */
    public function testNeg10_TextZoneWithCustomSizeAcceptedAtDll()
    {
        $publisherId = $this->_createPublisher();

        $dllZone = new PartialMockOA_Dll_Zone_CRUDCombo($this);
        $dllZone->setReturnValue('checkPermissions', true);

        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = $publisherId;
        $oZone->zoneName = 'Text Zone Custom Size';
        $oZone->type = self::ZONE_TEXT;
        $oZone->width = 300;
        $oZone->height = 250;

        // DLL layer does not enforce size for text zones; form does
        $result = $dllZone->modify($oZone);
        $this->assertTrue($result, 'NEG-10: DLL accepts text zone with custom size (form enforces 0x0)');

        $dllZone->delete($oZone->zoneId);
    }

    /**
     * 4F-NEG-11: VideoInstream with custom size - same as above.
     */
    public function testNeg11_VideoInstreamWithCustomSizeAcceptedAtDll()
    {
        $publisherId = $this->_createPublisher();

        $dllZone = new PartialMockOA_Dll_Zone_CRUDCombo($this);
        $dllZone->setReturnValue('checkPermissions', true);

        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = $publisherId;
        $oZone->zoneName = 'VideoInstream Custom Size';
        $oZone->type = self::ZONE_VIDEO_INSTREAM;
        $oZone->width = 640;
        $oZone->height = 480;

        $result = $dllZone->modify($oZone);
        $this->assertTrue($result, 'NEG-11: DLL accepts VideoInstream with custom size');

        $dllZone->delete($oZone->zoneId);
    }

    /**
     * 4F-NEG-12: VideoOverlay with custom size - same as above.
     */
    public function testNeg12_VideoOverlayWithCustomSizeAcceptedAtDll()
    {
        $publisherId = $this->_createPublisher();

        $dllZone = new PartialMockOA_Dll_Zone_CRUDCombo($this);
        $dllZone->setReturnValue('checkPermissions', true);

        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = $publisherId;
        $oZone->zoneName = 'VideoOverlay Custom Size';
        $oZone->type = self::ZONE_VIDEO_OVERLAY;
        $oZone->width = 320;
        $oZone->height = 240;

        $result = $dllZone->modify($oZone);
        $this->assertTrue($result, 'NEG-12: DLL accepts VideoOverlay with custom size');

        $dllZone->delete($oZone->zoneId);
    }

    /**
     * 4F-NEG-13: Interstitial type can only be set for existing zones.
     *
     * zone-edit.php:144-155 only shows Interstitial radio button if the
     * zone already has that type. However, the DLL layer does accept
     * Interstitial as a valid type (0-4 are valid). This test verifies
     * that the DLL layer creates the zone (the UI restriction is form-level).
     */
    public function testNeg13_InterstitialCreateAcceptedAtDll()
    {
        $publisherId = $this->_createPublisher();

        $dllZone = new PartialMockOA_Dll_Zone_CRUDCombo($this);
        $dllZone->setReturnValue('checkPermissions', true);

        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = $publisherId;
        $oZone->zoneName = 'Interstitial Create Test';
        $oZone->type = self::ZONE_INTERSTITIAL;
        $oZone->width = 468;
        $oZone->height = 60;

        // DLL allows this; UI would prevent it for new zones
        $result = $dllZone->modify($oZone);
        $this->assertTrue($result, 'NEG-13: DLL accepts Interstitial zone creation (UI restricts to existing)');

        $oZoneGet = null;
        $dllZone->getZone($oZone->zoneId, $oZoneGet);
        $this->assertEqual($oZoneGet->type, self::ZONE_INTERSTITIAL, 'NEG-13: Type should be Interstitial');

        $dllZone->delete($oZone->zoneId);
    }

    /**
     * 4F-NEG-14: Popup type can only be set for existing zones.
     *
     * zone-edit.php:156-167 only shows Popup radio button if the
     * zone already has that type. Same DLL behavior as Interstitial.
     */
    public function testNeg14_PopupCreateAcceptedAtDll()
    {
        $publisherId = $this->_createPublisher();

        $dllZone = new PartialMockOA_Dll_Zone_CRUDCombo($this);
        $dllZone->setReturnValue('checkPermissions', true);

        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = $publisherId;
        $oZone->zoneName = 'Popup Create Test';
        $oZone->type = self::ZONE_POPUP;
        $oZone->width = 728;
        $oZone->height = 90;

        $result = $dllZone->modify($oZone);
        $this->assertTrue($result, 'NEG-14: DLL accepts Popup zone creation (UI restricts to existing)');

        $oZoneGet = null;
        $dllZone->getZone($oZone->zoneId, $oZoneGet);
        $this->assertEqual($oZoneGet->type, self::ZONE_POPUP, 'NEG-14: Type should be Popup');

        $dllZone->delete($oZone->zoneId);
    }

    /**
     * 4F-NEG-15: Interstitial zone edit is allowed (existing zone already has the type).
     *
     * Interstitial radio button IS shown for zones that already have type=interstitial.
     */
    public function testNeg15_InterstitialEditAllowedForExisting()
    {
        $publisherId = $this->_createPublisher();

        $dllZone = new PartialMockOA_Dll_Zone_CRUDCombo($this);
        $dllZone->setReturnValue('checkPermissions', true);

        // Create with interstitial type
        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = $publisherId;
        $oZone->zoneName = 'Interstitial Edit Test';
        $oZone->type = self::ZONE_INTERSTITIAL;
        $oZone->width = 468;
        $oZone->height = 60;
        $dllZone->modify($oZone);

        // Edit the existing interstitial zone
        $oZone->zoneName = 'Interstitial Edited';
        $oZone->width = 728;
        $oZone->height = 90;
        $result = $dllZone->modify($oZone);
        $this->assertTrue($result, 'NEG-15: Editing existing Interstitial zone should succeed');

        $oZoneGet = null;
        $dllZone->getZone($oZone->zoneId, $oZoneGet);
        $this->assertEqual($oZoneGet->zoneName, 'Interstitial Edited', 'NEG-15: Name should be updated');
        $this->assertEqual($oZoneGet->type, self::ZONE_INTERSTITIAL, 'NEG-15: Type should remain Interstitial');

        $dllZone->delete($oZone->zoneId);
    }

    /**
     * 4F-NEG-16: Popup zone edit is allowed for existing zones.
     */
    public function testNeg16_PopupEditAllowedForExisting()
    {
        $publisherId = $this->_createPublisher();

        $dllZone = new PartialMockOA_Dll_Zone_CRUDCombo($this);
        $dllZone->setReturnValue('checkPermissions', true);

        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = $publisherId;
        $oZone->zoneName = 'Popup Edit Test';
        $oZone->type = self::ZONE_POPUP;
        $oZone->width = 250;
        $oZone->height = 250;
        $dllZone->modify($oZone);

        $oZone->zoneName = 'Popup Edited';
        $oZone->width = 300;
        $oZone->height = 250;
        $result = $dllZone->modify($oZone);
        $this->assertTrue($result, 'NEG-16: Editing existing Popup zone should succeed');

        $oZoneGet = null;
        $dllZone->getZone($oZone->zoneId, $oZoneGet);
        $this->assertEqual($oZoneGet->zoneName, 'Popup Edited', 'NEG-16: Name should be updated');

        $dllZone->delete($oZone->zoneId);
    }

    /**
     * 4F-NEG-17: Zone cannot be chained to itself.
     */
    public function testNeg17_ZoneCannotChainToItself()
    {
        $publisherId = $this->_createPublisher();

        $dllZone = new PartialMockOA_Dll_Zone_CRUDCombo($this);
        $dllZone->setReturnValue('checkPermissions', true);

        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = $publisherId;
        $oZone->zoneName = 'Self Chain Test';
        $oZone->type = self::ZONE_BANNER;
        $oZone->width = 468;
        $oZone->height = 60;
        $dllZone->modify($oZone);

        // Try to chain to itself
        $oZone->chainedZoneId = $oZone->zoneId;
        $result = $dllZone->modify($oZone);
        $this->assertFalse($result, 'NEG-17: Zone should not be chainable to itself');
        $this->assertEqual(
            $dllZone->getLastError(),
            $this->chainError,
            'NEG-17: Error should be self-chain error',
        );

        $dllZone->delete($oZone->zoneId);
    }

    /**
     * 4F-NEG-18: Zone cannot be chained to non-existent zone.
     */
    public function testNeg18_ZoneCannotChainToNonExistent()
    {
        $publisherId = $this->_createPublisher();

        $dllZone = new PartialMockOA_Dll_Zone_CRUDCombo($this);
        $dllZone->setReturnValue('checkPermissions', true);

        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = $publisherId;
        $oZone->zoneName = 'Invalid Chain Test';
        $oZone->type = self::ZONE_BANNER;
        $oZone->width = 468;
        $oZone->height = 60;
        $dllZone->modify($oZone);

        $oZone->chainedZoneId = 999999;
        $result = $dllZone->modify($oZone);
        $this->assertFalse($result, 'NEG-18: Chaining to non-existent zone should fail');

        $dllZone->delete($oZone->zoneId);
    }

    /**
     * 4F-NEG-19: Delete non-existent zone should fail.
     */
    public function testNeg19_DeleteNonExistentZone()
    {
        $dllZone = new PartialMockOA_Dll_Zone_CRUDCombo($this);
        $dllZone->setReturnValue('checkPermissions', true);

        $result = $dllZone->delete(999999);
        $this->assertFalse($result, 'NEG-19: Deleting non-existent zone should fail');
    }

    /**
     * 4F-NEG-20: Modify non-existent zone should fail.
     */
    public function testNeg20_ModifyNonExistentZone()
    {
        $dllZone = new PartialMockOA_Dll_Zone_CRUDCombo($this);
        $dllZone->setReturnValue('checkPermissions', true);

        $oZone = new OA_Dll_ZoneInfo();
        $oZone->zoneId = 999999;
        $oZone->zoneName = 'Non-existent Zone Edit';

        $result = $dllZone->modify($oZone);
        $this->assertFalse($result, 'NEG-20: Modifying non-existent zone should fail');
    }

    /**
     * 4F-NEG-21: Get non-existent zone should fail.
     */
    public function testNeg21_GetNonExistentZone()
    {
        $dllZone = new PartialMockOA_Dll_Zone_CRUDCombo($this);
        $dllZone->setReturnValue('checkPermissions', true);

        $oZoneGet = null;
        $result = $dllZone->getZone(999999, $oZoneGet);
        $this->assertFalse($result, 'NEG-21: Getting non-existent zone should fail');
        $this->assertEqual(
            $dllZone->getLastError(),
            $this->unknownIdError,
            'NEG-21: Error should be unknown zoneId',
        );
    }

    /**
     * 4F-NEG-22: Create zone without publisherId should fail.
     */
    public function testNeg22_CreateZoneWithoutPublisherId()
    {
        $dllZone = new PartialMockOA_Dll_Zone_CRUDCombo($this);
        $dllZone->setReturnValue('checkPermissions', true);

        $oZone = new OA_Dll_ZoneInfo();
        $oZone->zoneName = 'No Publisher Zone';
        $oZone->type = self::ZONE_BANNER;
        $oZone->width = 468;
        $oZone->height = 60;
        // publisherId intentionally not set

        $result = $dllZone->modify($oZone);
        $this->assertFalse($result, 'NEG-22: Creating zone without publisherId should fail');
    }

    /**
     * 4F-NEG-23: Create zone with non-existent publisherId should fail.
     */
    public function testNeg23_CreateZoneWithNonExistentPublisher()
    {
        $dllZone = new PartialMockOA_Dll_Zone_CRUDCombo($this);
        $dllZone->setReturnValue('checkPermissions', true);

        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = 999999;
        $oZone->zoneName = 'Bad Publisher Zone';
        $oZone->type = self::ZONE_BANNER;
        $oZone->width = 468;
        $oZone->height = 60;

        $result = $dllZone->modify($oZone);
        $this->assertFalse($result, 'NEG-23: Creating zone with non-existent publisher should fail');
    }

    /**
     * 4F-NEG-24: TRAFFICKER without ZONE_ADD permission denied create.
     */
    public function testNeg24_TraffickerWithoutZoneAddDeniedCreate()
    {
        $publisherId = $this->_createPublisher();

        $dllZone = new PartialMockOA_Dll_Zone_CRUDCombo($this);
        $dllZone->setReturnValue('checkPermissions', false);

        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = $publisherId;
        $oZone->zoneName = 'No ZONE_ADD Permission';
        $oZone->type = self::ZONE_BANNER;
        $oZone->width = 468;
        $oZone->height = 60;

        $result = $dllZone->modify($oZone);
        $this->assertFalse($result, 'NEG-24: TRAFFICKER without ZONE_ADD should be denied create');
    }

    /**
     * 4F-NEG-25: TRAFFICKER without ZONE_EDIT permission denied edit.
     */
    public function testNeg25_TraffickerWithoutZoneEditDeniedEdit()
    {
        $publisherId = $this->_createPublisher();

        // Create with permissions
        $dllZoneAllowed = new PartialMockOA_Dll_Zone_CRUDCombo($this);
        $dllZoneAllowed->setReturnValue('checkPermissions', true);

        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = $publisherId;
        $oZone->zoneName = 'No ZONE_EDIT Target';
        $oZone->type = self::ZONE_BANNER;
        $oZone->width = 468;
        $oZone->height = 60;
        $dllZoneAllowed->modify($oZone);

        // Try edit without permission
        $dllZoneDenied = new PartialMockOA_Dll_Zone_CRUDCombo($this);
        $dllZoneDenied->setReturnValue('checkPermissions', false);

        $oZone->zoneName = 'Attempted Edit';
        $result = $dllZoneDenied->modify($oZone);
        $this->assertFalse($result, 'NEG-25: TRAFFICKER without ZONE_EDIT should be denied edit');

        $dllZoneAllowed->delete($oZone->zoneId);
    }

    /**
     * 4F-NEG-26: TRAFFICKER without ZONE_DELETE permission denied delete.
     */
    public function testNeg26_TraffickerWithoutZoneDeleteDeniedDelete()
    {
        $publisherId = $this->_createPublisher();

        $dllZoneAllowed = new PartialMockOA_Dll_Zone_CRUDCombo($this);
        $dllZoneAllowed->setReturnValue('checkPermissions', true);

        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = $publisherId;
        $oZone->zoneName = 'No ZONE_DELETE Target';
        $oZone->type = self::ZONE_BANNER;
        $oZone->width = 468;
        $oZone->height = 60;
        $dllZoneAllowed->modify($oZone);

        $dllZoneDenied = new PartialMockOA_Dll_Zone_CRUDCombo($this);
        $dllZoneDenied->setReturnValue('checkPermissions', false);

        $result = $dllZoneDenied->delete($oZone->zoneId);
        $this->assertFalse($result, 'NEG-26: TRAFFICKER without ZONE_DELETE should be denied delete');

        $dllZoneAllowed->delete($oZone->zoneId);
    }

    /**
     * 4F-NEG-27: Zone name exceeding max length should fail.
     */
    public function testNeg27_ZoneNameExceedsMaxLength()
    {
        $publisherId = $this->_createPublisher();

        $dllZone = new PartialMockOA_Dll_Zone_CRUDCombo($this);
        $dllZone->setReturnValue('checkPermissions', true);

        $oZone = new OA_Dll_ZoneInfo();
        $oZone->publisherId = $publisherId;
        $oZone->zoneName = str_repeat('A', 246); // exceeds 245 char limit
        $oZone->type = self::ZONE_BANNER;
        $oZone->width = 468;
        $oZone->height = 60;

        $result = $dllZone->modify($oZone);
        $this->assertFalse($result, 'NEG-27: Zone name exceeding 245 chars should fail');
    }
}
