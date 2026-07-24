<?php
/**
 * Test Suite for booskit/awards phpBB Extension
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

$ext_dir = __DIR__ . '/../booskit/awards';

require_once $ext_dir . '/event/listener.php';
require_once $ext_dir . '/service/award_manager.php';

use booskit\awards\event\listener;
use booskit\awards\service\award_manager;

echo "=================================================\n";
echo " Running Unit Test Suite for booskit/awards      \n";
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
assert_test(isset($subscribed['core.text_formatter_s9e_configure_after']), 'Subscribes to core.text_formatter_s9e_configure_after');

// 2. Test User Role Level Calculation
$config = new \phpbb\config\config([
    'booskit_awards_access_l1' => '10, 11',
    'booskit_awards_access_l2' => '20',
    'booskit_awards_access_full' => '30',
    'booskit_awards_source' => 'local',
    'booskit_awards_perm_system' => 'legacy'
]);
$db = new \phpbb\db\driver\driver();
$user = new \phpbb\user();

$manager = new award_manager($config, $db, $user, 'phpbb_awards', 'phpbb_award_defs', 'phpbb_award_perm_groups');

// Test Regular User (Level 0)
$db->rows = [['group_id' => 5]];
assert_test($manager->get_user_role_level(123) === 0, 'User with group 5 has role level 0 (Regular)');

// Test L1 User (Level 1)
$manager = new award_manager($config, $db, $user, 'phpbb_awards', 'phpbb_award_defs', 'phpbb_award_perm_groups');
$db->rows = [['group_id' => 10]];
assert_test($manager->get_user_role_level(123) === 1, 'User with group 10 has role level 1 (L1)');

// Test L2 User (Level 2)
$manager = new award_manager($config, $db, $user, 'phpbb_awards', 'phpbb_award_defs', 'phpbb_award_perm_groups');
$db->rows = [['group_id' => 20]];
assert_test($manager->get_user_role_level(123) === 2, 'User with group 20 has role level 2 (L2)');

// Test Full Access User (Level 3)
$manager = new award_manager($config, $db, $user, 'phpbb_awards', 'phpbb_award_defs', 'phpbb_award_perm_groups');
$db->rows = [['group_id' => 30]];
assert_test($manager->get_user_role_level(123) === 3, 'User with group 30 has role level 3 (Full Access)');

// 3. Test Legacy Permission Actions (can_view_awards, can_add_award, can_remove_award)
// Full Access User (level 3) vs Regular User (level 0)
$manager = new award_manager($config, $db, $user, 'phpbb_awards', 'phpbb_award_defs', 'phpbb_award_perm_groups');
$db->rows = [
    ['group_id' => 30], // viewer
    ['group_id' => 5]   // target
];
assert_test($manager->can_view_awards(100, 200) === true, 'Level 3 viewer can view awards');
$manager = new award_manager($config, $db, $user, 'phpbb_awards', 'phpbb_award_defs', 'phpbb_award_perm_groups');
$db->rows = [
    ['group_id' => 30], // viewer
    ['group_id' => 5]   // target
];
assert_test($manager->can_add_award(100, 200) === true, 'Level 3 viewer can add awards to regular user');
$manager = new award_manager($config, $db, $user, 'phpbb_awards', 'phpbb_award_defs', 'phpbb_award_perm_groups');
$db->rows = [
    ['group_id' => 30], // viewer
    ['group_id' => 5]   // target
];
assert_test($manager->can_remove_award(100, 200) === true, 'Level 3 viewer can remove awards from regular user');

// Regular User (level 0) vs Regular User (level 0)
$manager = new award_manager($config, $db, $user, 'phpbb_awards', 'phpbb_award_defs', 'phpbb_award_perm_groups');
$db->rows = [
    ['group_id' => 5], // viewer
    ['group_id' => 5]  // target
];
assert_test($manager->can_add_award(101, 200) === false, 'Level 0 viewer cannot add awards');
$manager = new award_manager($config, $db, $user, 'phpbb_awards', 'phpbb_award_defs', 'phpbb_award_perm_groups');
$db->rows = [
    ['group_id' => 5], // viewer
    ['group_id' => 5]  // target
];
assert_test($manager->can_remove_award(101, 200) === false, 'Level 0 viewer cannot remove awards');

echo "\n-------------------------------------------------\n";
echo " Test Results: $passed Passed, $failed Failed.\n";
echo "-------------------------------------------------\n";

exit($failed === 0 ? 0 : 1);
}
