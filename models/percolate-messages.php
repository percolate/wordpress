<?php

/**
 * @package Percolate_Import_4
 *  POST methods
 */

/**
 * Class PercolateMessages
 * Model to handle warning messages
 */
class PercolateMessages
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

  // Singleton instance
  private static $instance = false;

  /**
   * Return singleton instance
   * @return Percolate_POST_Model
   */
	public static function instance() {
		if( !self::$instance )
			self::$instance = new PercolateMessages;

		return self::$instance;
	}

  public function __construct() {
    // Logging
    include_once(__DIR__ . '/percolate-log.php');
    $this->Log = Percolate_Log::instance();
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
