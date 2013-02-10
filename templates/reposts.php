<div class="meintopf_sources">
	Also on&nbsp;
	<?php foreach ($this->pingbacks as $pingback) { ?>
		<a href="<?= $pingback->comment_author_url; ?>" title="<?= $pingback->comment_author; ?>"><img src="http://g.etfv.co/<?= $pingback->comment_author_url; ?>"></a>
	<?php } ?>
</div>
