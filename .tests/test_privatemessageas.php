<?php
/**
 * Test Suite for booskit/privatemessageas phpBB Extension
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

namespace phpbb {
    class user {
        public $data = ['user_id' => 10];
        public function add_lang_ext($ext, $file) {}
    }
}

namespace phpbb\request {
    class request {}
}

namespace phpbb\template {
    class template {}
}

namespace phpbb\db\driver {
    interface driver_interface {}
    class driver implements driver_interface {
        public function sql_escape($str) { return addslashes($str); }
        public function sql_query($sql) { return new DummyResult(); }
        public function sql_fetchrow($res) { return false; }
        public function sql_freeresult($res) {}
    }
    class DummyResult {}
}

namespace {

if (!defined('IN_PHPBB')) {
    define('IN_PHPBB', true);
}

$ext_dir = __DIR__ . '/../booskit/privatemessageas';

require_once $ext_dir . '/event/listener.php';

use booskit\privatemessageas\event\listener;

echo "=================================================\n";
echo " Running Unit Test Suite for booskit/privatemessageas\n";
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
assert_test(isset($subscribed['core.ucp_pm_compose_template']), 'Subscribes to core.ucp_pm_compose_template');
assert_test(isset($subscribed['core.submit_pm_after']), 'Subscribes to core.submit_pm_after');
assert_test(isset($subscribed['core.ucp_pm_view_message']), 'Subscribes to core.ucp_pm_view_message');

echo "\n-------------------------------------------------\n";
echo " Test Results: $passed Passed, $failed Failed.\n";
echo "-------------------------------------------------\n";

exit($failed === 0 ? 0 : 1);
}
