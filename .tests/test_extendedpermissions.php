<?php
/**
 * Test Suite for booskit/extendedpermissions phpBB Extension
 */

namespace phpbb\extension {
    class base {}
    class manager {
        public function all_enabled() {
            return [
                'booskit/awards' => 'ext/booskit/awards/',
                'booskit/disciplinary' => 'ext/booskit/disciplinary/',
            ];
        }
        public function create_extension_metadata_manager($name) {
            return new class($name) {
                protected $name;
                public function __construct($name) { $this->name = $name; }
                public function get_metadata($type) {
                    if ($type === 'display-name') return 'Display ' . $this->name;
                    return ['description' => 'Test ' . $this->name, 'version' => '1.0.0'];
                }
            };
        }
    }
}

namespace phpbb\db\migration {
    class migration {
        protected $table_prefix = 'phpbb_';
        protected $config = [];
    }
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
        public function set($key, $val) { $this[$key] = $val; }
    }
}

namespace phpbb\auth {
    class auth {
        public $permissions = [];
        public $cache = [];
        public function acl_get($opt, $forum_id = 0) {
            return !empty($this->permissions[$opt]);
        }
    }
}

namespace phpbb {
    class user {
        public $data = ['user_id' => 2];
        public $lang = [];
        public function add_lang_ext($ext, $file) {}
    }
}

namespace phpbb\event {
    class data extends \ArrayObject {}
}

namespace phpbb\db\driver {
    interface driver_interface {}
    class driver implements driver_interface {
        public $rows = [];
        public $last_query = '';
        public $next_id = 1;

        public function sql_query($sql) {
            $this->last_query = $sql;
            return new DummyResult($this->rows);
        }
        public function sql_freeresult($res) {}
        public function sql_fetchrow($res) {
            if ($res instanceof DummyResult && !empty($res->rows)) {
                return array_shift($res->rows);
            }
            return false;
        }
        public function sql_nextid() {
            return $this->next_id++;
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
    }
    class DummyResult {
        public $rows;
        public function __construct($rows = []) {
            $this->rows = $rows;
        }
    }
}

namespace phpbb\request {
    class request {
        public $vars = [];
        public function variable($name, $default) {
            return isset($this->vars[$name]) ? $this->vars[$name] : $default;
        }
    }
}

namespace phpbb\template {
    class template {
        public $vars = [];
        public function assign_vars($ary) {
            $this->vars = array_merge($this->vars, $ary);
        }
    }
}

namespace {

if (!defined('IN_PHPBB')) {
    define('IN_PHPBB', true);
}
if (!defined('MODULES_TABLE')) {
    define('MODULES_TABLE', 'phpbb_modules');
}
if (!defined('GROUPS_TABLE')) {
    define('GROUPS_TABLE', 'phpbb_groups');
}
if (!defined('USER_GROUP_TABLE')) {
    define('USER_GROUP_TABLE', 'phpbb_user_group');
}
if (!defined('EXT_TABLE')) {
    define('EXT_TABLE', 'phpbb_ext');
}

function send_status_line($code, $msg) {}
function assert_test($cond, $msg) {
    if ($cond) {
        echo "[PASS] $msg\n";
    } else {
        echo "[FAIL] $msg\n";
        exit(1);
    }
}

require_once __DIR__ . '/../booskit/extendedpermissions/event/main_listener.php';
require_once __DIR__ . '/../booskit/extendedpermissions/migrations/install.php';
require_once __DIR__ . '/../booskit/extendedpermissions/migrations/v110_custom_extensions.php';
require_once __DIR__ . '/../booskit/extendedpermissions/migrations/v111_acp_access_perm.php';
require_once __DIR__ . '/../booskit/extendedpermissions/service/permission_manager.php';
require_once __DIR__ . '/../booskit/extendedpermissions/acp/custom_extensions_module_info.php';

echo "Running tests for booskit/extendedpermissions...\n\n";

// Test 1: Event listener subscriptions
$subscribed = \booskit\extendedpermissions\event\main_listener::getSubscribedEvents();
assert_test(isset($subscribed['core.permissions']), 'Subscribes to core.permissions');
assert_test(isset($subscribed['core.module_auth']), 'Subscribes to core.module_auth');
assert_test(isset($subscribed['core.modify_module_row']), 'Subscribes to core.modify_module_row');
assert_test(isset($subscribed['core.mcp_global_f_read_auth_after']), 'Subscribes to core.mcp_global_f_read_auth_after');
assert_test(isset($subscribed['core.page_header']), 'Subscribes to core.page_header');
assert_test(isset($subscribed['core.user_setup']), 'Subscribes to core.user_setup');
assert_test(isset($subscribed['core.adm_page_header']), 'Subscribes to core.adm_page_header');

// Test 2: Permission registration
$config = new \phpbb\config\config();
$auth = new \phpbb\auth\auth();
$request = new \phpbb\request\request();
$template = new \phpbb\template\template();
$db = new \phpbb\db\driver\driver();
$user = new \phpbb\user();

$listener = new \booskit\extendedpermissions\event\main_listener($config, $auth, $request, $template, $db, $user);

$event_perm = new \phpbb\event\data(['permissions' => []]);
$listener->add_permissions($event_perm);
assert_test(isset($event_perm['permissions']['a_extensions_manage']), 'Registers a_extensions_manage permission');
assert_test(isset($event_perm['permissions']['a_acp_access']), 'Registers a_acp_access permission');
assert_test($event_perm['permissions']['a_acp_access']['cat'] === 'settings', 'a_acp_access category is settings');
assert_test($event_perm['permissions']['a_acp_access']['lang'] === 'ACL_A_ACP_ACCESS', 'a_acp_access lang is ACL_A_ACP_ACCESS');
assert_test(isset($event_perm['permissions']['m_mod_logs']), 'Registers m_mod_logs permission');
assert_test(isset($event_perm['permissions']['m_last_actions']), 'Registers m_last_actions permission');
assert_test($event_perm['permissions']['m_mod_logs']['cat'] === 'misc', 'm_mod_logs category is misc');
assert_test($event_perm['permissions']['m_last_actions']['cat'] === 'misc', 'm_last_actions category is misc');

// Test 3: hide_latest_logs behaviour (default disabled permission = hides latest logs)
$auth->permissions['m_last_actions'] = false;
$template->vars = [];
$listener->hide_latest_logs();
assert_test(isset($template->vars['S_SHOW_LOGS']) && $template->vars['S_SHOW_LOGS'] === false, 'Hides latest logs when m_last_actions is disabled');

$auth->permissions['m_last_actions'] = true;
$template->vars = [];
$listener->hide_latest_logs();
assert_test(!isset($template->vars['S_SHOW_LOGS']), 'Does not hide latest logs when m_last_actions is enabled');

// Test 4: hide_mod_logs_tab behaviour
$auth->permissions['m_mod_logs'] = false;
$event_row = new \phpbb\event\data([
    'row' => ['module_basename' => 'mcp_logs'],
    'module_row' => ['display' => 1]
]);
$listener->hide_mod_logs_tab($event_row);
assert_test($event_row['module_row']['display'] === 0, 'Hides mod logs tab when m_mod_logs is disabled');

$auth->permissions['m_mod_logs'] = true;
$event_row = new \phpbb\event\data([
    'row' => ['module_basename' => 'mcp_logs'],
    'module_row' => ['display' => 1]
]);
$listener->hide_mod_logs_tab($event_row);
assert_test($event_row['module_row']['display'] === 1, 'Keeps mod logs tab displayed when m_mod_logs is enabled');

// Test 5: restrict_mod_logs behaviour
set_error_handler(function($errno, $errstr) {
    throw new \Exception($errstr);
});

$auth->permissions['m_mod_logs'] = false;
$request->vars['i'] = 'mcp_logs';
$event_logs = new \phpbb\event\data(['mode' => 'front']);
$caught = false;
try {
    $listener->restrict_mod_logs($event_logs);
} catch (\Exception $e) {
    $caught = true;
    assert_test($e->getMessage() === 'NOT_AUTHORISED', 'Denied access to moderator logs when m_mod_logs is disabled');
}
assert_test($caught, 'Exception thrown on unauthorized mod logs access');

$auth->permissions['m_mod_logs'] = true;
$caught = false;
try {
    $listener->restrict_mod_logs($event_logs);
} catch (\Exception $e) {
    $caught = true;
}
assert_test(!$caught, 'Allows access to moderator logs when m_mod_logs is enabled');

restore_error_handler();

// Test 6: check_module_auth behaviour for extensions manage permission
$event_auth = new \phpbb\event\data(['module_auth' => 'ext_foo/bar && acl_a_board']);
$listener->check_module_auth($event_auth);
assert_test(strpos($event_auth['module_auth'], 'acl_a_extensions_manage') !== false, 'Injects acl_a_extensions_manage into extension module auth check');

// Test 7: Migration update_data & revert_data
$migration = new \booskit\extendedpermissions\migrations\install();
$update_data = $migration->update_data();
$has_m_mod_logs = false;
$has_m_last_actions = false;
$has_a_acp_access = false;
foreach ($update_data as $entry) {
    if ($entry[0] === 'permission.add') {
        if ($entry[1][0] === 'm_mod_logs') $has_m_mod_logs = true;
        if ($entry[1][0] === 'm_last_actions') $has_m_last_actions = true;
        if ($entry[1][0] === 'a_acp_access') $has_a_acp_access = true;
    }
}
assert_test($has_m_mod_logs, 'Migration update_data adds m_mod_logs permission');
assert_test($has_m_last_actions, 'Migration update_data adds m_last_actions permission');
assert_test($has_a_acp_access, 'Migration update_data adds a_acp_access permission');

// Test 8: custom_extensions_module_info structure
$info = new \booskit\extendedpermissions\acp\custom_extensions_module_info();
$module_info = $info->module();
assert_test($module_info['title'] === 'ACP_EXTENSIONS_MANAGER', 'ACP module category title is ACP_EXTENSIONS_MANAGER');
assert_test(isset($module_info['modes']['custom_extensions']), 'Module defines custom_extensions mode');
assert_test($module_info['modes']['custom_extensions']['title'] === 'ACP_CUSTOM_EXTENSIONS', 'custom_extensions title is ACP_CUSTOM_EXTENSIONS');
assert_test(in_array('ACP_EXTENSIONS_MANAGER', $module_info['modes']['custom_extensions']['cat'], true), 'custom_extensions category is ACP_EXTENSIONS_MANAGER');
assert_test($module_info['modes']['custom_extensions']['auth'] === 'ext_booskit/extendedpermissions && acl_a_board', 'custom_extensions auth requires acl_a_board by default');

// Test 9: v110_custom_extensions migration
$v110 = new \booskit\extendedpermissions\migrations\v110_custom_extensions();
$v110_depends = \booskit\extendedpermissions\migrations\v110_custom_extensions::depends_on();
assert_test(in_array('\booskit\extendedpermissions\migrations\install', $v110_depends, true), 'v110 depends on install migration');

$schema = $v110->update_schema();
assert_test(isset($schema['add_tables']['phpbb_booskit_extperm_groups']), 'v110 schema adds booskit_extperm_groups table');
$v110_data = $v110->update_data();
$has_ext_mgr_cat = false;
$has_custom_ext_mod = false;
foreach ($v110_data as $entry) {
    if ($entry[0] === 'module.add') {
        if ($entry[1][1] === 'ACP_CAT_DOT_MODS' && $entry[1][2] === 'ACP_EXTENSIONS_MANAGER') {
            $has_ext_mgr_cat = true;
        }
        if ($entry[1][1] === 'ACP_EXTENSIONS_MANAGER' && isset($entry[1][2]['modes']) && in_array('custom_extensions', $entry[1][2]['modes'], true)) {
            $has_custom_ext_mod = true;
        }
    }
}
assert_test($has_ext_mgr_cat, 'v110 migration adds Extensions Manager category under ACP_CAT_DOT_MODS');
assert_test($has_custom_ext_mod, 'v110 migration adds Custom Extensions ACP module under Extensions Manager');

// Test 9b: v111_acp_access_perm migration
$v111 = new \booskit\extendedpermissions\migrations\v111_acp_access_perm();
$v111_depends = \booskit\extendedpermissions\migrations\v111_acp_access_perm::depends_on();
assert_test(in_array('\booskit\extendedpermissions\migrations\v110_custom_extensions', $v111_depends, true), 'v111 depends on v110 migration');
$v111_data = $v111->update_data();
$v111_has_acp_perm = false;
foreach ($v111_data as $entry) {
    if ($entry[0] === 'permission.add' && $entry[1][0] === 'a_acp_access') {
        $v111_has_acp_perm = true;
    }
}
assert_test($v111_has_acp_perm, 'v111 migration adds a_acp_access permission');

// Test 10: permission_manager CRUD operations
$db_mgr = new \phpbb\db\driver\driver();
$ext_mgr = new \phpbb\extension\manager();
$perm_mgr = new \booskit\extendedpermissions\service\permission_manager($config, $db_mgr, $user, $auth, $ext_mgr);

// Add permission group
$new_id = $perm_mgr->add_permission_group('Moderators', [4, 5], ['booskit/awards', 'booskit/disciplinary']);
assert_test($new_id === 1, 'add_permission_group returns new ID');
assert_test(strpos($db_mgr->last_query, 'INSERT INTO') !== false, 'add_permission_group executes INSERT query');

// Get permission groups with row simulation
$db_mgr->rows = [
    [
        'perm_group_id' => 1,
        'group_name' => 'Moderators',
        'applies_to' => '4,5',
        'can_manage_module' => 0,
        'allowed_extensions' => json_encode(['booskit/awards']),
        'permissions' => json_encode(['extensions' => ['booskit/awards' => 1]]),
    ]
];
$groups = $perm_mgr->get_permission_groups(true);
assert_test(count($groups) === 1, 'get_permission_groups returns 1 group');
assert_test($groups[0]['applies_to_array'] === [4, 5], 'applies_to parsed to integer array [4, 5]');
assert_test($groups[0]['allowed_extensions_array'] === ['booskit/awards'], 'allowed_extensions parsed to array');

// Update permission group
$perm_mgr->update_permission_group(1, 'Senior Moderators', [5], ['booskit/disciplinary']);
assert_test(strpos($db_mgr->last_query, 'UPDATE') !== false, 'update_permission_group executes UPDATE query');

// Delete permission group
$perm_mgr->delete_permission_group(1);
assert_test(strpos($db_mgr->last_query, 'DELETE FROM') !== false, 'delete_permission_group executes DELETE query');

// Test 11: can_user_access_module check
// Founder / admin access
$auth->permissions['a_board'] = true;
assert_test($perm_mgr->can_user_access_module(2) === true, 'Admin with a_board can access Custom Extensions module');

$auth->permissions['a_board'] = false;

// Global module access groups config
$config['booskit_extperm_module_access'] = '10,12';
$db_mgr->rows = [['group_id' => 10]]; // user is in group 10
assert_test($perm_mgr->can_user_access_module(2) === true, 'User in configured module_access_groups can access Custom Extensions module');

// User not in module_access_groups cannot access Custom Extensions module even with extension permissions
$config['booskit_extperm_module_access'] = '12';
$perm_mgr->clear_cache();
$db_mgr->rows = [['group_id' => 7]]; // user is in group 7, not 12
assert_test($perm_mgr->can_user_access_module(2) === false, 'User not in module_access_groups cannot access Custom Extensions module');

// Test 12: can_user_access_extension check
$perm_mgr->clear_cache();
$db_mgr->rows = [['group_id' => 7]];
$reflection = new \ReflectionClass($perm_mgr);
$prop = $reflection->getProperty('cached_perm_groups');
$prop->setAccessible(true);
$prop->setValue($perm_mgr, [
    [
        'perm_group_id' => 3,
        'group_name' => 'Support',
        'applies_to_array' => [7],
        'allowed_extensions_array' => ['booskit/disciplinary'],
    ]
]);
assert_test($perm_mgr->can_user_access_extension(2, 'booskit/disciplinary') === true, 'User can access extension granted by permission group');
assert_test($perm_mgr->can_user_access_extension(2, 'booskit/awards') === false, 'User cannot access extension not granted by permission group');

// Test 13: main_listener with permission_manager integrated
$listener_with_mgr = new \booskit\extendedpermissions\event\main_listener($config, $auth, $request, $template, $db_mgr, $user, $perm_mgr);

// Permitted extension allows auth rewrite
$valid_tokens_base = [
    'acl_([a-z0-9_]+)(,\$id)?' => '(int) $auth->acl_get(\'\\1\'\\2)',
    'ext_([a-zA-Z0-9_/]+)'      => 'array_key_exists(\'\\1\', $ext_mgr->all_enabled())',
];
$event_allowed = new \phpbb\event\data([
    'module_auth' => 'ext_booskit/disciplinary && acl_a_board',
    'valid_tokens' => $valid_tokens_base,
]);
$listener_with_mgr->check_module_auth($event_allowed);
assert_test(strpos($event_allowed['module_auth'], 'acl_a_extensions_manage') !== false, 'Permitted extension rewrites auth to include acl_a_extensions_manage');
assert_test(isset($event_allowed['valid_tokens']['acl_a_board']), 'Permitted extension sets valid_tokens for acl_a_board');

// Verify evaluation like phpBB's functions_module.php
$eval_tokens = ['ext_booskit/disciplinary', '&&', 'acl_a_board'];
$mod_auth_eval = implode(' ', $eval_tokens);
$mod_auth_eval = preg_replace(
    array_map(function($v) { return '#' . $v . '#'; }, array_keys($event_allowed['valid_tokens'])),
    array_values($event_allowed['valid_tokens']),
    $mod_auth_eval
);
$phpbb_extension_manager = $ext_mgr;
$is_auth_result = false;
eval('$is_auth_result = (int) (' . $mod_auth_eval . ');');
assert_test($is_auth_result === 1, 'functions_module.php eval completes successfully without syntax error');

// Forbidden extension does NOT rewrite auth (denies non-admin)
$event_forbidden = new \phpbb\event\data(['module_auth' => 'ext_booskit/awards && acl_a_board']);
$listener_with_mgr->check_module_auth($event_forbidden);
assert_test(strpos($event_forbidden['module_auth'], 'acl_a_extensions_manage') === false, 'Forbidden extension does not include acl_a_extensions_manage');

// Navigation row hiding for forbidden extension
$event_mod_row = new \phpbb\event\data([
    'row' => ['module_basename' => '\\booskit\\awards\\acp\\awards'],
    'module_row' => ['display' => 1]
]);
$listener_with_mgr->hide_mod_logs_tab($event_mod_row);
assert_test($event_mod_row['module_row']['display'] === 0, 'Hides unauthorized extension module from navigation');

// Direct URL restriction
set_error_handler(function($errno, $errstr) {
    throw new \Exception($errstr);
});
$request->vars['i'] = '-booskit-awards-acp-awards';
$caught = false;
try {
    $listener_with_mgr->restrict_acp_extension_access(new \phpbb\event\data());
} catch (\Exception $e) {
    $caught = true;
    assert_test($e->getMessage() === 'NOT_AUTHORISED', 'Denied direct ACP URL access to forbidden extension');
}
assert_test($caught, 'Exception thrown on direct forbidden extension ACP access');

restore_error_handler();

echo "\nAll tests passed successfully!\n";
}
