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

  const SCHEMA_MISMATCH_MSG = "Alternative schema versions have been found for the channel's posts. Please check your mapping and update if needed!";

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
    // Messages
    include_once(__DIR__ . '/percolate-messages.php');
    $this->Messages = PercolateMessages::instance();
    // Queue
    include_once(__DIR__ . '/percolate-queue.php');
    $this->Queue = Percolate_Queue::instance();
    // WPML
    include_once(__DIR__ . '/percolate-wpml.php');
    $this->Wpml = Percolate_WPML::instance();

    // Dom Parser plugin
    if (!class_exists('simple_html_dom_node')) {
      // Percolate_Log::log("simple_html_dom_node isn't present");
      require_once( dirname(__DIR__) . '/vendor/simple_html_dom.php' );
    }

    // AJAX endpoint
    add_action( 'wp_ajax_do_import', array( $this, 'importChannelPosts' ) );

  }


  /**
   * Endpoint for importing posts for the selected channel
   */
  public function importChannelPosts()
  {
    if( isset($_POST['data']) ) {
      $option = json_decode( $this->getChannels() );
      $channel = $option->channels->{$_POST['data']};

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
      if($channel->active == 'true') {
        $res = $this->processChannel( $channel );
      }
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
    $schemas = $this->getSchemas($channel);

    $posts = $this->getPosts($channel);

    if( !is_array($posts) || empty($posts) ) {
      $res = array(
        'success' => false,
        'messages' => 'No posts were found for this channel: ' . $channel
      );
      return $res;
    }

    $postsBySchema = array();
    foreach ($posts as $post) {
      /* we have schema versioning now, and post[schema_id] contains the version too
       *  eg. schema:00000000_11111111
       */
      $postSchema = explode("_", $post['schema_id']);
      $postSchemaRoot = $postSchema[0];
      $postsBySchema[$postSchemaRoot][] = $post;
    }

    foreach ($schemas as $schema) {

      // Get the plugin's template (called channel on the frontend)
      $template = $channel->{$schema['id']};

      // Flag if there are multiple versions of the schame has been found
      $schemaVersionMismatch = false;

      if( empty($template) || $template->postType !== 'false' ) {

        if( !is_array($postsBySchema[$schema['id']]) || empty($postsBySchema[$schema['id']]) ) {
          Percolate_Log::log('No posts found for ' . $schema['id']);
        } else {
          Percolate_Log::log('Importing posts for: ' . print_r($template, true));

          foreach ($postsBySchema[$schema['id']] as $post) {
            $success = $this->importPost($post, $template, $schema, $channel);

            // Check if there is an updated template
            if( $success['success'] == true
                && isset($tempate->version)
                && $tempate->version != $post['schema_id']
                && !$schemaVersionMismatch )
            {
              $schemaVersionMismatch = true;
              $this->Messages->addMessage(SCHEMA_MISMATCH_MSG, $res_schema["data"]);
            }

            $res['messages'][] = $success;
          }
        }
      }
    }

    return $res;
  }

  public function getPosts($channel)
  {
    /**
     * Call the percolate API and try to import stories
     */

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
      'queued.publishing',
      'queued.published',
      'live'
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
    $res['status'] = $post['status'];
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
      'post_type'		     =>	$template->postType,
      'post_status'	     =>	'any',
      'meta_key'         => 'percolate_id',
      'meta_value'       => $post['id'],
      'suppress_filters' => true // need to bypass the WPML language filter
    );
    // ----------- Post basics --------------
    $posts = new WP_Query( $args );
    if ( $posts->post_count > 0) {
      Percolate_Log::log('Post already imported: ' . $post['id']);
      // Delete post if any
      // wp_delete_post($posts->posts[0]->ID, true);
      $res['success'] = false;
      $res['percolate_id'] = $post['id'];
      $res['message'] = "Post already imported";
      return $res;
    }
    Percolate_Log::log('----------------------------------');
    Percolate_Log::log('Importing post: ' . $post['id'] );
    Percolate_Log::log('Post status: ' . $post['status']);

    // ----------- Post Author --------------
    $postAuthor = $channel->wpUser;
    if (isset($channel->userMapping->{$post['user_id']}) && !empty($channel->userMapping->{$post['user_id']})) {
      Percolate_Log::log('User mapping found for ' . $post['user_id']);
      $postAuthor = $channel->userMapping->{$post['user_id']};
    }

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

        if ($this->checkWpml($template) && $channel->topicsWpml == 'on')
        {
          // Percolate_Log::log('Post with WPML categories' . print_r($channel->{'topicsWPML'.$postLang}, true));
          $postLang = $post['ext'][$template->wpmlField];
          $category_wp = $channel->{'topicsWPML'.$postLang}->{$topic_id};
          $post_category[] = $category_wp;
        } else {
          $category_wp = $channel->topics->{$topic_id};
          $post_category[] = $category_wp;
        }

      }
    }

    // ----------- Post date & status --------------
    $post_status = 'future';
    $publish_date = $post['live_at'];

    // Still trying to fix 1970 bug
    if ($publish_date == NULL){
      Percolate_Log::log('No live_at date, using created_at.');
      $publish_date = $post['created_at'];
      $post_status = 'draft';
    }
    $publish_date = strtotime($publish_date);


    // GMT offset of WP
    $gmtOffset = get_option( 'gmt_offset' );
    $localTime = get_date_from_gmt(date('Y-m-d H:i:s', $publish_date));
    Percolate_Log::log('Local publishing time of the post: '. $localTime . '. GMT offest: '. $gmtOffset);

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
      'post_author'    => $postAuthor, // The user ID number of the author. Default is the current user ID.
      'post_date'      => $localTime, // [ Y-m-d H:i:s ] // The time post was made.
      // 'post_date_gmt'  => date('Y-m-d H:i:s', $publish_date), // The time post was made, in GMT.
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
    Percolate_Log::log('Post imported: ' . print_r($wp_post_id, true) . '. Publish date: UTM' . $publish_date . ', GMT: ' . get_date_from_gmt(date('Y-m-d H:i:s', $publish_date)) . ' Current time: ' . time());

    if ($post['status'] == 'draft' || (isset($template->safety) && $template->safety == 'on')) {
      Percolate_Log::log('Create event for transitioning post status, currently draft state.');
      $this->Queue->addEvent( array( "ID" => $wp_post_id, 'draft' => 'yes') );
    }
    else if($post['status'] == 'queued' || $post['status'] == 'queued.publishing' ||  $post['status'] == 'queued.published') {
      Percolate_Log::log('Create event for transitioning post status, at:  ' .get_date_from_gmt(date('Y-m-d H:i:s', $publish_date)) );
      $this->Queue->addEvent( array( "ID" => $wp_post_id, 'dateUTM' => $publish_date) );
    }

    // ----------- Factory meta fields --------------
    update_post_meta($wp_post_id, 'wp_channel_uuid', $channel->uuid);
    update_post_meta($wp_post_id, 'percolate_id', $post['id']);
    update_post_meta($wp_post_id, 'percolate_created_at', strtotime($post['created_at']));
    update_post_meta($wp_post_id, 'percolate_platform_id', $post['platform_id']);
    update_post_meta($wp_post_id, 'percolate_channel_id', $post['channel_id']);
    update_post_meta($wp_post_id, 'percolate_schema_id', $post['schema_id']);
    update_post_meta($wp_post_id, 'percolate_name', $post['name']);
    update_post_meta($wp_post_id, 'percolate_status', $post['status']);

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
          $valueTemp = $value[0];
          $valueTemp = strtolower($valueTemp);

          if($valueTemp === true || $valueTemp === 'true') {
            $value = 1;
          }
          if($valueTemp === false || $valueTemp === 'false') {
            $value = 0;
          }
        }


        // ----- ACF -----
        if( isset($template->acf) && $template->acf == 'on' ) {
          // Check for mapping
          if( isset($template->mapping->{$key}) && !empty($template->mapping->{$key}) ) {
            $_fieldname = $template->mapping->{$key};
          } else {
            $_fieldname = false;
          }
          $meta_success = update_field($_fieldname, $value, $wp_post_id);
        }
        // ----- No ACF -----
        else {

          // Check for mapping
          if( isset($template->mapping->{$key}) && !empty($template->mapping->{$key}) ) {
            $_fieldname = $template->mapping->{$key};
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
        // Percolate_Log::log('term_id: ' . $term);
        // Get term from Percolate
        // https://percolate.com/api/v5/term/?ids=term%3A2030798
        $key    = $channel->key;
        $method = "v5/term/";
        $fields = array(
          'ids'  => $term
        );
        $res_tag = $this->Percolate->callAPI($key, $method, $fields);

        if( isset($res_tag['data']) && isset($res_tag['data'][0]['name']) ) {
          $termName = $res_tag['data'][0]['name'];
          // Percolate_Log::log('term_name: ' . $termName);

          // if ($this->checkWpml($template)) {
          //   $postLang = $post['ext'][$template->wpmlField];
          //   Percolate_Log::log('Swithcing to: ' . $postLang);
          //   do_action( 'wpml_switch_language', $postLang);
          // }
          wp_set_post_tags( $wp_post_id, $termName, true );

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

    // ----------- WPML --------------
    if ($this->checkWpml($template)) {
      Percolate_Log::log('Post WPML - handling translations for ' . print_r($wp_post_id, true) . '. Language field: ' . $template->wpmlField);

      // Get the language from Percolate
      $postLang = $post['ext'][$template->wpmlField];
      Percolate_Log::log('Post WPML - language: ' . $postLang);

      // Set the language code in WPML's table
      $set_language_args = array(
        'element_id'      => $wp_post_id,
        'language_code'   => $postLang,
        'trid'            => FALSE // If set to FALSE it will create a new trid for the element
      );
      do_action( 'wpml_set_element_language_details', $set_language_args );

      // Add the original language code, so we can check if it's changed when syncing content
      update_post_meta($wp_post_id, 'percolate_language', $postLang);
    }

    // ----------- All done here --------------
    update_post_meta($post['id'], 'import_done', 'yes');
    $res['success'] = true;
    $res['percolate_id'] = $post['id'];
    $res['message'] = "Post imported successfully.";
    return $res;
  }

  /**
   * Methods for adding / removing the WP Cron job for importing posts
   */
  public function activateCron(){
    Percolate_Log::log('WP Cron: percolate_import_posts_event activated');
    wp_schedule_event(time(), 'every_5_min', 'percolate_import_posts_event');

    Percolate_Log::log('WP Cron: percolate_transition_posts_event activated');
    wp_schedule_event(time()+1, 'every_min', 'percolate_transition_posts_event');
  }
  public function deactivateCron(){
    Percolate_Log::log('WP Cron: percolate_import_posts_event deactiveted');
    wp_clear_scheduled_hook('percolate_import_posts_event');

    Percolate_Log::log('WP Cron: percolate_transition_posts_event deactiveted');
    wp_clear_scheduled_hook('percolate_transition_posts_event');
  }

  private function checkWpml($template)
  {
    return $this->Wpml->isActive() && isset($template->wpmlStatus) && $template->wpmlStatus == 'on' && isset($template->wpmlField);
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
