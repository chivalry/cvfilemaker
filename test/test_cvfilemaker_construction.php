<?php
require_once( '../CVFileMaker.php' );
require_once( 'test_cvfilemaker.php' );

class CVFileMakerTestConstruction extends CVFileMakerTest {
  
  //============================================================================
  function setUp() {
    parent::setup();
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
}
?>