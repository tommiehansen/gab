<?php
    /*
        POST
        Deals with POSTS in our invented psuedo-TOML files
        and then runs runner.php
        -
        Accepts
        $params: json
    */

    /* set large defaults for PHP */
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('memory_limit','512M');
    set_time_limit(3600);

    if( !$_POST ) {
        die('There was no _POST set');
    }



    # init
    require_once 'system/conf.php';
    require_once 'system/functions.php';
    require_once 'system/class.gab.php';
    $gab = new \GAB\core($conf);

    /*
        COMPILE STRATEGY FROM RANGE-SETTINGS
    */

    #echo 'run: ' . date('h:m:s');
    #prp($_POST);
    #exit;

    $dataset = _P('dataset');
    $params = _P('toml');
    $strat = _P('strat');
    $candle_size = _P('candle_size');
    $history_size = _P('history_size');

    # loop all lines
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

            // generate entire range
            $range = range(0, $max, $step);

            // ..then remove all under $min
            foreach( $range as $key => $val ){
                if($range[$key] < $min ) unset($range[$key]);
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

    #prp( $new );

    /* candle */
    if( contains(':', $candle_size) ){

        $vals = rmspace( $candle_size );
        $vals = str_replace(',', ':', $vals); // make all separators : for simplicy
        $vals = explode(':', $vals); // create array

        $min = $vals[0];
        $max = $vals[1];
        $step = $vals[2];

        $range = range($min, $max, $step);

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

            #prp($q);

            $domain = $_SERVER['HTTP_HOST'];
            $prefix = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
            $dir = dirname($_SERVER['PHP_SELF']);

            $post = curl_post2($prefix . $domain . $dir . '/runner.php', $q); // this echo entire <html>...
            #$post = strip_tags($post, '<dv');
            echo $post;

        }
        catch(Exception $e){
            echo '<p>Error in your TOML, Format for range values are min:max,stepping e.g. 20:60,10</p>';
        }
    }
