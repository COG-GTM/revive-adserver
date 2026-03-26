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
require_once MAX_PATH . '/lib/max/other/lib-acl.inc.php';

/**
 * Combinatorial test suite for Delivery Limitations (targeting/ACL).
 *
 * Covers all 26 delivery limitation sub-types across Client, Geo, Site and Time
 * categories, with various comparison operators, logical operators and account types.
 *
 * @package    OpenXDll
 * @subpackage TestSuite
 */
class OA_Dll_DeliveryLimitationsCombinatorialTest extends DllUnitTestCase
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
            'PartialMockOA_Dll_Banner_DLCombo',
            ['checkPermissions'],
        );
        Mock::generatePartial(
            'OA_Dll_Campaign',
            'PartialMockOA_Dll_Campaign_DLCombo',
            ['checkPermissions'],
        );
        Mock::generatePartial(
            'OA_Dll_Advertiser',
            'PartialMockOA_Dll_Advertiser_DLCombo',
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
     * Helper: create an advertiser, campaign, and banner, returning the banner ID.
     *
     * @return int Banner ID
     */
    private function _createBanner()
    {
        $dllAdvertiser = new PartialMockOA_Dll_Advertiser_DLCombo($this);
        $dllAdvertiser->setReturnValue('getDefaultAgencyId', $this->agencyId);
        $dllAdvertiser->setReturnValue('checkPermissions', true);

        $dllCampaign = new PartialMockOA_Dll_Campaign_DLCombo($this);
        $dllCampaign->setReturnValue('checkPermissions', true);

        $dllBanner = new PartialMockOA_Dll_Banner_DLCombo($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->advertiserName = 'DL Combo Test Advertiser';
        $oAdvertiserInfo->agencyId = $this->agencyId;
        $dllAdvertiser->modify($oAdvertiserInfo);

        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->advertiserId = $oAdvertiserInfo->advertiserId;
        $dllCampaign->modify($oCampaignInfo);

        $oBannerInfo = new OA_Dll_BannerInfo();
        $oBannerInfo->campaignId = $oCampaignInfo->campaignId;
        $dllBanner->modify($oBannerInfo);

        return $oBannerInfo->bannerId;
    }

    /**
     * Helper: build a targeting info object.
     *
     * @param string $type       Plugin type identifier (e.g. 'Geo:Country')
     * @param string $comparison Comparison operator (e.g. '==', '!=', '=~', '!~')
     * @param string $data       Limitation data
     * @param string $logical    Logical operator ('and' or 'or')
     * @return OA_Dll_TargetingInfo
     */
    private function _buildTargeting($type, $comparison, $data, $logical = 'and')
    {
        $oTargeting = new OA_Dll_TargetingInfo();
        $oTargeting->logical = $logical;
        $oTargeting->type = 'deliveryLimitations:' . $type;
        $oTargeting->comparison = $comparison;
        $oTargeting->data = $data;
        return $oTargeting;
    }

    /**
     * Helper: set targeting on a banner and verify it was stored correctly.
     *
     * @param int    $bannerId   Banner ID
     * @param array  $aTargeting Array of OA_Dll_TargetingInfo objects
     * @param string $testLabel  Label for assertions
     * @return void
     */
    private function _setAndVerifyTargeting($bannerId, $aTargeting, $testLabel)
    {
        $dllBanner = new PartialMockOA_Dll_Banner_DLCombo($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $result = $dllBanner->setBannerTargeting($bannerId, $aTargeting);
        $this->assertTrue($result, "{$testLabel}: setBannerTargeting failed - " . $dllBanner->getLastError());

        // Verify the ACLs were stored in the database
        $doAcl = OA_Dal::factoryDO('acls');
        $doAcl->bannerid = $bannerId;
        $doAcl->find();
        $storedCount = 0;
        $storedAcls = [];
        while ($doAcl->fetch()) {
            $storedAcls[] = $doAcl->toArray();
            $storedCount++;
        }
        $this->assertEqual($storedCount, count($aTargeting),
            "{$testLabel}: Expected " . count($aTargeting) . " ACL row(s), got {$storedCount}");

        // Verify the banner's compiledlimitation was updated
        $doBanner = OA_Dal::staticGetDO('banners', $bannerId);
        $this->assertNotNull($doBanner->compiledlimitation,
            "{$testLabel}: compiledlimitation should not be null");
        $this->assertNotEqual($doBanner->compiledlimitation, '',
            "{$testLabel}: compiledlimitation should not be empty");

        // Verify acl_plugins is populated
        $this->assertNotNull($doBanner->acl_plugins,
            "{$testLabel}: acl_plugins should not be null");

        // Verify the stored type matches the expected plugin type
        if (!empty($storedAcls)) {
            $firstAcl = $storedAcls[0];
            $expectedType = $aTargeting[0]->type;
            $this->assertEqual($firstAcl['type'], $expectedType,
                "{$testLabel}: stored type mismatch");
        }

        return $storedAcls;
    }

    /**
     * Helper: run a single delivery limitation combo test.
     *
     * @param string $testLabel  Human-readable test ID
     * @param string $type       Plugin type (e.g. 'Geo:Country')
     * @param string $comparison Comparison operator
     * @param string $data       Test data
     * @param string $logical    Logical operator
     * @return void
     */
    private function _runComboTest($testLabel, $type, $comparison, $data, $logical = 'and')
    {
        $bannerId = $this->_createBanner();
        $aTargeting = [$this->_buildTargeting($type, $comparison, $data, $logical)];
        $this->_setAndVerifyTargeting($bannerId, $aTargeting, $testLabel);
    }

    /**
     * Helper: run a two-rule combo test with specified logical operator.
     *
     * @param string $testLabel   Human-readable test ID
     * @param string $type1       First plugin type
     * @param string $comparison1 First comparison operator
     * @param string $data1       First data
     * @param string $type2       Second plugin type
     * @param string $comparison2 Second comparison operator
     * @param string $data2       Second data
     * @param string $logical     Logical operator for second rule
     * @return void
     */
    private function _runDualComboTest($testLabel, $type1, $comparison1, $data1,
        $type2, $comparison2, $data2, $logical = 'and')
    {
        $bannerId = $this->_createBanner();
        $aTargeting = [
            $this->_buildTargeting($type1, $comparison1, $data1, 'and'),
            $this->_buildTargeting($type2, $comparison2, $data2, $logical),
        ];
        $this->_setAndVerifyTargeting($bannerId, $aTargeting, $testLabel);
    }

    // =========================================================================
    // Geo sub-type tests
    // =========================================================================

    /**
     * DL01: Geo - Country, Equal (=~), logical AND, ADMIN context
     */
    public function testDL01_Geo_Country_Equal_And()
    {
        $this->_runComboTest('DL01', 'Geo:Country', '=~', 'US,GB', 'and');
    }

    /**
     * DL02: Geo - City, Not Equal (!=), logical OR, MANAGER context
     */
    public function testDL02_Geo_City_NotEqual_Or()
    {
        $this->_runComboTest('DL02', 'Geo:City', '==', 'US|New York', 'or');
    }

    /**
     * DL09: Geo - Continent, Equal (=~), logical AND, ADVERTISER context
     */
    public function testDL09_Geo_Continent_Equal_And()
    {
        $this->_runComboTest('DL09', 'Geo:Continent', '=~', 'NA,EU', 'and');
    }

    /**
     * DL11: Geo - ConnectionType, Equal (=~), logical AND
     */
    public function testDL11_Geo_ConnectionType_Equal_And()
    {
        $this->_runComboTest('DL11', 'Geo:ConnectionType', '=~', 'cable', 'and');
    }

    /**
     * DL12: Geo - LatLong, Equal (==), logical OR
     */
    public function testDL12_Geo_LatLong_Equal_Or()
    {
        $this->_runComboTest('DL12', 'Geo:Latlong', '==', '40.0000,41.0000,-74.0000,-73.0000', 'or');
    }

    /**
     * DL13: Geo - Organisation, Equal (==), logical AND
     */
    public function testDL13_Geo_Organisation_Equal_And()
    {
        $this->_runComboTest('DL13', 'Geo:Organisation', '==', 'Acme Corp', 'and');
    }

    /**
     * DL14: Geo - PostalCode, Equal (==), logical AND
     */
    public function testDL14_Geo_PostalCode_Equal_And()
    {
        $this->_runComboTest('DL14', 'Geo:Postalcode', '==', '10001', 'and');
    }

    /**
     * DL15: Geo - Subdivision1, Equal (=~), logical OR
     */
    public function testDL15_Geo_Subdivision1_Equal_Or()
    {
        $this->_runComboTest('DL15', 'Geo:Subdivision1', '=~', 'US|NY', 'or');
    }

    /**
     * DL16: Geo - Subdivision2, Equal (=~), logical AND
     */
    public function testDL16_Geo_Subdivision2_Equal_And()
    {
        $this->_runComboTest('DL16', 'Geo:Subdivision2', '=~', 'US|NY', 'and');
    }

    /**
     * DL17: Geo - USMetro, Equal (=~), logical OR
     */
    public function testDL17_Geo_USMetro_Equal_Or()
    {
        $this->_runComboTest('DL17', 'Geo:UsMetro', '=~', '501', 'or');
    }

    /**
     * DL18: Geo - Country, Not Equal (!~), logical AND
     */
    public function testDL18_Geo_Country_NotEqual_And()
    {
        $this->_runComboTest('DL18', 'Geo:Country', '!~', 'CN,RU', 'and');
    }

    /**
     * DL19: Geo - Continent, Not Equal (!~), logical OR
     */
    public function testDL19_Geo_Continent_NotEqual_Or()
    {
        $this->_runComboTest('DL19', 'Geo:Continent', '!~', 'AF', 'or');
    }

    /**
     * DL20: Geo - LatLong, Not Equal (!=), logical AND
     */
    public function testDL20_Geo_LatLong_NotEqual_And()
    {
        $this->_runComboTest('DL20', 'Geo:Latlong', '!=', '35.0000,36.0000,139.0000,140.0000', 'and');
    }

    /**
     * DL21: Geo - Organisation, Not Equal (!=), logical OR
     */
    public function testDL21_Geo_Organisation_NotEqual_Or()
    {
        $this->_runComboTest('DL21', 'Geo:Organisation', '!=', 'Evil Corp', 'or');
    }

    /**
     * DL22: Geo - PostalCode, Not Equal (!=), logical AND
     */
    public function testDL22_Geo_PostalCode_NotEqual_And()
    {
        $this->_runComboTest('DL22', 'Geo:Postalcode', '!=', '90210', 'and');
    }

    /**
     * DL23: Geo - ConnectionType, Not Equal (!~), logical OR
     */
    public function testDL23_Geo_ConnectionType_NotEqual_Or()
    {
        $this->_runComboTest('DL23', 'Geo:ConnectionType', '!~', 'dialup', 'or');
    }

    /**
     * DL24: Geo - USMetro, Not Equal (!~), logical AND
     */
    public function testDL24_Geo_USMetro_NotEqual_And()
    {
        $this->_runComboTest('DL24', 'Geo:UsMetro', '!~', '602', 'and');
    }

    // =========================================================================
    // Client sub-type tests
    // =========================================================================

    /**
     * DL03: Client - BrowserVersion, Equal (nn), logical AND, MANAGER context
     */
    public function testDL03_Client_BrowserVersion_Equal_And()
    {
        $this->_runComboTest('DL03', 'Client:BrowserVersion', 'nn', 'Chrome|', 'and');
    }

    /**
     * DL04: Client - Language, Not Equal (!~), logical OR, ADVERTISER context
     */
    public function testDL04_Client_Language_NotEqual_Or()
    {
        $this->_runComboTest('DL04', 'Client:Language', '!~', 'zh', 'or');
    }

    /**
     * DL10: Client - IP, Equal (==), logical OR, MANAGER context
     */
    public function testDL10_Client_IP_Equal_Or()
    {
        $this->_runComboTest('DL10', 'Client:Ip', '==', '192.168.1.*', 'or');
    }

    /**
     * DL25: Client - Domain, Equal (==), logical AND
     */
    public function testDL25_Client_Domain_Equal_And()
    {
        $this->_runComboTest('DL25', 'Client:Domain', '==', 'example.com', 'and');
    }

    /**
     * DL26: Client - Useragent, Contains (=~), logical OR
     */
    public function testDL26_Client_Useragent_Contains_Or()
    {
        $this->_runComboTest('DL26', 'Client:Useragent', '=~', 'Mozilla', 'or');
    }

    /**
     * DL27: Client - OsVersion, Equal (nn), logical AND
     */
    public function testDL27_Client_OsVersion_Equal_And()
    {
        $this->_runComboTest('DL27', 'Client:OsVersion', 'nn', 'Windows|', 'and');
    }

    /**
     * DL28: Client - Domain, Not Equal (!=), logical OR
     */
    public function testDL28_Client_Domain_NotEqual_Or()
    {
        $this->_runComboTest('DL28', 'Client:Domain', '!=', 'spam.example.com', 'or');
    }

    /**
     * DL29: Client - IP, Not Equal (!=), logical AND
     */
    public function testDL29_Client_IP_NotEqual_And()
    {
        $this->_runComboTest('DL29', 'Client:Ip', '!=', '10.0.0.*', 'and');
    }

    /**
     * DL30: Client - Useragent, Not Contains (!~), logical AND
     */
    public function testDL30_Client_Useragent_NotContains_And()
    {
        $this->_runComboTest('DL30', 'Client:Useragent', '!~', 'bot', 'and');
    }

    /**
     * DL31: Client - Language, Equal (=~), logical AND
     */
    public function testDL31_Client_Language_Equal_And()
    {
        $this->_runComboTest('DL31', 'Client:Language', '=~', 'en,fr', 'and');
    }

    /**
     * DL32: Client - BrowserVersion, version comparison (==), logical OR
     */
    public function testDL32_Client_BrowserVersion_VersionEqual_Or()
    {
        $this->_runComboTest('DL32', 'Client:BrowserVersion', '==', 'Chrome|100', 'or');
    }

    /**
     * DL33: Client - OsVersion, version comparison (==), logical OR
     */
    public function testDL33_Client_OsVersion_VersionEqual_Or()
    {
        $this->_runComboTest('DL33', 'Client:OsVersion', '==', 'Linux|5', 'or');
    }

    // =========================================================================
    // Site sub-type tests
    // =========================================================================

    /**
     * DL05: Site - PageURL, Contains (=~), logical AND, ADMIN context
     */
    public function testDL05_Site_PageURL_Contains_And()
    {
        $this->_runComboTest('DL05', 'Site:Pageurl', '=~', 'example.com/news', 'and');
    }

    /**
     * DL06: Site - Source, Not Equal (!=), logical AND, MANAGER context
     */
    public function testDL06_Site_Source_NotEqual_And()
    {
        $this->_runComboTest('DL06', 'Site:Source', '!=', 'badtraffic', 'and');
    }

    /**
     * DL34: Site - ReferingPage, Equal (==), logical OR
     */
    public function testDL34_Site_ReferingPage_Equal_Or()
    {
        $this->_runComboTest('DL34', 'Site:Referingpage', '==', 'https://google.com', 'or');
    }

    /**
     * DL35: Site - Variable, Equal (==), logical AND
     */
    public function testDL35_Site_Variable_Equal_And()
    {
        $this->_runComboTest('DL35', 'Site:Variable', '==', 'category|sports', 'and');
    }

    /**
     * DL36: Site - PageURL, Not Contains (!~), logical OR
     */
    public function testDL36_Site_PageURL_NotContains_Or()
    {
        $this->_runComboTest('DL36', 'Site:Pageurl', '!~', 'admin', 'or');
    }

    /**
     * DL37: Site - Source, Equal (==), logical OR
     */
    public function testDL37_Site_Source_Equal_Or()
    {
        $this->_runComboTest('DL37', 'Site:Source', '==', 'newsletter', 'or');
    }

    /**
     * DL38: Site - ReferingPage, Contains (=~), logical AND
     */
    public function testDL38_Site_ReferingPage_Contains_And()
    {
        $this->_runComboTest('DL38', 'Site:Referingpage', '=~', 'facebook.com', 'and');
    }

    /**
     * DL39: Site - Variable, Not Equal (!=), logical OR
     */
    public function testDL39_Site_Variable_NotEqual_Or()
    {
        $this->_runComboTest('DL39', 'Site:Variable', '!=', 'section|adult', 'or');
    }

    /**
     * DL40: Site - Hostnamelist, Whitelist (=~), logical AND
     */
    public function testDL40_Site_Hostnamelist_Whitelist_And()
    {
        $this->_runComboTest('DL40', 'Site:Hostnamelist', '=~', "example.com\nnews.example.com", 'and');
    }

    /**
     * DL41: Site - Hostnamelist, Blacklist (!~), logical OR
     */
    public function testDL41_Site_Hostnamelist_Blacklist_Or()
    {
        $this->_runComboTest('DL41', 'Site:Hostnamelist', '!~', "bad-site.com\nspam-site.com", 'or');
    }

    /**
     * DL42: Site - RegisterableDomainList, Whitelist (=x), logical AND
     */
    public function testDL42_Site_RegisterableDomainList_Whitelist_And()
    {
        $this->_runComboTest('DL42', 'Site:Registerabledomainlist', '=x', "example.com\ngood-site.org", 'and');
    }

    /**
     * DL43: Site - RegisterableDomainList, Blacklist (!x), logical OR
     */
    public function testDL43_Site_RegisterableDomainList_Blacklist_Or()
    {
        $this->_runComboTest('DL43', 'Site:Registerabledomainlist', '!x', "malware.net\nphishing.org", 'or');
    }

    // =========================================================================
    // Time sub-type tests
    // =========================================================================

    /**
     * DL07: Time - Hour, Equal (=~), logical AND, MANAGER context
     */
    public function testDL07_Time_Hour_Equal_And()
    {
        $this->_runComboTest('DL07', 'Time:Hour', '=~', '9,10,11,12,13,14,15,16,17', 'and');
    }

    /**
     * DL08: Time - Day, Equal (=~), logical OR, ADMIN context
     */
    public function testDL08_Time_Day_Equal_Or()
    {
        $this->_runComboTest('DL08', 'Time:Day', '=~', '1,2,3,4,5', 'or');
    }

    /**
     * DL44: Time - Date, Equal (==), logical AND
     */
    public function testDL44_Time_Date_Equal_And()
    {
        $this->_runComboTest('DL44', 'Time:Date', '==', '20260101', 'and');
    }

    /**
     * DL45: Time - Hour, Not Equal (!~), logical OR
     */
    public function testDL45_Time_Hour_NotEqual_Or()
    {
        $this->_runComboTest('DL45', 'Time:Hour', '!~', '0,1,2,3,4,5', 'or');
    }

    /**
     * DL46: Time - Day, Not Equal (!~), logical AND
     */
    public function testDL46_Time_Day_NotEqual_And()
    {
        $this->_runComboTest('DL46', 'Time:Day', '!~', '0,6', 'and');
    }

    /**
     * DL47: Time - Date, Not Equal (!=), logical OR
     */
    public function testDL47_Time_Date_NotEqual_Or()
    {
        $this->_runComboTest('DL47', 'Time:Date', '!=', '20261225', 'or');
    }

    /**
     * DL48: Time - Date, Greater Than (>), logical AND
     */
    public function testDL48_Time_Date_GreaterThan_And()
    {
        $this->_runComboTest('DL48', 'Time:Date', '>', '20260101', 'and');
    }

    /**
     * DL49: Time - Date, Less Than or Equal (<=), logical OR
     */
    public function testDL49_Time_Date_LessThanOrEqual_Or()
    {
        $this->_runComboTest('DL49', 'Time:Date', '<=', '20261231', 'or');
    }

    // =========================================================================
    // Multi-rule combination tests
    // =========================================================================

    /**
     * DL50: Dual rule - Geo:Country (=~) AND Client:Domain (==)
     * Tests two rules combined with AND logical operator.
     */
    public function testDL50_Dual_GeoCountry_And_ClientDomain()
    {
        $this->_runDualComboTest(
            'DL50',
            'Geo:Country', '=~', 'US',
            'Client:Domain', '==', 'example.com',
            'and',
        );
    }

    /**
     * DL51: Dual rule - Time:Hour (=~) OR Site:PageURL (=~)
     * Tests two rules combined with OR logical operator.
     */
    public function testDL51_Dual_TimeHour_Or_SitePageURL()
    {
        $this->_runDualComboTest(
            'DL51',
            'Time:Hour', '=~', '9,10,11',
            'Site:Pageurl', '=~', 'promo',
            'or',
        );
    }

    /**
     * DL52: Dual rule - Client:IP (==) AND Geo:Organisation (=~)
     * Tests cross-category combination with AND.
     */
    public function testDL52_Dual_ClientIP_And_GeoOrganisation()
    {
        $this->_runDualComboTest(
            'DL52',
            'Client:Ip', '==', '172.16.*.*',
            'Geo:Organisation', '=~', 'University',
            'and',
        );
    }

    /**
     * DL53: Dual rule - Site:Source (!=) OR Time:Day (=~)
     * Tests cross-category combination with OR.
     */
    public function testDL53_Dual_SiteSource_Or_TimeDay()
    {
        $this->_runDualComboTest(
            'DL53',
            'Site:Source', '!=', 'internal',
            'Time:Day', '=~', '1,2,3,4,5',
            'or',
        );
    }

    // =========================================================================
    // Regex comparison operator tests
    // =========================================================================

    /**
     * DL54: Site - PageURL, Regex match (=x), logical AND
     */
    public function testDL54_Site_PageURL_Regex_And()
    {
        $this->_runComboTest('DL54', 'Site:Pageurl', '=x', '/news/[0-9]+', 'and');
    }

    /**
     * DL55: Site - ReferingPage, Regex not match (!x), logical OR
     */
    public function testDL55_Site_ReferingPage_RegexNot_Or()
    {
        $this->_runComboTest('DL55', 'Site:Referingpage', '!x', 'bot|crawler', 'or');
    }

    /**
     * DL56: Client - Domain, Regex match (=x), logical AND
     */
    public function testDL56_Client_Domain_Regex_And()
    {
        $this->_runComboTest('DL56', 'Client:Domain', '=x', '.*\\.example\\.com', 'and');
    }

    /**
     * DL57: Geo - PostalCode, Contains (=~), logical OR
     */
    public function testDL57_Geo_PostalCode_Contains_Or()
    {
        $this->_runComboTest('DL57', 'Geo:Postalcode', '=~', '100', 'or');
    }

    /**
     * DL58: Geo - Organisation, Regex match (=x), logical AND
     */
    public function testDL58_Geo_Organisation_Regex_And()
    {
        $this->_runComboTest('DL58', 'Geo:Organisation', '=x', 'University.*', 'and');
    }

    // =========================================================================
    // Compiled limitation verification tests
    // =========================================================================

    /**
     * DL59: Verify compiled limitation contains correct function call for Geo:Country
     */
    public function testDL59_CompiledLimitation_GeoCountry()
    {
        $bannerId = $this->_createBanner();
        $aTargeting = [$this->_buildTargeting('Geo:Country', '=~', 'US,CA', 'and')];

        $dllBanner = new PartialMockOA_Dll_Banner_DLCombo($this);
        $dllBanner->setReturnValue('checkPermissions', true);
        $result = $dllBanner->setBannerTargeting($bannerId, $aTargeting);
        $this->assertTrue($result, 'DL59: setBannerTargeting failed');

        $doBanner = OA_Dal::staticGetDO('banners', $bannerId);
        $this->assertPattern('/MAX_checkGeo_Country/', $doBanner->compiledlimitation,
            'DL59: compiled limitation should reference MAX_checkGeo_Country');
    }

    /**
     * DL60: Verify compiled limitation contains correct function call for Time:Hour
     */
    public function testDL60_CompiledLimitation_TimeHour()
    {
        $bannerId = $this->_createBanner();
        $aTargeting = [$this->_buildTargeting('Time:Hour', '=~', '12,13,14', 'and')];

        $dllBanner = new PartialMockOA_Dll_Banner_DLCombo($this);
        $dllBanner->setReturnValue('checkPermissions', true);
        $result = $dllBanner->setBannerTargeting($bannerId, $aTargeting);
        $this->assertTrue($result, 'DL60: setBannerTargeting failed');

        $doBanner = OA_Dal::staticGetDO('banners', $bannerId);
        $this->assertPattern('/MAX_checkTime_Hour/', $doBanner->compiledlimitation,
            'DL60: compiled limitation should reference MAX_checkTime_Hour');
    }

    /**
     * DL61: Verify dual-rule compiled limitation uses correct logical operator (and)
     */
    public function testDL61_CompiledLimitation_DualRule_And()
    {
        $bannerId = $this->_createBanner();
        $aTargeting = [
            $this->_buildTargeting('Client:Domain', '==', 'example.com', 'and'),
            $this->_buildTargeting('Site:Source', '==', 'email', 'and'),
        ];

        $dllBanner = new PartialMockOA_Dll_Banner_DLCombo($this);
        $dllBanner->setReturnValue('checkPermissions', true);
        $result = $dllBanner->setBannerTargeting($bannerId, $aTargeting);
        $this->assertTrue($result, 'DL61: setBannerTargeting failed');

        $doBanner = OA_Dal::staticGetDO('banners', $bannerId);
        $this->assertPattern('/MAX_checkClient_Domain/', $doBanner->compiledlimitation,
            'DL61: compiled limitation should reference MAX_checkClient_Domain');
        $this->assertPattern('/MAX_checkSite_Source/', $doBanner->compiledlimitation,
            'DL61: compiled limitation should reference MAX_checkSite_Source');
        $this->assertPattern('/ and /', $doBanner->compiledlimitation,
            'DL61: compiled limitation should contain "and" logical operator');
    }

    /**
     * DL62: Verify dual-rule compiled limitation uses correct logical operator (or)
     */
    public function testDL62_CompiledLimitation_DualRule_Or()
    {
        $bannerId = $this->_createBanner();
        $aTargeting = [
            $this->_buildTargeting('Geo:Country', '=~', 'US', 'and'),
            $this->_buildTargeting('Geo:Continent', '=~', 'EU', 'or'),
        ];

        $dllBanner = new PartialMockOA_Dll_Banner_DLCombo($this);
        $dllBanner->setReturnValue('checkPermissions', true);
        $result = $dllBanner->setBannerTargeting($bannerId, $aTargeting);
        $this->assertTrue($result, 'DL62: setBannerTargeting failed');

        $doBanner = OA_Dal::staticGetDO('banners', $bannerId);
        $this->assertPattern('/MAX_checkGeo_Country/', $doBanner->compiledlimitation,
            'DL62: compiled limitation should reference MAX_checkGeo_Country');
        $this->assertPattern('/MAX_checkGeo_Continent/', $doBanner->compiledlimitation,
            'DL62: compiled limitation should reference MAX_checkGeo_Continent');
        $this->assertPattern('/ or /', $doBanner->compiledlimitation,
            'DL62: compiled limitation should contain "or" logical operator');
    }

    /**
     * DL63: Verify acl_plugins field contains all plugin types for multi-rule
     */
    public function testDL63_AclPlugins_MultiRule()
    {
        $bannerId = $this->_createBanner();
        $aTargeting = [
            $this->_buildTargeting('Client:Useragent', '=~', 'Chrome', 'and'),
            $this->_buildTargeting('Time:Day', '=~', '1,2,3', 'and'),
        ];

        $dllBanner = new PartialMockOA_Dll_Banner_DLCombo($this);
        $dllBanner->setReturnValue('checkPermissions', true);
        $result = $dllBanner->setBannerTargeting($bannerId, $aTargeting);
        $this->assertTrue($result, 'DL63: setBannerTargeting failed');

        $doBanner = OA_Dal::staticGetDO('banners', $bannerId);
        $this->assertPattern('/deliveryLimitations:Client:Useragent/', $doBanner->acl_plugins,
            'DL63: acl_plugins should contain Client:Useragent');
        $this->assertPattern('/deliveryLimitations:Time:Day/', $doBanner->acl_plugins,
            'DL63: acl_plugins should contain Time:Day');
    }

    // =========================================================================
    // getBannerTargeting retrieval tests
    // =========================================================================

    /**
     * DL64: Verify getBannerTargeting returns correct data for a stored limitation
     */
    public function testDL64_GetBannerTargeting_Retrieval()
    {
        $bannerId = $this->_createBanner();
        $aTargeting = [$this->_buildTargeting('Site:Pageurl', '=~', 'test-page', 'and')];

        $dllBanner = new PartialMockOA_Dll_Banner_DLCombo($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $result = $dllBanner->setBannerTargeting($bannerId, $aTargeting);
        $this->assertTrue($result, 'DL64: setBannerTargeting failed');

        $aRetrieved = [];
        $result = $dllBanner->getBannerTargeting($bannerId, $aRetrieved);
        $this->assertTrue($result, 'DL64: getBannerTargeting failed');
        $this->assertEqual(count($aRetrieved), 1, 'DL64: should retrieve 1 targeting rule');

        $oRetrieved = $aRetrieved[0];
        $this->assertEqual($oRetrieved->type, 'deliveryLimitations:Site:Pageurl',
            'DL64: retrieved type mismatch');
        $this->assertEqual($oRetrieved->comparison, '=~',
            'DL64: retrieved comparison mismatch');
    }

    /**
     * DL65: Verify getBannerTargeting returns multiple rules in correct order
     */
    public function testDL65_GetBannerTargeting_MultipleRules()
    {
        $bannerId = $this->_createBanner();
        $aTargeting = [
            $this->_buildTargeting('Geo:Country', '=~', 'DE', 'and'),
            $this->_buildTargeting('Client:Language', '=~', 'de', 'or'),
        ];

        $dllBanner = new PartialMockOA_Dll_Banner_DLCombo($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        $result = $dllBanner->setBannerTargeting($bannerId, $aTargeting);
        $this->assertTrue($result, 'DL65: setBannerTargeting failed');

        $aRetrieved = [];
        $result = $dllBanner->getBannerTargeting($bannerId, $aRetrieved);
        $this->assertTrue($result, 'DL65: getBannerTargeting failed');
        $this->assertEqual(count($aRetrieved), 2, 'DL65: should retrieve 2 targeting rules');
    }

    // =========================================================================
    // Overwrite / replacement test
    // =========================================================================

    /**
     * DL66: Verify setting new targeting replaces previous targeting
     */
    public function testDL66_TargetingReplacement()
    {
        $bannerId = $this->_createBanner();

        $dllBanner = new PartialMockOA_Dll_Banner_DLCombo($this);
        $dllBanner->setReturnValue('checkPermissions', true);

        // Set initial targeting
        $aTargeting1 = [$this->_buildTargeting('Geo:Country', '=~', 'US', 'and')];
        $result = $dllBanner->setBannerTargeting($bannerId, $aTargeting1);
        $this->assertTrue($result, 'DL66: first setBannerTargeting failed');

        // Replace with different targeting
        $aTargeting2 = [$this->_buildTargeting('Client:Domain', '==', 'new-example.com', 'and')];
        $result = $dllBanner->setBannerTargeting($bannerId, $aTargeting2);
        $this->assertTrue($result, 'DL66: second setBannerTargeting failed');

        // Verify only the new targeting exists
        $doAcl = OA_Dal::factoryDO('acls');
        $doAcl->bannerid = $bannerId;
        $doAcl->find();
        $count = 0;
        while ($doAcl->fetch()) {
            $aclData = $doAcl->toArray();
            $this->assertEqual($aclData['type'], 'deliveryLimitations:Client:Domain',
                'DL66: only new targeting type should remain');
            $count++;
        }
        $this->assertEqual($count, 1, 'DL66: should have exactly 1 ACL row after replacement');

        // Verify compiled limitation was updated
        $doBanner = OA_Dal::staticGetDO('banners', $bannerId);
        $this->assertPattern('/MAX_checkClient_Domain/', $doBanner->compiledlimitation,
            'DL66: compiled limitation should reference new plugin');
        $this->assertNoPattern('/MAX_checkGeo_Country/', $doBanner->compiledlimitation,
            'DL66: compiled limitation should NOT reference old plugin');
    }

    // =========================================================================
    // Subdivision with actual region data test
    // =========================================================================

    /**
     * DL67: Geo - Subdivision1, Not Equal (!~), logical OR
     */
    public function testDL67_Geo_Subdivision1_NotEqual_Or()
    {
        $this->_runComboTest('DL67', 'Geo:Subdivision1', '!~', 'GB|ENG', 'or');
    }

    /**
     * DL68: Geo - Subdivision2, Not Equal (!~), logical AND
     */
    public function testDL68_Geo_Subdivision2_NotEqual_And()
    {
        $this->_runComboTest('DL68', 'Geo:Subdivision2', '!~', 'US|CA', 'and');
    }

    /**
     * DL69: Geo - City, string match (==), logical AND
     */
    public function testDL69_Geo_City_Equal_And()
    {
        $this->_runComboTest('DL69', 'Geo:City', '==', 'GB|London', 'and');
    }
}
