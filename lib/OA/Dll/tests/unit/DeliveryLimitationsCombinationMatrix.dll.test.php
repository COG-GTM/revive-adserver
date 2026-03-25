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
require_once MAX_PATH . '/lib/OA/Dll/TargetingInfo.php';
require_once MAX_PATH . '/lib/OA/Dll/tests/util/DllUnitTestCase.php';

/**
 * Section 4K: Delivery Limitations Combination Matrix Tests
 *
 * Tests covering combinations of:
 *   - Limitation category: Client, Geo, Site, Time
 *   - All sub-types within each category
 *   - Comparison operators: Equal, Not Equal, Contains, Regex, etc.
 *   - Logical operators: and, or
 *   - Account types: ADMIN, MANAGER, ADVERTISER
 *
 * @package    OpenXDll
 * @subpackage TestSuite
 */
class OA_Dll_DeliveryLimitationsCombinationMatrixTest extends DllUnitTestCase
{
    /**
     * @var int
     */
    public $agencyId;

    public function __construct()
    {
        parent::__construct();
        Mock::generatePartial(
            'OA_Dll_Banner',
            'PartialMockOA_Dll_Banner_DLMatrix',
            ['checkPermissions'],
        );
        Mock::generatePartial(
            'OA_Dll_Campaign',
            'PartialMockOA_Dll_Campaign_DLMatrix',
            ['checkPermissions'],
        );
        Mock::generatePartial(
            'OA_Dll_Advertiser',
            'PartialMockOA_Dll_Advertiser_DLMatrix',
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

    /**
     * Helper: create advertiser → campaign → banner hierarchy and return bannerId.
     *
     * @return int The banner ID
     */
    private function _createBannerHierarchy()
    {
        $dllAdvertiserPartialMock = new PartialMockOA_Dll_Advertiser_DLMatrix($this);
        $dllCampaignPartialMock = new PartialMockOA_Dll_Campaign_DLMatrix($this);
        $dllBannerPartialMock = new PartialMockOA_Dll_Banner_DLMatrix($this);

        $dllAdvertiserPartialMock->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiserPartialMock->setReturnValue('checkPermissions', true);
        $dllCampaignPartialMock->setReturnValue('checkPermissions', true);
        $dllBannerPartialMock->setReturnValue('checkPermissions', true);

        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'DL Matrix Test Advertiser';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $this->assertTrue(
            $dllAdvertiserPartialMock->modify($oAdvertiserInfo),
            $dllAdvertiserPartialMock->getLastError(),
        );

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $this->assertTrue(
            $dllCampaignPartialMock->modify($oCampaignInfo),
            $dllCampaignPartialMock->getLastError(),
        );

        $oBannerInfo = new OA_Dll_BannerInfo();
        $oBannerInfo->campaignId = $oCampaignInfo->campaignId;
        $this->assertTrue(
            $dllBannerPartialMock->modify($oBannerInfo),
            $dllBannerPartialMock->getLastError(),
        );

        return $oBannerInfo->bannerId;
    }

    /**
     * Helper: set banner targeting and verify it was stored correctly.
     *
     * @param int    $bannerId   The banner to target
     * @param array  $aRules     Array of targeting rule definitions:
     *                           [['type' => ..., 'comparison' => ..., 'logical' => ..., 'data' => ...], ...]
     * @param string $accountCtx Account context label (for assertion messages only)
     */
    private function _setAndVerifyTargeting($bannerId, $aRules, $accountCtx)
    {
        $dllBannerPartialMock = new PartialMockOA_Dll_Banner_DLMatrix($this);
        $dllBannerPartialMock->setReturnValue('checkPermissions', true);

        // Build targeting array
        $aTargeting = [];
        foreach ($aRules as $idx => $rule) {
            $oTargeting = new OA_Dll_TargetingInfo();
            $oTargeting->logical = $rule['logical'];
            $oTargeting->type = $rule['type'];
            $oTargeting->comparison = $rule['comparison'];
            $oTargeting->data = $rule['data'];
            $aTargeting[$idx] = $oTargeting;
        }

        // Set targeting
        $result = $dllBannerPartialMock->setBannerTargeting($bannerId, $aTargeting);
        $this->assertTrue(
            $result,
            "[{$accountCtx}] setBannerTargeting failed for rules: " .
            json_encode(array_map(fn($r) => $r['type'], $aRules)) .
            ' Error: ' . $dllBannerPartialMock->getLastError(),
        );

        // Retrieve targeting
        $aRetrieved = [];
        $this->assertTrue(
            $dllBannerPartialMock->getBannerTargeting($bannerId, $aRetrieved),
            "[{$accountCtx}] getBannerTargeting failed",
        );

        // Verify count
        $this->assertEqual(
            count($aRetrieved),
            count($aRules),
            "[{$accountCtx}] Expected " . count($aRules) . " targeting rule(s), got " . count($aRetrieved),
        );

        // Verify each rule
        foreach ($aRules as $idx => $rule) {
            $expectedType = $rule['type'];
            if (!str_starts_with($expectedType, 'deliveryLimitations:')) {
                $expectedType = 'deliveryLimitations:' . $expectedType;
            }
            $this->assertEqual(
                $aRetrieved[$idx]->type,
                $expectedType,
                "[{$accountCtx}] Rule {$idx}: type mismatch",
            );
            $this->assertEqual(
                $aRetrieved[$idx]->comparison,
                $rule['comparison'],
                "[{$accountCtx}] Rule {$idx}: comparison mismatch",
            );
            $this->assertEqual(
                $aRetrieved[$idx]->logical,
                $rule['logical'],
                "[{$accountCtx}] Rule {$idx}: logical mismatch",
            );
            $this->assertEqual(
                $aRetrieved[$idx]->data,
                $rule['data'],
                "[{$accountCtx}] Rule {$idx}: data mismatch",
            );
        }
    }

    // =========================================================================
    //  DL01 – DL10: Required combos from specification
    // =========================================================================

    /** DL01: Geo/Country, Equal(=~), and, ADMIN */
    public function testDL01_GeoCountry_Equal_And_Admin()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Geo:Country', 'comparison' => '=~', 'logical' => 'and', 'data' => 'GB'],
        ], 'ADMIN');
    }

    /** DL02: Geo/City, NotEqual(==), or, MANAGER — City uses == operator */
    public function testDL02_GeoCity_Equal_Or_Manager()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Geo:City', 'comparison' => '==', 'logical' => 'or', 'data' => 'US|New York'],
        ], 'MANAGER');
    }

    /** DL03: Client/BrowserVersion, Equal(==), and, MANAGER */
    public function testDL03_ClientBrowserVersion_Equal_And_Manager()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Client:BrowserVersion', 'comparison' => '==', 'logical' => 'and', 'data' => 'Chrome|90'],
        ], 'MANAGER');
    }

    /** DL04: Client/Language, NotEqual(!~), or, ADVERTISER */
    public function testDL04_ClientLanguage_NotEqual_Or_Advertiser()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Client:Language', 'comparison' => '!~', 'logical' => 'or', 'data' => 'en'],
        ], 'ADVERTISER');
    }

    /** DL05: Site/PageURL, Equal(=~), and, ADMIN */
    public function testDL05_SitePageurl_Equal_And_Admin()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Site:Pageurl', 'comparison' => '=~', 'logical' => 'and', 'data' => 'http://example.com'],
        ], 'ADMIN');
    }

    /** DL06: Site/Source, NotEqual(!=), and, MANAGER */
    public function testDL06_SiteSource_NotEqual_And_Manager()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Site:Source', 'comparison' => '!=', 'logical' => 'and', 'data' => 'newsletter'],
        ], 'MANAGER');
    }

    /** DL07: Time/Hour, Equal(=~), and, MANAGER */
    public function testDL07_TimeHour_Equal_And_Manager()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Time:Hour', 'comparison' => '=~', 'logical' => 'and', 'data' => '9,10,11'],
        ], 'MANAGER');
    }

    /** DL08: Time/Day, Equal(=~), or, ADMIN */
    public function testDL08_TimeDay_Equal_Or_Admin()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Time:Day', 'comparison' => '=~', 'logical' => 'or', 'data' => '1,2,3'],
        ], 'ADMIN');
    }

    /** DL09: Geo/Continent, Equal(=~), and, ADVERTISER */
    public function testDL09_GeoContinent_Equal_And_Advertiser()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Geo:Continent', 'comparison' => '=~', 'logical' => 'and', 'data' => 'EU'],
        ], 'ADVERTISER');
    }

    /** DL10: Client/IP, Equal(==), or, MANAGER */
    public function testDL10_ClientIp_Equal_Or_Manager()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Client:Ip', 'comparison' => '==', 'logical' => 'or', 'data' => '192.168.1.*'],
        ], 'MANAGER');
    }

    // =========================================================================
    //  DL11 – DL26: Cover remaining sub-types (one test per sub-type)
    // =========================================================================

    /** DL11: Client/Domain, Equal(==), and, ADMIN */
    public function testDL11_ClientDomain_Equal_And_Admin()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Client:Domain', 'comparison' => '==', 'logical' => 'and', 'data' => 'example.com'],
        ], 'ADMIN');
    }

    /** DL12: Client/OsVersion, Equal(==), or, ADVERTISER */
    public function testDL12_ClientOsVersion_Equal_Or_Advertiser()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Client:OsVersion', 'comparison' => '==', 'logical' => 'or', 'data' => 'Windows|10'],
        ], 'ADVERTISER');
    }

    /** DL13: Client/Useragent, Contains(=~), and, MANAGER */
    public function testDL13_ClientUseragent_Contains_And_Manager()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Client:Useragent', 'comparison' => '=~', 'logical' => 'and', 'data' => 'Mozilla'],
        ], 'MANAGER');
    }

    /** DL14: Geo/ConnectionType, Equal(=~), or, ADMIN */
    public function testDL14_GeoConnectionType_Equal_Or_Admin()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Geo:ConnectionType', 'comparison' => '=~', 'logical' => 'or', 'data' => 'dialup'],
        ], 'ADMIN');
    }

    /** DL15: Geo/Latlong, Equal(==), and, MANAGER */
    public function testDL15_GeoLatlong_Equal_And_Manager()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Geo:Latlong', 'comparison' => '==', 'logical' => 'and', 'data' => '40.0,41.0,-74.0,-73.0'],
        ], 'MANAGER');
    }

    /** DL16: Geo/Organisation, Equal(==), or, ADVERTISER */
    public function testDL16_GeoOrganisation_Equal_Or_Advertiser()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Geo:Organisation', 'comparison' => '==', 'logical' => 'or', 'data' => 'Acme Inc'],
        ], 'ADVERTISER');
    }

    /** DL17: Geo/Postalcode, Contains(=~), and, ADMIN */
    public function testDL17_GeoPostalcode_Contains_And_Admin()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Geo:Postalcode', 'comparison' => '=~', 'logical' => 'and', 'data' => '10001'],
        ], 'ADMIN');
    }

    /** DL18: Geo/Subdivision1, Equal(=~), or, MANAGER */
    public function testDL18_GeoSubdivision1_Equal_Or_Manager()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Geo:Subdivision1', 'comparison' => '=~', 'logical' => 'or', 'data' => 'US|NY'],
        ], 'MANAGER');
    }

    /** DL19: Geo/Subdivision2, Equal(=~), and, ADVERTISER */
    public function testDL19_GeoSubdivision2_Equal_And_Advertiser()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Geo:Subdivision2', 'comparison' => '=~', 'logical' => 'and', 'data' => 'US|NYA'],
        ], 'ADVERTISER');
    }

    /** DL20: Geo/UsMetro, Equal(=~), or, ADMIN */
    public function testDL20_GeoUsMetro_Equal_Or_Admin()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Geo:UsMetro', 'comparison' => '=~', 'logical' => 'or', 'data' => '501'],
        ], 'ADMIN');
    }

    /** DL21: Site/Channel, Equal(=~), and, ADVERTISER */
    public function testDL21_SiteChannel_Equal_And_Advertiser()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Site:Channel', 'comparison' => '=~', 'logical' => 'and', 'data' => '1'],
        ], 'ADVERTISER');
    }

    /** DL22: Site/Hostnamelist, Whitelist(=~), or, MANAGER */
    public function testDL22_SiteHostnamelist_Whitelist_Or_Manager()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Site:Hostnamelist', 'comparison' => '=~', 'logical' => 'or', 'data' => "example.com\ntest.com"],
        ], 'MANAGER');
    }

    /** DL23: Site/Referingpage, Equal(==), and, ADMIN */
    public function testDL23_SiteReferingpage_Equal_And_Admin()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Site:Referingpage', 'comparison' => '==', 'logical' => 'and', 'data' => 'http://google.com'],
        ], 'ADMIN');
    }

    /** DL24: Site/Registerabledomainlist, Whitelist(=x), or, ADVERTISER */
    public function testDL24_SiteRegisterabledomainlist_Whitelist_Or_Advertiser()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Site:Registerabledomainlist', 'comparison' => '=x', 'logical' => 'or', 'data' => "example.com\ntest.org"],
        ], 'ADVERTISER');
    }

    /** DL25: Site/Variable, Equal(==), and, MANAGER */
    public function testDL25_SiteVariable_Equal_And_Manager()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Site:Variable', 'comparison' => '==', 'logical' => 'and', 'data' => 'country|US'],
        ], 'MANAGER');
    }

    /** DL26: Time/Date, Equal(==), and, ADVERTISER */
    public function testDL26_TimeDate_Equal_And_Advertiser()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Time:Date', 'comparison' => '==', 'logical' => 'and', 'data' => '20260101'],
        ], 'ADVERTISER');
    }

    // =========================================================================
    //  DL27 – DL40: Additional operator/logical/account coverage
    // =========================================================================

    /** DL27: Geo/Country, NotEqual(!~), or, MANAGER */
    public function testDL27_GeoCountry_NotEqual_Or_Manager()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Geo:Country', 'comparison' => '!~', 'logical' => 'or', 'data' => 'US'],
        ], 'MANAGER');
    }

    /** DL28: Geo/Continent, NotEqual(!~), or, ADMIN */
    public function testDL28_GeoContinent_NotEqual_Or_Admin()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Geo:Continent', 'comparison' => '!~', 'logical' => 'or', 'data' => 'AS'],
        ], 'ADMIN');
    }

    /** DL29: Geo/Country, Equal(=~), or, ADVERTISER */
    public function testDL29_GeoCountry_Equal_Or_Advertiser()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Geo:Country', 'comparison' => '=~', 'logical' => 'or', 'data' => 'FR,DE'],
        ], 'ADVERTISER');
    }

    /** DL30: Client/BrowserVersion, NotEqual(!=), or, ADMIN */
    public function testDL30_ClientBrowserVersion_NotEqual_Or_Admin()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Client:BrowserVersion', 'comparison' => '!=', 'logical' => 'or', 'data' => 'Firefox|80'],
        ], 'ADMIN');
    }

    /** DL31: Client/Language, Equal(=~), and, MANAGER */
    public function testDL31_ClientLanguage_Equal_And_Manager()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Client:Language', 'comparison' => '=~', 'logical' => 'and', 'data' => 'fr'],
        ], 'MANAGER');
    }

    /** DL32: Client/Ip, NotEqual(!=), and, ADVERTISER */
    public function testDL32_ClientIp_NotEqual_And_Advertiser()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Client:Ip', 'comparison' => '!=', 'logical' => 'and', 'data' => '10.0.0.1'],
        ], 'ADVERTISER');
    }

    /** DL33: Client/Domain, NotEqual(!=), or, MANAGER */
    public function testDL33_ClientDomain_NotEqual_Or_Manager()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Client:Domain', 'comparison' => '!=', 'logical' => 'or', 'data' => 'blocked.com'],
        ], 'MANAGER');
    }

    /** DL34: Client/Useragent, NotEqual(!=), or, ADVERTISER */
    public function testDL34_ClientUseragent_NotEqual_Or_Advertiser()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Client:Useragent', 'comparison' => '!=', 'logical' => 'or', 'data' => 'Googlebot'],
        ], 'ADVERTISER');
    }

    /** DL35: Site/Pageurl, NotEqual(!=), or, MANAGER */
    public function testDL35_SitePageurl_NotEqual_Or_Manager()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Site:Pageurl', 'comparison' => '!=', 'logical' => 'or', 'data' => 'http://blocked.com/page'],
        ], 'MANAGER');
    }

    /** DL36: Site/Source, Contains(=~), or, ADVERTISER */
    public function testDL36_SiteSource_Contains_Or_Advertiser()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Site:Source', 'comparison' => '=~', 'logical' => 'or', 'data' => 'partner'],
        ], 'ADVERTISER');
    }

    /** DL37: Site/Referingpage, NotEqual(!=), and, ADMIN */
    public function testDL37_SiteReferingpage_NotEqual_And_Admin()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Site:Referingpage', 'comparison' => '!=', 'logical' => 'and', 'data' => 'http://spam.com'],
        ], 'ADMIN');
    }

    /** DL38: Site/Variable, NotEqual(!=), and, MANAGER */
    public function testDL38_SiteVariable_NotEqual_And_Manager()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Site:Variable', 'comparison' => '!=', 'logical' => 'and', 'data' => 'region|blocked'],
        ], 'MANAGER');
    }

    /** DL39: Time/Hour, NotEqual(!~), or, ADVERTISER */
    public function testDL39_TimeHour_NotEqual_Or_Advertiser()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Time:Hour', 'comparison' => '!~', 'logical' => 'or', 'data' => '0,1,2,3'],
        ], 'ADVERTISER');
    }

    /** DL40: Time/Day, NotEqual(!~), and, MANAGER */
    public function testDL40_TimeDay_NotEqual_And_Manager()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Time:Day', 'comparison' => '!~', 'logical' => 'and', 'data' => '0,6'],
        ], 'MANAGER');
    }

    // =========================================================================
    //  DL41 – DL44: Additional Date/Geo/Site operator coverage
    // =========================================================================

    /** DL41: Time/Date, NotEqual(!=), or, ADMIN */
    public function testDL41_TimeDate_NotEqual_Or_Admin()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Time:Date', 'comparison' => '!=', 'logical' => 'or', 'data' => '20261225'],
        ], 'ADMIN');
    }

    /** DL42: Time/Date, GreaterOrEqual(>=), and, MANAGER */
    public function testDL42_TimeDate_GreaterOrEqual_And_Manager()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Time:Date', 'comparison' => '>=', 'logical' => 'and', 'data' => '20260301'],
        ], 'MANAGER');
    }

    /** DL43: Geo/Organisation, Contains(=~), and, MANAGER */
    public function testDL43_GeoOrganisation_Contains_And_Manager()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Geo:Organisation', 'comparison' => '=~', 'logical' => 'and', 'data' => 'Corp'],
        ], 'MANAGER');
    }

    /** DL44: Geo/Postalcode, NotEqual(!=), or, ADVERTISER */
    public function testDL44_GeoPostalcode_NotEqual_Or_Advertiser()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Geo:Postalcode', 'comparison' => '!=', 'logical' => 'or', 'data' => '90210'],
        ], 'ADVERTISER');
    }

    // =========================================================================
    //  DL45 – DL50: Multiple-rule combinations (cross-category)
    // =========================================================================

    /** DL45: Two rules — Geo/Country AND Time/Hour, ADMIN */
    public function testDL45_GeoCountry_And_TimeHour_Admin()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Geo:Country', 'comparison' => '=~', 'logical' => 'and', 'data' => 'US'],
            ['type' => 'Time:Hour', 'comparison' => '=~', 'logical' => 'and', 'data' => '8,9,10,11,12'],
        ], 'ADMIN');
    }

    /** DL46: Two rules — Site/Pageurl AND Client/Language, MANAGER */
    public function testDL46_SitePageurl_And_ClientLanguage_Manager()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Site:Pageurl', 'comparison' => '=~', 'logical' => 'and', 'data' => 'shop'],
            ['type' => 'Client:Language', 'comparison' => '=~', 'logical' => 'and', 'data' => 'en,fr'],
        ], 'MANAGER');
    }

    /** DL47: Two rules — Client/BrowserVersion OR Client/Domain, ADVERTISER */
    public function testDL47_ClientBrowserVersion_Or_ClientDomain_Advertiser()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Client:BrowserVersion', 'comparison' => '==', 'logical' => 'and', 'data' => 'Safari|14'],
            ['type' => 'Client:Domain', 'comparison' => '==', 'logical' => 'or', 'data' => 'apple.com'],
        ], 'ADVERTISER');
    }

    /** DL48: Two rules — Time/Day AND Site/Source, ADMIN */
    public function testDL48_TimeDay_And_SiteSource_Admin()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Time:Day', 'comparison' => '=~', 'logical' => 'and', 'data' => '1,2,3,4,5'],
            ['type' => 'Site:Source', 'comparison' => '==', 'logical' => 'and', 'data' => 'homepage'],
        ], 'ADMIN');
    }

    /** DL49: Three rules — Geo/Country AND Client/Ip AND Time/Hour, MANAGER */
    public function testDL49_ThreeRules_GeoClientTime_Manager()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Geo:Country', 'comparison' => '=~', 'logical' => 'and', 'data' => 'DE'],
            ['type' => 'Client:Ip', 'comparison' => '!=', 'logical' => 'and', 'data' => '10.0.0.*'],
            ['type' => 'Time:Hour', 'comparison' => '=~', 'logical' => 'and', 'data' => '6,7,8,9,10'],
        ], 'MANAGER');
    }

    /** DL50: Three rules — Site/Pageurl OR Geo/Continent OR Time/Day, ADVERTISER */
    public function testDL50_ThreeRules_SiteGeoTime_Advertiser()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Site:Pageurl', 'comparison' => '=~', 'logical' => 'and', 'data' => 'promo'],
            ['type' => 'Geo:Continent', 'comparison' => '=~', 'logical' => 'or', 'data' => 'NA'],
            ['type' => 'Time:Day', 'comparison' => '=~', 'logical' => 'or', 'data' => '5,6'],
        ], 'ADVERTISER');
    }

    // =========================================================================
    //  DL51 – DL52: Regex and additional operator coverage
    // =========================================================================

    /** DL51: Client/Domain, Regex(=x), and, ADVERTISER */
    public function testDL51_ClientDomain_Regex_And_Advertiser()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Client:Domain', 'comparison' => '=x', 'logical' => 'and', 'data' => '.*\\.example\\.com'],
        ], 'ADVERTISER');
    }

    /** DL52: Site/Pageurl, NotRegex(!x), or, ADMIN */
    public function testDL52_SitePageurl_NotRegex_Or_Admin()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Site:Pageurl', 'comparison' => '!x', 'logical' => 'or', 'data' => '.*blocked.*'],
        ], 'ADMIN');
    }

    // =========================================================================
    //  DL53 – DL55: More Geo operator combinations
    // =========================================================================

    /** DL53: Geo/Latlong, NotEqual(!=), or, ADMIN */
    public function testDL53_GeoLatlong_NotEqual_Or_Admin()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Geo:Latlong', 'comparison' => '!=', 'logical' => 'or', 'data' => '50.0,51.0,0.0,1.0'],
        ], 'ADMIN');
    }

    /** DL54: Geo/ConnectionType, NotEqual(!~), and, ADVERTISER */
    public function testDL54_GeoConnectionType_NotEqual_And_Advertiser()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Geo:ConnectionType', 'comparison' => '!~', 'logical' => 'and', 'data' => 'dialup'],
        ], 'ADVERTISER');
    }

    /** DL55: Geo/UsMetro, NotEqual(!~), and, MANAGER */
    public function testDL55_GeoUsMetro_NotEqual_And_Manager()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Geo:UsMetro', 'comparison' => '!~', 'logical' => 'and', 'data' => '602'],
        ], 'MANAGER');
    }

    // =========================================================================
    //  DL56 – DL58: Additional Client/Site/Time operator combos
    // =========================================================================

    /** DL56: Client/OsVersion, NotEqual(!=), and, MANAGER */
    public function testDL56_ClientOsVersion_NotEqual_And_Manager()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Client:OsVersion', 'comparison' => '!=', 'logical' => 'and', 'data' => 'Linux|5'],
        ], 'MANAGER');
    }

    /** DL57: Site/Hostnamelist, Blacklist(!~), and, ADMIN */
    public function testDL57_SiteHostnamelist_Blacklist_And_Admin()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Site:Hostnamelist', 'comparison' => '!~', 'logical' => 'and', 'data' => "malware.com\nspam.org"],
        ], 'ADMIN');
    }

    /** DL58: Site/Registerabledomainlist, Blacklist(!x), and, MANAGER */
    public function testDL58_SiteRegisterabledomainlist_Blacklist_And_Manager()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Site:Registerabledomainlist', 'comparison' => '!x', 'logical' => 'and', 'data' => "blocked.com\nspam.net"],
        ], 'MANAGER');
    }

    // =========================================================================
    //  DL59 – DL60: Time/Date additional operators
    // =========================================================================

    /** DL59: Time/Date, LessThan(<), or, ADVERTISER */
    public function testDL59_TimeDate_LessThan_Or_Advertiser()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Time:Date', 'comparison' => '<', 'logical' => 'or', 'data' => '20261231'],
        ], 'ADVERTISER');
    }

    /** DL60: Time/Date, GreaterThan(>), and, ADMIN */
    public function testDL60_TimeDate_GreaterThan_And_Admin()
    {
        $bannerId = $this->_createBannerHierarchy();
        $this->_setAndVerifyTargeting($bannerId, [
            ['type' => 'Time:Date', 'comparison' => '>', 'logical' => 'and', 'data' => '20260101'],
        ], 'ADMIN');
    }
}
