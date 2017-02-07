<?php

/**
 * @package Percolate_Importer
 */

/**
 * Class Percolate_MetaBox_Model
 */
class Percolate_MetaBox_Model
{

  public function __construct() {
  }

  /**
   * Check if plugin is active
   *
   * @return bool Plugin is acive or not
   */
  public function getStatus()
  {
    if ( is_plugin_active( 'meta-box/meta-box.php' ) ) {
      echo true;
      wp_die();
    } else {
      echo false;
      wp_die();
    }
  }

  /**
   * Get all metaboxes
   *
   * @return array
   */
  public function getData()
  {
    // WP loads plugins alphabetically, should this class shall already exist
    $meta_boxes = RWMB_Core::get_meta_boxes();

    $res = array(
      'success' => true,
      'groups'  => $meta_boxes
    );

    return $res;
  }


}
