<?php

/**
 * @package Percolate_Importer
 */

/**
 * Class Percolate_WP_Model
 * Model to provide to WP related functions
 */
class Percolate_WP_Model
{
  protected $option = 'PercV4Opt';


  public function __construct(
    Percolate_WPML_Model $percolate_WPML_Model
  ) {
    $this->Wpml = $percolate_WPML_Model;
  }


  /**
   * Get plugin data
   *
   * @return string JSON string
   */
  public function getData()
  {
    $options = get_option( $this->option );
    return $options;
  }


  /**
   * Save plugin data
   */
  public function setData()
  {
    if( isset($_POST['data']) ) {

      //  clear the options cache before trying to get or update the options
      wp_cache_delete ( 'alloptions', 'options' );

      $success = update_option( $this->option, json_encode($_POST['data']) );
      return $success;
    } else {
      return false;
    }
  }


  /**
   * Get WP categories
   */
  public function getCategories()
  {
    $args = array(
    	'hide_empty'               => 0,
    	'hierarchical'             => 1,
    	'taxonomy'                 => 'category'
    );

    if ($this->Wpml->isActive()) {

      $categories = array();
      $languages = $this->Wpml->getLanguages();

      foreach ($languages as $key => $language) {
        do_action( 'wpml_switch_language', $key );

        $categoriesPerLang = get_categories( $args );

        // add language property to categories
        foreach ($categoriesPerLang as $category) {
          $language_code = apply_filters( 'wpml_element_language_code', null, array(
            'element_id'=> (int)$category->term_id,
            'element_type'=> 'category'
          ));
          $category->language = $language_code;

          $categories[] = $category;
        }
      }

    } else {
      $categories = get_categories( $args );
    }

    // Percolate_Log::log('Categroies: ' . print_r($categories, true));
    return $categories;
  }


  /**
   * Get WP users
   */
  public function getUsers()
  {
    $args = array(
    	// 'role' => 'Administrator'
    );
    $users = get_users( $args );
    return $users;
  }


  /**
   * Get WP post types
   */
  public function getCpts()
  {
    $args = array(
     'public'   => true
    );
    $cpts = get_post_types( $args, 'objects' );
    return $cpts;
  }



}
