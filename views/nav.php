<?php
	$nav_pages = [
		'left' => [
			'run' => ['Run Strategy', 'index.php'],
			'view' => ['View runs', 'view.php'],
		],
		'right' => [
			'scratch' => ['Scratchpad', '#'],
			'about' => ['About','#'],
		],
	];
	if(!$page) $page = 'run';
?>
<div id="nav">
	<section>
		<?php
			$left = '<div class="menu"><i class="logo tip tip--bottom tip--pink">gabby</i>';
			$right = '<div class="menu right">';

			foreach( $nav_pages as $key => $arr )
			{
				foreach( $arr as $k => $sub )
				{
					$name = $sub[0];
					$link = $conf->urls->base . $sub[1];
					$page == $k  ? $className = 'on' : $className = '';

					$link = "<a href='$link' class='$className'>$name</a>";

					# temp stuff
					if( $k == 'scratch' ) $link = str_replace('class', 'onclick="document.getElementById(\'scratchpad\').classList.toggle(\'hidden\');return false;" class', $link);
					else if( $k == 'about') $link = str_replace('class', 'onclick="alert(\'No\');return false;" class', $link);

					# add to left/right
					$key == 'left' ? $left .= $link : $right .= $link;
				}
			} // foreach()

			$left .= '</div>';
			$right .= '</div>';

			# output
			echo $left . $right;
		?>
	</section>
	<div id="goup"></div>
</div>

<div id="scratchpad" class="popover hidden">
	<i class="close" onclick="this.parentNode.classList.toggle('hidden')">&times;</i>
	<h3>Scratchpad</h3>
	<p>Write notes and stuff, saves automatically</p>
	<form>
		<textarea></textarea>
	</form>
</div>
