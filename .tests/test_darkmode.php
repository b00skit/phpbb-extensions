<?php
/**
 * Test Suite for booskit/darkmode phpBB Extension
 */

namespace phpbb\extension {
    if (!class_exists('phpbb\\extension\\base', false)) {
        class base {}
    }
}

namespace phpbb\template {
    if (!interface_exists('phpbb\\template\\template', false)) {
        interface template {
            public function assign_vars(array $vars);
        }
    }
}

namespace phpbb\request {
    if (!interface_exists('phpbb\\request\\request_interface', false)) {
        interface request_interface {
            const COOKIE = 2;
            public function variable($var_name, $default, $multibyte = false, $super_global = \phpbb\request\request_interface::COOKIE);
        }
    }
}

namespace phpbb\config {
    if (!class_exists('phpbb\\config\\config', false)) {
        class config extends \ArrayObject {
            public function __construct(array $array = []) { parent::__construct($array, \ArrayObject::ARRAY_AS_PROPS); }
            #[\ReturnTypeWillChange]
            public function offsetGet($key) { return isset($this[$key]) ? parent::offsetGet($key) : null; }
        }
    }
}

namespace phpbb {
    if (!class_exists('phpbb\\user', false)) {
        class user {
            public function add_lang_ext($ext, $file) {}
            public function lang($key) { return $key; }
        }
    }
}

namespace Symfony\Component\EventDispatcher {
    if (!interface_exists('Symfony\\Component\\EventDispatcher\\EventSubscriberInterface', false)) {
        interface EventSubscriberInterface {
            public static function getSubscribedEvents();
        }
    }
}

namespace {
    $root_dir = dirname(__DIR__);
    $ext_dir = $root_dir . '/booskit/darkmode';
    $tests_passed = 0;
    $tests_total = 0;

    function assert_true($condition, $message) {
        global $tests_passed, $tests_total;
        $tests_total++;
        if ($condition) {
            $tests_passed++;
            echo "  [PASS] {$message}\n";
        } else {
            echo "  [FAIL] {$message}\n";
        }
    }

    echo "=== Running Booskit Dark Mode Extension Tests ===\n\n";

    // 1. Validate composer.json
    echo "1. Validating composer.json\n";
    $composer_file = $ext_dir . '/composer.json';
    assert_true(file_exists($composer_file), 'composer.json exists');
    $composer = json_decode(file_get_contents($composer_file), true);
    assert_true($composer !== null, 'composer.json is valid JSON');
    assert_true(isset($composer['name']) && $composer['name'] === 'booskit/darkmode', 'Extension name is booskit/darkmode');
    assert_true(isset($composer['type']) && $composer['type'] === 'phpbb-extension', 'Type is phpbb-extension');
    assert_true(isset($composer['extra']['soft-require']['phpbb/phpbb']), 'soft-require contains phpbb/phpbb');

    // 2. Validate ext.php
    echo "\n2. Validating ext.php\n";
    $ext_file = $ext_dir . '/ext.php';
    assert_true(file_exists($ext_file), 'ext.php exists');
    require_once $ext_file;
    assert_true(class_exists('booskit\\darkmode\\ext'), 'Class booskit\\darkmode\\ext exists');

    // 3. Validate services.yml
    echo "\n3. Validating services.yml\n";
    $services_file = $ext_dir . '/config/services.yml';
    assert_true(file_exists($services_file), 'services.yml exists');
    $services_content = file_get_contents($services_file);
    assert_true(strpos($services_content, 'booskit.darkmode.listener') !== false, 'Listener service declared');
    assert_true(strpos($services_content, 'event.listener') !== false, 'event.listener tag declared');
    assert_true(strpos($services_content, "'@request'") !== false, 'services.yml injects @request');
    assert_true(strpos($services_content, "'@config'") !== false, 'services.yml injects @config');

    // 4. Validate Language file
    echo "\n4. Validating Language definitions\n";
    if (!defined('IN_PHPBB')) {
        define('IN_PHPBB', true);
    }
    $lang_file = $ext_dir . '/language/en/darkmode.php';
    assert_true(file_exists($lang_file), 'language/en/darkmode.php exists');
    $lang = [];
    require $lang_file;
    assert_true(isset($lang['DARK_MODE_ENABLE']), 'DARK_MODE_ENABLE translation exists');
    assert_true(isset($lang['DARK_MODE_DISABLE']), 'DARK_MODE_DISABLE translation exists');
    assert_true(isset($lang['DARK_MODE_TOGGLE']), 'DARK_MODE_TOGGLE translation exists');

    // 5. Validate Event Listener & Request Class
    echo "\n5. Validating Event Listener & Request Class\n";
    $listener_file = $ext_dir . '/event/listener.php';
    assert_true(file_exists($listener_file), 'listener.php exists');
    $listener_code = file_get_contents($listener_file);
    assert_true(strpos($listener_code, '$_COOKIE') === false, 'listener.php contains NO $_COOKIE superglobal');
    assert_true(strpos($listener_code, 'request_interface::COOKIE') !== false, 'listener.php uses request_interface::COOKIE');

    require_once $listener_file;
    assert_true(class_exists('booskit\\darkmode\\event\\listener'), 'Class booskit\\darkmode\\event\\listener exists');

    $events = booskit\darkmode\event\listener::getSubscribedEvents();
    assert_true(isset($events['core.page_header']), 'Subscribed to core.page_header');

    // Mock template, user, request, config
    class MockTemplate implements phpbb\template\template {
        public $vars = [];
        public function assign_vars(array $vars) {
            $this->vars = array_merge($this->vars, $vars);
        }
    }

    class MockUser extends phpbb\user {
        public $loaded_langs = [];
        public function add_lang_ext($ext, $file) {
            $this->loaded_langs[] = "{$ext}/{$file}";
        }
        public function lang($key) {
            global $lang;
            return isset($lang[$key]) ? $lang[$key] : $key;
        }
    }

    class MockRequest implements phpbb\request\request_interface {
        public $cookies = [];
        public function variable($var_name, $default, $multibyte = false, $super_global = \phpbb\request\request_interface::COOKIE) {
            if ($super_global === \phpbb\request\request_interface::COOKIE) {
                return isset($this->cookies[$var_name]) ? $this->cookies[$var_name] : $default;
            }
            return $default;
        }
    }

    $mock_template = new MockTemplate();
    $mock_user = new MockUser();
    $mock_request = new MockRequest();
    $mock_config = new phpbb\config\config(['cookie_name' => 'phpbb3_test']);

    $listener = new booskit\darkmode\event\listener($mock_template, $mock_user, $mock_request, $mock_config);

    // Simulate page header when dark mode cookie is NOT set
    $mock_request->cookies = [];
    $listener->on_page_header(new \stdClass());
    assert_true($mock_template->vars['S_DARK_MODE_ENABLED'] === false, 'S_DARK_MODE_ENABLED is false when cookie not set');
    assert_true($mock_template->vars['L_DARK_MODE_ENABLE'] === 'Enable dark mode', 'L_DARK_MODE_ENABLE correctly assigned');
    assert_true($mock_template->vars['L_DARK_MODE_DISABLE'] === 'Disable dark mode', 'L_DARK_MODE_DISABLE correctly assigned');

    // Simulate page header when prefixed cookie IS set
    $mock_request->cookies['phpbb3_test_booskit_darkmode'] = '1';
    $listener->on_page_header(new \stdClass());
    assert_true($mock_template->vars['S_DARK_MODE_ENABLED'] === true, 'S_DARK_MODE_ENABLED is true when prefixed cookie is 1');

    // 6. Validate Template Event Files
    echo "\n6. Validating Template Events\n";
    $template_events = [
        'overall_header_head_append.html' => ['@booskit_darkmode/darkmode.css', 'data-theme'],
        'overall_footer_after.html' => ['@booskit_darkmode/darkmode.js'],
        'navbar_header_profile_list_after.html' => ['darkmode-toggle-user', 'darkmode-icon'],
        'navbar_header_logged_out_content.html' => ['darkmode-toggle-guest', 'darkmode-guest-container'],
    ];

    foreach ($template_events as $file => $expected_strings) {
        $full_path = $ext_dir . '/styles/all/template/event/' . $file;
        assert_true(file_exists($full_path), "Template event file exists: {$file}");
        $content = file_get_contents($full_path);
        foreach ($expected_strings as $str) {
            assert_true(strpos($content, $str) !== false, "{$file} contains '{$str}'");
        }
    }

    // 7. Validate Assets (CSS & JS)
    echo "\n7. Validating Color-Preserving Smart Theme Assets\n";
    $css_file = $ext_dir . '/styles/all/theme/darkmode.css';
    assert_true(file_exists($css_file), 'darkmode.css exists');
    $css_content = file_get_contents($css_file);
    assert_true(strpos($css_content, 'color-scheme: dark') !== false, 'darkmode.css sets color-scheme: dark');
    assert_true(strpos($css_content, 'filter: invert') === false, 'darkmode.css has NO root filter inversion');

    $js_file = $ext_dir . '/styles/all/theme/darkmode.js';
    assert_true(file_exists($js_file), 'darkmode.js exists');
    $js_content = file_get_contents($js_file);
    assert_true(strpos($js_content, 'rgbToHsl') !== false, 'darkmode.js has rgbToHsl conversion');
    assert_true(strpos($js_content, 'hslToRgb') !== false, 'darkmode.js has hslToRgb conversion');
    assert_true(strpos($js_content, 'getRelativeLuminance') !== false, 'darkmode.js has getRelativeLuminance analyzer');
    assert_true(strpos($js_content, 'getContrastRatio') !== false, 'darkmode.js has getContrastRatio WCAG calculator');
    assert_true(strpos($js_content, 'ensureElementsInserted') !== false, 'darkmode.js includes dynamic fallback injector');

    echo "\n=== Test Summary: {$tests_passed}/{$tests_total} tests passed ===\n";
    if ($tests_passed === $tests_total) {
        echo "ALL TESTS PASSED!\n";
        exit(0);
    } else {
        echo "SOME TESTS FAILED!\n";
        exit(1);
    }
}
