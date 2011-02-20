<?php
require_once( 'CVFileMaker.php' );
require_once( 'test_cvfilemaker.php' );

class CVFileMakerTestOperations extends CVFileMakerTest {
  
  //============================================================================
  function setUp() {
    parent::setup();
    
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