<div class="wrap">
	<div id="icon-edit-comments" class="icon32"></div><h2>mEintopf - <?= $this->title; ?></h2>
	
	<?php if ($this->message) { ?>
		<div id="message" class="updated fade"><p><strong><?= $this->message; ?></strong></p></div>
	<?php } ?>
	
	<?= ($this->content instanceof Template) ? $this->content->render() : $this->content; ?>
</div>
