<?php

/**
 * @package Percolate_Import_4
 *  AJAX methods
 */

/**
 * Class Percolate_AJAX_Model
 * Model to provide an AJAX interface
 */
class Percolate_AJAX_Model
{
  protected $option = 'PercV4Opt';

  protected $ACF;

  protected $Percolate;

  protected $Messages;

  // Singleton instance
  private static $instance = false;

  /**
   * Return singleton instance
   * @return Percolate_AJAX_Model
   */
	public static function instance() {
		if( !self::$instance )
			self::$instance = new Percolate_AJAX_Model;

		return self::$instance;
	}

  public function __construct() {
    // ACF methods
    include_once(__DIR__ . '/percolate-acf.php');
    $this->ACF = Percolate_ACF_Model::instance();

    // WPML methods
    include_once(__DIR__ . '/percolate-wpml.php');
    $this->Wpml = Percolate_WPML::instance();

    // Percolate API methods
    include_once(__DIR__ . '/percolate-api.php');
    $this->Percolate = Percolate_API_Model::instance();

    // Messages
    include_once(__DIR__ . '/percolate-messages.php');
    $this->Messages = PercolateMessages::instance();

    // Logging
    include_once(__DIR__ . '/percolate-log.php');
    $this->Log = Percolate_Log::instance();

    // Queue
    include_once(__DIR__ . '/percolate-queue.php');
    $this->Queue = Percolate_Queue::instance();

    // Serve templates to Angular
    add_action( 'wp_ajax_template', array( $this, 'getTemplate' ) );
    // Get the Percolate data model
    add_action( 'wp_ajax_get_data', array( $this, 'getData' ) );
    // Save the Percolate data model
    add_action( 'wp_ajax_set_data', array( $this, 'setData' ) );
    // Get WP categories
    add_action( 'wp_ajax_get_cpts', array( $this, 'getCpts' ) );
    // Get WP post types
    add_action( 'wp_ajax_get_categories', array( $this, 'getCategories' ) );
    // Get WP users
    add_action( 'wp_ajax_get_users', array( $this, 'getUsers' ) );
    // Is ACF active
    add_action( 'wp_ajax_get_acf_status', array( $this, 'getAcfStatus' ) );
    // Get ACF data
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
    $res = get_option( $this->option );
    echo $res;
    wp_die();
  }

  /**
   * Save plugin data
   */
  public function setData()
  {
    if( isset($_POST['data']) ) {

      //  clear the options cache before trying to get or update the options
      wp_cache_delete ( 'alloptions', 'options' );

      $update = update_option( $this->option, json_encode($_POST['data']) );

      $res = array(
        'success' => $update
      );
      echo json_encode($res);
    }
  	wp_die();
  }

  /**
   * Retrieve templates for Angular
   */
  public function getTemplate()
  {
    if( isset($_POST['template']) ) {
      include_once(dirname(__DIR__) . '/views/templates/' . $_POST['template'] . '.php');
    }
  	wp_die();
  }

  /**
   * Get WP categories
   */
  public function getCategories()
  {
    global $sitepress;
    if ($sitepress) {
      // if WPML is active, remove its filter for querying in active language only
      remove_filter('terms_clauses', array($sitepress, 'terms_clauses'));
      remove_filter('get_terms', array($SitePress,'get_terms_filter'));
    }
    $args = array(
    	'hide_empty'               => 0,
    	'hierarchical'             => 1,
    	'taxonomy'                 => 'category'
    );
    $res = get_categories( $args );

    if ($sitepress) {
      // add language property to categories
      foreach ($res as $category) {
        $language_code = apply_filters( 'wpml_element_language_code', null, array(
          'element_id'=> (int)$category->term_id,
          'element_type'=> 'category'
        ));
        $category->language = $language_code;
      }
      // re-enable the filter
      add_filter('terms_clauses', array($sitepress, 'terms_clauses'));
      add_filter('get_terms', array($SitePress,'get_terms_filter'));
    }
    Percolate_Log::log('Categroies: ' . print_r($res, true));
    echo json_encode($res);
    wp_die();
  }
  /**
   * Get WP users
   */
  public function getUsers()
  {
    $args = array(
    	// 'role' => 'Administrator'
    );
    $res = get_users( $args );
    echo json_encode($res);
    wp_die();
  }

  /**
   * Get WP post types
   */
  public function getCpts()
  {
    $args = array(
     'public'   => true
    );
    $res = get_post_types( $args, 'objects' );
    echo json_encode($res);
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


  /* ---------------------------------------------------------------
   * --------------------------------------------------------------- */

  /**
   * Percolate: get User
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
