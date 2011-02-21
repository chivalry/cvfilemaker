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
   * Possible values include 'result', 'records', and 'id'.
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
  
  /**
   * The constructor for the class
   *
   * Sets up the database properties (as defined by the FileMaker class) and the
   * table property of this class if they are passed to it. Otherwise it behaves
   * just like the parent.
   *
   * @param  array $options an optional array of the table properties and
   *                        database definition properties
   * @return void
   * @access public
   * @author Charles Ross
   **/
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

  /**
   * Magic method to provide access to protected properties when testing
   *
   * If a protected property is attempted to be accessed, this method is
   * executed. If the caller indicates that it's a unit test, go ahead and
   * provide access. Otherwise trigger an error.
   *
   * @param  string $name the name of the property to access
   * @return mixed        depends on the property accessed
   * @access public
   * @author Charles Ross
   **/
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

  /**
   * Magic method to provide access to protected methods when testing
   *
   * If a protected method is attempted to be accessed, this method is executed.
   * If the caller indicates that it's a unit test, go ahead and provide access.
   * Otherwise trigger an error.
   *
   * @param  string $name      the name of the method to access
   * @param  array  $arguments the arguments to pass onto the protected method
   * @return mixed             depends on the method accessed
   * @access public
   * @author Charles Ross
   **/
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

  /**
   * Set the tables to the passed parameter
   *
   * The $tables array can be a traditional array or a hash or a combination of
   * the two. For any member of the array that does not have a value attached
   * this method will assign default values to the layout and key keys.
   *
   * @param  string $tables the definition of the tables for the database with
   *                        optional inclusion of the layout to use and the name
   *                        of the primary key.
   * @return void
   * @access public
   * @author Charles Ross
   **/
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

  /**
   * Find all the records in the passed table.
   *
   * This is a wrapper for FileMaker's FileMaker_Command_FindAll and will return
   * either the FileMaker_Result object or an array of FileMaker_Record objects,
   * depending on the value of $this->return or the value of the return
   * parameter.
   *
   * @param  array $options see the $format local variable for the definition of
   *                        possible options.
   * @return mixed          either a FileMaker_Result or an array of
   *                        FileMaker_Record objects
   * @access public
   * @author Charles Ross
   **/
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
  
  /**
   * Find the records in the passed table with the given criteria.
   *
   * This is a wrapper for FileMaker's FileMaker_Command_Find and will return
   * either the FileMaker_Result object or an array of FileMaker_Record objects,
   * depending on the value of $this->return or the value of the return
   * parameter.
   *
   * @param  array $options see the $format local variable for the definition of
   *                        possible options.
   * @return mixed          either a FileMaker_Result or an array of
   *                        FileMaker_Record objects
   * @access public
   * @author Charles Ross
   **/
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

  /**
   * Find the record in the passed table with the given id.
   *
   * This is a wrapper for FileMaker's FileMaker_Command_Find and will return
   * either the FileMaker_Result object or the FileMaker_Record object,
   * depending on the value of $this->return or the value of the return
   * parameter.
   *
   * @param  array $options see the $format local variable for the definition of
   *                        possible options. The id option refers to the
   *                        primary key, not the record id.
   * @return mixed          either a FileMaker_Result or an array of
   *                        FileMaker_Record objects
   * @access public
   * @author Charles Ross
   **/
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
  
  /**
   * Create a record with the data passed.
   *
   * This is a wrapper for FileMaker's FileMaker_Command_Add and will return
   * either the FileMaker_Record object or the primary key of the created
   * record,  depending on the value of $this->return or the value of the return
   * parameter.
   *
   * @param  array $options see the $format local variable for the definition of
   *                        possible options.
   * @return mixed          either a FileMaker_Result or the new record's
   *                        primary key value.
   * @access public
   * @author Charles Ross
   **/
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
  
  /**
   * Edit the record in the passed table with the given id.
   *
   * This is a wrapper for FileMaker's FileMaker_Command_Edit and will return
   * either the FileMaker_Result object or the FileMaker_Record object,
   * depending on the value of $this->return or the value of the return
   * parameter.
   *
   * @param  array $options see the $format local variable for the definition of
   *                        possible options. The id option refers to the
   *                        primary key, not the record id.
   * @return mixed          either a FileMaker_Result or an array of
   *                        FileMaker_Record objects
   * @access public
   * @author Charles Ross
   **/
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
  
  /**
   * Return the first (generally only) record from a FileMaker_Result object.
   *
   * @param  FileMaker_Result $result
   * @return FileMaker_Record
   * @access public
   * @author Charles Ross
   **/
  public function getFirstRecord( FileMaker_Result $result ) {
    $recs = $result->getRecords();
    return $recs[0];
  }
  
  /**
   * Check the parameters against the format provided.
   *
   * Generally, especially when multiple parameters are needed or offered by a
   * method, they are passed as name/value pairs in an array. Each method can
   * specify the expected format for the options passed. Valid keys include
   * 'required', 'optional' and 'mutual'. 'required' options must be present,
   * 'optional' parameters may be present, and 'mutual' is an array of mutually
   * exclusive parameters, only one of which in any set can be present. This
   * method (attempts) to confirm that the parameters conform to the format.
   * "Attempts" because the mutual parameters do not appear to work entirely
   * correctly yet, which means my unit test is off somehow. This is why this
   * method isn't yet called to confirm the parameters of the editRecord method.
   *
   * @param  array $format the format that params should conform to
   * @return array $params the parameters that were passed to the calling method
   * @access protected
   * @author Charles Ross
   * @todo   Get 'mutual' format working correctly
   **/
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