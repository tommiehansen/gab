<?php
namespace GAB;

class core {

	function __construct( $conf )
	{
		# vars
		$this->conf = $conf;
		$this->endpoints = $conf->endpoints;
		$this->cache = $conf->cache;
		$this->cache_dir = $conf->dirs->cache;

		# requires
		require_once $conf->dirs->system . 'functions.php';
		require_once $conf->dirs->system . 'class.toml.php';

		$this->init();


	} // construct()


	# do stuff on init
	private function init(){

		/* auto create folders if not exist */
		$dir = $this->conf->dirs->cache;
		if(!file_exists($dir))
		{
			#prp('created dir: ' . $cache->cache_dir);
			mkdir($dir, 0755, true);
			file_put_contents( $dir . '.gitignore', ''); # create emtpy .gitignore
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
	 *  Get strategy
	 *
	 *  @param [string] $name Name for strategy
	 *  @return Returns array with strategy name and TOML-string or fails if no match
	 *
	 */
	public function get_strategy( $name )
	{
		$strats = $this->get( $this->endpoints->strategies, 'strategy.cache' );

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

	/**
	 *  Strategy average
	 *
	 *  @param [array] $arr Array of strategies
	 *  @return Returns Array with strategy average
	 *
	 */
	public function strategy_average( $arr, $rounding = true )
	{

		# create array + get total
		$avg = [];
		$total = count($arr);

		# first get totals
		foreach( $arr as $index => $arr )
		{
			foreach( $arr as $key => $val )
			{
				if( is_array($val) ){ // sub array -- TOML has a max of 1x subarray
					foreach( $val as $k => $v )
					{
						@$avg[$key][$k] += $v; // TODO: fix undefined index...
					}
				}
				else {
					@$avg[$key] += $val;
				}
			}

		} // foreach()

		# ..then calculate average
		foreach( $avg as $key => $val )
		{
			if( is_array($val) ){
				foreach( $val as $k => $v ){
					$a = $v / $total;
					 if( $rounding ) $a = (float) $a;
					 $avg[$key][$k] = (float) $a;
				}
			}
			else {
				$a = $val / $total;
				if( $rounding ) $a = (float) $a;
				$avg[$key] = (float) $a;
			}
		} // foreach()

		return $avg;

	} // strategy_average()


	/* other */

	# parse TOML, returns array
	public function parse_toml( $str )
	{
		return \Toml\Parser::fromString($str);
	}



	# return TOML from array
	public function create_toml( $arr )
	{
		$str = '';
		$nl = "\n";
		$i = 0;

		foreach( $arr as $key => $val ){
			if( is_array($val) )
			{
				if( $i++ > 0 ) $str .= $nl; // add space before if 2nd, 3rd [title]
				$str .= "[$key]" . $nl;
				foreach( $val as $k => $v ){
					$str .= "$k = $v" . $nl;
				}
			}
			else {
				$str .= "$key = $val" . $nl;
			}
		}

		$str = rtrim($str, "\n\n"); // clean end double space

		return $str;

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

//			'gekkoConfig' => [

				# should change
				'watch' => [
					'exchange' => 'binance', // e.g. 'binance'
					'currency' => 'USDT', // e.g. 'USDT'
					'asset' => 'ETH', // e.g. 'ETH'
				],

				# strat settings (from get_strategies())
				'tradingAdvisor' => [
					'enabled' => true,
					'method' => 'TEMA', # needs to change to strategy name!
					'candleSize' => 60, // always defined in minutes
					'historySize' => 10
				],

				// not required -- default is to use all data
				'backtest' => [
					'daterange' => [
						'from' => null,
						'to' => null
					],
				],

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
					'slippage' => 0.1,
					'simulationBalance' => [
						'asset' => 1,
						'currency' => 100
					],
					'reportRoundtrips' => 1,
					'enabled' => 1,
				],

//			], // gekkoConfig

            'backtestResultExporter' => [
                'enabled' => 1,
                'writeToDisk' => 0,
                'data' => [
                    'stratUpdates' => 0,
                    'roundtrips' => 1,
                    'stratCandles' => 0,
                    'stratCandleProps' => ['open'],
                    'trades' =>1
                ]
            ]
		];


		# to object
		$c = json_decode(json_encode($c));
		$s = $settings;

		# set values
		$watch = $c->watch;

		$watch->exchange = $s['pair']['exchange'];
		$watch->currency = $s['pair']['currency'];
		$watch->asset = $s['pair']['asset'];

//		reset($s['strategy']);
		$strategyName = array_keys($s['strategy'])[0];

        $c->{$strategyName} = $s['strategy'][$strategyName];

		# set tradingAdvisor
        $c->tradingAdvisor->method = $strategyName;

		# set time related stuff
		$timing = $s['timing'];
        $c->tradingAdvisor->candleSize = $timing['candleSize'];
        $c->tradingAdvisor->historySize = $timing['historySize'];

		$dates = $timing['daterange'];
        $c->backtest->daterange->from = $dates['from'];
        $c->backtest->daterange->to = $dates['to'];

		# check / set paperTrader
		if( isset( $this->conf->paperTrader ) )
		{
			$pt = $this->conf->paperTrader;
			$gp = $c->paperTrader;
			
			$gp->feeMaker = $pt->feeMaker;
			$gp->feeTaker = $pt->feeTaker;
			$gp->feeUsing = $pt->feeUsing;
			$gp->slippage = $pt->slippage;
			$gp->simulationBalance->asset = $pt->asset;
			$gp->simulationBalance->currency = $pt->currency;
		}
		return $c;

	} // config()








	/*
		PRIVATES
	*/


	# get
	private function get( $url, $file )
	{
		$cacheTime = $this->cache->cacheTime;
		$file = $this->cache_dir . $file;
		$curl = curl_cache($url, $file, $cacheTime);
		return json_decode($curl, true);
	}

	# post
	private function post( $url, $file, $vars )
	{
		$cacheTime = $this->cache->cacheTime;
		$file = $this->cache_dir . $file;
		return curl_post_cache( $url, $vars, $file, $cacheTime );
	}








} // class()
