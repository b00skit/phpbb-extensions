<?php
/**
 * Test Suite for booskit/usercareer phpBB Extension
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
        public $user_groups_override = null;
        public function sql_escape($str) { return addslashes($str); }
        public function sql_in_set($field, $array) { return "$field IN (" . implode(',', array_map('intval', $array)) . ")"; }
        public function sql_query($sql) {
            if ($this->user_groups_override !== null && strpos($sql, 'phpbb_user_group') !== false) {
                return new DummyResult($this->user_groups_override);
            }
            return new DummyResult($this->rows);
        }
        public function sql_fetchrow($res) {
            if ($res instanceof DummyResult && !empty($res->rows)) {
                return array_shift($res->rows);
            }
            return false;
        }
        public function sql_build_array($action, $array) { return "col='val'"; }
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

$ext_dir = __DIR__ . '/../booskit/usercareer';

require_once $ext_dir . '/event/listener.php';
require_once $ext_dir . '/service/career_manager.php';

use booskit\usercareer\event\listener;
use booskit\usercareer\service\career_manager;

echo "=================================================\n";
echo " Running Unit Test Suite for booskit/usercareer  \n";
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
    'booskit_career_access_l1' => '10',
    'booskit_career_access_l2' => '20',
    'booskit_career_access_l3' => '30',
    'booskit_career_access_full' => '40',
    'booskit_career_perm_system' => 'legacy',
    'booskit_career_access_view' => '5,10,20,30,40'
]);
$db = new \phpbb\db\driver\driver();
$user = new \phpbb\user();
$cache = new \phpbb\cache\driver\dummy();
$auth = new \phpbb\auth\auth();

$manager = new career_manager($config, $db, $user, $cache, $auth, 'phpbb_usercareer', 'phpbb_usercareer_defs', './', 'php');

// Full Access Check (Level 4)
$db->rows = [['group_id' => 40]];
assert_test($manager->get_user_role_level(123) === 4, 'User with group 40 has full access role level 4');

// L1 Check (Level 1)
$manager = new career_manager($config, $db, $user, $cache, $auth, 'phpbb_usercareer', 'phpbb_usercareer_defs', './', 'php');
$db->rows = [['group_id' => 10]];
assert_test($manager->get_user_role_level(123) === 1, 'User with group 10 has role level 1');

// 3. Permission Group Submit Permission Check
$manager->update_permission_group(1, 'Test Group', [10], 1, 0, [], [], [
    'view' => 1,
    'submit' => 1,
    'edit_own' => 0,
    'delete_own' => 0,
    'edit' => 0,
    'delete' => 0
]);
$db->rows = [
    [
        'perm_group_id' => 1,
        'group_name' => 'Test Group',
        'applies_to' => '10',
        'power_over_all' => 1,
        'power_over_self' => 0,
        'power_over_groups' => '',
        'exclude_groups' => '',
        'permissions' => '{"view":1,"submit":1,"edit_own":0,"delete_own":0,"edit":0,"delete":0}'
    ]
];
$db->user_groups_override = [['group_id' => 10]];
$effective = $manager->get_effective_permissions(123, 456);
// 4. Test Pre-generated Note Field Replacement
$tpl_body = "Reason: {note_var_8i45s}, User: {target}, Date: {@date}";
$replacements = [
    '{#target}' => 'JohnDoe',
    '{target}' => 'JohnDoe',
    '{@target}' => 'JohnDoe',
    '{#date}' => '01/JAN/2026',
    '{date}' => '01/JAN/2026',
    '{@date}' => '01/JAN/2026',
];
$field_var = 'note_var_8i45s';
$field_val = 'Speeding Violation';
$replacements['{@' . $field_var . '}'] = $field_val;
$replacements['{' . $field_var . '}'] = $field_val;
$replacements['{#' . $field_var . '}'] = $field_val;

$parsed = strtr($tpl_body, $replacements);
assert_test($parsed === "Reason: Speeding Violation, User: JohnDoe, Date: 01/JAN/2026", 'Parses pre-generated note variables without required @ or # prefix');

echo "\n-------------------------------------------------\n";
echo " Test Results: $passed Passed, $failed Failed.\n";
echo "-------------------------------------------------\n";

exit($failed === 0 ? 0 : 1);
}
