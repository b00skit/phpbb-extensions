<?php
/**
 * Test Suite for booskit/forumprivacy phpBB Extension
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
        public function acl_getf($opt, $clean = false) { return !empty($this->permissions[$opt]) ? [1 => true] : []; }
    }
}

namespace phpbb {
    class user {
        public $data = ['user_id' => 10];
    }
}

namespace phpbb\request {
    class request {}
}

namespace phpbb\event {
    class data extends \ArrayObject {}
}

namespace phpbb\db\driver {
    interface driver_interface {}
    class driver implements driver_interface {
        public function sql_escape($str) { return addslashes($str); }
        public function sql_in_set($field, $array) { return "$field IN (" . implode(',', array_map('intval', $array)) . ")"; }
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
if (!defined('POSTS_TABLE')) {
    define('POSTS_TABLE', 'phpbb_posts');
}
if (!defined('TOPICS_TABLE')) {
    define('TOPICS_TABLE', 'phpbb_topics');
}
if (!defined('POST_STICKY')) {
    define('POST_STICKY', 1);
}
if (!defined('POST_ANNOUNCE')) {
    define('POST_ANNOUNCE', 2);
}
if (!defined('POST_GLOBAL')) {
    define('POST_GLOBAL', 3);
}

$ext_dir = __DIR__ . '/../booskit/forumprivacy';

require_once $ext_dir . '/event/listener.php';

use booskit\forumprivacy\event\listener;

echo "=================================================\n";
echo " Running Unit Test Suite for booskit/forumprivacy\n";
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
assert_test(isset($subscribed['core.viewforum_get_topic_ids_data']), 'Subscribes to core.viewforum_get_topic_ids_data');
assert_test(isset($subscribed['core.viewtopic_modify_forum_id']), 'Subscribes to core.viewtopic_modify_forum_id');
assert_test(isset($subscribed['core.search_get_posts_data']), 'Subscribes to core.search_get_posts_data');

// 2. Test Custom ACL Registration
$auth = new \phpbb\auth\auth();
$user = new \phpbb\user();
$db = new \phpbb\db\driver\driver();
$request = new \phpbb\request\request();

$listener = new listener($auth, $user, $db, $request, './', 'php');

$event_perm = new \phpbb\event\data(['permissions' => []]);
$listener->add_permissions($event_perm);

assert_test(isset($event_perm['permissions']['f_view_others_topics']), 'Registers f_view_others_topics ACL');
assert_test(isset($event_perm['permissions']['f_post_others_topics']), 'Registers f_post_others_topics ACL');
assert_test(isset($event_perm['permissions']['f_search_others_topics']), 'Registers f_search_others_topics ACL');

// 3. Test Search Posts Filter SQL Injections
$auth->permissions['f_search_others_topics'] = false;
$listener = new listener($auth, $user, $db, $request, './', 'php');

$search_event = new \phpbb\event\data(['sql_array' => ['WHERE' => 'WHERE 1=1']]);
$listener->filter_search_posts($search_event);
assert_test(strpos($search_event['sql_array']['WHERE'], 't.topic_poster = 10') !== false, 'Search posts query restricts topic_poster to current user when f_search_others_topics is false');

echo "\n-------------------------------------------------\n";
echo " Test Results: $passed Passed, $failed Failed.\n";
echo "-------------------------------------------------\n";

exit($failed === 0 ? 0 : 1);
}
