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

// 4. Webhook Template Rendering with Special Characters
$custom_tpl = '{
    "content": "User {{USERNAME}} submitted form {{FORM_NAME}}",
    "note": "{{NOTE}}",
    "form_id": {{FORM_ID}},
    "fields": {{DISCORD_FIELDS_JSON}}
}';
$vars = [
    'USERNAME' => 'Jane "The Admin" Doe',
    'FORM_NAME' => 'Feedback "2026"',
    'NOTE' => "Line 1 with quotes \"here\"\nLine 2 with \\ backslash",
    'FORM_ID' => 42,
    'DISCORD_FIELDS_JSON' => json_encode([
        ['name' => 'Q1', 'value' => 'Answer 1', 'inline' => true]
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
];
$rendered = $manager->render_webhook_template($custom_tpl, $vars);
$decoded = json_decode($rendered, true);
assert_test($decoded !== null, 'Webhook template renders into 100% valid JSON with quotes and newlines');
assert_test($decoded['note'] === "Line 1 with quotes \"here\"\nLine 2 with \\ backslash", 'Webhook template properly unescapes multiline string with quotes');
assert_test($decoded['form_id'] === 42, 'Numeric placeholder rendered correctly');
assert_test(count($decoded['fields']) === 1 && $decoded['fields'][0]['name'] === 'Q1', 'DISCORD_FIELDS_JSON injected into JSON structure');

// 5. Webhook Template with Loop
$loop_tpl = '{
    "embeds": [{
        "title": "{{FORM_NAME}}",
        "fields": [
            {{#fields}}
            {
                "name": "{{label}}",
                "value": "{{value}}"
            }
            {{/fields}}
        ]
    }]
}';
$test_fields = [
    ['field_name' => 'name', 'field_label' => 'Full Name', 'field_type' => 'text'],
    ['field_name' => 'comments', 'field_label' => 'Comments', 'field_type' => 'textarea'],
];
$vars['name'] = 'Alice "Wonder"';
$vars['comments'] = "Multiline\nComment";
$rendered_loop = $manager->render_webhook_template($loop_tpl, $vars, $test_fields);
$decoded_loop = json_decode($rendered_loop, true);
assert_test($decoded_loop !== null, 'Webhook template loop {{#fields}} renders valid JSON without trailing commas');
assert_test(count($decoded_loop['embeds'][0]['fields']) === 2, 'Loop produced 2 field objects');
assert_test($decoded_loop['embeds'][0]['fields'][0]['name'] === 'Full Name', 'Loop field label correct');

// 6. Default Webhook Payloads (Discord vs Generic)
$form_record = ['form_id' => 7, 'form_name' => 'Bug Report', 'form_slug' => 'bug-report'];
$discord_payload_str = $manager->build_default_webhook_payload('https://discord.com/api/webhooks/123/abc', $form_record, $vars, ['name' => 'Alice'], [
    ['name' => 'Full Name', 'value' => 'Alice', 'inline' => false]
]);
$discord_json = json_decode($discord_payload_str, true);
assert_test($discord_json !== null && isset($discord_json['embeds']), 'Default payload for Discord URL generates Discord embed structure');
assert_test($discord_json['embeds'][0]['title'] === 'New Submission: Bug Report', 'Discord embed title correct');

$generic_payload_str = $manager->build_default_webhook_payload('https://example.com/api/webhook', $form_record, $vars, ['name' => 'Alice'], []);
$generic_json = json_decode($generic_payload_str, true);
assert_test($generic_json !== null && isset($generic_json['event']) && $generic_json['event'] === 'form_submission', 'Default payload for generic URL generates clean event JSON');
assert_test($generic_json['form_id'] === 7 && $generic_json['form_slug'] === 'bug-report', 'Generic payload contains form metadata');

// 7. Webhook Dispatch Disabled Check
assert_test($manager->send_form_webhook(['webhook_enabled' => 0, 'webhook_url' => 'https://example.com'], []) === false, 'Webhook does not send if webhook_enabled is false');
assert_test($manager->send_form_webhook(['webhook_enabled' => 1, 'webhook_url' => ''], []) === false, 'Webhook does not send if webhook_url is empty');

// 8. Invalid URL validation
$dispatch_res = $manager->dispatch_webhook_request('not-a-valid-url', '{}');
assert_test($dispatch_res['success'] === false && $dispatch_res['error'] === 'Invalid webhook URL', 'Invalid URL is safely rejected');

echo "\n-------------------------------------------------\n";
echo " Test Results: $passed Passed, $failed Failed.\n";
echo "-------------------------------------------------\n";

exit($failed === 0 ? 0 : 1);
}
