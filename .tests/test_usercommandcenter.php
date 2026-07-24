<?php
/**
 * Test Suite for booskit/usercommandcenter phpBB Extension
 */

namespace phpbb\extension {
    class base {}
    class manager {
        public $enabled = [];
        public function is_enabled($name) { return !empty($this->enabled[$name]); }
    }
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

$ext_dir = __DIR__ . '/../booskit/usercommandcenter';

require_once $ext_dir . '/service/ucc_manager.php';

use booskit\usercommandcenter\service\ucc_manager;

echo "=================================================\n";
echo " Running Unit Test Suite for booskit/usercommandcenter\n";
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
    'booskit_ucc_allowed_groups' => '5, 10, 15',
    'booskit_ucc_include_awards' => 1
]);
$db = new \phpbb\db\driver\driver();
$ext_mgr = new \phpbb\extension\manager();
$ext_mgr->enabled['booskit/awards'] = true;

$ucc = new ucc_manager($config, $db, $ext_mgr, null, 'phpbb_');

// 1. Test Extension Enabled Check
assert_test($ucc->is_ext_enabled('booskit/awards') === true, 'Recognizes booskit/awards as enabled');
assert_test($ucc->is_ext_enabled('booskit/commendations') === false, 'Recognizes booskit/commendations as disabled');

// 2. Test Allowed Groups Parsing
$allowed = $ucc->get_allowed_groups();
assert_test($allowed === [5, 10, 15], 'Parses allowed group IDs into integer array');

echo "\n-------------------------------------------------\n";
echo " Test Results: $passed Passed, $failed Failed.\n";
echo "-------------------------------------------------\n";

exit($failed === 0 ? 0 : 1);
}
