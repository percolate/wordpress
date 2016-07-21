<?php

/**
 * @package Percolate_Import_4
 *  API methods
 */

/**
 * Class Percolate_API_Model
 * Model to process API calls
 */
class Percolate_API_Model
{
  const API_BASE='https://percolate.com/api/';

  // Singleton instance
  private static $instance = false;

  /**
   * Return singleton instance
   * @return Percolate_API_Model
   */
	public static function instance() {
		if( !self::$instance )
			self::$instance = new Percolate_API_Model;

		return self::$instance;
	}

  public function __construct() {
    // Logging
    include_once(__DIR__ . '/percolate-log.php');
    $this->Log = Percolate_Log::instance();
  }


  public function callAPI ($api_key, $method, $fields=array(), $jsonFields=array(), $type="")
  {
    if( !isset($api_key) || !isset($method) ) {
      $res = 'Invalid request';
      Percolate_Log::log($res);
      return $res;
    }

    // URL of the call
    $url = self::API_BASE . "$method";

    $req = array(
      'method' => 'GET',
      'headers' => array("Content-type" => "application/json", "Authorization" => $api_key),
      'timeout' => 45,
    	'redirection' => 5,
    	'blocking' => true
    );

    // GET: make URL from fields
    if ($fields) {
      $tokens = array();
      foreach ($fields as $key=>$val) {
        $tokens[]="$key=$val";
      }
      $url .= "?" . implode('&', $tokens);
    }

    // POST: json post fields
    if ($jsonFields) {
      $req['method'] = 'POST';
      $req['body'] = json_encode($jsonFields);
    }

    // Custom CRUD
    if( $type != "" ) {
      $req['method'] = $type;
      $req['headers'] = array('Content-Type' => 'application/json', "Authorization" => $api_key, "Content-Length" => strlen(json_encode($jsonFields)));
      Percolate_Log::log("API: Custom CRUD, url: {$url}, req: " . print_r($req, true));
    }

    $res = wp_remote_request( $url, $req);

    if ( is_wp_error( $res ) ) {
       $error_message = $res->get_error_message();
       Percolate_Log::log($error_message);
       return;
    }

    $status = intval(wp_remote_retrieve_response_code($res));
    $data = json_decode( wp_remote_retrieve_body($res), true );

    if ($status != 200) {
      $message = "An unknown error occurred communicating with Percolate ($status) - $res";
      if ($data) {
        if ($data['error']) {
          $message = $data['error'];
        }
        if (array_key_exists('request', $data)) {
          $message .= ' -- Request: '.$data['request'];
        }
      } else {
        $message = "No Data received.";
      }
      Percolate_Log::log($message);
      return $message;
    }

    // error_log(print_r($data, true));
    return $data;
  }

  public function getImageFromServer($src, $filename)
  {
    Percolate_Log::log(print_r('Getting image from server: ' . $src . ', filename: ' . $filename, true));

    $permfile = $filename;
    $tmpfile = download_url( $src, $timeout = 300 );

    if( is_wp_error($tmpfile) ) {
      Percolate_Log::log(print_r("Error downloading file, filename {$filename}, src {$src}.", true));
      return false;
    }

    copy( $tmpfile, $permfile );
    unlink( $tmpfile ); // must unlink afterwards

    // Set correct file permissions
    $stat = stat(dirname($filename));
    $perms = $stat['mode'] & 0000666;
    @ chmod( $filename, $perms );

    return true;
  }

}
