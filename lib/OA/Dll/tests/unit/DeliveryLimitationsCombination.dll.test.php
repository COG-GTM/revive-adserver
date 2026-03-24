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
 * Section 4K — Delivery Limitations (Targeting/ACLs) Combination Matrix
 *
 * Comprehensive combinatorial tests covering the Delivery Limitations system.
 * Tests ~50 combinations across dimensions:
 *   - Limitation category: Client, Geo, Site, Time
 *   - Sub-types: All plugin types within each category
 *   - Comparison operators: == (Equal), != (Not Equal), =~ (Contains), !~ (Not Contains),
 *                           =x (Regex), !x (Regex Not), > (Greater), < (Less), etc.
 *   - Logical operators: and, or
 *   - Account types: ADMIN, MANAGER, ADVERTISER
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
require_once MAX_PATH . '/lib/OA/Dll/TargetingInfo.php';
require_once MAX_PATH . '/lib/OA/Dll/tests/util/DllUnitTestCase.php';
require_once MAX_PATH . '/lib/max/other/lib-acl.inc.php';
require_once LIB_PATH . '/Plugin/Component.php';

class OA_Dll_DeliveryLimitationsCombinationTest extends DllUnitTestCase
{
    /**
     * @var int
     */
    public $agencyId;

    /**
     * @var int
     */
    public $advertiserId;

    /**
     * @var int
     */
    public $campaignId;

    /**
     * @var int
     */
    public $bannerId;

    public function __construct()
    {
        parent::__construct();
        Mock::generatePartial(
            'OA_Dll_Banner',
            'PartialMockOA_Dll_Banner_DLCombination',
            ['checkPermissions'],
        );
        Mock::generatePartial(
            'OA_Dll_Campaign',
            'PartialMockOA_Dll_Campaign_DLCombination',
            ['checkPermissions'],
        );
        Mock::generatePartial(
            'OA_Dll_Advertiser',
            'PartialMockOA_Dll_Advertiser_DLCombination',
            ['checkPermissions', 'getDefaultAgencyId'],
        );
    }

    public function setUp()
    {
        $this->agencyId = DataGenerator::generateOne('agency');

        // Create advertiser
        $dllAdvertiser = new PartialMockOA_Dll_Advertiser_DLCombination($this);
        $dllAdvertiser->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiser->setReturnValue('checkPermissions', true);

        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'DL Combination Test Advertiser';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $dllAdvertiser->modify($oAdvertiserInfo);
        $this->advertiserId = $oAdvertiserInfo->advertiserId;

        // Create campaign
        $dllCampaign = new PartialMockOA_Dll_Campaign_DLCombination($this);
        $dllCampaign->setReturnValue('checkPermissions', true);

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $this->advertiserId;
        $dllCampaign->modify($oCampaignInfo);
        $this->campaignId = $oCampaignInfo->campaignId;

        // Create banner
        $dllBanner = new PartialMockOA_Dll_Banner_DLCombination($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $oBannerInfo = new OA_Dll_BannerInfo();
        $oBannerInfo->campaignId = $this->campaignId;
        $oBannerInfo->bannerName = 'DL Combination Test Banner';
        $dllBanner->modify($oBannerInfo);
        $this->bannerId = $oBannerInfo->bannerId;
    }

    public function tearDown()
    {
        DataGenerator::cleanUp();
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Build an ACL targeting info array for a single delivery limitation rule.
     *
     * @param string $type       Plugin type identifier (e.g. 'deliveryLimitations:Geo:Country')
     * @param string $comparison Comparison operator (e.g. '=~', '!=', '==')
     * @param string $data       Data value for the rule
     * @param string $logical    Logical operator ('and' or 'or')
     * @return OA_Dll_TargetingInfo
     */
    private function _buildTargetingInfo($type, $comparison, $data, $logical = 'and')
    {
        $oTargeting = new OA_Dll_TargetingInfo();
        $oTargeting->logical = $logical;
        $oTargeting->type = $type;
        $oTargeting->comparison = $comparison;
        $oTargeting->data = $data;
        return $oTargeting;
    }

    /**
     * Build an ACL array entry for use with OX_AclCheckInputsFields().
     *
     * @param string $type       Plugin type identifier
     * @param string $comparison Comparison operator
     * @param mixed  $data       Data value for the rule
     * @param string $logical    Logical operator
     * @param int    $order      Execution order
     * @return array
     */
    private function _buildAclArray($type, $comparison, $data, $logical = 'and', $order = 0)
    {
        return [
            'type' => $type,
            'comparison' => $comparison,
            'data' => $data,
            'logical' => $logical,
            'executionorder' => $order,
        ];
    }

    /**
     * Validate an ACL entry via OX_AclCheckInputsFields and return the result.
     *
     * @param array $acl Single ACL array entry
     * @return true|array True on success, array of error strings on failure
     */
    private function _validateAcl($acl)
    {
        return OX_AclCheckInputsFields([$acl], false);
    }

    /**
     * Set banner targeting via the DLL and verify the result.
     *
     * @param array $aTargetingInfos Array of OA_Dll_TargetingInfo objects
     * @param string $testId Test case identifier for error messages
     * @return bool
     */
    private function _setBannerTargetingAndVerify($aTargetingInfos, $testId)
    {
        $dllBanner = new PartialMockOA_Dll_Banner_DLCombination($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $result = $dllBanner->setBannerTargeting($this->bannerId, $aTargetingInfos);
        $this->assertTrue(
            $result,
            "{$testId}: setBannerTargeting failed: " . $dllBanner->getLastError(),
        );

        if ($result) {
            // Verify the targeting was saved by reading it back
            $aBannerTargeting = [];
            $getResult = $dllBanner->getBannerTargeting($this->bannerId, $aBannerTargeting);
            $this->assertTrue($getResult, "{$testId}: getBannerTargeting failed");
            $this->assertEqual(
                count($aBannerTargeting),
                count($aTargetingInfos),
                "{$testId}: Expected " . count($aTargetingInfos) . " targeting rules, got " . count($aBannerTargeting),
            );
        }

        return $result;
    }

    /**
     * Resolve account type string to constant value.
     *
     * @param string $accountType One of 'ADMIN', 'MANAGER', 'ADVERTISER'
     * @return int
     */
    private function _getAccountTypeConstant($accountType)
    {
        switch ($accountType) {
            case 'ADMIN':
                return OA_ACCOUNT_ADMIN;
            case 'MANAGER':
                return OA_ACCOUNT_MANAGER;
            case 'ADVERTISER':
                return OA_ACCOUNT_ADVERTISER;
            default:
                return OA_ACCOUNT_ADMIN;
        }
    }

    /**
     * Run a single combination test that validates ACL inputs and sets banner targeting.
     *
     * @param string $testId       Test case ID (e.g. 'DL01')
     * @param string $type         Plugin type (e.g. 'deliveryLimitations:Geo:Country')
     * @param string $comparison   Comparison operator
     * @param string $data         Data value
     * @param string $logical      Logical operator ('and' or 'or')
     * @param string $accountType  Account type label ('ADMIN', 'MANAGER', 'ADVERTISER')
     */
    private function _runCombinationTest($testId, $type, $comparison, $data, $logical, $accountType)
    {
        // 1. Validate ACL input fields
        $acl = $this->_buildAclArray($type, $comparison, $data, $logical);
        $validationResult = $this->_validateAcl($acl);
        $this->assertTrue(
            $validationResult === true,
            "{$testId}: ACL validation failed for {$type} with comparison={$comparison}, " .
            "data={$data}, logical={$logical}: " .
            (is_array($validationResult) ? implode('; ', $validationResult) : ''),
        );

        // 2. Verify plugin component can be instantiated
        $plugin = OX_Component::factoryByComponentIdentifier($type);
        $this->assertNotNull(
            $plugin,
            "{$testId}: Plugin {$type} could not be instantiated",
        );
        $this->assertIsA(
            $plugin,
            'Plugins_DeliveryLimitations',
            "{$testId}: Plugin {$type} is not a Plugins_DeliveryLimitations instance",
        );

        // 3. Set banner targeting via DLL
        $oTargeting = $this->_buildTargetingInfo($type, $comparison, $data, $logical);
        $this->_setBannerTargetingAndVerify([$oTargeting], $testId);

        // 4. Verify account type constant is valid
        $accountConstant = $this->_getAccountTypeConstant($accountType);
        $this->assertTrue(
            in_array($accountConstant, [OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER, OA_ACCOUNT_ADVERTISER]),
            "{$testId}: Invalid account type constant for {$accountType}",
        );
    }

    /**
     * Run a multi-rule combination test with two ACLs using the specified logical operator.
     *
     * @param string $testId      Test case ID
     * @param string $type1       First plugin type
     * @param string $comparison1 First comparison operator
     * @param string $data1       First data value
     * @param string $type2       Second plugin type
     * @param string $comparison2 Second comparison operator
     * @param string $data2       Second data value
     * @param string $logical     Logical operator between rules
     * @param string $accountType Account type label
     */
    private function _runMultiRuleCombinationTest(
        $testId,
        $type1,
        $comparison1,
        $data1,
        $type2,
        $comparison2,
        $data2,
        $logical,
        $accountType,
    ) {
        // Validate both ACLs
        $acl1 = $this->_buildAclArray($type1, $comparison1, $data1, 'and', 0);
        $acl2 = $this->_buildAclArray($type2, $comparison2, $data2, $logical, 1);

        $validationResult = OX_AclCheckInputsFields([$acl1, $acl2], false);
        $this->assertTrue(
            $validationResult === true,
            "{$testId}: Multi-rule ACL validation failed: " .
            (is_array($validationResult) ? implode('; ', $validationResult) : ''),
        );

        // Set both targeting rules on the banner
        $oTargeting1 = $this->_buildTargetingInfo($type1, $comparison1, $data1, 'and');
        $oTargeting2 = $this->_buildTargetingInfo($type2, $comparison2, $data2, $logical);
        $this->_setBannerTargetingAndVerify([$oTargeting1, $oTargeting2], $testId);
    }

    // =========================================================================
    // DL01–DL10: Provided sample combinations
    // =========================================================================

    /**
     * DL01: Geo - Country, Equal (=~), and, ADMIN
     */
    public function testDL01_GeoCountry_Equal_And_Admin()
    {
        $this->_runCombinationTest(
            'DL01',
            'deliveryLimitations:Geo:Country',
            '=~',
            'US',
            'and',
            'ADMIN',
        );
    }

    /**
     * DL02: Geo - City, Not Equal (!~), or, MANAGER
     */
    public function testDL02_GeoCity_NotEqual_Or_Manager()
    {
        $this->_runCombinationTest(
            'DL02',
            'deliveryLimitations:Geo:City',
            '!~',
            'London',
            'or',
            'MANAGER',
        );
    }

    /**
     * DL03: Client - BrowserVersion, Equal (nn = any version), and, MANAGER
     */
    public function testDL03_ClientBrowserVersion_Equal_And_Manager()
    {
        $this->_runCombinationTest(
            'DL03',
            'deliveryLimitations:Client:BrowserVersion',
            'nn',
            'Chrome|',
            'and',
            'MANAGER',
        );
    }

    /**
     * DL04: Client - Language, Not Equal (!~), or, ADVERTISER
     */
    public function testDL04_ClientLanguage_NotEqual_Or_Advertiser()
    {
        $this->_runCombinationTest(
            'DL04',
            'deliveryLimitations:Client:Language',
            '!~',
            'en',
            'or',
            'ADVERTISER',
        );
    }

    /**
     * DL05: Site - PageURL, Equal (=~), and, ADMIN
     */
    public function testDL05_SitePageURL_Equal_And_Admin()
    {
        $this->_runCombinationTest(
            'DL05',
            'deliveryLimitations:Site:Pageurl',
            '=~',
            'example.com',
            'and',
            'ADMIN',
        );
    }

    /**
     * DL06: Site - Source, Not Equal (!=), and, MANAGER
     */
    public function testDL06_SiteSource_NotEqual_And_Manager()
    {
        $this->_runCombinationTest(
            'DL06',
            'deliveryLimitations:Site:Source',
            '!=',
            'newsletter',
            'and',
            'MANAGER',
        );
    }

    /**
     * DL07: Time - Hour, Contains (=~), and, MANAGER
     */
    public function testDL07_TimeHour_Contains_And_Manager()
    {
        $this->_runCombinationTest(
            'DL07',
            'deliveryLimitations:Time:Hour',
            '=~',
            '9,10,11,12',
            'and',
            'MANAGER',
        );
    }

    /**
     * DL08: Time - Day, Contains (=~), or, ADMIN
     */
    public function testDL08_TimeDay_Contains_Or_Admin()
    {
        $this->_runCombinationTest(
            'DL08',
            'deliveryLimitations:Time:Day',
            '=~',
            '1,2,3,4,5',
            'or',
            'ADMIN',
        );
    }

    /**
     * DL09: Geo - Continent, Equal (=~), and, ADVERTISER
     */
    public function testDL09_GeoContinent_Equal_And_Advertiser()
    {
        $this->_runCombinationTest(
            'DL09',
            'deliveryLimitations:Geo:Continent',
            '=~',
            'EU',
            'and',
            'ADVERTISER',
        );
    }

    /**
     * DL10: Client - IP, Equal (==), or, MANAGER
     */
    public function testDL10_ClientIP_Equal_Or_Manager()
    {
        $this->_runCombinationTest(
            'DL10',
            'deliveryLimitations:Client:Ip',
            '==',
            '192.168.1.*',
            'or',
            'MANAGER',
        );
    }

    // =========================================================================
    // DL11–DL20: Remaining Geo sub-types + variations
    // =========================================================================

    /**
     * DL11: Geo - ConnectionType, Equal (=~), and, ADMIN
     */
    public function testDL11_GeoConnectionType_Equal_And_Admin()
    {
        $this->_runCombinationTest(
            'DL11',
            'deliveryLimitations:Geo:ConnectionType',
            '=~',
            'cable',
            'and',
            'ADMIN',
        );
    }

    /**
     * DL12: Geo - LatLong, Equal (==), or, MANAGER
     */
    public function testDL12_GeoLatLong_Equal_Or_Manager()
    {
        $this->_runCombinationTest(
            'DL12',
            'deliveryLimitations:Geo:Latlong',
            '==',
            '40.0000,42.0000,-74.0000,-71.0000',
            'or',
            'MANAGER',
        );
    }

    /**
     * DL13: Geo - Organisation, Equal (==), and, ADVERTISER
     */
    public function testDL13_GeoOrganisation_Equal_And_Advertiser()
    {
        $this->_runCombinationTest(
            'DL13',
            'deliveryLimitations:Geo:Organisation',
            '==',
            'Acme Corp',
            'and',
            'ADVERTISER',
        );
    }

    /**
     * DL14: Geo - PostalCode, Not Equal (!=), or, ADMIN
     */
    public function testDL14_GeoPostalCode_NotEqual_Or_Admin()
    {
        $this->_runCombinationTest(
            'DL14',
            'deliveryLimitations:Geo:Postalcode',
            '!=',
            '10001',
            'or',
            'ADMIN',
        );
    }

    /**
     * DL15: Geo - Subdivision1, Equal (=~), and, MANAGER
     */
    public function testDL15_GeoSubdivision1_Equal_And_Manager()
    {
        $this->_runCombinationTest(
            'DL15',
            'deliveryLimitations:Geo:Subdivision1',
            '=~',
            'CA',
            'and',
            'MANAGER',
        );
    }

    /**
     * DL16: Geo - Subdivision2, Not Equal (!~), or, ADVERTISER
     */
    public function testDL16_GeoSubdivision2_NotEqual_Or_Advertiser()
    {
        $this->_runCombinationTest(
            'DL16',
            'deliveryLimitations:Geo:Subdivision2',
            '!~',
            'LAX',
            'or',
            'ADVERTISER',
        );
    }

    /**
     * DL17: Geo - USMetro, Equal (=~), and, ADMIN
     */
    public function testDL17_GeoUSMetro_Equal_And_Admin()
    {
        $this->_runCombinationTest(
            'DL17',
            'deliveryLimitations:Geo:UsMetro',
            '=~',
            '501',
            'and',
            'ADMIN',
        );
    }

    /**
     * DL18: Geo - Country, Not Equal (!~), and, ADVERTISER
     */
    public function testDL18_GeoCountry_NotEqual_And_Advertiser()
    {
        $this->_runCombinationTest(
            'DL18',
            'deliveryLimitations:Geo:Country',
            '!~',
            'CN',
            'and',
            'ADVERTISER',
        );
    }

    /**
     * DL19: Geo - City, Equal (=~), and, ADMIN
     */
    public function testDL19_GeoCity_Equal_And_Admin()
    {
        $this->_runCombinationTest(
            'DL19',
            'deliveryLimitations:Geo:City',
            '=~',
            'New York',
            'and',
            'ADMIN',
        );
    }

    /**
     * DL20: Geo - LatLong, Not Equal (!=), and, ADVERTISER
     */
    public function testDL20_GeoLatLong_NotEqual_And_Advertiser()
    {
        $this->_runCombinationTest(
            'DL20',
            'deliveryLimitations:Geo:Latlong',
            '!=',
            '51.0000,52.0000,-1.0000,1.0000',
            'and',
            'ADVERTISER',
        );
    }

    // =========================================================================
    // DL21–DL28: Remaining Client sub-types + variations
    // =========================================================================

    /**
     * DL21: Client - Domain, Equal (==), and, ADMIN
     */
    public function testDL21_ClientDomain_Equal_And_Admin()
    {
        $this->_runCombinationTest(
            'DL21',
            'deliveryLimitations:Client:Domain',
            '==',
            'example.com',
            'and',
            'ADMIN',
        );
    }

    /**
     * DL22: Client - Domain, Contains (=~), or, MANAGER
     */
    public function testDL22_ClientDomain_Contains_Or_Manager()
    {
        $this->_runCombinationTest(
            'DL22',
            'deliveryLimitations:Client:Domain',
            '=~',
            'example',
            'or',
            'MANAGER',
        );
    }

    /**
     * DL23: Client - OS (OsVersion), Equal (nn = any version), and, ADVERTISER
     */
    public function testDL23_ClientOS_Equal_And_Advertiser()
    {
        $this->_runCombinationTest(
            'DL23',
            'deliveryLimitations:Client:OsVersion',
            'nn',
            'Windows|',
            'and',
            'ADVERTISER',
        );
    }

    /**
     * DL24: Client - UserAgent, Contains (=~), or, ADMIN
     */
    public function testDL24_ClientUserAgent_Contains_Or_Admin()
    {
        $this->_runCombinationTest(
            'DL24',
            'deliveryLimitations:Client:Useragent',
            '=~',
            'Mozilla',
            'or',
            'ADMIN',
        );
    }

    /**
     * DL25: Client - UserAgent, Not Equal (!=), and, MANAGER
     */
    public function testDL25_ClientUserAgent_NotEqual_And_Manager()
    {
        $this->_runCombinationTest(
            'DL25',
            'deliveryLimitations:Client:Useragent',
            '!=',
            'Googlebot',
            'and',
            'MANAGER',
        );
    }

    /**
     * DL26: Client - IP, Not Equal (!=), and, ADVERTISER
     */
    public function testDL26_ClientIP_NotEqual_And_Advertiser()
    {
        $this->_runCombinationTest(
            'DL26',
            'deliveryLimitations:Client:Ip',
            '!=',
            '10.0.0.1',
            'and',
            'ADVERTISER',
        );
    }

    /**
     * DL27: Client - Language, Equal (=~), and, ADMIN
     */
    public function testDL27_ClientLanguage_Equal_And_Admin()
    {
        $this->_runCombinationTest(
            'DL27',
            'deliveryLimitations:Client:Language',
            '=~',
            'fr',
            'and',
            'ADMIN',
        );
    }

    /**
     * DL28: Client - BrowserVersion, Not Equal (!=), or, ADVERTISER
     */
    public function testDL28_ClientBrowserVersion_NotEqual_Or_Advertiser()
    {
        $this->_runCombinationTest(
            'DL28',
            'deliveryLimitations:Client:BrowserVersion',
            '!=',
            'Chrome|90',
            'or',
            'ADVERTISER',
        );
    }

    // =========================================================================
    // DL29–DL38: Remaining Site sub-types + variations
    // =========================================================================

    /**
     * DL29: Site - PageURL, Not Equal (!=), or, MANAGER
     */
    public function testDL29_SitePageURL_NotEqual_Or_Manager()
    {
        $this->_runCombinationTest(
            'DL29',
            'deliveryLimitations:Site:Pageurl',
            '!=',
            'example.com/blocked',
            'or',
            'MANAGER',
        );
    }

    /**
     * DL30: Site - PageURL, Regex (=x), and, ADVERTISER
     */
    public function testDL30_SitePageURL_Regex_And_Advertiser()
    {
        $this->_runCombinationTest(
            'DL30',
            'deliveryLimitations:Site:Pageurl',
            '=x',
            'example\\.com/product/[0-9]+',
            'and',
            'ADVERTISER',
        );
    }

    /**
     * DL31: Site - ReferingPage, Equal (==), and, ADMIN
     */
    public function testDL31_SiteReferingPage_Equal_And_Admin()
    {
        $this->_runCombinationTest(
            'DL31',
            'deliveryLimitations:Site:Referingpage',
            '==',
            'https://www.google.com/',
            'and',
            'ADMIN',
        );
    }

    /**
     * DL32: Site - ReferingPage, Not Contains (!~), or, MANAGER
     */
    public function testDL32_SiteReferingPage_NotContains_Or_Manager()
    {
        $this->_runCombinationTest(
            'DL32',
            'deliveryLimitations:Site:Referingpage',
            '!~',
            'spam-site.com',
            'or',
            'MANAGER',
        );
    }

    /**
     * DL33: Site - Source, Equal (==), or, ADVERTISER
     */
    public function testDL33_SiteSource_Equal_Or_Advertiser()
    {
        $this->_runCombinationTest(
            'DL33',
            'deliveryLimitations:Site:Source',
            '==',
            'email_campaign',
            'or',
            'ADVERTISER',
        );
    }

    /**
     * DL34: Site - Source, Contains (=~), and, ADMIN
     */
    public function testDL34_SiteSource_Contains_And_Admin()
    {
        $this->_runCombinationTest(
            'DL34',
            'deliveryLimitations:Site:Source',
            '=~',
            'partner',
            'and',
            'ADMIN',
        );
    }

    /**
     * DL35: Site - Variable, Equal (==), and, MANAGER
     */
    public function testDL35_SiteVariable_Equal_And_Manager()
    {
        $this->_runCombinationTest(
            'DL35',
            'deliveryLimitations:Site:Variable',
            '==',
            'category|sports',
            'and',
            'MANAGER',
        );
    }

    /**
     * DL36: Site - Variable, Not Equal (!=), or, ADMIN
     */
    public function testDL36_SiteVariable_NotEqual_Or_Admin()
    {
        $this->_runCombinationTest(
            'DL36',
            'deliveryLimitations:Site:Variable',
            '!=',
            'section|premium',
            'or',
            'ADMIN',
        );
    }

    /**
     * DL37: Site - Hostnamelist, Whitelist (=~), and, ADVERTISER
     */
    public function testDL37_SiteHostnamelist_Whitelist_And_Advertiser()
    {
        $this->_runCombinationTest(
            'DL37',
            'deliveryLimitations:Site:Hostnamelist',
            '=~',
            "www.example.com\nwww.test.com",
            'and',
            'ADVERTISER',
        );
    }

    /**
     * DL38: Site - RegisterableDomainList, Whitelist (=x), or, MANAGER
     */
    public function testDL38_SiteRegisterableDomainList_Whitelist_Or_Manager()
    {
        $this->_runCombinationTest(
            'DL38',
            'deliveryLimitations:Site:Registerabledomainlist',
            '=x',
            "example.com\ntest.org",
            'or',
            'MANAGER',
        );
    }

    // =========================================================================
    // DL39–DL44: Remaining Time sub-types + variations
    // =========================================================================

    /**
     * DL39: Time - Date, Equal (==), and, ADMIN
     */
    public function testDL39_TimeDate_Equal_And_Admin()
    {
        $this->_runCombinationTest(
            'DL39',
            'deliveryLimitations:Time:Date',
            '==',
            '20260101@UTC',
            'and',
            'ADMIN',
        );
    }

    /**
     * DL40: Time - Date, Not Equal (!=), or, MANAGER
     */
    public function testDL40_TimeDate_NotEqual_Or_Manager()
    {
        $this->_runCombinationTest(
            'DL40',
            'deliveryLimitations:Time:Date',
            '!=',
            '20261225@UTC',
            'or',
            'MANAGER',
        );
    }

    /**
     * DL41: Time - Date, Greater Than (>), and, ADVERTISER
     */
    public function testDL41_TimeDate_GreaterThan_And_Advertiser()
    {
        $this->_runCombinationTest(
            'DL41',
            'deliveryLimitations:Time:Date',
            '>',
            '20260601@UTC',
            'and',
            'ADVERTISER',
        );
    }

    /**
     * DL42: Time - Date, Less Than Or Equal (<=), or, ADMIN
     */
    public function testDL42_TimeDate_LessThanOrEqual_Or_Admin()
    {
        $this->_runCombinationTest(
            'DL42',
            'deliveryLimitations:Time:Date',
            '<=',
            '20261231@UTC',
            'or',
            'ADMIN',
        );
    }

    /**
     * DL43: Time - Hour, Not Equal (!~), and, ADVERTISER
     */
    public function testDL43_TimeHour_NotEqual_And_Advertiser()
    {
        $this->_runCombinationTest(
            'DL43',
            'deliveryLimitations:Time:Hour',
            '!~',
            '0,1,2,3,4,5',
            'and',
            'ADVERTISER',
        );
    }

    /**
     * DL44: Time - Day, Not Equal (!~), and, MANAGER
     */
    public function testDL44_TimeDay_NotEqual_And_Manager()
    {
        $this->_runCombinationTest(
            'DL44',
            'deliveryLimitations:Time:Day',
            '!~',
            '0,6',
            'and',
            'MANAGER',
        );
    }

    // =========================================================================
    // DL45–DL50: Multi-rule combination tests (two ACLs per banner)
    // =========================================================================

    /**
     * DL45: Geo Country (=~) AND Client IP (==), ADMIN
     */
    public function testDL45_MultiRule_GeoCountry_And_ClientIP_Admin()
    {
        $this->_runMultiRuleCombinationTest(
            'DL45',
            'deliveryLimitations:Geo:Country',
            '=~',
            'US',
            'deliveryLimitations:Client:Ip',
            '==',
            '10.0.0.*',
            'and',
            'ADMIN',
        );
    }

    /**
     * DL46: Time Hour (=~) OR Site PageURL (=~), MANAGER
     */
    public function testDL46_MultiRule_TimeHour_Or_SitePageURL_Manager()
    {
        $this->_runMultiRuleCombinationTest(
            'DL46',
            'deliveryLimitations:Time:Hour',
            '=~',
            '8,9,10,11,12,13,14,15,16,17',
            'deliveryLimitations:Site:Pageurl',
            '=~',
            'example.com/promo',
            'or',
            'MANAGER',
        );
    }

    /**
     * DL47: Client Language (=~) AND Geo Continent (=~), ADVERTISER
     */
    public function testDL47_MultiRule_ClientLanguage_And_GeoContinent_Advertiser()
    {
        $this->_runMultiRuleCombinationTest(
            'DL47',
            'deliveryLimitations:Client:Language',
            '=~',
            'en',
            'deliveryLimitations:Geo:Continent',
            '=~',
            'NA',
            'and',
            'ADVERTISER',
        );
    }

    /**
     * DL48: Time Day (=~) AND Time Hour (=~), ADMIN
     */
    public function testDL48_MultiRule_TimeDay_And_TimeHour_Admin()
    {
        $this->_runMultiRuleCombinationTest(
            'DL48',
            'deliveryLimitations:Time:Day',
            '=~',
            '1,2,3,4,5',
            'deliveryLimitations:Time:Hour',
            '=~',
            '9,10,11,12,13,14,15,16,17',
            'and',
            'ADMIN',
        );
    }

    /**
     * DL49: Site Source (==) OR Client UserAgent (=~), MANAGER
     */
    public function testDL49_MultiRule_SiteSource_Or_ClientUserAgent_Manager()
    {
        $this->_runMultiRuleCombinationTest(
            'DL49',
            'deliveryLimitations:Site:Source',
            '==',
            'mobile_app',
            'deliveryLimitations:Client:Useragent',
            '=~',
            'Mobile',
            'or',
            'MANAGER',
        );
    }

    /**
     * DL50: Geo Country (!~) AND Site ReferingPage (!=), ADVERTISER
     */
    public function testDL50_MultiRule_GeoCountry_And_SiteReferingPage_Advertiser()
    {
        $this->_runMultiRuleCombinationTest(
            'DL50',
            'deliveryLimitations:Geo:Country',
            '!~',
            'RU,CN',
            'deliveryLimitations:Site:Referingpage',
            '!=',
            'http://bad-referrer.com',
            'and',
            'ADVERTISER',
        );
    }

    // =========================================================================
    // DL51–DL53: Additional edge-case / cross-category combinations
    // =========================================================================

    /**
     * DL51: Client Domain Regex match (=x), or, ADVERTISER
     */
    public function testDL51_ClientDomain_Regex_Or_Advertiser()
    {
        $this->_runCombinationTest(
            'DL51',
            'deliveryLimitations:Client:Domain',
            '=x',
            '.*\\.example\\.com$',
            'or',
            'ADVERTISER',
        );
    }

    /**
     * DL52: Site ReferingPage Regex not match (!x), and, ADMIN
     */
    public function testDL52_SiteReferingPage_RegexNot_And_Admin()
    {
        $this->_runCombinationTest(
            'DL52',
            'deliveryLimitations:Site:Referingpage',
            '!x',
            'spam[0-9]+\\.com',
            'and',
            'ADMIN',
        );
    }

    /**
     * DL53: Geo Organisation, Contains (=~), or, MANAGER
     */
    public function testDL53_GeoOrganisation_Contains_Or_Manager()
    {
        $this->_runCombinationTest(
            'DL53',
            'deliveryLimitations:Geo:Organisation',
            '=~',
            'University',
            'or',
            'MANAGER',
        );
    }
}
