<?php
/**
 * @package Percolate_Import_4
 *   Media Library
 */


class PercolateMedia
{
  /* ---------------------------------
   *
   * Private and public variables
   *
   * --------------------------------- */

  // Singleton instance
  private static $instance = false;

  //Plugin file path
  const FILE = __FILE__;

  /* ---------------------------------
   *
   * Public methods
   *
   * --------------------------------- */

  /**
   * Return singleton instance
   * @return PercolateMedia
   */
	public static function instance() {
		if( !self::$instance )
			self::$instance = new PercolateMedia;

		return self::$instance;
	}

  /**
   * Class constructor
   */
  public function __construct() {
    // Logging
    include_once(__DIR__ . '/percolate-log.php');
    $this->Log = Percolate_Log::instance();

    // Percolate API methods
    include_once(__DIR__ . '/percolate-api.php');
    $this->Percolate = Percolate_API_Model::instance();

		// Add the media button
		add_action( 'media_buttons', array( $this, 'mediaButtons' ), 20 );

    // Add admin scripts
    add_action( 'admin_enqueue_scripts', array( $this, 'addAdminScripts' ) );

    // Load the html into the page
    add_action( 'admin_footer', array( $this, 'renderModal' ) );
	}

  /**
   * Register all scripts for admin page
   */
 public function addAdminScripts () {
  //  global $pagenow;
   // Only operate on edit post pages
  //  if( $pagenow != 'post.php' && $pagenow != 'post-new.php' ) {
  //    return;
  //  }


   $scripts = array();
   $scripts[] = array(
     'handle'	 => 'PercolateMedia',
     'src'		 => plugins_url( '/public/js/media/app.js', dirname(__FILE__) ),
     'deps'		 => array('angular'),
     'version' => '1',
     'footer'  => true
   );
   $scripts[] = array(
     'handle'	 => 'MediaCtr',
     'src'		 => plugins_url( '/public/js/media/controllers/media.js', dirname(__FILE__) ),
     'deps'		 => array('angular'),
     'version' => '1',
     'footer'  => true
   );
   $scripts[] = array(
     'handle'	 => 'LoaderDirM',
     'src'		 => plugins_url( '/public/js/media/directives/loader.js', dirname(__FILE__) ),
     'deps'		 => array('angular'),
     'version' => '1',
     'footer'  => true
   );

   foreach( $scripts as $script ) {
     wp_enqueue_script( $script['handle'], $script['src'], $script['deps'], $script['version'], $script['footer']);
   }

   // ---------
   // Styles

   wp_enqueue_style( 'percolate-styles-media', plugins_url( '/public/styles/css/percolate-media.css', dirname(__FILE__) ), null, '1', 'all' );
 }


	/**
	 * Add "Percolate Images" button to edit screen
   */
	public function mediaButtons( ) { ?>
		<a href="#" id="insert-percolate-button" class="button percolate-images-activate add_media"
			title="Percolate Library"><span class="percolate-media-buttons-icon"></span>Percolate Library</a>
	<?php
	}

  /**
	 * Render the modal into the footer
   */
  public function renderModal () {
    include_once(dirname(__DIR__) . '/views/media/modal.php');
  }

  /**
	 * AJAX action to import image as to Featured
   */
  public function importImageEndpoint($value='')
  {
    $res = array(
      'success' => false,
      'messages' => array()
    );

    /*
     * $_POST['data'] keys: $imageKey, $key, $postId, $featured
     */
    if( isset($_POST['data']) ) {
      $imageID = $this->importImageWP(array($_POST['data']['imageKey']), $_POST['data']['key']);
      if( $_POST['data']['featured'] === "true" && $imageID) {
        Percolate_Log::log('featured: ' . $imageID);
        set_post_thumbnail( $_POST['data']['postId'], $imageID );
      }
      if( $imageID ) {
        //  ----- Image size -------
        $size = 'full';
        if( isset($_POST['data']['size']) ) {
          $size = $_POST['data']['size'];
        }

        $src = wp_get_attachment_image( $imageID, $size );

        $res = array(
          'success'   => true,
          'messages'  => array('Image successfully imported: ' . $imageID),
          'id'        => $imageID,
          'size'      => $size,
          'src'       => $src
        );
      }
    }
    echo json_encode($res);
    wp_die();
  }

  /**
	 * Import image from Percolate DAM to WP Media Library
   *
   * @param $imageKey: Percolate uid, eg. video:674337823375193305
   * @param $key: importer's custom channel set's key
   * @return string: imported image's ID
   */
  public function importImageWP ($imageKey, $key) {

    if( is_array($imageKey) ) {
      $imageKey = $imageKey[0];
    }
    // https://percolate.com/api/v3/media/video:674337823375193305
    $method = "v3/media/" . $imageKey;
    $imageData = $this->Percolate->callAPI($key, $method, $fields);
    Percolate_Log::log($method);
    // Percolate_Log::log(print_r($imageData, true));

    if( isset($imageData['src']) ) {
	    $src = $imageData['src'];
    }
    elseif( isset($imageData[0]['src']) ) {
      // It's an array field, let's gran the first match
      $src = $imageData[0]['src'];
      $imageData = $imageData[0];
    }
    elseif( isset($imageData['data']['formats'][0]['url']) ) {
      // It's an array field, let's gran the first match
      $src = $imageData['data']['formats'][0]['url'];
    }
    elseif( isset($imageData[0]['data']['formats'][0]['url']) ) {
      // It's an array field, let's gran the first match
      $src = $imageData[0]['data']['formats'][0]['url'];
      $imageData = $imageData[0];
    }
    else {
      Percolate_Log::log("Image soruce cannot be found in Percolate API response.");
      return;
    }

    if( isset($imageData['id']) ) {
	    $percolate_id =  $imageData['id'];
    }
    else if( isset($imageData['data']['id']) ) {
	    $percolate_id =  $imageData['data']['id'];
    }
    else {
      Percolate_Log::log("Image ID cannot be found");
      return;
    }

    // ----------- Check if already imported --------------
    $args = array(
    	'post_type'		=>	'attachment',
      'post_status'	=>	'any',
      'meta_key'    =>  'percolate_id',
	    'meta_value'  =>  $percolate_id
    );
    $posts = new WP_Query( $args );
    wp_reset_postdata();

    if ( $posts->post_count > 0) {
      // Percolate_Log::log(print_r($posts, true));
      Percolate_Log::log("Image already imported.");
      // wp_delete_post($posts->posts[0]->ID, true);
      return $posts->posts[0]->ID;
    }

    // ----- WP Media upload ------
    // Need to require these files
  	if ( !function_exists('media_handle_upload') ) {
  		require_once(ABSPATH . "wp-admin" . '/includes/image.php');
  		require_once(ABSPATH . "wp-admin" . '/includes/file.php');
  		require_once(ABSPATH . "wp-admin" . '/includes/media.php');
  	}

    $uploads = wp_upload_dir();
    // get unique file name
    $filename = wp_unique_filename($uploads['path'], $percolate_id);
    $filepath = $uploads['path'] . '/' . $filename;

    $url = '';

    $this->Percolate->getImageFromServer($src, $filepath);
    // Compute the URL
    $url = $uploads['url'] . '/' . $filename;
    // $object['media']['images'][$image]['url'] = $url;

    $type = mime_content_type($filepath);

    $title = $imageData['metadata']['original_filename'];
    if( !$title ) {
      $title = $imageData['id'];
    }

    $title = preg_replace('!\.[^.]+$!', '', basename($file));
    $content = '';

    // use image exif/iptc data for title and caption defaults if possible
    if ($image_meta = @wp_read_image_metadata($filepath)) {
      if ('' != trim($image_meta['title']))
        $title = trim($image_meta['title']);
      if ('' != trim($image_meta['caption']))
        $content = trim($image_meta['caption']);
    }

    if( isset($imageData['metadata']['description']) && !empty($imageData['metadata']['description']) ) {
      $content = $imageData['metadata']['description'];
    }

    $time = gmdate('Y-m-d H:i:s', @filemtime($filepath));

    if ($time) {
      $post_date_gmt = $time;
      $post_date = $time;
    } else {
      $post_date = current_time('mysql');
      $post_date_gmt = current_time('mysql', 1);
    }

    // Construct the attachment array
    $attachment = array(
        'post_mime_type' => $type,
        'guid' => $url,
        'post_parent' => 0, //$post_id,
        'post_title' => $title,
        'post_name' => $title,
        'post_content' => $content,
        'post_date' => $post_date,
        'post_date_gmt' => $post_date_gmt
    );

    $filepath = $uploads['path'] .'/'. $filename;
    // //Win32 fix:
    // $filepath = str_replace(strtolower(str_replace('\\', '/', $uploads['path'])), $uploads['path'], $filepath);
    // Save the data
    $id = wp_insert_attachment($attachment, $filepath);
    if (is_wp_error($id)) {
      Percolate_Log::log(print_r($id, true));
      return false;
    }
    $data = wp_generate_attachment_metadata($id, $filepath);
    wp_update_attachment_metadata($id, $data);

    // ----------- Percolate meta fields --------------
    update_post_meta($id, 'percolate_id', $imageData['id']);
    update_post_meta($id, 'percolate_uid', $imageData['uid']);
    update_post_meta($id, 'percolate_created_at', strtotime($imageData['metadata']['created_at']));

  	// $src = wp_get_attachment_url( $id );

    return $id;
  }

  /**
	 * Import image from an URL to WP Media Library
   * @param string $url: image's src attribute
   * @return string: imported image's url
   */
  public function importImageFromUrl ($url) {
    // Need to require these files
  	if ( !function_exists('media_handle_upload') ) {
  		require_once(ABSPATH . "wp-admin" . '/includes/image.php');
  		require_once(ABSPATH . "wp-admin" . '/includes/file.php');
  		require_once(ABSPATH . "wp-admin" . '/includes/media.php');
  	}

  	$tmp = download_url( $url );
    if (is_wp_error($id)) {
      Percolate_Log::log(print_r($id, true));
      return false;
    }
  	$post_id = 0;
  	$desc = "Scraped image from Percolate post html";
  	$file_array = array();

  	// Set variables for storage
  	// fix file filename for query strings
  	preg_match('/[^\?]+\.(jpg|jpe|jpeg|gif|png)/i', $url, $matches);
  	$file_array['name'] = basename($matches[0]);
  	$file_array['tmp_name'] = $tmp;

  	// If error storing temporarily, unlink
  	if ( is_wp_error( $tmp ) ) {
      Percolate_Log::log(print_r($tmp, true));
  		@unlink($file_array['tmp_name']);
  		$file_array['tmp_name'] = '';
  	}

  	// do the validation and storage stuff
  	$id = media_handle_sideload( $file_array, $post_id, $desc );

  	// If error storing permanently, unlink
  	if ( is_wp_error($id) ) {
  		@unlink($file_array['tmp_name']);
      Percolate_Log::log(print_r($id, true));
  		return false;
  	}

  	return $src = wp_get_attachment_url( $id );
  }

}

?>
