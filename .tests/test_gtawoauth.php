<?php
/**
 * Test Suite for booskit/gtawoauth phpBB Extension
 */

namespace phpbb\extension {
    class base {}
}

namespace phpbb\db\migration {
    class migration {}
}

namespace phpbb\auth\provider\oauth\service {
    class base {
        public $redirect_uri;
    }
}

namespace phpbb\config {
    class config extends \ArrayObject {
        public function __construct(array $array = []) { parent::__construct($array, \ArrayObject::ARRAY_AS_PROPS); }
        #[\ReturnTypeWillChange]
        public function offsetGet($key) { return isset($this[$key]) ? parent::offsetGet($key) : null; }
    }
}

namespace phpbb\request {
    interface request_interface {}
    class request implements request_interface {}
}

namespace phpbb\controller {
    class helper {}
}

namespace {

if (!defined('IN_PHPBB')) {
    define('IN_PHPBB', true);
}

$ext_dir = __DIR__ . '/../booskit/gtawoauth';

require_once $ext_dir . '/auth/provider/gtaw.php';

use booskit\gtawoauth\auth\provider\gtaw;

echo "=================================================\n";
echo " Running Unit Test Suite for booskit/gtawoauth   \n";
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

// 1. Test OAuth Provider Redirect URI Generation
$config = new \phpbb\config\config([
    'auth_oauth_gtaw_base_url' => 'https://forum.example.com'
]);
$request = new \phpbb\request\request();
$helper = new \phpbb\controller\helper();

$provider = new gtaw($config, $request, $helper);
$redirect_uri = $provider->get_redirect_uri();

assert_test($redirect_uri === 'https://forum.example.com/app.php/gtaw/callback', 'Generates correct callback URL from configured base URL');

// 2. Custom Redirect URI override test
$provider->set_redirect_uri('https://custom.example.com/callback');
assert_test($provider->get_redirect_uri() === 'https://custom.example.com/callback', 'Allows setting custom redirect URI override');

echo "\n-------------------------------------------------\n";
echo " Test Results: $passed Passed, $failed Failed.\n";
echo "-------------------------------------------------\n";

exit($failed === 0 ? 0 : 1);
}
