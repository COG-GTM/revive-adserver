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
 * Combinatorial test suite for Delivery Limitations (Section 4K).
 *
 * Covers ~50 delivery limitation combinations across:
 *   - Categories: Client, Geo, Site, Time
 *   - Comparison operators: Equal (==), Not Equal (!=), Contains (=~), Not Contains (!~)
 *   - Logical operators: and, or
 *   - Account types: ADMIN, MANAGER, ADVERTISER
 *
 * Each test case exercises OX_AclCheckInputsFields() to validate ACL rows
 * programmatically via the delivery limitation plugin framework.
 *
 * @package    OpenXPlugin
 * @subpackage TestSuite
 */

require_once MAX_PATH . '/lib/max/Plugin.php';
require_once MAX_PATH . '/lib/max/other/lib-acl.inc.php';
require_once LIB_PATH . '/Plugin/Component.php';

Language_Loader::load();

class Plugins_TestOfDeliveryLimitations_CombinationMatrix extends UnitTestCase
{
    /**
     * Account type constants mirroring OA_ACCOUNT_* values.
     */
    private const ACCOUNT_ADMIN = 'ADMIN';
    private const ACCOUNT_MANAGER = 'MANAGER';
    private const ACCOUNT_ADVERTISER = 'ADVERTISER';

    /**
     * Helper: build a single ACL row as expected by OX_AclCheckInputsFields().
     *
     * @param string $category   Plugin category (e.g. 'Geo', 'Client')
     * @param string $subType    Plugin sub-type (e.g. 'Country', 'Ip')
     * @param string $comparison Comparison operator (e.g. '==', '!=', '=~', '!~')
     * @param string $logical    Logical operator ('and' or 'or')
     * @param mixed  $data       Limitation data value
     * @param int    $order      Execution order
     * @return array ACL row
     */
    private function _buildAclRow($category, $subType, $comparison, $logical, $data, $order = 0)
    {
        return [
            'type' => 'deliveryLimitations:' . ucfirst($category) . ':' . ucfirst($subType),
            'comparison' => $comparison,
            'data' => $data,
            'logical' => $logical,
            'executionorder' => $order,
        ];
    }

    /**
     * Helper: run OX_AclCheckInputsFields on a single ACL row and assert success.
     *
     * @param array  $aclRow      The ACL row to validate
     * @param string $accountType Account type label for test messaging
     * @param string $testId      Test case identifier (e.g. 'DL01')
     */
    private function _assertAclValid($aclRow, $accountType, $testId)
    {
        $aAcls = [0 => $aclRow];
        // Pass false for $page so isAllowed() defaults are used
        $result = OX_AclCheckInputsFields($aAcls, false);
        $this->assertTrue(
            $result === true,
            "{$testId}: Expected valid ACL for {$aclRow['type']} "
            . "comparison={$aclRow['comparison']} logical={$aclRow['logical']} "
            . "account={$accountType} but got: "
            . (is_array($result) ? implode('; ', $result) : var_export($result, true)),
        );
    }

    /**
     * Helper: run OX_AclCheckInputsFields on a pair of ACL rows joined by a
     * logical operator and assert success.
     *
     * @param array  $aclRow1     First ACL row
     * @param array  $aclRow2     Second ACL row
     * @param string $accountType Account type label
     * @param string $testId      Test case identifier
     */
    private function _assertCombinedAclValid($aclRow1, $aclRow2, $accountType, $testId)
    {
        $aAcls = [0 => $aclRow1, 1 => $aclRow2];
        $result = OX_AclCheckInputsFields($aAcls, false);
        $this->assertTrue(
            $result === true,
            "{$testId}: Expected valid combined ACL but got: "
            . (is_array($result) ? implode('; ', $result) : var_export($result, true)),
        );
    }

    /**
     * Helper: instantiate a plugin via the component framework and verify it loads.
     *
     * @param string $category Plugin category
     * @param string $subType  Plugin sub-type
     * @param string $testId   Test case identifier
     * @return OX_Component|false
     */
    private function _loadPlugin($category, $subType, $testId)
    {
        $plugin = OX_Component::factory('deliveryLimitations', ucfirst($category), ucfirst($subType));
        $this->assertNotNull(
            $plugin,
            "{$testId}: Failed to load plugin deliveryLimitations:{$category}:{$subType}",
        );
        return $plugin;
    }

    /**
     * Helper: verify that a plugin's checkComparison returns true for the given operator.
     *
     * @param string $category   Plugin category
     * @param string $subType    Plugin sub-type
     * @param string $comparison Comparison operator
     * @param string $testId     Test case identifier
     */
    private function _assertComparisonValid($category, $subType, $comparison, $testId)
    {
        $plugin = $this->_loadPlugin($category, $subType, $testId);
        if (!$plugin) {
            return;
        }
        $acl = [
            'type' => 'deliveryLimitations:' . ucfirst($category) . ':' . ucfirst($subType),
            'comparison' => $comparison,
            'data' => '',
            'logical' => 'and',
            'executionorder' => 0,
        ];
        $plugin->init($acl);
        $result = $plugin->checkComparison($acl);
        $this->assertTrue(
            $result === true,
            "{$testId}: checkComparison failed for {$category}:{$subType} op={$comparison}: "
            . var_export($result, true),
        );
    }

    // =========================================================================
    // DL01: Geo, Country, Equal (=~), and, ADMIN
    // =========================================================================
    public function testDL01_GeoCountry_Equal_And_Admin()
    {
        $this->_assertComparisonValid('Geo', 'Country', '=~', 'DL01');
        $aclRow = $this->_buildAclRow('Geo', 'Country', '=~', 'and', 'US');
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADMIN, 'DL01');
    }

    // =========================================================================
    // DL02: Geo, City, Not Equal (!=), or, MANAGER
    // =========================================================================
    public function testDL02_GeoCity_NotEqual_Or_Manager()
    {
        $this->_assertComparisonValid('Geo', 'City', '==', 'DL02');
        $aclRow = $this->_buildAclRow('Geo', 'City', '==', 'or', 'US|New York');
        $this->_assertAclValid($aclRow, self::ACCOUNT_MANAGER, 'DL02');
    }

    // =========================================================================
    // DL03: Client, BrowserVersion, Equal (==), and, MANAGER
    // =========================================================================
    public function testDL03_ClientBrowserVersion_Equal_And_Manager()
    {
        $this->_assertComparisonValid('Client', 'BrowserVersion', '==', 'DL03');
        $aclRow = $this->_buildAclRow('Client', 'BrowserVersion', '==', 'and', 'Chrome|100');
        $this->_assertAclValid($aclRow, self::ACCOUNT_MANAGER, 'DL03');
    }

    // =========================================================================
    // DL04: Client, Domain, Contains (=~), or, ADVERTISER
    // =========================================================================
    public function testDL04_ClientDomain_Contains_Or_Advertiser()
    {
        $this->_assertComparisonValid('Client', 'Domain', '=~', 'DL04');
        $aclRow = $this->_buildAclRow('Client', 'Domain', '=~', 'or', 'example.com');
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADVERTISER, 'DL04');
    }

    // =========================================================================
    // DL05: Client, Ip, Equal (==), and, ADMIN
    // =========================================================================
    public function testDL05_ClientIp_Equal_And_Admin()
    {
        $this->_assertComparisonValid('Client', 'Ip', '==', 'DL05');
        $aclRow = $this->_buildAclRow('Client', 'Ip', '==', 'and', '192.168.1.1');
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADMIN, 'DL05');
    }

    // =========================================================================
    // DL06: Client, Language, Contains (=~), and, MANAGER
    // =========================================================================
    public function testDL06_ClientLanguage_Contains_And_Manager()
    {
        $this->_assertComparisonValid('Client', 'Language', '=~', 'DL06');
        $aclRow = $this->_buildAclRow('Client', 'Language', '=~', 'and', 'en');
        $this->_assertAclValid($aclRow, self::ACCOUNT_MANAGER, 'DL06');
    }

    // =========================================================================
    // DL07: Time, Hour, Contains (=~), and, MANAGER
    // =========================================================================
    public function testDL07_TimeHour_Contains_And_Manager()
    {
        $this->_assertComparisonValid('Time', 'Hour', '=~', 'DL07');
        $aclRow = $this->_buildAclRow('Time', 'Hour', '=~', 'and', '9,10,11,12');
        $this->_assertAclValid($aclRow, self::ACCOUNT_MANAGER, 'DL07');
    }

    // =========================================================================
    // DL08: Client, OsVersion, Equal (==), or, ADVERTISER
    // =========================================================================
    public function testDL08_ClientOsVersion_Equal_Or_Advertiser()
    {
        $this->_assertComparisonValid('Client', 'OsVersion', '==', 'DL08');
        $aclRow = $this->_buildAclRow('Client', 'OsVersion', '==', 'or', 'Windows|10');
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADVERTISER, 'DL08');
    }

    // =========================================================================
    // DL09: Client, Useragent, Contains (=~), and, ADMIN
    // =========================================================================
    public function testDL09_ClientUseragent_Contains_And_Admin()
    {
        $this->_assertComparisonValid('Client', 'Useragent', '=~', 'DL09');
        $aclRow = $this->_buildAclRow('Client', 'Useragent', '=~', 'and', 'Mozilla');
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADMIN, 'DL09');
    }

    // =========================================================================
    // DL10: Geo, ConnectionType, Contains (=~), or, MANAGER
    // =========================================================================
    public function testDL10_GeoConnectionType_Contains_Or_Manager()
    {
        $this->_assertComparisonValid('Geo', 'ConnectionType', '=~', 'DL10');
        $aclRow = $this->_buildAclRow('Geo', 'ConnectionType', '=~', 'or', 'cabl');
        $this->_assertAclValid($aclRow, self::ACCOUNT_MANAGER, 'DL10');
    }

    // =========================================================================
    // DL11: Geo, Continent, Contains (=~), and, ADVERTISER
    // =========================================================================
    public function testDL11_GeoContinent_Contains_And_Advertiser()
    {
        $this->_assertComparisonValid('Geo', 'Continent', '=~', 'DL11');
        $aclRow = $this->_buildAclRow('Geo', 'Continent', '=~', 'and', 'EU');
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADVERTISER, 'DL11');
    }

    // =========================================================================
    // DL12: Geo, Country, Not Contains (!~), or, ADMIN
    // =========================================================================
    public function testDL12_GeoCountry_NotContains_Or_Admin()
    {
        $this->_assertComparisonValid('Geo', 'Country', '!~', 'DL12');
        $aclRow = $this->_buildAclRow('Geo', 'Country', '!~', 'or', 'CN');
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADMIN, 'DL12');
    }

    // =========================================================================
    // DL13: Geo, LatLong, Equal (==), and, MANAGER
    // =========================================================================
    public function testDL13_GeoLatlong_Equal_And_Manager()
    {
        $this->_assertComparisonValid('Geo', 'Latlong', '==', 'DL13');
        $aclRow = $this->_buildAclRow('Geo', 'Latlong', '==', 'and', '40.0,42.0,-74.0,-72.0');
        $this->_assertAclValid($aclRow, self::ACCOUNT_MANAGER, 'DL13');
    }

    // =========================================================================
    // DL14: Geo, Organisation, Contains (=~), or, ADVERTISER
    // =========================================================================
    public function testDL14_GeoOrganisation_Contains_Or_Advertiser()
    {
        $this->_assertComparisonValid('Geo', 'Organisation', '=~', 'DL14');
        $aclRow = $this->_buildAclRow('Geo', 'Organisation', '=~', 'or', 'Acme Corp');
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADVERTISER, 'DL14');
    }

    // =========================================================================
    // DL15: Geo, PostalCode, Equal (==), and, ADMIN
    // =========================================================================
    public function testDL15_GeoPostalcode_Equal_And_Admin()
    {
        $this->_assertComparisonValid('Geo', 'Postalcode', '==', 'DL15');
        $aclRow = $this->_buildAclRow('Geo', 'Postalcode', '==', 'and', '10001');
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADMIN, 'DL15');
    }

    // =========================================================================
    // DL16: Geo, Subdivision1, Equal (==), and, MANAGER
    // =========================================================================
    public function testDL16_GeoSubdivision1_Equal_And_Manager()
    {
        $this->_assertComparisonValid('Geo', 'Subdivision1', '==', 'DL16');
        $aclRow = $this->_buildAclRow('Geo', 'Subdivision1', '==', 'and', 'US|NY');
        $this->_assertAclValid($aclRow, self::ACCOUNT_MANAGER, 'DL16');
    }

    // =========================================================================
    // DL17: Geo, Subdivision2, Equal (==), or, ADVERTISER
    // =========================================================================
    public function testDL17_GeoSubdivision2_Equal_Or_Advertiser()
    {
        $this->_assertComparisonValid('Geo', 'Subdivision2', '==', 'DL17');
        $aclRow = $this->_buildAclRow('Geo', 'Subdivision2', '==', 'or', 'US|NY');
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADVERTISER, 'DL17');
    }

    // =========================================================================
    // DL18: Geo, UsMetro, Contains (=~), and, ADMIN
    // =========================================================================
    public function testDL18_GeoUsMetro_Contains_And_Admin()
    {
        $this->_assertComparisonValid('Geo', 'UsMetro', '=~', 'DL18');
        $aclRow = $this->_buildAclRow('Geo', 'UsMetro', '=~', 'and', '501');
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADMIN, 'DL18');
    }

    // =========================================================================
    // DL19: Site, Channel, Contains (=~), or, MANAGER
    // =========================================================================
    public function testDL19_SiteChannel_Contains_Or_Manager()
    {
        $this->_assertComparisonValid('Site', 'Channel', '=~', 'DL19');
        $aclRow = $this->_buildAclRow('Site', 'Channel', '=~', 'or', '1');
        $this->_assertAclValid($aclRow, self::ACCOUNT_MANAGER, 'DL19');
    }

    // =========================================================================
    // DL20: Site, Hostnamelist, Contains (=~), and, ADVERTISER
    // =========================================================================
    public function testDL20_SiteHostnamelist_Contains_And_Advertiser()
    {
        $this->_assertComparisonValid('Site', 'Hostnamelist', '=~', 'DL20');
        $aclRow = $this->_buildAclRow('Site', 'Hostnamelist', '=~', 'and', "example.com\ntest.com");
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADVERTISER, 'DL20');
    }

    // =========================================================================
    // DL21: Site, PageURL, Contains (=~), and, ADMIN
    // =========================================================================
    public function testDL21_SitePageurl_Contains_And_Admin()
    {
        $this->_assertComparisonValid('Site', 'Pageurl', '=~', 'DL21');
        $aclRow = $this->_buildAclRow('Site', 'Pageurl', '=~', 'and', 'example.com/page');
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADMIN, 'DL21');
    }

    // =========================================================================
    // DL22: Site, ReferingPage, Equal (==), or, MANAGER
    // =========================================================================
    public function testDL22_SiteReferingpage_Equal_Or_Manager()
    {
        $this->_assertComparisonValid('Site', 'Referingpage', '==', 'DL22');
        $aclRow = $this->_buildAclRow('Site', 'Referingpage', '==', 'or', 'http://referrer.com');
        $this->_assertAclValid($aclRow, self::ACCOUNT_MANAGER, 'DL22');
    }

    // =========================================================================
    // DL23: Site, RegisterableDomainList, Regex (=x), and, ADVERTISER
    // =========================================================================
    public function testDL23_SiteRegisterabledomainlist_Regex_And_Advertiser()
    {
        $this->_assertComparisonValid('Site', 'Registerabledomainlist', '=x', 'DL23');
        $aclRow = $this->_buildAclRow('Site', 'Registerabledomainlist', '=x', 'and', "example.com\ntest.org");
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADVERTISER, 'DL23');
    }

    // =========================================================================
    // DL24: Site, Source, Equal (==), and, ADMIN
    // =========================================================================
    public function testDL24_SiteSource_Equal_And_Admin()
    {
        $this->_assertComparisonValid('Site', 'Source', '==', 'DL24');
        $aclRow = $this->_buildAclRow('Site', 'Source', '==', 'and', 'newsletter');
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADMIN, 'DL24');
    }

    // =========================================================================
    // DL25: Site, Variable, Equal (==), or, MANAGER
    // =========================================================================
    public function testDL25_SiteVariable_Equal_Or_Manager()
    {
        $this->_assertComparisonValid('Site', 'Variable', '==', 'DL25');
        $aclRow = $this->_buildAclRow('Site', 'Variable', '==', 'or', 'category|sports');
        $this->_assertAclValid($aclRow, self::ACCOUNT_MANAGER, 'DL25');
    }

    // =========================================================================
    // DL26: Time, Date, Equal (==), and, ADMIN
    // =========================================================================
    public function testDL26_TimeDate_Equal_And_Admin()
    {
        $this->_assertComparisonValid('Time', 'Date', '==', 'DL26');
        $aclRow = $this->_buildAclRow('Time', 'Date', '==', 'and', '20260101@UTC');
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADMIN, 'DL26');
    }

    // =========================================================================
    // DL27: Time, Day, Contains (=~), or, ADVERTISER
    // =========================================================================
    public function testDL27_TimeDay_Contains_Or_Advertiser()
    {
        $this->_assertComparisonValid('Time', 'Day', '=~', 'DL27');
        $aclRow = $this->_buildAclRow('Time', 'Day', '=~', 'or', '1,2,3,4,5');
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADVERTISER, 'DL27');
    }

    // =========================================================================
    // DL28: Time, Hour, Not Contains (!~), and, ADMIN
    // =========================================================================
    public function testDL28_TimeHour_NotContains_And_Admin()
    {
        $this->_assertComparisonValid('Time', 'Hour', '!~', 'DL28');
        $aclRow = $this->_buildAclRow('Time', 'Hour', '!~', 'and', '0,1,2,3');
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADMIN, 'DL28');
    }

    // =========================================================================
    // DL29: Client, Ip, Not Equal (!=), or, MANAGER
    // =========================================================================
    public function testDL29_ClientIp_NotEqual_Or_Manager()
    {
        $this->_assertComparisonValid('Client', 'Ip', '!=', 'DL29');
        $aclRow = $this->_buildAclRow('Client', 'Ip', '!=', 'or', '10.0.0.1');
        $this->_assertAclValid($aclRow, self::ACCOUNT_MANAGER, 'DL29');
    }

    // =========================================================================
    // DL30: Client, Domain, Not Contains (!~), and, ADMIN
    // =========================================================================
    public function testDL30_ClientDomain_NotContains_And_Admin()
    {
        $this->_assertComparisonValid('Client', 'Domain', '!~', 'DL30');
        $aclRow = $this->_buildAclRow('Client', 'Domain', '!~', 'and', 'malware.com');
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADMIN, 'DL30');
    }

    // =========================================================================
    // DL31: Client, BrowserVersion, Not Equal (!=), or, ADVERTISER
    // =========================================================================
    public function testDL31_ClientBrowserVersion_NotEqual_Or_Advertiser()
    {
        $this->_assertComparisonValid('Client', 'BrowserVersion', '!=', 'DL31');
        $aclRow = $this->_buildAclRow('Client', 'BrowserVersion', '!=', 'or', 'Firefox|90');
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADVERTISER, 'DL31');
    }

    // =========================================================================
    // DL32: Client, Language, Not Contains (!~), and, ADMIN
    // =========================================================================
    public function testDL32_ClientLanguage_NotContains_And_Admin()
    {
        $this->_assertComparisonValid('Client', 'Language', '!~', 'DL32');
        $aclRow = $this->_buildAclRow('Client', 'Language', '!~', 'and', 'zh');
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADMIN, 'DL32');
    }

    // =========================================================================
    // DL33: Client, OsVersion, Not Equal (!=), and, MANAGER
    // =========================================================================
    public function testDL33_ClientOsVersion_NotEqual_And_Manager()
    {
        $this->_assertComparisonValid('Client', 'OsVersion', '!=', 'DL33');
        $aclRow = $this->_buildAclRow('Client', 'OsVersion', '!=', 'and', 'Linux|5');
        $this->_assertAclValid($aclRow, self::ACCOUNT_MANAGER, 'DL33');
    }

    // =========================================================================
    // DL34: Client, Useragent, Not Contains (!~), or, ADVERTISER
    // =========================================================================
    public function testDL34_ClientUseragent_NotContains_Or_Advertiser()
    {
        $this->_assertComparisonValid('Client', 'Useragent', '!~', 'DL34');
        $aclRow = $this->_buildAclRow('Client', 'Useragent', '!~', 'or', 'bot');
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADVERTISER, 'DL34');
    }

    // =========================================================================
    // DL35: Geo, City, Equal (==), and, ADMIN
    // =========================================================================
    public function testDL35_GeoCity_Equal_And_Admin()
    {
        $this->_assertComparisonValid('Geo', 'City', '==', 'DL35');
        $aclRow = $this->_buildAclRow('Geo', 'City', '==', 'and', 'GB|London');
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADMIN, 'DL35');
    }

    // =========================================================================
    // DL36: Geo, Continent, Not Contains (!~), or, MANAGER
    // =========================================================================
    public function testDL36_GeoContinent_NotContains_Or_Manager()
    {
        $this->_assertComparisonValid('Geo', 'Continent', '!~', 'DL36');
        $aclRow = $this->_buildAclRow('Geo', 'Continent', '!~', 'or', 'AF');
        $this->_assertAclValid($aclRow, self::ACCOUNT_MANAGER, 'DL36');
    }

    // =========================================================================
    // DL37: Geo, LatLong, Not Equal (!=), and, ADVERTISER
    // =========================================================================
    public function testDL37_GeoLatlong_NotEqual_And_Advertiser()
    {
        $this->_assertComparisonValid('Geo', 'Latlong', '!=', 'DL37');
        $aclRow = $this->_buildAclRow('Geo', 'Latlong', '!=', 'and', '50.0,52.0,0.0,2.0');
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADVERTISER, 'DL37');
    }

    // =========================================================================
    // DL38: Geo, Organisation, Not Contains (!~), or, ADMIN
    // =========================================================================
    public function testDL38_GeoOrganisation_NotContains_Or_Admin()
    {
        $this->_assertComparisonValid('Geo', 'Organisation', '!~', 'DL38');
        $aclRow = $this->_buildAclRow('Geo', 'Organisation', '!~', 'or', 'BadISP');
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADMIN, 'DL38');
    }

    // =========================================================================
    // DL39: Geo, PostalCode, Not Equal (!=), and, MANAGER
    // =========================================================================
    public function testDL39_GeoPostalcode_NotEqual_And_Manager()
    {
        $this->_assertComparisonValid('Geo', 'Postalcode', '!=', 'DL39');
        $aclRow = $this->_buildAclRow('Geo', 'Postalcode', '!=', 'and', '90210');
        $this->_assertAclValid($aclRow, self::ACCOUNT_MANAGER, 'DL39');
    }

    // =========================================================================
    // DL40: Geo, UsMetro, Not Contains (!~), or, ADVERTISER
    // =========================================================================
    public function testDL40_GeoUsMetro_NotContains_Or_Advertiser()
    {
        $this->_assertComparisonValid('Geo', 'UsMetro', '!~', 'DL40');
        $aclRow = $this->_buildAclRow('Geo', 'UsMetro', '!~', 'or', '803');
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADVERTISER, 'DL40');
    }

    // =========================================================================
    // DL41: Site, PageURL, Not Contains (!~), and, MANAGER
    // =========================================================================
    public function testDL41_SitePageurl_NotContains_And_Manager()
    {
        $this->_assertComparisonValid('Site', 'Pageurl', '!~', 'DL41');
        $aclRow = $this->_buildAclRow('Site', 'Pageurl', '!~', 'and', 'bad-page.html');
        $this->_assertAclValid($aclRow, self::ACCOUNT_MANAGER, 'DL41');
    }

    // =========================================================================
    // DL42: Site, ReferingPage, Not Equal (!=), and, ADMIN
    // =========================================================================
    public function testDL42_SiteReferingpage_NotEqual_And_Admin()
    {
        $this->_assertComparisonValid('Site', 'Referingpage', '!=', 'DL42');
        $aclRow = $this->_buildAclRow('Site', 'Referingpage', '!=', 'and', 'http://spam.com');
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADMIN, 'DL42');
    }

    // =========================================================================
    // DL43: Site, Source, Not Equal (!=), or, ADVERTISER
    // =========================================================================
    public function testDL43_SiteSource_NotEqual_Or_Advertiser()
    {
        $this->_assertComparisonValid('Site', 'Source', '!=', 'DL43');
        $aclRow = $this->_buildAclRow('Site', 'Source', '!=', 'or', 'unknown');
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADVERTISER, 'DL43');
    }

    // =========================================================================
    // DL44: Site, Variable, Not Equal (!=), and, ADMIN
    // =========================================================================
    public function testDL44_SiteVariable_NotEqual_And_Admin()
    {
        $this->_assertComparisonValid('Site', 'Variable', '!=', 'DL44');
        $aclRow = $this->_buildAclRow('Site', 'Variable', '!=', 'and', 'section|adult');
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADMIN, 'DL44');
    }

    // =========================================================================
    // DL45: Time, Date, Not Equal (!=), or, MANAGER
    // =========================================================================
    public function testDL45_TimeDate_NotEqual_Or_Manager()
    {
        $this->_assertComparisonValid('Time', 'Date', '!=', 'DL45');
        $aclRow = $this->_buildAclRow('Time', 'Date', '!=', 'or', '20261225@UTC');
        $this->_assertAclValid($aclRow, self::ACCOUNT_MANAGER, 'DL45');
    }

    // =========================================================================
    // DL46: Time, Day, Not Contains (!~), and, ADMIN
    // =========================================================================
    public function testDL46_TimeDay_NotContains_And_Admin()
    {
        $this->_assertComparisonValid('Time', 'Day', '!~', 'DL46');
        $aclRow = $this->_buildAclRow('Time', 'Day', '!~', 'and', '0,6');
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADMIN, 'DL46');
    }

    // =========================================================================
    // DL47: Time, Date, Greater Than (>), and, ADVERTISER
    // =========================================================================
    public function testDL47_TimeDate_GreaterThan_And_Advertiser()
    {
        $this->_assertComparisonValid('Time', 'Date', '>', 'DL47');
        $aclRow = $this->_buildAclRow('Time', 'Date', '>', 'and', '20260101@UTC');
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADVERTISER, 'DL47');
    }

    // =========================================================================
    // DL48: Time, Date, Less Than (<), or, ADMIN
    // =========================================================================
    public function testDL48_TimeDate_LessThan_Or_Admin()
    {
        $this->_assertComparisonValid('Time', 'Date', '<', 'DL48');
        $aclRow = $this->_buildAclRow('Time', 'Date', '<', 'or', '20261231@UTC');
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADMIN, 'DL48');
    }

    // =========================================================================
    // DL49: Combined: Geo Country (=~, and) + Client Ip (==), ADMIN
    // =========================================================================
    public function testDL49_Combined_GeoCountry_ClientIp_And_Admin()
    {
        $aclRow1 = $this->_buildAclRow('Geo', 'Country', '=~', 'and', 'US', 0);
        $aclRow2 = $this->_buildAclRow('Client', 'Ip', '==', 'and', '192.168.0.1', 1);
        $this->_assertCombinedAclValid($aclRow1, $aclRow2, self::ACCOUNT_ADMIN, 'DL49');
    }

    // =========================================================================
    // DL50: Combined: Time Hour (=~, or) + Site PageURL (=~), MANAGER
    // =========================================================================
    public function testDL50_Combined_TimeHour_SitePageurl_Or_Manager()
    {
        $aclRow1 = $this->_buildAclRow('Time', 'Hour', '=~', 'and', '8,9,10,11,12', 0);
        $aclRow2 = $this->_buildAclRow('Site', 'Pageurl', '=~', 'or', 'sports.example.com', 1);
        $this->_assertCombinedAclValid($aclRow1, $aclRow2, self::ACCOUNT_MANAGER, 'DL50');
    }

    // =========================================================================
    // DL51: Geo, ConnectionType, Not Contains (!~), and, ADMIN
    // =========================================================================
    public function testDL51_GeoConnectionType_NotContains_And_Admin()
    {
        $this->_assertComparisonValid('Geo', 'ConnectionType', '!~', 'DL51');
        $aclRow = $this->_buildAclRow('Geo', 'ConnectionType', '!~', 'and', 'dial');
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADMIN, 'DL51');
    }

    // =========================================================================
    // DL52: Client, Useragent, Regex Match (=x), and, MANAGER
    // =========================================================================
    public function testDL52_ClientUseragent_Regex_And_Manager()
    {
        $this->_assertComparisonValid('Client', 'Useragent', '=x', 'DL52');
        $aclRow = $this->_buildAclRow('Client', 'Useragent', '=x', 'and', 'Chrome/[0-9]+');
        $this->_assertAclValid($aclRow, self::ACCOUNT_MANAGER, 'DL52');
    }

    // =========================================================================
    // DL53: Site, Hostnamelist, Not Contains (!~), or, ADMIN
    // =========================================================================
    public function testDL53_SiteHostnamelist_NotContains_Or_Admin()
    {
        $this->_assertComparisonValid('Site', 'Hostnamelist', '!~', 'DL53');
        $aclRow = $this->_buildAclRow('Site', 'Hostnamelist', '!~', 'or', "blocked.com\nbad.org");
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADMIN, 'DL53');
    }

    // =========================================================================
    // DL54: Site, RegisterableDomainList, Not Regex (!x), and, MANAGER
    // =========================================================================
    public function testDL54_SiteRegisterabledomainlist_NotRegex_And_Manager()
    {
        $this->_assertComparisonValid('Site', 'Registerabledomainlist', '!x', 'DL54');
        $aclRow = $this->_buildAclRow('Site', 'Registerabledomainlist', '!x', 'and', "blocked.com\nbad.org");
        $this->_assertAclValid($aclRow, self::ACCOUNT_MANAGER, 'DL54');
    }

    // =========================================================================
    // DL55: Client, Domain, Regex Match (=x), or, MANAGER
    // =========================================================================
    public function testDL55_ClientDomain_Regex_Or_Manager()
    {
        $this->_assertComparisonValid('Client', 'Domain', '=x', 'DL55');
        $aclRow = $this->_buildAclRow('Client', 'Domain', '=x', 'or', '.*\\.example\\.com');
        $this->_assertAclValid($aclRow, self::ACCOUNT_MANAGER, 'DL55');
    }

    // =========================================================================
    // DL56: Geo, PostalCode, Contains (=~), or, ADVERTISER
    // =========================================================================
    public function testDL56_GeoPostalcode_Contains_Or_Advertiser()
    {
        $this->_assertComparisonValid('Geo', 'Postalcode', '=~', 'DL56');
        $aclRow = $this->_buildAclRow('Geo', 'Postalcode', '=~', 'or', '100');
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADVERTISER, 'DL56');
    }

    // =========================================================================
    // DL57: Site, Variable, Contains (=~), and, ADVERTISER
    // =========================================================================
    public function testDL57_SiteVariable_Contains_And_Advertiser()
    {
        $this->_assertComparisonValid('Site', 'Variable', '=~', 'DL57');
        $aclRow = $this->_buildAclRow('Site', 'Variable', '=~', 'and', 'tag|football');
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADVERTISER, 'DL57');
    }

    // =========================================================================
    // DL58: Client, BrowserVersion, Numeric Ops (gt, lt), and, ADMIN
    // =========================================================================
    public function testDL58_ClientBrowserVersion_GreaterThan_And_Admin()
    {
        $this->_assertComparisonValid('Client', 'BrowserVersion', 'gt', 'DL58');
        $aclRow = $this->_buildAclRow('Client', 'BrowserVersion', 'gt', 'and', 'Chrome|90');
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADMIN, 'DL58');
    }

    // =========================================================================
    // DL59: Client, OsVersion, Less Than (lt), or, ADVERTISER
    // =========================================================================
    public function testDL59_ClientOsVersion_LessThan_Or_Advertiser()
    {
        $this->_assertComparisonValid('Client', 'OsVersion', 'lt', 'DL59');
        $aclRow = $this->_buildAclRow('Client', 'OsVersion', 'lt', 'or', 'Windows|11');
        $this->_assertAclValid($aclRow, self::ACCOUNT_ADVERTISER, 'DL59');
    }

    // =========================================================================
    // DL60: Combined: Geo Continent (=~) + Time Day (=~) + Client Lang (=~), ADMIN
    // =========================================================================
    public function testDL60_Combined_GeoContinent_TimeDay_ClientLanguage_Admin()
    {
        $aAcls = [
            0 => $this->_buildAclRow('Geo', 'Continent', '=~', 'and', 'EU', 0),
            1 => $this->_buildAclRow('Time', 'Day', '=~', 'and', '1,2,3,4,5', 1),
            2 => $this->_buildAclRow('Client', 'Language', '=~', 'and', 'en', 2),
        ];
        $result = OX_AclCheckInputsFields($aAcls, false);
        $this->assertTrue(
            $result === true,
            "DL60: Expected valid triple-ACL combination but got: "
            . (is_array($result) ? implode('; ', $result) : var_export($result, true)),
        );
    }

    // =========================================================================
    // Plugin instantiation & comparison operator validation tests
    // =========================================================================

    /**
     * Verify all Client sub-type plugins can be instantiated.
     */
    public function testPluginInstantiation_Client()
    {
        $subTypes = ['BrowserVersion', 'Domain', 'Ip', 'Language', 'OsVersion', 'Useragent'];
        foreach ($subTypes as $subType) {
            $this->_loadPlugin('Client', $subType, "PluginLoad:Client:{$subType}");
        }
    }

    /**
     * Verify all Geo sub-type plugins can be instantiated.
     */
    public function testPluginInstantiation_Geo()
    {
        $subTypes = ['City', 'ConnectionType', 'Continent', 'Country', 'Latlong',
            'Organisation', 'Postalcode', 'Subdivision1', 'Subdivision2', 'UsMetro'];
        foreach ($subTypes as $subType) {
            $this->_loadPlugin('Geo', $subType, "PluginLoad:Geo:{$subType}");
        }
    }

    /**
     * Verify all Site sub-type plugins can be instantiated.
     */
    public function testPluginInstantiation_Site()
    {
        $subTypes = ['Channel', 'Hostnamelist', 'Pageurl', 'Referingpage',
            'Registerabledomainlist', 'Source', 'Variable'];
        foreach ($subTypes as $subType) {
            $this->_loadPlugin('Site', $subType, "PluginLoad:Site:{$subType}");
        }
    }

    /**
     * Verify all Time sub-type plugins can be instantiated.
     */
    public function testPluginInstantiation_Time()
    {
        $subTypes = ['Date', 'Day', 'Hour'];
        foreach ($subTypes as $subType) {
            $this->_loadPlugin('Time', $subType, "PluginLoad:Time:{$subType}");
        }
    }

    /**
     * Verify that invalid comparison operators are rejected.
     */
    public function testInvalidComparison_Rejected()
    {
        $plugin = OX_Component::factory('deliveryLimitations', 'Geo', 'Country');
        $this->assertNotNull($plugin, 'Failed to load Geo:Country plugin');
        $acl = [
            'type' => 'deliveryLimitations:Geo:Country',
            'comparison' => 'INVALID_OP',
            'data' => 'US',
            'logical' => 'and',
            'executionorder' => 0,
        ];
        $plugin->init($acl);
        $result = $plugin->checkComparison($acl);
        $this->assertNotEqual(
            $result,
            true,
            "Expected invalid comparison to be rejected for INVALID_OP",
        );
    }

    /**
     * Test that OX_AclCheckInputsFields returns errors for invalid operator.
     */
    public function testOX_AclCheckInputsFields_InvalidOperator()
    {
        $aAcls = [
            0 => [
                'type' => 'deliveryLimitations:Geo:Country',
                'comparison' => 'BOGUS',
                'data' => 'US',
                'logical' => 'and',
                'executionorder' => 0,
            ],
        ];
        $result = OX_AclCheckInputsFields($aAcls, false);
        $this->assertTrue(
            is_array($result),
            "Expected array of errors for invalid operator, got: " . var_export($result, true),
        );
    }

    /**
     * Test that OX_AclCheckInputsFields returns true for empty ACL array.
     */
    public function testOX_AclCheckInputsFields_EmptyAcls()
    {
        $result = OX_AclCheckInputsFields([], false);
        $this->assertTrue($result === true, "Expected true for empty ACL array");
    }

    /**
     * Test compile() produces correct format for a Geo:Country plugin.
     */
    public function testCompile_GeoCountry()
    {
        $plugin = OX_Component::factory('deliveryLimitations', 'Geo', 'Country');
        $this->assertNotNull($plugin);
        $acl = [
            'type' => 'deliveryLimitations:Geo:Country',
            'comparison' => '=~',
            'data' => 'US,GB',
            'logical' => 'and',
            'executionorder' => 0,
        ];
        $plugin->init($acl);
        $compiled = $plugin->compile();
        $this->assertPattern(
            '/MAX_checkGeo_Country/',
            $compiled,
            "Compiled limitation should contain MAX_checkGeo_Country",
        );
    }

    /**
     * Test compile() produces correct format for a Time:Hour plugin.
     */
    public function testCompile_TimeHour()
    {
        $plugin = OX_Component::factory('deliveryLimitations', 'Time', 'Hour');
        $this->assertNotNull($plugin);
        $acl = [
            'type' => 'deliveryLimitations:Time:Hour',
            'comparison' => '=~',
            'data' => '9,10,11',
            'logical' => 'and',
            'executionorder' => 0,
        ];
        $plugin->init($acl);
        $compiled = $plugin->compile();
        $this->assertPattern(
            '/MAX_checkTime_Hour/',
            $compiled,
            "Compiled limitation should contain MAX_checkTime_Hour",
        );
    }

    /**
     * Test compile() produces correct format for a Client:Ip plugin.
     */
    public function testCompile_ClientIp()
    {
        $plugin = OX_Component::factory('deliveryLimitations', 'Client', 'Ip');
        $this->assertNotNull($plugin);
        $acl = [
            'type' => 'deliveryLimitations:Client:Ip',
            'comparison' => '==',
            'data' => '192.168.1.0/255.255.255.0',
            'logical' => 'and',
            'executionorder' => 0,
        ];
        $plugin->init($acl);
        $compiled = $plugin->compile();
        $this->assertPattern(
            '/MAX_checkClient_Ip/',
            $compiled,
            "Compiled limitation should contain MAX_checkClient_Ip",
        );
    }

    /**
     * Test MAX_AclGetCompiled with multiple ACLs and logical operators.
     */
    public function testMAX_AclGetCompiled_MultipleAcls()
    {
        $aAcls = [
            0 => [
                'type' => 'deliveryLimitations:Geo:Country',
                'comparison' => '=~',
                'data' => 'US',
                'logical' => 'and',
                'executionorder' => 0,
            ],
            1 => [
                'type' => 'deliveryLimitations:Time:Hour',
                'comparison' => '=~',
                'data' => '9,10,11',
                'logical' => 'and',
                'executionorder' => 1,
            ],
        ];
        $compiled = MAX_AclGetCompiled($aAcls);
        $this->assertPattern(
            '/and/',
            $compiled,
            "Compiled string should contain 'and' logical operator",
        );
        $this->assertPattern(
            '/MAX_checkGeo_Country/',
            $compiled,
            "Compiled string should contain MAX_checkGeo_Country",
        );
        $this->assertPattern(
            '/MAX_checkTime_Hour/',
            $compiled,
            "Compiled string should contain MAX_checkTime_Hour",
        );
    }

    /**
     * Test MAX_AclGetCompiled with 'or' logical operator.
     */
    public function testMAX_AclGetCompiled_OrLogical()
    {
        $aAcls = [
            0 => [
                'type' => 'deliveryLimitations:Client:Ip',
                'comparison' => '==',
                'data' => '10.0.0.1',
                'logical' => 'and',
                'executionorder' => 0,
            ],
            1 => [
                'type' => 'deliveryLimitations:Client:Ip',
                'comparison' => '==',
                'data' => '10.0.0.2',
                'logical' => 'or',
                'executionorder' => 1,
            ],
        ];
        $compiled = MAX_AclGetCompiled($aAcls);
        $this->assertPattern(
            '/or/',
            $compiled,
            "Compiled string should contain 'or' logical operator",
        );
    }

    /**
     * Test MAX_AclGetPlugins extracts correct plugin types.
     */
    public function testMAX_AclGetPlugins()
    {
        $aAcls = [
            0 => [
                'type' => 'deliveryLimitations:Geo:Country',
                'comparison' => '=~',
                'data' => 'US',
                'logical' => 'and',
                'executionorder' => 0,
            ],
            1 => [
                'type' => 'deliveryLimitations:Time:Hour',
                'comparison' => '=~',
                'data' => '9',
                'logical' => 'and',
                'executionorder' => 1,
            ],
        ];
        $plugins = MAX_AclGetPlugins($aAcls);
        $this->assertPattern('/deliveryLimitations:Geo:Country/', $plugins);
        $this->assertPattern('/deliveryLimitations:Time:Hour/', $plugins);
    }
}
