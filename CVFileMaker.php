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
      if ( $this->_checkParams( $optionsFormat, $options ) ) {
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
                    && $this->_isMutual( $format['mutual'], $param );
      
      $isParam = $isOptional || $isRequired || $isMutual;
    }
    
    // Check that required params are present.
    $requiredExists = !isset( $format['required'] ) ||
      $this->_requiredParamsExist( $format['required'], $params );
    
    // Check that mutual parameters are exclusive.
    $mutualsAreExclusive = !isset( $format['mutual'] ) ||
      $this->_mutualParamsAreExclusive( $format['mutual'], $params );
    
    return $isParam && $requiredExists && $mutualsAreExclusive;
  }
  
  //============================================================================
  protected function _isMutual( $mutualParamSets, $param ) {
    $mutualParams = array();
    foreach ( $mutualParamSets as $mutualParamSet ) {
      $mutualParams = array_merge( $mutualParams, $mutualParamSet );
    }
    return in_array( $param, $mutualParams );
  }

  //============================================================================
  protected function _requiredParamsExist( $requiredParams, $params ) {
    $requiredExists = false;
    foreach ( $requiredParams as $requiredParam ) {
      $requiredExists = $requiredExists
                        || in_array( $requiredParam, array_keys( $params ) );
    }
    return $requiredExists;
  }
  
  //============================================================================
  protected function _mutualParamsAreExclusive( $mutualParamSets, $params ) {
    $mutualsAreExclusive = true;
    foreach ( $mutualParamSets as $mutualSet ) {
      $mutualsAreExclusive = $mutualsAreExclusive &&
        ( count( array_intersect( array_values( $mutualSet ),
                                  array_keys( $params ) ) ) == 1 );
    }
    
    return $mutualsAreExclusive;
  }
}
?>