<?php
/**
 * Test Suite for booskit/extendedpermissions phpBB Extension
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
    class config {}
}

namespace phpbb\auth {
    class auth {
        public $permissions = [];
        public function acl_get($opt, $forum_id = 0) {
            return !empty($this->permissions[$opt]);
        }
    }
}

namespace phpbb {
    class user {
        public function add_lang_ext($ext, $file) {}
    }
}

namespace phpbb\event {
    class data extends \ArrayObject {}
}

namespace phpbb\db\driver {
    interface driver_interface {}
    class driver implements driver_interface {
        public function sql_query($sql) { return new DummyResult(); }
        public function sql_freeresult($res) {}
        public function sql_fetchrow($res) { return false; }
    }
    class DummyResult {}
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

echo "Running tests for booskit/extendedpermissions...\n\n";

// Test 1: Event listener subscriptions
$subscribed = \booskit\extendedpermissions\event\main_listener::getSubscribedEvents();
assert_test(isset($subscribed['core.permissions']), 'Subscribes to core.permissions');
assert_test(isset($subscribed['core.module_auth']), 'Subscribes to core.module_auth');
assert_test(isset($subscribed['core.modify_module_row']), 'Subscribes to core.modify_module_row');
assert_test(isset($subscribed['core.mcp_global_f_read_auth_after']), 'Subscribes to core.mcp_global_f_read_auth_after');
assert_test(isset($subscribed['core.page_header']), 'Subscribes to core.page_header');

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
foreach ($update_data as $entry) {
    if ($entry[0] === 'permission.add') {
        if ($entry[1][0] === 'm_mod_logs') $has_m_mod_logs = true;
        if ($entry[1][0] === 'm_last_actions') $has_m_last_actions = true;
    }
}
assert_test($has_m_mod_logs, 'Migration update_data adds m_mod_logs permission');
assert_test($has_m_last_actions, 'Migration update_data adds m_last_actions permission');

echo "\nAll tests passed successfully!\n";
}
