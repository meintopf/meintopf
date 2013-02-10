
<div id="meintopf_feeds">
	<h3>"Add to mEintopf" scriptlet</h3>
	To add the feed of the page you are currently on to your mEintopf, you can use this scriptlet. Just drag-and-drop it into you bookmarks bar, and click it when you are on a page which you want to follow.
	<div class="pressthis"><a href="javascript:(function(){window.open('<?= admin_url("admin.php?page=meintopf");?>&action=add&feedurl='+encodeURIComponent(window.location),'_blank');})()"><span>Add to mEintopf</span></a></div>
	<h3>Feeds you follow</h3>
	<table class="wp-list-table widefat fixed">
		<thead>
			<tr><th width="50">Avatar</th><th>Feed URL</th><th>Actions</th></tr>
		</thead>
		<tbody>
			<?php foreach ($this->feeds as $feed) { ?>
				<tr><td><img src="http://g.etfv.co/<?= $feed["text"]; ?>"></td><td><?= $feed["text"]; ?></td><td><a href="<?= $feed["removal_link"]; ?>">Unfollow</a></td></tr>
			<?php } ?>
		</tbody>
	</table>
	</ul>
</div>
