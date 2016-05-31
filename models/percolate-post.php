<?php

/**
 * @package Percolate_Import_4
 *  POST methods
 */

/**
 * Class Percolate_POST_Model
 * Model to process Post related methods
 */
class Percolate_POST_Model
{

  protected $option = 'PercV4Opt';

  protected $Percolate;

  // Singleton instance
  private static $instance = false;

  /**
   * Return singleton instance
   * @return Percolate_POST_Model
   */
	public static function instance() {
		if( !self::$instance )
			self::$instance = new Percolate_POST_Model;

		return self::$instance;
	}

  public function __construct() {
    // Logging
    include_once(__DIR__ . '/percolate-log.php');
    $this->Log = Percolate_Log::instance();
    // Percolate API methods
    include_once(__DIR__ . '/percolate-api.php');
    $this->Percolate = Percolate_API_Model::instance();
    // Media library
    include_once(__DIR__ . '/percolate-media.php');
    $this->Media = PercolateMedia::instance();
    // Dom Parser plugin
    if (!class_exists('simple_html_dom_node')) {
      // Percolate_Log::log("simple_html_dom_node isn't present");
      require_once( dirname(__DIR__) . '/vendor/simple_html_dom.php' );
    }
  }


  /**
   * Endpoint for importing posts for the selected channel
   */
  public function importChannelPosts()
  {
    if( isset($_POST['data']) ) {
      $option = json_decode( $this->getChannels() );
      $channel = $option->channels->$_POST['data'];

      $res = $this->processChannel( $channel );
    }

    echo json_encode($res);
    wp_die();

  }

  /**
   * Endpoint for WP Cron to import posts
   */
  public function importStories () {
    Percolate_Log::log('WP Cron: importing posts.');

    $option = json_decode( $this->getChannels() );
    if( !isset($option->channels) ) {
      Percolate_Log::log('No channels were found, exiting.');
      return;
    }

    foreach ($option->channels as $channel) {
      $res = $this->processChannel( $channel );
      Percolate_Log::log(print_r($res, true));
    }

    return;
  }

  /* --------------------------------
   * Public Methods
   * -------------------------------- */

  public function getChannels()
  {
    $option = get_option( $this->option );
    return $option;
  }

  public function getSchemas($channel)
  {
    $key    = $channel->key;
    $method = "v5/schema/";
    $fields = array(
      'scope_ids' => 'license:' . $channel->license,
      'ext.platform_ids' => $channel->platform,
      'type' => 'post'
    );

    $res_schema = $this->Percolate->callAPI($key, $method, $fields);
    // Percolate_Log::log(print_r($res_schema, true));
    return $schemas = $res_schema["data"];
  }

  public function processChannel($channel)
  {
    $res = array(
      'success' => true,
      'messages' => array()
    );

    // Percolate_Log::log(print_r($channel, true));

    $schemas = $this->getSchemas($channel);

    $posts = $this->getPosts($channel);
    $postsBySchema = array();
    foreach ($posts as $post) {
      $postsBySchema[$post['schema_id']][] = $post;
    }

    foreach ($schemas as $schema) {
      $template = $channel->$schema['id'];
      if( empty($template) || $template->postType !== 'false' ) {
        Percolate_Log::log('importing posts for:');
        Percolate_Log::log(print_r($template, true));
        foreach ($postsBySchema[$schema['id']] as $post) {
          $success = $this->importPost($post, $template, $schema, $channel);
          $res['messages'][] = $success;
        }
      }
    }

    return $res;
  }

  /**
   * Call the percolate API and try to import stories
   */
  public function getPosts($channel)
  {
    $page   = 0;
    $offset = 0;
    $batch  = 100;
    $posts  = array();

    $key    = $channel->key;
    $method = "v5/post/";
    $fields = array(
      'scope_ids'     => 'license:' . $channel->license,
      'platform_ids'  => $channel->platform,
      'limit'         => $batch,
      'offset'        => $offset
    );

    $res_posts = $this->Percolate->callAPI($key, $method, $fields);
    $posts = array_merge($posts, $res_posts['data']);

    // Percolate_Log::log(print_r($res_posts, true));

    while( intval($res_posts['meta']['total']) > (intval($res_posts['meta']['query']['offset'])+intval($res_posts['meta']['query']['limit'])) ) {
      $page++;
      $offset = $batch * $page;
      $fields = array(
        'scope_ids'     => 'license:' . $channel->license,
        'platform_ids'  => $channel->platform,
        'limit'         => $batch,
        'offset'        => $offset
      );
      $res_posts = $this->Percolate->callAPI($key, $method, $fields);
      $posts = array_merge($posts, $res_posts['data']);
    }

    return $posts;
  }

  /**
   * Add a percolate story to WP
   */
  public function importPost($post, $template, $schema, $channel)
  {
    /*
     * $post: percolate post object
     * $template: importer's template settings
     * $schema: percolate's custom schema
     * $channel: importer's selected channel
     */
    $res = array(
      'success' => true,
      'message' => ''
    );

    // ------ Check if we have everything --------
    if( !isset($post) || empty($post) )
    {
          $res['success'] = false;
          $res['message'] = 'Missing data for $post';
          return $res;
    }
    if( !isset($template) || empty($template) )
    {
          $res['success'] = false;
          $res['message'] = 'Missing data for $template';
          $res['percolate_id'] = $post['id'];
          return $res;
    }
    if( !isset($schema) || empty($schema) )
    {
          $res['success'] = false;
          $res['message'] = 'Missing data for $schema';
          $res['percolate_id'] = $post['id'];
          return $res;
    }
    if( !isset($channel) || empty($channel) )
    {
          $res['success'] = false;
          $res['message'] = 'Missing data for $channel';
          $res['percolate_id'] = $post['id'];
          return $res;
    }

    $statusToImport = array(
      'queued.publishing'
    );

    if( isset($template->import) ) {
      switch($template->import){
        case 'draft':
          $statusToImport[] = 'draft';
          $statusToImport[] = 'queued';
          break;
        case 'queued':
          $statusToImport[] = 'queued';
          break;
      }
    }

    // Percolate_Log::log("status to import: ");
    // Percolate_Log::log(print_r($statusToImport, true));

    // ------ Check approval status from Perc --------
    Percolate_Log::log('Post status: ' . $post['status']);
    if( isset($post['status']) && !in_array($post['status'], $statusToImport) )
    {
          // Percolate_Log::log($post['id'] . " hasn't been approved yet. Status: " . $post['status']);
          $res['success'] = false;
          $res['message'] = "Post hasn't been approved yet. Status: " . $post['status'];
          $res['percolate_id'] = $post['id'];
          return $res;
    }

    // ------ Check if imported already --------
    $args = array(
    	'post_type'		=>	$template->postType,
      'post_status'	=>	'any',
      'meta_key'    => 'percolate_id',
	    'meta_value'  => $post['id']
    );
    // ----------- Post basics --------------
    $posts = new WP_Query( $args );
    if ( $posts->post_count > 0) {
      Percolate_Log::log('Post already imported.');
      // Delete post if any
      // wp_delete_post($posts->posts[0]->ID, true);
      $res['success'] = false;
      $res['percolate_id'] = $post['id'];
      $res['message'] = "Post alreadey imported";
      return $res;
    }

    Percolate_Log::log('Importing post: ' . $post['id'] );

    // ----------- Post title --------------
    $title = "";
    if ( isset($template->postTitle) && !empty($template->postTitle) ) {
      $title = $post['ext'][$template->postTitle];
    }
    elseif ( !isset($post['name']) || empty($post['name']) ) {
      $title = $post['name'];
    }

    // ----------- Post body --------------
    $body = "";
    if ( isset($template->postBody) && !empty($template->postBody) ) {
      $body = $post['ext'][$template->postBody];
    }

    // Open links in new tab
    if( array_key_exists('tab', $channel) && $channel->tab == true && is_string( $body ) ) {
      $body = preg_replace("/<a(.*?)>/", "<a$1 target=\"_blank\">", $body);
    }

    // ----------- Process post body for images --------------
    if( is_string($body) ) {
      Percolate_Log::log('Body is a string, checking for images...');
      $html = str_get_html($body);

      if (is_object($html)) {
        // Find all images
        foreach($html->find('img') as $img) {
          Percolate_Log::log('Image found: ' . print_r($img->src, true));
          $newSrc = $this->Media->importImageFromUrl($img->src);
          if( $newSrc ) {
            Percolate_Log::log('Image imported: ' . print_r($newSrc, true));
            $img->src = $newSrc;
          }
        }

        $body = $html->save();
      }
    }

    // ----------- Categories --------------
    $post_category = array();

    if( isset($post['topic_ids']) && !empty($post['topic_ids']) ) {
      foreach ($post['topic_ids'] as $topic_id) {
        $topic_id = str_replace( 'topic:', '', $topic_id );
        $category_wp = $channel->topics->$topic_id;
        $post_category[] = $category_wp;
      }
    }

    // ----------- Post date & status --------------
    $post_status = 'future';
    $publish_date = $post['live_at'];

    // Trying to fix 1970 bug
    if ($publish_date == NULL){
      foreach($object['schedules'] as $schedule){
        if ($schedule['published_at'] != NULL){
          $publish_date = $schedule['published_at'];
        }
      }
    }

    // Still trying to fix 1970 bug
    if ($publish_date == NULL){
      $publish_date = $object['created_at'];
      $post_status = 'draft';
    }
    $publish_date = strtotime($publish_date);

    // ----------- Post status--------------
    if ( (isset($template->safety) && $template->safety == 'on') || $post['status'] == 'draft' ) {
      $post_status = 'draft';
    }

    $post_args = array(
      // 'ID'             => [ <post id> ] // Are you updating an existing post?
      'post_content'   => $body, // The full text of the post.
      // 'post_name'      => [ <string> ] // The name (slug) for your post
      'post_title'     => $title, // The title of your post.
      'post_status'    => $post_status, // [ 'draft' | 'publish' | 'pending'| 'future' | 'private' | custom registered status ]
      'post_type'      => $template->postType,
      'post_author'    => $channel->wpUser, // The user ID number of the author. Default is the current user ID.
      // 'post_date'      => date('Y-m-d H:i:s', $publish_date), // [ Y-m-d H:i:s ] // The time post was made.
      'post_date_gmt'  => date('Y-m-d H:i:s', $publish_date), // The time post was made, in GMT.
      'post_category'  => $post_category // [ array(<category id>, ...) ] // Default empty.
    );

    // Percolate_Log::log('Post object:');
    // Percolate_Log::log(print_r($post_args, true));
    $wp_post_id = wp_insert_post($post_args);

    if( !$wp_post_id ) {
      Percolate_Log::log('Post cannot be inserted.');
      $res['success'] = false;
      $res['percolate_id'] = $post['id'];
      $res['message'] = 'Post cannot be inserted into WP.';
      return $res;
    }
    Percolate_Log::log('Post imported: ' . print_r($wp_post_id, true));

    // ----------- Factory meta fields --------------
    update_post_meta($wp_post_id, 'wp_channel_uuid', $channel->uuid);
    update_post_meta($wp_post_id, 'percolate_id', $post['id']);
    update_post_meta($wp_post_id, 'percolate_created_at', strtotime($post['created_at']));
    update_post_meta($wp_post_id, 'percolate_platform_id', $post['platform_id']);
    update_post_meta($wp_post_id, 'percolate_channel_id', $post['channel_id']);
    update_post_meta($wp_post_id, 'percolate_schema_id', $post['schema_id']);
    update_post_meta($wp_post_id, 'percolate_name', $post['name']);

    // ----------- Meta fields --------------
    if( isset($post['ext']) && !empty($post['ext']) ) {
      $res['meta'] = array();
      $fieldDefinitions = $schema['fields'];

      // An array to hold imported assets' keys
      $importedFields = array();

      foreach ($post['ext'] as $key => $value) {

        // Chech if it's an asset field & import asset from Percolate DAM
        $definition = $this->searchInArray($fieldDefinitions, 'key', $key);
        if( $definition[0]['type'] == 'asset' ) {
          Percolate_Log::log('Asset field found, importing from Percolate');
          $imageID = $this->Media->importImageWP($value, $channel->key);

          $value = $importedFields[$key] = $imageID;
        }

        // Open links in new tab
        if( array_key_exists('tab', $channel) && $channel->tab == true && is_string( $value ) ) {
          $value = preg_replace("/<a(.*?)>/", "<a$1 target=\"_blank\">", $value);
        }

        /*
         * Check if field is an a Single or Multi Select array
         *  If it is, we're correctly converting this to be ACF True/False field compatible
         */
        if(is_array($value)){
          $value = array_shift($value);
          $value = strtolower($value);
          switch($value){
            case true:
            case 'true':
              $value = 1;
              break;
            case false:
            case 'false':
              $value = 0;
              break;
          }
        }


        // ----- ACF -----
        if( isset($template->acf) && $template->acf == 'on' ) {
          // Check for mapping
          if( isset($template->mapping->$key) && !empty($template->mapping->$key) ) {
            $_fieldname = $template->mapping->$key;
          } else {
            $_fieldname = false;
          }
          $meta_success = update_field($_fieldname, $value, $wp_post_id);
        }
        // ----- No ACF -----
        else {

          // Check for mapping
          if( isset($template->mapping->$key) && !empty($template->mapping->$key) ) {
            $_fieldname = $template->mapping->$key;
          } else {
            $_fieldname = $key;
          }
          $meta_success = update_post_meta($wp_post_id, $_fieldname, $value);
        }

        $res['meta'][] = 'Adding meta field: ' . $key . ', mapped to: ' . $_fieldname . '. WP ID: ' . $meta_success;


      }
    }

    // ----------- Perc topics -> WP tags --------------
    if( isset($post['term_ids']) && !empty($post['term_ids']) ) {
      $res['terms'] = array();
      foreach ($post['term_ids'] as $term) {

        // Get term from Percolate
        // https://percolate.com/api/v5/term/?ids=term%3A2030798
        $key    = $channel->key;
        $method = "v5/term/";
        $fields = array(
          'ids'  => $term
        );
        $res_tag = $this->Percolate->callAPI($key, $method, $fields);

        if( isset($res_tag['data']) && isset($res_tag['data'][0]['name']) ) {
          wp_set_post_tags( $wp_post_id, $res_tag['data'][0]['name'], true );

          $meta_success = update_post_meta($wp_post_id, $_fieldname, $value);
          $res['term'][] = 'Adding term: ' . $term;
        } else {
          $res['term'][] = 'Cannot add term: ' . $term;
        }
      }
    }

    // ----------- Featured image --------------
    if ( isset($template->image) && $template->image == 'on' && isset($template->postImage) && isset($importedFields[$template->postImage]) ) {
      // Gegt image ID from the imported fields array
      $imageID = $importedFields[$template->postImage];
      set_post_thumbnail( $wp_post_id, $imageID );
    }

    // ----------- All done here --------------
    $res['success'] = true;
    $res['percolate_id'] = $post['id'];
    $res['message'] = "Post imported successfully.";
    return $res;
  }

  /**
   * Methods for adding / removing the WP Cron job for importing posts
   */
  public function activateImport(){
    Percolate_Log::log('WP Cron: percolate_import_posts_event activeted');
    wp_schedule_event(time(), 'every_5_min', 'percolate_import_posts_event');
  }
  public function deactivateImport(){
    Percolate_Log::log('WP Cron: percolate_import_posts_event deactiveted');
    wp_clear_scheduled_hook('percolate_import_posts_event');
  }


  private function searchInArray($array, $key, $value) {
    $results = array();

    if (is_array($array)) {
      if (isset($array[$key]) && $array[$key] == $value) {
          $results[] = $array;
      }

      foreach ($array as $subarray) {
          $results = array_merge($results, $this->searchInArray($subarray, $key, $value));
      }
    }

    return $results;
  }


}
