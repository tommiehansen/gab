<?php
	/*
		GAB
		view runs
	*/

	require_once 'system/conf.php';
	require_once $conf->dirs->system . 'functions.php';
	require_once $conf->dirs->system . 'class.gab.php';

	$page = 'view';
	$page_title = 'View runs';
	require $conf->dirs->views . 'header.php';

	# new instance
	$gab = new \GAB\core($conf);
?>
<?php


    # 'global' params
    $setup = (object) [];
    $setup->limit = _G('limit') ? _G('limit') : 10;
    $setup->order = _G('order') ? _G('order') : 'strategy_profit';

?>
        <form action="view.php">

            <section id="results">
                <h3>Select collection of runs</h3>
                <input type="text" id="filter" placeholder="Type to filter datasets">

	            <?php
	                _G('db') ? $db = _G('db') : $db = '';

	                $files = listfiles( $conf->dirs->results );
	                if( !is_array($files) ){ die($files); }

	                # pre-process files (add dates etc)
	                $list = [];
	                foreach( $files as $key => $dbs )
	                {
	                    # must contain .db extension
	                    if( !contains('.db', $dbs) ) continue;
	                    $time = filemtime($conf->dirs->results . $dbs);
	                    $size = filesize($conf->dirs->results . $dbs);
	                    $list[$key]['name'] = $dbs;
	                    $list[$key]['last_run'] = $time;
	                    $list[$key]['filesize'] = sprintf("%4.2f MB", $size/1048576);
	                }

	                # fix keys
	                $list = array_values($list);

	                # sort by date desc
	                usort($list, function($a, $b) { return (float) $b['last_run'] - (float) $a['last_run']; });

	                # add date
	                foreach($list as $k => $v ){
	                    $list[$k]['date'] = date('Y-m-d H:i', $v['last_run']);
	                }

	                #prp($list);

	                $html = '<table class="colored striped sortable"><thead>';

	                foreach( $list as $key => $dbs )
	                {
	                    # must contain .db extension
	                    if( !contains('.db', $dbs['name']) ) continue;

	                    $arr = explode('__', $dbs['name']);

	                    $exchange = ucfirst($arr[0]);
	                    $asset = strtoupper($arr[1]);
	                    $currency = strtoupper($arr[2]);

	                    $fromTo = explode('--', $arr[3]);
	                    $from = $fromTo[0];
	                    $to = $fromTo[1];

	                    $strategy = $arr[4];
	                    $strategy = str_replace(['_','-','.db'], [' ',' ',''], $strategy);
	                    $strategy = strtoupper($strategy);

	                    $filesize = $dbs['filesize'];

	                    // table header
	                    if( $key == 0 ){
	                        $html .= "
	                            <tr>
	                                <th>Exchange</th>
	                                <th>A</th>
	                                <th>C</th>
	                                <th>From</th>
	                                <th>To</th>
	                                <th>Strategy</th>
	                                <th>Size</th>
	                                <th>Last change</th>
	                            </tr>
	                        ";
	                        $html .= "</thead><tbody>";
	                    }

	                    $name = str_replace('.db', '', $dbs['name']);
	                    $dbsFile = $dbs['name'];
	                    $db_name = $dbs['name'];
	                    $db_name == $db ? $c = 'checked' : $c = '';
	                    if( !$db && $key == 0 ) $c = 'checked';
	                    $date = $dbs['date'];
	                    $input = "<input name='db' value='$db_name' type='radio' $c>";
	                    $cleanClass = $filesize > 70 ? 'red' : '';
	                    $html .= "
	                        <tr class='$c' rel='$dbsFile'>
	                            <td>$input $exchange</td>
	                            <td>$asset</td>
	                            <td>$currency</td>
	                            <td>$from</td>
	                            <td>$to</td>
	                            <td>$strategy</td>
	                            <td>
	                                <i>$filesize</i>
	                                <div class='right'>
	                                    <a class='button button-outline small tip clean $cleanClass' data-tip='Cleans the results table and keeps 500 most profitable runs'>CLEAN</a>
	                                    <a class='button button-outline small tip red remove' data-tip='Remove this result set'>R</a>
	                                </div>
	                            </td>
	                            <td>$date</td>
	                        </tr>
	                    ";
	                }


	                $html .= '</tbody></table>';
	                echo $html;
	            ?>

		        <row>
		            <column>
		                <label for="limit">Use Top X runs for averages and list</label>
		                <input type="number" id="limit" pattern="[0-9]*" max="10000" min="2" name="limit" placeholder="Number of runs to show, default: 10" value="<?= $setup->limit ?>">
		            </column>
		            <column>
		                <label for="order">Order results by</label>
		                <?php
		                    $orders = 'strategy_profit,sharpe,trades_win_avg,trades_win_percent,alpha';
		                    $orders = explode(',', $orders);
		                ?>
		                <select name="order">
		                    <?php
		                        foreach($orders as $key => $order){
		                            $checked = '';
		                            if( $setup->order === $order ) $checked = ' selected';
		                            $name = str_replace('_',' ', $order);
		                            $name = str_replace('avg','average', $name);
		                            $name = ucfirst($name);
		                            echo "<option value='$order'{$checked}>$name</option>";
		                        }
		                    ?>
		                </select>
		            </column>
		        </row>
		        <row>
		        <column>
		            <input type="submit" value="Get data">
		        </column>
		        </row>

			</section>
		</form>

<?php


    if(_G('db'))
    {
		// get db
		$db = _G('db');
		$db_name = $db;

        $name = str_replace('.db','', $db);
        $q = explode('__', $name);

        $db = new PDO('sqlite:' .  $conf->dirs->results . $db) or die('Error: Could not open database ' . $db);

        $query = "
            SELECT * FROM results
            ORDER BY $setup->order DESC
            LIMIT $setup->limit
        ";

        $totalRuns = "
            SELECT count(id) as total FROM runs
        ";

        $db->beginTransaction();

            $res = $db->query($query);
            $res = $res->fetchAll(PDO::FETCH_ASSOC);

            # total runs for entire exchange/pair
            $total = $db->query($totalRuns);
            $total_runs = $total->fetchAll()[0]['total'];

        $db->commit();

		$hasResults = true;
		if( !isset($res[0]) )
		{
			$hasResults = false;
			echo "
				<section>
					<hr>
					<h2>No results could be found</h2>
				</section>
			";
		}

?>

<?php if( $hasResults ) { ?>

		<?php

        # single values
        $first = (object) $res[0];
        $market_profit = number_format($first->market_profit);
        $strategy_profit = number_format($first->strategy_profit);

        $str = "
            <section id='cards'>
                <hr>
                <h2>You and the market</h2>
                <p>Showing the best from order $setup->order</p>
                <row>
                    <column>
                        <div class='card has-border has-large-border left positive'>
                            <div class='inner'>
                                <h4>Profit</h4>
                                <i class='subtitle'>Best strategy profit for '$setup->order'</i>
                                <h1>{$strategy_profit}%</h1>
                                <hr>
                                <p>
                                    Sharpe: $first->sharpe / Trades: $first->trades / Win: $first->trades_win_percent% of all trades<br>
                                    Win avg: $first->trades_win_avg% / Lose avg: $first->trades_lose_avg%<br>
                                    Best trade: $first->trades_best% / Worst trade: $first->trades_worst%<br>
                                    Candle size: {$first->candle_size} min / Trades/day: $first->trades_per_day
                                </p>
                                <a class='button' href='views/view.more.php?id={$first->id}&db=$db_name' target='_blank'>Open run</a>
                            </div>
                        </div>
                    </column>
                    <column>
                        <div class='card has-border has-large-border left'>
                            <div class='inner'>
                                <h4>Market</h4>
                                <i class='subtitle'>Market performance</i>
                                <h1>{$market_profit}%</h1>
                                <hr>
                                <h4>Total</h4>
                                <i class='subtitle'>Total runs for database/strategy</i>
                                <h1>$total_runs</h1>
                                <button class='button-outline' style='visibility:hidden'>xxx</button>
                            </div>
                        </div>
                    </column>
                </row>
            </section>
        ";

        echo $str;


        # pre-process
        $cols = [
			'id',
            'candle_size',
            'strategy_profit',
            'sharpe',
            'trades',
            'trades_win_percent',
            'trades_win_avg',
            'trades_lose_avg',
        ];

        $tbl = [];
        foreach($res as $key => $arr){
            foreach($arr as $k => $v ){
                if( in_array($k, $cols) ){
                    $k == 'strategy_profit' ? $k = 'profit' : '';
                    if($k == 'candle_size') { $k = 'candle'; $v = $v . ' min'; }
                    if($k == 'trades_win_percent') { $k = 'win%'; $v = $v . '%'; }
                    if($k == 'trades_win_avg' ) $v = $v . '%';
                    if($k == 'trades_lose_avg' ) $v = $v . '%';
                    if($k == 'trades_best' || $k == 'trades_worst' ) $v = $v . '%';
                    if($k == 'profit' ) $v = number_format($v) . '%';

                    $k = str_replace('trades_','', $k);
                    $k = str_replace('_','.', $k);
                    $tbl[$key][$k] = $v;
                }
                else {
                    continue;
                }
            }

        }

        // add columns
        foreach( $tbl as $k => $v )
        {
            $id = $tbl[$k]['id'];
            $dbname = _G('db');
            $str = "
                <a class='button button-outline' href='views/view.more.php?id=$id&db=$dbname' target='_blank'>Open</a>
            ";
            $tbl[$k]['&nbsp;'] = $str;
			unset($tbl[$k]['id']); // remove id
        }

        $tbl = sqlTable($tbl, 'sortable colored striped', false, 'tbl');

        $html = "
            <section>
                <hr>
                <h2>Top runs</h2>
                <p>Top $setup->limit runs for order '$setup->order'</p>
                $tbl
            </section>
        ";

        echo $html;

    } // if _G('db')

?>

<?php
	} // end $hasResults
?>

<?php
    if( _G('db') && $hasResults ):
?>

<?php

    # exclude columns to calculate on
    $exclude = "id,market_profit,alpha,strat";
    $exclude = explode(",", $exclude);

    $new = [];
    foreach( $res as $key => $arr ) // re-use $res array from SQL-query
	{
        foreach($arr as $k => $v ){
            if( !in_array($k, $exclude) ) $new[$key][$k] = $v;
        }
    }

    $avg = [];
    foreach( $new as $key => $arr )
	{
        foreach( $arr as $k => $v ){
            @$avg[$k] += $v;
        }
    }

    $total = count($new);
    $q = [];
    foreach($avg as $k => $v ){
        $avg = number_format($v/$total);
        $q[$k] = $avg;
    }

    $avg = $q;
?>

<section>
    <hr>
    <h2>Average strategy</h2>
    <p>Average meta for top <?= $setup->limit ?> sorted by "<?= $setup->order ?>"</p>

    <table class="striped colored mb-double">
    <thead>
        <tr>
            <th>Name</th>
            <th>Average</th>
        </tr>
    </thead>
    <tbody>
    <?php
		$percent_values = ['trades_win_percent','trades_win_avg','trades_lose_avg','trades_best','trades_worst','strategy_profit'];
		$str = '';

        foreach( $avg as $k => $v )
        {
            $old = $k;
            if( $k == 'candle_size' ){ $v = $v . ' min'; }

			// nicer large numbers
			if( is_numeric($v) && $v > 999 ) $v = (float) number_format($v, 3);

			// add %
			if( in_array($k, $percent_values) ) $v = $v . '%';

			// fix trades_
			if( contains('trades_', $k) ){ $k = str_replace('trades_', 'Trades &rarr; ', $k); }

			// fix format
			$k = str_replace(['_','avg','percent'], [' ', 'average','%'], $k);
			$k = ucwords($k);

            $str .= "
                <tr>
                    <td>$k</td>
                    <td>$v</td>
                </tr>
            ";
        }
        echo $str;


		/* generate average strategy settings */

		# get selected strategy
		$arr = explode('__', $db_name);
		$strat_name = $arr[4];
		$strat_name = str_replace('.db', '', $strat_name);

		# add strategy params to array
		$strat_values = [];
		foreach($res as $k => $v ){
			$strat = json_decode(gzdecode($v['strat']), true);
			$strat = $strat[$strat_name];
			$strat_values[] = $strat;
		}

		# get average from all the strat_params values
		$avg = $gab->strategy_average($strat_values);

		# create a new TOML-file out of it
		$avg_toml = $gab->create_toml($avg); // returns string

    ?>
	</tbody>
    </table>

    <h4>Generated settings</h4>
    <p>For the strategy and the top <?php echo $setup->limit ?> runs</p>
    <form id="generate">

	    <?php
	        echo "<textarea class='strat_avg mb monospace' id='strat_avg' onfocus='this.select()'>$avg_toml</textarea>";
	    ?>
		<h6>Strategy test generator</h6>
		<p>Generate +/- settings from average strategy parameters above</p>

		<div id="gen">
			<row>
				<column>
					<label><b>1000's</b>&nbsp; +/&minus; and stepping</label>
					<input type="number" value="200" max="1000">
					<input type="number" value="100" max="500">
				</column>
				<column>
					<label><b>100's</b>&nbsp; +/&minus; and stepping</label>
					<input type="number" value="100" max="">
					<input type="number" value="50" max="">
				</column>
				<column>
					<label><b>10's</b>&nbsp; +/&minus; and stepping</label>
					<input type="number" value="10" max="">
					<input type="number" value="5" max="">
				</column>
				<column>
					<label><b>1's</b>&nbsp; +/&minus; and stepping</label>
					<input type="number" value="5" max="">
					<input type="number" value="5" max="">
				</column>
			</row>
		</div>

		<input type="submit" class="button mt mb" value="Generate">
		<div id="generated"></div>
    </form>

</section>

<?php
    // end if _G('db') set
    endif;
?>



<script>
	/* TEMP: CLEAN + MOVE ALL THIS */
	window.onload = function(){
		let gen = $('#generate');

		gen.on('submit', function(e){
			e.preventDefault();

			let inputs = $('#gen').find('input');
			let arr = [];

			inputs.each(function(i){
				arr[i] = $(this).val();
			})

			let str = plusMinusStrategy( $('#strat_avg')[0].value, arr );

			// calc possibilities
			let possible = calcPossibilities( str );

			// html
			let html = "<h6>Possibilities</h6><p>" + possible + " different possibilities</p>";
			html += "<h6>Generated dynamic settings</h6><p>You might want to check it first and make some adjustments..</p><textarea id='gen_dyn' class='monospace'>"+ str +"</textarea>";
			$('#generated').html(html);
			gab.autoSizeTextarea($('#gen_dyn')[0]);

		})
	}


	function plusMinusStrategy( str, arr )
	{
		str = str || false;
		if( !str ) { alert('Error for plusMinusStrategy() function'); return false; }

		let pm1000 = +arr[0],
			s1000 = +arr[1],
			pm100 = +arr[2],
			s100 = +arr[3],
			pm10 = +arr[4],
			s10 = +arr[5],
			pm1 = +arr[6],
			s1 = +arr[7];

		let originalLines = str.split('\n');
		let lines = originalLines;

		// split to values
		let i = 0,
			len = lines.length,
			cur, val, key, split, val_len, min, max, step, isMinus;

		let calc_arr = [];
		for(i; i < len; i++){
			cur = lines[i];

			if( cur.indexOf('=') > -1 )
			{
				split = cur.split('= '); // get second (the value)
				key = split[0]
				val = Math.floor(split[1]);
				val_len = val.toString().length;
				isMinus = false;

				//console.log(val);

				if( val < 0 ) isMinus = true;
				if( isMinus ) val_len = val.toString().replace('-','').length;

				switch( val_len ){
					case 4:
						val = Math.floor(val / 500) * 500; // round to nearest 500
						min = val - pm1000;
						max = val + pm1000;
						step = s1000;
						break;
					case 3:
						val = Math.floor(val/50) * 50; // round.. ~50
						min = val - pm100;
						max = val + pm100;
						step = s100;
						break;
					case 2:
						val = Math.floor(val/5) * 5; // round.. ~5
						min = val-pm10;
						max = val+pm10;
						step = s10;
						if( min == 0 ) min = 5;
						if( isMinus && max == 0 ){
							max = min + 20;
							if( max == 0 ) max = -5;
						}
						break;
					case 1:
						val = Math.floor(val/5) * 5; // round.. 5
						if( val == 0 ) val = 1;
						min = val - pm1;
						max = val + pm1;
						step = s1;
						if( min == 0 ) min = 1;
				}

				// concatinate
				val = min + ':' + max + ',' + step;

				// set at line and add key back
				lines[i] = key + "= " + val;

			} // if cur.indexOf()

		} // for()

		// create string
		i = 0;
		let out = '';
		for( i; i < len; i++ )
		{
			out += lines[i] + "\n";
		}

		return out;

	} // plusMinusStrategy()

	function range(start, end, step)
	{

		var range = [];
		var typeofStart = typeof start;
		var typeofEnd = typeof end;

		if (step === 0) {
			throw TypeError("Step cannot be zero.");
		}

		if (typeofStart == "undefined" || typeofEnd == "undefined") {
			throw TypeError("Must pass start and end arguments.");
		}
		else if (typeofStart != typeofEnd) {
			throw TypeError("Start and end arguments must be of same type.");
		}

		typeof step == "undefined" && (step = 1);

		if (end < start) {
		step = -step;
		}

		if (typeofStart == "number") {

		while (step > 0 ? end >= start : end <= start) {
		range.push(start);
		start += step;
		}

		} else if (typeofStart == "string") {

		if (start.length != 1 || end.length != 1) {
			throw TypeError("Only strings with one character are supported.");
		}

		start = start.charCodeAt(0);
		end = end.charCodeAt(0);

		while (step > 0 ? end >= start : end <= start) {
			range.push(String.fromCharCode(start));
			start += step;
		}

		}
		else {
			throw TypeError("Only string and number types are supported");
		}

		return range;

	}

	// calcPossibilities, requires TOML file with dynamic parameters
	function calcPossibilities( toml )
	{
		let lines = toml.split("\n"),
			i = 0, len = lines.length,
			cur, split, val,
			countArr = [],
			possibilities = 1;

		for( i; i < len; i++ )
		{
			cur = lines[i];
			if( cur.indexOf('=') > -1 )
			{
				split = cur.split('= ');
				val = split[1];
				val = val.replace(',',':');
				val = val.split(':');
				countArr[i] = range(+val[0],+val[1],+val[2]).length;
			} // cur.indexOf()
		}
		i = 0, len = countArr.length;
		for( i; i < len; i++ ){
			cur = countArr[i];
			if( !isNaN(cur) ){
				possibilities = possibilities * cur;
			}
		}

		possibilities = possibilities.toLocaleString('en-GB');
		return possibilities;
	}
</script>

<?php
	require $conf->dirs->views . 'footer.php';
?>
