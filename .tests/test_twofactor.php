<?php
/**
 * Test Suite for booskit/twofactor phpBB Extension
 */

namespace phpbb\config {
    class config extends \ArrayObject {
        public function __construct(array $array = []) { parent::__construct($array, \ArrayObject::ARRAY_AS_PROPS); }
        #[\ReturnTypeWillChange]
        public function offsetGet($key) { return isset($this[$key]) ? parent::offsetGet($key) : null; }
    }
}

namespace phpbb\request {
    interface request_interface {
        const POST = 0;
        const GET = 1;
        const COOKIE = 2;
    }
    class request implements request_interface {
        public $cookies = [];
        public $vars = [];

        public function is_set_post($name) { return false; }
        public function is_set($name, $super_global = \phpbb\request\request_interface::POST) {
            if ($super_global === \phpbb\request\request_interface::COOKIE) {
                return isset($this->cookies[$name]);
            }
            return isset($this->vars[$name]);
        }
        public function variable($name, $default, $multibyte = false, $super_global = false) {
            if ($super_global === \phpbb\request\request_interface::COOKIE) {
                return isset($this->cookies[$name]) ? $this->cookies[$name] : $default;
            }
            return isset($this->vars[$name]) ? $this->vars[$name] : $default;
        }
        public function server($name, $default = '') {
            return isset($this->vars[$name]) ? $this->vars[$name] : $default;
        }
    }
}

namespace phpbb {
    class user {
        public $data = ['user_id' => 2, 'group_id' => 2, 'session_id' => 'sess123', 'username' => 'TestUser'];
        public $ip = '127.0.0.1';
        public $cookies = [];

        public function set_cookie($name, $cookiedata, $cookietime) {
            $this->cookies[$name] = [
                'data' => $cookiedata,
                'expires' => $cookietime,
            ];
            $_COOKIE['phpbb3_' . $name] = $cookiedata;
        }
    }
}

namespace phpbb\template {
    class template {
        public function assign_vars(array $vars) {}
    }
}

namespace phpbb\controller {
    class helper {
        public function route($route, array $params = []) { return $route; }
        public function render($template, $title = '') { return null; }
    }
}

namespace phpbb\log {
    class log {
        public function add() {}
    }
}

namespace phpbb\db\driver {
    interface driver_interface {
        public function sql_query($sql);
        public function sql_fetchrow($result);
        public function sql_freeresult($result);
        public function sql_escape($str);
        public function sql_build_array($mode, $array);
        public function sql_query_limit($sql, $total, $offset = 0);
        public function sql_fetchfield($field, $rownum = false, $query_id = false);
    }
}

namespace booskit\twofactor\service {
    class totp {}
    class backup_code_manager {}
}

namespace {

if (!defined('IN_PHPBB')) {
    define('IN_PHPBB', true);
}
if (!defined('ANONYMOUS')) {
    define('ANONYMOUS', 1);
}

if (!function_exists('generate_board_url')) {
    function generate_board_url() {
        return 'http://example.com/phpbb';
    }
}

if (!function_exists('append_sid')) {
    function append_sid($url, $params = false, $is_amp = false, $session_id = false) {
        if ($params) {
            $url .= (strpos($url, '?') !== false ? '&' : '?') . (is_array($params) ? http_build_query($params) : $params);
        }
        return $url;
    }
}

class mock_db implements \phpbb\db\driver\driver_interface {
    public $records = [];
    public $user_groups = [];
    public $pending_logins = [];
    public $trusted_devices = [];

    public function sql_query($sql) {
        if (strpos($sql, 'DELETE FROM phpbb_booskit_2fa_pending_logins') !== false) {
            if (preg_match("/login_token = '([a-f0-9]+)'/", $sql, $m)) {
                unset($this->pending_logins[$m[1]]);
            } elseif (preg_match('/user_id = (\d+)/', $sql, $m)) {
                $uid = (int)$m[1];
                foreach ($this->pending_logins as $tok => $row) {
                    if ($row['user_id'] == $uid) {
                        unset($this->pending_logins[$tok]);
                    }
                }
            } elseif (preg_match('/expires_at < (\d+)/', $sql, $m)) {
                $time = (int)$m[1];
                foreach ($this->pending_logins as $tok => $row) {
                    if ($row['expires_at'] < $time) {
                        unset($this->pending_logins[$tok]);
                    }
                }
            }
        }

        if (strpos($sql, 'DELETE FROM phpbb_booskit_2fa_trusted_devices') !== false) {
            if (preg_match('/user_id = (\d+)/', $sql, $m)) {
                $uid = (int)$m[1];
                foreach ($this->trusted_devices as $dev_id => $row) {
                    if ($row['user_id'] == $uid) {
                        unset($this->trusted_devices[$dev_id]);
                    }
                }
            }
        }

        if (strpos($sql, 'booskit_2fa_users') !== false && strpos($sql, 'DELETE FROM') !== false) {
            if (preg_match('/user_id = (\d+)/', $sql, $m)) {
                $uid = (int)$m[1];
                unset($this->records[$uid]);
            }
        }
        return $sql;
    }

    public function sql_fetchrow($result) {
        if (strpos($result, 'booskit_2fa_users') !== false) {
            preg_match('/user_id = (\d+)/', $result, $m);
            $uid = isset($m[1]) ? (int)$m[1] : 0;
            return isset($this->records[$uid]) ? $this->records[$uid] : false;
        }
        if (strpos($result, 'booskit_2fa_trusted_devices') !== false) {
            if (preg_match("/device_token_hash = '([a-f0-9]+)'/", $result, $m)) {
                $hash = $m[1];
                foreach ($this->trusted_devices as $row) {
                    if ($row['device_token_hash'] === $hash && $row['expires_at'] > time()) {
                        return $row;
                    }
                }
            }
            return false;
        }
        if (strpos($result, 'booskit_2fa_pending_logins') !== false) {
            if (preg_match("/login_token = '([a-f0-9]+)'/", $result, $m)) {
                $token = $m[1];
                if (isset($this->pending_logins[$token])) {
                    $row = $this->pending_logins[$token];
                    if ($row['expires_at'] > time()) {
                        return $row;
                    }
                }
            }
            return false;
        }
        return false;
    }

    public function sql_freeresult($result) {}
    public function sql_escape($str) { return addslashes($str); }
    public function sql_build_array($mode, $array) {
        if ($mode === 'INSERT') {
            if (isset($array['login_token'])) {
                $this->pending_logins[$array['login_token']] = $array;
            }
            if (isset($array['device_token_hash'])) {
                $dev_id = count($this->trusted_devices) + 1;
                $array['device_id'] = $dev_id;
                $this->trusted_devices[$dev_id] = $array;
            }
            if (isset($array['user_id']) && isset($array['is_enabled'])) {
                $this->records[$array['user_id']] = $array;
            }
        }
        return '';
    }
    public function sql_query_limit($sql, $total, $offset = 0) {
        return $this->sql_query($sql);
    }
    public function sql_fetchfield($field, $rownum = false, $query_id = false) {
        return false;
    }
}

require_once __DIR__ . '/../booskit/twofactor/service/twofactor_manager.php';
require_once __DIR__ . '/../booskit/twofactor/controller/verify.php';

use booskit\twofactor\service\twofactor_manager;
use booskit\twofactor\service\totp;
use booskit\twofactor\service\backup_code_manager;
use booskit\twofactor\controller\verify;

echo "=================================================\n";
echo " Running Unit Test Suite for booskit/twofactor   \n";
echo "=================================================\n\n";

$passed = 0;
$failed = 0;

function assert_test($condition, $description) {
    global $passed, $failed;
    if ($condition) {
        echo " [PASS] $description\n";
        $passed++;
    } else {
        echo " [FAIL] $description\n";
        $failed++;
    }
}

$config = new \phpbb\config\config([
    'cookie_name' => 'phpbb3',
    'booskit_2fa_enabled' => 1,
    'booskit_2fa_groups_oauth' => '',
    'booskit_2fa_groups_enforce' => '5',
    'booskit_2fa_groups_ucp' => '4,5',
    'booskit_2fa_groups_mcp' => '4,5',
    'booskit_2fa_groups_acp' => '5',
]);

$db = new mock_db();
$user = new \phpbb\user();
$request = new \phpbb\request\request();
$log = new \phpbb\log\log();
$totp = new totp();
$backup_codes = new backup_code_manager();
$template = new \phpbb\template\template();
$helper = new \phpbb\controller\helper();

$manager = new twofactor_manager($config, $db, $user, $request, $log, $totp, $backup_codes, 'phpbb_');

// User 2: 2FA enabled, belongs to group 2 (Registered Users)
$db->records[2] = [
    'user_id' => 2,
    'secret' => 'JBSWY3DPEHPK3PXP',
    'is_enabled' => 1,
];

// User 3: 2FA not enabled
$db->records[3] = [
    'user_id' => 3,
    'is_enabled' => 0,
];

// User 5: 2FA enabled, belongs to group 5 (Administrators)
$db->records[5] = [
    'user_id' => 5,
    'secret' => 'JBSWY3DPEHPK3PXP',
    'is_enabled' => 1,
];

// 1. When OAuth groups setting is empty, OAuth login skips 2FA
$is_req_oauth_empty = $manager->is_2fa_required_for_login(2, true);
assert_test($is_req_oauth_empty === false, 'OAuth login skips 2FA when no OAuth groups are configured in ACP');

// 2. Standard login requires 2FA even if OAuth groups setting is empty
$is_req_standard = $manager->is_2fa_required_for_login(2, false);
assert_test($is_req_standard === true, 'Standard login requires 2FA for users with 2FA enabled');

// 3. User without 2FA enabled does not require 2FA on OAuth or standard login
assert_test($manager->is_2fa_required_for_login(3, true) === false, 'User without 2FA does not require 2FA on OAuth');
assert_test($manager->is_2fa_required_for_login(3, false) === false, 'User without 2FA does not require 2FA on standard login');

// 4. When OAuth groups setting has group 5:
$config['booskit_2fa_groups_oauth'] = '5';

// User 2 (group 2) logging in via OAuth should NOT require 2FA
assert_test($manager->is_2fa_required_for_login(2, true) === false, 'User not in OAuth groups list skips 2FA when logging in via OAuth');

// User 5 (group 5) logging in via OAuth SHOULD require 2FA
$user->data['user_id'] = 5;
$user->data['group_id'] = 5;
$manager5 = new twofactor_manager($config, $db, $user, $request, $log, $totp, $backup_codes, 'phpbb_');
assert_test($manager5->is_2fa_required_for_login(5, true) === true, 'User in OAuth groups list gets 2FA prompt when logging in via OAuth');

// 5. Pending login token generation for pre-session 2FA
$token = $manager5->create_pending_login(5, true, 1, 'viewtopic.php?f=2&t=1', true, false);
assert_test(!empty($token) && strlen($token) === 64, 'Creates 64-character pre-session pending login token');

// 6. Retrieve pending login data with matching IP
$pending_data = $manager5->get_pending_login($token, '127.0.0.1');
assert_test(
    $pending_data !== null &&
    $pending_data['user_id'] === 5 &&
    $pending_data['autologin'] === 1 &&
    $pending_data['auth_via_oauth'] === 1 &&
    $pending_data['redirect_url'] === 'viewtopic.php?f=2&t=1',
    'Retrieves stored pending login metadata correctly when IP matches creator IP'
);

// 7. Reject pending login token when client IP does NOT match creator IP (Flaw 6)
$pending_wrong_ip = $manager5->get_pending_login($token, '192.168.1.100');
assert_test($pending_wrong_ip === null, '[Security Fix - Flaw 6] Rejects pending login token on client IP mismatch');

// 8. Delete pending login token upon verification
$manager5->delete_pending_login($token);
assert_test($manager5->get_pending_login($token) === null, 'Deletes and invalidates pending login token after verification');

// 9. Expired pending login token is rejected
$token_exp = $manager5->create_pending_login(5, false, 1, 'index.php', false, false);
$db->pending_logins[$token_exp]['expires_at'] = time() - 10; // set expired
assert_test($manager5->get_pending_login($token_exp) === null, 'Rejects expired pending login tokens');

// 10. Remember Device Secure Cookie Token (Flaw 1)
assert_test($manager->is_device_remembered(2) === false, '[Security Fix - Flaw 1] Device is NOT remembered without token cookie');

$manager->remember_device(2);
assert_test(isset($user->cookies['2fa_remember']), 'Remember device sets client cookie');
$raw_cookie_token = $user->cookies['2fa_remember']['data'];
assert_test(strlen($raw_cookie_token) === 64, 'Remember device cookie token is a 64-character cryptographically random string');

// Inject cookie into mock request
$request->cookies['phpbb3_2fa_remember'] = $raw_cookie_token;
assert_test($manager->is_device_remembered(2) === true, 'Device IS recognized as remembered when presenting valid token cookie');

// Wrong cookie token fails
$request->cookies['phpbb3_2fa_remember'] = str_repeat('a', 64);
assert_test($manager->is_device_remembered(2) === false, 'Invalid cookie token does not authenticate as remembered device');

// Revoke trusted devices clears DB and expires cookie
$manager->revoke_trusted_devices(2);
assert_test($user->cookies['2fa_remember']['data'] === '', 'Revoking trusted devices deletes client cookie');
$request->cookies['phpbb3_2fa_remember'] = $raw_cookie_token;
assert_test($manager->is_device_remembered(2) === false, 'Revoking trusted devices invalidates remembered status in database');

// 11. Open Redirect Prevention (Flaw 2)
$verify_ctrl = new verify($config, $request, $template, $user, $helper, $manager, $totp, $backup_codes, $db, 'phpbb_');

// Test relative safe redirect
$safe_rel = $verify_ctrl->get_safe_redirect_url('viewtopic.php?f=2&t=5');
assert_test($safe_rel === 'http://example.com/phpbb/viewtopic.php?f=2&t=5', 'Permits valid internal relative redirect paths');

// Test same-domain absolute safe redirect
$safe_abs = $verify_ctrl->get_safe_redirect_url('http://example.com/phpbb/viewforum.php?f=2');
assert_test($safe_abs === 'http://example.com/phpbb/viewforum.php?f=2', 'Permits valid internal absolute redirects on the same domain');

// Test open redirect attempt to external http/https domain
$malicious_redirect = $verify_ctrl->get_safe_redirect_url('https://evil.com/phishing');
assert_test($malicious_redirect === 'http://example.com/phpbb/index.php', '[Security Fix - Flaw 2] Blocks open redirect attempt to external domain (https://evil.com)');

// Test protocol-relative open redirect attempt (//evil.com)
$protocol_relative = $verify_ctrl->get_safe_redirect_url('//evil.com/phishing');
assert_test($protocol_relative === 'http://example.com/phpbb/index.php', '[Security Fix - Flaw 2] Blocks protocol-relative open redirect (//evil.com)');

// Test backslash protocol-relative redirect attempt (\\evil.com)
$backslash_relative = $verify_ctrl->get_safe_redirect_url('\\\\evil.com/phishing');
assert_test($backslash_relative === 'http://example.com/phpbb/index.php', '[Security Fix - Flaw 2] Blocks backslash open redirect (\\\\evil.com)');

// 12. Accidental 2FA Activation Prevention
// User 99 has no record in 2fa_users (never activated 2FA)
assert_test($manager->is_user_2fa_enabled(99) === false, 'User 99 does not have 2FA enabled');
$manager->mark_session_verified('sess_oauth_99', 99, 'trusted_device', 'login');
assert_test($manager->is_user_2fa_enabled(99) === false, '[Fix] mark_session_verified on non-2FA user does NOT activate 2FA or create blank secret record');
assert_test(!isset($db->records[99]), '[Fix] No row created in 2fa_users for non-2FA user when verifying session');

// 13. Self-Healing of Corrupt Records (Account with is_enabled = 1 but empty secret)
$db->records[100] = [
    'user_id'              => 100,
    'secret'               => '',
    'is_enabled'           => 1,
    'enabled_at'           => time(),
    'last_login_at'        => time(),
    'last_login_ip'        => '127.0.0.1',
    'last_login_method'    => 'trusted_device',
    'reset_backup_pending' => 0,
];
assert_test($manager->is_user_2fa_enabled(100) === false, '[Fix] User with empty secret is NOT considered 2FA-enabled');
assert_test($manager->get_user_record(100) === null, '[Fix] Corrupted user record with empty secret is self-healed and cleaned up from database');
assert_test(!isset($db->records[100]), '[Fix] Corrupt row removed from users table');

echo "\n-------------------------------------------------\n";
echo " Test Results: $passed Passed, $failed Failed.\n";
echo "-------------------------------------------------\n";

exit($failed === 0 ? 0 : 1);
}
