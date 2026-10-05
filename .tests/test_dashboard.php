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
            public $query_log = [];
            public $in_memory_tables = [
                'post_roles' => [],
                'stat_posts' => [],
                'posts'      => [],
            ];

            public function sql_query($sql) {
                $this->query_log[] = $sql;
                
                // Track DELETE from stat_posts
                if (preg_match('/DELETE FROM \w*booskit_dashboard_stat_posts WHERE post_id = (\d+)/', $sql, $m)) {
                    $pid = (int) $m[1];
                    $this->in_memory_tables['stat_posts'] = array_values(array_filter(
                        $this->in_memory_tables['stat_posts'],
                        function($row) use ($pid) { return (int)$row['post_id'] !== $pid; }
                    ));
                } elseif (preg_match('/DELETE FROM \w*booskit_dashboard_stat_posts WHERE post_id IN \(([^)]+)\)/', $sql, $m)) {
                    $pids = array_map('intval', explode(',', $m[1]));
                    $this->in_memory_tables['stat_posts'] = array_values(array_filter(
                        $this->in_memory_tables['stat_posts'],
                        function($row) use ($pids) { return !in_array((int)$row['post_id'], $pids, true); }
                    ));
                } elseif (strpos($sql, 'DELETE FROM') === 0 && strpos($sql, 'booskit_dashboard_stat_posts') !== false) {
                    $this->in_memory_tables['stat_posts'] = [];
                }

                // Track DELETE from post_roles
                if (preg_match('/DELETE FROM \w*booskit_dashboard_post_roles WHERE post_id = (\d+)/', $sql, $m)) {
                    $pid = (int) $m[1];
                    unset($this->in_memory_tables['post_roles'][$pid]);
                } elseif (preg_match('/DELETE FROM \w*booskit_dashboard_post_roles WHERE post_id IN \(([^)]+)\)/', $sql, $m)) {
                    $pids = array_map('intval', explode(',', $m[1]));
                    foreach ($pids as $pid) {
                        unset($this->in_memory_tables['post_roles'][$pid]);
                    }
                }

                // Track INSERT into post_roles
                if (strpos($sql, 'INSERT INTO') === 0 && strpos($sql, 'booskit_dashboard_post_roles') !== false) {
                    if (preg_match("/VALUES\s*\(\s*(\d+),\s*(\d+),\s*'([^']*)',\s*(\d+)\s*\)/is", $sql, $m)) {
                        $pid = (int) $m[1];
                        $uid = (int) $m[2];
                        $roles = $m[3];
                        $time = (int) $m[4];
                        $this->in_memory_tables['post_roles'][$pid] = [
                            'post_id'    => $pid,
                            'poster_id'  => $uid,
                            'user_roles' => $roles,
                            'post_time'  => $time,
                        ];
                    }
                }

                // Track UPDATE post_roles
                if (strpos($sql, 'UPDATE') === 0 && strpos($sql, 'booskit_dashboard_post_roles') !== false) {
                    if (preg_match("/WHERE post_id = (\d+)/", $sql, $wm)) {
                        $pid = (int) $wm[1];
                        if (preg_match("/user_roles = '([^']*)'/", $sql, $rm)) {
                            if (isset($this->in_memory_tables['post_roles'][$pid])) {
                                $this->in_memory_tables['post_roles'][$pid]['user_roles'] = $rm[1];
                            }
                        }
                    }
                }

                // Track INSERT into stat_posts
                if (strpos($sql, 'INSERT INTO') === 0 && strpos($sql, 'booskit_dashboard_stat_posts') !== false) {
                    if (preg_match("/VALUES\s*\(\s*'([^']*)',\s*(\d+),\s*(\d+),\s*(\d+),\s*(\d+),\s*(\d+)(?:,\s*'([^']*)')?\s*\)/is", $sql, $m)) {
                        $this->in_memory_tables['stat_posts'][] = [
                            'stat_tag'      => $m[1],
                            'post_id'       => (int) $m[2],
                            'topic_id'      => (int) $m[3],
                            'forum_id'      => (int) $m[4],
                            'poster_id'     => (int) $m[5],
                            'post_time'     => (int) $m[6],
                            'poster_groups' => isset($m[7]) ? $m[7] : '',
                        ];
                    }
                }

                // SELECT post_roles
                if (strpos($sql, 'booskit_dashboard_post_roles') !== false && strpos($sql, 'SELECT') === 0) {
                    if (preg_match('/WHERE post_id = (\d+)/', $sql, $m)) {
                        $pid = (int) $m[1];
                        if (isset($this->in_memory_tables['post_roles'][$pid])) {
                            return new DummyResult([$this->in_memory_tables['post_roles'][$pid]]);
                        }
                        return new DummyResult([]);
                    }
                    if (!empty($this->in_memory_tables['post_roles'])) {
                        return new DummyResult(array_values($this->in_memory_tables['post_roles']));
                    }
                    return new DummyResult([]);
                }

                // SELECT posts
                if (strpos($sql, 'phpbb_posts') !== false && strpos($sql, 'SELECT') === 0) {
                    if (!empty($this->in_memory_tables['posts'])) {
                        return new DummyResult(array_values($this->in_memory_tables['posts']));
                    }
                    return new DummyResult([]);
                }

                return new DummyResult($this->rows);
            }

            public function sql_build_array($mode, $array) {
                if ($mode === 'INSERT') {
                    $cols = implode(', ', array_keys($array));
                    $vals = implode(', ', array_map(function($v) {
                        return is_int($v) ? $v : "'" . addslashes((string)$v) . "'";
                    }, array_values($array)));
                    return "($cols) VALUES ($vals)";
                } elseif ($mode === 'UPDATE') {
                    $parts = [];
                    foreach ($array as $k => $v) {
                        $val = is_int($v) ? $v : "'" . addslashes((string)$v) . "'";
                        $parts[] = "$k = $val";
                    }
                    return implode(', ', $parts);
                }
                return '';
            }

            public function sql_like_expression($expr) {
                return "LIKE '" . addslashes($expr) . "'";
            }

            public function get_any_char() {
                return '%';
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

            public function sql_nextid() {
                return 1;
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

// Subclass to mock user group queries and stat defs
class TestableDashboardManager extends dashboard_manager {
    public $user_groups_map = [];
    public $stat_defs_map = [];

    public function get_user_groups($user_id) {
        return isset($this->user_groups_map[$user_id]) ? $this->user_groups_map[$user_id] : [2];
    }

    public function get_stat_definition_by_tag($stat_tag) {
        $clean = strtolower(trim((string) $stat_tag));
        return isset($this->stat_defs_map[$clean]) ? $this->stat_defs_map[$clean] : null;
    }

    public function get_stat_definitions($cat_id = 0, $only_visible = false, $viewer_id = 0) {
        $defs = array_values($this->stat_defs_map);
        if ($cat_id > 0) {
            $defs = array_values(array_filter($defs, function($d) use ($cat_id) {
                return isset($d['cat_id']) && (int)$d['cat_id'] === (int)$cat_id;
            }));
        }
        if ($only_visible) {
            $defs = array_values(array_filter($defs, function($d) use ($viewer_id) {
                return $this->can_view_stat_definition($viewer_id, $d);
            }));
        }
        return $defs;
    }
}

$config = new \phpbb\config\config([
    'booskit_dashboard_enabled'                  => 1,
    'booskit_dashboard_perm_system'              => 'legacy',
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

// 1. Dashboard Access (Legacy)
assert_test($mgr->can_view_dashboard(10) === true, 'Admin (Group 5) can view dashboard');
assert_test($mgr->can_view_dashboard(20) === true, 'Mod (Group 4) can view dashboard');
assert_test($mgr->can_view_dashboard(30) === false, 'Standard User (Group 2) cannot view dashboard when restricted');

// 2. Group-to-Group Profile Access
assert_test($mgr->can_view_user_profile(20, 30) === true, 'Mod (4) can view Standard Member (2)');
assert_test($mgr->can_view_user_profile(20, 40) === false, 'Mod (4) CANNOT view VIP (8)');
assert_test($mgr->can_view_user_profile(20, 10) === false, 'Mod (4) CANNOT view Admin (5)');
assert_test($mgr->can_view_user_profile(10, 20) === true, 'Admin (5) can view Mod (4)');
assert_test($mgr->can_view_user_profile(20, 20) === true, 'Mod user can view own profile');

// Admin override test
$auth->acl['a_'] = true;
assert_test($mgr->can_view_user_profile(10, 40) === true, 'Admin override allows viewing any group profile (VIP 8)');
$auth->acl['a_'] = false;

// 3. Issued Actions Permissions
assert_test($mgr->can_view_issued_actions(10, 30) === true, 'Admin in group 5 can view what user 30 issued');
assert_test($mgr->can_view_issued_actions(20, 30) === false, 'Mod in group 4 CANNOT view what user 30 issued');
assert_test($mgr->can_view_issued_actions(20, 20) === true, 'User 20 CAN view what they issued themselves');

// 4. Recent Topics Permissions
assert_test($mgr->can_view_recent_topics(20, 30) === true, 'Mod in group 4 can view recent topics of user 30');
assert_test($mgr->can_view_recent_topics(40, 30) === false, 'VIP user 40 CANNOT view recent topics of user 30');
assert_test($mgr->can_view_recent_topics(20, 20) === true, 'User 20 CAN view their own recent topics');

// 5. Overview Stats
$stats = $mgr->get_overview_stats();
assert_test($stats['total_users'] === 150, 'Overview stats correctly reports total users');
assert_test($stats['total_topics'] === 300, 'Overview stats correctly reports total topics');
assert_test($stats['total_posts'] === 1200, 'Overview stats correctly reports total posts');

// =========================================================================
// 6. STATISTICS MODULE: CATEGORY & DEFINITION PERMISSIONS
// =========================================================================
echo "\n--- Testing Statistics Permissions ---\n";

$cat_training = [
    'cat_id'   => 1,
    'cat_name' => 'Training',
];

$cat_vip = [
    'cat_id'   => 2,
    'cat_name' => 'VIP Category',
];

$cat_empty = [
    'cat_id'   => 3,
    'cat_name' => 'Empty Category',
];

// $stat_ftp is in Training (1). View: Mod(4) & Admin(5). Use: VIP(8) & Admin(5).
$stat_ftp = [
    'stat_id'                  => 1,
    'stat_tag'                 => 'ftp_phase1',
    'stat_title'               => 'FTP Phase 1',
    'cat_id'                   => 1,
    'allowed_groups'           => '4,5',
    'allowed_groups_array'     => [4, 5],
    'use_allowed_groups'       => '8,5',
    'use_allowed_groups_array' => [8, 5],
];

// $stat_vip_only is in VIP Category (2). View: VIP(8). Use: VIP(8).
$stat_vip_only = [
    'stat_id'                  => 2,
    'stat_tag'                 => 'vip_event',
    'stat_title'               => 'VIP Event',
    'cat_id'                   => 2,
    'allowed_groups'           => '8',
    'allowed_groups_array'     => [8],
    'use_allowed_groups'       => '8',
    'use_allowed_groups_array' => [8],
];

// $stat_public is in Uncategorized (0). Public view, public use.
$stat_public = [
    'stat_id'                  => 3,
    'stat_tag'                 => 'general_patrol',
    'stat_title'               => 'General Patrol',
    'cat_id'                   => 0,
    'allowed_groups'           => '',
    'allowed_groups_array'     => [],
    'use_allowed_groups'       => '',
    'use_allowed_groups_array' => [],
];

// $stat_bots has View: Admin(5), Use: Bots(6)
$stat_bots = [
    'stat_id'                  => 4,
    'stat_tag'                 => 'bot_test',
    'stat_title'               => 'Bot Test',
    'cat_id'                   => 0,
    'allowed_groups'           => '5',
    'allowed_groups_array'     => [5],
    'use_allowed_groups'       => '6',
    'use_allowed_groups_array' => [6],
];

$mgr->stat_defs_map['ftp_phase1'] = $stat_ftp;
$mgr->stat_defs_map['vip_event'] = $stat_vip_only;
$mgr->stat_defs_map['general_patrol'] = $stat_public;
$mgr->stat_defs_map['bot_test'] = $stat_bots;

// Definition View permissions (defined purely by tag):
assert_test($mgr->can_view_stat_definition(10, $stat_ftp) === true, 'Admin (5) can view FTP stat definition');
assert_test($mgr->can_view_stat_definition(20, $stat_ftp) === true, 'Mod (4) can view FTP stat definition');
assert_test($mgr->can_view_stat_definition(30, $stat_ftp) === false, 'Standard User (2) CANNOT view FTP stat definition');
assert_test($mgr->can_view_stat_definition(40, $stat_vip_only) === true, 'VIP (8) can view VIP event stat');
assert_test($mgr->can_view_stat_definition(20, $stat_vip_only) === false, 'Mod (4) CANNOT view VIP event stat');
assert_test($mgr->can_view_stat_definition(30, $stat_public) === true, 'Standard User (2) can view public stat');
assert_test($mgr->can_view_stat_definition(10, $stat_bots) === true, 'Admin (5) can view Bot Test stat');
assert_test($mgr->can_view_stat_definition(20, $stat_bots) === false, 'Mod (4) CANNOT view Bot Test stat');

// Category View permissions (dynamically derived from definition permissions):
assert_test($mgr->can_view_stat_category(10, $cat_training) === true, 'Admin (5) can view Training category');
assert_test($mgr->can_view_stat_category(20, $cat_training) === true, 'Mod (4) can view Training category (has visible FTP stat)');
assert_test($mgr->can_view_stat_category(30, $cat_training) === false, 'Standard User (2) CANNOT view Training category (no visible stats under it)');
assert_test($mgr->can_view_stat_category(40, $cat_vip) === true, 'VIP (8) can view VIP category (has visible VIP stat)');
assert_test($mgr->can_view_stat_category(20, $cat_vip) === false, 'Mod (4) CANNOT view VIP category (no visible stats under it)');
assert_test($mgr->can_view_stat_category(20, $cat_empty) === false, 'Mod (4) CANNOT view Empty category (no definitions)');

// Definition Usage permissions (defined purely by tag):
assert_test($mgr->can_use_stat_definition(10, $stat_ftp) === true, 'Admin (5) can use FTP stat');
assert_test($mgr->can_use_stat_definition(40, $stat_ftp) === true, 'VIP (8) can use FTP stat');
assert_test($mgr->can_use_stat_definition(20, $stat_ftp) === false, 'Mod (4) CANNOT use FTP stat (not in tag use_allowed_groups)');
assert_test($mgr->can_use_stat_definition(30, $stat_ftp) === false, 'Standard User (2) CANNOT use FTP stat');
assert_test($mgr->can_use_stat_definition(40, $stat_vip_only) === true, 'VIP (8) can use VIP event stat');
assert_test($mgr->can_use_stat_definition(30, $stat_vip_only) === false, 'Standard User (2) CANNOT use VIP event stat');
assert_test($mgr->can_use_stat_definition(30, $stat_public) === true, 'Standard User (2) can use public stat');
assert_test($mgr->can_use_stat_definition(10, $stat_bots) === false, 'Admin (5) CANNOT use Bot-only stat');

// Category Usage permissions (dynamically derived from definition permissions):
assert_test($mgr->can_use_stat_category(40, $cat_training) === true, 'VIP (8) can use Training category (has usable FTP stat)');
assert_test($mgr->can_use_stat_category(20, $cat_training) === false, 'Mod (4) CANNOT use Training category (no usable stats under it)');
assert_test($mgr->can_use_stat_category(40, $cat_vip) === true, 'VIP (8) can use VIP category (has usable VIP stat)');
assert_test($mgr->can_use_stat_category(30, $cat_vip) === false, 'Standard User (2) CANNOT use VIP category');

// =========================================================================
// 7. POST ROLES ONLY STORED ON STAT TAGS & EDITING / REMOVING STATS TESTS
// =========================================================================
echo "\n--- Testing Tag Detection, Role Storing, and Edit/Delete Mechanics ---\n";

// Test 7.1: A normal post with NO stat tags should NOT store any post roles!
$normal_post = [
    'post_id'   => 50,
    'topic_id'  => 1,
    'forum_id'  => 2,
    'poster_id' => 30,
    'post_time' => 1599999000,
    'post_text' => 'This is just a regular post without any tags at all.',
];
$db->in_memory_tables['posts'][50] = $normal_post;
$mgr->sync_post_stats(50, $normal_post['post_text'], 1, 2, 30, 1599999000);

assert_test($mgr->get_post_roles(50) === null, 'Normal post without stat tags does NOT store post roles in DB');
$stat_posts_50 = array_filter($db->in_memory_tables['stat_posts'], function($r){ return (int)$r['post_id'] === 50; });
assert_test(empty($stat_posts_50), 'Normal post without stat tags does NOT create stat_posts');

// Test 7.2: User 30 writes post #101 containing [stat=vip_event] and [stat=general_patrol]
// Roles SHOULD be stored because stat tags are called!
$post_101 = [
    'post_id'   => 101,
    'topic_id'  => 1,
    'forum_id'  => 2,
    'poster_id' => 30,
    'post_time' => 1600000000,
    'post_text' => 'Hello this is my post [stat=vip_event][/stat] and [stat=general_patrol][/stat]',
];
$db->in_memory_tables['posts'][101] = $post_101;
$mgr->sync_post_stats(101, $post_101['post_text'], 1, 2, 30, 1600000000);

// Verify post roles recorded in DB:
$roles_101 = $mgr->get_post_roles(101);
assert_test($roles_101 === [2], 'Post 101 recorded member roles at time of post as [2]');

// Verify stat posts created:
$stat_posts_101 = array_values(array_filter($db->in_memory_tables['stat_posts'], function($r){ return (int)$r['post_id'] === 101; }));
assert_test(count($stat_posts_101) === 1, 'Post 101 has exactly 1 indexed stat post (vip_event was restricted)');
assert_test($stat_posts_101[0]['stat_tag'] === 'general_patrol', 'Post 101 indexed general_patrol only');

// Test 7.3: User 40 (Group 8, VIP) writes post #102 containing [visiblestat=vip_event] and [stat=general_patrol]
$post_102 = [
    'post_id'   => 102,
    'topic_id'  => 1,
    'forum_id'  => 2,
    'poster_id' => 40,
    'post_time' => 1600000100,
    'post_text' => 'VIP post [visiblestat=vip_event]VIP Badge[/visiblestat] and [stat=general_patrol][/stat]',
];
$db->in_memory_tables['posts'][102] = $post_102;
$mgr->sync_post_stats(102, $post_102['post_text'], 1, 2, 40, 1600000100);

$roles_102 = $mgr->get_post_roles(102);
assert_test($roles_102 === [8], 'Post 102 recorded member roles at time of post as [8]');
$stat_posts_102 = array_values(array_filter($db->in_memory_tables['stat_posts'], function($r){ return (int)$r['post_id'] === 102; }));
assert_test(count($stat_posts_102) === 2, 'Post 102 indexed both vip_event and general_patrol for VIP user');

// Test 7.4: EDITING POST #102 to REMOVE one stat tag (remove general_patrol, keep vip_event)
$post_102['post_text'] = 'VIP post [visiblestat=vip_event]VIP Badge[/visiblestat]';
$db->in_memory_tables['posts'][102] = $post_102;
$mgr->sync_post_stats(102, $post_102['post_text'], 1, 2, 40, 1600000100);

$stat_posts_102_after_edit = array_values(array_filter($db->in_memory_tables['stat_posts'], function($r){ return (int)$r['post_id'] === 102; }));
assert_test(count($stat_posts_102_after_edit) === 1, 'Editing post 102 to remove tag reduced indexed count to 1');
assert_test($stat_posts_102_after_edit[0]['stat_tag'] === 'vip_event', 'Editing post 102 kept vip_event and removed general_patrol');

// Test 7.5: EDITING POST #101 to REMOVE ALL STAT TAGS
$post_101['post_text'] = 'Hello I edited my post and removed all stat tags.';
$db->in_memory_tables['posts'][101] = $post_101;
$mgr->sync_post_stats(101, $post_101['post_text'], 1, 2, 30, 1600000000);

$stat_posts_101_after_edit = array_values(array_filter($db->in_memory_tables['stat_posts'], function($r){ return (int)$r['post_id'] === 101; }));
assert_test(empty($stat_posts_101_after_edit), 'Editing post 101 to remove all tags cleared all indexed stat posts');
assert_test($mgr->get_post_roles(101) === null, 'Editing post 101 to remove all tags cleared post roles from DB');

// Test 7.6: EDITING POST #50 (which had NO tags) to ADD a stat tag
$post_50_edited = [
    'post_id'   => 50,
    'topic_id'  => 1,
    'forum_id'  => 2,
    'poster_id' => 40, // VIP
    'post_time' => 1599999000,
    'post_text' => 'Edited post with added [stat=vip_event][/stat]',
];
$db->in_memory_tables['posts'][50] = $post_50_edited;
$mgr->sync_post_stats(50, $post_50_edited['post_text'], 1, 2, 40, 1599999000);

assert_test($mgr->get_post_roles(50) === [8], 'Editing post 50 to add a tag recorded member roles as [8]');
$stat_posts_50_after = array_values(array_filter($db->in_memory_tables['stat_posts'], function($r){ return (int)$r['post_id'] === 50; }));
assert_test(count($stat_posts_50_after) === 1 && $stat_posts_50_after[0]['stat_tag'] === 'vip_event', 'Editing post 50 to add tag successfully indexed vip_event');

// Test 7.7: DELETING A POST
unset($db->in_memory_tables['posts'][50]);
$mgr->delete_post_stats(50, true);
$stat_posts_50_deleted = array_values(array_filter($db->in_memory_tables['stat_posts'], function($r){ return (int)$r['post_id'] === 50; }));
assert_test(empty($stat_posts_50_deleted), 'Deleting post 50 removed stat posts');
assert_test($mgr->get_post_roles(50) === null, 'Deleting post 50 removed post roles from DB');

// =========================================================================
// 8. PROMOTION & RESYNC TEST
// =========================================================================
echo "\n--- Promoting User 30 and Testing Resync Consistency ---\n";

// User 30 creates post #200 with [stat=vip_event] while only having Role [2] (restricted)
$post_200 = [
    'post_id'   => 200,
    'topic_id'  => 1,
    'forum_id'  => 2,
    'poster_id' => 30,
    'post_time' => 1600000500,
    'post_text' => 'Post by user 30 [stat=vip_event][/stat]',
];
$db->in_memory_tables['posts'][200] = $post_200;
$mgr->sync_post_stats(200, $post_200['post_text'], 1, 2, 30, 1600000500);

assert_test($mgr->get_post_roles(200) === [2], 'Post 200 recorded member roles as [2] at post time');
$stat_posts_200 = array_values(array_filter($db->in_memory_tables['stat_posts'], function($r){ return (int)$r['post_id'] === 200; }));
assert_test(empty($stat_posts_200), 'Post 200 has 0 stats because user 30 lacks permission');

// User 30 is now promoted to Group 8 (VIP)!
$mgr->user_groups_map[30] = [8];

// Admin runs Resync
$resync_count = $mgr->resync_all_stat_posts();

// Post 200 was created when User 30 was [2], so resync must NOT index it
$stat_posts_200_resync = array_values(array_filter($db->in_memory_tables['stat_posts'], function($r){ return (int)$r['post_id'] === 200; }));
assert_test(empty($stat_posts_200_resync), 'After resync, historical post 200 is STILL restricted (not counted)');

// User 30 creates post #201 while in Group 8
$post_201 = [
    'post_id'   => 201,
    'topic_id'  => 1,
    'forum_id'  => 2,
    'poster_id' => 30,
    'post_time' => 1600000600,
    'post_text' => 'Post by promoted user 30 [stat=vip_event][/stat]',
];
$db->in_memory_tables['posts'][201] = $post_201;
$mgr->sync_post_stats(201, $post_201['post_text'], 1, 2, 30, 1600000600);

assert_test($mgr->get_post_roles(201) === [8], 'Post 201 recorded member roles as [8]');
$stat_posts_201 = array_values(array_filter($db->in_memory_tables['stat_posts'], function($r){ return (int)$r['post_id'] === 201; }));
assert_test(count($stat_posts_201) === 1 && $stat_posts_201[0]['stat_tag'] === 'vip_event', 'Post 201 created after promotion is successfully indexed');

// Secondary resync verification
$resync_count_2 = $mgr->resync_all_stat_posts();
assert_test($resync_count_2 === 2, 'Resync indexed exactly 2 valid occurrences (post 102 and post 201)');

echo "\n-------------------------------------------------\n";
echo " Test Results: $passed Passed, $failed Failed.\n";
echo "-------------------------------------------------\n";

exit($failed === 0 ? 0 : 1);
}
