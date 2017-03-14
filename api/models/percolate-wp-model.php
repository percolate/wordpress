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
  protected $optionEvents = 'PercV4Events';

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
   * Get the saved events from the DB
   *
   * @return array Events
   */
  public function getEventsData()
  {
    $events = json_decode( get_option( $this->optionEvents ) );
    // Percolate_Log::log('WP model: Get Events' . print_r($events, true));
    return $events;
  }


  /**
   * Save the events to the DB
   *
   * @param array $events all the events
   * @return bool true
   */
  public function setEventsData( $events )
  {
    update_option( $this->optionEvents, json_encode($events) );
    // Percolate_Log::log('WP model: Set Events' . print_r($events, true));
    return true;
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
   * Get custom taxonomies
   *
   * @return array|false Taxonomies
   */
  public function getTaxonomies()
  {
    $args = array(
      'public'   => true,
      '_builtin' => false

    );
    $taxonomies = get_taxonomies( $args, 'objects', 'and' );
    return $taxonomies;
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

  /**
   * Generate the posts preview link
   *
   * Works for custom post types too
   *
   * @param string $wpPostID WP post ID
   * @return string URL
   */
  public function generatePreviewLink($wpPostID)
  {
    return get_site_url() . "/?p=$wpPostID&preview=true";
  }



}
