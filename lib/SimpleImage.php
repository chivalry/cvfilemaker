<?php
/**
 * File: SimpleImage.php
 * Author: Simon Jarvis
 * Copyright: 2006 Simon Jarvis
 * Date: 08/11/06
 * Link: http://www.white-hat-web-design.co.uk/articles/php-image-resizing.php
 * 
 * This program is free software; you can redistribute it and/or 
 * modify it under the terms of the GNU General Public License 
 * as published by the Free Software Foundation; either version 2 
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful, 
 * but WITHOUT ANY WARRANTY; without even the implied warranty of 
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the 
 * GNU General Public License for more details: 
 * http://www.gnu.org/licenses/gpl.html
 *
 * @copyright Simon Javis, 2006
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 * @link http://www.white-hat-web-design.co.uk/articles/php-image-resizing.php
 *
 **/
 
class SimpleImage {
  
  /**
   * a reference to the current working image
   *
   * @var resource
   **/
  var $image;
  
  /**
   * a constant referring to the image type
   *
   * @var  integer
   * @link http://www.php.net/manual/en/image.constants.php
   **/
  var $image_type;
  
  /**
   * save after each change if true
   *
   * @var boolean
   **/
  var $autoSave = false;

  function load( $filename ) {
  $image_info = getimagesize( $filename );
    $this->image_type = $image_info[2];
    
    if ( $this->image_type == IMAGETYPE_JPEG ) {
      $this->image = imagecreatefromjpeg( $filename );
      
    } elseif ( $this->image_type == IMAGETYPE_GIF ) {
      $this->image = imagecreatefromgif( $filename );
      
    } elseif ( $this->image_type == IMAGETYPE_PNG ) {
      $this->image = imagecreatefrompng( $filename );
    }
  }
  
  function save( $filename,
                 $image_type  = IMAGETYPE_JPEG,
                 $compression = 75,
                 $permissions = null ) {
    if ( $image_type == IMAGETYPE_JPEG ) {
      imagejpeg( $this->image, $filename, $compression );
     
    } elseif ( $image_type == IMAGETYPE_GIF ) {
      imagegif( $this->image, $filename );
     
    } elseif ( $image_type == IMAGETYPE_PNG ) {
      imagepng( $this->image, $filename );
    }
  
    if ( $permissions != null ) {
      chmod( $filename, $permissions );
    }
  }
   
  function output( $image_type = IMAGETYPE_JPEG ) {
    if ( $image_type == IMAGETYPE_JPEG ) {
      imagejpeg( $this->image );

    } elseif ( $image_type == IMAGETYPE_GIF ) {
      imagegif($this->image);         

    } elseif ( $image_type == IMAGETYPE_PNG ) {
      imagepng( $this->image );
    }   
  }
   
  function getWidth() {
    return imagesx( $this->image );
  }

  function getHeight() {
    return imagesy( $this->image );
  }

  function resizeToHeight( $height ) {
    $ratio = $height / $this->getHeight();
    $width = $this->getWidth() * $ratio;
    $this->resize( $width, $height );
  }

  function resizeToWidth( $width ) {
    $ratio = $width / $this->getWidth();
    $height = $this->getheight() * $ratio;
    $this->resize( $width, $height );
  }

  function scale( $scale ) {
    $width = $this->getWidth() * $scale / 100;
    $height = $this->getheight() * $scale / 100; 
    $this->resize( $width, $height );
  }

  function resize( $width, $height ) {
    $new_image = imagecreatetruecolor( $width, $height );
    imagecopyresampled( $new_image, $this->image, 0, 0, 0, 0, $width, $height,
      $this->getWidth(), $this->getHeight() );
    $this->image = $new_image;   
  }
  
  /**
   * Resize the image so that neither dimension is larger than the param.
   *
   * @param  integer $dim
   * @return void
   * @access public
   * @author Charles Ross
   **/
  function resizeToMaxSquare( $dim ) {
    $this->resizeToMax( $dim, $dim );
  }
  
  /**
   * Resize the image within the constraints provided.
   *
   * @param  integer $dimW the max width
   * @param  integer $dimW the max height
   * @return void
   * @access public
   * @author Charles Ross
   **/
  function resizeToMax( $dimW, $dimH ) {
    while ( $this->getWidth() > $dimW || $this->getHeight() > $dimH ) {
      if ( $this->getWidth() > $dimW ) {
        $this->resizeToWidth( $dimW );
      } else {
        $this->resizeToHeight( $dimH );
      }
    }
  }
}
?>