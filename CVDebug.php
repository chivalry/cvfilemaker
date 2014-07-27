<?php
/**
 * An object-oriented version of my own debugging routines.
 *
 * @author Charles Ross <chivalry@mac.com>
 * @version 1.0
 * @copyright Charles Ross, 3 March, 2011
 **/
class CVDebug {
  static function vardump( $varArray, $echo = false ) {
    echo '<pre>';

    foreach ( $varArray as $var => $val ) {
      echo $var . ' = ';
      var_dump( $val );
    }
    
    echo '</pre>';
  }
}
?>