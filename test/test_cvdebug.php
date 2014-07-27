<?php
require_once( '../CVDebug.php' );

class CVDebugTest extends UnitTestCase {
  
  private $singleVar = array( 'x' => '1' );
  private $doubleVar = array( 'x' => 1, 'y' => 2 );
  private $dumps = array();
  
  //============================================================================
  public function setUp() {
    ob_start();
    CVDebug::vardump( $this->singleVar );
    $this->dump[] = ob_get_clean();
    
    ob_start();
    CVDebug::vardump( $this->doubleVar );
    $this->dump[] = ob_get_clean();
  }


  //============================================================================
  public function test__vardump_Is_Enclosed_In_pre_Tags() {
    $this->assertIdentical( strpos( $this->dump[0], '<pre>' ), 0 );
    $rev = strrev( $this->dump[0] );
    $revTag = strrev( '</pre>' );
    $this->assertIdentical( strpos( $rev, $revTag ), 0 );
  }
  
  public function test__vardump_Should_Contain_var_dump_Output() {
    ob_start();
    var_dump( '1' );
    $dump = ob_get_clean();
    
    $pos = strpos( $this->dump[0], $dump );
    $this->assertTrue( $pos >= 0 && $pos <= strlen( $this->dump[0] ) );
  }

}
?>