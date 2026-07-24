<?php
/**
 * Master Unit Test Runner for booskit extensions
 */

echo "=================================================================\n";
echo "       PHPBB EXTENSIONS MASTER UNIT TEST SUITE RUNNER            \n";
echo "=================================================================\n\n";

$test_files = glob(__DIR__ . '/test_*.php');
sort($test_files);

$total_suites = count($test_files);
$passed_suites = 0;
$failed_suites = 0;

foreach ($test_files as $file) {
    $filename = basename($file);
    echo "▶ Executing $filename ...\n";
    
    $cmd = 'php ' . escapeshellarg($file);
    $output = [];
    $exit_code = 0;
    
    exec($cmd, $output, $exit_code);
    
    echo implode("\n", $output) . "\n";
    
    if ($exit_code === 0) {
        $passed_suites++;
        echo "[SUITE PASSED] $filename\n";
    } else {
        $failed_suites++;
        echo "[SUITE FAILED] $filename (Exit Code: $exit_code)\n";
    }
    echo "-----------------------------------------------------------------\n\n";
}

echo "=================================================================\n";
echo " AGGREGATED SUMMARY REPORT                                       \n";
echo "=================================================================\n";
echo " Total Test Suites Run : $total_suites\n";
echo " Suites Passed         : $passed_suites\n";
echo " Suites Failed         : $failed_suites\n";
echo "=================================================================\n";

exit($failed_suites === 0 ? 0 : 1);
