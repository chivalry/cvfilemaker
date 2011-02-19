<?php
require_once( 'FileMaker.php' );

class CVFileMaker extends FileMaker {
  
  protected $tables;
  protected $table;
  protected $return = 'result';
  
  const LO_PREFIX  = 'Web>';
  const DEFAULT_PK = 'ID';
  
  const ERR_NO_MATCHING_RECORDS = 401;
  const ERR_CALC_VALIDATION = 507;
  
  //============================================================================
  private function ___PUBLIC_FUNCTIONS() {}
  //============================================================================
  
  //============================================================================
  // Use the parameter if it's provided, otherwise use the constants if they're
  //   defined, otherwise default to the original behavior. If a tables option
  //   is passed, set that as well. The only option currently supported is the
  //   table option. See the setTable method for how this should be formatted.
  //   Eventually the properties could be supported as additional options.
  public function __construct( $options = null ) {
    $format = array( 'optional' => array( 'tables' ) );

    if ( $this->checkParams( $format, $options ) ) {
      parent::__construct();
    
      $db = defined( 'DATABASE' ) ? DATABASE : null;
      $hs = defined( 'HOSTSPEC' ) ? HOSTSPEC : null;
      $un = defined( 'USERNAME' ) ? USERNAME : null;
      $pw = defined( 'PASSWORD' ) ? PASSWORD : null;
    
      if ( $options ) {
        $db = isset( $options['database'] ) ? $options['database'] : $db;
        $hs = isset( $options['hostspec'] ) ? $options['hostspec'] : $hs;
        $un = isset( $options['hostspec'] ) ? $options['hostspec'] : $un;
        $pw = isset( $options['password'] ) ? $options['password'] : $pw;
      
        if ( isset( $options['tables'] ) ) {
          $this->setTables( $options['tables'] );
        }
      }
    
      $this->setProperty( 'database', $db );
      $this->setProperty( 'hostspec', $hs );
      $this->setProperty( 'username', $un );
      $this->setProperty( 'password', $pw );
    }
  }

  //============================================================================
  // The tables property should be an array where the keys are names of the
  //   tables. Values should be arrays with keys for properties, such as the
  //   layout to use and the name of the primary key. If any table names are
  //   missing information, use the default settings.
  // $tables should be an array of the form
  //   array( 'tableName' => array( 'layout' => 'layoutName',
  //                                'key' => 'keyname' ) )
  public function setTables( $tables ) {
    $t = array();
    
    foreach ( $tables as $table => $values ) {
      // if the table var is a string, use it as the base.
      if ( is_string( $table ) ) {
        $tName   = $table;
        $tLayout = isset( $values['layout'] ) ? $values['layout']
                                              : self::LO_PREFIX . $tName;
        $tKey    = isset( $values['key'] )    ? $values['key']
                                              : self::DEFAULT_PK;
      
      // If the table var is not a string, no options were given and the
      //   name is found in the value.
      } else {
        $tName   = $values;
        $tLayout = self::LO_PREFIX . $tName;
        $tKey    = self::DEFAULT_PK;
      }
      
      $t[$tName] = array( 'layout' => $tLayout, 'key' => $tKey );
    }
    $this->tables = $t;
  }
  
  //============================================================================
  // Return the contents of the $tables array, public only for testing purposes.
  //   There's probably no non-testing need for this.
  public function getTables() {
    return $this->tables;
  }
  
  //============================================================================
  // Set the default return type for methods.
  public function setReturn( $ret ) {
    $this->return = $ret;
  }
  
  //============================================================================
  // Return the default return type for methods. This is only here for testing.
  public function getReturn() {
    return $this->return;
  }

  //============================================================================
  // Use the tables property to get the needed information to find all of the
  //   records in the specified table. $options should be an array with a key
  //   for the table name: ('table' => 'tableName'). Supported options are:
  //   table (required)
  //   sort_orders (optional)
  //   return (optional)
  public function findAll( $options ) {
    $format = array( 'required' => array( 'table' ),
                     'optional' => array( 'sort_orders', 'return' ) );

    if ( $this->checkParams( $format, $options ) ) {
      $this->table = $options['table'];
      $sortOrders = isset( $options['sort_orders'] ) ? $options['sort_orders']
                                                     : null;
      $ret = isset( $options['return'] ) ? $options['return'] : $this->return;
    
      $findAllCmd = $this->newFindAllCommand( $this->layout() );
      if ( $sortOrders ) {
        foreach( $sortOrders as $sortOrder ) {
          $findCmd->addSortRule( $sortOrder['field'],
                                 $sortOrder['precedence'],
                                 $sortOrder['order'] );
        }
      }
      $result = $findAllCmd->execute();
    
      return $ret == 'result' ? $result : $result->getRecords();
    }
  }
  
  //============================================================================
  // Find a single record given the id that it would have in its primary key
  //   field. Supported options are:
  // table (required)
  // id (required)
  // return (optional)
  public function findById( $options ) {
    $format = array( 'required' => array( 'table', 'id' ),
                     'optional' => array( 'return' ) );

    if ( $this->checkParams( $format, $options ) ) {
      $this->table = $options['table'];
      $ret = isset( $options['return'] ) ? $options['return'] : $this->return;
    
      $opts = array( 'table'    => $options['table'],
                     'criteria' => array( $this->key() => $options['id'] ) );
      $result = $this->find( $opts );
    
      if ( $ret == 'result' ) {
        return $result;
      } else {
        $recs = $result->getRecords();
        return $recs[0];
      }
    }
  }
  
  //============================================================================
  // Find records in the given table with the given criteria and the given
  //   sort options. Sort options haven't been tested yet and they can probably
  //   be broken into a separate private method shared by this and the findAll
  //   method. Supported options are:
  // table (required)
  // criteria (required)
  // sort_orders (optional)
  // return (optional)
  public function find( $options ) {
    $format = array( 'required' => array( 'table', 'criteria' ),
                     'optional' => array( 'sort_orders', 'return' ) );

    if ( $this->checkParams( $format, $options ) ) {
      $this->table = $options['table'];
      $criteria    = $options['criteria'];
      $sortOrders  = isset( $options['sort_orders'] ) ? $options['sort_orders']
                                                      : null;
      $ret = isset( $options['return'] ) ? $options['return'] : $this->return;
    
      $findCmd = $this->newFindCommand( $this->layout() );
      foreach ( $criteria as $field => $value ) {
        $field = $field == 'id' ? $this->tables[$this->table]['key'] : $field;
        $findCmd->addFindCriterion( $field, $value );
      }
    
      if ( $sortOrders ) {
        foreach( $sortOrders as $sortOrder ) {
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
  // Create a new record in the given table with the provided data and return it
  //   to the caller. Return the record's id if that's requested. Supported
  //   options are:
  // table (required)
  // data (required)
  // return (optional)
  public function newRecord( $options ) {
    $format = array( 'required' => array( 'table', 'data' ),
                     'optional' => array( 'return' ) );

    if ( $this->checkParams( $format, $options ) ) {
      $ret         = isset( $options['return'] ) ? $options['return']
                                                 : 'record';
      $this->table = $options['table'];
      $newCmd      = $this->newAddCommand( $this->layout(), $options['data'] );
      $result      = $newCmd->execute();
      $recs        = $result->getRecords();
      $rec         = $recs[0];

      if ( $ret == 'record' ) {
        return $rec;      
      } else {
        return $rec->getField( $this->key() );
      }
    }
  }

  //============================================================================
  // Edit the given record with the given data. Records can be identified either
  //   by their record ID or by their primary key field (id). Supported options
  //   are:
  // table (required)
  // record_id (optional, but must have this or id)
  // id (optional, but must have this or record_id)
  // data (required)
  public function editRecord( $options ) {
    $format = array( 'required' => array( 'table', 'data' ),
                     'mutual'   => array( 'record_id', 'id' ) );

    if ( $this->checkParams( $format, $options ) ) {
      $this->table = $options['table'];
    
      if ( isset( $options['record_id'] ) ) {
        $recordID = $options['record_id'];      
      } else {
        $id = $options['id'];
        $rec = $this->findById( array( 'table' => $this->table,
                                       'id' => $id,
                                       'return' => 'records' ) );
        $recordID = $rec->getRecordId();
      }
    
      $editCmd = $this->newEditCommand( $this->layout(), $recordID );
      foreach ( $options['data'] as $key => $value ) {
        $editCmd->setField( $key, $value );
      }
      $result = $editCmd->execute();
    }
  }
  
  //============================================================================
  public function getFirstRecord( $result ) {
    $recs = $result->getRecords();
    return $recs[0];
  }

  //============================================================================
  private function ___PROTECTED_FUNCTIONS() {}
  //============================================================================

  //============================================================================
  // Return the layout for the current table.
  protected function layout() {
    return $this->tables[$this->table]['layout'];
  }
  
  //============================================================================
  // Return the key field for the current table.
  protected function key() {
    return $this->tables[$this->table]['key'];
  }
  
  //============================================================================
  // When a parameter is sent as an array of options, this function confirms
  //   that it conforms to the required format. The format is itself specified
  //   as an array with two members: 'required', and 'optional'. All the
  //   required parameters should be present in $params and all of the params
  //   should exist in either 'required' or 'optional'.
  protected function checkParams( $format, $params ) {
    return true;
  }
}
?>