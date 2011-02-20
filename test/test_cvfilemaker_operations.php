<?php
require_once( '../CVFileMaker.php' );
require_once( 'test_cvfilemaker.php' );

class CVFileMakerTestOperations extends CVFileMakerTest {
  
  protected $recID;
  protected $ID;
  
  protected $cv;
  
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
      $recs   = $result->getRecords();
      $rec    = $recs[0];
      $this->recID = $i == 1 ? $rec->getRecordId()    : $this->recID;
      $this->ID    = $i == 1 ? $rec->getField( 'ID' ) : $this->ID;
    }
    
    // Usually need a CVFileMaker object with the same properties.
    $this->cv = new CVFileMaker(
      array( 'properties' => $this->standardProperties,
             'tables'     => $this->standardTableDef ) );

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
    $findAllCmd = $this->cv->newFindAllCommand( 'Web>Users' );
    $result     = $findAllCmd->execute();
    $baseCount  = $result->getFoundSetCount();
    
    $result    = $this->cv->findAll( array( 'table' => 'Users' ) );
    $testCount = $result->getFoundSetCount();
    
    $this->assertEqual( $baseCount, $testCount );
  }
  
  //============================================================================
  function test__Find_Should_Get_The_Right_Record() {
    $result = $this->cv->find(
      array( 'table' => 'Users',
             'criteria' => array( 'ID' => $this->ID ) ) );
    $rec = $this->cv->getFirstRecord( $result );
    $firstName = $rec->getField( 'FirstName' );
    
    $this->assertEqual( $firstName, 'First1' );
  }
  
  //============================================================================
  function test__Find_Should_Return_A_Record_When_Requested() {
    $recs = $this->cv->find( array( 'table'     => 'Users',
                                    'criteria'  => array( 'ID' => $this->ID ),
                                    'return'    => 'record' ) );
    $rec = $recs[0];
    $firstName = $rec->getField( 'FirstName' );
    
    $this->assertEqual( $firstName, 'First1' );
  }
  
  //============================================================================
  function test__FindById_Should_Get_The_Right_Record() {
    $result = $this->cv->findById( array( 'table' => 'Users',
                                          'id'    => $this->ID ) );
    $rec = $this->cv->getFirstRecord( $result );
    $firstName = $rec->getField( 'FirstName' );
    
    $this->assertEqual( $firstName, 'First1' );
  }

  //============================================================================
  function test__NewRecord_Should_Create_A_New_Record() {
    $allRecs = $this->cv->findAll( array( 'table' => 'Users' ) );
    $count = intval( $allRecs->getFoundSetCount() );
    
    $data = array( 'FirstName'    => 'First',
                   'LastName'     => 'Last',
                   'IsTestRecord' => 1 );
    $result = $this->cv->newRecord( array( 'table' => 'Users',
                                           'data'  => $data ) );
    
    $allRecs = $this->cv->findAll( array( 'table' => 'Users' ) );
    $newCount = intval( $allRecs->getfoundSetCount() );
    
    $this->assertEqual( $count + 1, $newCount );
  }
  
  //============================================================================
  function test__EditRecord_Should_Edit_Records_Data() {
    $data = array( 'FirstName' => 'NewFirst' );
    $this->cv->editRecord( array( 'table' => 'Users',
                                  'data'  => $data,
                                  'id'    => $this->ID ) );
    $result = $this->cv->findById( array( 'table' => 'Users',
                                          'id'    => $this->ID ) );
    $rec = $this->cv->getFirstRecord( $result );
    $firstName = $rec->getField( 'FirstName' );
    
    $this->assertEqual( $firstName, 'NewFirst' );
  }
}
?>