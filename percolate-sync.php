<?php
/**
 * @package Percolate_Importer
 */
/*
Plugin Name: WP Percolate Importer
Plugin URI: https://github.com/percolate/wordpress
Description: Percolate integration for Wordpress, which includes the ability to sync posts, media library elements and custom creative templates.
Author: Percolate Industries, Inc.
Version: 4.x-1.2.0
Author URI: http://percolate.com
*/

require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/models/percolate-acf.php');
require_once(__DIR__ . '/models/percolate-log.php');
require_once(__DIR__ . '/models/percolate-api.php');
require_once(__DIR__ . '/models/percolate-messages.php');
require_once(__DIR__ . '/models/percolate-ajax.php');
require_once(__DIR__ . '/models/percolate-post.php');
require_once(__DIR__ . '/models/percolate-queue.php');
require_once(__DIR__ . '/models/percolate-media.php');
require_once(__DIR__ . '/models/percolate-updater.php');
require_once(__DIR__ . '/models/percolate-wpml.php');

class Percolate_Setup
{
  /**
   * Class constructor
   */
  public function __construct(
    Percolate_Log $percolate_Log,
    Percolate_Post_Model $percolate_Post_Model,
    PercolateMedia $percolateMedia,
    Percolate_AJAX_Model $percolate_AJAX_Model
  ) {

    $this->Post = $percolate_Post_Model;

    // GitHub updater
    new Percolate_GitHubPluginUpdater( __FILE__, 'percolate', 'wordpress' );

    // WP Plugin methods
    register_activation_hook(__FILE__, array($this, '__activation'));
    register_deactivation_hook(__FILE__, array($this, '__deactivation'));

    // Add settings page
    add_action('admin_menu', array($this, 'register_settings_page'));

    // Add admin scripts
    add_action('admin_enqueue_scripts', array( $this, 'addAdminScripts' ));

    // Add Angular's tags to header
    add_action('wp_head', array( $this, 'setupHeader' ));

    // Add custom Cron schedules
    add_filter('cron_schedules', array( $this, 'cron_update_schedules' ));
  }

  /**
   * Plugin activation logic
   */
  public function __activation() {
    Percolate_Log::log('Percolate Importer plugin activated.');
    // Activate the WP Cron task for importing posts
    $this->Post->activateCron();
  }

  /**
   * Plugin activation logic
   */
  public function __deactivation() {
    Percolate_Log::log('Percolate Importer plugin deactivated.');
    // Dectivate the WP Cron task for importing posts
    $this->Post->deactivateCron();
  }

  /**
   * Settings page registration logic
   */
  public function register_settings_page () {
    if (current_user_can('manage_options')) { // admin management options

      add_menu_page(
        'Percolate WordPress Importer',
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


  /**
   * Register all scripts for admin page
   */
  public function addAdminScripts () {

    $scripts = array();
    $scripts[] = array(
    	'handle'	=> 'underscore',
    	'src'		  => plugins_url( '/public/lib/underscore-1.8.3/underscore-min.js', __FILE__ ),
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
    	'handle'	=> 'PerolcateWP-App',
    	'src'		  => plugins_url( '/public/js/settings/app.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'PerolcateWP-Api',
    	'src'		  => plugins_url( '/public/js/api/api.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'PerolcateWP-Percolate',
    	'src'		  => plugins_url( '/public/js/api/percolate.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'PerolcateWP-UuidSrv',
    	'src'		  => plugins_url( '/public/js/settings/services/uuid.service.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'PerolcateWP-MainCtr',
    	'src'		  => plugins_url( '/public/js/settings/controllers/main.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'PerolcateWP-IndexCtr',
    	'src'		  => plugins_url( '/public/js/settings/controllers/index.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'PerolcateWP-AddCtr',
    	'src'		  => plugins_url( '/public/js/settings/controllers/add.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'PerolcateWP-AddSetupCtr',
    	'src'		  => plugins_url( '/public/js/settings/controllers/add.setup.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'PerolcateWP-AddTopicsCtr',
    	'src'		  => plugins_url( '/public/js/settings/controllers/add.topics.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'PerolcateWP-AddTemplatesCtr',
    	'src'		  => plugins_url( '/public/js/settings/controllers/add.templates.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'PerolcateWP-SettingsCtr',
    	'src'		  => plugins_url( '/public/js/settings/controllers/settings.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'PerolcateWP-LogCtr',
    	'src'		  => plugins_url( '/public/js/settings/controllers/log.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'PerolcateWP-LoaderDir',
    	'src'		  => plugins_url( '/public/js/settings/directives/loader.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );

    if ( is_admin() ) {
      // Only load scripts and styles in the admin

      foreach( $scripts as $script ) {
      	wp_enqueue_script( $script['handle'], $script['src'], $script['deps'], $script['version'], $script['footer']);
      }

      wp_localize_script( 'PerolcateWP-Api', 'ajax_object',
        array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
      wp_localize_script( 'PerolcateWP-Percolate', 'ajax_object',
        array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

      // ---------
      // Styles

      wp_enqueue_style( 'percolate-styles', plugins_url( '/public/styles/css/percolate-settings.css', __FILE__ ), null, '1', 'all' );
    }
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

$container = DI\ContainerBuilder::buildDevContainer();

$container->get('Percolate_Setup');

?>
