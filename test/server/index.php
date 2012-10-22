<?php
require_once(dirname(__FILE__) . '/../KortTests.php');

//run one specific Testfile or all available tests in this directory
if (isset($argv[1]) || isset($_GET['file'])) {
    $filename = isset($argv[1]) ? $argv[1] : $_GET['file'];
    if (file_exists($filename)) {
        Kort\Tests\KortTestRunner::runTestFile($filename);
    } else {
        die("Requested file '".$filename."' does not exist!");
    }

} else {
    //run tests in all directories
    $basedir = dirname(__FILE__);
    $dh = opendir($basedir);
    $suite = new AllTests();
    ob_start();
    while (($file = readdir($dh)) !== false) {
        if (is_dir($file) && $file != "..") {
             Kort\Tests\KortTestRunner::runTestDirectory($basedir."/".$file, $suite);
        }
    }
    $singleTestOutput = ob_get_contents();
    ob_end_clean();
    closedir($dh);
    $suite->run();
    echo "<h1>Detailed test results:</h1>";
    echo $singleTestOutput;
}
