<?php

/**
 * @package Percolate_Importer
 */

/**
 * Class Percolate_Post_Model
 */
class Percolate_Post_Model
{
  const POST_PREVIEW_COPY = "Post has been updated in WordPress: ";

  private $postStatuses = array(
    'draft' => array(
      'key'     => 'draft',
      'label'   => 'Draft',
      'weight'  => 0
    ),
    'queued' => array(
      'key'     => 'queued',
      'label'   => 'Queued',
      'weight'  => 1
    ),
    'queued.publishing' => array(
      'key'     => 'queued.publishing',
      'label'   => 'On Schedule',
      'weight'  => 2
    )
  );

  public function __construct(
    Percolate_Media $Percolate_Media,
    Percolate_API_Service $Percolate_API_Service,
    Percolate_Queue_Model $percolate_Queue_Model,
    Percolate_WP_Model $percolate_WP_Model,
    Percolate_WPML_Model $percolate_WPML_Model
  ) {
    $this->Percolate = $Percolate_API_Service;
    $this->Media = $Percolate_Media;
    $this->Wp = $percolate_WP_Model;
    $this->Wpml = $percolate_WPML_Model;
    $this->Queue = $percolate_Queue_Model;

    // Dom Parser plugin
    if (!class_exists('simple_html_dom_node')) {
      // Percolate_Log::log("simple_html_dom_node isn't present");
      require_once( dirname(__DIR__) . '/vendor/simple_html_dom.php' );
    }

  }


  /**
   * Get all posts from Percolate
   *
   * @param stdObject $channel
   * @return array Posts
   */
  public function getAllPosts($channel)
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
      'statuses'      => 'draft,queued.*',
      'limit'         => $batch,
      'offset'        => $offset
    );

    $res_posts = $this->Percolate->callAPI($key, $method, $fields);
    if (!isset($res_posts['data'])) { return $posts; }

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
   * Get the post data from Percolate for a post that's already imported
   *
   * @param string $wpPostID WP post ID
   * @return arrray|false Post object from Percolate
   */
  public function getExistingPost($wpPostID)
  {
    $postPercolateID = get_post_meta($wpPostID, 'percolate_id', true);
    if(!isset($postPercolateID) || empty($postPercolateID)) {
      Percolate_Log::log('No Percolate ID found for this post. ' . $wpPostID);
      return false;
    }

    $key    = $this->getPostChannel($wpPostID)->key;
    $method = "v5/post/" . $postPercolateID;
    $fields = array();

    $res = $this->Percolate->callAPI($key, $method, $fields);

    if(!isset($res['data'])) {
      Percolate_Log::log('There was an error, check the API response.');
      return;
    }

    return $res['data'];
  }


  /**
   * Get the channel options of an already imported post
   *
   * @param string $wpPostID WP post ID
   * @return stdObject Stored WP channel data
   */
  public function getPostChannel($wpPostID)
  {
    // Get the UUID of the WP-Perc channel that imported the post
    $wpChannelUuid = get_post_meta($wpPostID, 'wp_channel_uuid', true);
    if( empty($wpChannelUuid)) {
      Percolate_Log::log("No channel UUID found for post {$wpPostID}.");
      return false;
    }

    // Get the plugin options from DB
    $option = json_decode( $this->Wp->getData() );
    if( !isset($option->channels) ) {
      Percolate_Log::log('No channels were found, exiting.');
      return false;
    }

    // Percolate_Log::log("Post's original importing channel found.");
    return $option->channels->{$wpChannelUuid};
  }

  /**
   * Get the schemas from Percolate for the given channel
   *
   * @param stdObject $channel
   * @return array|false Schemas
   */
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

    if (isset($res_schema["data"])) {
      return $schemas = $res_schema["data"];
    } else {
      Percolate_Log::log('No shchemas were found.');
      return false;
    }
  }


  public function updateExistingPost($wpPostID)
  {
    $percolatePost = $this->getExistingPost($wpPostID);

    // Check if updated_at date to see if the post has been changed
    if (intval(strtotime($percolatePost['updated_at'])) <= intval(get_post_meta($wpPostID, 'percolate_updated_at', true)) ) {
      Percolate_Log::log('Post has not been updated: ' . $wpPostID);
      return false;
    }

    // Don't sync approved posts
    Percolate_Log::log('APPROVALS: checking status' . $percolatePost['status'] . strpos($percolatePost['status'], 'approvals'));
    if (strpos($percolatePost['status'], 'approvals') !== FALSE) {
      Percolate_Log::log('Post status is not valid for sync.' . $wpPostID);
      return false;
    }

    $channel = $this->getPostChannel($wpPostID);
    $schemas = $this->getSchemas($channel);
    if (!$schemas) return false;
    $schema = PercolateHelpers::searchInArray($schemas, 'id', PercolateHelpers::getOriginalSchemaId($percolatePost['schema_id']));
    $template = $channel->{$schema[0]['id']};

    $res = $this->importPost($percolatePost, $template, $schema[0], $channel, $wpPostID);
    return $res;
  }


  /**
   * Import a Percolate post into WP
   *
   * @param stdObject $post Percolate post data
   * @param stdObject $template Importer's template settings
   * @param array $schema Percolate's custom schema
   * @param stdObject $channel Importer's selected channel
   *
   * @return array Status messages
   */
  public function importPost($post, $template, $schema, $channel, $wpPostID = null)
  {
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

    Percolate_Log::log('----------------------------------');


    if ($wpPostID) {

      // ------- Updating post -------
      Percolate_Log::log('Updating post: ' . $post['id'] . ',WP: ' . $wpPostID);
      Percolate_Log::log('Post status: ' . $post['status']);
      $updatePost = true;
    }
    else
    {
      // ------- Creating new post -------
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

      // ------ Check approval status from Perc --------
      $res['status'] = $post['status'];
      if( isset($post['status']) && !in_array($post['status'], $statusToImport) )
      {
        $res['success'] = false;
        $res['message'] = "Post is not ready to import. Status: " . $post['status'];
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
        // DEBUG: Delete post if any
        // wp_delete_post($posts->posts[0]->ID, true);
        $res['success'] = false;
        $res['percolate_id'] = $post['id'];
        $res['message'] = "Post already imported";
        return $res;
      }
      Percolate_Log::log('Importing post: ' . $post['id'] );
      Percolate_Log::log('Post status: ' . $post['status']);
    }



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
        Percolate_Log::log('Body is processed by str_get_html');
        // Find all images
        foreach($html->find('img') as $img) {
          Percolate_Log::log('Image found for post : ' . $post['id'] . " - " . print_r(htmlspecialchars_decode($img->src), true));
          $newSrc = $this->Media->importImageFromUrl(htmlspecialchars_decode($img->src));
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

    if ($wpPostID) {
      $post_args['ID'] = $wpPostID;
    }

    $wpPostID = wp_insert_post($post_args);

    if( !$wpPostID ) {
      Percolate_Log::log('Post cannot be inserted.');
      $res['success'] = false;
      $res['percolate_id'] = $post['id'];
      $res['message'] = 'Post cannot be inserted into WP.';
      return $res;
    }
    Percolate_Log::log('Post imported: ' . print_r($wpPostID, true) . '. Publish date: UTM' . $publish_date . ', GMT: ' . get_date_from_gmt(date('Y-m-d H:i:s', $publish_date)) . ' Current time: ' . time());


    // ----------- Queue & Syncing --------------
    if ($post['status'] != 'live') {
      $event = array(
        "ID" => $wpPostID,
        "idPerc" => $post['id'],
        "statusPerc" => $post['status'], // draft || queued || queued.publishing
        "statusWP" => $post_status, // draft || future
        "updatedDateUTM" => strtotime($post['updated_at'])
      );

      // if ($post['status'] == 'draft' || (isset($template->safety) && $template->safety == 'on')) {
      //   Percolate_Log::log('Create event for transitioning post status, currently draft state.');
      // }

      // Post has a live_at date
      if ($post['status'] == 'queued' || $post['status'] == 'queued.publishing') {
        $event['dateUTM'] = $publish_date;
      }

      // Handoff is at a later state, so we'll need to keep content in sync
      $event['sync'] = $this->checkHandoff($template->import, $template->handoff);

      Percolate_Log::log(' post to the Sync queue: ' . print_r($event, true));
      $this->Queue->addEvent( $event );
    }

    // ----------- Preview link --------------
    if ($post['status'] != 'live') {
      $url = $this->Wp->generatePreviewLink($wpPostID);
      Percolate_Log::log('Sending preview link: ' . $url);

      $fields = array(
        "scope_id"  => "license:" . $channel->license,
        "object_id" => $post['id'],
        "body"      => self::POST_PREVIEW_COPY . $url,
      );

      $this->Percolate->callAPI($channel->key, "v5/comment/", null, $fields);
    }

    // ----------- Factory meta fields --------------
    update_post_meta($wpPostID, 'wp_channel_uuid', $channel->uuid);
    update_post_meta($wpPostID, 'percolate_id', $post['id']);
    update_post_meta($wpPostID, 'percolate_created_at', strtotime($post['created_at']));
    update_post_meta($wpPostID, 'percolate_platform_id', $post['platform_id']);
    update_post_meta($wpPostID, 'percolate_channel_id', $post['channel_id']);
    update_post_meta($wpPostID, 'percolate_schema_id', $post['schema_id']);
    update_post_meta($wpPostID, 'percolate_name', $post['name']);
    update_post_meta($wpPostID, 'percolate_status', $post['status']);
    update_post_meta($wpPostID, 'percolate_updated_at', strtotime($post['updated_at']));

    // ----------- Meta fields --------------
    if( isset($post['ext']) && !empty($post['ext']) ) {
      $res['meta'] = array();
      $fieldDefinitions = $schema['fields'];

      // An array to hold imported assets' keys
      $importedFields = array();

      foreach ($post['ext'] as $key => $value) {

        // Chech if it's an asset field & import asset from Percolate DAM
        $definition = PercolateHelpers::searchInArray($fieldDefinitions, 'key', $key);
        if( $definition[0]['type'] == 'asset' ) {
          Percolate_Log::log('Asset field found, importing from Percolate');
          $imageID = $this->Media->importImage($value, $channel->key);

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
          $meta_success = update_field($_fieldname, $value, $wpPostID);
        }
        // ----- Meta Box -----
        else if( isset($template->acf) && $template->acf == 'metabox' ) {
          if( isset($template->mapping->{$key}) && !empty($template->mapping->{$key}) ) {
            $_fieldname = $template->mapping->{$key};

            if(is_array($value)){
              delete_post_meta($wpPostID, $_fieldname);
              foreach ($value as $subvalue) {
                $meta_success = add_post_meta($wpPostID, $_fieldname, $subvalue);
              }
            } else {
              $meta_success = update_post_meta($wpPostID, $_fieldname, $value);
            }

          }
        }
        // ----- WP -----
        else {

          // Check for mapping
          if( isset($template->mapping->{$key}) && !empty($template->mapping->{$key}) ) {
            $_fieldname = $template->mapping->{$key};
          } else {
            $_fieldname = $key;
          }
          $meta_success = update_post_meta($wpPostID, $_fieldname, $value);
        }

        $res['meta'][] = 'Adding meta field: ' . $key . ', mapped to: ' . $_fieldname . '. WP ID: ' . $meta_success;


      }
    }

    // ----------- Perc topics -> WP tags --------------
    if( isset($post['term_ids']) && !empty($post['term_ids']) ) {
      $res['terms'] = array();
      foreach ($post['term_ids'] as $index=>$term) {
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
          //   check index for removing existing tags first
          wp_set_post_tags( $wpPostID, $termName, $index==0 ? false : true );

          $res['term'][] = 'Adding term: ' . $term;
        } else {
          $res['term'][] = 'Cannot add term: ' . $term;
        }
      }
    }

    // ----------- Custom Taxonomies --------------
    if ( isset($template->taxonomy) && $template->taxonomy == 'on' && isset($template->taxonomyField) && isset($template->taxonomyWP) ) {
      $terms = $post['ext'][$template->taxonomyField];
      if (!is_array($terms)) {
        $terms = explode(',', $terms);
      }
      Percolate_Log::log("Mapping {$template->taxonomyField} to {$template->taxonomyWP}, terms: " . print_r($terms, true));
      wp_set_object_terms($wpPostID, $terms, $template->taxonomyWP, false);
    }

    // ----------- Featured image --------------
    if ( isset($template->image) && $template->image == 'on' && isset($template->postImage) && isset($importedFields[$template->postImage]) ) {
      // Gegt image ID from the imported fields array
      $imageID = $importedFields[$template->postImage];
      Percolate_Log::log('Setting imageID:' . $imageID . ' to wpPostID' . $wpPostID );
      set_post_thumbnail( $wpPostID, $imageID );
      Percolate_Log::log('Set imageID:' . $imageID . ' to wpPostID' . $wpPostID );

    }

    // ----------- WPML --------------
    if ($this->checkWpml($template)) {
      Percolate_Log::log('Post WPML - handling translations for ' . print_r($wpPostID, true) . '. Language field: ' . $template->wpmlField);

      // Get the language from Percolate
      $postLang = $post['ext'][$template->wpmlField];
      Percolate_Log::log('Post WPML - language: ' . $postLang);

      // Set the language code in WPML's table
      $set_language_args = array(
        'element_id'      => $wpPostID,
        'language_code'   => $postLang,
        'trid'            => FALSE // If set to FALSE it will create a new trid for the element
      );
      do_action( 'wpml_set_element_language_details', $set_language_args );

      // Add the original language code, so we can check if it's changed when syncing content
      update_post_meta($wpPostID, 'percolate_language', $postLang);
    }

    // ----------- All done here --------------
    update_post_meta($post['id'], 'import_done', 'yes');
    $res['success'] = true;
    $res['percolate_id'] = $post['id'];
    $res['message'] = "Post imported successfully.";
    return $res;
  }



  /**
   * Check if post needs to be synced
   *
   * Dont' need to sync if handoff is not later the earliest import
   *
   * @param string $import Earliest import status
   * @param string $handoff Handoff status
   * @return bool
   */
  private function checkHandoff($import, $handoff)
  {
    Percolate_Log::log('checkHandoff: ' . $import . " -> " . $handoff);
    if ($this->postStatuses[$handoff]['weight'] > $this->postStatuses[$import]['weight']){
      return true;
    } else {
      return false;
    }
  }

  /**
   * Check if WPML is active and it's switched on for the project
   *
   * @param stdObject $template Current template
   * @return bool
   */
  private function checkWpml($template)
  {
    return $this->Wpml->isActive() && isset($template->wpmlStatus) && $template->wpmlStatus == 'on' && isset($template->wpmlField);
  }


}
