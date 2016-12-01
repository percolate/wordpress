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

    if ( !is_dir($dir)) {
      wp_mkdir_p($dir);
    }

    if ( ! @file_exists($dir . DIRECTORY_SEPARATOR . 'index.php') ) {
      @touch( $dir . DIRECTORY_SEPARATOR . 'index.php' );
    }

    if ( ! @file_exists($dir . DIRECTORY_SEPARATOR . 'log') ) {
      @touch( $dir . DIRECTORY_SEPARATOR . 'log' );
    }
  }

  public static function log($msg='')
  {
    $uploads = wp_upload_dir();
    $dir = $uploads['basedir'] . DIRECTORY_SEPARATOR . self::LOGS_DIRECTORY;

    if ( is_dir($dir) && is_writable($dir) && is_writable($dir . DIRECTORY_SEPARATOR . self::LOG_FILE))
    {
      try {
        $date = date('d.m.Y h:i:s');
        error_log($date . " | " . $msg . "\n", 3, $dir . DIRECTORY_SEPARATOR . self::LOG_FILE);
      } catch (Exception $e) {
        error_log($msg);
      }
    } else {
      error_log($msg);
    }
  }

  public static function getLog()
  {
    $uploads = wp_upload_dir();
    $furi = $uploads['basedir'] . DIRECTORY_SEPARATOR . self::LOGS_DIRECTORY . DIRECTORY_SEPARATOR . self::LOG_FILE;
    if (  is_readable($furi)) {
      $file = file_get_contents( $furi );
      $res = array("success" => true, "log" => $file);
    } else {
      $res = array("success" => false);
    }
    return $res;
  }

  public static function deleteLog()
  {
    $uploads = wp_upload_dir();
    $fh = fopen( $uploads['basedir'] . DIRECTORY_SEPARATOR . self::LOGS_DIRECTORY . DIRECTORY_SEPARATOR . self::LOG_FILE , 'w' );
    fclose($fh);
  }

}
