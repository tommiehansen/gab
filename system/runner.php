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


/* 3 - get overall params */
# NOTE: Gekko doesn't seem to accept date format, needsfix
date_default_timezone_set('UTC');
$jsFrom = date('Y-m-d\TH:i:s\Z', $settings->from);
$jsTo = date('Y-m-d\TH:i:s\Z', $settings->to);
$dbFrom = date('Y-m-d', $settings->from);
$dbTo = date('Y-m-d', $settings->to);
#$jsFrom = $settings->from;
#$jsTo = $settings->to;

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
			'from' => $settings['date_from'],
			'to' => $settings['date_to'],
		],
	],

	'strategy' => $strat, // array ['STRAT_NAME']['VALUE'] = XXX

];


# set config (adds rest of configuration items to array)
$gconf = $gab->set_config($c);

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
$dir = "sqlite:" . $conf->dirs->results . $file;
$db	= new PDO($dir) or die("Error creating database file");

# settings
$db->exec("PRAGMA synchronous=NORMAL");
#$db->exec('PRAGMA journal_mode=WAL'); // use Write-Ahead-Logging
#$db->exec('PRAGMA temp_store=FILE');
$db->exec('PRAGMA journal_mode=MEMORY'); // use MEMORY
$db->exec('PRAGMA temp_store=MEMORY');
$db->exec('PRAGMA auto_vacuum=OFF');

# start transaction
$db->beginTransaction();


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




/* check if run already exist */

# check if id already exist
$q = $db->query("SELECT id FROM runs WHERE id = '$run_id'");
$runs = $q->fetchAll();

# commit above
$db->commit();

# start again
$db->beginTransaction();

empty( $runs ) ? $hasRan = false : $hasRan = true;

if( $hasRan )
{
	echo "<u class='notice'>Notice: Already ran these set of parameters so skipping...</u>";
	$db->commit(); // end transaction
	exit; // just exit
}

/* RUN */
$url = $conf->endpoints->backtest;
$curl = curl_post($url, json_encode($gconf));

if( $curl->status !== 200 ){ die('Runner.php ERROR: Get from curl_post() returned status: ' . $curl->status); }

$get = json_decode($curl->data);
unset($get->candles);
#prp($get);

if( !$get ) die('Runner.php ERROR: Get from curl_post() did not return data, something is wrong');

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

	# prepare
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
	$numTrades = number_format($r->trades);
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
			$trades['lose']++;
			$trades['lose_avg'][] = $r->profit; // add to array for later calc
		}
		else {
			$trades['win']++;
			$trades['win_avg'][] = $r->profit;
		}
	}

	// best and worst trade
	$trades['best'] = number_format(max($trades['win_avg']), 2); // re-use win_avg array
	$trades['worst'] = number_format(min($trades['lose_avg']), 2);

	// calc win-percent (how many of the trades were wins?)
	$win_percent = ( count($trades['win_avg']) / $report->trades ) * 100;
	$trades['win_percent'] = number_format($win_percent, 2);

	// calc averages for win and losing trades
	$count = count($trades['win_avg']);
	$total = 0;
	foreach( $trades['win_avg'] as $num ){ $total += $num; }
	$trades['win_avg'] = number_format($total/$count, 2);

	$count = count($trades['lose_avg']);
	$total = 0;
	foreach( $trades['lose_avg'] as $num ){ $total += $num; }
	$trades['lose_avg'] = number_format($total/$count, 2);

	// calc average trades per day
	$dateDiff = date_between($report->startTime, $report->endTime);
	$days = $dateDiff->days;
	$trades['per_day'] = number_format($report->trades/$days, 2);


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

}

# END TRANSACTION
$db->commit();


/* OUTPUT */
if( $profitStrategy > $profitMarket && $profitStrategy !== 0 )
{
	$calc = number_format($profitStrategy - $profitMarket) . '%';
	$percentProfit = number_format($report->relativeProfit) . '%';
	echo "<u class='success'>Success!</u> Performed $calc better";
}
else
{
	$calc = number_format($profitMarket - $profitStrategy) . '%';
	echo "<u class='bad'>Bad!</u> Performed $calc worse then market";
}
