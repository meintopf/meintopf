<?php
/*
Plugin Name: mEintopf
Version: 0.00.1
Description: soup.io for the real world
Author: Severin Schols
Author URI: http://severin.schols.de/
License: MIT
*/

// SimplePie Wordpress Edition
include_once(ABSPATH . WPINC . '/feed.php');

// Simple templating system
include_once(dirname( __FILE__ ).'/Template.class.php');

// Plugin activation & deactivation hooks
register_activation_hook( __FILE__, 'meintopf_activate' );
register_deactivation_hook(__FILE__, 'meintopf_deactivate');

// Custom action: plugin initialization, feed fetching using cron
add_action( 'init', 'meintopf_init' );
add_action( 'meintopf_fetch_feeds', 'meintopf_reader_fetch_feeds');

// Activate the plugin
function meintopf_activate() {
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
	wp_schedule_event( time(), 'hourly', 'meintopf_fetch_feeds');
}

// deactivate plugin
function meintopf_deactivate() {
	// Remove feed fetcher from schedule
	wp_clear_scheduled_hook('meintopf_fetch_feeds');
}

// Init the plugin each time
function meintopf_init() {
	/* Custom actions */
	// Create admin menu entry
	add_action('admin_menu', 'meintopf_admin_menu_entries');
	// add javascript to header
	add_action('admin_print_scripts', 'meintopf_reader_javascript');
	// Ajax hooks
	add_action('wp_ajax_meintopf_repost', 'meintopf_ajax_repost');
}

// Action endpoint: add entries to the admin menu
function meintopf_admin_menu_entries() {
	add_menu_page('mEintopf', 'mEintopf', 'publish_posts', 'meintopf', 'meintopf_menu_page',"",'1'); 

}

// Show the admin menu page
function meintopf_menu_page() {
	
	$message = "";
	
	if (isset($_GET['action']) && $_GET['action'] == "fetch") {
		if (meintopf_reader_fetch_feeds()) {
			$message = "Feeds updated.";
		} else {
			$message = "Error getting feeds";
		}
		$out = new Template('base.php', array(
			"message" => $message,
			"content" => ""
		));
		$out->render();
	} else {
		if( isset($_POST['feedurl']) ) {
			meintopf_add_feed($_POST['feedurl']);
			$message = "Feed added.";
		}
		$posts = meintopf_reader_posts();
		
		$out = new Template('base.php', array(
			"message" => $message,
			"content" => new Template('reader.php', array("posts" => $posts))
		));
		$out->render();
	}
}

function meintopf_reader_posts() {
	$args = array(
    'posts_per_page'  => 20,
    'offset'          => 0,
    'orderby'         => 'post_date',
    'order'           => 'DESC',
    'post_type'       => 'meintopf_item',
    'post_status'     => '%',
    'suppress_filters' => true );
	$posts = get_posts( $args );
	return $posts;
}

function meintopf_reader_javascript() {
	?>
<script type="text/javascript" >
function meintopf_repost(id) {
	var data = {
		action: 'meintopf_repost',
		post_id: id
	};
	// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
	jQuery.post(ajaxurl, data, function(response) {
		alert('Got this from the server: ' + response);
	});
};
</script>
<?php
}

function meintopf_ajax_repost() {
	$id = intval($_POST["post_id"]);
	$post = get_post($id);
	if ($post->post_type == "meintopf_item" && $post->post_status == "draft") {
		$repost = array(
			'post_type' =>'post',
			'post_title' => $post->post_title,
			'post_content' => $post->post_content,
			'post_status' => "publish",
			'comment_status' => "closed",
			'ping_status' => "open",
			'to_ping' => $post->guid
		);
		$success = wp_insert_post($repost);
		if ($success <> 0) {
			$post->post_status = "publish";
			wp_update_post($post);
			echo "success";
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
	
	// get the list of feeds
	$feeds = meintopf_get_feeds();
	
	// Make sure it's not empty.
	if (count($feeds) > 0) {
		// Fetch all feeds as one, using WP's wrapper.
		$feed = fetch_feed($feeds);
		
		// We can haz feed items
		if (!is_wp_error( $feed )) {
			foreach($feed->get_items() as $item) {
				// Check if we have that post already
				$args = array(
					'meta_key' => 'meintopf_source',
					'meta_value' => $item->get_permalink(),
					'post_type' => 'meintopf_item'
				);
				$my_query = null;
				$my_query = new WP_Query($args);
				if ( !$my_query->have_posts() ) { // No, we don't. Insert it.
					// Create the post itself
					$post = array(
						'post_type' =>'meintopf_item',
						'post_title' => $item->get_title(),
						'post_content' => $item->get_content(),
						'post_date_gmt' => $item->get_gmdate('Y-m-d H:i:s'),
						'guid' => $item->get_id()
					);
					$id = wp_insert_post($post); // Insert the post
					
					update_post_meta($id, 'meintopf_source', $item->get_permalink());
					
					// more metadata
					$author = $item->get_feed()->get_title();
					if ($author_obj = $item->get_author())
						$author = $author_obj->get_name();
					$meta = array(
						'permalink' => $item->get_permalink(),
						'author' => $author,
						'feed_url' => $item->get_feed()->subscribe_url(),
						'feed_title' => $item->get_feed()->get_title()
					);
					update_post_meta($id, 'meintopf_item_metadata', $meta);
				}
				wp_reset_query();
			}
		} else {
			return false;
		}
	} else {
		return false;
	}
	// Reset cache duration
	remove_filter( 'wp_feed_cache_transient_lifetime', $return_cache_duration);
	return true;
}

/* ******************
 *	Manage Feeds
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
