var meintopf_item_template;

function meintopf_repost(id) {
	var data = {
		action: 'meintopf_repost',
		post_id: id
	};
	// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
	jQuery.post(ajaxurl, data, function(response) {
		var html = meintopf_render_item(response);
		jQuery("#item_"+response["ID"]).replaceWith(html);
	});
};

function meintopf_next_posts(page_no) {
	var data = {
		action: 'meintopf_next_posts',
		page_no: page_no
	};
	// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
	jQuery.post(ajaxurl, data, function(posts) {
		jQuery.each(posts, function(index, value) {
			var html = meintopf_render_item(value);
			jQuery("#meintopf_feed #loader").before(html);
		});
		jQuery("#meintopf_feed #loader #load_next").off().click(function() {
				meintopf_next_posts(page_no + 1);
			});
	});
};

function meintopf_render_item(post) {
	var context = {
		title: post["post_title"],
		content: post["post_content"],
		reposted: (post["post_status"]=="publish"),
		id: post["ID"]
	}
	return meintopf_item_template(context);
}
jQuery(window).ready(function() {
	var source   = jQuery("#meintopf_reader_item_template").html();
	meintopf_item_template = Handlebars.compile(source);
	meintopf_next_posts(1);
});
