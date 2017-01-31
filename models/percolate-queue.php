<?php

/**
 * @package Percolate_Import_4
 *  API methods
 */

/**
 * Class Percolate_Queue
 * Model to handle queues for CRON jobs
 */
class Percolate_Queue
{

  protected $option = 'PercV4Opt';
  protected $optionEvents = 'PercV4Events';


  // Singleton instance
  private static $instance = false;

  /**
   * Return singleton instance
   * @return Percolate_Log
   */
	public static function instance()
  {
		if( !self::$instance )
			self::$instance = new Percolate_Queue;

		return self::$instance;
	}


  public function __construct()
  {
    // Logging
    include_once(__DIR__ . '/percolate-log.php');
    $this->Log = Percolate_Log::instance();

    // Percolate API methods
    include_once(__DIR__ . '/percolate-api.php');
    $this->Percolate = Percolate_API_Model::instance();

  }


  /**
   * Get the saved events from the DB
   *
   * @return array of events
   */
  public function getEvents()
  {
    $events = json_decode( get_option( $this->optionEvents ) );
    // Percolate_Log::log('Events' . print_r($events, true));
    return $events;
  }


  /**
   * Save the events to the DB
   *
   * @param array $events: all the events
   * @return bool true or false
   */
  private function setEvents( $events )
  {
    update_option( $this->optionEvents, json_encode($events) );
    return true;
  }


  /**
   * Adds an event
   *
   * @param array $event: event to add
   * @return array: events
   */
  public function addEvent( $event = array() )
  {
     $events = $this->getEvents();

     if( !$events || empty($events) ) {
       $events = array(
         "postToTransition" => new stdClass()
       );
       $this->setEvents($events);
       $events = $this->getEvents();
     }

     $events->postToTransition->{$event['ID']} = $event;
     $this->setEvents($events);

     return $events;
  }

  /**
   * Delete all event
   *
   * @return array: success
   */
  public function deleteEvents()
  {
     $events = array(
       "postToTransition" => new stdClass()
     );
     $this->setEvents($events);

     return array('sucess' => true );
  }


  /**
   * Get the plugin's saved channels
   *
   * @return string: JSON string of channels
   */
  public function getChannels()
  {
    $option = get_option( $this->option );
    return $option;
  }


  /**
   * Method for checking all future posts
   *
   * @return bool success or failure
   */
  public function syncPosts()
  {
    Percolate_Log::log('Sync Posts hook.');

    $events = $this->getEvents();
    Percolate_Log::log('Events: ' . print_r($events, true) . " Current time: " . time());

    if( !isset($events->postToTransition) || empty($events->postToTransition) ) {
      return false;
    }

    /*
     * Check if post transitionin is in progress, b/c we don't want to send duplicate events to Perc
     *   Also we need to count the CRON cycles since it's running, just in case something went wrong.
     *   It resets after 5 cycles and does the transitioning again.
     */
    if( isset($events->transitionInProgress) && filter_var( $events->transitionInProgress, FILTER_VALIDATE_BOOLEAN) ) {
      // filter_var for making sure we get the right value from the JSON
      //   http://stackoverflow.com/a/15075609/4074266
      if( isset($events->inTransitionCycle) ) {
        $events->inTransitionCycle = intval($events->inTransitionCycle) + 1;
      } else {
        $events->inTransitionCycle = 0;
      }
      $this->setEvents($events);
      if( isset($events->inTransitionCycle) && intval($events->inTransitionCycle) > 1 ) {
        Percolate_Log::log('Sync Posts hook: oops, looks like it is stuck, restarting...');
        $events->inTransitionCycle = 0;
        $this->setEvents($events);
      } else {
        Percolate_Log::log('Sync Posts hook: posts are syncing.');
        return false;
      }
    }

    // Start transitioning
    $events->transitionInProgress = true;
    $this->setEvents($events);

    foreach ($events->postToTransition as $key => $event) {
      Percolate_Log::log('Current post status in WP: ' . get_post_status($event->ID) . ($event->draft == 'yes' && get_post_status($event->ID) == 'publish'));

      // Post is TRASHED
      if (get_post_status($event->ID) == 'trash' || get_post_status($event->ID) == false) {
        Percolate_Log::log('Removed trashed post from queue, WP ID: ' . $event->ID);
        unset($events->postToTransition->{$key});
        $this->setEvents($events);
      }

      // Post needs to sync
      if ( isset($event->sync) && filter_var( $event->sync, FILTER_VALIDATE_BOOLEAN) ) {

      }

      // Post is going LIVE
      if( (isset($event->dateUTM) && time() > $event->dateUTM) || ($event->draft == 'yes' && get_post_status($event->ID) == 'publish') ) {
        Percolate_Log::log('Transitioning post: ' . $event->ID);
        $res = $this->transitionSinglePost( $event );

        // Remove the transitioned item from the DB
        if($res) {
          unset($events->postToTransition->{$key});
          $this->setEvents($events);
        }
      }
    }

    $events->transitionInProgress = false;
    $this->setEvents($events);

    return true;
  }


  /**
   * Method for catching when a post gets published
   *   -> it updates the status in Percolate
   *
   * @param object $event: post event - schemas/DB-PercV4Events.json
   * @return bool success or failure
   */
  public function transitionSinglePost( $event )
  {
    $post_id = $event->ID;
    Percolate_Log::log('Post transition event, post WP ID:' . $post_id);

    $postPercID = get_post_meta($post_id, 'percolate_id', true);
    if(!isset($postPercID) || empty($postPercID)) { return false; }

    // ------------- Post status -------------
    $postPerc = $this->getPost($post_id, $postPercID);
    Percolate_Log::log('Post current status:' . $post['status']);

    switch ($postPerc['status']) {
      case 'draft':
        $this->transitionPostApiCall( $post_id, $postPerc, 'queued' );
        $this->transitionPostApiCall( $post_id, $postPerc, 'queued.publishing' );
        $this->transitionPostApiCall( $post_id, $postPerc, 'queued.published' );
        break;
      case 'queued':
        $this->transitionPostApiCall( $post_id, $postPerc, 'queued.publishing' );
        $this->transitionPostApiCall( $post_id, $postPerc, 'queued.published' );
        break;
      case 'queued.publishing':
        $this->transitionPostApiCall( $post_id, $postPerc, 'queued.published' );
        break;
    }
    $res = $this->transitionPostApiCall( $post_id, $postPerc, 'live', $event->dateUTM );
    return $res;
  }


  /**
   * Method for syncing post data from Percolate to WP
   *
   * @param object $event: post event - schemas/DB-PercV4Events.json
   * @return bool success or failure
   */
  public function syncSinglePost($event)
  {


  }

  /**
   * Method for syncing post data from Percolate to WP
   *
   * @param object $event: post event - schemas/DB-PercV4Events.json
   * @return string: Percolate API key
   */
  private function getChannelKey($wp_post_id)
  {
    // Get the UUID of the WP-Perc channel that imported the post
    $wpChannelUuid = get_post_meta($wp_post_id, 'wp_channel_uuid', true);
    if( empty($wpChannelUuid)) {
      Percolate_Log::log("No channel UUID found for post {$wp_post_id}.");
      return false;
    }

    // Get the plugin options from DB
    $option = json_decode( $this->getChannels() );
    if( !isset($option->channels) ) {
      Percolate_Log::log('No channels were found, exiting.');
      return false;
    }

    Percolate_Log::log("Post's original importing channel found.");
    return $option->channels->{$wpChannelUuid}->key;
  }


  /**
   * Calls the Percolate API to get the post status by ID
   *
   * @param string $wp_post_id: WP post ID
   * @param string $postPercID: Percolate post ID
   * @param object $postsChannel: plugin's channel that's originally imported the post
   *
   * @return arrray post status
   */
  private function getPost($wp_post_id, $postPercID)
  {
    $key    = $this->getChannelKey($wp_post_id);
    $method = "v5/post/" . $postPercID;
    $fields = array();

    $res = $this->Percolate->callAPI($key, $method, $fields);

    if(!isset($res['data'])) {
      Percolate_Log::log('There was an error, check the API response.');
      return;
    }

    return $res['data'];
  }


  /**
   * Calls the Percolate API to transition the post
   *
   * @param string $wp_post_id: WP post ID
   * @param array $postPerc: Percolate post
   * @param object $postsChannel: plugin's channel that's originally imported the post
   * @param string $status: status to transition the post to
   *
   * @return array API response
   */
  private function transitionPostApiCall( $wp_post_id, $postPerc, $status='live', $dateUTM=NULL )
  {
    $key    = $this->getChannelKey($wp_post_id);
    $method = "v5/post/" . $postPerc['id'];
    $fields = array();
    $jsonFields = array(
      'topic_ids' => $postPerc['topic_ids'],
      'term_ids' => $postPerc['term_ids'],
      'ext' => $postPerc['ext'],
      'description' => $postPerc['description'],
      'name' => $postPerc['name'],
      'status' => $status
    );
    if( isset($dateUTM) ) {
      $jsonFields['live_at'] = date(DATE_RFC3339, $dateUTM);
    }

    $res = $this->Percolate->callAPI($key, $method, $fields, $jsonFields, 'PUT');

    if(!isset($res['data'])) {
      Percolate_Log::log('There was an error, API response: ' . print_r($res, true));
      return;
    }

    update_post_meta($wp_post_id, 'percolate_status', $res['data']['status']);
    Percolate_Log::log('Post '. $wp_post_id .' was transitioned to ' . $status);

    return $res;
  }


  private function updatePreviewLinks( $event )
  {
    # code...
  }

}
