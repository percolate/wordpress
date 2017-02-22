<?php

/**
 * @package Percolate_Importer
 *  POST methods
 */

/**
 * Class Percolate_Messages
 * Model to handle warning messages
 */
class Percolate_Messages
{

  protected $optionMessages = 'PercV4Messages';

  protected $structure = array(
    'warning' => array(
      // 'active'  => true,
      // 'message' => 'Schema:xxxx has a new version, please update your channels!',
      // 'debug'   => array(
      //   'schemaNew' => $schema
      // )
    )
  );

  public function __construct() {
  }

  public function getMessages()
  {
    /**
     * Get the saved messages from the DB
     *
     * @return array of messages
     */
    $messages = json_decode( get_option( $this->optionMessages ) );
    return $messages;
  }

  public function setMessages( $messages )
  {
    /**
     * Save the messages to the DB
     *
     * @param array $messages: all the messages
     * @return bool true or false
     */
    update_option( $this->optionMessages, json_encode($messages) );
    return true;
  }

  public function addMessage( $message="", $debug=NULL, $type="warning" )
  {
    /**
     * Adds an message
     *
     * @param array $message: message to add
     * @return array: messages
     */

     $messages = $this->getMessages();

     if( !$messages || empty($messages) ) {
       $this->setMessages($structure);
       $messages = $this->getMessages();
     }

     $messages->{$type}[] = array(
       "active" => true,
       "message" => $message,
       "debug"  => isset($debug) ? $debug : NULL
     );
     $this->setMessages($messages);

     return $messages;
  }

}
