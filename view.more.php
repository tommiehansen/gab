<?php

    /* set large defaults for PHP */
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('max_execution_time', 3600000);
    ini_set('memory_limit','512M');
    set_time_limit(0);


    require_once 'system/conf.php';
    require_once 'system/functions.php';

    # checks
    _G('db') ? $db = _G('db') : $db = false;
    _G('id') ? $id = _G('id') : $id = false;
    _G('windowed') ? $windowed = _G('windowed') : $windowed = false;
    if(!$db || !$id) die('E');

    $page = 'view';
?>

<?php if($windowed): ?>
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
    <section>
<?php endif; ?>

<h3>Run id <?= $id ?></h3>
<?php if($windowed): ?>
<p><?= $db ?></p>
<?php endif; ?>
<a href="#more_data" class="button">Data</a>
<a href="#more_strategy" class="button">Strategy params</a>
<a href="#more_roundtrips" class="button">Roundtrips</a>

<?php if(!$windowed): ?>
<a class="button button-outline" href="view.more.php?db=<?= $db ?>&id=<?= $id ?>&windowed=true" target="_blank">Open in new window</a>
<?php endif; ?>

<?php

    /* get data */
    $db = new PDO('sqlite:' .  $conf->dirs->results . $db) or die('Error @ db');

    $sql = "
        SELECT * FROM results
        WHERE id = '$id'
    ";

    $res = $db->query($sql);
    $res = $res->fetchAll(PDO::FETCH_ASSOC)[0];

    $blobs = ['roundtrips', 'strat_params', 'report'];
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
        <table>
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
    $strat = json_decode(gzdecode($new_blobs['strat_params']), true);

    $str = '';
    foreach( $strat as $name => $arr ){
        $str .= "# $name\n";
        foreach($arr as $k => $v ){
            $str .= "$k = $v\n";
        }
    }

    echo "<hr><h4 id='more_strategy'>Strategy params</h4><p>All the parameters stored</p>";
    echo "<form><textarea onload='autoSizeTextarea(this)' onfocus='this.select();' onclick='autoSizeTextarea(this)' style='min-height:320px'>$str</textarea></form>";

    echo "
        <hr>
        <h4 id='more_roundtrips'>Roundtrips</h4>
    ";


    #prp($roundtrips);


    foreach($roundtrips as $key => $arr )
    {
        if( $key === 0 ){
            echo "
                <table class='tbl striped' style='transform: translateZ(0)'>
                <thead>
                    <tr>
            ";
            foreach( $arr as $k => $v ){
                if( $k == 'pnl' ) continue;
                if( $k == 'entryAt' ) $k = 'Entry';
                if( $k == 'exitAt' ) $k = 'Exit';
                echo "<th>$k</th>";
            }
            echo "
                </tr>
                </thead>
                <tbody>
            ";
        }

        echo "<tr>";
        foreach( $arr as $k => $v ){
            if( $k == 'pnl' ) continue;
            if( contains('00Z', $v) ) $v = substr(tdate($v), 0, 16);
            if( $k == 'duration' ) $v = secondsToHuman($v/1000);
            if( is_numeric($v) ) $v = number_format($v, 2);
            if( $k == 'profit' ) {
                if( contains('-', $v) ){
                    $v = '<span class="negative">' . $v . '%</span>';
                }
                else {
                    $v = '<span class="positive">' . $v . '%</span>';
                }
            }
            echo "<td>$v</td>";
        }
        echo "</tr>";
    }

    echo "</tbody></table>";

?>


<?php if($windowed): ?>
</section>


<script>
/* auto resize textarea */
function autoSizeTextarea( self ){
    self.setAttribute('style','height: 0px; transition:none'); // reset
    self.style.height = (self.scrollHeight) + 'px';
    self.setAttribute('style', 'height:' + (self.scrollHeight + 30) + 'px');
}
</script>
</body>
</html>

<?php endif; ?>
