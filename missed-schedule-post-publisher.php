<?php
/*
	Plugin Name: Missed Schedule Post Publisher
	Description: This plugin publish missed scheduled posts.
	Plugin URI: http://www.ubilisim.com/missed-schedule-post-publisher-wordpress-plugin/
	Version: 1.0.3
	Author: UfukArt
	Author URI: http://www.ubilisim.com
	Text Domain: missed-schedule-post-publisher
	Domain Path: /languages/
	License: GPL2
*/

// Security
defined( 'ABSPATH' ) or exit;

// Add to db Default execute time, last executed time plugin activated
function mspp_execute_time_first_add() {
	update_option('mspp_execute_time', 20);
	update_option('mspp_last_execute_time', time());
}
register_activation_hook( __FILE__, 'mspp_execute_time_first_add' );

// Delete from db Default execute time, last executed time plugin deactivated
function mspp_execute_time_delete() {
	delete_option('mspp_execute_time');
	delete_option('mspp_last_execute_time');
}
register_deactivation_hook( __FILE__, 'mspp_execute_time_delete' );

// Add Menu To WordPress
function missed_schedule_post_publisher_menu() {
	add_options_page('Missed Schedule Post Publisher','Missed Schedule', 'manage_options', 'missed-schedule-post-publisher', 'missed_schedule_post_publisher_manage');
}
add_action('admin_menu', 'missed_schedule_post_publisher_menu');

// // Add Menu To Toolbar
// add_action( 'admin_bar_menu', 'mspp_toolbar_menu', 999 );

// function mspp_toolbar_menu( $wp_admin_bar ) {
// 	$args = array(
// 		'id'    => 'MSPP',
// 		'title' => 'Missed Schedule Post Publisher',
// 		'href'  => admin_url() . 'admin.php?page=missed-schedule-post-publisher',
// 	);
// 	$wp_admin_bar->add_node( $args );
// 	$args = array(
// 		'id'    => 'MSPP_LEXE',
// 		'title' => '<span class="ab-icon"></span><span class="ab-label">Plugin Running Every ' . get_option("mspp_execute_time") . ' Minutes</span>',
// 		'parent' => 'MSPP'
// 	);
// 	$wp_admin_bar->add_node( $args );
// 	$args = array(
// 		'id'    => 'MSPP_LET',
// 		'title' => '<span class="ab-icon"></span><span class="ab-label">Last Executed Time (GMT): ' . gmdate('Y-m-d H:i:00', get_option('mspp_last_execute_time')) . '</span>',
// 		'parent' => 'MSPP'
// 	);
// 	$wp_admin_bar->add_node( $args );
// }

// Plugin Management Page
function missed_schedule_post_publisher_manage() {
	if(isset($_POST["action"]) && $_POST["action"]=="update"){
		// Wp_nonce check
		if (!isset($_POST['missed_schedule_post_publisher_update']) || ! wp_verify_nonce( $_POST['missed_schedule_post_publisher_update'], 'missed_schedule_post_publisher_update' ) ) {
			exit('Sorry, you do not have access to this page! https://www.ubilisim.com/missed-schedule-post-publisher-wordpress-plugin/');
		}else{
			$mspp_execute_time = sanitize_text_field($_POST['mspp_execute_time']);
			update_option('mspp_execute_time', $mspp_execute_time);
			//echo'<div class="updated"><p><strong>Settings Saved.</strong></p></div>';
            echo '<div id="setting-error-settings_updated" class="notice notice-success settings-error is-dismissible"> 
<p><strong>Settings saved.</strong></p></div>';
		}
	}
?>
<h1 class="wp-heading-inline">Missed Schedule Post Publisher</h1>
<p>Running every <strong><?php echo get_option("mspp_execute_time");?></strong> minutes. Last run time (GMT): <?php echo gmdate("Y-m-d H:i:00", get_option("mspp_last_execute_time"));?></p>
<form method="post">
	Run Every <select name="mspp_execute_time" id="mspp_execute_time" required>
		<option value="5">5 Minutes</option>
		<option value="10">10 Minutes</option>
		<option value="15">15 Minutes</option>
		<option value="20" selected>20 Minutes (Recommended)</option>
		<option value="30">30 Minutes</option>
		<option value="60">Every Hour</option>
	</select>
	<input type="hidden" name="action" value="update">
<?php wp_nonce_field('missed_schedule_post_publisher_update','missed_schedule_post_publisher_update');?>
	<input type="submit" value="Update">
</form>
<?php
}

function pubMissedPosts() {
	if (is_front_page() || is_single()) {
		global $wpdb;
		$now = gmdate("Y-m-d H:i:00");
		$sql = "Select ID from $wpdb->posts where post_status='future' and post_date_gmt<='$now'";
		$resulto = $wpdb->get_results($sql);
		if($resulto) {
			foreach( $resulto as $thisarr ) {
				wp_publish_post($thisarr->ID);
			}
		}
	}
}

// if conditions provided, check and publish missed schedule future posts.
if(time() >= get_option("mspp_last_execute_time") + (60 * get_option("mspp_execute_time"))) {
	add_action('wp_head', 'pubMissedPosts');
	update_option('mspp_last_execute_time', time());
}