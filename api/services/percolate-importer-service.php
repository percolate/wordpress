<?php

/**
 * @package Percolate_Importer
 */

/**
 * Class Percolate_Importer_Service
 */
class Percolate_Importer_Service
{

  public function __construct(
    Percolate_Queue $percolate_Queue,
    Percolate_API_Service $Percolate_API_Service,
    Percolate_WP_Model $percolate_WP_Model,
    Percolate_Messages $percolate_Messages,
    Percolate_Post_Model $percolate_Post_Model
  ) {
    $this->Wp = $percolate_WP_Model;
    $this->Percolate = $Percolate_API_Service;
    $this->Posts = $percolate_Post_Model;
    $this->Messages = $percolate_Messages;


    // AJAX endpoint
    add_action('wp_ajax_do_import', array( $this, 'importChannelPosts' ));

    // Action for WP-Cron import
    add_action('percolate_import_posts_event', array($this, 'importStories'));
  }


  /**
   * Adding the WP Cron job for importing posts
   *
   * @return void
   */
  public function activateCron(){
    Percolate_Log::log('WP Cron: percolate_import_posts_event activated');
    wp_schedule_event(time(), 'every_5_min', 'percolate_import_posts_event');

    Percolate_Log::log('WP Cron: percolate_sync_posts_event activated');
    wp_schedule_event(time()+1, 'every_min', 'percolate_sync_posts_event');
  }
  /**
   * Removing the WP Cron job for importing posts
   *
   * @return void
   */
  public function deactivateCron(){
    Percolate_Log::log('WP Cron: percolate_import_posts_event deactiveted');
    wp_clear_scheduled_hook('percolate_import_posts_event');

    Percolate_Log::log('WP Cron: percolate_sync_posts_event deactiveted');
    wp_clear_scheduled_hook('percolate_sync_posts_event');
  }

  /**
   * Endpoint for importing posts for the selected channel
   *
   * @return void
   */
  public function importChannelPosts()
  {
    if( isset($_POST['data']) ) {
      $option = json_decode( $this->Wp->getData() );
      $channel = $option->channels->{$_POST['data']};

      $res = $this->processChannel( $channel );
    }

    echo json_encode($res);
    wp_die();
  }

  /**
   * Endpoint for WP Cron to import posts
   *
   * @return void
   */
  public function importStories () {
    Percolate_Log::log('WP Cron: importing posts.');

    $option = json_decode( $this->Wp->getData() );
    if( !isset($option->channels) ) {
      Percolate_Log::log('No channels were found, exiting.');
      return;
    }

    foreach ($option->channels as $channel) {
      if($channel->active == 'true') {
        $res = $this->processChannel( $channel );
      }
    }

    return;
  }


  /**
   * Process the supplied channel and import its posts
   *
   * @param stdObject $channel
   * @return array Success messages
   */
  private function processChannel($channel)
  {
    $res = array(
      'success' => true,
      'messages' => array()
    );
    $schemas = $this->getSchemas($channel);

    $posts = $this->Posts->getAllPosts($channel);

    if( !is_array($posts) || empty($posts) ) {
      $res = array(
        'success' => false,
        'messages' => 'No posts were found for this channel: ' . $channel
      );
      return $res;
    }

    $postsBySchema = array();
    foreach ($posts as $post) {
      /* we have schema versioning now, and post[schema_id] contains the version too
       *  eg. schema:00000000_11111111
       */
      $postSchema = explode("_", $post['schema_id']);
      $postSchemaRoot = $postSchema[0];
      $postsBySchema[$postSchemaRoot][] = $post;
    }

    foreach ($schemas as $schema) {

      // Get the plugin's template (called channel on the frontend)
      $template = $channel->{$schema['id']};

      // Flag if there are multiple versions of the schame has been found
      $schemaVersionMismatch = false;

      if( empty($template) || $template->postType !== 'false' ) {

        if( !is_array($postsBySchema[$schema['id']]) || empty($postsBySchema[$schema['id']]) ) {
          Percolate_Log::log('No posts found for ' . $schema['id']);
        } else {
          Percolate_Log::log('Importing posts for: ' . print_r($template, true));

          foreach ($postsBySchema[$schema['id']] as $post) {
            $success = $this->Posts->importPost($post, $template, $schema, $channel);

            // Check if there is an updated template
            if( $success['success'] == true
                && isset($tempate->version)
                && $tempate->version != $post['schema_id']
                && !$schemaVersionMismatch )
            {
              $schemaVersionMismatch = true;
              $this->Messages->addMessage(SCHEMA_MISMATCH_MSG, $res_schema["data"]);
            }

            $res['messages'][] = $success;
          }
        }
      }
    }

    return $res;
  }

  /**
   * Get the schemas from Percolate for the given channel
   *
   * @param stdObject $channel
   * @return array Schemas
   */
  private function getSchemas($channel)
  {
    $key    = $channel->key;
    $method = "v5/schema/";
    $fields = array(
      'scope_ids' => 'license:' . $channel->license,
      'ext.platform_ids' => $channel->platform,
      'type' => 'post'
    );

    $res_schema = $this->Percolate->callAPI($key, $method, $fields);
    // Percolate_Log::log(print_r($res_schema, true));

    return $schemas = $res_schema["data"];
  }


}
