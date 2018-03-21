<?php
namespace GAB;

class core {

	function __construct( $conf )
	{
		# requires
		require_once 'functions.php';

		# vars
		$this->conf = $conf;
		$this->endpoints = $conf->endpoints;
		$this->cache = $conf->cache;

		# libs
		require_once $this->conf->dirs->system . 'class.toml.php';

		# TEMP: always clear all
		#$this->clear_cache( $conf->cache->cache_dir );
		#$this->clear_cache( $conf->dirs->results );

		$this->init();


	} // construct()


	# do stuff on init
	private function init(){

		/* auto create folders if not exist */
		$cache = $this->conf->cache;
		if(!file_exists($cache->cache_dir))
		{
			#prp('created dir: ' . $cache->cache_dir);
			mkdir($cache->cache_dir, 0755, true);
			file_put_contents( $cache->cache_dir . '.gitignore', ''); # create emtpy .gitignore
		}

		$results_dir = $this->conf->dirs->results;
		if(!file_exists($results_dir))
		{
			#prp('created dir: ' . $results_dir);
			mkdir($results_dir, 0755, true);
			file_put_contents( $results_dir . '.gitignore', ''); # create emtpy .gitignore
		}

	}


	/*
		PUBLIC
		GET / POST
	*/


	# get stratgies
	# returns array of strategies and meta
	public function get_strategies( $parse = true )
	{

		$strats = $this->get( $this->endpoints->strategies, 'strategies.cache' );

		$new = [];
		$noTOML = [];

		# create simpler array
		foreach($strats as $strat)
		{
			$name = $strat['name'];
			$params = $strat['params'];
			$parse ? $parsed = $this->parse_toml($params) : $parsed = $params;

			if( empty($parsed) )
			{
				$noTOML[$name] = []; // we could potentially somehow parse the JS files and auto-create TOML-files (dreadfully tedious)
			}
			else {
				$new[$name] = $parsed; // key/values to arrays
			}
		}

		$new['notoml'] = $noTOML;
		$strats = null;

		return $new;
	}

	/**
	 *  @brief Get strategy
	 *
	 *  @param [string] $name Name for strategy
	 *  @return Returns array with strategy name and TOML-string or fals if no match
	 *
	 */
	public function get_strategy( $name )
	{
		$strats = $this->get( $this->endpoints->strategies, 'strategies' );

		$match = false;

		foreach( $strats as $strat )
		{
			if( $strat['name'] == $name ){
				$match = $strat['params'];
			}
		}

		return $match;
	}


	# get available datasets
	# returns array of datasets
	public function get_datasets()
	{
		$data = $this->post( $this->conf->endpoints->datasets, 'datasets.cache', '{}' );
		$data = $data->data->datasets;
		$data = json_decode(json_encode($data), true);

		return $data;
	}


	/* other */

	# parse TOML, returns array
	public function parse_toml( $str )
	{
		return \Toml\Parser::fromString($str);
	}



	# return TOML from array
	public function create_toml( $str )
	{

	}

	# clear all caches
	# use: clear_cache( $dir );
	public function clear_cache( $dir )
	{
		if(file_exists($dir))
		{
			$files = array_diff(scandir($dir), array('.', '..'));
			foreach ($files as $file) {
				(is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
			}
			rmdir($dir);
		}

	} // clear_cache()

	# config
	public function set_config( $settings = false )
	{

		if( !$settings ) die('No settings for set_config()');

		// define all params
		$c = [

			'gekkoConfig' => [

				# should change
				'watch' => [
					'exchange' => 'binance', // e.g. 'binance'
					'currency' => 'USDT', // e.g. 'USDT'
					'asset' => 'ETH', // e.g. 'ETH'
				],

				# strat settings (from get_strategies())
				/*
				'TEMA' => [
					#'__empty' => 0, // empty needed if no TOML-file
					'short' => 10,
					'long' => 80,
					'SMA_long' => 0,
				],
				*/

				'tradingAdvisor' => [
					'enabled' => true,
					'method' => 'TEMA', # needs to change to strategy name!
					'candleSize' => 60, // always defined in minutes
					'historySize' => 10
				],

				// not required -- default is to use all data
				/*
				'backtest' => [
					'daterange' => [
						'from' => '2017-11-30T22:08:00Z',
						'to' => '2018-01-13T22:58:00Z'
					],
				],
				*/


				/* stuff that will not change */

				// needed to get results
				'performanceAnalyzer' => [
					'riskFreeReturn' => 2,
					'enabled' => 1,
				],

				'valid' => 1,
				'paperTrader' => [
					'feeMaker' => 0.25,
					'feeTaker' => 0.25,
					'feeUsing' => 'maker',
					'slippage' => 0.05,
					'simulationBalance' => [
						'asset' => 1,
						'currency' => 100
					],
					'reportRoundtrips' => 1,
					'enabled' => 1,
				],

			], // gekkoConfig

			'data' => [
				'candleProps' => 0, // 0 = disable, else ['close','start']
				'indicatorResults' => 0, // 0 or 1 (does nothing?)
				'report' => 1,
				'roundtrips' => 1, // set to 1 to get all roundtrips
				'trader' => 0, // does nothing?
			],

		];


		# to object
		$c = json_decode(json_encode($c));
		$s = $settings;

		# set values
		$gc = $c->gekkoConfig;
		$watch = $gc->watch;

		$watch->exchange = $s['pair']['exchange'];
		$watch->currency = $s['pair']['currency'];
		$watch->asset = $s['pair']['asset'];

		$strategyName = key($s['strategy']);
		$gc->$strategyName = $s['strategy'][$strategyName];

		# set tradingAdvisor
		$gc->tradingAdvisor->method = $strategyName;

		# set time related stuff
		$timing = $s['timing'];
		$gc->tradingAdvisor->candleSize = $timing['candleSize'];
		$gc->tradingAdvisor->historySize = $timing['historySize'];

		return $c;

	} // config()








	/*
		PRIVATES
	*/


	# get
	private function get( $url, $file )
	{
		$cacheTime = $this->cache->cacheTime;
		$file = $this->cache->cache_dir . $file;
		$curl = curl_cache($url, $file, $cacheTime);
		return json_decode($curl, true);
	}

	# post
	private function post( $url, $file, $vars )
	{
		$cacheTime = $this->cache->cacheTime;
		$file = $this->cache->cache_dir . $file;
		return curl_post_cache( $url, $vars, $file, $cacheTime );
	}








} // class()
