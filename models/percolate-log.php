<?php

/**
 * @package Percolate_Import_4
 *  API methods
 */

/**
 * Class Percolate_API_Model
 * Model to process API calls
 */
class Percolate_Log
{

  // Singleton instance
  private static $instance = false;

  const LOGS_DIRECTORY =  'percolate_logs';
  const LOG_FILE       =  'log';

  /**
   * Return singleton instance
   * @return Percolate_Log
   */
	public static function instance()
  {
		if( !self::$instance )
			self::$instance = new Percolate_Log;

		return self::$instance;
	}

  public function __construct() {
    $uploads = wp_upload_dir();
    $dir = $uploads['basedir'] . DIRECTORY_SEPARATOR . self::LOGS_DIRECTORY;

    if ( !is_dir($dir)) wp_mkdir_p($dir);

    if ( ! @file_exists($dir . DIRECTORY_SEPARATOR . 'index.php') ) @touch( $dir . DIRECTORY_SEPARATOR . 'index.php' );
  }

  public function log($msg='')
  {
    $uploads = wp_upload_dir();

    if ( is_dir($uploads['basedir'] . DIRECTORY_SEPARATOR . self::LOGS_DIRECTORY) and is_writable($uploads['basedir'] . DIRECTORY_SEPARATOR . self::LOGS_DIRECTORY))
    {
      $date = date('d.m.Y h:i:s');
      error_log($date . " | " . $msg . "\n", 3, $uploads['basedir'] . DIRECTORY_SEPARATOR . self::LOGS_DIRECTORY . DIRECTORY_SEPARATOR . self::LOG_FILE);
    } else {
      error_log($msg);
    }
  }

}
