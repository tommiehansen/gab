<?php

    require_once '../system/conf.php';
    require_once $conf->dirs->system . 'functions.php';
    require_once $conf->dirs->system . 'class.gab.php';

    # checks
    _G('db') ? $db = _G('db') : $db = false;
    _G('id') ? $id = _G('id') : $id = false;
    if(!$db || !$id) die('E');

    $page = 'view.more';
    $page_title = 'View more';

    require 'header.php';

    $gab = new GAB\core($conf);

    # get / check db type
	$dbc = $conf->db;
	$dbc->host == 'sqlite' ? $isMySQL = false : $isMySQL = true;
?>


<section>
<h3>Run id <?= $id ?></h3>
<p><?= $db ?></p>

<div id="scrollButtons">
    <a href="#more_data" class="button">Data</a>
    <a href="#more_strategy" class="button">Strategy params</a>
    <a href="#more_roundtrips" class="button">Roundtrips</a>
</div>

<?php

    if( $isMySQL )
    {
        $dbName = str_replace('.db', '', $db);
        $con = "mysql:host=".$dbc->host.";dbname=$dbName;charset=utf8mb4";
        $db = new PDO($con, $dbc->user, $dbc->pass) or die("Error connecting to MySQL");
    }
    else {
        $db = new PDO('sqlite:' .  $conf->dirs->results . $db) or die('Error @ db');
    }

    $sql = "
        SELECT * FROM results a
        JOIN blobs b ON a.id = b.id
        WHERE a.id = '$id'
    ";

    $res = $db->query($sql);
    $res = $res->fetchAll(PDO::FETCH_ASSOC)[0];

    $blobs = ['roundtrips', 'report'];
    $normal = 'candle_size,strategy_profit,market_profit,sharpe,alpha,trades,trades_win,trades_lose,trades_win_percent,trades_win_avg,trades_lose_avg,trades_best,trades_worst,trades_per_day';
    $normal = explode(',', $normal);

    $new = [];
    $new_blobs = [];

    foreach($res as $k=>$r){
        if( in_array($k, $normal) ) { $new[$k] = $r; }
        else if( in_array($k, $blobs) ) { $new_blobs[$k] = $r; }
    }

    # roundtrips blob used later
    $roundtrips = json_decode(gzdecode($new_blobs['roundtrips']), true);

    // get All Time High balance
    $ath = [];

    foreach($roundtrips as $key => $arr ){
        $ath[$key]['balance'] = $roundtrips[$key]['exitBalance'];
        $ath[$key]['date'] = $roundtrips[$key]['exitAt'];
    }

    $price_last = end($ath);
    $price_ath = max($ath); // array

    #$price_from_ath = ($price_ath['balance'] / $price_last['balance']);
    $price_from_ath = ($price_last['balance'] / $price_ath['balance']) * 100;
    $price_from_ath < $new['strategy_profit'] ? $price_from_ath = '-' . $price_from_ath : '';
    $price_from_ath = number_format($price_from_ath, 2) . '%';



    // pre process data
    $pre = [];
    foreach( $new as $k => $v )
    {
        if( $k == 'candle_size' ) { $k = 'candle'; $v = $v . ' min'; }
        if( $k == 'strategy_profit' ){ $k = 'profit'; $v = number_format($v) . '%'; }
        if( contains('trades_', $k) ){ $k = str_replace('trades_', '', $k); }
        if( $k == 'strategy_profit' ){ $k = 'profit'; $v = number_format($v) . '%'; }
        if( $k == 'win_percent' ) $v = $v . '%';
        if( $k == 'win_avg' ) $v = $v . '%';
        if( $k == 'lose_avg' ) $v = $v . '%';
        if( $k == 'best' ) $v = $v . '%';
        if( $k == 'worst' ) $v = $v . '%';
        if( $k == 'market_profit' ){ $k = 'market'; $v = number_format($v) . '%'; }

        $pre[$k] = $v;
    }

    $tpl = "
        <tr>
            <td>_title_</td>
            <td>_subtitle_</td>
            <td>_data_</td>
        </tr>
    ";

    echo "
        <hr>
        <h4 id='more_data'>Data</h4>
        <table class='colored striped'>
        <thead>
            <th>Name</th>
            <th>Description</th>
            <th>Value</th>
        </thead>
        <tbody>
    ";

    foreach( $pre as $k => $v )
    {
        $str = $tpl;
        $title = ucfirst($k);
        $str = str_replace('_title_', $title, $str);
        $subtitle = $k;

        if( $k == 'candle' ) $subtitle = 'Candle size in minutes';
        if( $k == 'profit' ) $subtitle = 'Strategy profit/return in percent';
        if( $k == 'market' ) $subtitle = 'Market profit in percent';
        if( $k == 'sharpe' ) $subtitle = 'Risk/return ratio';
        if( $k == 'alpha' ) $subtitle = 'Alpha performance';
        if( $k == 'trades' ) $subtitle = 'Total number of trades';
        if( $k == 'win' ) $subtitle = 'Number of winning trades';
        if( $k == 'lose' ) $subtitle = 'Number of losing trades';
        if( $k == 'win_percent' ) $subtitle = 'Percent winning trades out of total';

        if( $k == 'win_avg' ) $subtitle = 'Average gain for winning trades';
        if( $k == 'lose_avg' ) $subtitle = 'Average loss for losing trades';
        if( $k == 'best' ) $subtitle = 'Best trade gain';
        if( $k == 'worst' ) $subtitle = 'Worst trade loss';

        if( $k == 'per_day' ) $subtitle = 'Average number of trades per day';

        $str = str_replace('_subtitle_', $subtitle, $str);
        $str = str_replace('_data_', $v, $str);

        // add-ins
        if( $k == 'profit' )
        {
            $price_from_ath; $price_ath; $price_last;

            $a = '<sup>$</sup>' . number_format($price_last['balance']);
            $b = date('Y-m', strtotime($price_last['date']));


            $s = $tpl;
            $s = str_replace('_title_','Current balance',$s);
            $s = str_replace('_subtitle_', 'Sum at end of run', $s);
            $s = str_replace('_data_', "$a ($b)", $s);
            $str .= $s;

            $a = number_format($price_ath['balance']);
            $b = date('Y-m', strtotime($price_ath['date']));

            $s = $tpl;
            $s = str_replace('_title_','ATH Price/Value',$s);
            $s = str_replace('_subtitle_', 'All Time High balance for strategy', $s);
            $s = str_replace('_data_', "<sup>$</sup>$a ($b)", $s);
            $str .= $s;

        }

        echo $str;
    }
    echo "</tbody></table>";



    /* get strategy */
    $strat = json_decode(gzdecode($res['strat']), true);
    $strat = array_values($strat);
    $str = $gab->create_toml($strat[0]);

    echo "<hr><h4 id='more_strategy'>Strategy params</h4><p>All the parameters stored</p>";
    echo "<h5>TOML</h5>";
    echo "<form><textarea onfocus='this.select();' onclick='gab.autoSizeTextarea(this)' style='min-height:320px'>$str</textarea></form>";

    $str = json_encode($strat[0],JSON_PRETTY_PRINT);

    echo "<h5>JSON</h5>";
    echo "<form><textarea onfocus='this.select();' onclick='gab.autoSizeTextarea(this)' style='min-height:320px'>$str</textarea></form>";

    echo "
        <hr>
        <h4 id='more_roundtrips'>Roundtrips</h4>
    ";


    #prp($roundtrips);


    foreach($roundtrips as $key => $arr )
    {
        if( $key === 0 ){
            echo "
                <table id='roundtrips_table' class='tbl colored striped sticky' style='transform: translateZ(0)' data-sortable>
                <thead>
                    <tr>
            ";
            foreach( $arr as $k => $v ){
                if( $k == 'id' || $k == 'pnl' ) continue;
                $color = '';
                if( contains('exit', $k) ) $color = 'exit';
                if( $k == 'entryAt' ) $k = 'Entry';
                if( $k == 'exitAt' ) $k = 'Exit';
                if($k == 'entryPrice') $k = 'Price';
                if( $k == 'entryBalance' ) $k = 'Balance';
                if( $k == 'exitPrice' ) $k = 'Price';
                if( $k == 'exitBalance' ) $k = 'Balance';
                echo "<th class='$color'>$k</th>";
            }
            echo "
                </tr>
                </thead>
                <tbody>
            ";
        }

        echo "<tr>";
        foreach( $arr as $k => $v ){
            $orig = $v;

            if( $k == 'id' || $k == 'pnl' ) continue;
            if( contains('00Z', $v) ) { $v = substr(tdate($v), 0, 16); }
            if ('entryAt' == $k || 'exitAt' == $k) $v = date('Y-m-d H:i',$v);

            if( $k == 'duration' ) $v = secondsToHuman($v/1000);

            if( is_numeric($v) ) {
                if ('entryPrice' == $k || 'exitPrice' == $k) {
                    $v = number_format($v, 8);
                } else {
                    $v = number_format($v, 2);

                }

            }
            if( $k == 'profit' ) {
                if( contains('-', $v) ){
                    $v = '<span class="negative">' . $v . '%</span>';
                }
                else {
                    $v = '<span class="positive">' . $v . '%</span>';
                }
            }
            echo "<td data-value='$orig'>$v</td>";
        }
        echo "</tr>";
    }

    echo "</tbody></table>";

?>
</section>

<script>
window.onload = function(){
    let src = "<?php echo $conf->urls->assets; ?>floatThead.js";
    $.getScript(src, function(){
        $('#roundtrips_table').floatThead({
            position: 'absolute',
            top: 80
        });
    })

    $('#scrollButtons').on('click', 'a', function(e){
        e.preventDefault();
        var target = this.hash;
		$('html, body').animate({
			scrollTop: $(target).offset().top-100
		}, 500);
    })
}
</script>

<?php
    require 'footer.php';
?>
