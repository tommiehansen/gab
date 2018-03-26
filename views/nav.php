<?php
	if(!$page) $page = 'run';
?>
<div id="nav">
	<section>
		<i class="logo">GAB</i>
		<div class="menu">
			<a href="index.php" class="<?php if($page == 'run') echo 'on'; ?>">Run strategy</a>
			<a href="view.php" class="<?php if($page == 'view') echo 'on'; ?>">View runs</a>
		</div>
		<div class="menu right">
			<a href="#" onclick="document.getElementById('scratchpad').classList.toggle('hidden');return false;">Scratchpad</a>
			<a href="about.php" onclick='alert("No");return false;'>About</a>
		</div>
	</section>
</div>

<div id="scratchpad" class="popover hidden">
	<i class="close" onclick="this.parentNode.classList.toggle('hidden')">&times;</i>
	<h3>Scratchpad</h3>
	<p>Write notes and stuff, saves automatically</p>
	<form>
		<textarea></textarea>
	</form>
</div>
