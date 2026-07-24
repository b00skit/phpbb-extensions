<?php
/**
 * Test Suite for booskit/sendas phpBB Extension
 */

echo "=================================================\n";
echo " Running Unit Test Suite for booskit/sendas      \n";
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

$ext_dir = __DIR__ . '/../booskit/sendas';

assert_test(is_dir($ext_dir), 'booskit/sendas directory exists');
assert_test(file_exists($ext_dir . '/composer.json') || is_dir($ext_dir . '/adm'), 'sendas extension structure is recognized as stub/deprecated');

echo "\n-------------------------------------------------\n";
echo " Test Results: $passed Passed, $failed Failed.\n";
echo "-------------------------------------------------\n";

exit($failed === 0 ? 0 : 1);
