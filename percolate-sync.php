<?php
/**
 * @package Percolate_Importer
 */
/*
Plugin Name: WP Percolate Importer
Plugin URI: https://github.com/percolate/wordpress
Description: Percolate integration for Wordpress, which includes the ability to sync posts, media library elements and custom creative templates.
Author: Percolate Industries, Inc.
Version: 4.x-1.2.7
Author URI: http://percolate.com
*/

require_once(__DIR__ . '/api/vendor/autoload.php');

require_once(__DIR__ . '/api/models/percolate-acf-model.php');
require_once(__DIR__ . '/api/models/percolate-metabox-model.php');
require_once(__DIR__ . '/api/models/percolate-messages-model.php');
require_once(__DIR__ . '/api/models/percolate-queue-model.php');
require_once(__DIR__ . '/api/models/percolate-wp-model.php');
require_once(__DIR__ . '/api/models/percolate-wpml-model.php');
require_once(__DIR__ . '/api/models/percolate-post-model.php');

require_once(__DIR__ . '/api/helpers/percolate-helpers.php');

require_once(__DIR__ . '/api/services/percolate-api-service.php');
require_once(__DIR__ . '/api/services/percolate-ajax-service.php');
require_once(__DIR__ . '/api/services/percolate-media-service.php');
require_once(__DIR__ . '/api/services/percolate-importer-service.php');
require_once(__DIR__ . '/api/services/percolate-sync-service.php');
require_once(__DIR__ . '/api/services/percolate-updater-service.php');
require_once(__DIR__ . '/api/services/percolate-log-service.php');



class Percolate_Setup
{
  /**
   * Class constructor
   */
  public function __construct(
    Percolate_Log $percolate_Log,
    Percolate_Media $Percolate_Media,
    Percolate_Importer_Service $percolate_Importer_Service,
    Percolate_Sync_Service $percolate_Sync_Service,
    Percolate_AJAX_Service $Percolate_AJAX_Service
  ) {

    $this->Post = $percolate_Importer_Service;

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


    if(!get_option('PercV4hasAcceptMessage') ) {
        if(empty($_GET['hidePercV4Message'])) {
            function show_plugin_alert_activation_percolate()
            {
                $url = $_SERVER['REQUEST_URI'];
                $query = parse_url($url, PHP_URL_QUERY);
                if ($query) {
                    $url .= '&hidePercV4Message=1';
                } else {
                    $url .= '?hidePercV4Message=1';
                }
                ?>
                <div class="error notice">
                    <p>Percolate plugin has been updated to new version. You have to map <strong>all of taxonomy
                            AGAIN!</strong></p>
                    <p><a href="<?php echo $url; ?>">dismiss</a></p>
                </div>
                <?php
            }

            add_action('admin_notices', 'show_plugin_alert_activation_percolate');
        } else {
            function dismiss_notice_percov4()
            {
                if (isset($_GET['hidePercV4Message']))
                    update_option('PercV4hasAcceptMessage', 1);
            }

            add_action('admin_init', 'dismiss_notice_percov4');
        }
    }
  }

  /**
   * Plugin activation logic
   */
  public function __activation() {
    Percolate_Log::log('Percolate Importer plugin activated.');
    // Activate the WP Cron task for importing posts
    $this->Post->activateCron();

    update_option('PercV4hasAcceptMessage', 1);

    if($this->check_old_taxonomy()) {
        update_option('PercV4hasAcceptMessage', 0);
    }

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
        plugin_dir_url( __FILE__ ) . '/frontend/images/percolate-icon.png',
        81
      );

    }
  }

  public function renderSettings () {
    include_once(__DIR__ . '/frontend/views/settings/index.php');
  }

    /**
     * Check taxonomy version of plugin
     */
    public function check_old_taxonomy() {
        $option = json_decode( $this->Post->Wp->getData() );

        $showAlert = false;
        //in case of old version (taxonomy fixing)
        foreach($option->channels as $channel) {

            if(isset($channel->taxonomyMapping)) {
                $newTaxonomyMapping = [];

                foreach($channel->taxonomyMapping as $taxonomy) {

                    if(isset($taxonomy->taxonomyPercoKey)) {
                        array_push($newTaxonomyMapping, $taxonomy);
                    } else {
                        $showAlert = true;
                    }
                }

                if(count($newTaxonomyMapping) > 0) {
                    $channel->taxonomyMapping = $newTaxonomyMapping;
                } else {
                    unset($channel->taxonomyMapping);
                }

            }
        }

        if($showAlert) {
            wp_cache_delete ( 'alloptions', 'options' );

            $success = update_option( 'PercV4Opt', json_encode($option) );

            if($success) {
                Percolate_Log::log('Percolate database successfully update taxonomy.');
            } else {
                Percolate_Log::log('Error - Percolate plugin cant update database!');
            }
        }

        return $showAlert;
    }


  /**
   * Register all scripts for admin page
   */
  public function addAdminScripts () {

    $scripts = array();
    $scripts[] = array(
    	'handle'	=> 'underscore',
    	'src'		  => plugins_url( '/frontend/vendor/underscore-1.8.3/underscore-min.js', __FILE__ ),
    	'deps'		=> null,
      'version' => '1.8.3',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'velocity',
    	'src'		  => plugins_url( '/frontend/vendor/velocity-1.2.3/velocity.min.js', __FILE__ ),
    	'deps'		=> array('jquery'),
      'version' => '1.2.3',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'bootstrap',
    	'src'		  => plugins_url( '/frontend/vendor/bootstrap-sass-3.3.6/assets/javascripts/bootstrap.min.js', __FILE__ ),
    	'deps'		=> array('jquery'),
      'version' => '3.3.6',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'angular',
    	'src'		  => plugins_url( '/frontend/vendor/angular-1.4.8/angular.min.js', __FILE__ ),
    	'deps'		=> array('jquery'),
      'version' => '1.4.8',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'ngAnimate',
    	'src'		  => plugins_url( '/frontend/vendor/angular-1.4.8/angular-animate.min.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1.4.8',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'ui-router',
    	'src'		  => plugins_url( '/frontend/vendor/ui-router-0.2.15/angular-ui-router.min.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '0.2.15',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'PerolcateWP-App',
    	'src'		  => plugins_url( '/frontend/scripts/settings/app.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'PerolcateWP-Api',
    	'src'		  => plugins_url( '/frontend/scripts/api/api.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'PerolcateWP-Percolate',
    	'src'		  => plugins_url( '/frontend/scripts/api/percolate.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'PerolcateWP-UuidSrv',
    	'src'		  => plugins_url( '/frontend/scripts/settings/services/uuid.service.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'PerolcateWP-PaginationSrv',
    	'src'		  => plugins_url( '/frontend/scripts/settings/services/pagination.service.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'PerolcateWP-MainCtr',
    	'src'		  => plugins_url( '/frontend/scripts/settings/controllers/main.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'PerolcateWP-IndexCtr',
    	'src'		  => plugins_url( '/frontend/scripts/settings/controllers/index.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'PerolcateWP-AddCtr',
    	'src'		  => plugins_url( '/frontend/scripts/settings/controllers/add.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'PerolcateWP-AddSetupCtr',
    	'src'		  => plugins_url( '/frontend/scripts/settings/controllers/add.setup.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'PerolcateWP-AddTopicsCtr',
    	'src'		  => plugins_url( '/frontend/scripts/settings/controllers/add.topics.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'PerolcateWP-AddTemplatesCtr',
    	'src'		  => plugins_url( '/frontend/scripts/settings/controllers/add.templates.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'PerolcateWP-SettingsCtr',
    	'src'		  => plugins_url( '/frontend/scripts/settings/controllers/settings.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'PerolcateWP-LogCtr',
    	'src'		  => plugins_url( '/frontend/scripts/settings/controllers/log.js', __FILE__ ),
    	'deps'		=> array('angular'),
      'version' => '1',
      'footer'  => true
    );
    $scripts[] = array(
    	'handle'	=> 'PerolcateWP-LoaderDir',
    	'src'		  => plugins_url( '/frontend/scripts/settings/directives/loader.js', __FILE__ ),
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

      wp_enqueue_style( 'percolate-styles', plugins_url( '/frontend/styles/css/percolate-settings.css', __FILE__ ), null, '1', 'all' );
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

// Bootstrap the plugin with PHP-DI
$container = DI\ContainerBuilder::buildDevContainer();
$container->get('Percolate_Setup');

?>
