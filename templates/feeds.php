
<div id="meintopf_feeds">
	<h3>Feeds you follow</h3>
	<table class="wp-list-table widefat fixed">
		<thead>
			<tr><th>Feed URL</th><th>Actions</th></tr>
		</thead>
		<tbody>
			<?php foreach ($this->feeds as $feed) { ?>
				<tr><td><?= $feed["text"]; ?></td><td><a href="<?= $feed["removal_link"]; ?>">Unfollow</a></td></tr>
			<?php } ?>
		</tbody>
	</table>
	</ul>
</div>
