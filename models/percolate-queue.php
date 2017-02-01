<?php

/**
 * @package Percolate_Importer
 *  API methods
 */

/**
 * Class Percolate_Queue
 * Model to handle post syncing and transitioning
 */
class Percolate_Queue
{

  protected $option = 'PercV4Opt';
  protected $optionEvents = 'PercV4Events';


  public function __construct(
    Percolate_API_Model $percolate_API_Model
  ){
    // Percolate API methods
    $this->Percolate = $percolate_API_Model;

    // Action for WP-Cron post transition
    add_action('percolate_sync_posts_event', array($this, 'syncPosts'));
  }


  /**
   * Get the saved events from the DB
   *
   * @return array Events
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
   * @param array $events all the events
   * @return bool true
   */
  private function setEvents( $events )
  {
    update_option( $this->optionEvents, json_encode($events) );
    return true;
  }


  /**
   * Adds an event
   *
   * @param array $event event to add
   * @return array events
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
   * @return array success
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
   * @return string JSON string of channels
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
        Percolate_Log::log('Syncing post: ' . $event->ID);
        $this->syncSinglePost($event);
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
   * Catching when a post gets published
   *   -> it updates the status in Percolate
   *
   * @param object $event post event - schemas/DB-PercV4Events.json
   * @return bool success or failure
   */
  public function transitionSinglePost( $event )
  {
    Percolate_Log::log('Post transition event, post WP ID:' . $event->ID);

    $postPercolate = $this->getExistingPost($event->ID);
    Percolate_Log::log('Post current status:' . $post['status']);

    switch ($postPercolate['status']) {
      case 'draft':
        $this->transitionPostApiCall( $event->ID, $postPercolate, 'queued' );
        $this->transitionPostApiCall( $event->ID, $postPercolate, 'queued.publishing' );
        $this->transitionPostApiCall( $event->ID, $postPercolate, 'queued.published' );
        break;
      case 'queued':
        $this->transitionPostApiCall( $event->ID, $postPercolate, 'queued.publishing' );
        $this->transitionPostApiCall( $event->ID, $postPercolate, 'queued.published' );
        break;
      case 'queued.publishing':
        $this->transitionPostApiCall( $event->ID, $postPercolate, 'queued.published' );
        break;
    }
    $res = $this->transitionPostApiCall( $event->ID, $postPercolate, 'live', $event->dateUTM );
    return $res;
  }


  /**
   * Syncing post data from Percolate to WP
   *
   * @param object $event post event - schemas/DB-PercV4Events.json
   * @return bool success or failure
   */
  public function syncSinglePost($event)
  {
    $post = $this->getExistingPost($event->ID);
    // $this->Post->updateExistingPost($event->ID, $post);
  }

  /**
   * Get the channel's API key
   *
   * @param string $wpPostID WP post ID
   * @return stdObject Stored WP channel data
   */
  private function getChannel($wpPostID)
  {
    // Get the UUID of the WP-Perc channel that imported the post
    $wpChannelUuid = get_post_meta($wpPostID, 'wp_channel_uuid', true);
    if( empty($wpChannelUuid)) {
      Percolate_Log::log("No channel UUID found for post {$wpPostID}.");
      return false;
    }

    // Get the plugin options from DB
    $option = json_decode( $this->getChannels() );
    if( !isset($option->channels) ) {
      Percolate_Log::log('No channels were found, exiting.');
      return false;
    }

    Percolate_Log::log("Post's original importing channel found.");
    return $option->channels->{$wpChannelUuid};
  }


  /**
   * Calls the Percolate API to get the post status by ID
   *
   * @param string $wpPostID WP post ID
   * @return arrray|false Post object from Percolate
   */
  private function getExistingPost($wpPostID)
  {
    $postPercolateID = get_post_meta($wpPostID, 'percolate_id', true);
    if(!isset($postPercolateID) || empty($postPercolateID)) {
      Percolate_Log::log('No Percolate ID found for this post. ' . $wpPostID);
      return false;
    }

    $key    = $this->getChannel($wpPostID)->key;
    $method = "v5/post/" . $postPercolateID;
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
   * @param string $wpPostID WP post ID
   * @param array $postPercolate Percolate post data
   * @param string $status Status to transition the post to
   * @param string $dateUTM live_at date in UTM, optional - if WP draft post goes live
   *
   * @return array API response
   */
  private function transitionPostApiCall( $wpPostID, $postPercolate, $status='live', $dateUTM=NULL )
  {
    $key    = $this->getChannel($wpPostID)->key;
    $method = "v5/post/" . $postPercolate['id'];
    $fields = array();
    $jsonFields = array(
      'topic_ids' => $postPercolate['topic_ids'],
      'term_ids' => $postPercolate['term_ids'],
      'ext' => $postPercolate['ext'],
      'description' => $postPercolate['description'],
      'name' => $postPercolate['name'],
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

    update_post_meta($wpPostID, 'percolate_status', $res['data']['status']);
    Percolate_Log::log('Post '. $wpPostID .' was transitioned to ' . $status);

    return $res;
  }


  private function updatePreviewLinks( $event )
  {
    # code...
  }

}
