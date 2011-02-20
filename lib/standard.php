<?php
//==============================================================================
function vardump( $varArray ) {
  foreach ( $varArray as $var => $val ) {
    echo '<pre>' . $var . ' = ';
    var_dump( $val );
    echo '</pre>';
  }
}

//==============================================================================
function htmlLink( $url, $text ) {
  return '<a href="' . $url . '">' . $text . '</a>';
}

//==============================================================================
function isValidEmail( $email ) {
  return filter_var( $email, FILTER_VALIDATE_EMAIL )
    && preg_match( '/@.+\./', $email );
}
?>