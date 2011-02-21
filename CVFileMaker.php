<?php
require_once( 'lib/FileMaker.php' );
require_once( 'lib/standard.php' );

/**
 * Convenience wrapper for FileMaker PHP API's FileMaker class
 *
 * Most of the operations performed with the FileMaker PHP API are similar and
 * common. This class is a sub-class of the FileMaker class that wraps common
 * operations such as finding, creating, editing and deleting records.
 *
 * @author Charles Ross <chivalry@mac.com>
 * @version 1.0
 * @copyright Charles Ross, 20 February, 2011
 **/
class CVFileMaker extends FileMaker {
  
  /**
   * The tables array that defines the schema for the database.
   *
   * This should be an array of the form array( 'tableName' => array( 'layout'
   * => 'layoutName', 'key' => 'primaryKeyName' ), 'anotherTable' => ... )
   *
   * @var array
   **/
  protected $tables;
  
  /**
   * The current table.
   *
   * The name of the current table which allows the retrieval from the $tables
   * array the names of the layout to use and the primary key name.
   *
   * @var string
   **/
  protected $table;
  
  /**
   * The format of the values to be returned.
   * 
   * Values are by default returned in the same manner used by the FileMaker
   * class, generally returning a FileMaker_Result object. This can be
   * overridden, especially when it's known that a single record should be
   * returned, to return instead the record (FileMaker Record object). When
   * creating a new record, the return could be the id of the created record.
   *
   * @var string
   **/
  protected $return = 'result';
  
  /**
   * Default layout prefix
   *
   * Tables can be defined without explicit layout names and when that's the
   * case, prefix the following constant to the table name to determine the
   * layout to use.
   **/
  const LO_PREFIX = 'Web>';
  
  /**
   * Default primary key name
   *
   * Tables can be defined without explicit primary key name and when that's
   * the case, use the following constant as the primary key name.
   **/  
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
          $this->setTables( $options['tables'] );
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
  public function __get( $name ) {
    if ( $this->_inTesting() ) {
      return $this->$name;
    } else {
      trigger_error( 'Cannot access protected property CVFileMaker::$' .
                       $name . ' in ' . $trace[0]['file'] . ' on line ' .
                       $trace[0]['line'],
                      E_USER_NOTICE );
    }
  }

  //============================================================================
  public function __call( $name, $arguments ) {
    if ( $this->_inTesting() ) {
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
  public function setTables( array $tables ) {
    $t = array();
    
    foreach ( $tables as $table => $values ) {
      // If the table var is a string, use it as the base.
      if ( is_string( $table ) ) {
        $tName = $table;
        $tLayout = isset( $values['layout'] ) ? $values['layout']
                                              : self::LO_PREFIX . $tName;
        $tKey = isset( $values['key'] ) ? $values['key']
                                        : ( $tName == 'Globals' ? 'gOne'
                                          : self::DEFAULT_PK );
      
      // If the table var is not a string, no options were given and the
      //   name is found in the value.
      } else {
        $tName = $values;
        $tLayout = self::LO_PREFIX . $tName;
        $tKey = $tName == 'Globals' ? 'gOne' : self::DEFAULT_PK;
      }
      
      $t[$tName] = array( 'layout' => $tLayout, 'key' => $tKey );
    }
    
    $this->tables = $t;
  }

  //============================================================================
  public function findAll( array $options ) {
    $format = array( 'required' => array( 'table' ),
                     'optional' => array( 'sort_orders', 'return' ) );
    
    if ( $this->_checkParams( $format, $options ) ) {
      $this->table = $options['table'];
      $sortOrders = isset( $options['sort_orders'] ) ? $options['sort_orders']
                                                     : null;
      $ret = isset( $options['return'] ) ? $options['return'] : $this->return;
      
      $findAllCmd = $this->newFindAllCommand( $this->_layout() );
      if ( $sortOrders ) {
        foreach ( $sortOrders as $sortOrder ) {
          $findCmd->addSortRule( $sortOrder['field'],
                                 $sortOrder['precedence'],
                                 $sortOrder['order'] );
        }
      }
      $result = $findAllCmd->execute();
      
      return $ret = 'result' ? $result : $result->getRecords();
    }
  }
  
  //============================================================================
  public function find( array $options ) {
    $format = array( 'required' => array( 'table', 'criteria' ),
                     'optional' => array( 'sort_orders', 'return' ) );

    if ( $this->_checkParams( $format, $options ) ) {
      $this->table = $options['table'];
      $criteria = $options['criteria'];
      $sortOrders = isset( $options['sort_oders'] ) ? $options['sort_orders']
                                                    : null;
      $ret = isset( $options['return'] ) ? $options['return'] : $this->return;
    
      $findCmd = $this->newFindCommand( $this->_layout() );
      foreach ( $criteria as $field => $value ) {
        $field = $field == 'id' ? $this->tables[$this->table]['key'] : $field;
        $findCmd->addFindCriterion( $field, $value );
      }
    
      if ( $sortOrders ) {
        foreach ( $sortOrders as $sortOrder ) {
          $findCmd->addSortRule( $sortOrder['field'],
                                 $sortOrder['precedence'],
                                 $sortOrder['order'] );
        }
      }
      $result = $findCmd->execute();
    
      return $ret == 'result' ? $result : $result->getRecords();
    }
  }

  //============================================================================
  public function findById( array $options ) {
    $format = array( 'required' => array( 'table', 'id' ),
                     'optional' => array( 'return' ) );
    
    if ( $this->_checkParams( $format, $options ) ) {
      $this->table = $options['table'];
      $ret = isset( $options['return'] ) ? $options['return'] : $this->return;
      
      $opts = array( 'table'    => $options['table'],
                     'criteria' => array( $this->_key() => $options['id'] ) );
      $result = $this->find( $opts );
      
      if ( $ret == 'result' ) {
        return $result;
      } else {
        return $this->getFirstRecord( $result );
      }
    }
  }
  
  //============================================================================
  public function newRecord( array $options ) {
    $format = array( 'required' => array( 'table', 'data' ),
                     'optional' => array( 'return' ) );
    
    if ( $this->_checkParams( $format, $options ) ) {
      $ret = isset( $options['return'] ) ? $options['return'] : 'record';
      
      $this->table = $options['table'];
      $newCmd = $this->newAddCommand( $this->_layout(), $options['data'] );
      $result = $newCmd->execute();
      $rec = $this->getFirstRecord( $result );
      
      if ( $ret == 'record' ) {
        return $rec;
      } else {
        return $rec->getField( $this->_key() );
      }
    }
  }
  
  //============================================================================
  public function editRecord( array $options ) {
    $format = array( 'required' => array( 'table', 'data' ),
                     'mutual'   => array( 'record_id', 'id' ) );
    
    $this->table = $options['table'];
  
    if ( isset( $options['record_id'] ) ) {
      $recID = $options['record_id'];
    } else {
      $id = $options['id'];
      $rec = $this->findById( array( 'table'  => $this->table,
                                     'id'     => $id,
                                     'return' => 'records' ) );
      $recID = $rec->getRecordID();
    }
  
  
    $editCmd = $this->newEditCommand( $this->_layout(), $recID );
    foreach ( $options['data'] as $key => $value ) {
      $editCmd->setField( $key, $value );
    }
    $result = $editCmd->execute();
  }
  
  //============================================================================
  public function getFirstRecord( FileMaker_Result $result ) {
    $recs = $result->getRecords();
    return $recs[0];
  }
  
  //============================================================================
  protected function _checkParams( array $format, array $params ) {
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
    $requiredExists = !isset( $format['required'] )
      || $this->_requiredParamsExist( $format['required'], $params );
    
    // Check that mutual parameters are exclusive.
    $mutualsAreExclusive = !isset( $format['mutual'] )
      || $this->_mutualParamsAreExclusive( $format['mutual'], $params );
    
    return $isParam && $requiredExists && $mutualsAreExclusive;
  }
  
  //============================================================================
  protected function _isMutual( array $mutualParamSets, $param ) {
    $mutualParams = array();
    foreach ( $mutualParamSets as $mutualParamSet ) {
      $mutualParams = array_merge( $mutualParams, $mutualParamSet );
    }
    return in_array( $param, $mutualParams );
  }

  //============================================================================
  protected function _requiredParamsExist( array $requiredParams,
                                           array $params ) {
    $requiredExists = false;
    foreach ( $requiredParams as $requiredParam ) {
      $requiredExists = $requiredExists
                        || in_array( $requiredParam, array_keys( $params ) );
    }
    return $requiredExists;
  }
  
  //============================================================================
  protected function _mutualParamsAreExclusive( array $mutualParamSets,
                                                array $params ) {
    $mutualsAreExclusive = true;
    foreach ( $mutualParamSets as $mutualSet ) {
      $mutualsAreExclusive = $mutualsAreExclusive &&
        ( count( array_intersect( array_values( $mutualSet ),
                                  array_keys( $params ) ) ) == 1 );
    }
    
    return $mutualsAreExclusive;
  }

  //============================================================================
  protected function _inTesting() {
    $traceRecs = debug_backtrace();
    $inTesting = false;
    foreach ( $traceRecs as $traceRec ) {
      $inTesting = $inTesting ||
                   preg_match( '/simpletest/', $traceRec['file'] );
    }
    return $inTesting;
  }
  
  //============================================================================
  protected function _layout() {
    return $this->tables[$this->table]['layout'];
  }

  //============================================================================
  protected function _key() {
    return $this->tables[$this->table]['key'];
  }
}
?>