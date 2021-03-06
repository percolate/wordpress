<?php

/**
 * @package Percolate_Importer
 *  WPML methods
 */

/**
 * Class Percolate_WPML_Model
 * Model to provide an WPML related methods
 */
class Percolate_WPML_Model
{

  public function __construct() {
    add_action( 'plugins_loaded', array( $this, 'getWpmlStatus' ) );
  }

  /**
   * WPML: check if plugin is active
   */
  public function getWpmlStatus()
  {
    // based on: https://wpml.org/forums/topic/how-to-check-if-wpml-is-installed-and-active/
    if ( function_exists('icl_object_id') ) {
      $this->isWpmlActive = true;
    } else {
      $this->isWpmlActive = false;
    }

    // Percolate_Log::log('WPML get status: ' . $this->isWpmlActive);
    return $this->isWpmlActive;
  }

  public function isActive()
  {
    return $this->isWpmlActive;
  }

  public function getDefaultLanguage()
  {
    // based on: https://wpml.org/forums/topic/api-to-get-the-default-language/
    global $sitepress;
    return $sitepress->get_default_language();
  }

  public function getLanguages()
  {
    $languages = apply_filters( 'wpml_active_languages', NULL );
    return $languages;
  }


}
