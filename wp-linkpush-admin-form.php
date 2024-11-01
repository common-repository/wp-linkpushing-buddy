<?php
if($_POST['wplpbuddy_hidden'] == 'Y') {
    //Form data sent
    $username = $_POST['wplpbuddy_username'];
    update_option('wplpbuddy_username', $username);

    $lpapi = $_POST['wplpbuddy_lpapi'];
    update_option('wplpbuddy_lpapi', $lpapi);

    $acc_type = get_option('wplpbuddy_lp_acc_type');
    if (!$acc_type && get_option('wplpbuddy_username')  && get_option('wplpbuddy_username')) {
        include_once("WpLpApi.php");
        $wp_lp_api = new WpLpApi(get_option('wplpbuddy_username'), get_option('wplpbuddy_lpapi'));
        $res = $wp_lp_api->get_account_type();
        $acc_type = $wp_lp_api->get_result($res);
        if ($acc_type === 0) {

        }
        else  {
            update_option('wplpbuddy_lp_acc_type', $acc_type);
        }
    }
?>
<div class="updated"><p><strong><?php _e('Options saved.' ); ?></strong></p></div>
<?php
} else {
    //Normal page display
    $username = get_option('wplpbuddy_username');
    $lpapi = get_option('wplpbuddy_lpapi');
}


?>

<div class="wrap">
    <?php    echo "<h2>" . __( 'WP Linkpusing Buddy Display Options', 'wplpbuddy_trdom' ) . "</h2>"; ?>

    <form name="wplpbuddy_form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
        <input type="hidden" name="wplpbuddy_hidden" value="Y">
        <?php    echo "<h4>" . __( 'Linkpushing Settings', 'wplpbuddy_trdom' ) . "</h4>"; ?>
        <p><?php _e("Linkpusing Username: " ); ?><input type="text" name="wplpbuddy_username" value="<?php echo $username; ?>" size="20"><?php _e(" ex: lpuser1" ); ?></p>
        <p><?php _e("Linkpusing API Key: " ); ?><input type="text" name="wplpbuddy_lpapi" value="<?php echo $lpapi; ?>" size="60"><?php _e(" ex: A001-12345-23456-A9999" ); ?></p>
        <p><?php _e("Current Linkpusing Subscription: " . get_option('wplpbuddy_lp_acc_type') ); ?></p>


        <p class="submit">
            <input type="submit" name="Submit" value="<?php _e('Update Options', 'wplpbuddy_trdom' ) ?>" />
        </p>
    </form>
</div>