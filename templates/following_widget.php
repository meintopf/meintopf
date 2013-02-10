<?php	foreach ($this->feeds as $feed) { ?>
	<a href="<?= $feed["link"]; ?>" title="<?= $feed["title"]; ?>"><img src="http://g.etfv.co/<?= $feed["link"]; ?>" alt="<?= $feed["title"]; ?>" width="16" height="16"></a>
<?php } ?>
