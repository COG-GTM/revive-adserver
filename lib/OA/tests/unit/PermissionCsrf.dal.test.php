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

require_once MAX_PATH . '/lib/OA/Dal.php';
require_once MAX_PATH . '/lib/OA/Permission.php';
require_once MAX_PATH . '/lib/OA/Dal/DataGenerator.php';
require_once MAX_PATH . '/www/admin/lib-sessions.inc.php';

/**
 * Tests for the central CSRF and access helpers in OA_Permission
 *
 * @package    OpenXPermission
 * @subpackage TestSuite
 */
class Test_OA_PermissionCsrf extends UnitTestCase
{
    private $aServer;
    private $aGet;
    private $aPost;
    private $aCookie;
    private $aSession;

    public function setUp()
    {
        global $session;

        $this->aServer = $_SERVER;
        $this->aGet = $_GET;
        $this->aPost = $_POST;
        $this->aCookie = $_COOKIE;
        $this->aSession = $session ?? null;

        $_GET = [];
        $_POST = [];
        $_COOKIE['sessionID'] = 'csrf-test-' . bin2hex(random_bytes(8));
        $session = [];
    }

    public function tearDown()
    {
        global $session;

        $_SERVER = $this->aServer;
        $_GET = $this->aGet;
        $_POST = $this->aPost;
        $_COOKIE = $this->aCookie;
        $session = $this->aSession;

        DataGenerator::cleanUp();
    }

    public function testIsStateChangingRequest()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertFalse(OA_Permission::isStateChangingRequest());
        $this->assertTrue(OA_Permission::isStateChangingRequest(true));

        $_GET['clientid'] = '1';
        $this->assertFalse(OA_Permission::isStateChangingRequest());

        foreach (OA_Permission::CSRF_STATE_CHANGE_PARAMS as $param) {
            $_GET = [$param => '1'];
            $this->assertTrue(OA_Permission::isStateChangingRequest(), "GET with {$param} should be state-changing");
        }

        $_GET = ['link' => '1'];
        $this->assertFalse(OA_Permission::isStateChangingRequest(['submit']));

        $_GET = [];
        $_SERVER['REQUEST_METHOD'] = 'HEAD';
        $this->assertFalse(OA_Permission::isStateChangingRequest());

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->assertTrue(OA_Permission::isStateChangingRequest());
        $this->assertTrue(OA_Permission::isStateChangingRequest([]));
    }

    public function testGetWithoutActionPasses()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['clientid' => '1'];

        $this->assertTrue(OA_Permission::isCsrfSafeRequest());
    }

    public function testPostWithoutTokenIsRejected()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['submit' => '1'];

        $this->assertFalse(OA_Permission::isCsrfSafeRequest());

        $_POST['token'] = '';
        $this->assertFalse(OA_Permission::isCsrfSafeRequest());

        $_POST['token'] = ['not-a-string'];
        $this->assertFalse(OA_Permission::isCsrfSafeRequest());
    }

    public function testPostWithInvalidTokenIsRejected()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        phpAds_SessionGetToken();
        $_POST = ['submit' => '1', 'token' => 'invalid-' . bin2hex(random_bytes(8))];

        $this->assertFalse(OA_Permission::isCsrfSafeRequest());
    }

    public function testTokenOnWrongMethodIsRejected()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = ['token' => phpAds_SessionGetToken()];
        $_POST = ['submit' => '1'];

        $this->assertFalse(OA_Permission::isCsrfSafeRequest());
    }

    public function testPostWithValidTokenPasses()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $token = phpAds_SessionGetToken();
        $this->assertTrue(is_string($token) && '' !== $token);
        $_POST = ['submit' => '1', 'token' => $token];

        $this->assertTrue(OA_Permission::isCsrfSafeRequest());

        // A second check on the same request (e.g. a per-page checkSessionToken()) still passes
        $this->assertTrue(OA_Permission::isCsrfSafeRequest());
        $this->assertTrue(phpAds_SessionValidateToken($token));

        // The token is single use: a fresh one is issued for the next form
        $this->assertNotEqual(phpAds_SessionGetToken(), $token);
    }

    public function testGetWithActionRequiresToken()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['action' => 'remove', 'zoneid' => '1'];

        $this->assertFalse(OA_Permission::isCsrfSafeRequest());

        $_GET['token'] = phpAds_SessionGetToken();
        $this->assertTrue(OA_Permission::isCsrfSafeRequest());
    }

    public function testDeleteScriptRuleRequiresTokenOnPlainGet()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['clientid' => '1'];

        $rule = OA_Permission::getCsrfRuleForScript(MAX_PATH . '/www/admin/advertiser-delete.php');
        $this->assertIdentical($rule, true);
        $this->assertFalse(OA_Permission::isCsrfSafeRequest($rule));

        $_GET['token'] = phpAds_SessionGetToken();
        $this->assertTrue(OA_Permission::isCsrfSafeRequest($rule));
    }

    public function testEnforceCsrfForStateChangeAllowsValidRequest()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['token' => phpAds_SessionGetToken()];

        OA_Permission::enforceCsrfForStateChange();
        OA_Permission::checkSessionToken();
        OA_Permission::enforceCsrfForProtectedScript(MAX_PATH . '/www/admin/zone-delete.php');

        // Reaching this point means none of the checks redirected
        $this->assertTrue(true);
    }

    public function testGetCsrfRuleForScript()
    {
        $adminDir = MAX_PATH . '/www/admin';

        foreach (OA_Permission::CSRF_PROTECTED_SCRIPTS as $script => $rule) {
            $this->assertTrue(file_exists("{$adminDir}/{$script}"), "{$script} does not exist");
            $this->assertIdentical(OA_Permission::getCsrfRuleForScript("{$adminDir}/{$script}"), $rule);
        }

        $this->assertIdentical(OA_Permission::getCsrfRuleForScript("{$adminDir}/agency-user.php"), ['submit']);
        $this->assertNull(OA_Permission::getCsrfRuleForScript("{$adminDir}/index.php"));
        $this->assertNull(OA_Permission::getCsrfRuleForScript("{$adminDir}/does-not-exist.php"));
        $this->assertNull(OA_Permission::getCsrfRuleForScript(MAX_PATH . '/www/delivery/ajs.php'));
        $this->assertNull(OA_Permission::getCsrfRuleForScript(''));
    }

    public function testHasAccountAndAccess()
    {
        global $session;

        $doAgency = OA_Dal::factoryDO('agency');
        $agencyId = DataGenerator::generateOne($doAgency);
        $agencyAccountId = OA_Dal::staticGetDO('agency', $agencyId)->account_id;

        $doClients = OA_Dal::factoryDO('clients');
        $doClients->agencyid = $agencyId;
        $doClients->reportlastdate = '2007-04-02 12:00:00';
        $clientId = DataGenerator::generateOne($doClients);

        $doOtherAgency = OA_Dal::factoryDO('agency');
        $otherAgencyId = DataGenerator::generateOne($doOtherAgency);
        $doOtherClients = OA_Dal::factoryDO('clients');
        $doOtherClients->agencyid = $otherAgencyId;
        $doOtherClients->reportlastdate = '2007-04-02 12:00:00';
        $otherClientId = DataGenerator::generateOne($doOtherClients);

        $oUser = new OA_Permission_User(OA_Dal::factoryDO('users'), true);
        $oUser->aAccount['account_id'] = $agencyAccountId;
        $oUser->aAccount['account_type'] = OA_ACCOUNT_MANAGER;
        $oUser->aAccount['entity_id'] = $agencyId;
        $session['user'] = $oUser;

        $this->assertTrue(OA_Permission::hasAccountAndAccess([OA_ACCOUNT_MANAGER]));
        $this->assertTrue(OA_Permission::hasAccountAndAccess([OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER], ['clients' => $clientId]));
        $this->assertTrue(OA_Permission::hasAccountAndAccess([OA_ACCOUNT_MANAGER], ['clients' => $clientId, 'agency' => $agencyId]));
        $this->assertFalse(OA_Permission::hasAccountAndAccess([OA_ACCOUNT_ADMIN], ['clients' => $clientId]));
        $this->assertFalse(OA_Permission::hasAccountAndAccess([OA_ACCOUNT_MANAGER], ['clients' => $otherClientId]));
        $this->assertFalse(OA_Permission::hasAccountAndAccess([OA_ACCOUNT_MANAGER], ['clients' => $clientId, 'agency' => $otherAgencyId]));

        // Should not redirect when access is granted
        OA_Permission::enforceAccountAndAccess([OA_ACCOUNT_MANAGER], ['clients' => $clientId, 'agency' => $agencyId]);
        $this->assertTrue(true);
    }
}
