<?php
/**
 * Test Suite for booskit/mention phpBB Extension
 */

namespace phpbb\extension {
    if (!class_exists('phpbb\extension\base')) {
        class base {}
    }
}

namespace phpbb\db\migration {
    if (!class_exists('phpbb\db\migration\migration')) {
        class migration {}
    }
}

namespace Symfony\Component\HttpFoundation {
    if (!class_exists('Symfony\Component\HttpFoundation\JsonResponse')) {
        class JsonResponse {
            public $data;
            public $status;
            public function __construct($data = null, $status = 200) {
                $this->data = $data;
                $this->status = $status;
            }
            public function getStatusCode() { return $this->status; }
            public function getData() { return $this->data; }
            public function getContent() { return json_encode($this->data); }
        }
    }
}

namespace Symfony\Component\EventDispatcher {
    if (!interface_exists('Symfony\Component\EventDispatcher\EventSubscriberInterface')) {
        interface EventSubscriberInterface {
            public static function getSubscribedEvents();
        }
    }
}

namespace phpbb\config {
    if (!class_exists('phpbb\config\config')) {
        class config extends \ArrayObject {
            public function __construct(array $array = []) { parent::__construct($array, \ArrayObject::ARRAY_AS_PROPS); }
            #[\ReturnTypeWillChange]
            public function offsetGet($key) { return isset($this[$key]) ? parent::offsetGet($key) : null; }
            public function set($key, $val) { $this[$key] = $val; }
        }
    }
    if (!class_exists('phpbb\config\db_text')) {
        class db_text {
            public $data = [];
            public function __construct($db, $table) {}
            public function get($key) { return isset($this->data[$key]) ? $this->data[$key] : ''; }
            public function set($key, $val) { $this->data[$key] = $val; }
        }
    }
}

namespace phpbb\log {
    if (!interface_exists('phpbb\log\log_interface')) {
        interface log_interface {
            public function add($mode, $user_id, $log_ip, $log_operation, $log_time = false, $additional_data = []);
        }
    }
    if (!class_exists('phpbb\log\dummy_log')) {
        class dummy_log implements log_interface {
            public $logs = [];
            public function add($mode, $user_id, $log_ip, $log_operation, $log_time = false, $additional_data = []) {
                $this->logs[] = compact('mode', 'user_id', 'log_ip', 'log_operation');
            }
        }
    }
}

namespace phpbb\auth {
    if (!class_exists('phpbb\auth\auth')) {
        class auth {
            public $permissions = [];
            public function acl_get($opt, $forum_id = 0) { return !empty($this->permissions[$opt]); }
            public function acl_get_list($users, $opt, $forum_id = 0) {
                $ret = [];
                foreach ($users as $u) {
                    $ret[$forum_id][$opt][] = $u;
                }
                return $ret;
            }
        }
    }
}

namespace phpbb {
    if (!class_exists('phpbb\user')) {
        class user {
            public $data = ['user_id' => 2, 'is_registered' => true];
            public $ip = '127.0.0.1';
            public $lang = ['CONFIG_UPDATED' => 'Configuration updated', 'FORM_INVALID' => 'Invalid form'];
            public function add_lang_ext($ext, $file) {}
        }
    }

    if (!class_exists('phpbb\user_loader')) {
        class user_loader {
            public function get_user($id) {
                return ['user_id' => $id, 'username' => 'User_' . $id];
            }
            public function get_avatar($id) {
                return '';
            }
        }
    }
}

namespace phpbb\template {
    if (!class_exists('phpbb\template\template')) {
        class template {
            public $vars = [];
            public $blocks = [];
            public function assign_vars($vars) { $this->vars = array_merge($this->vars, $vars); }
            public function assign_block_vars($blockname, $vars) {
                $this->blocks[$blockname][] = $vars;
            }
        }
    }
}

namespace phpbb\event {
    if (!class_exists('phpbb\event\data')) {
        class data extends \ArrayObject {}
    }
}

namespace phpbb\controller {
    if (!class_exists('phpbb\controller\helper')) {
        class helper {
            public function route($name, array $params = []) { return '/app.php' . ($name ? '/' . $name : ''); }
        }
    }
}

namespace phpbb\request {
    if (!interface_exists('phpbb\request\request_interface')) {
        interface request_interface {
            public function variable($var_name, $default, $multibyte = false, $path = \phpbb\request\request_interface::REQUEST);
            public function is_set_post($name);
        }
    }
    if (!class_exists('phpbb\request\dummy_request')) {
        class dummy_request implements request_interface {
            public $data = [];
            public $post_keys = [];
            public function variable($var_name, $default, $multibyte = false, $path = 0) {
                return isset($this->data[$var_name]) ? $this->data[$var_name] : $default;
            }
            public function is_set_post($name) {
                return in_array($name, $this->post_keys) || array_key_exists($name, $this->data);
            }
        }
    }
}

namespace phpbb\notification {
    if (!class_exists('phpbb\notification\manager')) {
        class manager {
            public $notifications_added = [];
            public function add_notifications($types, $data) {
                $this->notifications_added[] = ['types' => $types, 'data' => $data];
            }
        }
    }
}

namespace phpbb\notification\type {
    if (!class_exists('phpbb\notification\type\base')) {
        class base {
            protected $db;
            protected $language;
            protected $user;
            protected $auth;
            protected $phpbb_root_path;
            protected $php_ext;
            protected $user_notifications_table;
            protected $notification_manager;
            protected $notification_type_id = 1;
            private $data = [];

            public function __construct($db, $language, $user, $auth, $phpbb_root_path, $php_ext, $user_notifications_table) {
                $this->db = $db;
                $this->language = $language;
                $this->user = $user;
                $this->auth = $auth;
                $this->phpbb_root_path = $phpbb_root_path;
                $this->php_ext = $php_ext;
                $this->user_notifications_table = $user_notifications_table;
            }

            public function set_notification_manager($notification_manager) {
                $this->notification_manager = $notification_manager;
            }

            public function set_data($k, $v) { $this->data[$k] = $v; }
            public function get_data($k) { return isset($this->data[$k]) ? $this->data[$k] : null; }

            protected function get_authorised_recipients($users, $forum_id, $options, $sort = false) {
                $ret = [];
                foreach ($users as $uid) {
                    $ret[$uid] = [''];
                }
                return $ret;
            }
            public function get_url() { return 'viewtopic.php?p=123#p123'; }
        }
    }

    if (!class_exists('phpbb\notification\type\post')) {
        class post extends base {
            protected $user_loader;
            protected $config;
            public function set_config($config) { $this->config = $config; }
            public function set_user_loader($loader) { $this->user_loader = $loader; }
            public function get_email_template_variables() { return ['TOPIC_TITLE' => 'Test']; }
        }
    }
}

namespace phpbb\db\driver {
    if (!interface_exists('phpbb\db\driver\driver_interface')) {
        interface driver_interface {}
    }
    if (!class_exists('phpbb\db\driver\dummy_driver')) {
        class dummy_driver implements driver_interface {
            public $mock_users = [];
            public $mock_groups = [];
            public $mock_user_groups = [];
            public $query_log = [];

            public function sql_escape($str) { return addslashes($str); }
            public function sql_like_expression($expression) { return "LIKE '" . addslashes($expression) . "'"; }
            public function get_any_char() { return '%'; }
            public function sql_in_set($field, $array) {
                if (empty($array)) return '1=0';
                $escaped = array_map(function($x) { return is_numeric($x) ? $x : "'" . addslashes($x) . "'"; }, $array);
                return "$field IN (" . implode(',', $escaped) . ")";
            }
            public function sql_query($sql) {
                $this->query_log[] = $sql;
                return new DummyResult($this->filter_mock_data($sql));
            }
            public function sql_query_limit($sql, $total, $offset = 0) {
                $this->query_log[] = $sql;
                $res = $this->filter_mock_data($sql);
                return new DummyResult(array_slice($res, $offset, $total));
            }
            public function sql_fetchrow($res) {
                if ($res instanceof DummyResult && !empty($res->rows)) {
                    return array_shift($res->rows);
                }
                return false;
            }
            public function sql_freeresult($res) {}

            private function filter_mock_data($sql) {
                $result = [];

                // GROUPS_TABLE query
                if (strpos($sql, 'phpbb_groups') !== false || strpos($sql, 'groups') !== false) {
                    if (strpos($sql, 'group_name') !== false && preg_match("/IN \(([^)]+)\)/", $sql, $m)) {
                        $names = array_map(function($s) { return trim($s, " '\""); }, explode(',', $m[1]));
                        foreach ($this->mock_groups as $g) {
                            if (in_array($g['group_name'], $names)) {
                                $result[] = $g;
                            }
                        }
                    } else {
                        $result = $this->mock_groups;
                    }
                    return $result;
                }

                // USER_GROUP_TABLE query
                if (strpos($sql, 'phpbb_user_group') !== false || strpos($sql, 'user_group') !== false) {
                    if (preg_match("/user_id = (\d+)/", $sql, $m)) {
                        $uid = (int) $m[1];
                        foreach ($this->mock_user_groups as $ug) {
                            if ($ug['user_id'] == $uid) {
                                $result[] = $ug;
                            }
                        }
                    } else if (preg_match("/group_id IN \(([^)]+)\)/", $sql, $m)) {
                        $gids = array_map('intval', explode(',', $m[1]));
                        foreach ($this->mock_user_groups as $ug) {
                            if (in_array($ug['group_id'], $gids)) {
                                $result[] = $ug;
                            }
                        }
                    } else {
                        $result = $this->mock_user_groups;
                    }
                    return $result;
                }

                // USERS_TABLE query
                foreach ($this->mock_users as $u) {
                    if (strpos($sql, 'username_clean') !== false) {
                        if (preg_match("/LIKE '([^%']*)%?'/", $sql, $m)) {
                            $term = str_replace('%', '', $m[1]);
                            if ($term === '' || stripos($u['username_clean'], $term) !== false) {
                                $result[] = $u;
                            }
                        } else if (preg_match("/IN \(([^)]+)\)/", $sql, $m)) {
                            $names = array_map(function($s) { return trim($s, " '\""); }, explode(',', $m[1]));
                            if (in_array($u['username_clean'], $names)) {
                                $result[] = $u;
                            }
                        }
                    } else {
                        $result[] = $u;
                    }
                }
                return $result;
            }
        }
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
if (!defined('USERS_TABLE')) {
    define('USERS_TABLE', 'phpbb_users');
}
if (!defined('GROUPS_TABLE')) {
    define('GROUPS_TABLE', 'phpbb_groups');
}
if (!defined('USER_GROUP_TABLE')) {
    define('USER_GROUP_TABLE', 'phpbb_user_group');
}
if (!defined('USER_NORMAL')) {
    define('USER_NORMAL', 0);
}
if (!defined('USER_FOUNDER')) {
    define('USER_FOUNDER', 3);
}
if (!defined('ANONYMOUS')) {
    define('ANONYMOUS', 1);
}
if (!defined('ITEM_APPROVED')) {
    define('ITEM_APPROVED', 1);
}
if (!defined('ITEM_UNAPPROVED')) {
    define('ITEM_UNAPPROVED', 0);
}
if (!defined('ENT_COMPAT')) {
    define('ENT_COMPAT', 2);
}

// Function stubs for testing
if (!function_exists('add_form_key')) {
    function add_form_key($key) {}
}
if (!function_exists('check_form_key')) {
    function check_form_key($key) { return true; }
}
if (!function_exists('adm_back_link')) {
    function adm_back_link($url) { return ' <a href="' . $url . '">Back</a>'; }
}

// Include files under test
require_once __DIR__ . '/../booskit/mention/ext.php';
require_once __DIR__ . '/../booskit/mention/service/mention_manager.php';
require_once __DIR__ . '/../booskit/mention/controller/mention.php';
require_once __DIR__ . '/../booskit/mention/controller/acp_controller.php';
require_once __DIR__ . '/../booskit/mention/notification/type/mention.php';
require_once __DIR__ . '/../booskit/mention/notification/type/group_mention.php';
require_once __DIR__ . '/../booskit/mention/event/listener.php';
require_once __DIR__ . '/../booskit/mention/migrations/v100_initial.php';
require_once __DIR__ . '/../booskit/mention/migrations/v101_group_mentions.php';

$passed = 0;
$failed = 0;

function assert_test($condition, $name) {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo " [PASS] $name\n";
    } else {
        $failed++;
        echo " [FAIL] $name\n";
    }
}

echo "=================================================\n";
echo " Running Unit Test Suite for booskit/mention\n";
echo "=================================================\n\n";

// 1. Extension and composer metadata
$ext = new \booskit\mention\ext();
assert_test($ext instanceof \phpbb\extension\base, 'booskit/mention/ext.php instantiates cleanly');

$composer_json = json_decode(file_get_contents(__DIR__ . '/../booskit/mention/composer.json'), true);
assert_test($composer_json['name'] === 'booskit/mention', 'composer.json has package name booskit/mention');

// 2. Services and Routing
$services_content = file_get_contents(__DIR__ . '/../booskit/mention/config/services.yml');
assert_test(strpos($services_content, 'booskit.mention.service.mention_manager') !== false, 'services.yml defines mention_manager service');
assert_test(strpos($services_content, 'booskit.mention.notification.type.group_mention') !== false, 'services.yml defines group_mention notification service');
assert_test(strpos($services_content, 'booskit.mention.controller.acp.settings') !== false, 'services.yml defines acp.settings controller');

// 3. Mention Manager Service Tests
$config = new \phpbb\config\config([
    'booskit_mention_enabled' => 1,
    'booskit_mention_group_enabled' => 1,
]);
$db = new \phpbb\db\driver\dummy_driver();
$user = new \phpbb\user();

$db->mock_groups = [
    ['group_id' => 2, 'group_name' => 'Registered Users', 'group_type' => 0, 'group_colour' => ''],
    ['group_id' => 4, 'group_name' => 'Global Moderators', 'group_type' => 0, 'group_colour' => '00AA00'],
    ['group_id' => 5, 'group_name' => 'Administrators', 'group_type' => 0, 'group_colour' => 'AA0000'],
    ['group_id' => 7, 'group_name' => 'Support Team', 'group_type' => 0, 'group_colour' => '0000FF'],
    ['group_id' => 8, 'group_name' => 'Developers', 'group_type' => 0, 'group_colour' => 'FFA500'],
];

// User 2 in group 2 (Registered Users)
// User 10 in group 5 (Administrators)
// User 20 in group 8 (Developers)
$db->mock_user_groups = [
    ['user_id' => 2, 'group_id' => 2],
    ['user_id' => 10, 'group_id' => 5],
    ['user_id' => 20, 'group_id' => 8],
];

$manager = new \booskit\mention\service\mention_manager($config, $db, $user, 'phpbb_');

// Test 3A: Empty matrix -> Everyone can mention everyone
assert_test($manager->is_group_mention_enabled() === true, 'Group mention is enabled by default');
assert_test($manager->can_user_mention_group(2, 8) === true, 'When matrix is empty, normal user can mention Developers');
assert_test($manager->can_user_mention_group(2, 5) === true, 'When matrix is empty, normal user can mention Administrators');

// Test 3B: Group mention permissions in matrix
// Set matrix restricting Group 2 to only mention Group 2, while Group 5 has '*' (can mention all groups)
$manager->set_group_matrix([
    5 => '*', // Admins can mention all groups
    2 => [2], // Registered users can only mention Registered Users
]);
// Normal user 2 should NOT be able to mention Developers (8)
assert_test($manager->can_user_mention_group(2, 8) === false, 'Restricted group 2 cannot mention group 8');
assert_test($manager->can_user_mention_group(2, 2) === true, 'Restricted group 2 can mention group 2');

// Admin user 10 belongs to group 5 which has '*' (all groups)
assert_test($manager->can_user_mention_all_groups(10) === true, 'Admin user is recognized as having mention-all permission via matrix');
assert_test($manager->can_user_mention_group(10, 8) === true, 'Admin user can mention group 8');
assert_test($manager->can_user_mention_group(10, 2) === true, 'Admin user can mention group 2');

// User 20 belongs to group 8 (Developers), which is NOT defined in matrix
// As soon as at least 1 group is defined, unlisted groups have NO mention rights
assert_test($manager->can_user_mention_group(20, 8) === false, 'Unlisted group 8 cannot mention group 8 when matrix is defined');
assert_test($manager->can_user_mention_group(20, 2) === false, 'Unlisted group 8 cannot mention group 2 when matrix is defined');
assert_test($manager->can_user_mention_all_groups(20) === false, 'Unlisted group 8 cannot mention all groups when matrix is defined');

// Clearing the matrix restores free-for-all
$manager->set_group_matrix([]);
assert_test($manager->can_user_mention_group(20, 8) === true, 'Clearing matrix restores free-for-all mention permission');
assert_test($manager->can_user_mention_all_groups(20) === true, 'Clearing matrix restores free-for-all mention-all permission');

// Restore matrix for subsequent tests
$manager->set_group_matrix([
    5 => '*',
    2 => [2],
]);

// Test 3C: Enable/disable group mentions
$manager->set_group_mention_enabled(false);
assert_test($manager->is_group_mention_enabled() === false, 'Group mentioning can be disabled');
assert_test($manager->can_user_mention_group(10, 8) === false, 'Disabled group mentioning blocks mention checks');
$manager->set_group_mention_enabled(true);

// 4. Listener Subscribed Events & BBCode
$template = new \phpbb\template\template();
$helper = new \phpbb\controller\helper();
$notif_manager = new \phpbb\notification\manager();
$listener = new \booskit\mention\event\listener($template, $user, $helper, $notif_manager, $db, $manager);

$events = \booskit\mention\event\listener::getSubscribedEvents();
assert_test(isset($events['core.submit_post_end']), 'Listener subscribes to core.submit_post_end');

// Test BBCode configuration for mentiongroup
$mock_bbcodes = new class extends \ArrayObject {
    public function addCustom($bbcode, $template) {
        if (strpos($bbcode, 'mentiongroup') !== false) {
            $this['mentiongroup'] = ['bbcode' => $bbcode, 'template' => $template];
        } else {
            $this['mention'] = ['bbcode' => $bbcode, 'template' => $template];
        }
    }
};
$configurator = (object) ['BBCodes' => $mock_bbcodes];
$listener->configure_s9e_bbcode(['configurator' => $configurator]);
assert_test(isset($configurator->BBCodes['mentiongroup']), 'configure_s9e_bbcode registers custom mentiongroup BBCode');

// Test Fallback rendering for mentiongroup
$event_render = new \phpbb\event\data(['html' => 'Notice for [mentiongroup=8]Developers[/mentiongroup]!']);
$listener->render_s9e_bbcode($event_render);
assert_test(strpos($event_render['html'], 'memberlist.php?mode=group&amp;g=8') !== false, 'render_s9e_bbcode converts [mentiongroup=8] to group link');

// 5. Post Submission Dispatches Group Mention Notification
$event_post_group = [
    'mode'            => 'post',
    'post_visibility' => ITEM_APPROVED,
    'username'        => 'Admin',
    'subject'         => 'Group Meeting',
    'data'            => [
        'post_id'   => 301,
        'topic_id'  => 80,
        'forum_id'  => 1,
        'poster_id' => 10,
        'message'   => 'Calling [mentiongroup=8]Developers[/mentiongroup]',
    ],
];
$notif_manager->notifications_added = [];
$listener->submit_post_end($event_post_group);
assert_test(count($notif_manager->notifications_added) === 1, 'submit_post_end dispatches notifications for group mentions');
assert_test(in_array('booskit.mention.notification.type.group_mention', $notif_manager->notifications_added[0]['types']), 'Group mention notification type is included');

// Test edit does not dispatch
$event_post_edit = $event_post_group;
$event_post_edit['mode'] = 'edit';
$notif_manager->notifications_added = [];
$listener->submit_post_end($event_post_edit);
assert_test(count($notif_manager->notifications_added) === 0, 'Group mentions are NOT notified on post edit');

// 6. Group Mention Notification Type Class
$auth = new \phpbb\auth\auth();
$group_notif = new \booskit\mention\notification\type\group_mention($db, null, $user, $auth, './', 'php', 'phpbb_user_notifications');
$group_notif->set_notification_manager($notif_manager);
$group_notif->set_mention_manager($manager);

assert_test($group_notif->get_type() === 'booskit.mention.notification.type.group_mention', 'Group mention get_type is correct');

// find_users_for_notification expands group 8 to member user 20
$post_mention_data = [
    'poster_id' => 10, // Admin (can mention all)
    'forum_id'  => 1,
    'post_text' => 'Attention [mentiongroup=8]Developers[/mentiongroup] please.',
];
$recipients = $group_notif->find_users_for_notification($post_mention_data);
assert_test(isset($recipients[20]), 'find_users_for_notification notifies member of mentioned group 8 (user 20)');

// Test unauthorized poster cannot trigger group notification
$post_unauth_data = [
    'poster_id' => 2, // Normal user in group 2 (restricted from group 8)
    'forum_id'  => 1,
    'post_text' => 'Trying to ping [mentiongroup=8]Developers[/mentiongroup].',
];
$recipients_unauth = $group_notif->find_users_for_notification($post_unauth_data);
assert_test(empty($recipients_unauth), 'Unauthorized author mention does not notify restricted group');

// 7. Autocomplete Controller with Group Mentions
$req = new \phpbb\request\dummy_request();
$ctrl = new \booskit\mention\controller\mention($db, $user, $auth, $req, $manager);

// Query "dev" as Admin user 10 (can mention all)
$user->data['user_id'] = 10;
$req->data['q'] = 'dev';
$resp = $ctrl->find_users();
$results = json_decode($resp->getContent(), true);
$found_group = false;
foreach ($results as $item) {
    if (!empty($item['is_group']) && $item['id'] === 8) {
        $found_group = true;
        break;
    }
}
assert_test($found_group === true, 'Autocomplete returns matching group Developers for query "dev"');

// Query "dev" as restricted user 2
$user->data['user_id'] = 2;
$resp_restricted = $ctrl->find_users();
$results_restricted = json_decode($resp_restricted->getContent(), true);
$found_restricted_group = false;
foreach ($results_restricted as $item) {
    if (!empty($item['is_group']) && $item['id'] === 8) {
        $found_restricted_group = true;
        break;
    }
}
assert_test($found_restricted_group === false, 'Autocomplete does NOT return restricted group to unauthorized user');

// 8. ACP Controller
$log = new \phpbb\log\dummy_log();
$acp_ctrl = new \booskit\mention\controller\acp_controller($config, $req, $template, $user, $log, $manager);

// Simulate ACP form submit
$req->post_keys = ['submit'];
$req->data['booskit_mention_group_enabled'] = 1;
$req->data['mention_targets_all_5'] = 1;
$req->data['mention_targets_all_2'] = 0;
$req->data['mention_targets_2'] = [2, 7];

try {
    @$acp_ctrl->handle('adm/index.php');
} catch (\Exception $e) {
    // trigger_error ends execution in real phpBB
}

$saved_matrix = $manager->get_group_matrix();
assert_test($saved_matrix[5] === '*', 'ACP saves wildcard for group 5');
assert_test($saved_matrix[2] === [2, 7], 'ACP saves target group array for group 2');

// Simulate adding a new group (group 8) via ACP
$req->post_keys = ['add_group_btn'];
$req->data['configured_groups'] = [5, 2];
$req->data['mention_targets_all_5'] = 1;
$req->data['mention_targets_all_2'] = 0;
$req->data['mention_targets_2'] = [2, 7];
$req->data['add_source_group_id'] = 8;

try {
    @$acp_ctrl->handle('adm/index.php');
} catch (\Exception $e) {}

$saved_matrix = $manager->get_group_matrix();
assert_test(isset($saved_matrix[8]), 'ACP successfully adds group 8 to mention matrix');
assert_test($saved_matrix[8] === '*', 'Newly added group 8 defaults to wildcard (all groups)');

// Simulate removing a group (group 2) via ACP
$req->post_keys = ['delete_source_group'];
$req->data['configured_groups'] = [5, 2, 8];
$req->data['delete_source_group'] = 2;
$req->data['add_source_group_id'] = 0;

try {
    @$acp_ctrl->handle('adm/index.php');
} catch (\Exception $e) {}

$saved_matrix = $manager->get_group_matrix();
assert_test(!isset($saved_matrix[2]), 'ACP successfully removes group 2 from mention matrix');
assert_test(isset($saved_matrix[5]) && isset($saved_matrix[8]), 'Remaining groups 5 and 8 are preserved');

// Simulate clearing all groups from matrix in ACP
$req->post_keys = ['submit'];
$req->data['configured_groups_present'] = 1;
$req->data['configured_groups'] = [];
$req->data['delete_source_group'] = 0;
$req->data['add_source_group_id'] = 0;

try {
    @$acp_ctrl->handle('adm/index.php');
} catch (\Exception $e) {}

$cleared_matrix = $manager->get_group_matrix();
assert_test(empty($cleared_matrix), 'Clearing all groups via ACP returns matrix to empty (free-for-all)');
assert_test($manager->can_user_mention_group(2, 8) === true, 'Free-for-all permits normal user to mention group 8');


// 9. Migrations
$mig101 = new \booskit\mention\migrations\v101_group_mentions();
assert_test($mig101->depends_on() === ['\booskit\mention\migrations\v100_initial'], 'Migration v101 depends on v100');
$update_data = $mig101->update_data();
assert_test($update_data[0][1][0] === 'booskit_mention_group_enabled', 'Migration v101 adds booskit_mention_group_enabled');

echo "\n-------------------------------------------------\n";
echo " Test Results: $passed Passed, $failed Failed.\n";
echo "-------------------------------------------------\n";

exit($failed === 0 ? 0 : 1);
}
