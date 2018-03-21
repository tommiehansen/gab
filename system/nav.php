<?php
	if(!$page) $page = 'select';
?>
<div id="nav">
	<section>
		<i class="logo">GAB</i>
		<div class="menu">
			<a href="select.php" class="<?php if($page == 'select') echo 'on'; ?>">Run strategy</a>
			<a href="view.php" class="<?php if($page == 'view') echo 'on'; ?>">View runs</a>
		</div>
		<div class="menu right">
			<a href="#" onclick="document.getElementById('scratchpad').classList.toggle('hidden');return false;">Scratchpad</a>
			<a href="about.php" onclick='alert("No");return false;'>About</a>
		</div>
	</section>
</div>
