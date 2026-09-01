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
    interface request_interface {}
    class request implements request_interface {
        public function is_set_post($name) { return false; }
        public function variable($name, $default, $multibyte = false, $cookie = false) { return $default; }
    }
}

namespace phpbb {
    class user {
        public $data = ['user_id' => 2, 'group_id' => 2, 'session_id' => 'sess123'];
        public $ip = '127.0.0.1';
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

class mock_db implements \phpbb\db\driver\driver_interface {
    public $records = [];
    public $user_groups = [];
    public $pending_logins = [];

    public function sql_query($sql) {
        if (strpos($sql, 'INSERT INTO phpbb_booskit_2fa_pending_logins') !== false) {
            // Parse insert if needed or handled in sql_build_array
        }
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
        return $sql;
    }

    public function sql_fetchrow($result) {
        if (strpos($result, 'booskit_2fa_users') !== false) {
            preg_match('/user_id = (\d+)/', $result, $m);
            $uid = isset($m[1]) ? (int)$m[1] : 0;
            return isset($this->records[$uid]) ? $this->records[$uid] : false;
        }
        if (strpos($result, 'booskit_2fa_trusted_devices') !== false) {
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
        if ($mode === 'INSERT' && isset($array['login_token'])) {
            $this->pending_logins[$array['login_token']] = $array;
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

use booskit\twofactor\service\twofactor_manager;
use booskit\twofactor\service\totp;
use booskit\twofactor\service\backup_code_manager;

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

$manager = new twofactor_manager($config, $db, $user, $request, $log, $totp, $backup_codes, 'phpbb_');

// User 2: 2FA enabled, belongs to group 2 (Registered Users)
$db->records[2] = [
    'user_id' => 2,
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

// 6. Retrieve pending login data
$pending_data = $manager5->get_pending_login($token);
assert_test(
    $pending_data !== null &&
    $pending_data['user_id'] === 5 &&
    $pending_data['autologin'] === 1 &&
    $pending_data['auth_via_oauth'] === 1 &&
    $pending_data['redirect_url'] === 'viewtopic.php?f=2&t=1',
    'Retrieves stored pending login metadata correctly before session creation'
);

// 7. Delete pending login token upon verification
$manager5->delete_pending_login($token);
assert_test($manager5->get_pending_login($token) === null, 'Deletes and invalidates pending login token after verification');

// 8. Expired pending login token is rejected
$token_exp = $manager5->create_pending_login(5, false, 1, 'index.php', false, false);
$db->pending_logins[$token_exp]['expires_at'] = time() - 10; // set expired
assert_test($manager5->get_pending_login($token_exp) === null, 'Rejects expired pending login tokens');

echo "\n-------------------------------------------------\n";
echo " Test Results: $passed Passed, $failed Failed.\n";
echo "-------------------------------------------------\n";

exit($failed === 0 ? 0 : 1);
}
