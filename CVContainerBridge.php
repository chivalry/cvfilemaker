<?php
// Taken directly from the FileMaker tutorial for the PHP API, should be
//   integrated into the CVFileMaker class.

require_once( 'RAFileMaker.php' );
$fm = new RAFileMaker;

if ( isset( $_GET['path'] ) ) {
  $url = substr( $_GET['path'], 0, strpos( $_GET['path'], '?' ) );
  $url = substr( $url, strrpos( $url, '.' ) + 1 );
  switch( $url ) {
    case 'jpg':
      $contentType = 'image/jpeg';
      break;
    case 'gif':
      $contentType = 'image/gif';
      break;
    default:
      $contentType = 'application/octet-stream';
  }

  header( 'Content-type: ' . $contentType );
  echo $fm->getContainerData( $_GET['path'] );
}
?>