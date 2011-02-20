<?php
require_once( 'CVFileMaker.php' );
require_once( 'test_cvfilemaker.php' );

class CVFileMakerTestParameters extends CVFileMakerTest {
  
  //============================================================================
  function setUp() {
    parent::setup();
  }
  
  //============================================================================
  function test__Optional_Parameters_Should_Be_Valid() {
    $cv = new CVFileMaker;

    $format = array( 'optional' => array( 'param1', 'param2' ) );
    $params = array( 'param1' => 1, 'param2' => 2 );

    $this->assertTrue( $cv->checkParams( $format, $params ) );
  }
  
  //============================================================================
  function test__Required_Parameters_Should_Be_Valid() {
    $cv = new CVFileMaker;

    $format = array( 'required' => array( 'param1', 'param2' ) );
    $params = array( 'param1' => 1, 'param2' => 2 );

    $this->assertTrue( $cv->checkParams( $format, $params ) );
  }
  
  //============================================================================
  function test__Mutual_Parameters_Should_Be_Valid() {
    $cv = new CVFileMaker;

    $format = array( 'mutual' => array( array( 'param1', 'param2' ) ) );
    $params = array( 'param1' => 1 );

    $this->assertTrue( $cv->checkParams( $format, $params ) );
  }
  
  //============================================================================
  function test__Invalid_Parameters_Should_Fail() {
    $cv = new CVFileMaker;

    $format = array( 'optional' => array( 'param3', 'param4' ) );
    $params = array( 'invalid' => 0 );

    $this->assertFalse( $cv->checkParams( $format, $params ) );
  }
  

  //============================================================================
  function test__Required_Parameters_Should_Be_Required() {
    $cv = new CVFileMaker;
    
    $format = array( 'required' => array( 'param1', 'param2' ) );
    $params = array( 'param3' => 3 );
    
    $this->assertFalse( $cv->checkParams( $format, $params ) );
  }

  //============================================================================
  function test__Mutual_Parameters_Should_Be_Exclusive() {
    $cv = new CVFileMaker;
    
    $format = array( 'mutual'   => array( array( 'param1', 'param2' ) ) );
    $params = array( 'param1' => 1, 'param2' => 2 );
    
    $this->assertFalse( $cv->checkParams( $format, $params ) );
  }
}
?>