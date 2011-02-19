<?php
require_once( 'simpletest/autorun.php' );
require_once( 'CVFileMaker.php' );

class CVFileMakerTest extends UnitTestCase {
  
  private $standardDB = 'BYB_MSP';
  private $standardHS = 'http://buildyourbdr.com/';
  private $standardUN = 'Web User';
  private $standardPW = 'g.i.joe';
  
  private $standardProperties;
  
  private $standardTableDef = array( 'Globals', 'Quotes', 'Servers', 'Users' );
  
  private $customDB = 'ReachInsights';
  private $customHS = 'http://64.71.231.45';
  private $customUN = 'WebUser';
  private $customPW = 'g.i.joe';
  
  private $customProperties;
  
  private $customTableDef = array(
    'Choices'       => array( 'layout' => 'Web>Choices',
                              'key'    => 'KP_Choices_ID' ),
    'Exams'         => array( 'layout' => 'Web>Exams',
                              'key'    => 'KP_Exam_ID' ),
    'People'        => array( 'layout' => 'Web>People',
                              'key'    => 'KP_Person_ID' ),
    'Questions'     => array( 'layout' => 'Web>Questions',
                              'key'    => 'KP_Question_ID' ),
    'RecordedExams' => array( 'layout' => 'Web>RecordedExam',
                              'key'    => 'KP_RecordedExam_ID' ),
    'Responses'     => array( 'layout' => 'Web>Responses',
                              'key'    => 'KP_Response_ID' ) );
  
  //============================================================================
  function setUp() {
    $this->standardProperties = array( 'database' => $this->standardDB,
                                       'hostspec' => $this->standardHS,
                                       'username' => $this->standardUN,
                                       'password' => $this->standardPW );
                                       
    $this->customProperties = array( 'database' => $this->customDB,
                                     'hostspec' => $this->customHS,
                                     'username' => $this->customUN,
                                     'password' => $this->customPW );
  }
  
  //============================================================================
  function test__Construction_With_Parameters_Connects_To_Database() {
    $cv         = new CVFileMaker(
                    array( 'properties' => $this->standardProperties ) );
    $findAllCmd = $cv->newFindAllCommand( 'Web>Globals' );
    $result     = $findAllCmd->execute();
    
    $this->assertFalse( CVFileMaker::isError( $result ) );
  }
  
  //============================================================================
  function test__Construction_Should_Accept_Only_Single_Array_Parameter() {
    $this->expectError( new PatternExpectation( '/must be an array/i' ) );
    $cv = new CVFileMaker( 'string' );
  }
  
  //============================================================================
  function test__Construction_Shouldnt_Accept_Invalid_Keys_In_Array() {
    $this->expectError( new PatternExpectation( '/invalid key/i' ) );
    $cv = new CVFileMaker( array( 'key1' => 'value1' ) );
  }
  
  //============================================================================
  function test__Construction_Should_Properly_Set_Tables() {
    $cv = new CVFileMaker( array( 'tables' => $this->standardTableDef ) );
    $tables = $cv->tables;
    $this->assertEqual( $tables, $this->standardTableDef );
  }
}
?>