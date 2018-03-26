<?php
	/*
		GAB
		index / run strategy
	*/

	require_once 'system/conf.php';
	require_once $conf->dirs->system . 'functions.php';
	require_once $conf->dirs->system . 'class.gab.php';

	$page = 'run';
	$page_title = 'Run strategy';
	require $conf->dirs->views . 'header.php';

	# new instance
	$gab = new \GAB\core($conf);

	# get exchanges and pairs
	$datasets = $gab->get_datasets();
?>

<form method='post' action='<?php echo $conf->urls->system . 'post.php'; ?>' id='gab_selectForm'>

	<!--
		DATASET SELECTION
	-->

		<?php

			# create temp arr
			$new = [];

			/* pre-process: fix multi-period exchange/pair */
			foreach( $datasets as $key => $set )
			{
				# so this stupid exchange-pair has multiple ranges, need to add to new array
				$ranges = $set['ranges'];
				if( count($ranges) > 1 )
				{
					foreach( $ranges as $rk => $r )
					{
						$setCopy = $datasets[$key]; // copy original
						unset($setCopy['ranges']); // rm original ranges
						$setCopy['ranges'][] = $r;
						$new[] = $setCopy;
					}

				} // if

				// just copy
				else {
					$new[] = $datasets[$key];
				}

			}

			/* pre-process: sort datasets */
			foreach( $new as $key => $set )
			{

				# get dates 0 (can be multiple)
				$from = $set['ranges'][0]['from'];
				$to = $set['ranges'][0]['to'];

				# get no.of days
				$numDays = date_between( date('Y-m-d', $from), date('Y-m-d', $to));
				$numDays = $numDays->days;

				$new[$key] = $set; // copy-paste
				$new[$key]['numDays'] = $numDays;

			} // foreach


			# set new as datasets and rm $new (restore)
			$datasets = $new;
			$new = null;

			# sort desc with usort
			usort($datasets, function($a, $b) { return $b['numDays'] - $a['numDays']; });


			/* loop - output table */
			$str = '<table class="colored striped"><thead>';
			$numDatasets = count($datasets);
			foreach( $datasets as $key => $set )
			{
				# keys to vars [ exchange, currency, asset, (array) ranges ]
				foreach( $set as $k => $v ){  $$k = $v; }

				# create table headers
				if( $key === 0 )
				{
					$str .= "<tr>";
					foreach( $set as $k => $v ){
						if( $k === 'numDays' ) continue;
						$str .= "<th>" . $k . "</th>";
					}
					$str .= "<th>duration</th>";
					$str .= "</tr></thead><tbody>";
				}

				# TEMP / MUSTFIX: brutefoce using first range
				$ranges = $ranges[0];


				$from = $ranges['from'];
				$to = $ranges['to'];

				$dateFrom = date('Y-m-d', $from);
				$dateTo = date('Y-m-d', $to);

				$diff = date_between( $dateFrom, $dateTo);
				$niceRange = '';

				# get diff (ugly)
				$y = $diff->y;
				$m = $diff->m;
				$w = floor($diff->d/7);

				if( $y ) $niceRange .= $y == 1 ? $y . ' year, ' : $y . ' years, ';
				if( $m ) $niceRange .= $m == 1 ? $m . ' month, ' : $m . ' months, ';
				if( !$y && $w ){
					$niceRange .= $w == 1 ? $w . ' week, ' : $w . ' weeks, ';
				}

				$niceRange = rtrim($niceRange, ', ');
				$exchange = ucfirst($exchange); // please..

				// last always checked (since it has least data = failure to select won't take forever)
				$key === $numDatasets-1 ? $selected = 'checked' : $selected = '';

				# get flay array
				unset($set['numDays']);
				$set_flat = json_encode(array_flatten($set));

				# output rows
				$str .= "<tr class='$selected'>";
					$str .= '<td>';
					$str .= "<input type='radio' name='dataset' value='$set_flat' $selected> $exchange"; // NOTE: key (ref @ later stage -- really needed? only need exchange, pair and dates etc -- no ref to internal order)
					$str .= '</td>';
					$str .= "<td>$asset</td>";
					$str .= "<td>$currency</td>";
					$str .= "<td>$dateFrom &ndash; $dateTo</td>";
					$str .= "<td>$niceRange</td>";
				$str .= "</tr>";
			}

			$str .= "</tbody></table>";
		?>

	<section>
		<button class="button-outline small right tip" data-tip="New data? Clear the cache and reload the page." onclick="ajax.get('./system/clear_cache.php?clearcache=yes','Cache was cleared.<br>Reload the page to get new datasets etc');return false;">Clear cache</button>
	</section>

	<section id="datasets">
		<h3>Datasets</h3>
		<input type="text" id="filter" placeholder="Type to filter datasets" autofocus>
		<?= $str ?>
	</section>





	<!--
		STRATEGY SELECTION
	-->

	<?php

		# get available
		$strategies = $gab->get_strategies( $parse = false );
		unset($strategies['notoml']); // no toml = cannot use it

		# get all strategy names
		$strategy_names = array_keys($strategies);

	?>

	<section>
		<button class="button-outline small right tip" data-tip="New strategies? Clear the cache and reload the page." onclick="ajax.get('./system/clear_cache.php?clearcache=yes','Cache was cleared.<br>Reload the page to get new datasets etc');return false;">Clear cache</button>
	</section>

	<section id="strategies">
		<h3>Strategies</h3>

		<select name="strat" id='strat'>
		<?php
			foreach( $strategy_names as $k => $v ) {
				echo "<option>" . $v . '</option>';
			}
		?>
		</select>

		<textarea name="toml" id="toml" class="monospace"><?= array_values($strategies)[0]; ?></textarea>

		<?php
			/* generate list of ids with strats (for fast selections later via JS) */
			/* change this to include prev params */
			foreach( $strategies as $name => $params ) {
				if( contains('.', $name ) ) $name = str_replace('.','_', $name);
				echo "<textarea name='_{$name}' class='$name hidden'>$params</textarea>";
			}
		?>

	</section>


	<section>
		<h3>Other settings</h3>
		<row>
			<!-- CANDLE SIZE -->
			<column>
				<label for="candle_size">Candle size <small>in minutes, supports dynamic values e.g. 5:15,5</small></label>
				<input name="candle_size" id="candle_size" value="30" placeholder="Minutes or Range eg 5:15,5">
			</column>

			<!-- HISTORY SIZE -->
			<column>
				<label for="history_size">History size <small>Size in no. of candles</small></label>
				<input name="history_size" id="history_size" value="10" placeholder="History size in number of candles">
			</column>
		</row>

		<row>
			<column>
				<!-- TIMEOUT -->
				<label for="ajax_timeout">AJAX timeout <small>In minutes</small></label>
				<input type="number" name="ajax_timeout" id="ajax_timeout" value="15" placeholder="Timeout a run after X minutes, this due to the fact that some threads can hang.">
			</column>

			<!-- THREADS -->
			<column>
				<label for="threads">Threads <small>Number of threads</small></label>
				<input type="number" min="1" max="15" maxlength="2" name="threads" id="threads" value="3" placeholder="Note: Too many threads will not always speed things up.">
			</column>
		</row>

	</section>

	<section>
		<!-- SUBMIT -->
		<input type="submit" value="RUN IT!" id="submit" class="button-large">
	</section>


</form>


<!-- echo entire config as json object (for later use by js) -->
<div id="conf" class="hidden"><?= json_encode($conf) ?></div>

<!-- logger -->
<section id="logger">
	<hr>
	<h3>Log</h3> <button class="button-outline small" id="log_clear">CLEAR</button>
	<pre id="log_stat"><u>Status</u> <span id="log_status">Idle</span>  <u>Completed</u> <span id="log_runs">0</span>  <u>Duration</u> <span id="log_duration">0h 0m 0s</span></pre>
	<pre id="logs" class="hidden"></pre>
</section>

<?php
	require $conf->dirs->views . 'footer.php';
?>
