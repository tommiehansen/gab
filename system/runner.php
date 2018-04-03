<?php
/*
	RUNNER
	Runs things from JSON-settings
	-
	Requires
	$_POST ...
*/
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'conf.php';
require_once $conf->dirs->system . 'functions.php';
require_once $conf->dirs->system . 'class.gab.php';

$gab = new \GAB\core($conf);

#prph('POST');
#prp($_POST);


/* 1 - What strategy? */
if( _P('strategy_name') ){
	$strat_name = _P('strategy_name');
}
else {
	die('No strategy was set to be used. Cannot run.');
}

#print_r($_POST);

/* 2 - get strategy default params */
$strat_name = _P('strategy_name');
$strat_post = json_decode(_P('strategy_params'));
$candle_size = _P('candle_size');
$history_size = _P('history_size');
$settings = json_decode(_P('dataset'));

@$strat = [ $strat_name => $gab->get_strategies()[$strat_name] ]; // returns array

if( !$strat[$strat_name] ) die('Could not find strategy or it does not have a valid TOML file');

# ..then set if params set
foreach($strat_post as $key => $val ){
	$strat[$strat_name][$key] = $val;
}

#prp($strat); exit;
#prp($settings); exit;
#prph("settings\n\n");
#prp($settings);

/* 3 - get overall params */
# NOTE: Gekko doesn't seem to accept date format ?!, needsfix
date_default_timezone_set('UTC');

// we're sending data in Y-m-d 00:00:00 format so need to convert
$jsFrom = date('Y-m-d\TH:i:s\Z', strtotime($settings->from));
$jsTo = date('Y-m-d\TH:i:s\Z', strtotime($settings->to));
$dbFrom = date('Y-m-d', strtotime($settings->from));
$dbTo = date('Y-m-d', strtotime($settings->to));

$settings = [
	'candle_size' => (int) $candle_size,
	'history_size' => (int) $history_size,
	'exchange' => $settings->exchange,
	'currency' => $settings->currency,
	'asset' => $settings->asset,
	'date_from' => $jsFrom, // format: 2017-11-30T22:08:00Z or plain JS date eg 7828749322
	'date_to' => $jsTo,
];

/* 4 - set all data in config array */
# NOTE: This is the main Gekko configuration object
$c = [

	'pair' => [
		'exchange' => $settings['exchange'],
		'currency' => $settings['currency'],
		'asset' => $settings['asset'],
	],

	'timing' => [
		'candleSize' => $settings['candle_size'], // minutes
		'historySize' => $settings['history_size'], // minutes
		'daterange' => [
			'from' => "$jsFrom",
			'to' => "$jsTo",
		],
	],

	'strategy' => $strat, // array ['STRAT_NAME']['VALUE'] = XXX

];


# set config (adds rest of configuration items to array)
$gconf = $gab->set_config($c);

#prp($settings);
#prp($c); exit;
#prp($gconf); exit;


/* 5 - create unique string for run */

# get all strat-params and create string unique string per run
$str = $settings['candle_size'];
$str .= $settings['history_size'];

$strat = json_decode(json_encode($strat), true); // force array

foreach( $strat[$strat_name] as $key => $value )
{
	if( is_array($value) ){
		foreach($value as $k => $v ){
			$str .= $v;
		}
	}
	else { $str .= $value; }
}

#prp($str); exit;
$run_id = $str;




/* DATABASE */

# create name for db [exchange_asset]
$fromTo = $dbFrom . '--' . $dbTo; // add dateRange (simple)
$file = $settings['exchange'] . '__' . $settings['asset'] . '__' . $settings['currency'] . '__' . $fromTo . '__' . $strat_name;
$file .= '.db';
#prp($file); exit;


# fields
$blobs_fields = (array) $conf->db_fields->blobs;
$results_fields = (array) $conf->db_fields->results;

# database setup
$db_file = $conf->dirs->results . $file;
$db = null;

# start transaction
#$db->beginTransaction();

# check if file already exists and don't repeat queries ...
if( !file_exists($db_file) )
{
	$dir = "sqlite:" . $db_file;
	$db	= new PDO($dir) or die("Error creating database file"); // creates the file

	# settings
	#$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("PRAGMA synchronous=OFF");
	$db->exec('PRAGMA journal_mode=MEMORY'); // MEMORY
	$db->exec('PRAGMA temp_store=MEMORY');
	$db->exec('PRAGMA auto_vacuum=OFF');

	# create runs table
	$sql = "
	CREATE TABLE IF NOT EXISTS runs (
		id TEXT PRIMARY KEY UNIQUE,
		success TEXT
	)";

	$db->query($sql);


	# create results table
	$sql = "
		CREATE TABLE IF NOT EXISTS results (
	";

	foreach( $results_fields as $key => $val ){ $sql .= "`$key` $val, "; }
	$sql = rtrim($sql, ', '); $sql .= ")";
	$db->query($sql);

	# create blobs table
	$sql = "
		CREATE TABLE IF NOT EXISTS blobs (
	";

	foreach( $blobs_fields as $key => $val ){ $sql .= "`$key` $val, "; }
	$sql = rtrim($sql, ', '); $sql .= ")";
	$db->query($sql);

	# set hasRan to false (since no runs...)
	$hasRan = false;

} // if !file_exists
else {
	$dir = "sqlite:" . $db_file;
	$db	= new PDO($dir) or die("Error creating database file");

	# settings
	#$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("PRAGMA synchronous=OFF");
	$db->exec('PRAGMA journal_mode=MEMORY'); // MEMORY
	$db->exec('PRAGMA temp_store=MEMORY');
	$db->exec('PRAGMA auto_vacuum=OFF');

	# check if id already exist
	$q = $db->query("SELECT id FROM runs WHERE id = '$run_id'");
	$runs = $q->fetchAll();
	empty( $runs ) ? $hasRan = false : $hasRan = true;
}


if( $hasRan )
{
	echo "<u class='notice'>Notice: Already ran these set of parameters so skipping...</u>";
	$db = null;
	exit; // just exit
}

/* RUN */
$timer_start = timer_start(); // start timer
$url = $conf->endpoints->backtest;
$curl = curl_post($url, json_encode($gconf));
$timer_end = timer_end($timer_start); // returns seconds

if( $curl->status !== 200 ){
	die('Runner.php ERROR: Get from curl_post() returned status: ' . $curl->status);
}

$get = json_decode($curl->data);
unset($get->candles);
#prp($get);

if( !$get ) die('Runner.php ERROR: Get from curl_post() did not return data, something is wrong');

# start transaction
$db->beginTransaction();

	try {

		/* 6 - Set run status at db (for later use) */
		$sql = "
			INSERT INTO runs (id, success) VALUES (?, ?)
		";
		$q = $db->prepare($sql);

		/* 7 - check if strategy beat market */
		$report = $get->report;
		$profitMarket = $report->market;
		$profitStrategy = $report->relativeProfit;

		# beat the market, add 'true' to 'success'
		if( $profitStrategy > $profitMarket ){
			$q->execute([$run_id, 'true']);
		}
		# did not beat market, add 'false' to 'success'
		else {
			$q->execute([$run_id, 'false']);
		}

		# ...if write stuff
		if( $profitStrategy > $profitMarket && $profitStrategy !== 0 )
		{
			$strat; $report;

			# results
			$sql = "INSERT INTO results (";
			foreach( $results_fields as $key => $val ){ $sql .= "`$key`,"; }
			$sql = rtrim($sql, ',');
			$sql .= ") VALUES (";

			foreach( $results_fields as $val ){ $sql .= "?,"; }
			$sql = rtrim($sql, ','); $sql .= ")";

			$results = $db->prepare($sql);

			$r = $report;
			$currency = $c['pair']['currency'];
			$relativeProfit = round($r->relativeProfit);
			$marketProfit = round($r->market);
			$sharpe = number_format($r->sharpe, 2);
			$numTrades = $r->trades; // note, 1x roundtrip = 1x buy + 1x sell
			$numRoundtrips = count($get->roundtrips);
			$alpha = number_format($r->alpha);
			$exchange = $settings['exchange'];

			/* loop and add trading data from roundtrips */
			$roundtrips = $get->roundtrips;
			$trades = [
				'win' => 0,
				'lose' => 0,
				'win_percent' => 0,
				'win_avg' => [],
				'lose_avg' => [],
				'best' => 0,
				'worst' => 0,
				'per_day' => 0,
			];

			// get lose/win trades
			foreach( $roundtrips as $r )
			{
				$profit = $r->profit;
				if( $profit < 0 ){
					$trades['lose'] = $trades['lose'] + 2; // NOTE: 1x roundtrip = 1 buy + 1 sell
					$trades['lose_avg'][] = $r->profit; // add to array for later calc
				}
				else {
					$trades['win'] = $trades['win'] + 2; // NOTE: 1x roundtrip = 1 buy + 1 sell
					$trades['win_avg'][] = $r->profit;
				}
			}

			// DEBUG
			if( !$trades['worst'] = @min($trades['lose_avg']) ){
				#prp($c); // output conf
			}

			// check there were wins
			if( !empty($trades['win_avg']) )
			{
				$numWinningTrades = count($trades['win_avg']);

				$trades['best'] = number_format(max($trades['win_avg']), 2); // re-use win_avg array

				// calc win-percent (how many of the trades were wins?)
				$win_percent = ( $numWinningTrades / $numRoundtrips ) * 100;
				$trades['win_percent'] = number_format($win_percent, 2);

				// calc averages winning trades
				$count = count($trades['win_avg']);
				$total = 0;
				foreach( $trades['win_avg'] as $num ){ $total += $num; }
				$trades['win_avg'] = number_format($total/$count, 2);
			}
			else {
				$trades['best'] = 0;
				$trades['win_avg'] = 0;
				$trades['best'] = 0;
				$trades['win_percent'] = 0;
			}

			// check if there were losing trades
			if( !empty($trades['lose_avg']) )
			{
				$trades['worst'] = number_format(min($trades['lose_avg']), 2);

				// calc averages losing trades
				$count = count($trades['lose_avg']);
				$total = 0;
				foreach( $trades['lose_avg'] as $num ){ $total += $num; }
				$trades['lose_avg'] = number_format($total/$count, 2);
			}
			else {
				$trades['worst'] = 0;
				$trades['lose_avg'] = 0;
			}

			// calc average trades per day
			if( $numTrades > 0 )
			{
				$dateDiff = date_between($report->startTime, $report->endTime);
				$days = $dateDiff->days;
				$trades['per_day'] = number_format($report->trades/$days, 2);
			}
			else {
				$trades['per_day'] = 0;
		 	}


			/* set array with all data to be written */
			$t = (object) $trades;
			$arr = [
				$run_id,
				$settings['candle_size'],
				"$relativeProfit",
				"$marketProfit",
				$sharpe,
				$alpha,
				$numTrades,
				/* add all calculated trading values */
				$t->win,
				$t->lose,
				$t->win_percent,
				$t->win_avg,
				$t->lose_avg,
				$t->best,
				$t->worst,
				$t->per_day,
				gzencode(json_encode($strat, true)),
			];

			$results->execute($arr);

			# BLOBS
			$sql = "INSERT INTO blobs (";
			foreach( $blobs_fields as $key => $val ){ $sql .= "`$key`,"; }
			$sql = rtrim($sql, ',');
			$sql .= ") VALUES (";

			foreach( $blobs_fields as $val ){ $sql .= "?,"; }
			$sql = rtrim($sql, ','); $sql .= ")";

			$blobs = $db->prepare($sql);

			// add
			$report_blob = gzencode(json_encode($report));
			$roundtrips_blob = gzencode(json_encode($get->roundtrips));

			$blobs_arr = [
				$run_id,
				$report_blob,
				$roundtrips_blob,
			];

			$blobs->execute($blobs_arr);

		} // end profitStrategy > market

		# END TRANSACTION
		$db->commit();

	}
	catch(Exception $e){
		echo $e->getMessage();
		$db->rollBack();
	}


/* OUTPUT */
if( $profitStrategy > $profitMarket && $profitStrategy !== 0 )
{
	$calc = number_format($profitStrategy - $profitMarket) . '%';
	$percentProfit = number_format($report->relativeProfit) . '%';
	$str = "<u class='success'>Success!</u> Performed $calc better";
}
else
{
	$calc = number_format($profitMarket - $profitStrategy) . '%';
	$str = "<u class='bad'>Bad!</u> Performed $calc worse then market";
}

$str .= " <u class='notice'>[{$timer_end} @ {$candle_size}min candle]</u>";

if( $conf->multiserver )
{
	$server = $conf->endpoints->backtest;
	$server = explode(':', $server)[1]; // hide ports etc
	$server = str_replace('//','', $server);
	if( !contains('localhost', $server ) ){
		$server = substr($server, 0, 3) . '***'; // hide servers..
	}
	$str .= " <u class='notice'>@ $server";
}

echo $str;


$db = null;
exit;
