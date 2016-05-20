<?php
/**
 * @package Percolate_Import_4
 */
/*
Plugin Name: WP Percolate v4
Plugin URI: http://percolate.com
Description: Percolate integration for Wordpress, which includes the ability to sync posts, media library elements and custom creative templates.
Author: Percolate Industries, Inc.
Version: 4.0.2
Author URI: http://percolate.com

*/

class PercolateImportV4
{
  /* ---------------------------------
   *
   * Private and public variables
   *
   * --------------------------------- */

  // API methods
  protected $API;

  // AJAX interface
  protected $AJAX;

  // ACF interface
  protected $acf;

  // Media library
  protected $Media;

  // Singleton instance
  private static $instance = false;

  //Plugin file path
  const FILE = __FILE__;

  /* ---------------------------------
   *
   * Public methods
   *
   * --------------------------------- */

  /**
   * Return singleton instance
   * @return PercolateImportV4
   */
	public static function instance() {
		if( !self::$instance )
			self::$instance = new PercolateImportV4;

		return self::$instance;
	}

  /**
   * Class constructor
   */
  public function __construct() {
    if ( ! is_admin() ) {
      return false;
    }

    // Logging
    include_once(__DIR__ . '/models/percolate-log.php');
    $this->Log = Percolate_Log::instance();

    // API methods
    include_once(__DIR__ . '/models/percolate-api.php');
    $this->API = Percolate_API_Model::instance();

    // AJAX interface
    include_once(__DIR__ . '/models/percolate-ajax.php');
    $this->AJAX = Percolate_AJAX_Model::instance();

    // Post model
    include_once(__DIR__ . '/models/percolate-post.php');
    $this->Post = Percolate_POST_Model::instance();

    // Media library
    include_once(__DIR__ . '/models/percolate-media.php');
    $this->Media = PercolateMedia::instance();

    // GitHub updater
    require_once( __DIR__ . '/models/percolate-updater.php' );
    new Percolate_GitHubPluginUpdater( __FILE__, 'percolate', 'wordpress' );

    // WP Plugin methods
    register_activation_hook(self::FILE, array($this, '__activation'));
    register_deactivation_hook(self::FILE, array($this, '__deactivation'));

    // Add settings page
    add_action( 'admin_menu', array($this, 'register_settings_page') );

    // Add admin scripts
    add_action( 'admin_enqueue_scripts', array( $this, 'addAdminScripts' ) );

    // Add Angular's tags to header
    add_action('wp_head', array( $this, 'setupHeader' ));

    // Add custom Cron schedules
    add_filter('cron_schedules', array( $this, 'cron_update_schedules' ));

    // Serve templates to Angular
    add_action( 'wp_ajax_template', array( $this->AJAX, 'getTemplate' ) );
    // Get the Percolate data model
    add_action( 'wp_ajax_get_data', array( $this->AJAX, 'getData' ) );
    // Save the Percolate data model
    add_action( 'wp_ajax_set_data', array( $this->AJAX, 'setData' ) );
    // Get WP categories
    add_action( 'wp_ajax_get_cpts', array( $this->AJAX, 'getCpts' ) );
    // Get WP post types
    add_action( 'wp_ajax_get_categories', array( $this->AJAX, 'getCategories' ) );
    // Get WP users
    add_action( 'wp_ajax_get_users', array( $this->AJAX, 'getUsers' ) );
    // Is ACF active
    add_action( 'wp_ajax_get_acf_status', array( $this->AJAX, 'getAcfStatus' ) );
    // Get ACF data
    add_action( 'wp_ajax_get_acf_data', array( $this->AJAX, 'getAcfData' ) );
    // Call the Percolate API
    add_action( 'wp_ajax_call_percolate', array( $this->AJAX, 'callPercolateApi' ) );

    // Import posts for channel
    add_action( 'wp_ajax_do_import', array( $this->Post, 'importChannelPosts' ) );
    // Action for WP-Cron
    add_action('percolate_import_posts_event', array($this->Post, 'importStories'));

    // Import image into WP
    add_action( 'wp_ajax_image_import', array( $this->Media, 'importImageEndpoint' ) );

    // Percolate_Log::log('Percolate V4 constructed!');
  }

  /**
   * Plugin activation logic
   */
  public function __activation() {
    // Activate the WP Cron task for importing posts
    $this->Post->activateImport();
  }

  /**
   * Plugin activation logic
   */
  public function __deactivation() {
    // Dectivate the WP Cron task for importing posts
    $this->Post->deactivateImport();
  }

  /**
   * Settings page registration logic
   */
  public function register_settings_page () {
    if (current_user_can('manage_options')) { // admin management options

      add_menu_page(
        'Percolate WP Importer',
        'Percolate',
        'administrator', // or 'manage_options'
        'percolate-settings',
        array($this, 'renderSettings'),
        plugin_dir_url( __FILE__ ) . '/public/images/percolate-icon.png',
        81
      );

    }
  }

  public function renderSettings () {
    include_once(__DIR__ . '/views/settings/index.php');
  }

  /* ---------------------------------
   *
   * Private methods
   *
   * --------------------------------- */

   /**
    * Register all scripts for admin page
    */
  public function addAdminScripts () {

    $scripts = array();
    // $scripts[] = array(
    // 	'handle'	=> 'lodash',
    // 	'src'		  => plugins_url( '/lib/lodash-4.0.0/lodash.core.js', __FILE__ ),
    // 	'deps'		=> null,
    //   'version' => '4.0.0',
    //   'footer'  => true
    // );
    $scripts[] = array(
    	'handle'	=> 'underscore',
    	'src'		  => plugins_url( '/public/lib/lodash-1.8.3/underscore-min.js', __FILE__ ),
    	'deps'		=> null,
      'version' => '1.8.3',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'velocity',
    	'src'		  => plugins_url( '/public/lib/velocity-1.2.3/velocity.min.js', __FILE__ ),
    	'deps'		=> array('jquery'),
      'version' => '1.2.3',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'bootstrap',
    	'src'		  => plugins_url( '/public/lib/bootstrap-sass-3.3.6/assets/javascripts/bootstrap.min.js', __FILE__ ),
    	'deps'		=> array('jquery'),
      'version' => '3.3.6',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'angular',
    	'src'		  => plugins_url( '/public/lib/angular-1.4.8/angular.min.js', __FILE__ ),
    	'deps'		=> array('jquery'),
      'version' => '1.4.8',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'ngAnimate',
    	'src'		  => plugins_url( '/public/lib/angular-1.4.8/angular-animate.min.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1.4.8',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'ui-router',
    	'src'		  => plugins_url( '/public/lib/ui-router-0.2.15/angular-ui-router.min.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '0.2.15',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'app',
    	'src'		  => plugins_url( '/public/js/settings/app.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'Api',
    	'src'		  => plugins_url( '/public/js/api/api.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'Percolate',
    	'src'		  => plugins_url( '/public/js/api/percolate.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'UuidSrv',
    	'src'		  => plugins_url( '/public/js/settings/services/uuid.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'MainCtr',
    	'src'		  => plugins_url( '/public/js/settings/controllers/main.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'IndexCtr',
    	'src'		  => plugins_url( '/public/js/settings/controllers/index.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'AddCtr',
    	'src'		  => plugins_url( '/public/js/settings/controllers/add.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'AddSetupCtr',
    	'src'		  => plugins_url( '/public/js/settings/controllers/add.setup.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'AddTopicsCtr',
    	'src'		  => plugins_url( '/public/js/settings/controllers/add.topics.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'AddTemplatesCtr',
    	'src'		  => plugins_url( '/public/js/settings/controllers/add.templates.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'SettingsCtr',
    	'src'		  => plugins_url( '/public/js/settings/controllers/settings.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'LoaderDir',
    	'src'		  => plugins_url( '/public/js/settings/directives/loader.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );
    foreach( $scripts as $script ) {
    	wp_enqueue_script( $script['handle'], $script['src'], $script['deps'], $script['version'], $script['footer']);
    }

    wp_localize_script( 'Api', 'ajax_object',
      array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
    wp_localize_script( 'Percolate', 'ajax_object',
      array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

    // ---------
    // Styles

    wp_enqueue_style( 'percolate-styles', plugins_url( '/public/styles/css/percolate-settings.css', __FILE__ ), null, '1', 'all' );
  }

  public function setupHeader()
  {
    echo '<base href="/">';
  }

  public function cron_update_schedules()
  {
    return array(
        'every_30_min' => array('interval' => 1800, 'display' => 'Once in 30 minutes'),
        'every_15_min' => array('interval' => 900, 'display' => 'Once in 15 minutes'),
        'every_5_min' => array('interval' => 300, 'display' => 'Once in 5 minutes'),
        'every_min' => array('interval' => 60, 'display' => 'In every minute')
    );
  }
}

PercolateImportV4::instance();

?>
