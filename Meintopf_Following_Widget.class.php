<?php
// Add following widget
class Meintopf_Following_Widget extends WP_Widget {
	
	function __construct() {
		$widget_ops = array('classname' => 'Meintopf_Following_Widget', 'description' => 'A list of your following feeds.' );
		parent::__construct('Meintopf_Following_Widget', 'Following', $widget_ops);
	}

	// Website widget
	function widget($args, $instance) {
		extract($args, EXTR_SKIP);
		echo $before_widget;
		$title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
		if ( !empty( $title ) ) { echo $before_title . $title . $after_title; };
		$feeds = meintopf_get_feeds();
		$out = new Template('following_widget.php', array(
			"feeds" => $feeds
		));
		$out->render();
		echo $after_widget;
	}

	// What happens on update
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		return $instance;
	}

	// Form in the admin panel
	function form($instance) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'entry_title' => '', 'comments_title' => '' ) );
		$title = strip_tags($instance['title']);
?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo attribute_escape($title); ?>" /></label></p>
<?php
	}
}

