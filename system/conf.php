<?php

	// server paths
	$dirRoot = dirname(__FILE__);
	$base_path = str_replace('/system','/', $dirRoot);
	$system_path = $base_path . 'system/';

	/*
		GET USER CONFIGURATION
		Have none? See the error that will show up.
	*/
	$userConf = false;
	if( file_exists($system_path . 'user.config.php') ){
		$userConf = true;
	}

	if( $userConf )
	{
		/* load user config */
		require_once $system_path . 'user.config.php';

		/* general conf */
		# cache
		$conf['cache'] = [
			'cache_dir' => 'cache/',
			'caching' => true,
			'cacheTime' => '6 hours',
		];

		# multi-server -- set first as primary
		$multiServer = false;

		if( is_array( $server )){
			$multiServer = true;
			$primaryServer = $server[0];
		}
		else { $primaryServer = $server; }

		$conf['multiserver'] = $multiServer; // set in conf object for later use

		$primaryServer .= "/api/"; // add /api/

		# endpoints
		$conf['endpoints'] = [
			'server' => $primaryServer,
			'backtest' => $primaryServer . 'backtest',
			'strategies' => $primaryServer . 'strategies',
			'datasets' => $primaryServer . 'scansets', // get all datasets and meta (eg. from <> to dates)
			'kill' => $primaryServer . 'killGekko', // kill Gekko (POST)
			'start' => $primaryServer . 'startGekko', // start Gekko (POST)
		];

		# multi-server, use random for backtesting
		if( $multiServer )
		{
			shuffle( $server ); // randomize array
			$conf['endpoints']['backtest'] = $server[0] . '/api/backtest'; // then set
		}


		/* url's and paths */

		// make sure server param actually set before doing stuff
		$server = $_SERVER['HTTP_HOST'];

		// web urls
		$domain = $_SERVER['HTTP_HOST'];
		$protocol = isset($_SERVER["HTTPS"]) ? 'https://' : 'http://';
		$docRoot = substr(__DIR__, strlen($_SERVER[ 'DOCUMENT_ROOT' ]));

		// add slash before docRoot if it doesn't exist
		if( substr($docRoot, 0, 1) !== "/" ) $docRoot = '/' . $docRoot;

		// create base_url
		$base_url = $protocol . $domain . $docRoot;
		$base_url = str_replace('system', '', $base_url);

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
			'base' => $base_url,
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

		# DEBUG
		#include_once 'functions.php';
		#prp($domain);
		#prp($docRoot);
		#prp($base_url);
		#prp($conf);
		#exit;

		/* set large defaults for PHP */
		error_reporting(E_ALL);
		ini_set('display_errors', 1);
		ini_set('memory_limit','512M');
		set_time_limit(SERVER_TIMEOUT); // 60 minutes

	}
	// no user conf
	else {

		/* set large defaults for PHP */
		error_reporting(E_ALL);
		ini_set('display_errors', 1);

		$base_url = './';
		$conf['urls'] = [
			'assets' => $base_url . 'assets/',
		];

		$conf = json_decode(json_encode($conf));

		$page = 'Error';
		$page_title = 'Error -- user config';

		require './views/header.php';

		echo "
			<style>
				#nav, #scratchpad { display: none; } body { padding: 0 !important; margin:0 !important; height: auto; }
				h1 { margin-top:0; color: #fff !important; }
				body { background: linear-gradient(45deg, navy, hotpink); }
				body,h1,h2,h3,h4,h5,h6,p { color: rgba(255,255,255,0.8); }
				h1 + p { font-weight: 200; }
				h6 { color: #fff; }
				ol { line-height: 1.8; }
				.red {
					background: #4527a0;
    				color: #fff;
					display: inline-block;
					padding: 10px 20px;
					border-radius: 5px;
				}
				a {	color: #fff; }
				a:hover { color: rgba(255,255,255,0.8); }
				#error {
					font-size:1.8rem;line-height:1.5;
				}
				ol b { color: #fff; }
				.center-vertical {
					background: linear-gradient(45deg, #FF9800, #F06292);
					background: linear-gradient(45deg, #3f51b5, #ec407a);
					margin:0;
				}
				hr { margin: 0 0 30px; padding: 0 0 30px 0; border-color: rgba(255,255,255,0.3); }
				section ol { list-style-type: decimal !important; }
			</style>
			<div class='center-vertical fullscreen'>
				<section id='error' style='max-width: 700px'>
					<h1>Complete system failure</h1>
					<p>Something went straight to hell</p>
					<p><b class='red'>No file for user configuration could be found</b></p>
					<hr>
					<h6>Do this</h6>
					<ol>
						<li>Make a copy of <b>system/user.config.sample.php</b>
						<li>Modify it to suit your needs and rename it to <b>system/user.config.php</b></li>
						<li>See if it bloody works</li>
					</ol>
					<p>You can also perform a simple compatibility check by visiting <a href='sanitycheck.php'>sanitycheck.php</a></p>
				</section>
			</div>
		";

		require './views/footer.php';

		echo "
			<script>
				talk.cancel();
				talk.say('OH NO! Complete system failure!');
				talk.say('No file for user configuration could be found anywhere!');
				talk.say('Fix this problem by doing this:');

				let lis = $('ol').find('li');
				lis.each(function(index){
					var txt = this.innerText;
					txt = (index+1) + '. ' + txt;
					talk.say( txt );
				})

				talk.say('You can also perform a simple compatibility check by visiting sanitycheck. Or maybe for you it\'s the insanity check? LOL!');
			</script>
		";

		exit;

	} // end !user file
