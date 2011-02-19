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
    } elseif ( !is_null( $options ) ) {
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
      return $this->$name;
    } else {
      trigger_error( 'Cannot access protected property CVFileMaker::$' .
                       $name . ' in ' . $trace[0]['file'] . ' on line ' .
                       $trace[0]['line'],
                      E_USER_NOTICE );
    }
  }

  //============================================================================
  function __call( $name, $arguments ) {
    $trace = debug_backtrace();
    $caller = $trace[2];
    $inTesting = preg_match( '/simpletest/', $caller['file'] );
    
    if ( $inTesting ) {
      if ( $name == 'checkParams' ) {
        return $this->_checkParams( $arguments[0], $arguments[1] );
      }
    } else {
      trigger_error( 'Call to protected method CVFileMaker::' . $name .
                       '() in ' . $trace[0]['file'] . ' on line ' .
                       $trace[0]['line'],
                     E_USER_NOTICE );
    }
  }

  //============================================================================
  protected function _checkParams( $format, $params ) {
    // Check that the params passed are all expected in the format.
    $validOptional = $validRequired = $validMutual = true;
    
    foreach ( array_keys( $params ) as $param ) {
      $isOptional = isset( $format['optional'] )
                    && in_array( $param, $format['optional'] );
      $isRequired = isset( $format['required'] )
                    && in_array( $param, $format['required'] );
      $isMutual   = isset( $format['mutual'] )
                    && in_array( $param, $format['mutual'] );
      
      $isParam = $isOptional || $isRequired || $isMutual;
    }
    
    return $isParam;
  }
}
?>