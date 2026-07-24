<?php
/**
 * Test Suite for booskit/phpbbapi phpBB Extension
 */

namespace phpbb\extension {
    class base {}
}

namespace phpbb\db\migration {
    class migration {}
}

namespace phpbb\config {
    class config extends \ArrayObject {
        public function __construct(array $array = []) { parent::__construct($array, \ArrayObject::ARRAY_AS_PROPS); }
        #[\ReturnTypeWillChange]
        public function offsetGet($key) { return isset($this[$key]) ? parent::offsetGet($key) : null; }
    }
}

namespace phpbb {
    class user {}
}

namespace phpbb\request {
    interface request_interface {}
    class request implements request_interface {
        public $headers = [];
        public $vars = [];
        public function header($name) { return isset($this->headers[$name]) ? $this->headers[$name] : null; }
        public function variable($name, $default) { return isset($this->vars[$name]) ? $this->vars[$name] : $default; }
    }
}

namespace phpbb\db\driver {
    interface driver_interface {}
    class driver implements driver_interface {
        public $rows = [];
        public function sql_escape($str) { return addslashes($str); }
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

$ext_dir = __DIR__ . '/../booskit/phpbbapi';

require_once $ext_dir . '/controller/api.php';

use booskit\phpbbapi\controller\api;

echo "=================================================\n";
echo " Running Unit Test Suite for booskit/phpbbapi   \n";
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

// Subclass api controller to override json() method so test runner doesn't exit
class testable_api extends api {
    public $last_json = null;
    public $last_status = null;
    protected function json($data, $status = 200) {
        $this->last_json = $data;
        $this->last_status = $status;
        return $data;
    }
    public function test_require_key() {
        return $this->require_key();
    }
}

$config = new \phpbb\config\config([
    'booskit_phpbbapi_key' => 'secret_key_12345'
]);
$request = new \phpbb\request\request();
$db = new \phpbb\db\driver\driver();
$user = new \phpbb\user();

// 1. Invalid API Key Test
$request->headers['X-API-Key'] = 'wrong_key';
$api = new testable_api($config, $db, $request, $user, 'phpbb_');
$res = $api->test_require_key();
assert_test($api->last_status === 403, 'Returns 403 Forbidden for invalid API key in header');

// 2. Valid API Key Test (X-API-Key header)
$request->headers['X-API-Key'] = 'secret_key_12345';
$api = new testable_api($config, $db, $request, $user, 'phpbb_');
$res = $api->test_require_key();
assert_test($res === null, 'Passes authorization check (returns null) for valid X-API-Key header');

// 3. Valid API Key Test (Query parameter fallback)
unset($request->headers['X-API-Key']);
$request->vars['key'] = 'secret_key_12345';
$api = new testable_api($config, $db, $request, $user, 'phpbb_');
$res = $api->test_require_key();
assert_test($res === null, 'Passes authorization check for valid query parameter key');

echo "\n-------------------------------------------------\n";
echo " Test Results: $passed Passed, $failed Failed.\n";
echo "-------------------------------------------------\n";

exit($failed === 0 ? 0 : 1);
}
