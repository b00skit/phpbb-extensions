<?php
/**
 * Test Suite for booskit/datacollector phpBB Extension
 */

namespace phpbb\extension {
    class base {}
}

namespace phpbb\db\migration {
    class migration {}
}

namespace Symfony\Component\HttpFoundation {
    class Response {
        public $content;
        public $status;
        public function __construct($content = '', $status = 200) {
            $this->content = $content;
            $this->status = $status;
        }
        public function getStatusCode() { return $this->status; }
        public function getContent() { return $this->content; }
    }
    class JsonResponse extends Response {
        public function __construct($data = null, $status = 200) {
            parent::__construct(json_encode($data), $status);
        }
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
        public $data = ['user_id' => 2];
    }
}

namespace phpbb\request {
    class request {
        public $vars = [];
        public function variable($name, $default) { return isset($this->vars[$name]) ? $this->vars[$name] : $default; }
    }
}

namespace phpbb\controller {
    class helper {}
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
if (!defined('ITEM_MOVED')) {
    define('ITEM_MOVED', 2);
}
if (!defined('GROUPS_TABLE')) {
    define('GROUPS_TABLE', 'phpbb_groups');
}
if (!defined('USER_GROUP_TABLE')) {
    define('USER_GROUP_TABLE', 'phpbb_user_group');
}
if (!defined('USERS_TABLE')) {
    define('USERS_TABLE', 'phpbb_users');
}
if (!defined('TOPICS_TABLE')) {
    define('TOPICS_TABLE', 'phpbb_topics');
}

$ext_dir = __DIR__ . '/../booskit/datacollector';

require_once $ext_dir . '/controller/collector.php';

use booskit\datacollector\controller\collector;

echo "=================================================\n";
echo " Running Unit Test Suite for booskit/datacollector\n";
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

$config = new \phpbb\config\config([
    'booskit_datacollector_post_url' => 'https://example.com/api',
    'booskit_datacollector_forum_id' => 5,
    'booskit_datacollector_group_id' => 10,
]);
$request = new \phpbb\request\request();
$db = new \phpbb\db\driver\driver();
$user = new \phpbb\user();
$helper = new \phpbb\controller\helper();
$auth = new \phpbb\auth\auth();

// 1. Access Denied Test
$collector = new collector($config, $request, $db, $user, $helper, $auth);
$response = $collector->send();
assert_test($response->getStatusCode() === 403, 'Returns 403 Access Denied for unauthorized user');

// 2. Access Granted Test for Admin (a_)
$auth->permissions['a_'] = true;
$request->vars['type'] = 'forum';
$response_admin = $collector->send();
assert_test($response_admin->getStatusCode() !== 403, 'Allows access for admin user (a_)');

// 3. Config Missing URL Test
$config_no_url = new \phpbb\config\config(['booskit_datacollector_post_url' => '']);
$collector_no_url = new collector($config_no_url, $request, $db, $user, $helper, $auth);
$response_500 = $collector_no_url->send();
assert_test($response_500->getStatusCode() === 500, 'Returns 500 when post_url is missing');

echo "\n-------------------------------------------------\n";
echo " Test Results: $passed Passed, $failed Failed.\n";
echo "-------------------------------------------------\n";

exit($failed === 0 ? 0 : 1);
}
