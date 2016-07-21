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

    // Percolate API methods
    include_once(__DIR__ . '/percolate-api.php');
    $this->Percolate = Percolate_API_Model::instance();

    // Messages
    include_once(__DIR__ . '/percolate-messages.php');
    $this->Messages = PercolateMessages::instance();

    // Logging
    include_once(__DIR__ . '/percolate-log.php');
    $this->Log = Percolate_Log::instance();
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
    $args = array(
    	'hide_empty'               => 0,
    	'hierarchical'             => 1,
    	'taxonomy'                 => 'category'
    );
    $res = get_categories( $args );
    echo json_encode($res);
    wp_die();
  }
  /**
   * Get WP users
   */
  public function getUsers()
  {
    $args = array(
    	'role' => 'Administrator'
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
