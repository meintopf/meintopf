<?php
/*
Plugin Name: mEintopf
Version: 0.2.1
Description: soup.io for the real world
Author: Severin Schols
Author URI: http://severin.schols.de/
License: MIT
*/

// SimplePie Wordpress Edition
include_once(ABSPATH . WPINC . '/feed.php');

// Simple templating system
include_once(dirname( __FILE__ ).'/Template.class.php');

// Include other classes
include_once(dirname( __FILE__ ).'/Meintopf_Following_Widget.class.php');

// Plugin activation & deactivation hooks
register_activation_hook( __FILE__, 'meintopf_activate' );
register_deactivation_hook(__FILE__, 'meintopf_deactivate');

// Custom action: plugin initialization, feed fetching using cron
add_action( 'init', 'meintopf_init' );
add_action( 'meintopf_fetch_feeds', 'meintopf_reader_fetch_feeds');
add_action( 'wp_enqueue_scripts', 'meintopf_scripts' );
add_action( 'wp_before_admin_bar_render', 'meintopf_adminbar' );
add_action( 'widgets_init', 'meintopf_widget_registration' ); 

// Filters to do things to other things
add_filter( 'the_content', 'meintopf_filter_content_append' );
add_filter( 'comments_array', 'meintopf_filter_comments', 20, 2 );
add_filter( 'cron_schedules', 'meintopf_cron_interval');

// Activate the plugin
function meintopf_activate() {
	// Version check
	global $wp_version;
	if (version_compare($wp_version,"3.5","<"))	{
		exit("mEintopf requires WordPress 3.5 or newer.");
	}
	
	// Generate options array
	$options = array("feeds" => array());
	add_option("meintopf_options",$options);
	
	// Create custom post type to store soup content
	if (!post_type_exists('meintopf_item')) {
		$args = array(
			'public' => false,
			'exclude_from_search' => false,
			'publicly_queryable' => false,
			'show_ui' => false, 
			'show_in_menu' => false, 
			'query_var' => true,
			'rewrite' => array( 'slug' => 'book' ),
			'capability_type' => 'post',
			'has_archive' => false, 
			'hierarchical' => false,
			'menu_position' => null,
			'map_meta_cap' => false,
			'query_var' => false,
			'can_export' => false,
			'supports' => array( 'title', 'editor', 'author', 'thumbnail', 'custom-fields'  )
		);
		register_post_type( 'meintopf_item', $args );
	}
	
	// schedule feed fetcher
	wp_schedule_event( time(), '10_minutes', 'meintopf_fetch_feeds');
	
	// update/check existing data
	meintopf_data_update();
}

// deactivate plugin
function meintopf_deactivate() {
	// Remove feed fetcher from schedule
	wp_clear_scheduled_hook('meintopf_fetch_feeds');
}

// Check data consistency on activation
function meintopf_data_update() {
	$posts = meintopf_reader_get_posts(0,-1);
	$feeds = meintopf_get_feeds();
	foreach ($posts as $post) {
		// item metadata "feed_link" added in commit 0b13bc0c3818d8781e265d158ee2250fda5175b7, update previous posts
		if (!array_key_exists("feed_link",$post->meta)) {
			$post->meta["feed_link"] = $feeds[$post->meta["feed_url"]]["link"];
			update_post_meta($post->ID, 'meintopf_item_metadata', $post->meta);
		}
	}
}

// Init the plugin each time
function meintopf_init() {
	// Register JS
	wp_register_script( 'meintopf_admin_js', plugins_url('js/script.js', __FILE__) );
	wp_register_script( 'handlebars', plugins_url('js/handlebars.js', __FILE__) );
	
	// Register CSS
	wp_register_style( 'meintopf_css', plugins_url('css/style.css', __FILE__) );
	wp_register_style( 'meintopf_admin_css', plugins_url('css/admin.css', __FILE__) );
	
	/* Custom actions */
	// Create admin menu entry
	add_action('admin_menu', 'meintopf_admin_menu_entries');
	// Ajax hooks
	add_action('wp_ajax_meintopf_repost', 'meintopf_ajax_repost');
	add_action('wp_ajax_meintopf_next_posts', 'meintopf_ajax_next_posts');
}

// Action endpoint: add entries to the admin menu
function meintopf_admin_menu_entries() {
	$page = add_menu_page('mEintopf', 'mEintopf', 'publish_posts', 'meintopf', 'meintopf_page_reader',"",'1');
	add_submenu_page( "meintopf", "mEintopf - Stream", "Stream", "publish_posts", "meintopf", "meintopf_page_reader" );
	add_submenu_page( "meintopf", "mEintopf - Feeds", "Feeds", "publish_posts", "feeds", "meintopf_page_feeds" );
	/* Using registered $page handle to hook script load */
	add_action('admin_print_scripts-' . $page, 'meintopf_admin_scripts');
}

// Scripts to add to every page
function meintopf_scripts() {
	wp_enqueue_style( 'meintopf_css' );
}

// Scripts to add to admin page
function meintopf_admin_scripts() {
	wp_enqueue_script( 'handlebars' );
	wp_enqueue_script( 'meintopf_admin_js' );
	/* Enqueue WordPress' script for handling the meta boxes */
	wp_enqueue_script('postbox');
	
	wp_enqueue_style( 'meintopf_admin_css' );
}

// Show the admin menu page
function meintopf_page_reader() {
	
	$message = "";
	
	if (isset($_GET['action']) && $_GET['action'] == "fetch") {
		// Manual feed fetching
		if (meintopf_reader_fetch_feeds()) {
			$message = "Feeds updated.";
		} else {
			$message = "Error getting feeds";
		}
		$out = new Template('base.php', array(
			"title" => "Manual Feed Update",
			"message" => $message,
			"content" => ""
		));
		$out->render();
	} else {
		if( isset($_REQUEST['feedurl']) ) {
			//trying to add a new feed.
			if (isset($_GET['feedurl'])) {
				$feedurl = rawurldecode($_GET['feedurl']);
			} else {
				$feedurl = $_POST["feedurl"];
			}
			$success = meintopf_add_feed($feedurl);
			if ($success) {
				$message = "Feed added.";
			} else {
				$message = "Could not add feed " . $feedurl;
			}
		}
		
		// create and render template
		$out = new Template('base.php', array(
			"title" => "Your Stream",
			"message" => $message,
			"content" => new Template('reader.php', array())
		));
		$out->render();
	}
}


// Show the feed management page
function meintopf_page_feeds() {
	$message = "";
	
	// Add a feed, if asked to do so
	if( isset($_POST['action']) && isset($_POST['feedurl']) ) {
		if ($_POST['action'] == "add") {
			//trying to add a new feed.
			$success = meintopf_add_feed($_POST['feedurl']);
			if ($success) {
				$message = "Feed added.";
			} else {
				$message = "Could not add feed.";
			}
		} 
	}
	
	// Remove a feed if asked to do so.
	if (isset($_GET['action']) && isset($_GET['feedurl']) ) {
		if ($_GET['action'] == "remove") {
			//trying to remove a feed.
			$feed_url = rawurldecode($_GET['feedurl']);
			$success = meintopf_remove_feed($feed_url);
			if ($success) {
				$message = "Feed removed.";
			} else {
				$message = "Could not remove feed.";
			}
		}
	}
	
	$feeds = meintopf_get_feeds();
	$feeds_template = array();
	foreach ($feeds as &$feed) {
		$feed["text"] = htmlentities($feed["feed_url"]);
		$feed["removal_link"] = add_query_arg( array( "action" => "remove",
				"feedurl" => htmlentities(rawurlencode($feed["feed_url"]))));
	}
	
	// create and render template
	$out = new Template('base.php', array(
		"title" => "Your followed feeds",
		"message" => $message,
		"content" => new Template('feeds.php', array(
			"feeds" => $feeds
			))
	));
	$out->render();
}

// Get all items
function meintopf_reader_get_posts($page_no, $posts_per_page = 10) {
	$args = array(
    'posts_per_page'  => $posts_per_page,
    'paged'           => $page_no,
    'orderby'         => 'post_date_gmt',
    'post_type'       => 'meintopf_item',
    'post_status'     => 'any',
    'suppress_filters' => true );
	$posts = get_posts($args, ARRAY_A);
	foreach ($posts as &$post) {
		$post->meta = get_post_meta($post->ID, 'meintopf_item_metadata', true);
	}
	return $posts;
}

// AJAX function to grab new items. Called with attribute "page_no".
function meintopf_ajax_next_posts() {
	// Get attribute
	$page_no = intval($_POST["page_no"]);
	// Get items
	$posts = meintopf_reader_get_posts($page_no);
	// Send items back as json
	wp_send_json($posts);
}

// AJAX function for reposting a single item. Called with attribute "post_id"
function meintopf_ajax_repost() {
	// Get attribute
	$id = intval($_POST["post_id"]);
	// Get the post to be reposted
	$post = get_post($id);
	// Check if it is a valid post, and has not been reposted before.
	if ($post->post_type == "meintopf_item" && $post->post_status == "draft") {
		// Grab post metadata
		$meta = get_post_meta($post->ID, 'meintopf_item_metadata', true);
		// create the real post
		$repost = array(
			'post_type' =>'post',
			'post_title' => $post->post_title,
			'post_content' => $post->post_content,
			'post_status' => "publish",
			'comment_status' => "closed",
			'ping_status' => "open"
		);
		// insert it
		$success = wp_insert_post($repost);
		if ($success <> 0) {
			// Append metadata for repost
			update_post_meta($success, 'meintopf_item_metadata', $meta);
			
			// Send pingback
			pingback(meintopf_filter_content_append($post->post_content,$success), $success);
			
			// Set meintopf item status to publish, as in "has been reposted"
			$post->post_status = "publish";
			wp_update_post($post);
			
			$post->meta = $meta;
			// Send meintopf item back as json.
			wp_send_json($post);
		} else {
			echo "failure while posting";
		}
	} else {
		echo "failure while getting post";
	}
	die();
}

// Fetch updates
function meintopf_reader_fetch_feeds() {
	// Set feed wrapper's cache lifetime to 30 minutes instead of 12 hours.
	$return_cache_duration = create_function( '$a', 'return 1800;' ) ;
	add_filter( 'wp_feed_cache_transient_lifetime', $return_cache_duration);
	
	// Allow additional tags in KSES via filter.
	add_filter( 'wp_kses_allowed_html', 'meintopf_adjust_kses_tags');
	
	// get the list of feeds
	$options = get_option("meintopf_options");
	$feeds = $options["feeds"];
	
	// Make sure it's not empty.
	if (count($feeds) > 0) {
		
		// Fetch each feed individually.
		foreach ($feeds as $feed_url) {
			
			// Fetch feed, using WP's SimplePie wrapper.
			$feed = fetch_feed($feed_url);
			
			// We can haz feed items
			if (!is_wp_error( $feed )) {
				foreach($feed->get_items() as $item) {
					// Check if we have that post already
					$args = array(
						'meta_key' => 'meintopf_guid',
						'meta_value' => $item->get_id(false),
						'post_type' => 'meintopf_item',
						'post_status'     => 'any'
					);
					$my_query = null;
					$my_query = new WP_Query($args);
					if ( !$my_query->have_posts() ) { // No, we don't. Insert it.
						// Create the post itself
						$post = array(
							'post_type' =>'meintopf_item',
							'post_title' => htmlspecialchars_decode($item->get_title()),
							'post_content' => $item->get_content(),
							'post_date' => $item->get_date('Y-m-d H:i:s'),
							'post_date_gmt' => $item->get_gmdate('Y-m-d H:i:s'),
							'guid' => $item->get_id()
						);
						$id = wp_insert_post($post); // Insert the post
						
						update_post_meta($id, 'meintopf_guid', $item->get_id(false));
						
						// more metadata
						$author = $item->get_feed()->get_title();
						if ($author_obj = $item->get_author())
							$author = $author_obj->get_name();
						$meta = array(
							'permalink' => $item->get_permalink(),
							'author' => $author,
							'feed_url' => $item->get_feed()->subscribe_url(),
							'feed_title' => $item->get_feed()->get_title(),
							'feed_link' => $item->get_feed()->get_link()
						);
						update_post_meta($id, 'meintopf_item_metadata', $meta);
					}
					wp_reset_query();
				}
			} else {
				// Do nothing for now, as it would interrupt other feeds.
			}
		}
	} else {
		return false;
	}
	// Reset cache duration
	remove_filter( 'wp_feed_cache_transient_lifetime', $return_cache_duration);
	// remove extra kses tags
	remove_filter( 'wp_kses_allowed_html', 'meintopf_adjust_kses_tags');
	return true;
}

// FILTER: Allow extra tags in pulled feeds. For now: iframe (for youtube embedding)
function meintopf_adjust_kses_tags($allowedtags) {
	$allowedtags['iframe'] = array( 'src' => true, 'width' => true, 'height' => true, 'frameborder' => true, 'allowfullscreen' => true );
	return $allowedtags;
}

// FILTER: Append "Reposted from" to any post with the given set of metadata.
// Exact content of the append is pulled from template
function meintopf_filter_content_append( $content, $id = -1 ) {
	if ($id == -1) {
		$id = get_the_ID();
	}
	// get the metadata
	$meta = get_post_meta( $id , 'meintopf_item_metadata', true);
	// Got something?
	if ($meta != "") {
		// Create instance of template with given values.
		$out = new Template('repost.php', array(
			"feedurl" => $meta["feed_url"],
			"permalink" => $meta["permalink"],
			"title" => $meta["feed_title"]
		));
		// Render the template out to a string and append it to the content.
		$content = $content . $out->rendertoString();
	}
	
	// get pingbacks
	$args = array( "type" => "pingback", "post_id" => $id);
	$pingbacks = get_comments($args);
	if (count($pingbacks) > 0) {
		// Create instance of template with given values.
		$out = new Template('reposts.php', array(
			"pingbacks" => $pingbacks
		));
		// Render the template out to a string and append it to the content.
		$content = $content . $out->rendertoString();
	}
	
	return $content;
}

/* ******************
 *	Manage Feeds
 * ******************/
// Get list of feeds we are subscribed to
function meintopf_get_feeds() {
	// try to get feeds from cache
	$feeds = wp_cache_get('feeds', 'meintopf');
	// if cache is empty, gather information
	if ($feeds === false) {
		$options = get_option("meintopf_options");
		$feeds = array();
		foreach ($options["feeds"] as $feed_url) {
			$feed = fetch_feed($feed_url);
			// Feed is valid
			if (!is_wp_error( $feed )) {
				$feeds[$feed_url] = array(
					"feed_url" => $feed_url,
					"title" => $feed->get_title(),
					"link" => $feed->get_link()
				);
			}				
		}
		// store in cache for 24h
		wp_cache_set('feeds', $feeds, 'meintopf', 86400);
	}
	return $feeds;
}

// Add a feed to the list
function meintopf_add_feed($feed_url) {
	// Get mEintopf options
	$options = get_option("meintopf_options");
	// Check if feed is already subscribed to.
	if (in_array($feed_url, $options['feeds'])) {
		return false;
	}
	// Initialize a simplepie object with the given url
	$feed = fetch_feed($feed_url);
	// Not a valid feed?
	if (is_wp_error( $feed )) {
		// Failure.
		return false;
	} else { // Feed is valid
		// Check again if feed is already subscribed to.
		if (in_array($feed->subscribe_url(), $options['feeds'])) {
			return false;
		}
		// Add subscribe url to feed list (instead of given url to avoid autodiscovery)
		$options['feeds'][] = $feed->subscribe_url(); 
		update_option('meintopf_options',$options);
		// Clear cached feeds
		wp_cache_delete("feeds", "meintopf");
		// Schedule feed fetching.
		wp_schedule_single_event(time(), 'meintopf_fetch_feeds');
		// spawn crown now.
		spawn_cron(time());
		// Success.
		return true;
	}
}

// Remove a feed from the list
function meintopf_remove_feed($feed_url) {
	// Get mEintopf options
	$options = get_option("meintopf_options");
	// Check if feed is subscribed to.
	if (!in_array($feed_url, $options['feeds'])) {
		return false;
	}
	// Search and remove item.
	$index = array_search($feed_url, $options['feeds']);
	unset($options['feeds'][$index]);
	// Store updated feed list
	update_option('meintopf_options',$options);
	return true;
}

// Add mEintopf Feed-Link to Adminbar
function meintopf_adminbar() {
	global $wp_admin_bar;
        
	$wp_admin_bar->add_menu( array(
		'id'    => 'mEintopf',
		'title' => 'mEintopf Stream',
		'href'  => admin_url("admin.php?page=meintopf")
	));
}

// Filter pingbacks out
function meintopf_filter_comments($comments, $post_id) {
	foreach ($comments as $key => $comment) {
		if ($comment->comment_type == "pingback") {
			unset($comments[$key]);
		}
	}
	return $comments;
}

function meintopf_widget_registration(){
	register_widget('Meintopf_Following_Widget');
}

// Add a new cron interval: 10 minutes
// http://www.ontimedesign.net/tips-tricks/how-run-wordpress-wp_cron-every-10-minutes/
function meintopf_cron_interval($interval) {
	$interval['10_minutes'] = array('interval' => 10*60, 'display' => 'Once every 10 minutes');
	return $interval;
}
