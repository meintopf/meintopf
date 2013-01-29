<h3>Add Feed</h3>

<form action="<?= $_SERVER['REQUEST_URI'];?>" method="post">
	<ul>
		<li>
			<label for="feedurl">Feed-URL</label>
			<input type="text" maxlength="45" size="10" name="feedurl" id="feedurl">
			<input type="submit" class="button-primary">
		</li>
	</ul>
</form>

<hr>

<div id="meintopf_feed">
	<script id="meintopf_reader_item_template" type="text/x-handlebars-template">
		<div class="meintopf_reader_item" id="item_{{id}}">
			{{#if title}}
				<h3>{{title}}</h3>
			{{/if}}
			
			<div class="meintopf_reader_content">{{{content}}}</div>
			{{#unless reposted }}
				<a href="#" onclick="meintopf_repost({{id}});return false;">Repost</a>
			{{/unless}}
			{{#if reposted}}
				Already reposted.
			{{/if}}
		</div>
	</script>
	<div id="loader"><a id="load_next" href="#">Load next posts</a></div>
</div>

