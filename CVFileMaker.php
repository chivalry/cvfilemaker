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
        
        if ( isset( $options['properties'] ) ) {
          $this->setProperty( 'database', $options['properties']['database'] );
          $this->setProperty( 'hostspec', $options['properties']['hostspec'] );
          $this->setProperty( 'username', $options['properties']['username'] );
          $this->setProperty( 'password', $options['properties']['password'] );
        }
        
        if ( isset( $options['tables'] ) ) {
          $this->tables = $options['tables'];
        }

      } else {
        trigger_error( 'Invalid key(s) passed to new CVFileMaker' );
      }
    } else {
      trigger_error(
        'Any parameters to new CVFileMaker object must be an array' );
    }
  }

  //============================================================================
  function __get( $name ) {
    $trace = debug_backtrace();
    $caller = $trace[1];
    $inTesting = preg_match( '/simpletest/', $caller['file'] );
    
    if ( $inTesting ) {
      if ( property_exists( $this, $name ) ) {
        if ( $name == 'tables' ) {
          return $this->tables;
        }
      }
    } else {
      trigger_error( 'Cannot access protected property CVFileMaker::$' .
                       $name . ' in ' . $trace[0]['file'] . ' on line ' .
                       $trace[0]['line'],
                      E_USER_NOTICE );
    }
  }
}
?>