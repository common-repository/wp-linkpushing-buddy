<?php
/* 
Plugin Name: WP Linkpush Buddy
Plugin URI: http://linkpusing.net/blog/wp-linkpush-buddy
Version: 1.0
Author: <a ref="http://linkpushing.net">Linkpushing.net</a>
Description: The WP Linkpush Buddy plugin provides an simple integration into your <a href="http://linkpushing.net">Linkpushing</a> account allowing you to start a Linkpush campaign for your new post with the click of a button
*/
define("WP_LP_BUDDY_TABLE_SUFFIX", "wplpbuddy");

if (!class_exists("WPLinkpushBuddy")) {
	class WPLinkpushBuddy {
        const LANG = 'wplpbuddy_lang';
        var $table_name;

		function WPLinkpushBuddy() { //constructor
            global $wpdb;
            add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
            add_action( 'save_post', array( $this, 'save' ) );
            $this->table_name = $wpdb->prefix . WP_LP_BUDDY_TABLE_SUFFIX;
		}
	
		function addHeaderCode() {
		}

        /**
         * Adds the meta box container
         */
        public function add_meta_box() {
            add_meta_box(
                'wplpbuddy_submit_to_lp_name'
                ,__( 'Submit to Linkpusing', self::LANG )
                ,array( &$this, 'render_meta_box_content' )
                ,'post'
                ,'advanced'
                ,'high'
            );
        }

        public function save( $post_id ) {
            global $wpdb;
            // don't run the echo if this is an auto save
            if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
                return;

            //if saving in a custom table, get post_ID
            $post_ID = $post_id;
            $post = get_post($post_ID);
            // don't run the echo if the function is called for saving revision.
            if ( $post->post_type == 'revision' )
                return;


            // Secondly we need to check if the user intended to change this value.
            if ( ! isset( $_POST['wplpbuddy_noncename'] ) || ! wp_verify_nonce( $_POST['wplpbuddy_noncename'], plugin_basename( __FILE__ ) ) )
                return;

            // Thirdly we can save the value to the database

            $permalink = get_permalink($post_ID);
            //sanitize user input
            $mydata = sanitize_text_field( $_POST['wplpbuddy_submit_to_lp_field'] );
            $mydata_index = sanitize_text_field( $_POST['wplpbuddy_submit_to_lp_field_for_indexing'] );
            if ($mydata_index == 'on') {
                // Submit selected
                $url = $permalink;
                $tag_names = get_the_tags($post_ID);
                if ($tag_names && count($tag_names) > 0) {
                    // Tags exist
                    // Check if already posted
                    $details = $this->get_details_by_wp_post_id($post_ID);
                    if ($details == null) {
                        include_once("WpLpApi.php");
                        $wp_lp_api = new WpLpApi(get_option('wplpbuddy_username'), get_option('wplpbuddy_lpapi'));
                        $ret = $wp_lp_api->submit_wp_for_indexing( $url);
                        $result = $wp_lp_api->get_result($ret);
                        $entry = array();

                        $entry['post_id'] = $post_ID;
                        $entry['time'] = current_time('mysql');
                        $entry['type'] = 'index';
                        $entry['submitted'] = current_time('mysql');
                        if ($result <= 0) {
                            // Failed to install get error
                            $entry['lp_id'] = 0;
                            $entry['lp_status'] = 0;
                            $entry['error_status'] = $result * -1;
                        } else {
                            $entry['lp_id'] = $result;
                            $entry['lp_status'] = 1;
                            $entry['error_status'] = 0;
                        }

                        $rows_affected = $wpdb->insert( $this->table_name, $entry);
                    }
                }
            }
            else if ($mydata == 'on') {
                // Submit selected
                $url = $permalink;
                $tag_names = get_the_tags($post_ID);
                if ($tag_names && count($tag_names) > 0) {
                    // Tags exist
                    // Check if already posted
                    $details = $this->get_details_by_wp_post_id($post_ID);
                    if ($details == null) {
                        include_once("WpLpApi.php");
                        $wp_lp_api = new WpLpApi(get_option('wplpbuddy_username'), get_option('wplpbuddy_lpapi'));
                        $ret = $wp_lp_api->post_to_lp($post->post_title, $url, $tag_names);
                        $result = $wp_lp_api->get_result($ret);
                        $entry = array();

                        $entry['post_id'] = $post_ID;
                        $entry['time'] = current_time('mysql');
                        $entry['type'] = 'submit';
                        $entry['submitted'] = current_time('mysql');
                        if ($result <= 0) {
                            // Failed to install get error
                            $entry['lp_id'] = 0;
                            $entry['lp_status'] = 0;
                            $entry['error_status'] = $result * -1;
                        } else {
                            $entry['lp_id'] = $result;
                            $entry['lp_status'] = 1;
                            $entry['error_status'] = 0;
                        }

                        $rows_affected = $wpdb->insert( $this->table_name, $entry);
                    }
                }

            }
        }


        public function get_details_by_wp_post_id($wp_post_id) {
            global $wpdb;
            $rows = $wpdb->get_results("select * from " . $this->table_name . " where post_id=$wp_post_id");
            if ($rows != null) {
                foreach ($rows as $obj) {
                    return $obj;
                }
            }
            return null;
        }
        /**
         * Render Meta Box content
         */
        public function render_meta_box_content( $post ) {
            // Use nonce for verification
            wp_nonce_field( plugin_basename( __FILE__ ), 'wplpbuddy_noncename' );
            $lp_username = get_option('wplpbuddy_username');
            $lp_package = get_option('wplpbuddy_lp_acc_type');
            if (empty($lp_username)) {
                echo 'You have not entered your Linkpusing API key, if you have an Linkpushing account, login and set your API key <a href="' . get_site_url() . '/wp-admin/options-general.php?page=WPLinkpushBuddy">here</a> ';
                echo ('<center><b>Sign up for your account at : <a href="http://linkpushing.net/amember/signup.php">Linkpushing Signup</a></b></center>');
                echo '<br>If you just want to get your links indexed, select the free account, fill in the form and login to get your API key. <br>Select a subscription package for more automatic promotion on your posts';
                echo '<br>Free account gets your posts and pages noticed fast, check out our <a href="http://linkpushing.net/blog">Blog</a> for more info';
                echo '<br>Subscription accounts gets your posts and pages noticed and ranked at the click of a button';
            } else if ($lp_package == 'Free WP Linkpush Plugin') {
                // The actual fields for data entry
                // Use get_post_meta to retrieve an existing value from the database and use the value for the form
                $value = get_post_meta( $post->ID, '_wplpbuddy_meta_value_key', true );
                echo '<label for="wplpbuddy_submit_to_lp_field_for_indexing">';
                _e( 'Submit this post to my Linkpushing account for indexing', 'wplpbuddy_textdomain' );
                echo '</label> ';
                echo '<input type="checkbox" id="wplpbuddy_submit_to_lp_field_for_indexing" name="wplpbuddy_submit_to_lp_field_for_indexing"  />';
                _e( '<p><a href="http://linkpushing.net/amember/login.php">Upgrade your Linkpushing account</a> and this post could be promoted using a Linkpush</p>' );
            } else {
                // The actual fields for data entry
                // Use get_post_meta to retrieve an existing value from the database and use the value for the form
                $value = get_post_meta( $post->ID, '_wplpbuddy_meta_value_key', true );
                echo '<label for="wplpbuddy_submit_to_lp_field">';
                _e( 'Submit this post to my Linkpushing account (' . $lp_package . ')', 'wplpbuddy_textdomain' );
                echo '</label> ';
                echo '<input type="checkbox" id="wplpbuddy_submit_to_lp_field" name="wplpbuddy_submit_to_lp_field"  />';
            }
        }


	}

} //End Class WPLinkpushBuddy


function wplpbuddy_admin_actions() {
    add_options_page("WP Linkpush Buddy", "WP Linkpush Buddy", 1, "WPLinkpushBuddy", "wplpbuddy_admin");
}


function wplpbuddy_admin() {
    include_once('wp-linkpush-admin-form.php');
}


function wplpbuddy_install () {
    global $wpdb;
    add_option( "wplpbuddy_db_version", "1.0" );
    $table_name = $wpdb->prefix . WP_LP_BUDDY_TABLE_SUFFIX;
    $sql = "CREATE TABLE $table_name (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      post_id mediumint(9) ,
      lp_id int(9) ,
      type varchar(10) ,
      time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
      lp_status mediumint(9) DEFAULT 0 NOT NULL ,
      error_status mediumint(9) DEFAULT 0 NOT NULL ,
      submitted datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
      UNIQUE KEY id (id)
    );";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}


if (class_exists("WPLinkpushBuddy")) {
	$wp_lp_buddy = new WPLinkpushBuddy();
}
//Actions and Filters	
if (isset($wp_lp_buddy)) {
	//Actions
	add_action('admin_menu', 'wplpbuddy_admin_actions');
	add_action('wp_head', array(&$wp_lp_buddy, 'addHeaderCode'), 1);
    //add_action( 'add_meta_boxes', 'wplpbuddy_add_custom_box' );
	//Filters
}

register_activation_hook( __FILE__, 'wplpbuddy_install' );

?>
