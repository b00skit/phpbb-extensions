<?php
/**
 * Test Suite for booskit/forms phpBB Extension
 */

namespace phpbb\extension {
    class base {}
}

namespace phpbb\db\migration {
    class migration {}
}

namespace phpbb {
    class user {
        public $data = ['user_id' => 2];
    }
}

namespace phpbb\db\driver {
    interface driver_interface {}
    class driver implements driver_interface {
        public $rows = [];
        public function sql_escape($str) { return addslashes($str); }
        public function sql_in_set($field, $array) { return "$field IN (" . implode(',', array_map('intval', $array)) . ")"; }
        public function sql_query($sql) { return new DummyResult($this->rows); }
        public function sql_fetchrow($res) {
            if ($res instanceof DummyResult && !empty($res->rows)) {
                return array_shift($res->rows);
            }
            return false;
        }
        public function sql_freeresult($res) {}
        public function sql_nextid() { return 1; }
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
if (!defined('USER_GROUP_TABLE')) {
    define('USER_GROUP_TABLE', 'phpbb_user_group');
}

$ext_dir = __DIR__ . '/../booskit/forms';

require_once $ext_dir . '/service/form_manager.php';

use booskit\forms\service\form_manager;

echo "=================================================\n";
echo " Running Unit Test Suite for booskit/forms       \n";
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

$db = new \phpbb\db\driver\driver();
$user = new \phpbb\user();
$manager = new form_manager($db, $user, 'phpbb_forms', 'phpbb_form_fields');

// 1. Unrestricted Form Access Check
assert_test($manager->check_access(123, '') === true, 'Empty group_ids_str allows access to all users');

// 2. Restricted Form Access Check - User in allowed group
$db->rows = [['group_id' => 10]];
assert_test($manager->check_access(123, '10,20') === true, 'User in group 10 is granted access to form restricted to 10,20');

// 3. Restricted Form Access Check - User NOT in allowed group
$manager = new form_manager($db, $user, 'phpbb_forms', 'phpbb_form_fields');
$db->rows = [];
assert_test($manager->check_access(123, '10,20') === false, 'User outside groups 10,20 is denied access');

echo "\n-------------------------------------------------\n";
echo " Test Results: $passed Passed, $failed Failed.\n";
echo "-------------------------------------------------\n";

exit($failed === 0 ? 0 : 1);
}
