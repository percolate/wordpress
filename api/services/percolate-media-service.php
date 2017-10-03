<?php
/**
 * @package Percolate_Importer
 *   Media Library
 */


class Percolate_Media
{
  //Plugin file path
  const FILE = __FILE__;

  /**
   * Class constructor
   */
  public function __construct(Percolate_API_Service $Percolate_API_Service) {
    $this->Percolate = $Percolate_API_Service;

    // AJAX endpoint
    add_action( 'wp_ajax_image_import', array( $this, 'importImageEndpoint' ) );

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
     'handle'	 => 'Percolate_Media',
     'src'		 => plugins_url( '/frontend/scripts/media/app.js', dirname(dirname(__FILE__)) ),
     'deps'		 => array('angular'),
     'version' => '1',
     'footer'  => true
   );
   $scripts[] = array(
     'handle'	 => 'MediaCtr',
     'src'		 => plugins_url( '/frontend/scripts/media/controllers/media.js', dirname(dirname(__FILE__)) ),
     'deps'		 => array('angular'),
     'version' => '1',
     'footer'  => true
   );
   $scripts[] = array(
     'handle'	 => 'LoaderDirM',
     'src'		 => plugins_url( '/frontend/scripts/media/directives/loader.js', dirname(dirname(__FILE__)) ),
     'deps'		 => array('angular'),
     'version' => '1',
     'footer'  => true
   );

   foreach( $scripts as $script ) {
     wp_enqueue_script( $script['handle'], $script['src'], $script['deps'], $script['version'], $script['footer']);
   }

   // ---------
   // Styles

   wp_enqueue_style( 'percolate-styles-media', plugins_url( '/frontend/styles/css/percolate-media.css', dirname(dirname(__FILE__)) ), null, '1', 'all' );
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
    include_once(dirname(dirname(__DIR__)) . '/frontend/views/media/modal.php');
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
      $imageID = $this->importImage(array($_POST['data']['imageKey']), $_POST['data']['key']);
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
   * @param $imageKey: Percolate uid, eg. video:674337.....193305
   * @param $key: importer's custom channel set's key
   * @return string|false: imported image's ID
   */
  public function importImage ($imageKey, $key) {

    if( is_array($imageKey) ) {
      $imageKey = $imageKey[0];
    }

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
    elseif( isset($imageData['images']['large']['url']) ) {
      // It's an array field, let's gran the first match
      $src = $imageData['images']['large']['url'];
    }
    elseif( isset($imageData[0]['images']['large']['url']) ) {
      // It's an array field, let's gran the first match
      $src = $imageData[0]['images']['large']['url'];
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
  	if ( !function_exists('media_sideload_image') ) {
  		require_once(ABSPATH . "wp-admin" . '/includes/image.php');
  		require_once(ABSPATH . "wp-admin" . '/includes/file.php');
  		require_once(ABSPATH . "wp-admin" . '/includes/media.php');
  	}

    $title = $imageData['metadata']['original_filename'];
    if( !$title ) {
      $title = $imageData['id'];
    }

    $id = $this->uploadToWp($src, $title);

    // If error storing permanently, unlink
  	if ( is_wp_error($id) ) {
      Percolate_Log::log(print_r($id, true));
  		return false;
  	}

    // ----------- Percolate meta fields --------------
    update_post_meta($id, 'percolate_id', $imageData['id']);
    update_post_meta($id, 'percolate_uid', $imageData['uid']);
    update_post_meta($id, 'percolate_created_at', strtotime($imageData['metadata']['created_at']));

    return $id;
  }

  /**
	 * Import image from an URL to WP Media Library
   * @param string $url: image's src attribute
   * @return string|false: imported image's url
   */
  public function importImageFromUrl ($url) {
    // Need to require these files
  	if ( !function_exists('media_sideload_image') ) {
  		require_once(ABSPATH . "wp-admin" . '/includes/image.php');
  		require_once(ABSPATH . "wp-admin" . '/includes/file.php');
  		require_once(ABSPATH . "wp-admin" . '/includes/media.php');
  	}


  	// fix file filename for query strings
  	preg_match('/[^\?]+\.(jpg|jpe|jpeg|gif|png)/i', $url, $matches);
  	$title = basename($matches[0]);
    $desc = "Scraped image from Percolate post html";

    // ----------- Check if already imported --------------
    $args = array(
    	'post_type'		=>	'attachment',
      'post_status'	=>	'any',
	    'title'       =>  $title
    );
    $images = new WP_Query( $args );
    wp_reset_postdata();

    if ( $images->post_count > 0) {
      Percolate_Log::log("Image already imported.");
      return wp_get_attachment_url( $images->posts[0]->ID );
    }


    $id = $this->uploadToWp($url, $title);

  	// If error importing
  	if ( is_wp_error($id) ) {
      Percolate_Log::log(print_r($id, true));
  		return false;
  	}

  	return $src = wp_get_attachment_url( $id );
  }

  /**
   * Uploads the image from given URL to WP
   * @param  string $src: The image's src
   * @param  string $title: Title of the asset
   * @return int|WP_Error id: The uploaded asset's ID or error
   */
  private function uploadToWp($src, $title = '')
  {
    // media_sideload_image can only return the ID on WP v4.8 and above
    if (version_compare ( get_bloginfo('version') , '4.8.0' , '>=')) {

      return media_sideload_image($src, null, $title, 'id');

    } else {

      if ( ! empty( $src ) ) {

        // Set variables for storage, fix file filename for query strings.
        preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $src, $matches );
        if ( ! $matches ) {
            return new WP_Error( 'image_sideload_failed', __( 'Invalid image URL' ) );
        }

        $file_array = array();
        $file_array['name'] = basename( $matches[0] );

        // Download file to temp location.
        $file_array['tmp_name'] = download_url( $src );

        // If error storing temporarily, return the error.
        if ( is_wp_error( $file_array['tmp_name'] ) ) {
          return $file_array['tmp_name'];
        }

        // Do the validation and storage stuff.
        $id = media_handle_sideload( $file_array, 0, $title );

        // If error storing permanently, unlink.
        if ( is_wp_error( $id ) ) {
          @unlink( $file_array['tmp_name'] );
        }

        return $id;
      }

    }
  }

}
