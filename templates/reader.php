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

<?php foreach( $this->posts as $post ) { ?>
	<div class="meintopf_reader_item">
		<?php if ($post->post_title) { ?>
			<h3><?= $post->post_title;?></h3>
		<?php } ?>
		
		<div class="meintopf_reader_content"><?= $post->post_content; ?></div>
		
		<?php if ($post->post_status == "draft") { ?>
			<a href="#" onclick="meintopf_repost(<?= $post->ID;?>)">Repost</a>
		<?php } else { ?>
			Already Reposted
		<?php } ?>
	</div>
<?php } ?>
