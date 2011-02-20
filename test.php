<?php
require_once( 'simpletest/unit_tester.php' );
require_once( 'simpletest/reporter.php' );

$test = new TestSuite( 'All CVFileMaker Tests' );
$test->addFile( 'test_cvfilemaker_basics.php' );
$test->run( new HtmlReporter() );
?>