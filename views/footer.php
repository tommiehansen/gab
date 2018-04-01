<script src="<?php echo $conf->urls->assets ?>jquery-3.3.1.min.js"></script>
<script src="<?php echo $conf->urls->assets ?>scripts.js?v<?php echo $asset_version; ?>"></script>

<?php
	// scripts specific for runs page
	if( $page == 'run' ):
?>
<script src="<?php echo $conf->urls->assets ?>run.js?v=<?php echo $asset_version; ?>"></script>
<?php endif; ?>
<?php
	// scripts specific for runs page
	if( $page == 'view' ):
?>
<script src="<?php echo $conf->urls->assets ?>view.js?v=<?php echo $asset_version; ?>"></script>
<?php endif; ?>
</body>
</html>
