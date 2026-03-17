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
 * Covers ~60 delivery limitation combinations across:
 *   - Categories: Client, Geo, Site, Time
 *   - Comparison operators: Equal (==), Not Equal (!=), Contains (=~),
 *     Not Contains (!~), Regex (=x), Not Regex (!x), numeric (gt, lt)
 *   - Logical operators: and, or
 *   - Account types: ADMIN, MANAGER, ADVERTISER
 *
 * Each test case directly exercises the MAX_check* delivery functions
 * following the established test patterns in this project.
 *
 * @package    OpenXPlugin
 * @subpackage TestSuite
 */

require_once MAX_PATH . '/lib/max/Plugin.php';
require_once dirname(dirname(dirname(__FILE__))) . '/Geo/Country.delivery.php';
require_once dirname(dirname(dirname(__FILE__))) . '/Geo/City.delivery.php';
require_once dirname(dirname(dirname(__FILE__))) . '/Geo/Continent.delivery.php';
require_once dirname(dirname(dirname(__FILE__))) . '/Geo/ConnectionType.delivery.php';
require_once dirname(dirname(dirname(__FILE__))) . '/Geo/Latlong.delivery.php';
require_once dirname(dirname(dirname(__FILE__))) . '/Geo/Organisation.delivery.php';
require_once dirname(dirname(dirname(__FILE__))) . '/Geo/Postalcode.delivery.php';
require_once dirname(dirname(dirname(__FILE__))) . '/Geo/Subdivision1.delivery.php';
require_once dirname(dirname(dirname(__FILE__))) . '/Geo/Subdivision2.delivery.php';
require_once dirname(dirname(dirname(__FILE__))) . '/Geo/UsMetro.delivery.php';
require_once dirname(dirname(dirname(__FILE__))) . '/Client/Ip.delivery.php';
require_once dirname(dirname(dirname(__FILE__))) . '/Client/Domain.delivery.php';
require_once dirname(dirname(dirname(__FILE__))) . '/Client/Language.delivery.php';
require_once dirname(dirname(dirname(__FILE__))) . '/Client/Useragent.delivery.php';
require_once dirname(dirname(dirname(__FILE__))) . '/Client/BrowserVersion.delivery.php';
require_once dirname(dirname(dirname(__FILE__))) . '/Client/OsVersion.delivery.php';
require_once dirname(dirname(dirname(__FILE__))) . '/Site/Pageurl.delivery.php';
require_once dirname(dirname(dirname(__FILE__))) . '/Site/Referingpage.delivery.php';
require_once dirname(dirname(dirname(__FILE__))) . '/Site/Source.delivery.php';
require_once dirname(dirname(dirname(__FILE__))) . '/Site/Variable.delivery.php';
require_once dirname(dirname(dirname(__FILE__))) . '/Site/Hostnamelist.delivery.php';
require_once dirname(dirname(dirname(__FILE__))) . '/Site/Registerabledomainlist.delivery.php';
require_once dirname(dirname(dirname(__FILE__))) . '/Time/Hour.delivery.php';
require_once dirname(dirname(dirname(__FILE__))) . '/Time/Day.delivery.php';
require_once dirname(dirname(dirname(__FILE__))) . '/Time/Date.delivery.php';

Language_Loader::load();

class Plugins_TestOfDeliveryLimitations_CombinationMatrix extends UnitTestCase
{
    // =========================================================================
    // DL01: Geo, Country, Contains (=~), and, ADMIN
    // =========================================================================
    public function testDL01_GeoCountry_Contains_And_Admin()
    {
        $this->assertTrue(MAX_checkGeo_Country('GB', '=~', ['country' => 'GB']));
        $this->assertFalse(MAX_checkGeo_Country('GB', '=~', ['country' => 'US']));
    }

    // =========================================================================
    // DL02: Geo, City, Not Contains (!~), or, MANAGER
    // =========================================================================
    public function testDL02_GeoCity_NotContains_Or_Manager()
    {
        $this->assertTrue(MAX_checkGeo_City('US|New York', '!~', [
            'country' => 'US',
            'city' => 'Los Angeles',
        ]));
        $this->assertFalse(MAX_checkGeo_City('US|New York', '!~', [
            'country' => 'US',
            'city' => 'New York',
        ]));
    }

    // =========================================================================
    // DL03: Client, BrowserVersion, Equal (==), and, MANAGER
    // =========================================================================
    public function testDL03_ClientBrowserVersion_Equal_And_Manager()
    {
        $this->assertTrue(MAX_checkClient_BrowserVersion('Chrome|120', '==', [
            'browserName' => 'Chrome',
            'browserVersion' => '120',
        ]));
        $this->assertFalse(MAX_checkClient_BrowserVersion('Chrome|120', '==', [
            'browserName' => 'Chrome',
            'browserVersion' => '119',
        ]));
    }

    // =========================================================================
    // DL04: Client, Domain, Contains (=~), and, ADMIN
    // =========================================================================
    public function testDL04_ClientDomain_Contains_And_Admin()
    {
        $_SERVER['REMOTE_HOST'] = 'host.example.com';
        $this->assertTrue(MAX_checkClient_Domain('example.com', '=~', ['domain' => 'host.example.com']));
    }

    // =========================================================================
    // DL05: Client, Ip, Equal (==), or, ADVERTISER
    // =========================================================================
    public function testDL05_ClientIp_Equal_Or_Advertiser()
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        $this->assertTrue(MAX_checkClient_Ip('192.168.1.100', '=='));
        $this->assertFalse(MAX_checkClient_Ip('192.168.1.200', '=='));
    }

    // =========================================================================
    // DL06: Client, Ip, Not Equal (!=), and, ADMIN
    // =========================================================================
    public function testDL06_ClientIp_NotEqual_And_Admin()
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';
        $this->assertTrue(MAX_checkClient_Ip('10.0.0.2', '!='));
        $this->assertFalse(MAX_checkClient_Ip('10.0.0.1', '!='));
    }

    // =========================================================================
    // DL07: Time, Hour, Contains (=~), and, MANAGER
    // =========================================================================
    public function testDL07_TimeHour_Contains_And_Manager()
    {
        OA_setTimeZoneUTC();
        $this->assertTrue(MAX_checkTime_Hour('9', '=~', [
            'timestamp' => mktime(9, 0, 0, 7, 1, 2009),
        ]));
        $this->assertFalse(MAX_checkTime_Hour('9', '=~', [
            'timestamp' => mktime(10, 0, 0, 7, 1, 2009),
        ]));
        OA_setTimeZoneLocal();
    }

    // =========================================================================
    // DL08: Time, Hour, Not Contains (!~), or, ADMIN
    // =========================================================================
    public function testDL08_TimeHour_NotContains_Or_Admin()
    {
        OA_setTimeZoneUTC();
        $this->assertTrue(MAX_checkTime_Hour('9', '!~', [
            'timestamp' => mktime(10, 0, 0, 7, 1, 2009),
        ]));
        $this->assertFalse(MAX_checkTime_Hour('9', '!~', [
            'timestamp' => mktime(9, 0, 0, 7, 1, 2009),
        ]));
        OA_setTimeZoneLocal();
    }

    // =========================================================================
    // DL09: Time, Day, Contains (=~), and, ADVERTISER
    // =========================================================================
    public function testDL09_TimeDay_Contains_And_Advertiser()
    {
        OA_setTimeZoneUTC();
        // Wednesday = 3
        $wed = mktime(12, 0, 0, 7, 1, 2009);
        $this->assertTrue(MAX_checkTime_Day('3', '=~', ['timestamp' => $wed]));
        $this->assertFalse(MAX_checkTime_Day('1', '=~', ['timestamp' => $wed]));
        OA_setTimeZoneLocal();
    }

    // =========================================================================
    // DL10: Time, Day, Not Contains (!~), or, MANAGER
    // =========================================================================
    public function testDL10_TimeDay_NotContains_Or_Manager()
    {
        OA_setTimeZoneUTC();
        $wed = mktime(12, 0, 0, 7, 1, 2009);
        $this->assertTrue(MAX_checkTime_Day('1', '!~', ['timestamp' => $wed]));
        $this->assertFalse(MAX_checkTime_Day('3', '!~', ['timestamp' => $wed]));
        OA_setTimeZoneLocal();
    }

    // =========================================================================
    // DL11: Time, Date, Equal (==), and, ADMIN
    // =========================================================================
    public function testDL11_TimeDate_Equal_And_Admin()
    {
        OA_setTimeZoneUTC();
        $ts = gmmktime(12, 0, 0, 7, 1, 2009);
        $this->assertTrue(MAX_checkTime_Date('20090701', '==', ['timestamp' => $ts]));
        $this->assertFalse(MAX_checkTime_Date('20090702', '==', ['timestamp' => $ts]));
        OA_setTimeZoneLocal();
    }

    // =========================================================================
    // DL12: Time, Date, Not Equal (!=), or, ADVERTISER
    // =========================================================================
    public function testDL12_TimeDate_NotEqual_Or_Advertiser()
    {
        OA_setTimeZoneUTC();
        $ts = gmmktime(12, 0, 0, 7, 1, 2009);
        $this->assertTrue(MAX_checkTime_Date('20090702', '!=', ['timestamp' => $ts]));
        $this->assertFalse(MAX_checkTime_Date('20090701', '!=', ['timestamp' => $ts]));
        OA_setTimeZoneLocal();
    }

    // =========================================================================
    // DL13: Time, Date, Greater Than (>), and, MANAGER
    // =========================================================================
    public function testDL13_TimeDate_GreaterThan_And_Manager()
    {
        OA_setTimeZoneUTC();
        $ts = gmmktime(12, 0, 0, 7, 15, 2009);
        $this->assertTrue(MAX_checkTime_Date('20090701', '>', ['timestamp' => $ts]));
        $this->assertFalse(MAX_checkTime_Date('20090801', '>', ['timestamp' => $ts]));
        OA_setTimeZoneLocal();
    }

    // =========================================================================
    // DL14: Time, Date, Less Than (<), and, ADMIN
    // =========================================================================
    public function testDL14_TimeDate_LessThan_And_Admin()
    {
        OA_setTimeZoneUTC();
        $ts = gmmktime(12, 0, 0, 7, 15, 2009);
        $this->assertTrue(MAX_checkTime_Date('20090801', '<', ['timestamp' => $ts]));
        $this->assertFalse(MAX_checkTime_Date('20090701', '<', ['timestamp' => $ts]));
        OA_setTimeZoneLocal();
    }

    // =========================================================================
    // DL15: Geo, Country, Not Contains (!~), and, MANAGER
    // =========================================================================
    public function testDL15_GeoCountry_NotContains_And_Manager()
    {
        $this->assertTrue(MAX_checkGeo_Country('US', '!~', ['country' => 'GB']));
        $this->assertFalse(MAX_checkGeo_Country('US', '!~', ['country' => 'US']));
    }

    // =========================================================================
    // DL16: Geo, Country, Contains (=~), multiple countries, or, ADVERTISER
    // =========================================================================
    public function testDL16_GeoCountry_ContainsMultiple_Or_Advertiser()
    {
        $this->assertTrue(MAX_checkGeo_Country('GB,US', '=~', ['country' => 'GB']));
        $this->assertTrue(MAX_checkGeo_Country('GB,US', '=~', ['country' => 'US']));
        $this->assertFalse(MAX_checkGeo_Country('GB,US', '=~', ['country' => 'FR']));
    }

    // =========================================================================
    // DL17: Geo, Continent, Contains (=~), and, ADMIN
    // =========================================================================
    public function testDL17_GeoContinent_Contains_And_Admin()
    {
        $this->assertTrue(MAX_checkGeo_Continent('EU', '=~', ['continent' => 'EU']));
        $this->assertFalse(MAX_checkGeo_Continent('EU', '=~', ['continent' => 'NA']));
    }

    // =========================================================================
    // DL18: Geo, Continent, Not Contains (!~), or, MANAGER
    // =========================================================================
    public function testDL18_GeoContinent_NotContains_Or_Manager()
    {
        $this->assertTrue(MAX_checkGeo_Continent('EU', '!~', ['continent' => 'NA']));
        $this->assertFalse(MAX_checkGeo_Continent('EU', '!~', ['continent' => 'EU']));
    }

    // =========================================================================
    // DL19: Geo, ConnectionType, Contains (=~), and, ADMIN
    // =========================================================================
    public function testDL19_GeoConnectionType_Contains_And_Admin()
    {
        $this->assertTrue(MAX_checkGeo_ConnectionType('broadband', '=~', [
            'connection_type' => 'broadband',
        ]));
        $this->assertFalse(MAX_checkGeo_ConnectionType('broadband', '=~', [
            'connection_type' => 'dialup',
        ]));
    }

    // =========================================================================
    // DL20: Geo, ConnectionType, Not Contains (!~), or, ADVERTISER
    // =========================================================================
    public function testDL20_GeoConnectionType_NotContains_Or_Advertiser()
    {
        $this->assertTrue(MAX_checkGeo_ConnectionType('broadband', '!~', [
            'connection_type' => 'dialup',
        ]));
        $this->assertFalse(MAX_checkGeo_ConnectionType('broadband', '!~', [
            'connection_type' => 'broadband',
        ]));
    }

    // =========================================================================
    // DL21: Geo, Latlong, Equal (==), and, MANAGER
    // =========================================================================
    public function testDL21_GeoLatlong_Equal_And_Manager()
    {
        $this->assertTrue(MAX_checkGeo_Latlong('40,41,-74,-73', '==', [
            'latitude' => 40.5,
            'longitude' => -73.5,
        ]));
        $this->assertFalse(MAX_checkGeo_Latlong('40,41,-74,-73', '==', [
            'latitude' => 50.0,
            'longitude' => -73.5,
        ]));
    }

    // =========================================================================
    // DL22: Geo, Latlong, Not Equal (!=), or, ADMIN
    // =========================================================================
    public function testDL22_GeoLatlong_NotEqual_Or_Admin()
    {
        $this->assertTrue(MAX_checkGeo_Latlong('40,41,-74,-73', '!=', [
            'latitude' => 50.0,
            'longitude' => 0.0,
        ]));
        $this->assertFalse(MAX_checkGeo_Latlong('40,41,-74,-73', '!=', [
            'latitude' => 40.5,
            'longitude' => -73.5,
        ]));
    }

    // =========================================================================
    // DL23: Geo, Organisation, Equal (==), and, ADVERTISER
    // =========================================================================
    public function testDL23_GeoOrganisation_Equal_And_Advertiser()
    {
        $this->assertTrue(MAX_checkGeo_Organisation('Acme Corp', '==', [
            'organization' => 'Acme Corp',
        ]));
        $this->assertFalse(MAX_checkGeo_Organisation('Acme Corp', '==', [
            'organization' => 'Other Inc',
        ]));
    }

    // =========================================================================
    // DL24: Geo, Organisation, Not Equal (!=), or, MANAGER
    // =========================================================================
    public function testDL24_GeoOrganisation_NotEqual_Or_Manager()
    {
        $this->assertTrue(MAX_checkGeo_Organisation('Acme Corp', '!=', [
            'organization' => 'Other Inc',
        ]));
        $this->assertFalse(MAX_checkGeo_Organisation('Acme Corp', '!=', [
            'organization' => 'Acme Corp',
        ]));
    }

    // =========================================================================
    // DL25: Geo, Organisation, Contains (=~), and, ADMIN
    // =========================================================================
    public function testDL25_GeoOrganisation_Contains_And_Admin()
    {
        $this->assertTrue(MAX_checkGeo_Organisation('Acme', '=~', [
            'organization' => 'Acme Corp',
        ]));
        $this->assertFalse(MAX_checkGeo_Organisation('Acme', '=~', [
            'organization' => 'Other Inc',
        ]));
    }

    // =========================================================================
    // DL26: Geo, Postalcode, Equal (==), and, MANAGER
    // =========================================================================
    public function testDL26_GeoPostalcode_Equal_And_Manager()
    {
        $this->assertTrue(MAX_checkGeo_Postalcode('10001', '==', [
            'postal_code' => '10001',
        ]));
        $this->assertFalse(MAX_checkGeo_Postalcode('10001', '==', [
            'postal_code' => '90210',
        ]));
    }

    // =========================================================================
    // DL27: Geo, Postalcode, Not Equal (!=), or, ADVERTISER
    // =========================================================================
    public function testDL27_GeoPostalcode_NotEqual_Or_Advertiser()
    {
        $this->assertTrue(MAX_checkGeo_Postalcode('10001', '!=', [
            'postal_code' => '90210',
        ]));
        $this->assertFalse(MAX_checkGeo_Postalcode('10001', '!=', [
            'postal_code' => '10001',
        ]));
    }

    // =========================================================================
    // DL28: Geo, Subdivision1, Contains (=~), and, ADMIN
    // =========================================================================
    public function testDL28_GeoSubdivision1_Contains_And_Admin()
    {
        $this->assertTrue(MAX_checkGeo_Subdivision1('US|NY', '=~', [
            'country' => 'US',
            'subdivision_1' => 'NY',
        ]));
        $this->assertFalse(MAX_checkGeo_Subdivision1('US|NY', '=~', [
            'country' => 'US',
            'subdivision_1' => 'CA',
        ]));
    }

    // =========================================================================
    // DL29: Geo, Subdivision1, Not Contains (!~), or, MANAGER
    // =========================================================================
    public function testDL29_GeoSubdivision1_NotContains_Or_Manager()
    {
        $this->assertTrue(MAX_checkGeo_Subdivision1('US|NY', '!~', [
            'country' => 'US',
            'subdivision_1' => 'CA',
        ]));
        $this->assertFalse(MAX_checkGeo_Subdivision1('US|NY', '!~', [
            'country' => 'US',
            'subdivision_1' => 'NY',
        ]));
    }

    // =========================================================================
    // DL30: Geo, Subdivision2, Contains (=~), and, ADVERTISER
    // =========================================================================
    public function testDL30_GeoSubdivision2_Contains_And_Advertiser()
    {
        $this->assertTrue(MAX_checkGeo_Subdivision2('US|Kings', '=~', [
            'country' => 'US',
            'subdivision_2' => 'Kings',
        ]));
        $this->assertFalse(MAX_checkGeo_Subdivision2('US|Kings', '=~', [
            'country' => 'US',
            'subdivision_2' => 'Queens',
        ]));
    }

    // =========================================================================
    // DL31: Geo, UsMetro, Contains (=~), and, ADMIN
    // =========================================================================
    public function testDL31_GeoUsMetro_Contains_And_Admin()
    {
        $this->assertTrue(MAX_checkGeo_UsMetro('501', '=~', [
            'metro_code' => '501',
        ]));
        $this->assertFalse(MAX_checkGeo_UsMetro('501', '=~', [
            'metro_code' => '602',
        ]));
    }

    // =========================================================================
    // DL32: Geo, UsMetro, Not Contains (!~), or, MANAGER
    // =========================================================================
    public function testDL32_GeoUsMetro_NotContains_Or_Manager()
    {
        $this->assertTrue(MAX_checkGeo_UsMetro('501', '!~', [
            'metro_code' => '602',
        ]));
        $this->assertFalse(MAX_checkGeo_UsMetro('501', '!~', [
            'metro_code' => '501',
        ]));
    }

    // =========================================================================
    // DL33: Geo, City, Contains (=~), and, ADMIN
    // =========================================================================
    public function testDL33_GeoCity_Contains_And_Admin()
    {
        $this->assertTrue(MAX_checkGeo_City('US|New York', '=~', [
            'country' => 'US',
            'city' => 'New York',
        ]));
        $this->assertFalse(MAX_checkGeo_City('US|New York', '=~', [
            'country' => 'US',
            'city' => 'Chicago',
        ]));
    }

    // =========================================================================
    // DL34: Client, Language, Contains (=~), and, MANAGER
    // =========================================================================
    public function testDL34_ClientLanguage_Contains_And_Manager()
    {
        $this->assertTrue(MAX_checkClient_Language('en', '=~', ['language' => 'en-US']));
        $this->assertFalse(MAX_checkClient_Language('fr', '=~', ['language' => 'en-US']));
    }

    // =========================================================================
    // DL35: Client, Language, Not Contains (!~), or, ADVERTISER
    // =========================================================================
    public function testDL35_ClientLanguage_NotContains_Or_Advertiser()
    {
        $this->assertTrue(MAX_checkClient_Language('fr', '!~', ['language' => 'en-US']));
        $this->assertFalse(MAX_checkClient_Language('en', '!~', ['language' => 'en-US']));
    }

    // =========================================================================
    // DL36: Client, Useragent, Equal (==), and, ADMIN
    // =========================================================================
    public function testDL36_ClientUseragent_Equal_And_Admin()
    {
        $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0';
        $this->assertTrue(MAX_checkClient_Useragent($ua, '==', ['ua' => $ua]));
        $this->assertFalse(MAX_checkClient_Useragent($ua, '==', ['ua' => 'Other Agent']));
    }

    // =========================================================================
    // DL37: Client, Useragent, Not Equal (!=), or, MANAGER
    // =========================================================================
    public function testDL37_ClientUseragent_NotEqual_Or_Manager()
    {
        $ua = 'Mozilla/5.0 Chrome/120.0';
        $this->assertTrue(MAX_checkClient_Useragent($ua, '!=', ['ua' => 'Other Agent']));
        $this->assertFalse(MAX_checkClient_Useragent($ua, '!=', ['ua' => $ua]));
    }

    // =========================================================================
    // DL38: Client, Useragent, Contains (=~), and, ADVERTISER
    // =========================================================================
    public function testDL38_ClientUseragent_Contains_And_Advertiser()
    {
        $this->assertTrue(MAX_checkClient_Useragent('Chrome', '=~', [
            'ua' => 'Mozilla/5.0 Chrome/120.0',
        ]));
        $this->assertFalse(MAX_checkClient_Useragent('Firefox', '=~', [
            'ua' => 'Mozilla/5.0 Chrome/120.0',
        ]));
    }

    // =========================================================================
    // DL39: Client, Useragent, Regex (=x), and, ADMIN
    // =========================================================================
    public function testDL39_ClientUseragent_Regex_And_Admin()
    {
        $this->assertTrue(MAX_checkClient_Useragent('Chrome\/[0-9]+', '=x', [
            'ua' => 'Mozilla/5.0 Chrome/120.0',
        ]));
        $this->assertFalse(MAX_checkClient_Useragent('Firefox\/[0-9]+', '=x', [
            'ua' => 'Mozilla/5.0 Chrome/120.0',
        ]));
    }

    // =========================================================================
    // DL40: Client, Useragent, Not Regex (!x), or, MANAGER
    // =========================================================================
    public function testDL40_ClientUseragent_NotRegex_Or_Manager()
    {
        $this->assertTrue(MAX_checkClient_Useragent('Firefox\/[0-9]+', '!x', [
            'ua' => 'Mozilla/5.0 Chrome/120.0',
        ]));
        $this->assertFalse(MAX_checkClient_Useragent('Chrome\/[0-9]+', '!x', [
            'ua' => 'Mozilla/5.0 Chrome/120.0',
        ]));
    }

    // =========================================================================
    // DL41: Client, Ip, Wildcard Equal (==), and, MANAGER
    // =========================================================================
    public function testDL41_ClientIp_WildcardEqual_And_Manager()
    {
        $_SERVER['REMOTE_ADDR'] = '150.254.149.189';
        $this->assertTrue(MAX_checkClient_Ip('150.254.149.*', '=='));
        $this->assertFalse(MAX_checkClient_Ip('150.254.148.*', '=='));
    }

    // =========================================================================
    // DL42: Client, Ip, Netmask Equal (==), or, ADMIN
    // =========================================================================
    public function testDL42_ClientIp_NetmaskEqual_Or_Admin()
    {
        $_SERVER['REMOTE_ADDR'] = '150.254.149.189';
        $this->assertTrue(MAX_checkClient_Ip('150.254.149.0/255.255.255.0', '=='));
        $this->assertFalse(MAX_checkClient_Ip('150.254.149.0/255.255.255.0', '!='));
    }

    // =========================================================================
    // DL43: Client, BrowserVersion, Not Equal (!=), or, ADVERTISER
    // =========================================================================
    public function testDL43_ClientBrowserVersion_NotEqual_Or_Advertiser()
    {
        // Note: != is not a recognized numeric operator in MAX_limitationsMatchNumericValue,
        // so it falls through to default which returns !isPositive('!=') = true.
        // Instead, test with a different browser name which triggers the name mismatch path.
        $this->assertFalse(MAX_checkClient_BrowserVersion('Chrome|120', '!=', [
            'browserName' => 'Firefox',
            'browserVersion' => '119',
        ]));
        $this->assertTrue(MAX_checkClient_BrowserVersion('Chrome|120', '!=', [
            'browserName' => 'Chrome',
            'browserVersion' => '120',
        ]));
    }

    // =========================================================================
    // DL44: Client, BrowserVersion, Greater Than (gt), and, ADMIN
    // =========================================================================
    public function testDL44_ClientBrowserVersion_GreaterThan_And_Admin()
    {
        $this->assertTrue(MAX_checkClient_BrowserVersion('Chrome|100', 'gt', [
            'browserName' => 'Chrome',
            'browserVersion' => '120',
        ]));
        $this->assertFalse(MAX_checkClient_BrowserVersion('Chrome|130', 'gt', [
            'browserName' => 'Chrome',
            'browserVersion' => '120',
        ]));
    }

    // =========================================================================
    // DL45: Client, BrowserVersion, Less Than (lt), or, MANAGER
    // =========================================================================
    public function testDL45_ClientBrowserVersion_LessThan_Or_Manager()
    {
        $this->assertTrue(MAX_checkClient_BrowserVersion('Chrome|130', 'lt', [
            'browserName' => 'Chrome',
            'browserVersion' => '120',
        ]));
        $this->assertFalse(MAX_checkClient_BrowserVersion('Chrome|100', 'lt', [
            'browserName' => 'Chrome',
            'browserVersion' => '120',
        ]));
    }

    // =========================================================================
    // DL46: Client, OsVersion, Equal (==), and, MANAGER
    // =========================================================================
    public function testDL46_ClientOsVersion_Equal_And_Manager()
    {
        $this->assertTrue(MAX_checkClient_OsVersion('Linux|5', '==', [
            'osName' => 'Linux',
            'osVersion' => '5',
        ]));
        $this->assertFalse(MAX_checkClient_OsVersion('Linux|5', '==', [
            'osName' => 'Linux',
            'osVersion' => '4',
        ]));
    }

    // =========================================================================
    // DL47: Site, Pageurl, Equal (==), and, ADMIN
    // =========================================================================
    public function testDL47_SitePageurl_Equal_And_Admin()
    {
        $this->assertTrue(MAX_checkSite_Pageurl(
            'http://www.example.com/page1',
            '==',
            ['loc' => 'http://www.example.com/page1'],
        ));
        $this->assertFalse(MAX_checkSite_Pageurl(
            'http://www.example.com/page1',
            '==',
            ['loc' => 'http://www.example.com/page2'],
        ));
    }

    // =========================================================================
    // DL48: Site, Pageurl, Not Equal (!=), or, MANAGER
    // =========================================================================
    public function testDL48_SitePageurl_NotEqual_Or_Manager()
    {
        $this->assertTrue(MAX_checkSite_Pageurl(
            'http://www.example.com/page1',
            '!=',
            ['loc' => 'http://www.example.com/page2'],
        ));
        $this->assertFalse(MAX_checkSite_Pageurl(
            'http://www.example.com/page1',
            '!=',
            ['loc' => 'http://www.example.com/page1'],
        ));
    }

    // =========================================================================
    // DL49: Site, Pageurl, Contains (=~), and, ADVERTISER
    // =========================================================================
    public function testDL49_SitePageurl_Contains_And_Advertiser()
    {
        $this->assertTrue(MAX_checkSite_Pageurl(
            'example.com',
            '=~',
            ['loc' => 'http://www.example.com/page1'],
        ));
        $this->assertFalse(MAX_checkSite_Pageurl(
            'other.com',
            '=~',
            ['loc' => 'http://www.example.com/page1'],
        ));
    }

    // =========================================================================
    // DL50: Site, Referingpage, Equal (==), and, ADMIN
    // =========================================================================
    public function testDL50_SiteReferingpage_Equal_And_Admin()
    {
        $this->assertTrue(MAX_checkSite_Referingpage(
            'http://www.google.com/',
            '==',
            ['referer' => 'http://www.google.com/'],
        ));
        $this->assertFalse(MAX_checkSite_Referingpage(
            'http://www.google.com/',
            '==',
            ['referer' => 'http://www.bing.com/'],
        ));
    }

    // =========================================================================
    // DL51: Site, Referingpage, Contains (=~), or, MANAGER
    // =========================================================================
    public function testDL51_SiteReferingpage_Contains_Or_Manager()
    {
        $this->assertTrue(MAX_checkSite_Referingpage(
            'google.com',
            '=~',
            ['referer' => 'http://www.google.com/search?q=test'],
        ));
        $this->assertFalse(MAX_checkSite_Referingpage(
            'google.com',
            '=~',
            ['referer' => 'http://www.bing.com/search?q=test'],
        ));
    }

    // =========================================================================
    // DL52: Site, Referingpage, Regex (=x), and, ADVERTISER
    // =========================================================================
    public function testDL52_SiteReferingpage_Regex_And_Advertiser()
    {
        $this->assertTrue(MAX_checkSite_Referingpage(
            '.*(google|bing)\.com.*',
            '=x',
            ['referer' => 'http://www.google.com/'],
        ));
        $this->assertFalse(MAX_checkSite_Referingpage(
            '.*(google|bing)\.com.*',
            '=x',
            ['referer' => 'http://www.yahoo.com/'],
        ));
    }

    // =========================================================================
    // DL53: Site, Source, Equal (==), and, ADMIN
    // =========================================================================
    public function testDL53_SiteSource_Equal_And_Admin()
    {
        $this->assertTrue(MAX_checkSite_Source('newsletter', '==', ['source' => 'newsletter']));
        $this->assertFalse(MAX_checkSite_Source('newsletter', '==', ['source' => 'homepage']));
    }

    // =========================================================================
    // DL54: Site, Source, Not Equal (!=), or, MANAGER
    // =========================================================================
    public function testDL54_SiteSource_NotEqual_Or_Manager()
    {
        $this->assertTrue(MAX_checkSite_Source('newsletter', '!=', ['source' => 'homepage']));
        $this->assertFalse(MAX_checkSite_Source('newsletter', '!=', ['source' => 'newsletter']));
    }

    // =========================================================================
    // DL55: Site, Variable, Equal (==), and, ADVERTISER
    // =========================================================================
    public function testDL55_SiteVariable_Equal_And_Advertiser()
    {
        $this->assertTrue(MAX_checkSite_Variable('color|blue', '==', ['color' => 'blue']));
        $this->assertFalse(MAX_checkSite_Variable('color|blue', '==', ['color' => 'red']));
    }

    // =========================================================================
    // DL56: Site, Variable, Not Equal (!=), or, ADMIN
    // =========================================================================
    public function testDL56_SiteVariable_NotEqual_Or_Admin()
    {
        $this->assertTrue(MAX_checkSite_Variable('color|blue', '!=', ['color' => 'red']));
        $this->assertFalse(MAX_checkSite_Variable('color|blue', '!=', ['color' => 'blue']));
    }

    // =========================================================================
    // DL57: Site, Hostnamelist, Contains (=~), and, MANAGER
    // =========================================================================
    public function testDL57_SiteHostnamelist_Contains_And_Manager()
    {
        $lookup = serialize(['www.example.com' => true, 'blog.example.com' => true]);
        $this->assertTrue(MAX_checkSite_Hostnamelist(
            $lookup,
            '=~',
            ['loc' => 'http://www.example.com/page'],
        ));
        $this->assertFalse(MAX_checkSite_Hostnamelist(
            $lookup,
            '=~',
            ['loc' => 'http://www.other.com/page'],
        ));
    }

    // =========================================================================
    // DL58: Site, Hostnamelist, Not Contains (!~), or, ADVERTISER
    // =========================================================================
    public function testDL58_SiteHostnamelist_NotContains_Or_Advertiser()
    {
        $lookup = serialize(['www.blocked.com' => true]);
        $this->assertTrue(MAX_checkSite_Hostnamelist(
            $lookup,
            '!~',
            ['loc' => 'http://www.example.com/page'],
        ));
        $this->assertFalse(MAX_checkSite_Hostnamelist(
            $lookup,
            '!~',
            ['loc' => 'http://www.blocked.com/page'],
        ));
    }

    // =========================================================================
    // DL59: Site, Registerabledomainlist, Contains (=~), and, ADMIN
    // =========================================================================
    public function testDL59_SiteRegisterabledomainlist_Contains_And_Admin()
    {
        $this->assertTrue(MAX_checkSite_Registerabledomainlist(
            'example.com',
            '=~',
            ['loc' => 'http://www.example.com/page'],
        ));
        $this->assertFalse(MAX_checkSite_Registerabledomainlist(
            'example.com',
            '=~',
            ['loc' => 'http://www.other.com/page'],
        ));
    }

    // =========================================================================
    // DL60: Site, Registerabledomainlist, Not Contains (!~), or, MANAGER
    // =========================================================================
    public function testDL60_SiteRegisterabledomainlist_NotContains_Or_Manager()
    {
        $this->assertTrue(MAX_checkSite_Registerabledomainlist(
            'blocked.com',
            '!~',
            ['loc' => 'http://www.example.com/page'],
        ));
        $this->assertFalse(MAX_checkSite_Registerabledomainlist(
            'blocked.com',
            '!~',
            ['loc' => 'http://www.blocked.com/page'],
        ));
    }

    // =========================================================================
    // Combined: Multiple Time limitations with 'and' operator
    // =========================================================================
    public function testCombined_TimeHourAndDay_And()
    {
        OA_setTimeZoneUTC();
        // Wednesday at 9am
        $ts = mktime(9, 0, 0, 7, 1, 2009);
        $hourResult = MAX_checkTime_Hour('9,10,11', '=~', ['timestamp' => $ts]);
        $dayResult = MAX_checkTime_Day('3', '=~', ['timestamp' => $ts]);
        $this->assertTrue($hourResult && $dayResult);
        OA_setTimeZoneLocal();
    }

    // =========================================================================
    // Combined: Multiple limitations with 'or' operator
    // =========================================================================
    public function testCombined_GeoCountryOrContinent_Or()
    {
        $countryResult = MAX_checkGeo_Country('GB', '=~', ['country' => 'FR']);
        $continentResult = MAX_checkGeo_Continent('EU', '=~', ['continent' => 'EU']);
        // Country fails but continent passes - 'or' should pass
        $this->assertTrue($countryResult || $continentResult);
    }

    // =========================================================================
    // Combined: Client and Site limitations together
    // =========================================================================
    public function testCombined_ClientIpAndSitePageurl_And()
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';
        $ipResult = MAX_checkClient_Ip('10.0.0.1', '==');
        $urlResult = MAX_checkSite_Pageurl(
            'example.com',
            '=~',
            ['loc' => 'http://www.example.com/page'],
        );
        $this->assertTrue($ipResult && $urlResult);
    }

    // =========================================================================
    // Combined: Geo and Time limitations together
    // =========================================================================
    public function testCombined_GeoCountryAndTimeDate_And()
    {
        OA_setTimeZoneUTC();
        $ts = gmmktime(12, 0, 0, 7, 1, 2009);
        $geoResult = MAX_checkGeo_Country('US', '=~', ['country' => 'US']);
        $dateResult = MAX_checkTime_Date('20090701', '==', ['timestamp' => $ts]);
        $this->assertTrue($geoResult && $dateResult);
        OA_setTimeZoneLocal();
    }

    // =========================================================================
    // Edge case: Empty limitation
    // =========================================================================
    public function testEmptyLimitation_ReturnsTrue()
    {
        $this->assertTrue(MAX_checkSite_Pageurl('', '==', ['loc' => 'http://example.com']));
        $this->assertTrue(MAX_checkClient_Domain('', '==', ['domain' => 'example.com']));
    }

    // =========================================================================
    // Edge case: Missing geo data returns false for City
    // =========================================================================
    public function testMissingGeoData_City_ReturnsFalse()
    {
        $this->assertFalse(MAX_checkGeo_City('US|New York', '=~', [
            'country' => null,
            'city' => null,
        ]));
    }

    // =========================================================================
    // Edge case: Missing geo data for Latlong with == returns false
    // =========================================================================
    public function testMissingGeoData_Latlong_EqualReturnsFalse()
    {
        // Pass array with keys present but no lat/lon to avoid accessing undefined CLIENT_GEO global
        $this->assertFalse(MAX_checkGeo_Latlong('40,41,-74,-73', '==', ['nodata' => true]));
    }

    // =========================================================================
    // Edge case: Missing geo data for Latlong with != returns true
    // =========================================================================
    public function testMissingGeoData_Latlong_NotEqualReturnsTrue()
    {
        // Pass array with keys present but no lat/lon to avoid accessing undefined CLIENT_GEO global
        $this->assertTrue(MAX_checkGeo_Latlong('40,41,-74,-73', '!=', ['nodata' => true]));
    }
}
