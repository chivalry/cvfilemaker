<?php
require_once( 'FileMaker.php' );

class CVFileMaker extends FileMaker {
  
  protected $tables;
  protected $table;
  protected $return = 'result';
  
  const LO_PREFIX = 'Web>';
  const DEFAULT_PK = 'ID';
  
  const ERR_NO_MATCHING_RECORDS = 401;
  const ERR_CALC_VALIDAITON = 507;
  
  //============================================================================
  public function __construct( $options = null ) {
    $optionsFormat = array( 'optional' => array( 'tables', 'properties' ) );
    
    if ( is_array( $options ) ) {
      $validOptions = true;
      foreach ( array_keys( $options ) as $key ) {
        $validOptions = $validOptions &&
          in_array( $key, $optionsFormat['optional'] );
      }
      
      if ( $validOptions ) {
        parent::__construct();
    
        $this->setProperty( 'database', $options['properties']['database'] );
        $this->setProperty( 'hostspec', $options['properties']['hostspec'] );
        $this->setProperty( 'username', $options['properties']['username'] );
        $this->setProperty( 'password', $options['properties']['password'] );

      } else {
        trigger_error( 'Invalid key(s) passed to new CVFileMaker' );
      }
    } else {
      trigger_error(
        'Any parameters to new CVFileMaker object must be an array' );
    }
  }
}
?>