<?php
/**
 * Test Suite for booskit/select2 phpBB Extension
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
    class user {}
    class path_helper {}
}

namespace phpbb\template {
    class template {
        public $vars = [];
        public function assign_vars($ary) { $this->vars = array_merge($this->vars, $ary); }
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
if (!defined('RANKS_TABLE')) {
    define('RANKS_TABLE', 'phpbb_ranks');
}

function generate_board_url() {
    return 'http://example.com/forum';
}

$ext_dir = __DIR__ . '/../booskit/select2';

require_once $ext_dir . '/event/listener.php';

use booskit\select2\event\listener;

echo "=================================================\n";
echo " Running Unit Test Suite for booskit/select2     \n";
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
assert_test(isset($subscribed['core.page_header']), 'Subscribes to core.page_header');
assert_test(isset($subscribed['core.acp_page_header']), 'Subscribes to core.acp_page_header');

// 2. Test Template Injection Data
$template = new \phpbb\template\template();
$user = new \phpbb\user();
$db = new \phpbb\db\driver\driver();
$db->rows = [
    ['rank_id' => 1, 'rank_title' => 'Admin', 'rank_image' => 'admin.png', 'rank_special' => 1]
];
$config = new \phpbb\config\config(['ranks_path' => 'images/ranks']);
$path_helper = new \phpbb\path_helper();

$listener = new listener($template, $user, $db, $config, $path_helper);
$listener->inject_select2_data([]);

assert_test(isset($template->vars['SELECT2_RANKS_JSON']), 'Injects SELECT2_RANKS_JSON into template');
assert_test(strpos($template->vars['SELECT2_RANKS_JSON'], 'Admin') !== false, 'JSON output contains rank title Admin');
assert_test($template->vars['SELECT2_RANKS_BASE_URL'] === 'http://example.com/forum/images/ranks/', 'Formats ranks_base_url correctly');

echo "\n-------------------------------------------------\n";
echo " Test Results: $passed Passed, $failed Failed.\n";
echo "-------------------------------------------------\n";

exit($failed === 0 ? 0 : 1);
}
