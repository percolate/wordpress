<?php

/**
 * @package Percolate_Importer
 *  API methods
 */

/**
 * Class Percolate_Queue_Model
 * Model to handle post syncing and transitioning
 */
class Percolate_Queue_Model
{

  public function __construct(
    Percolate_WP_Model $percolate_WP_Model
  ){
    $this->Wp = $percolate_WP_Model;
  }

  /**
   * Get the saved events from the DB
   *
   * @return array Events
   */
  public function getEvents()
  {
    $events = $this->Wp->getEventsData();
    return $events;
  }

  /**
   * Save the events to the DB
   *
   * @param array $events all the events
   * @return bool true
   */
  public function setEvents( $events )
  {
    $success = $this->Wp->setEventsData($events);
    return $success;
  }

  /**
   * Adds an event
   *
   * @param array $event event to add
   * @return array events
   */
  public function addEvent( $event = array() )
  {
     $events = $this->Wp->getEventsData();

     if( !$events || empty($events) ) {
       $events = array(
         "postToTransition" => new stdClass()
       );
       $this->Wp->setEventsData($events);
       $events = $this->Wp->getEventsData();
     }

     $events->postToTransition->{$event['ID']} = $event;
     $this->Wp->setEventsData($events);

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
     $this->Wp->setEventsData($events);

     return array('sucess' => true );
  }

}
