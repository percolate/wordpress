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

  public function getEvents()
  {
    /**
     * Get the saved events from the DB
     *
     * @return array of events
     */
    $events = json_decode( get_option( $this->optionEvents ) );
    // Percolate_Log::log('Events' . print_r($events, true));
    return $events;
  }

  private function setEvents( $events )
  {
    /**
     * Save the events to the DB
     *
     * @param array $events: all the events
     * @return bool true or false
     */
    update_option( $this->optionEvents, json_encode($events) );
    return true;
  }

  public function addEvent( $event = array() )
  {
    /**
     * Adds an event
     *
     * @param array $event: event to add
     * @return array: events
     */

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

  public function deleteEvents()
  {
    /**
     * Adds an event
     *
     * @return array: events
     */

     $events = array(
       "postToTransition" => new stdClass()
     );
     $this->setEvents($events);

     return array('sucess' => true );
  }

  public function getChannels()
  {
    $option = get_option( $this->option );
    return $option;
  }

  /**
   * Method for checking all future posts
   */
  public function transitionPosts()
  {
    Percolate_Log::log('Transition Posts hook.');

    $events = $this->getEvents();
    Percolate_Log::log('Events: ' . print_r($events, true) . " Current time: " . time());

    if( !isset($events->postToTransition) || empty($events->postToTransition) ) {
      return false;
    }

    /*
     * Check if post transitionin is in progress, b/c we don't want to send duplicate events to Perc
     *   Also we need to count the CRON cycles, since it's running, just in case something went wrong.
     *   It resets after 5 cycles and does the transitioning again.
     */
    if( isset($events->transitionInProgress) && ($events->transitionInProgress == true || $events->transitionInProgress == 'true' || $events->transitionInProgress == 1) ) {
      if( isset($events->inTransitionCycle) ) {
        $events->inTransitionCycle = intval($events->inTransitionCycle) + 1;
      } else {
        $events->inTransitionCycle = 0;
      }
      $this->setEvents($events);
      if( isset($events->inTransitionCycle) && intval($events->inTransitionCycle) > 1 ) {
        Percolate_Log::log('Transition Posts hook: oops, looks like it is stuck, restarting...');
        $events->inTransitionCycle = 0;
        $this->setEvents($events);
      } else {
        Percolate_Log::log('Transition Posts hook: posts are transitioning.');
        return false;
      }
    }

    // Start transitioning
    $events->transitionInProgress = true;
    $this->setEvents($events);

    foreach ($events->postToTransition as $key => $event) {
      Percolate_Log::log('Current post status in WP: ' . get_post_status($event->ID) . ($event->draft == 'yes' && get_post_status($event->ID) == 'publish'));

      if (get_post_status($event->ID) == 'trash') {
        Percolate_Log::log('Removed trashed post from queue, WP ID: ' . $event->ID);
        unset($events->postToTransition->{$key});
        $this->setEvents($events);
      }

      if( (isset($event->dateUTM) && time() > $event->dateUTM) || ($event->draft == 'yes' && get_post_status($event->ID) == 'publish') ) {
        Percolate_Log::log('Transitioning post: ' . $event->ID);
        $res = $this->postTransition( $event );

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
   *   -> in that case we need to tell percolate the new post status
   */
  public function postTransition( $event )
  {
    /**
     * @param object $event: post event {ID: WP post ID, dateUTM: live_at date}
     * @return bool success or failure
     */

    $post_id = $event->ID;
    Percolate_Log::log('Post transition event, post WP ID:' . $post_id);

    $postPercID = get_post_meta($post_id, 'percolate_id', true);
    if(!isset($postPercID) || empty($postPercID)) { return false; }

    Percolate_Log::log('Post was published in WP. WP ID: ' . $post_id . '. Percolate ID:' . $postPercID . '. Calling Percolate API to transition status.');

    // ------------- Importing Channel -------------
    // Get the UUID of the WP-Perc channel that imported the post
    $wpChannelUuid = get_post_meta($post_id, 'wp_channel_uuid', true);
    if( empty($wpChannelUuid)) {
      Percolate_Log::log("No channel UUID found for post {$post_id}.");
      return false;
    }

    // Get the plugin options from DB
    $option = json_decode( $this->getChannels() );
    if( !isset($option->channels) ) {
      Percolate_Log::log('No channels were found, exiting.');
      return false;
    }

    $postsChannel = $option->channels->{$wpChannelUuid};
    Percolate_Log::log("Post's original importing channel found.");

    // ------------- Post status -------------
    $postStatus = $this->getPostStatus($post_id, $postPercID, $postsChannel);
    Percolate_Log::log('Post current status:' . $postStatus);

    switch ($postStatus) {
      case 'draft':
        $this->transitionPost( $post_id, $postPercID, $postsChannel, 'queued' );
        $this->transitionPost( $post_id, $postPercID, $postsChannel, 'queued.publishing' );
        $this->transitionPost( $post_id, $postPercID, $postsChannel, 'queued.published' );
        break;
      case 'queued':
        $this->transitionPost( $post_id, $postPercID, $postsChannel, 'queued.publishing' );
        $this->transitionPost( $post_id, $postPercID, $postsChannel, 'queued.published' );
        break;
      case 'queued.publishing':
        $this->transitionPost( $post_id, $postPercID, $postsChannel, 'queued.published' );
        break;
    }
    $res = $this->transitionPost( $post_id, $postPercID, $postsChannel, 'live', $event->dateUTM );
    return $res;
  }


  /* ------------------- Post transiting functions ----------------------- */
  private function getPostStatus($wp_post_id, $postPercID, $postsChannel)
  {
    /**
     * Calls the Percolate API to get the post status by ID
     *
     * @param string $wp_post_id: WP post ID
     * @param string $postPercID: Percolate post ID
     * @param object $postsChannel: plugin's channel that's originally imported the post
     *
     * @return string post status
     */
    $key    = $postsChannel->key;
    $method = "v5/post/" . $postPercID;
    $fields = array();

    $res = $this->Percolate->callAPI($key, $method, $fields);

    if(!isset($res['data'])) {
      Percolate_Log::log('There was an error, API response: ' . print_r($res, true));
      return;
    }

    return $res['data']['status'];
  }

  private function transitionPost( $wp_post_id, $postPercID, $postsChannel, $status='live', $dateUTM=NULL )
  {
    /**
     * Calls the Percolate API to transition the post
     *
     * @param string $wp_post_id: WP post ID
     * @param string $postPercID: Percolate post ID
     * @param object $postsChannel: plugin's channel that's originally imported the post
     * @param string $status: status to transition the post to
     *
     * @return array API response
     */
    $key    = $postsChannel->key;
    $method = "v5/post/" . $postPercID;
    $fields = array();
    $jsonFields = array(
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
  /* ----------------- End of post transiting functions ------------------- */

}
