<?php

	/* core */
	$server = 'http://localhost:3000/api/';


	/* general conf */
	# cache
	$conf['cache'] = [
		'cache_dir' => 'cache/',
		'caching' => true,
		'cacheTime' => '6 hours',
	];


	# endpoints
	$conf['endpoints'] = [
		'server' => $server,
		'backtest' => $server . 'backtest',
		'strategies' => $server . 'strategies',
		'datasets' => $server . 'scansets', // get all datasets and meta (eg. from <> to dates)
		'kill' => $server . 'killGekko', // kill Gekko (POST)
		'start' => $server . 'startGekko', // start Gekko (POST)
		'script_files' => __DIR__,
	];


	# system dirs
	$conf['dirs'] = [
		'system' => 'system/',
		'results' => 'results/',
		'cache' => $conf['cache']['cache_dir'],
		'logs' => 'logs/',
	];

	/* allow config changes from UI */

	# disable caching
	if( isset($_GET['recache']) && $_GET['recache'] == 1 || !$conf['cache']['caching'] )
	{
		$conf['cache']['cacheTime'] = '1 second';
	}

	// turn to object
	$conf = json_decode(json_encode($conf));


	/* set large defaults for PHP */
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
	ini_set('memory_limit','512M');
	set_time_limit(900);
