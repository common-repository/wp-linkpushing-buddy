<?php
/**
 * Linkpusing Client API.
  * Date: 22/06/13
 * Time: 21:14
 */

define("LP_API_URL", "http://linkpushing.net/amember/wpapi.php");

define("WP_LP_ERROR_API_NOT_FOUND", 1);
define("WP_LP_ERROR_USER_NOT_FOUND", 2);
define("WP_LP_ERROR_ACTION_NOT_FOUND", 3);
define("WP_LP_ERROR_INVALID_ACTION", 4);
define("WP_LP_ERROR_INVALID_URL", 5);
define("WP_LP_ERROR_INVALID_PUSH_URL", 6);
define("WP_LP_ERROR_TITLE_REQUIRED", 7);
define("WP_LP_ERROR_BODY_REQUIRED", 8);
define("WP_LP_ERROR_EXCERPT_REQUIRED", 9);
define("WP_LP_ERROR_KEYWORDS_REQUIRED", 10);
define("WP_LP_ERROR_PUSH_SAVE_FAILED", 11);
define("WP_LP_ERROR_NAME_REQUIRED", 12);
define("WP_LP_ERROR_PUSH_NOT_FOUND", 13);

class WpLpApi
{
    var $lp_username = null;
    var $lp_api_key = null;
    var $err_msgs = array(
        WP_LP_ERROR_API_NOT_FOUND => "That API could not be found",
        WP_LP_ERROR_USER_NOT_FOUND => "A user could not be found for that API",
        WP_LP_ERROR_ACTION_NOT_FOUND => "Action could not be found",
        WP_LP_ERROR_INVALID_ACTION => "Invalid Action",
        WP_LP_ERROR_INVALID_URL => "Invalid URL",
        WP_LP_ERROR_INVALID_PUSH_URL => "URL must begin with http:// or https://",
        WP_LP_ERROR_TITLE_REQUIRED => "Article title is a required field",
        WP_LP_ERROR_BODY_REQUIRED => "Article body is a required field",
        WP_LP_ERROR_EXCERPT_REQUIRED => "Article excerpt is a required field",
        WP_LP_ERROR_KEYWORDS_REQUIRED => "Keywords is a required field",
        WP_LP_ERROR_PUSH_SAVE_FAILED => "Failed to save push, contact administrator",
        WP_LP_ERROR_NAME_REQUIRED => "Entry name is a required field",
        WP_LP_ERROR_PUSH_NOT_FOUND => "push_id not found for that user",
    );
    function WpLpApi($lp_username, $lp_api_key)
    {
        $this->lp_username = $lp_username;
        $this->lp_api_key = $lp_api_key;
    }

    function post_to_lp($post_title, $post_url, $key_objs) {
        $keywords = array();
        foreach($key_objs as $key) {
            $keywords[] = $key->name;
        }
        $entry_keywords = implode(",", $keywords);
        $full_url = LP_API_URL .
            "?api=" . urlencode($this->lp_api_key) .
            "&action=addPush" .
            "&entry_url=" . urlencode($post_url) .
            "&entry_name=" . urlencode($post_url) .
            "&entry_keywords=" . urlencode($entry_keywords);
        $result = file_get_contents($full_url);
    }


    function get_account_type() {
        $full_url = LP_API_URL .
            "?api=" . urlencode($this->lp_api_key) .
            "&action=getAccountType";
        $result = file_get_contents($full_url);
        $result = mb_convert_encoding($result, 'UTF-8',
            mb_detect_encoding($result, 'UTF-8, ISO-8859-1', true));
        return $result;
    }


    function submit_wp_for_indexing($post_url) {

        $full_url = LP_API_URL .
            "?api=" . urlencode($this->lp_api_key) .
            "&action=submitWpLink" .
            "&entry_url=" . urlencode($post_url);
        $result = file_get_contents($full_url);
    }

    function get_result($str) {
        $parts = explode("\n", $str);
        $status = $parts[0];
        if ( trim($status) == "SUCCESS") {
            $msg = $parts[1];
            list($label, $lp_id) = split("=", $msg);
            return $lp_id;
        } else {
            $msg = $parts[1];
            return $msg * -1;
        }
    }

    function get_error($error_code) {
        return $this->err_msgs[$error_code];
    }
}
?>