<script src="<?php echo $conf->urls->assets ?>jquery-3.3.1.min.js"></script>
<script src="<?php echo $conf->urls->assets ?>scripts.js"></script>

<?php
	// scripts specific for runs page
	if( $page == 'run' ):
?>
<script src="<?php echo $conf->urls->assets ?>run.js"></script>
<?php endif; ?>
<?php
	// scripts specific for runs page
	if( $page == 'view' ):
?>
<script src="<?php echo $conf->urls->assets ?>view.js"></script>
<?php endif; ?>
</body>
</html>
