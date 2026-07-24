<?php
/**
 * Test Suite for booskit/topiclogviews phpBB Extension
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
        public $data = ['user_id' => 10, 'is_bot' => false];
        public $ip = '127.0.0.1';
    }
}

namespace phpbb\request {
    interface request_interface {}
    class request implements request_interface {}
}

namespace phpbb\log {
    interface log_interface {}
    class dummy_log implements log_interface {
        public $logs = [];
        public function add($mode, $user_id, $ip, $action, $time = false, $params = array()) {
            $this->logs[] = ['mode' => $mode, 'action' => $action, 'params' => $params];
        }
    }
}

namespace phpbb\event {
    class data extends \ArrayObject {}
}

namespace {

if (!defined('IN_PHPBB')) {
    define('IN_PHPBB', true);
}
if (!defined('ANONYMOUS')) {
    define('ANONYMOUS', 1);
}

$ext_dir = __DIR__ . '/../booskit/topiclogviews';

require_once $ext_dir . '/event/listener.php';

use booskit\topiclogviews\event\listener;

echo "=================================================\n";
echo " Running Unit Test Suite for booskit/topiclogviews\n";
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

// 1. Test Subscribed Events
$subscribed = listener::getSubscribedEvents();
assert_test(isset($subscribed['core.user_setup']), 'Subscribes to core.user_setup');
assert_test(isset($subscribed['core.viewtopic_assign_template_vars_before']), 'Subscribes to core.viewtopic_assign_template_vars_before');

// 2. Test Logging Behavior when extension disabled vs enabled
$config_disabled = new \phpbb\config\config(['booskit_topiclogviews_enable' => 0]);
$user = new \phpbb\user();
$log = new \phpbb\log\dummy_log();
$request = new \phpbb\request\request();
$auth = new \phpbb\auth\auth();

$listener_disabled = new listener($config_disabled, $user, $log, $request, $auth);
$event = new \phpbb\event\data(['forum_id' => 1, 'topic_id' => 10, 'topic_data' => ['topic_title' => 'Test Topic']]);
$listener_disabled->log_topic_view($event);
assert_test(count($log->logs) === 0, 'Does not log topic view when booskit_topiclogviews_enable is 0');

// 3. Test Logging Behavior when enabled
$config_enabled = new \phpbb\config\config([
    'booskit_topiclogviews_enable' => 1,
    'booskit_topiclogviews_exclude_bots' => 1,
    'booskit_topiclogviews_log_guests' => 1,
    'booskit_topiclogviews_mod_only' => 0,
    'booskit_topiclogviews_session_dedup' => 0
]);
$log_enabled = new \phpbb\log\dummy_log();
$listener_enabled = new listener($config_enabled, $user, $log_enabled, $request, $auth);
$event_enabled = new \phpbb\event\data(['forum_id' => 1, 'topic_id' => 10, 'topic_data' => ['topic_title' => 'Test Topic']]);
$listener_enabled->log_topic_view($event_enabled);
assert_test(count($log_enabled->logs) === 1, 'Logs topic view when booskit_topiclogviews_enable is 1');
assert_test($log_enabled->logs[0]['action'] === 'LOG_TOPIC_VIEWED', 'Action logged is LOG_TOPIC_VIEWED');

echo "\n-------------------------------------------------\n";
echo " Test Results: $passed Passed, $failed Failed.\n";
echo "-------------------------------------------------\n";

exit($failed === 0 ? 0 : 1);
}
