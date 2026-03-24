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

require_once MAX_PATH . '/lib/max/Delivery/common.php';
require_once MAX_PATH . '/lib/max/Delivery/adSelect.php';
require_once MAX_PATH . '/lib/max/Delivery/adRender.php';
require_once MAX_PATH . '/lib/max/Delivery/limitations.php';
require_once MAX_PATH . '/lib/max/Delivery/cache.php';
require_once MAX_PATH . '/lib/max/Delivery/log.php';
require_once MAX_PATH . '/lib/max/Delivery/cookie.php';

/**
 * Base class for combinatorial delivery integration tests.
 *
 * Provides helper methods for setting up test data, manipulating cookies,
 * and exercising the delivery pipeline with a real database backend.
 *
 * @package    MaxDelivery
 * @subpackage TestSuite
 */
class Test_DeliveryIntegrationBase extends UnitTestCase
{
    /**
     * Saved cookie state for teardown restoration.
     * @var array
     */
    protected $tmpCookie;

    /**
     * Saved GLOBALS state for teardown restoration.
     * @var array
     */
    protected $tmpMaxConf;

    /**
     * IDs created during the test for cleanup.
     * @var array
     */
    protected $createdIds = [
        'agencies'    => [],
        'advertisers' => [],
        'campaigns'   => [],
        'banners'     => [],
        'zones'       => [],
        'affiliates'  => [],
    ];

    // -----------------------------------------------------------------------
    // Campaign priority constants (mirrors the delivery engine values)
    // -----------------------------------------------------------------------
    const PRIORITY_OVERRIDE  = -1;
    const PRIORITY_REMNANT   = 0;
    const PRIORITY_CONTRACT_MIN = 1;
    const PRIORITY_CONTRACT_MAX = 10;
    const PRIORITY_ECPM      = -2;

    // -----------------------------------------------------------------------
    // Banner storage type constants
    // -----------------------------------------------------------------------
    const STORAGETYPE_SQL  = 'sql';
    const STORAGETYPE_WEB  = 'web';
    const STORAGETYPE_URL  = 'url';
    const STORAGETYPE_HTML = 'html';
    const STORAGETYPE_TXT  = 'txt';

    // -----------------------------------------------------------------------
    // setUp / tearDown
    // -----------------------------------------------------------------------

    public function setUp()
    {
        // Save state
        $this->tmpCookie = $_COOKIE;
        $this->tmpMaxConf = $GLOBALS['_MAX']['CONF'] ?? [];

        // Reset cookies
        $_COOKIE = [];

        // Initialise delivery globals
        MAX_commonInitVariables();

        // Ensure MAX_RAND is set
        if (!isset($GLOBALS['_MAX']['MAX_RAND'])) {
            $GLOBALS['_MAX']['MAX_RAND'] = mt_getrandmax();
        }
    }

    public function tearDown()
    {
        // Restore cookies
        $_COOKIE = $this->tmpCookie;

        // Restore config
        if (!empty($this->tmpMaxConf)) {
            $GLOBALS['_MAX']['CONF'] = $this->tmpMaxConf;
        }

        // Clean up created database records (reverse order)
        $this->cleanupCreatedRecords();
    }

    // -----------------------------------------------------------------------
    // Record cleanup
    // -----------------------------------------------------------------------

    protected function cleanupCreatedRecords()
    {
        // Clean in dependency order: banners -> campaigns -> advertisers -> agencies
        // zones -> affiliates
        $cleanupOrder = [
            'banners'     => 'banners',
            'zones'       => 'zones',
            'campaigns'   => 'campaigns',
            'advertisers' => 'clients',
            'affiliates'  => 'affiliates',
            'agencies'    => 'agency',
        ];

        foreach ($cleanupOrder as $key => $table) {
            foreach ($this->createdIds[$key] as $id) {
                $doTable = OA_Dal::factoryDO($table);
                if ($doTable) {
                    $primaryKey = $key === 'agencies' ? 'agencyid' :
                        ($key === 'advertisers' ? 'clientid' :
                        ($key === 'affiliates' ? 'affiliateid' :
                        ($key === 'campaigns' ? 'campaignid' :
                        ($key === 'banners' ? 'bannerid' : 'zoneid'))));
                    $doTable->$primaryKey = $id;
                    $doTable->delete();
                }
            }
        }
    }

    // -----------------------------------------------------------------------
    // Data insertion helpers
    // -----------------------------------------------------------------------

    /**
     * Insert an agency record and return its ID.
     *
     * @param string $name Agency name
     * @return int agencyid
     */
    protected function insertAgency($name = 'Test Agency')
    {
        $doAgency = OA_Dal::factoryDO('agency');
        $doAgency->name = $name;
        $doAgency->contact = 'Test';
        $doAgency->email = 'test@example.com';
        $doAgency->active = 1;
        $id = DataGenerator::generateOne($doAgency);
        $this->createdIds['agencies'][] = $id;
        return $id;
    }

    /**
     * Insert a publisher (affiliate) record and return its ID.
     *
     * @param int    $agencyId Parent agency ID
     * @param string $name     Publisher name
     * @return int affiliateid
     */
    protected function insertPublisher($agencyId, $name = 'Test Publisher')
    {
        $doAffiliate = OA_Dal::factoryDO('affiliates');
        $doAffiliate->agencyid = $agencyId;
        $doAffiliate->name = $name;
        $doAffiliate->mnemonic = 'TST';
        $doAffiliate->contact = 'Test';
        $doAffiliate->email = 'test@example.com';
        $doAffiliate->website = 'http://www.example.com';
        $doAffiliate->an_website_id = 0;
        $id = DataGenerator::generateOne($doAffiliate);
        $this->createdIds['affiliates'][] = $id;
        return $id;
    }

    /**
     * Insert an advertiser (client) record and return its ID.
     *
     * @param int    $agencyId Parent agency ID
     * @param string $name     Advertiser name
     * @return int clientid
     */
    protected function insertAdvertiser($agencyId, $name = 'Test Advertiser')
    {
        $doClient = OA_Dal::factoryDO('clients');
        $doClient->agencyid = $agencyId;
        $doClient->clientname = $name;
        $doClient->contact = 'Test';
        $doClient->email = 'test@example.com';
        $doClient->an_adnetwork_id = 0;
        $id = DataGenerator::generateOne($doClient);
        $this->createdIds['advertisers'][] = $id;
        return $id;
    }

    /**
     * Insert a campaign with a specific priority tier.
     *
     * @param int    $advertiserId Parent advertiser ID
     * @param int    $priority     Campaign priority (use class constants)
     * @param array  $extra        Extra fields to set on the campaign
     * @return int campaignid
     */
    protected function insertCampaign($advertiserId, $priority = self::PRIORITY_REMNANT, $extra = [])
    {
        $doCampaign = OA_Dal::factoryDO('campaigns');
        $doCampaign->clientid = $advertiserId;
        $doCampaign->campaignname = 'Test Campaign p=' . $priority;
        $doCampaign->status = 0; // OA_ENTITY_STATUS_RUNNING
        $doCampaign->priority = $priority;
        $doCampaign->weight = isset($extra['weight']) ? $extra['weight'] : 1;

        // Set revenue info for eCPM campaigns
        if ($priority == self::PRIORITY_ECPM) {
            $doCampaign->revenue_type = isset($extra['revenue_type']) ? $extra['revenue_type'] : 1; // CPM
            $doCampaign->revenue = isset($extra['revenue']) ? $extra['revenue'] : 1.00;
            $doCampaign->ecpm = isset($extra['ecpm']) ? $extra['ecpm'] : 1.00;
            $doCampaign->ecpm_enabled = 1;
        }

        // Apply extra fields
        foreach ($extra as $field => $value) {
            $doCampaign->$field = $value;
        }

        $id = DataGenerator::generateOne($doCampaign);
        $this->createdIds['campaigns'][] = $id;
        return $id;
    }

    /**
     * Insert a banner with a specific storage type.
     *
     * @param int    $campaignId   Parent campaign ID
     * @param string $storageType  Banner storage type (use class constants)
     * @param array  $extra        Extra fields to set on the banner
     * @return int bannerid
     */
    protected function insertBanner($campaignId, $storageType = self::STORAGETYPE_HTML, $extra = [])
    {
        $doBanner = OA_Dal::factoryDO('banners');
        $doBanner->campaignid = $campaignId;
        $doBanner->status = 0; // OA_ENTITY_STATUS_RUNNING
        $doBanner->storagetype = $storageType;
        $doBanner->width = isset($extra['width']) ? $extra['width'] : 468;
        $doBanner->height = isset($extra['height']) ? $extra['height'] : 60;
        $doBanner->weight = isset($extra['weight']) ? $extra['weight'] : 1;
        $doBanner->description = 'Test Banner type=' . $storageType;
        $doBanner->acl_plugins = '';
        $doBanner->acls_updated = date('Y-m-d H:i:s');
        $doBanner->compiledlimitation = '';
        $doBanner->ext_bannertype = '';

        // Set type-specific fields
        switch ($storageType) {
            case self::STORAGETYPE_HTML:
                $doBanner->htmltemplate = isset($extra['htmltemplate'])
                    ? $extra['htmltemplate']
                    : '<div>Test HTML Banner</div>';
                $doBanner->contenttype = 'html';
                break;

            case self::STORAGETYPE_TXT:
                $doBanner->bannertext = isset($extra['bannertext'])
                    ? $extra['bannertext']
                    : 'Test Text Banner';
                $doBanner->contenttype = 'txt';
                break;

            case self::STORAGETYPE_URL:
                $doBanner->imageurl = isset($extra['imageurl'])
                    ? $extra['imageurl']
                    : 'http://example.com/banner.gif';
                $doBanner->contenttype = 'gif';
                break;

            case self::STORAGETYPE_WEB:
                $doBanner->filename = isset($extra['filename'])
                    ? $extra['filename']
                    : 'test_banner.gif';
                $doBanner->contenttype = 'gif';
                break;

            case self::STORAGETYPE_SQL:
                $doBanner->filename = isset($extra['filename'])
                    ? $extra['filename']
                    : 'test_banner.gif';
                $doBanner->contenttype = 'gif';
                break;
        }

        $doBanner->url = isset($extra['url']) ? $extra['url'] : 'http://www.example.com/landing';

        // Apply extra fields
        foreach ($extra as $field => $value) {
            $doBanner->$field = $value;
        }

        $id = DataGenerator::generateOne($doBanner);
        $this->createdIds['banners'][] = $id;
        return $id;
    }

    /**
     * Insert a zone and return its ID.
     *
     * @param int    $publisherId  Parent publisher (affiliate) ID
     * @param array  $extra        Extra fields to set on the zone
     * @return int zoneid
     */
    protected function insertZone($publisherId, $extra = [])
    {
        $doZone = OA_Dal::factoryDO('zones');
        $doZone->affiliateid = $publisherId;
        $doZone->zonename = 'Test Zone';
        $doZone->zonetype = isset($extra['zonetype']) ? $extra['zonetype'] : 3; // banner zone
        $doZone->width = isset($extra['width']) ? $extra['width'] : 468;
        $doZone->height = isset($extra['height']) ? $extra['height'] : 60;

        // Apply extra fields
        foreach ($extra as $field => $value) {
            $doZone->$field = $value;
        }

        $id = DataGenerator::generateOne($doZone);
        $this->createdIds['zones'][] = $id;
        return $id;
    }

    /**
     * Link a banner to a zone via ad_zone_assoc.
     *
     * @param int $zoneId   Zone ID
     * @param int $bannerId Banner ID
     * @return int association ID
     */
    protected function linkBannerToZone($zoneId, $bannerId)
    {
        $doAdZone = OA_Dal::factoryDO('ad_zone_assoc');
        $doAdZone->zone_id = $zoneId;
        $doAdZone->ad_id = $bannerId;
        return DataGenerator::generateOne($doAdZone);
    }

    // -----------------------------------------------------------------------
    // Convenience helpers for creating full ad stacks
    // -----------------------------------------------------------------------

    /**
     * Create a complete ad stack: agency -> publisher -> advertiser -> campaign -> banner -> zone -> link.
     *
     * @param int    $priority     Campaign priority
     * @param string $storageType  Banner storage type
     * @param array  $campaignExtra Extra campaign fields
     * @param array  $bannerExtra   Extra banner fields
     * @param array  $zoneExtra     Extra zone fields
     * @return array ['agencyId', 'publisherId', 'advertiserId', 'campaignId', 'bannerId', 'zoneId']
     */
    protected function createFullAdStack(
        $priority = self::PRIORITY_REMNANT,
        $storageType = self::STORAGETYPE_HTML,
        $campaignExtra = [],
        $bannerExtra = [],
        $zoneExtra = []
    ) {
        $agencyId = $this->insertAgency();
        $publisherId = $this->insertPublisher($agencyId);
        $advertiserId = $this->insertAdvertiser($agencyId);
        $campaignId = $this->insertCampaign($advertiserId, $priority, $campaignExtra);
        $bannerId = $this->insertBanner($campaignId, $storageType, $bannerExtra);
        $zoneId = $this->insertZone($publisherId, $zoneExtra);
        $this->linkBannerToZone($zoneId, $bannerId);

        return [
            'agencyId'     => $agencyId,
            'publisherId'  => $publisherId,
            'advertiserId' => $advertiserId,
            'campaignId'   => $campaignId,
            'bannerId'     => $bannerId,
            'zoneId'       => $zoneId,
        ];
    }

    // -----------------------------------------------------------------------
    // Cache / zone-linked-ads helpers
    // -----------------------------------------------------------------------

    /**
     * Build a zone-linked-ad-infos array matching the structure expected by
     * _adSelectCommon(). This mirrors the format returned by
     * MAX_cacheGetZoneLinkedAdInfos().
     *
     * @param array $ads     Array of ad records keyed by ad_id
     * @param int   $zoneId  Zone ID
     * @param array $zoneInfo  Extra zone info fields
     * @return array
     */
    protected function buildZoneLinkedAdInfos($ads, $zoneId, $zoneInfo = [])
    {
        $xAds = [];  // Override (priority = -1)
        $cAds = [];  // Contract (priority = 1-10), keyed by priority
        $lAds = [];  // Remnant (priority = 0)
        $eAds = [];  // eCPM (priority = -2)

        foreach ($ads as $adId => $ad) {
            $ad['ad_id'] = $adId;
            $ad['priority_factor'] = isset($ad['priority_factor']) ? $ad['priority_factor'] : 1;
            $ad['priority'] = isset($ad['priority']) ? $ad['priority'] : 0.5;

            $campaignPriority = isset($ad['campaign_priority']) ? $ad['campaign_priority'] : 0;

            if ($campaignPriority == self::PRIORITY_OVERRIDE) {
                $xAds[$adId] = $ad;
            } elseif ($campaignPriority >= self::PRIORITY_CONTRACT_MIN && $campaignPriority <= self::PRIORITY_CONTRACT_MAX) {
                if (!isset($cAds[$campaignPriority])) {
                    $cAds[$campaignPriority] = [];
                }
                $cAds[$campaignPriority][$adId] = $ad;
            } elseif ($campaignPriority == self::PRIORITY_ECPM) {
                $ad['ecpm'] = isset($ad['ecpm']) ? $ad['ecpm'] : 1.0;
                $eAds[$adId] = $ad;
            } else {
                $lAds[$adId] = $ad;
            }
        }

        $result = [
            'xAds' => $xAds,
            'ads'  => $cAds,
            'lAds' => $lAds,
            'eAds' => $eAds,
            'count_active' => count($ads),
            'zone_id' => $zoneId,
            'zone_companion' => false,
        ];

        return array_merge($result, $zoneInfo);
    }

    // -----------------------------------------------------------------------
    // Cookie / Capping helpers
    // -----------------------------------------------------------------------

    /**
     * Set an ad-level cap cookie.
     *
     * @param int $adId       Banner ID
     * @param int $viewCount  Number of impressions recorded
     */
    protected function setAdCapCookie($adId, $viewCount)
    {
        $conf = $GLOBALS['_MAX']['CONF'];
        $_COOKIE[$conf['var']['capAd']][$adId] = $viewCount;
    }

    /**
     * Set an ad-level session cap cookie.
     *
     * @param int $adId       Banner ID
     * @param int $viewCount  Number of impressions in this session
     */
    protected function setAdSessionCapCookie($adId, $viewCount)
    {
        $conf = $GLOBALS['_MAX']['CONF'];
        $_COOKIE[$conf['var']['sessionCapAd']][$adId] = $viewCount;
    }

    /**
     * Set an ad-level block cookie (time-based).
     *
     * @param int $adId      Banner ID
     * @param int $timestamp Block-until timestamp
     */
    protected function setAdBlockCookie($adId, $timestamp)
    {
        $conf = $GLOBALS['_MAX']['CONF'];
        $_COOKIE[$conf['var']['blockAd']][$adId] = $timestamp;
    }

    /**
     * Set a campaign-level cap cookie.
     *
     * @param int $campaignId Campaign ID
     * @param int $viewCount  Number of impressions recorded
     */
    protected function setCampaignCapCookie($campaignId, $viewCount)
    {
        $conf = $GLOBALS['_MAX']['CONF'];
        $_COOKIE[$conf['var']['capCampaign']][$campaignId] = $viewCount;
    }

    /**
     * Set a campaign-level session cap cookie.
     *
     * @param int $campaignId Campaign ID
     * @param int $viewCount  Number of impressions in this session
     */
    protected function setCampaignSessionCapCookie($campaignId, $viewCount)
    {
        $conf = $GLOBALS['_MAX']['CONF'];
        $_COOKIE[$conf['var']['sessionCapCampaign']][$campaignId] = $viewCount;
    }

    /**
     * Set a campaign-level block cookie (time-based).
     *
     * @param int $campaignId Campaign ID
     * @param int $timestamp  Block-until timestamp
     */
    protected function setCampaignBlockCookie($campaignId, $timestamp)
    {
        $conf = $GLOBALS['_MAX']['CONF'];
        $_COOKIE[$conf['var']['blockCampaign']][$campaignId] = $timestamp;
    }

    /**
     * Set a zone-level cap cookie.
     *
     * @param int $zoneId    Zone ID
     * @param int $viewCount Number of impressions recorded
     */
    protected function setZoneCapCookie($zoneId, $viewCount)
    {
        $conf = $GLOBALS['_MAX']['CONF'];
        $_COOKIE[$conf['var']['capZone']][$zoneId] = $viewCount;
    }

    /**
     * Set a zone-level session cap cookie.
     *
     * @param int $zoneId    Zone ID
     * @param int $viewCount Number of impressions in this session
     */
    protected function setZoneSessionCapCookie($zoneId, $viewCount)
    {
        $conf = $GLOBALS['_MAX']['CONF'];
        $_COOKIE[$conf['var']['sessionCapZone']][$zoneId] = $viewCount;
    }

    /**
     * Set a zone-level block cookie (time-based).
     *
     * @param int $zoneId    Zone ID
     * @param int $timestamp Block-until timestamp
     */
    protected function setZoneBlockCookie($zoneId, $timestamp)
    {
        $conf = $GLOBALS['_MAX']['CONF'];
        $_COOKIE[$conf['var']['blockZone']][$zoneId] = $timestamp;
    }

    /**
     * Mark the viewer as a new viewer (no viewer ID cookie).
     */
    protected function setNewViewer()
    {
        $GLOBALS['_MAX']['COOKIE']['newViewerId'] = true;
    }

    /**
     * Mark the viewer as a returning viewer (has a viewer ID cookie).
     */
    protected function setReturningViewer()
    {
        $GLOBALS['_MAX']['COOKIE']['newViewerId'] = false;
    }

    // -----------------------------------------------------------------------
    // ACL / Delivery Limitation helpers
    // -----------------------------------------------------------------------

    /**
     * Set compiled limitations on a banner.
     *
     * The compiled limitation is a PHP expression that returns true/false.
     *
     * @param int    $bannerId           Banner ID
     * @param string $compiledLimitation The compiled limitation expression
     * @param string $aclPlugins         Comma-separated list of plugin identifiers
     */
    protected function setBannerLimitation($bannerId, $compiledLimitation, $aclPlugins = '')
    {
        $doBanner = OA_Dal::factoryDO('banners');
        $doBanner->get($bannerId);
        $doBanner->compiledlimitation = $compiledLimitation;
        $doBanner->acl_plugins = $aclPlugins;
        $doBanner->acls_updated = date('Y-m-d H:i:s');
        $doBanner->update();
    }

    /**
     * Insert an ACL limitation record for a banner.
     *
     * @param int    $bannerId  Banner ID
     * @param int    $order     Execution order (starting from 0)
     * @param string $type      Limitation type (e.g., 'deliveryLimitations:Time:Day')
     * @param string $comparison Comparison operator (e.g., '==', '!=', '=~', '!~')
     * @param string $data      Limitation data (e.g., '1,2,3' for days)
     * @param string $logical   Logical operator to combine with previous rule ('and' or 'or')
     */
    protected function insertAclLimitation($bannerId, $order, $type, $comparison, $data, $logical = 'and')
    {
        $doAcl = OA_Dal::factoryDO('acls');
        $doAcl->bannerid = $bannerId;
        $doAcl->logical = $logical;
        $doAcl->type = $type;
        $doAcl->comparison = $comparison;
        $doAcl->data = $data;
        $doAcl->executionorder = $order;
        DataGenerator::generateOne($doAcl);
    }

    // -----------------------------------------------------------------------
    // Delivery simulation helpers
    // -----------------------------------------------------------------------

    /**
     * Simulate selecting an ad for a zone through the full delivery pipeline.
     *
     * @param int    $zoneId   Zone ID to request
     * @param string $source   Source parameter
     * @param array  $context  Context array for companion/exclusion
     * @param string $what     The "what" parameter (e.g., 'zone:123')
     * @return array|false     The selected ad array or false
     */
    protected function simulateAdSelect($zoneId, $source = '', $context = [], $what = '')
    {
        if (empty($what)) {
            $what = 'zone:' . $zoneId;
        }

        return MAX_adSelect($what, '', '', $source, 0, 'UTF-8', $context, true, '', '', '');
    }

    /**
     * Simulate rendering a banner.
     *
     * @param array  $aBanner  The banner ad-array (as returned by MAX_adSelect)
     * @param int    $zoneId   Zone ID
     * @param string $source   Source parameter
     * @return string The rendered HTML
     */
    protected function simulateAdRender($aBanner, $zoneId = 0, $source = '')
    {
        return MAX_adRender($aBanner, $zoneId, $source, '', '', false, 'UTF-8', true, true, true, '', null, $context);
    }

    // -----------------------------------------------------------------------
    // Assertion helpers
    // -----------------------------------------------------------------------

    /**
     * Assert that an ad was selected (result is a valid ad array).
     *
     * @param mixed  $result   The result from simulateAdSelect()
     * @param string $message  Assertion message
     */
    protected function assertAdSelected($result, $message = 'Expected an ad to be selected')
    {
        $this->assertTrue(is_array($result), $message);
        $this->assertTrue(!empty($result['ad_id']) || !empty($result['bannerid']), $message . ' (has ad_id or bannerid)');
    }

    /**
     * Assert that no ad was selected.
     *
     * @param mixed  $result   The result from simulateAdSelect()
     * @param string $message  Assertion message
     */
    protected function assertNoAdSelected($result, $message = 'Expected no ad to be selected')
    {
        $this->assertTrue($result === false || (is_array($result) && empty($result['ad_id']) && empty($result['bannerid'])), $message);
    }

    /**
     * Assert that a specific banner was selected.
     *
     * @param mixed  $result    The result from simulateAdSelect()
     * @param int    $bannerId  Expected banner ID
     * @param string $message   Assertion message
     */
    protected function assertBannerSelected($result, $bannerId, $message = '')
    {
        if (empty($message)) {
            $message = "Expected banner {$bannerId} to be selected";
        }
        $this->assertTrue(is_array($result), $message);
        $actualId = isset($result['bannerid']) ? $result['bannerid'] : (isset($result['ad_id']) ? $result['ad_id'] : null);
        $this->assertEqual($actualId, $bannerId, $message);
    }

    /**
     * Assert that the rendered output contains a specific string.
     *
     * @param string $rendered  Rendered HTML output
     * @param string $needle    String to search for
     * @param string $message   Assertion message
     */
    protected function assertRenderedContains($rendered, $needle, $message = '')
    {
        if (empty($message)) {
            $message = "Expected rendered output to contain '{$needle}'";
        }
        $this->assertTrue(strpos($rendered, $needle) !== false, $message);
    }
}
