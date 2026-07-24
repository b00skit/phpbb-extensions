<?php
/**
 * Test Suite for booskit/threadprefixes phpBB Extension
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

namespace phpbb\auth {
    class auth {
        public $permissions = [];
        public function acl_get($opt, $forum_id = 0) { return !empty($this->permissions[$opt]); }
    }
}

namespace phpbb {
    class user {}
}

namespace phpbb\db\driver {
    interface driver_interface {}
    class driver implements driver_interface {
        public $rows = [];
        public function sql_query($sql) { return new DummyResult($this->rows); }
        public function sql_fetchrow($res) {
            if ($res instanceof DummyResult && !empty($res->rows)) {
                return array_shift($res->rows);
            }
            return false;
        }
        public function sql_freeresult($res) {}
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

$ext_dir = __DIR__ . '/../booskit/threadprefixes';

require_once $ext_dir . '/event/listener.php';
require_once $ext_dir . '/service/prefix_manager.php';

use booskit\threadprefixes\event\listener;
use booskit\threadprefixes\service\prefix_manager;

echo "=================================================\n";
echo " Running Unit Test Suite for booskit/threadprefixes\n";
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
assert_test(isset($subscribed['core.permissions']), 'Subscribes to core.permissions');
assert_test(isset($subscribed['core.posting_modify_template_vars']), 'Subscribes to core.posting_modify_template_vars');
assert_test(isset($subscribed['core.submit_post_end']), 'Subscribes to core.submit_post_end');
assert_test(isset($subscribed['core.viewforum_modify_topicrow']), 'Subscribes to core.viewforum_modify_topicrow');

// 2. Test Tag Forum Restriction Check (is_tag_allowed_for_forum)
$db = new \phpbb\db\driver\driver();
$db->rows = [
    ['tag_id' => 1, 'tag_name' => 'WIP', 'tag_forums' => json_encode([5, 10])]
];
$pm = new prefix_manager($db, 'phpbb_threadprefixes');

assert_test($pm->is_tag_allowed_for_forum(1, 5) === true, 'Tag 1 is allowed for forum 5');
assert_test($pm->is_tag_allowed_for_forum(1, 99) === false, 'Tag 1 is not allowed for forum 99');

echo "\n-------------------------------------------------\n";
echo " Test Results: $passed Passed, $failed Failed.\n";
echo "-------------------------------------------------\n";

exit($failed === 0 ? 0 : 1);
}
