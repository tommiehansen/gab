<?php
	/* USER SETTINGS --------------------------------------- */

	// your server
	$server = 'http://localhost:3000/api/';

	// timeout
	define('SERVER_TIMEOUT', 600); // seconds, 600 = 10 minutes

	/*
		NOTE: The trailing for $server / after /api/
		If you change server you will need to
		'clear cache' before running to get new
		datasets and strategies for that specific
		server.

		NOTE: Server timeout is used by everything
		inside the system like cURL timeout, script
		max execution time etc.
	*/

	/* ----------------------------------------------------- */




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
	];



	/* url's and paths */

	// make sure server param actually set before doing stuff
	$server = $_SERVER['HTTP_HOST'];

	// web urls
	$domain = $_SERVER['HTTP_HOST'];
	$protocol = isset($_SERVER["HTTPS"]) ? 'https://' : 'http://';
	$base_url = $protocol . $domain . substr(__DIR__, strlen($_SERVER[ 'DOCUMENT_ROOT' ])) . '/';
	$base_url = str_replace('system/', '', $base_url);

	// server paths
	$dirRoot = dirname(__FILE__);
	$base_path = str_replace('/system', '/',$dirRoot);

	# system dirs
	$conf['dirs'] = [
		'base' => $base_path,
		'system' => $base_path . 'system/',
		'results' => $base_path . 'results/',
		'views' => $base_path . 'views/',
		'cache' => $base_path . $conf['cache']['cache_dir'],
	];

	$dirs = (object) $conf['dirs'];

	$conf['urls'] = [
		'system' => $base_url . 'system/',
		'results' => $base_url . 'results/',
		'assets' => $base_url . 'assets/',
	];

	/* database fields */

	$conf['db_fields'] = [

		'blobs' => [
			'id' => 'TEXT PRIMARY KEY UNIQUE',
			'report' => 'BLOB',
			'roundtrips' => 'BLOB',
		],

		'results' => [
			'id' => 'TEXT PRIMARY KEY UNIQUE',
			'candle_size' => 'INTEGER',
			'strategy_profit' => 'INTEGER',
			'market_profit' => 'INTEGER',
			'sharpe' => 'REAL',
			'alpha' => 'REAL',
			'trades' => 'INTEGER',
			'trades_win' => 'INTEGER',
			'trades_lose' => 'INTEGER',
			'trades_win_percent' => 'REAL',
			'trades_win_avg' => 'REAL',
			'trades_lose_avg' => 'REAL',
			'trades_best' => 'REAL',
			'trades_worst' => 'REAL',
			'trades_per_day' => 'REAL',
			'strat' => 'BLOB',
		],
	];

	// turn to object
	$conf = json_decode(json_encode($conf));


	/* set large defaults for PHP */
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
	ini_set('memory_limit','512M');
	set_time_limit(3600); // 60 minutes
