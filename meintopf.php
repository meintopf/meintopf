<?php
/*
Plugin Name: mEintopf
Version: 0.01.1
Description: soup.io for the real world
Author: Severin Schols
Author URI: http://severin.schols.de/
License: MIT
*/

//include_once('libs/simplepie_1.3.1.mini.php');

register_activation_hook( __FILE__, 'meintopf_activate' );
add_action( 'init', 'meintopf_init' );

// Activate the plugin
function meintopf_activate() {
	$options = array("feeds" => array());
	add_option("meintopf_options",$options);
}

// Init the plugin each time
function meintopf_init() {
	add_action('admin_menu', 'meintopf_admin_menu_entries');
}

// Add entries to the admin menu
function meintopf_admin_menu_entries() {
	add_menu_page('mEintopf', 'mEintopf', 'publish_posts', 'meintopf', 'meintopf_menu_page',"",'1'); 

}

// Show the admin menu page
function meintopf_menu_page() {
	if( isset($_POST['feedurl']) ) {
		meintopf_add_feed($_POST['feedurl']);
	}
	echo "<div><h2>Add Feed</h2><form action=\"\" method=\"post\"><label>Feed-URL:</label><input type=\"text\" name=\"feedurl\"><input type=\"submit\"></form></div><hr>";
	$feeds = meintopf_get_feeds();
	if (count($feeds) == 0) {
		echo "No feeds yet!";
	} else {
		$feed = new SimplePie();
		$feed->set_feed_url($feeds);
		$feed->set_cache_duration (600);
		$success = $feed->init();
		$feed->handle_content_type();
		if ($success) {
			foreach($feed->get_items() as $item) {
				echo "<div><h3>".$item->get_title()."</h3><div>".$item->get_content()."</div><pre>".$item->get_id()."</pre></div><hr>";
			}
		}
	}
}

/* ******************
 *  Manage Feeds
 * ******************/

function meintopf_get_feeds() {
	$options = get_option("meintopf_options");
	return $options["feeds"];
}

function meintopf_add_feed($feed) {
	$options = get_option("meintopf_options");
	$options['feeds'][] = $feed;
	update_option('meintopf_options',$options);
}
?>
