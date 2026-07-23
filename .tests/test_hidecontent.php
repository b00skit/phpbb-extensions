<?php
/**
 * Test Suite for booskit/hidecontent phpBB Extension
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
        public function acl_getf($opt, $clean = false) {
            return !empty($this->permissions[$opt]) ? [1 => true] : [];
        }
    }
}

namespace phpbb {
    class user {
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
        public $hidden_post_ids = [];
        public function sql_in_set($field, $array) { return "$field IN (" . implode(',', $array) . ")"; }
        public function sql_query($sql) { return new DummyResult($this->hidden_post_ids); }
        public function sql_query_limit($sql, $total) { return new DummyResult($this->hidden_post_ids); }
        public function sql_fetchrow($res) {
            if (!empty($res->ids)) {
                $id = array_shift($res->ids);
                return ['post_id' => $id, 'topic_id' => 10, 'post_hidden' => 1];
            }
            return false;
        }
        public function sql_freeresult($res) {}
        public function sql_fetchfield($field) { return 0; }
    }
    class DummyResult {
        public $ids;
        public function __construct($ids = []) { $this->ids = $ids; }
    }
}

namespace phpbb\request {
    class request {}
}

namespace phpbb\template {
    class template {
        public $vars = [];
        public function assign_vars($ary) { $this->vars = array_merge($this->vars, $ary); }
    }
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
if (!defined('USERS_TABLE')) {
    define('USERS_TABLE', 'phpbb_users');
}
if (!defined('ITEM_APPROVED')) {
    define('ITEM_APPROVED', 1);
}
if (!defined('ANONYMOUS')) {
    define('ANONYMOUS', 1);
}

$ext_dir = dirname(__DIR__) . '/booskit/hidecontent';

require_once $ext_dir . '/ext.php';
require_once $ext_dir . '/event/listener.php';
require_once $ext_dir . '/controller/main.php';
require_once $ext_dir . '/migrations/v100_initial.php';

use booskit\hidecontent\event\listener;

echo "=================================================\n";
echo " Running Unit Test Suite for booskit/hidecontent \n";
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
assert_test(isset($subscribed['core.viewtopic_get_post_data']), 'Subscribes to core.viewtopic_get_post_data');
assert_test(isset($subscribed['core.viewforum_get_topic_ids_data']), 'Subscribes to core.viewforum_get_topic_ids_data');
assert_test(isset($subscribed['core.display_forums_before']), 'Subscribes to core.display_forums_before');
assert_test(isset($subscribed['core.search_get_posts_data']), 'Subscribes to core.search_get_posts_data');
assert_test(isset($subscribed['core.search_get_topic_data']), 'Subscribes to core.search_get_topic_data');

// 2. Test Permission Registration
$event = new \phpbb\event\data(['permissions' => []]);
$listener = new listener(
    new \phpbb\config\config(),
    new \phpbb\auth\auth(),
    new \phpbb\user(),
    new \phpbb\db\driver\driver(),
    new \phpbb\request\request(),
    new \phpbb\template\template(),
    './',
    'php'
);

$listener->add_permissions($event);
assert_test(isset($event['permissions']['m_hide']), 'Registers m_hide permission');
assert_test(isset($event['permissions']['m_view_hidden']), 'Registers m_view_hidden permission');
assert_test($event['permissions']['m_hide']['cat'] === 'post_actions', 'm_hide category is post_actions');

// 3. Test Search SQL Filters for Users without m_view_hidden
$auth_no_mod = new \phpbb\auth\auth();
$auth_no_mod->permissions['m_view_hidden'] = false;
$listener_no_mod = new listener(
    new \phpbb\config\config(),
    $auth_no_mod,
    new \phpbb\user(),
    new \phpbb\db\driver\driver(),
    new \phpbb\request\request(),
    new \phpbb\template\template(),
    './',
    'php'
);

$search_event = new \phpbb\event\data(['sql_array' => ['WHERE' => 'WHERE 1=1']]);
$listener_no_mod->filter_search_posts($search_event);
assert_test(strpos($search_event['sql_array']['WHERE'], 'p.post_hidden = 0 AND t.topic_hidden = 0') !== false, 'Search posts query filters hidden posts/topics for regular users');

$search_topic_event = new \phpbb\event\data(['sql_where' => 'WHERE 1=1']);
$listener_no_mod->filter_search_topics($search_topic_event);
assert_test(strpos($search_topic_event['sql_where'], 't.topic_hidden = 0') !== false, 'Search topics query filters hidden topics for regular users');

// 4. Test Search SQL Filters for Users WITH m_view_hidden
$auth_mod = new \phpbb\auth\auth();
$auth_mod->permissions['m_view_hidden'] = true;
$listener_mod = new listener(
    new \phpbb\config\config(),
    $auth_mod,
    new \phpbb\user(),
    new \phpbb\db\driver\driver(),
    new \phpbb\request\request(),
    new \phpbb\template\template(),
    './',
    'php'
);

$search_event_mod = new \phpbb\event\data(['sql_array' => ['WHERE' => 'WHERE 1=1']]);
$listener_mod->filter_search_posts($search_event_mod);
assert_test(strpos($search_event_mod['sql_array']['WHERE'], 'f.forum_id IN (1)') !== false, 'Search posts query allows hidden content for m_view_hidden moderators');

// 5. Test Viewtopic Post Filtering for Users without m_view_hidden
$db_with_hidden = new \phpbb\db\driver\driver();
$db_with_hidden->hidden_post_ids = [20]; // Post ID 20 is hidden
$listener_viewtopic = new listener(
    new \phpbb\config\config(),
    $auth_no_mod,
    new \phpbb\user(),
    $db_with_hidden,
    new \phpbb\request\request(),
    new \phpbb\template\template(),
    './',
    'php'
);

$vt_event = new \phpbb\event\data([
    'topic_data' => ['topic_hidden' => 0],
    'forum_id' => 1,
    'post_list' => [10, 20, 30]
]);
$listener_viewtopic->check_viewtopic_access($vt_event);
assert_test($vt_event['post_list'] === [10, 30], 'Viewtopic filters out post 20 which is hidden');

// 6. Test Viewforum Topic Row Marking for Moderators
$vt_row_event = new \phpbb\event\data([
    'row' => ['forum_id' => 1, 'topic_id' => 5, 'topic_hidden' => 1, 'topic_last_post_id' => 100],
    'topic_row' => []
]);
$listener_mod->modify_viewforum_topicrow($vt_row_event);
assert_test(!empty($vt_row_event['topic_row']['S_TOPIC_HIDDEN']), 'Viewforum marks topic_row S_TOPIC_HIDDEN for moderators');

echo "\n-------------------------------------------------\n";
echo " Test Results: $passed Passed, $failed Failed.\n";
echo "-------------------------------------------------\n";

exit($failed === 0 ? 0 : 1);
}
