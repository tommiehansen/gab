<?php
    require_once 'conf.php';
    if( $conf->allow_origin ){
        header('Access-Control-Allow-Origin: *');
    }

    /*
        POST
        Deals with POSTS in our invented psuedo-TOML files
        and then runs runner.php
        -
        Accepts
        $params: json
    */

    if( !$_POST ) {
        die('There was no _POST set');
    }

    #print_r($_POST);
    #exit;

    # init
    require_once $conf->dirs->system . 'functions.php';
    require_once $conf->dirs->system . 'class.gab.php';
    $gab = new \GAB\core($conf);

    /*
        COMPILE STRATEGY FROM RANGE-SETTINGS
    */

    $dataset = _P('dataset');
    $params = _P('toml');
    $strat = _P('strat');
    $candle_size = _P('candle_size');
    $history_size = _P('history_size');
    $debug = _P('debug');

    // change dataset
    if(_P('from'))
    {
        $dateFrom = _P('from');
        $dateTo = _P('to');
        $ds = json_decode($dataset);
        #prp($ds);
        $ds->from = _P('from');
        $ds->to = _P('to');
        $dataset = json_encode($ds);
    }

    // check if debug mode
    if( $debug === 'true' ){ $debug = true; }
    else { $debug = false; }

    // check if cli mode
    _P('cli') ? $isCli = true : $isCli = false;

    /* LOOP LINES IN TOML */
    $lines = preg_split("/((\r?\n)|(\r\n?))/", $params);
    $new = '';
    $hasError = false;

    foreach( $lines as $line )
    {
        if(contains(':', $line ))
        {
            // all lines must has equals sign so use to explode
            $lineArr = explode('=', $line);

            $vals = rmspace( $lineArr[1] );
            $vals = str_replace(',', ':', $vals); // make all separators : for simplicy
            $vals = explode(':', $vals); // create array

            // get min/max
            $min = $vals[0];
            $max = $vals[1];

            // error check
            if( !empty($vals[2]) )
            {
                // not empty, get stepping
                $step = $vals[2];
            }
            else {
                $hasError[] = 'No stepping set. Format is min:max,stepping';
                continue;
            }

            /* generate entire range */

            // generate + error check
            if( !$range = @range($min, $max, $step) ){
                die("<u class='info'>ERROR</u> Step exceeds the specified range, fix your strategy settings! Setting was: {$min}:{$max},${step}");
            }
			else {
				$range = @range($min, $max, $step);
			}

            // ...and add back $min
            $range[0] = $min;

            // all above is due to the fact that range() is inclusive and thus we need this logic
            // TODO: make a function out of this

            // shuffle and get first from shuffled
            shuffle($range);
            $range = $range[0];

            // put back at line
            $new .= $lineArr[0] .'= '. $range . "\n";
        }

        // else just add to new lines
        else {
            $new .= $line . "\n";
        }
    }

    # use to debug strat params
    #prp( $new ); exit;

    # candle size
    if( contains(':', $candle_size) )
    {

        $vals = rmspace( $candle_size );
        $vals = str_replace(',', ':', $vals); // make all separators : for simplicy
        $vals = explode(':', $vals); // create array

        $min = $vals[0];
        $max = $vals[1];
        $step = $vals[2];

        // error-check the range (or die)
        if( !$range = @range($min, $max, $step) ){
            die('<u class="info">ERROR</u> Step exceeds the specified range for <u class="bad">Candle size</u>, fix your strategy settings!');
        }

        // ..then remove all under $min
        foreach( $range as $key => $val ){
            if($range[$key] < $min ) unset($range[$key]);
        }

        // ...and add back $min
        $range[0] = $min;

        // shuffle and get first from shuffled
        shuffle($range);
        $range = $range[0];

        $candle_size = $range;

    }

    #prp($candle_size); exit;

    // debug
    if( $debug )
    {
        $str = "\n<u class='bad'>Candle size</u> <u class='yellow'>$candle_size</u>\n";
        $str .= "<u class='bad'>Strategy</u>\n<u class='yellow'>" . $new . '</u>';
        echo $str;
        exit;
    }

    if( $hasError )
    {
        echo '<p>There were errors <br>';
        foreach( $hasError as $k => $v )
        {
            echo "- $v <br>";
        }
        echo "</p>";
    }

    // no errors
    else {

        # take new string and generate array (from toml)
        try {

            # post fields
            $q['strategy_name'] = $strat;
            $q['strategy_params'] = json_encode($gab->parse_toml($new));
            $q['dataset'] = $dataset;
            $q['candle_size'] = $candle_size;
            $q['history_size'] = $history_size;
            if($isCli) $q['cli'] = true;

            #prp($q); exit;

            $domain = $_SERVER['HTTP_HOST'];
            $prefix = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
            $dir = dirname($_SERVER['PHP_SELF']);

            // fix subdomains
            if( $conf->allow_origin )
            {
                if( contains('gab', $domain) ){
                    $d = explode('.', $domain); // so user can have gab90.host.host.com
                    unset($d[0]); // remove first e.g. gab90
                    $d = implode('.', $d); // then put back together
                    $domain = $d;
                }
            }

            $post = curl_post2($prefix . $domain . $dir . '/runner.php', $q); // this echo entire <html>...
            if( $isCli ) $post = strip_tags($post);
            echo $post;
            exit;
        }
        catch(Exception $e){
            echo 'Error in your TOML, Format for range values are min:max,stepping e.g. 20:60,10';
            echo "\n";
            echo $e->getMessage();
            exit;
        }
    }
