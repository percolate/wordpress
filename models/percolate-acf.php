<?php

/**
 * @package Percolate_Importer
 *  ACF methods
 */

/**
 * Class Percolate_ACF_Model
 * Model to provide an ACF related methods
 */
class Percolate_ACF_Model
{
  // Singleton instance
  private static $instance = false;

  /**
   * Return singleton instance
   * @return Percolate_ACF_Model
   */
	public static function instance() {
		if( !self::$instance )
			self::$instance = new Percolate_ACF_Model;

		return self::$instance;
	}

  public function __construct() {
    add_action( 'admin_init', array( $this, 'get_ACF_data' ) );
  }

  public function get_ACF_data()
  {
    if ( is_plugin_active( 'advanced-custom-fields/acf.php' ) ) {
      // Percolate_Log::log('ACF v4 active');
      $this->acf = 'v4';
    } else if ( is_plugin_active( 'advanced-custom-fields-pro/acf.php' ) ) {
      // Percolate_Log::log('ACF v5 active');
      $this->acf = 'v5';
    } else {
      $this->acf = null;
    }
  }

  /**
   * ACF: check if plugin is active
   */
  public function getAcfStatus()
  {
    // // We don't want ACF for now
    // echo false;
    // wp_die();

    if ( is_plugin_active( 'advanced-custom-fields/acf.php' ) || is_plugin_active( 'advanced-custom-fields-pro/acf.php' ) ) {
      echo true;
      wp_die();
    } else {
      echo false;
      wp_die();
    }
  }

  public function getAcfData()
  {
    $groups = $this->getAcfGroups();
    $fields = $this->getAcfFields();
    $res = array(
      'success' => true,
      'groups'  => $groups,
      'fields'  => $fields,
    );

    return $res;
  }

  public function getAcfGroups()
  {
    if ($this->acf && $this->acf == 'v5'){
  		$this->acfGroups = get_posts(array('posts_per_page' => -1, 'post_type' => 'acf-field-group'));
  	}
  	else
    {
  		$this->acfGroups = get_posts(array('posts_per_page' => -1, 'post_type' => 'acf'));
  	}
    return $this->acfGroups;
  }


  public function getAcfFields()
  {
    if( !isset($this->acfGroups) ) { return false; }
    $all_existing_acf = array();

    foreach ($this->acfGroups as $group) {

      $all_existing_acf[$group->ID] = array();

      if ($this->acf == 'v5' ){
        $fields = get_posts(array('posts_per_page' => -1, 'post_type' => 'acf-field', 'post_parent' => $group->ID));

        foreach ($fields as $field) {
          // Percolate_Log::log('Fields: ' . print_r(unserialize( $field->post_content  ), true));
          $all_existing_acf[$group->ID][] = array(
            'key'   => $field->post_name,
            'label' => $field->post_title,
            'data'  => unserialize($field->post_content)
          );
  			}
    	}
    	else
      {

    		foreach (get_post_meta($group->ID, '') as $cur_meta_key => $cur_meta_val) {
    			if (strpos($cur_meta_key, 'field_') !== 0) continue;
  				$field = (!empty($cur_meta_val[0])) ? unserialize($cur_meta_val[0]) : array();
          $all_existing_acf[$group->ID][] = $field;
  			}

    	}

    }

    return $all_existing_acf;
  }


}
