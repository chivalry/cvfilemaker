<?php
require_once( 'simpletest/unit_tester.php' );
require_once( 'simpletest/reporter.php' );
require_once( '../lib/standard.php' );

$test = new TestSuite( 'CVFileMaker' );
$test->addFile( 'test_cvfilemaker_construction.php' );
$test->addFile( 'test_cvfilemaker_parameters.php' );
$test->addFile( 'test_cvfilemaker_operations.php' );
$test->run( new HtmlReporter() );

$test = new TestSuite( 'CVDebug' );
$test->addFile( 'test_cvdebug.php' );
$test->run( new HtmlReporter() );
?>