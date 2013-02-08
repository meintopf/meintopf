<div id="poststuff">
	<div id="post-body" class="metabox-holder columns-2">
		<div id="post-body-content">
			
			<div id="meintopf_feed">
				<script id="meintopf_reader_item_template" type="text/x-handlebars-template">
					<div class="meintopf_reader_item" id="item_{{id}}">
						{{#if title}}
							<h3>{{title}}</h3>
						{{/if}}
						
						<div class="meintopf_reader_content">{{{content}}}</div>
						<div class="repost_link">
							{{#unless reposted }}
								<a href="#" onclick="meintopf_repost({{id}});return false;">Repost</a>
							{{/unless}}
							{{#if reposted}}
								Already reposted.
							{{/if}}
						</div>
					</div>
				</script>
				<div id="loader"><a id="load_next" href="#">Load next posts</a></div>
			</div>
		</div>
		
		<div id="postbox-container-1" class="postbox-container">
			<div class="postbox">
				<div class="handlediv" title="Click to toggle"><br /></div><h3 class='hndle'><span>Add Feed</span></h3>
				<div class="inside">
					<form action="<?= $_SERVER['REQUEST_URI'];?>" method="post">
						<ul>
							<li>
								<label for="feedurl">Feed-URL</label><br>
								<input type="text" maxlength="255" size="30" name="feedurl" id="feedurl">
								<input type="submit" class="button button-primary button-large" value="Add Feed">
							</li>
						</ul>
					</form>
				</div>
			</div>
		</div>
		
	</div>
</div>
<img src="<?= plugins_url( 'images/reposting.gif' , dirname(__FILE__) );?>" style="display:none;" id="meintopf_repost_gif">
