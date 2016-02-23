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


  public function callAPI ($api_key, $method, $fields=array(), $jsonFields=array())
  {
    if( !isset($api_key) || !isset($method) ) {
      $res = 'Invalid request';
      Percolate_Log::log($res);
      return $res;
    }
    // URL of the call
    $url = self::API_BASE . "$method";

    // GET: make URL from fields
    if ($fields) {
      $tokens = array();
      foreach ($fields as $key=>$val) {
        $tokens[]="$key=$val";
      }
      $url.="?" . implode('&', $tokens);
    }

    // initialize CURL
    $curl_handle = curl_init($url);
    curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl_handle, CURLOPT_HEADER, 0);
    curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array("Content-type: application/json", "Authorization: $api_key"));

    // POST: json post fields
    if ($jsonFields) {
      curl_setopt($curl_handle, CURLOPT_POSTFIELDS, json_encode($jsonFields));
    } else {
      // curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array('Expect:', "Authorization: $key"));
    }

    $res = curl_exec($curl_handle);
    $status = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
    curl_close($curl_handle);

    $data = json_decode( $res, true );

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
      // throw new Exception($message, $status);
      // return $message;
    }

    return $data;
  }

  public function getImageFromServer($src, $filename)
  {
    Percolate_Log::log(print_r('Getting image from server: ' . $src . ', filename: ' . $filename, true));
    /* get image by url */
    $curl_handle = curl_init($src);
    curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl_handle, CURLOPT_HEADER, 0);

    $f = fopen($filename, 'w');

    if($f !== false)
    {
      curl_setopt($curl_handle, CURLOPT_FILE, $f);
      curl_exec($curl_handle);
      fclose($f);

      // Set correct file permissions
      $stat = stat(dirname($filename));
      $perms = $stat['mode'] & 0000666;
      @ chmod( $filename, $perms );

      $res = true;

    } else {
      Percolate_Log::log(print_r("fopen error for filename {$filename}", true));
    }

    $status = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);

    curl_close($curl_handle);

    if ($status != 200) {

      $message = "An unknown error occurred communicating with Percolate ($status)";

      $res = false;

      Percolate_Log::log(print_r($message, true));
    }

    return $res;
  }

}
