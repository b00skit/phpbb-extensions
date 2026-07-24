<?php
/**
 * Test Suite for booskit/gtawtracker phpBB Extension
 */

namespace phpbb\extension {
    class base {}
}

namespace phpbb\db\migration {
    class migration {}
}

namespace Symfony\Component\HttpFoundation {
    class JsonResponse {
        public $data;
        public $status;
        public function __construct($data = null, $status = 200) {
            $this->data = $data;
            $this->status = $status;
        }
        public function getStatusCode() { return $this->status; }
        public function getData() { return $this->data; }
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
        public $data = ['user_id' => 10, 'user_type' => 0];
    }
}

namespace phpbb\language {
    class language {
        public function add_lang() {}
        public function lang($key) { return $key; }
    }
}

namespace phpbb\db\driver {
    interface driver_interface {}
    class driver implements driver_interface {
        public $rows = [];
        public function sql_in_set($field, $array) { return "$field IN (" . implode(',', array_map('intval', $array)) . ")"; }
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
if (!defined('USER_FOUNDER')) {
    define('USER_FOUNDER', 3);
}
if (!defined('USER_GROUP_TABLE')) {
    define('USER_GROUP_TABLE', 'phpbb_user_group');
}

$ext_dir = __DIR__ . '/../booskit/gtawtracker';

require_once $ext_dir . '/controller/ajax.php';

use booskit\gtawtracker\controller\ajax;

echo "=================================================\n";
echo " Running Unit Test Suite for booskit/gtawtracker \n";
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
    'booskit_gtawtracker_faction_id' => 10,
    'booskit_gtawtracker_view_groups' => '50,60'
]);
$request = null;
$user = new \phpbb\user();
$template = null;
$provider = null;
$db = new \phpbb\db\driver\driver();
$language = new \phpbb\language\language();

$controller = new ajax($config, $request, $user, $template, $provider, $db, 'phpbb_', $language);

// 1. Permission Denied Test (Regular User not in view groups)
$db->rows = [];
$res = $controller->fetch_data(999);
assert_test($res->getStatusCode() === 403 && $res->getData()['error'] === 'GTAW_TRACKER_NO_ACCESS', 'Denies access (GTAW_TRACKER_NO_ACCESS) to user outside allowed view groups');

// 2. Permission Granted for Founder (check_permissions passes)
$user_founder = new \phpbb\user();
$user_founder->data['user_type'] = USER_FOUNDER;
$controller_founder = new ajax($config, $request, $user_founder, $template, $provider, $db, 'phpbb_', $language);
$res_founder = $controller_founder->fetch_data(999);
assert_test($res_founder->getData()['error'] === 'GTAW_TRACKER_NO_LINK', 'Founder passes permission check and proceeds to OAuth token check (GTAW_TRACKER_NO_LINK)');

echo "\n-------------------------------------------------\n";
echo " Test Results: $passed Passed, $failed Failed.\n";
echo "-------------------------------------------------\n";

exit($failed === 0 ? 0 : 1);
}
