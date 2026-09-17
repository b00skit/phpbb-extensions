<?php
/**
 * Test Suite for booskit/dashboard phpBB Extension
 */

namespace phpbb\extension {
    if (!class_exists('phpbb\extension\base')) {
        class base {}
    }
    if (!class_exists('phpbb\extension\manager')) {
        class manager {
            public $enabled = [];
            public function is_enabled($name) { return !empty($this->enabled[$name]); }
        }
    }
}

namespace phpbb\db\migration {
    if (!class_exists('phpbb\db\migration\migration')) {
        class migration {}
    }
}

namespace phpbb\config {
    if (!class_exists('phpbb\config\config')) {
        class config extends \ArrayObject {
            public function __construct(array $array = []) { parent::__construct($array, \ArrayObject::ARRAY_AS_PROPS); }
            #[\ReturnTypeWillChange]
            public function offsetGet($key) { return isset($this[$key]) ? parent::offsetGet($key) : null; }
        }
    }
}

namespace phpbb\auth {
    if (!class_exists('phpbb\auth\auth')) {
        class auth {
            public $acl = [];
            public function acl_get($opt, $f = 0) {
                if (isset($this->acl[$opt])) {
                    return (bool) $this->acl[$opt];
                }
                return false;
            }
        }
    }
}

namespace phpbb\db\driver {
    if (!interface_exists('phpbb\db\driver\driver_interface')) {
        interface driver_interface {}
    }
    if (!class_exists('phpbb\db\driver\driver')) {
        class driver implements driver_interface {
            public $rows = [];
            public $last_query = '';
            public function sql_query($sql) {
                $this->last_query = $sql;
                return new DummyResult($this->rows);
            }
            public function sql_fetchrow($res) {
                if ($res instanceof DummyResult && !empty($res->rows)) {
                    return array_shift($res->rows);
                }
                return false;
            }
            public function sql_fetchrowset($res) {
                if ($res instanceof DummyResult) {
                    $rows = $res->rows;
                    $res->rows = [];
                    return $rows;
                }
                return [];
            }
            public function sql_fetchfield($field) {
                return 42;
            }
            public function sql_freeresult($res) {}
            public function sql_escape($str) { return addslashes($str); }
            public function sql_in_set($field, $array, $negate = false) {
                if (empty($array)) return $negate ? '1=1' : '0=1';
                $vals = implode(',', array_map('intval', $array));
                return $field . ($negate ? ' NOT IN (' : ' IN (') . $vals . ')';
            }
            public function sql_query_limit($sql, $limit, $start = 0) {
                return $this->sql_query($sql);
            }
        }
    }
    if (!class_exists('phpbb\db\driver\DummyResult')) {
        class DummyResult {
            public $rows;
            public function __construct($rows = []) { $this->rows = $rows; }
        }
    }
}

namespace {

if (!defined('IN_PHPBB')) {
    define('IN_PHPBB', true);
}
if (!defined('USER_GROUP_TABLE')) {
    define('USER_GROUP_TABLE', 'phpbb_user_group');
}
if (!defined('USERS_TABLE')) {
    define('USERS_TABLE', 'phpbb_users');
}
if (!defined('FORUMS_TABLE')) {
    define('FORUMS_TABLE', 'phpbb_forums');
}
if (!defined('TOPICS_TABLE')) {
    define('TOPICS_TABLE', 'phpbb_topics');
}
if (!defined('POSTS_TABLE')) {
    define('POSTS_TABLE', 'phpbb_posts');
}
if (!defined('SESSIONS_TABLE')) {
    define('SESSIONS_TABLE', 'phpbb_sessions');
}
if (!defined('GROUPS_TABLE')) {
    define('GROUPS_TABLE', 'phpbb_groups');
}
if (!defined('ANONYMOUS')) {
    define('ANONYMOUS', 1);
}
if (!defined('USER_IGNORE')) {
    define('USER_IGNORE', 2);
}
if (!defined('FORUM_POST')) {
    define('FORUM_POST', 1);
}
if (!defined('TOPICS_TRACK_TABLE')) {
    define('TOPICS_TRACK_TABLE', 'phpbb_topics_track');
}

$ext_dir = __DIR__ . '/../booskit/dashboard';
require_once $ext_dir . '/service/dashboard_manager.php';

use booskit\dashboard\service\dashboard_manager;

echo "=================================================\n";
echo " Running Unit Test Suite for booskit/dashboard\n";
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

// Subclass to mock user group queries
class TestableDashboardManager extends dashboard_manager {
    public $user_groups_map = [];

    public function get_user_groups($user_id) {
        return isset($this->user_groups_map[$user_id]) ? $this->user_groups_map[$user_id] : [2]; // 2 = registered users
    }
}

$config = new \phpbb\config\config([
    'booskit_dashboard_enabled'                  => 1,
    'booskit_dashboard_allowed_groups'           => '4, 5',
    'booskit_dashboard_include_awards'           => 1,
    'booskit_dashboard_group_profile_access'     => "4:2,3\n5:2,3,4,5",
    'booskit_dashboard_profile_admin_override'   => 1,
    'booskit_dashboard_issued_groups'            => '5',
    'booskit_dashboard_issued_allow_self'        => 1,
    'booskit_dashboard_recent_topics_groups'     => '4, 5',
    'booskit_dashboard_recent_topics_allow_self' => 1,
    'load_online_time'                           => 15,
    'num_users'                                  => 150,
    'num_topics'                                 => 300,
    'num_posts'                                  => 1200,
]);

$db = new \phpbb\db\driver\driver();
$ext_mgr = new \phpbb\extension\manager();
$ext_mgr->enabled['booskit/awards'] = true;
$auth = new \phpbb\auth\auth();

$mgr = new TestableDashboardManager($config, $db, $ext_mgr, null, $auth, 'phpbb_');

// Setup mock groups:
// User 10 is Admin (group 5)
// User 20 is Moderator (group 4)
// User 30 is Standard Member (group 2)
// User 40 is VIP (group 8)
$mgr->user_groups_map[10] = [5];
$mgr->user_groups_map[20] = [4];
$mgr->user_groups_map[30] = [2];
$mgr->user_groups_map[40] = [8];

// 1. Dashboard Access
assert_test($mgr->can_view_dashboard(10) === true, 'Admin (Group 5) can view dashboard');
assert_test($mgr->can_view_dashboard(20) === true, 'Mod (Group 4) can view dashboard');
assert_test($mgr->can_view_dashboard(30) === false, 'Standard User (Group 2) cannot view dashboard when restricted');

// 2. Group-to-Group Profile Access
// Rule 4:2,3 (Mod can view standard members 2,3)
// Rule 5:2,3,4,5 (Admin can view 2,3,4,5)
assert_test($mgr->can_view_user_profile(20, 30) === true, 'Mod (4) can view Standard Member (2)');
assert_test($mgr->can_view_user_profile(20, 40) === false, 'Mod (4) CANNOT view VIP (8)');
assert_test($mgr->can_view_user_profile(20, 10) === false, 'Mod (4) CANNOT view Admin (5)');
assert_test($mgr->can_view_user_profile(10, 20) === true, 'Admin (5) can view Mod (4)');
assert_test($mgr->can_view_user_profile(30, 30) === true, 'User can always view own profile');

// Admin override test
$auth->acl['a_'] = true;
assert_test($mgr->can_view_user_profile(10, 40) === true, 'Admin override allows viewing any group profile (VIP 8)');
$auth->acl['a_'] = false;

// 3. Issued Actions Permissions
// Config: booskit_dashboard_issued_groups = '5', issued_allow_self = 1
assert_test($mgr->can_view_issued_actions(10, 30) === true, 'Admin in group 5 can view what user 30 issued');
assert_test($mgr->can_view_issued_actions(20, 30) === false, 'Mod in group 4 CANNOT view what user 30 issued');
assert_test($mgr->can_view_issued_actions(20, 20) === true, 'User 20 CAN view what they issued themselves');

// 4. Recent Topics Permissions
// Config: booskit_dashboard_recent_topics_groups = '4, 5', allow_self = 1
assert_test($mgr->can_view_recent_topics(20, 30) === true, 'Mod in group 4 can view recent topics of user 30');
assert_test($mgr->can_view_recent_topics(40, 30) === false, 'VIP user 40 CANNOT view recent topics of user 30');
assert_test($mgr->can_view_recent_topics(40, 40) === true, 'User 40 CAN view their own recent topics');

// 5. Overview Stats
$stats = $mgr->get_overview_stats();
assert_test($stats['total_users'] === 150, 'Overview stats correctly reports total users');
assert_test($stats['total_topics'] === 300, 'Overview stats correctly reports total topics');
assert_test($stats['total_posts'] === 1200, 'Overview stats correctly reports total posts');

echo "\n-------------------------------------------------\n";
echo " Test Results: $passed Passed, $failed Failed.\n";
echo "-------------------------------------------------\n";

exit($failed === 0 ? 0 : 1);
}
