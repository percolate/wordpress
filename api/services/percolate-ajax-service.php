<?php

/**
 * @package Percolate_Importer
 *  AJAX methods
 */

/**
 * Class Percolate_AJAX_Service
 * Model to provide an AJAX interface
 */
class Percolate_AJAX_Service
{

  public function __construct(
    Percolate_Log $percolate_Log,
    Percolate_Messages $percolate_Messages,
    Percolate_ACF_Model $percolate_ACF_Model,
    Percolate_MetaBox_Model $percolate_MetaBox_Model,
    Percolate_WPML_Model $percolate_WPML_Model,
    Percolate_API_Service $Percolate_API_Service,
    Percolate_Queue_Model $percolate_Queue_Model,
    Percolate_WP_Model $percolate_WP_Model
  ) {
    $this->Log = $percolate_Log;
    $this->ACF = $percolate_ACF_Model;
    $this->MetaBox = $percolate_MetaBox_Model;
    $this->Wpml = $percolate_WPML_Model;
    $this->Percolate = $Percolate_API_Service;
    $this->Messages = $percolate_Messages;
    $this->Queue = $percolate_Queue_Model;
    $this->Wp = $percolate_WP_Model;

    // Serve templates to Angular
    add_action( 'wp_ajax_template', array( $this, 'getTemplate' ) );
    // Get the Percolate data model
    add_action( 'wp_ajax_get_data', array( $this, 'getData' ) );
    // Save the Percolate data model
    add_action( 'wp_ajax_set_data', array( $this, 'setData' ) );
    // Get WP categories
    add_action( 'wp_ajax_get_cpts', array( $this, 'getCpts' ) );
    // Get WP categories
    add_action( 'wp_ajax_get_categories', array( $this, 'getCategories' ) );
    // Get WP taxonomies
    add_action( 'wp_ajax_get_taxonomies', array( $this, 'getTaxonomies' ) );
    // Get WP terms
    add_action( 'wp_ajax_get_terms', array( $this, 'getTerms' ) );
    // Get WP users
    add_action( 'wp_ajax_get_users', array( $this, 'getUsers' ) );
    // Is ACF active
    add_action( 'wp_ajax_get_acf_status', array( $this, 'getAcfStatus' ) );
    // Get ACF data
    add_action( 'wp_ajax_get_metabox_data', array( $this, 'getMetaBoxData' ) );
    // Is MetaBox active
    add_action( 'wp_ajax_get_metabox_status', array( $this, 'getMetaBoxStatus' ) );
    // Get MetaBox data
    add_action( 'wp_ajax_get_acf_data', array( $this, 'getAcfData' ) );
    // Is WPML active
    add_action( 'wp_ajax_get_wpml_status', array( $this, 'getWpmlStatus' ) );
    // Get WPML default language
    add_action( 'wp_ajax_get_wpml_language', array( $this, 'getWpmlDefaultLanguage' ) );
    // Call the Percolate API
    add_action( 'wp_ajax_call_percolate', array( $this, 'callPercolateApi' ) );
    // Get the warning messages
    add_action( 'wp_ajax_get_messages', array( $this, 'getMessages' ) );
    // Set the warning messages
    add_action( 'wp_ajax_set_messages', array( $this, 'setMessages' ) );
    // Get the log
    add_action( 'wp_ajax_get_log', array( $this, 'getLog' ) );
    // Delete the log
    add_action( 'wp_ajax_delete_log', array( $this, 'deleteLog' ) );
    // Get the queue
    add_action( 'wp_ajax_get_queue', array( $this, 'getQueue' ) );
    // Delete the queue
    add_action( 'wp_ajax_delete_queue', array( $this, 'deleteQueue' ) );
  }

  /**
   * Get plugin data
   */
  public function getData()
  {
    $res = $this->Wp->getData();
    echo $res;
    wp_die();
  }

  /**
   * Save plugin data
   */
  public function setData()
  {
    $success = $this->Wp->setData();
    $res = array(
      'success' => $success
    );
    echo json_encode($res);
  	wp_die();
  }

  /**
   * Retrieve templates for Angular
   */
  public function getTemplate()
  {
    if( isset($_POST['template']) ) {
      include_once(dirname(dirname(__DIR__)) . '/frontend/views/templates/' . $_POST['template'] . '.php');
    }
  	wp_die();
  }

  /**
   * Get WP categories
   */
  public function getCategories()
  {
    $categories = $this->Wp->getCategories();
    echo json_encode($categories);
    wp_die();
  }

  /**
   * Get WP taxonomies
   */
  public function getTaxonomies()
  {
    $taxonomies = $this->Wp->getTaxonomies();
    echo json_encode($taxonomies);
    wp_die();
  }

  /**
   * Get WP terms
   */
  public function getTerms()
  {
    $terms = $this->Wp->getTerms();
    echo json_encode($terms);
    wp_die();
  }

  /**
   * Get WP users
   */
  public function getUsers()
  {
    $users = $this->Wp->getUsers();
    echo json_encode($users);
    wp_die();
  }

  /**
   * Get WP post types
   */
  public function getCpts()
  {
    $cpts = $this->Wp->getCpts();
    echo json_encode($cpts);
    wp_die();
  }

  /**
   * ACF: check if plugin is active
   */
  public function getAcfStatus()
  {
    $res =  $this->ACF->getAcfStatus();
    echo json_encode($res);
    wp_die();
  }

  /**
   * ACF: get field groups & fields
   */
  public function getAcfData()
  {
    $res = $this->ACF->getAcfData();
    echo json_encode($res);
    wp_die();
  }

  /**
   * MetaBox: check if plugin is active
   */
  public function getMetaBoxStatus()
  {
    $res =  $this->MetaBox->getStatus();
    echo json_encode($res);
    wp_die();
  }

  /**
   * MetaBox: get field groups & fields
   */
  public function getMetaBoxData()
  {
    $res = $this->MetaBox->getData();
    echo json_encode($res);
    wp_die();
  }

  /**
   * WPML: check if plugin is active
   */
  public function getWpmlStatus()
  {
    $res = $this->Wpml->isActive();
    echo json_encode($res);
    wp_die();
  }

  /**
   * WPML: check if plugin is active
   */
  public function getWpmlDefaultLanguage()
  {
    $res = $this->Wpml->getDefaultLanguage();
    echo json_encode($res);
    wp_die();
  }

  /**
   * WPML: get available languages
   */
  public function getWpmlLanguages()
  {
    $res = $this->Wpml->getLanguages();
    echo json_encode($res);
    wp_die();
  }

  /**
   * Get the warning messages
   */
  public function getMessages()
  {
    $res = $this->Messages->getMessages();
    echo json_encode($res);
    wp_die();
  }

  /**
   * Save the warning messages
   */
  public function setMessages()
  {
    $res = $this->Messages->setMessages();
    echo json_encode($res);
    wp_die();
  }

  /**
   * Get the Percolate log
   */
  public function getLog()
  {
    $res = $this->Log->getLog();
    echo json_encode($res);
    wp_die();
  }

  /**
   * Clear the Percolate log
   */
  public function deleteLog()
  {
    $res = $this->Log->deleteLog();
    echo json_encode($res);
    wp_die();
  }

  /**
   * Get the Percolate queue
   */
  public function getQueue()
  {
    $res = $this->Queue->getEvents();
    echo json_encode($res);
    wp_die();
  }

  /**
   * Clear the Percolate queue
   */
  public function deleteQueue()
  {
    $res = $this->Queue->deleteEvents();
    echo json_encode($res);
    wp_die();
  }

  /**
   * Call the Percolate API
   */
  public function callPercolateApi()
  {
    $res = $this->Percolate->callAPI(
      isset($_POST['data']['key']) ? $_POST['data']['key'] : null,
      isset($_POST['data']['method']) ? $_POST['data']['method'] : null,
      isset($_POST['data']['fields']) ? $_POST['data']['fields'] : null,
      isset($_POST['data']['$jsonFields']) ? $_POST['data']['$jsonFields'] : null
    );
    echo json_encode($res);
    wp_die();
  }
}
