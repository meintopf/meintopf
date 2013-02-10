<?php
	foreach ($this->feeds as $feed) {
			$baseurl = parse_url($feed);
			echo '<a href="'.$baseurl["scheme"].'://'.$baseurl["host"].'"><img src="http://g.etfv.co/'.$feed.'"></a>';
		}