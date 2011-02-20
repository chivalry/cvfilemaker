<?php
require_once( 'CVFileMaker.php' );

class CVFileMakerTestBasics extends UnitTestCase {
  
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
    
    // Create sample FileMaker records in the standard database flagged so
    //   they can be deleted on tearDown.
    $fm = $this->newFileMaker();
    
    for ( $i = 1; $i <= 2; $i++ ) {
      $data = array( 'Email'        => 'email' . $i . '@example.com',
                     'FirstName'    => 'First' . $i,
                     'LastName'     => 'Last' . $i,
                     'IsTestRecord' => 1 );
      $addCmd = $fm->newAddCommand( 'Web>Users', $data );
      $result = $addCmd->execute();
    }
  }
  
  //============================================================================
  function tearDown() {
    // Delete the samples records created in setUp.
    $fm = $this->newFileMaker();
    
    $findCmd = $fm->newFindCommand( 'Web>Users' );
    $findCmd->addFindCriterion( 'IsTestRecord', 1 );
    $result = $findCmd->execute();
    $recs = $result->getRecords();
    
    foreach ( $recs as $rec ) {
      $recID = $rec->getRecordId();
      $delCmd = $fm->newDeleteCommand( 'Web>Users', $recID );
      $delCmd->execute();
    }
    
    $scriptCmd = $fm->newPerformScriptCommand( 'Web>Users',
                                               'Reset User ID Serial' );
    $scriptCmd->execute();
  }

  //============================================================================
  function newFileMaker() {
    $fm = new FileMaker;
    $fm->setProperty( 'database', $this->standardDB );
    $fm->setProperty( 'hostspec', $this->standardHS );
    $fm->setProperty( 'username', $this->standardUN );
    $fm->setProperty( 'password', $this->standardPW );
    return $fm;
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
    $cv = new CVFileMaker( array( 'tables' => $this->customTableDef ) );
    $this->assertEqual( $cv->tables, $this->customTableDef );
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
  
  //============================================================================
  function test__Incomplete_Tables_Should_Get_Default_Values() {
    $cv = new CVFileMaker( array( 'properties' => $this->standardProperties ) );
    
    $cv->setTables( $this->standardTableDef );
    $definedTables = $cv->tables;
    
    $globalTableKey    = $definedTables['Globals']['key'];
    $globalTableLayout = $definedTables['Globals']['layout'];
    $quotesTableKey    = $definedTables['Quotes']['key'];
    $quotesTableLayout = $definedTables['Quotes']['layout'];
    
    $this->assertEqual( $globalTableKey,    'gOne' );
    $this->assertEqual( $globalTableLayout, 'Web>Globals' );
    $this->assertEqual( $quotesTableKey,    'ID' );
    $this->assertEqual( $quotesTableLayout, 'Web>Quotes' );
  }
  
  //============================================================================
  function test__FindAll_Should_Get_Every_Record() {
    // Use standard FileMaker object to get the records without CVFileMaker
    $cv = new CVFileMaker( array( 'properties' => $this->standardProperties,
                                  'tables'     => $this->standardTableDef ) );

    $findAllCmd = $cv->newFindAllCommand( 'Web>Users' );
    $result     = $findAllCmd->execute();
    $baseCount  = $result->getFoundSetCount();
    
    $result    = $cv->findAll( array( 'table' => 'Users' ) );
    $testCount = $result->getFoundSetCount();
    
    $this->assertEqual( $baseCount, $testCount );
  }
}
?>