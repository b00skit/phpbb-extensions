<?php
/**
 * Test Suite for booskit/commendations phpBB Extension
 */

namespace phpbb\extension {
    class base {}
}

namespace phpbb\db\migration {
    class migration {}
}

namespace Symfony\Component\EventDispatcher {
    interface EventSubscriberInterface {
        public static function getSubscribedEvents();
    }
}

namespace phpbb\cache\driver {
    interface driver_interface {}
    class dummy implements driver_interface {
        public function get($key) { return false; }
        public function put($key, $var, $ttl = 0) {}
        public function purge() {}
    }
}

namespace phpbb\config {
    class config extends \ArrayObject {
        public function __construct(array $array = []) { parent::__construct($array, \ArrayObject::ARRAY_AS_PROPS); }
        #[\ReturnTypeWillChange]
        public function offsetGet($key) { return isset($this[$key]) ? parent::offsetGet($key) : null; }
    }
}

namespace phpbb\auth {
    class auth {
        public $permissions = [];
        public function acl_get($opt, $forum_id = 0) { return !empty($this->permissions[$opt]); }
    }
}

namespace phpbb {
    class user {
        public $data = ['user_id' => 2];
        public function add_lang_ext($ext, $file) {}
        public function format_date($t) { return date('Y-m-d', $t); }
    }
}

namespace phpbb\event {
    class data extends \ArrayObject {}
}

namespace phpbb\db\driver {
    interface driver_interface {}
    class driver implements driver_interface {
        public $rows = [];
        public $query_log = [];
        public function sql_escape($str) { return addslashes($str); }
        public function sql_in_set($field, $array) { return "$field IN (" . implode(',', array_map('intval', $array)) . ")"; }
        public function sql_query($sql) {
            $this->query_log[] = $sql;
            return new DummyResult($this->rows);
        }
        public function sql_fetchrow($res) {
            if ($res instanceof DummyResult && !empty($res->rows)) {
                return array_shift($res->rows);
            }
            return false;
        }
        public function sql_freeresult($res) {}
        public function sql_nextid() { return 100; }
    }
    class DummyResult {
        public $rows;
        public function __construct($rows = []) { $this->rows = $rows; }
    }
}

namespace {

if (!defined('IN_PHPBB')) {
    define('IN_PHPBB', true);
}
if (!defined('USER_GROUP_TABLE')) {
    define('USER_GROUP_TABLE', 'phpbb_user_group');
}

$ext_dir = __DIR__ . '/../booskit/commendations';

require_once $ext_dir . '/event/listener.php';
require_once $ext_dir . '/service/commendations_manager.php';

use booskit\commendations\event\listener;
use booskit\commendations\service\commendations_manager;

echo "=================================================\n";
echo " Running Unit Test Suite for booskit/commendations\n";
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

// 1. Test Event Subscriptions
$subscribed = listener::getSubscribedEvents();
assert_test(isset($subscribed['core.user_setup']), 'Subscribes to core.user_setup');
assert_test(isset($subscribed['core.memberlist_view_profile']), 'Subscribes to core.memberlist_view_profile');

// 2. Test User Role Level Calculation (0..4)
$config = new \phpbb\config\config([
    'booskit_commendations_access_l1' => '10',
    'booskit_commendations_access_l2' => '20',
    'booskit_commendations_access_l3' => '30',
    'booskit_commendations_access_full' => '40',
    'booskit_commendations_perm_system' => 'legacy',
    'booskit_commendations_access_view' => '5,10,20,30,40'
]);
$db = new \phpbb\db\driver\driver();
$user = new \phpbb\user();
$cache = new \phpbb\cache\driver\dummy();
$auth = new \phpbb\auth\auth();

$manager = new commendations_manager($config, $db, $user, $cache, $auth, 'phpbb_');

// Level 4 Check
$db->rows = [['group_id' => 40]];
assert_test($manager->get_user_role_level(123) === 4, 'User with group 40 has role level 4');

// Level 1 Check
$manager = new commendations_manager($config, $db, $user, $cache, $auth, 'phpbb_');
$db->rows = [['group_id' => 10]];
assert_test($manager->get_user_role_level(123) === 1, 'User with group 10 has role level 1');

// 3. Test View Access
$manager = new commendations_manager($config, $db, $user, $cache, $auth, 'phpbb_');
$db->rows = [['group_id' => 10]];
assert_test($manager->get_user_view_access(123, 456) === true, 'User in view group has view access');

$manager = new commendations_manager($config, $db, $user, $cache, $auth, 'phpbb_');
$db->rows = [['group_id' => 99]]; // not in access_view
assert_test($manager->get_user_view_access(123, 456) === false, 'User outside view group has no view access');

// 4. Test Edit / Delete Authorization Logic
// Level 4 user can delete any commendation
$manager = new commendations_manager($config, $db, $user, $cache, $auth, 'phpbb_');
$db->rows = [['group_id' => 40]]; // Level 4
assert_test($manager->can_delete_commendation(123, 456, 999) === true, 'Level 4 admin can delete any commendation');

// Issuer can edit their own issued commendation
$manager = new commendations_manager($config, $db, $user, $cache, $auth, 'phpbb_');
$db->rows = [['group_id' => 10]]; // Level 1
assert_test($manager->can_edit_commendation(123, 456, 123) === true, 'Issuer can edit own issued commendation');

echo "\n-------------------------------------------------\n";
echo " Test Results: $passed Passed, $failed Failed.\n";
echo "-------------------------------------------------\n";

exit($failed === 0 ? 0 : 1);
}
