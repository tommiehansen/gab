<?php
	/*
		GAB
		Gekko Automated Backtests
		-
		TODO (* = DONE):
		* - Save form fields via localStorage...! else is super-annoying
		* - Add new fields to database with calculated min/max/etc stuff
		* - Add buttons to clear everything inside cache/ directory
		* - Compile run helpful statistics out of the roundtrips:
			a. Max Drawdown / Max Gain
			b. Winning Trades / Losing Trades
			c. Bad Trade average loss, Good trade average gain
		* - Fix the damn CSS so one can have multi columns; fix a proper stylesheet....
		- MOVE huge blobs to other table
		This because the huge blobs are only used in view.more but slows anything other
		down as FUCK.

		- Allow date selection in Y-m-d format:
			input: dateFrom
			input: dateTo
		Gekko wants the date range in this format:
		from: 2017-12-01T10:00:00Z
		to: 2018-01-10T10:00:00Z

		Maybe not add though, would also create unique backset tests
		since PERIOD A results would differ greatly from PERIOD B results.

		- multi dimensional TOML-files doesn't really work
		gets flattened too much somewhere or something:

		* - Save ALL dynamic strategy params to localStorage (so that switching to another doesn't delete all previous settings for a strat... )
		 * ^ above becomes a lot of saves though.....
		```
		[params]
		hi = 10
		[params2]
		hi = 10
		```
		doesnt work
	*/

	require_once 'system/conf.php';
	require_once 'system/functions.php';
	require_once 'system/class.gab.php';

	$page = 'select';

?>
<!doctype html>
<html lang="en-us">
<head>
	<title>GAB - Gekko Automated Backtests</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=1">
	<link href="assets/css/styles.css" rel="stylesheet">
</head>
<body class="medium">
<?php include 'system/nav.php' ?>

<?php

	# new instance
	$gab = new \GAB\core($conf);

	# get exchanges and pairs
	$datasets = $gab->get_datasets();

?>

<form method='post' action='post.php' id='gab_selectForm'>

	<!--
		DATASET SELECTION
	-->

		<?php

			# create temp arr
			$new = [];

			/* pre-process: fix multi-period exchange/pair */
			foreach( $datasets as $key => $set )
			{

				#if( $set['exchange'] != 'binance') continue; # TEMP: only binance now

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
		<button class="button-outline small right tip" rel="New data? Clear the cache and reload the page." onclick="ajax.get('./system/clear_cache.php?clearcache=yes','Cache was cleared.<br>Reload the page to get new datasets etc');return false;">Clear cache</button>
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
		<button class="button-outline small right" onclick="ajax.get('./system/clear_cache.php?clearcache=yes','Cache was cleared.<br>Reload the page to get new datasets etc');return false;">Clear cache</button>
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

	</section>

	<?php
		/* generate list of ids with strats (for fast selections later via JS) */
		/* change this to include prev params */
		foreach( $strategies as $name => $params ) {
			echo "<div id='$name' class='hidden'>$params</div>";
		}
	?>


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

<div id="scratchpad" class="popover hidden">
	<i class="close" onclick="this.parentNode.classList.toggle('hidden')">&times;</i>
	<h3>Scratchpad</h3>
	<p>Write notes and stuff, saves automatically</p>
	<form>
		<textarea></textarea>
	</form>
</div>

















<script src="assets/jquery-3.3.1.min.js"></script>
<script src="assets/scripts.js"></script>

<script>

	/* scratchpad */
	let scratch = $('#scratchpad');
	scratch.on('keyup', 'textarea', function(){
		localStorage.setItem('scratch', this.value);
		autoSizeTextarea(this);
	})

	if( localStorage.getItem('scratch') )
	{
		let sa = scratch.find('textarea');
		sa.val(localStorage.getItem('scratch'));
		setTimeout(function(){
			autoSizeTextarea(sa[0]);
		}, 200)
	}

</script>

<script>
	// check missing stepping in #toml
	$('#toml').on('blur', function(){
		let lines = $(this).val().split('\n'),
			len = lines.length;

		var vals, cur, i = 0;

		for(i;i<len;i++){
			cur = lines[i];
			if( cur.indexOf('=') !== -1 ){
				cur = cur.split('= ');
				cur = cur[1];
				if( cur.indexOf(':') !== -1 ){
					if( cur.indexOf(',') !== -1 ){}
					else { alert('Strategy settings is missing stepping at line ' + i); }
				}
			}
		}
	})
</script>


<script>
/*
* sayt - Save As You Type
* Licensed under The MIT License (MIT)
* http://www.opensource.org/licenses/mit-license.php
* Copyright (c) 2011 Ben Griffiths (mail@thecodefoundryltd.com)
*/
!function(a){a.fn.sayt=function(b){function k(b){var d="";jQuery.each(b,function(a,b){d=d+b.name+":::--FIELDANDVARSPLITTER--:::"+b.value+":::--FORMSPLITTERFORVARS--:::"}),"undefined"!=typeof Storage?localStorage.setItem(e,d):a.cookie(e,d,{expires:c.days})}function l(a,b,c){var d=(a+"").indexOf(b,c||0);return d!==-1&&d}function m(b,c){var d=a.extend({},b),e=d.find("[data-sayt-exclude]");e.remove();for(i in c)e=d.find(c[i]),e.remove();var f=d.serializeArray();return f}var c=a.extend({prefix:"autosaveFormCookie-",erase:!1,days:3,autosave:!0,savenow:!1,recover:!1,autorecover:!0,checksaveexists:!1,exclude:[],id:this.attr("id")},b),d=this,e=c.prefix+c.id;if(1==c.erase)return a.cookie(e,null),"undefined"!=typeof Storage&&localStorage.removeItem(e),!0;var f;if(f="undefined"!=typeof Storage?localStorage.getItem(e):a.cookie(e),1==c.checksaveexists)return!!f;if(1==c.savenow){var g=m(d,c.exclude);return k(g),!0}if(1==c.autorecover||1==c.recover){if(f){var h=f.split(":::--FORMSPLITTERFORVARS--:::"),j={};a.each(h,function(b,c){var d=c.split(":::--FIELDANDVARSPLITTER--:::");""!=a.trim(d[0])&&(a.trim(d[0])in j?j[a.trim(d[0])]=j[a.trim(d[0])]+":::--MULTISELECTSPLITTER--:::"+d[1]:j[a.trim(d[0])]=d[1])}),a.each(j,function(b,c){if(l(c,":::--MULTISELECTSPLITTER--:::")>0){var e=c.split(":::--MULTISELECTSPLITTER--:::");a.each(e,function(c,e){a('input[name="'+b+'"], select[name="'+b+'"], textarea[name="'+b+'"]',a(d)).find('[value="'+e+'"]').prop("selected",!0),a('input[name="'+b+'"][value="'+e+'"], select[name="'+b+'"][value="'+e+'"], textarea[name="'+b+'"][value="'+e+'"]',a(d)).prop("checked",!0)})}else a('input[name="'+b+'"], select[name="'+b+'"], textarea[name="'+b+'"]',a(d)).val([c])})}if(1==c.recover)return!0}1==c.autosave&&this.find("input, select, textarea").each(function(b){a(this).change(function(){var a=m(d,c.exclude);k(a)}),a(this).keyup(function(){var a=m(d,c.exclude);k(a)})})}}(jQuery);
</script>

<script>

	/* save/restore form */
	var selectForm = $('#gab_selectForm');
	selectForm.sayt({ 'autorecover': true, 'days': 999 });
	selectForm.on('change', function(){
		$(this).sayt({'savenow': true});
	})

	// fix color selction
	var datasets = $('#datasets');
	datasets.find('tr').removeClass('checked');
	datasets.find(':checked').parents('tr').addClass('checked');

</script>





<script>

	/* get config from php echo */
	var conf = document.getElementById('conf').innerText;
	conf = JSON.parse(conf);

	/* ajax / submit */

	// globals (evil)
	runCount = 0;
	noResultRuns = 0;
	xhrPool = [];
	intervalCounter = null;
	elapsedTime = 0;
	stopAll = false;

	$(document).ajaxSend(function(e, jqXHR, options){
		xhrPool.push(jqXHR);
	});

	$(document).ajaxComplete(function(e, jqXHR, options) {
		xhrPool = $.grep(xhrPool, function(x){return x!=jqXHR});
	});

	window.abortAllAjaxRequests = function() {
		$.each(xhrPool, function(idx, jqXHR) {
			jqXHR.abort();
		});
	};

	let f = $('#gab_selectForm');
	let log_duration = $('#log_duration');

	f.on('submit', function(e){
		e.preventDefault();

		// VARs
		var serialized = $(this).serialize(),
			form_url = $(this).prop('action'),
			timeout = $('#ajax_timeout')[0].value * 60000,
			maxNoResultsRuns = 50,
			noResultRuns = 0, // reset
			threads = f.find('#threads')[0].value,
			sub = $('#submit');

		sub.blur();

		// cancelling (NOTE: reverse logic)
		if( stopAll ) {
			abortAllAjaxRequests();
			sub[0].value = 'RUN IT!';
			sub.removeClass('on');
			stopAll = false;
			talk.cancel(); // kill all speech
			talk.say('Stopping running instances.');
			runCount = 0; // reset
			noResultRuns = 0; // reset
			elapsedTime = 0;
			clearInterval(intervalCounter);

			$('#log_runs').text('0'); // reset
			$('#log_duration').text('0h 0m 0s'); // reset

			// NOTE: no way to kill Gekko itself since backtests doesnt return Gekko run id

			return false; // quit here
		}
		// running
		else {
			stopAll = true;

			runCount = 0; // reset
			noResultRuns = 0; // reset
			elapsedTime = 0;

			// set status
			sub[0].value = 'STOP IT!';
			sub.addClass('on');
			let strat = $('#strat option:selected').text();
			let strat_orig = strat;
			$('#log_status').text("Running with " + threads + ' threads');
			$('#logs').removeClass('hidden').html('<u class="info">INFO</u> <u class="success">Running strategy: '+ strat_orig +', please stand by...</u>');

			// say stuff
			strat = strat.replace(/_/g,'. ');
			strat = strat.replace(/-/g,'. ');
			talk.cancel();
			talk.say('Running strategy: '+ strat +'. Using ' + threads + ' threads, please stand by.');

			// start interval
			intervalCounter = setInterval(function(){
				elapsedTime++;
				var str = elapsedTime;
				str = secondsToMinutes(str);
				log_duration[0].innerText = str;
			}, 1000);
		}




		/* init multi threads */
		var len = threads.length, i = 0;
		while(threads--){
			jax_multi(form_url, serialized, timeout, maxNoResultsRuns);
		}

	})

	// seconds to minutes
	function secondsToMinutes( time ){
		//return Math.floor(seconds / 60) + ':' + ('0' + Math.floor(seconds % 60)).slice(-2)

		var hours = Math.floor(time / 3600);
		time -= hours * 3600;
		var minutes = Math.floor(time / 60);
		time -= minutes * 60;
		var seconds = parseInt(time % 60, 10);

		return hours + 'h ' + minutes + 'm ' + seconds + 's';
	}

	// generate timestamp and force 24h time format
	function localTimestamp(){
		return new Date().toLocaleTimeString('en-GB');
	}

	function jax_multi(form_url, serial, timeout, maxNoResultsRuns ){

		if( noResultRuns < maxNoResultsRuns+1 ){

			$.ajax({
				type: "POST",
				url: form_url,
				data: serial,
				timeout: timeout,
				start_time: new Date().getTime(),
				beforeSend: function (jqXHR, settings) {
	   				xhrPool.push(jqXHR);
   				},
				success: function(data){

					//talk.cancel(); // make sure to cancel, else 20x 'completed' will come one after the other
					//talk.say('Completed.');

					// set text
					var data = $.parseHTML(data);
					var str =  $(data).text();

					let logs = $('#logs');

					// nice formatting
					str = str.replace('Bad!','<u class="bad">Bad!</u>');
					str = str.replace('Success!','<u class="success">Success!</u>');
					let time = '<u class="timestamp">' + localTimestamp() + '</u>';
					logs.prepend(time + ' ' +str + "\n");

					// set runCount
					runCount++;
					$('#log_runs').text(runCount);

					// check if no. of pre-lines is too massive and cut..
					let lines = logs.html().split('\n');
					let preLen = lines.length;
					if( preLen > 50 ){
						lines = lines.splice(0,50);
						lines = lines.join('\n');
						logs.html(lines);
					}

					// check if this is a 'no results run' meaning it is already ran
					// if 'no results run > X' stop everything since nothing more to run
					var request_time = (new Date().getTime()-this.start_time)/1000;
					if( request_time < 2 ){ // NOTE: if user runs strategy that takes 0 seconds... it won't work that well
						noResultRuns++;
					}
					else {
						noResultRuns = 0; // reset since there seems to be runs left
					}

					jax_multi(form_url, serial, timeout, maxNoResultsRuns);

				},
				error: function(){
					if( !stopAll ){ // this is reversed logic...
						$('#logs').prepend("Error -- running again..\n");
						jax_multi(form_url, serial, timeout, maxNoResultsRuns);
					}
					else {
						$('#log_status').text('Stopped: Press RUN IT! to run again.');
					}
				},
			}); // ajax

		} // if noResultRuns < maxNoResultsRuns
		else {
			$('#logs').text('Exhausted all possible combinations -- stopping automatically.');
			noResultRuns = 0; // reset
			$('#submit').trigger('click');
		}

	} // jax_multi()


	// clear logs
	$('#log_clear').on('click', function(){
		$('#logs')[0].innerText = '';
	})

</script>















<script>

	/* dataset stuff */
	function tr_check(el){

		let input = el.find('input');
		input.prop('checked', 'checked');
		input.focus();
		input.trigger('select');
		el.parent().find('.checked').removeClass('checked');
		el.addClass('checked');

	}

	function filterTable( self, trs ){
		let val = self.value.toLowerCase();
		trs.each(function(){
			this.innerText.toLowerCase().indexOf(val) > -1 ? this.classList.remove('hidden') : this.classList.add('hidden');
		})
	}


	var datasets = $('#datasets'),
		ds = datasets.find('tbody'),
		trs = ds.find('tr'),
		trsLen = trs.length;

	datasets.on('keyup', '#filter', function(){
		filterTable(this, trs);
	});

	datasets.on('click', 'tr', function(){
		tr_check($(this));
	})

	datasets.on('keydown', '#filter', function(e){
		let kc = e.keyCode || e.which;
		if( kc == '9' ) {
			e.preventDefault();
			tr_check(datasets.find('tbody tr').not('.hidden').first());
		}
	})





	/* strat selection */
	let strategies = $('#strategies');

	strategies.on('change', '#strat', function(){
		let val = this.value;
		let toml = $('#'+val)[0].innerText;
		let textarea = $('#toml')[0];
		textarea.value = toml;
		autoSizeTextarea(textarea);
	})

	function autoSizeTextarea( self ){
		self.setAttribute('style','height: 0px; transition:none'); // reset
		self.style.height = (self.scrollHeight) + 'px';
		self.setAttribute('style', 'height:' + (self.scrollHeight + 30) + 'px');
	}

	setTimeout(function(){
		autoSizeTextarea(strategies[0].querySelector('textarea')); // init @ load
	}, 100);

	strategies.on('keyup', 'textarea', function(){
		autoSizeTextarea(this);
	})

</script>

</body>
</html>
