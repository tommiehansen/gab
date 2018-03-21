<?php
    $page = 'view';
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
    /* REQ's */
    require_once 'system/functions.php';
    require_once 'system/conf.php';
    require_once 'system/class.gab.php';
    $gab = new GAB\core($conf);


    # 'global' params
    $setup = (object) [];
    $setup->limit = _G('limit') ? _G('limit') : 20;
    $setup->order = _G('order') ? _G('order') : 'strategy_profit';

?>
        <form action="view.php" type="get">

            <section id="results">
                <h3>Select collection of runs</h3>
                <input type="text" id="filter" placeholder="Type to filter datasets" autofocus="true">

                <?php
                    if(_G('clearstatcache') === '1'){
                        clearstatcache();
                        echo "<p>Stat cache was cleared.</p>";
                    }
                ?>

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
                                    <a class='button button-outline small tip clean $cleanClass' rel='Cleans the results table and keeps 500 most profitable runs'>CLEAN</a>
                                    <a class='button button-outline small tip red remove' rel='Remove this result set'>R</a>
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
                <input type="text" id="limit" maxlength="2" name="limit" placeholder="50" value="<?= $setup->limit ?>">
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
            <input type="submit" value="Get data"> <button class="button-outline" onclick="window.location.href='view.php?clearstatcache=1';return false;">Clear stat cache</button>
        </column>
        </row>
        </form>
    </section>

<?php


    if(_G('db'))
    {
        $db = _G('db');

        $name = str_replace('.db','', $db);
        $q = explode('__', $name);

        $db = new PDO('sqlite:' .  $conf->dirs->results . $db) or die('Error @ db');

        $list = [
            'id',
            'candle_size',
            'strategy_profit',
            'sharpe',
            'alpha',
            'trades',
            'trades_win',
            'trades_lose',
            'trades_win_percent',
            'trades_win_avg',
            'trades_lose_avg',
            'trades_best',
            'trades_worst',
            'trades_per_day',
        ];

        $list_flat = implode(',', $list);

        $query = "
            SELECT
            *
            FROM RESULTS
            ORDER BY $setup->order DESC
            LIMIT $setup->limit
        ";

        $totalRuns = "
            SELECT count(success) as total FROM runs
        ";

        $db->beginTransaction();

            $res = $db->prepare($query);
            $res->execute();
            $res = $res->fetchAll(PDO::FETCH_ASSOC);

            # total runs for entire exchange/pair
            $total = $db->prepare($totalRuns);
            $total->execute();
            $total_runs = $total->fetchAll()[0]['total'];

        $db->commit();

        # single values
        $first = (object) $res[0];

        $market_profit = number_format($first->market_profit);
        $strategy_profit = number_format($first->strategy_profit);
        $strat_params = json_decode(gzdecode($first->strat_params), true);

        // parse strat_params
        $str = '';
        foreach( $strat_params as $k => $p ){
            $str .= "# $k\n";
            foreach($p as $kk => $v){
                $str .= "$kk = $v\n";
            }
        }


        $strat_str = $str;

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
                                <button class='show_popover'>View strat settings</button>
                                <div class='popover hidden'>
                                    <i class='close' onclick='this.parentNode.classList.toggle(\"hidden\")'>&times;</i>
                                    <h3>Strategy params</h3>
                                    <p>For best performing params</p>
                                    <textarea onfocus='this.select()'>$strat_str</textarea>
                                </div>
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
        $noTable = ['strat_params','report','roundtrips'];
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
                <button class='show_popover button-outline' rel='view.more.php?id=$id&db=$dbname'>Open</button>
            ";
            $tbl[$k]['&nbsp;'] = $str;
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
    if(_G('db')):
?>

<?php
    # exclude columns to calculate on
    $exclude = "id,trades,trades_win,trades_lose,market_profit,trades_win_avg,trades_lose_avg,trades_best,trades_worst,trades_per_day,sharpe,alpha,strat_params,report,roundtrips";
    $exclude = explode(",", $exclude);

    $sql = "
        SELECT * FROM results
        ORDER BY $setup->order DESC
        LIMIT $setup->limit
    ";

    $res = $db->query($sql);
    $res = $res->fetchAll(PDO::FETCH_ASSOC);

    $new = [];
    foreach($res as $key => $arr ){
        foreach($arr as $k => $v ){
            if( !in_array($k, $exclude) ) $new[$key][$k] = $v;
        }
    }

    $avg = [];
    foreach( $new as $key => $arr ){
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
    #prp($avg);
?>
<section>
    <hr>
    <h2>Average strategy</h2>
    <p>Average meta and strategy parameter settings for top <?= $setup->limit ?> sorted by '<?= $setup->order ?>'</p>

    <table class="striped colored">
    <thead>
        <tr>
            <th>Name</th>
            <th>Average</th>
        </tr>
    </thead>
    <tbody>
    <?php
        $str = '';
        $strat = "# Average strategy settings\n";
        foreach( $avg as $k => $v )
        {
            $old = $k;
            if( $k == 'strategy_profit' ){ $k = 'Strategy profit'; $v = $v . '%'; }
            if( $k == 'candle_size' ){ $k = 'Candle size'; $v = $v . ' min'; }
            if( contains('trades_', $k) ){ $k = str_replace('trades_', '', $k); }
            if( $k == 'win_percent' ) { $k = 'Win percent'; $v = $v . '%'; }
            $str .= "
                <tr>
                    <td>$k</td>
                    <td>$v</td>
                </tr>
            ";

            if( $old == 'candle_size' || $old == 'strategy_profit' || $old == 'trades_win_percent' ){}
            else {
                $strat .= "$k = $v\n";
            }
        }
        echo $str;
    ?>
</tbody>
    </table>

    <h4>Generated settings</h4>
    <p>From the averages above, excluding candle size etc</p>
    <form>
    <?php
        echo "<textarea class='strat_avg' id='strat_avg' onfocus='this.select()'>$strat</textarea>";
    ?>
    </form>

</section>






<?php
    // end if _G('db') set
    endif;
?>


















<script src="assets/jquery-3.3.1.min.js"></script>
<script src="assets/scripts.js"></script>

<script>
/* sortable table */
(function(){var g=/\bsortable\b/;document.addEventListener("click",function(d){var c=d.target;if("TH"==c.nodeName){var a=c.parentNode;d=a.parentNode.parentNode;if(g.test(d.className)){var e,b=a.cells;for(a=0;a<b.length;a++)b[a]===c?e=a:b[a].className=b[a].className.replace(" dir-down","").replace(" dir-up","");b=c.className;a=" dir-down";-1==b.indexOf(" dir-down")?b=b.replace(" dir-up","")+" dir-down":(a=" dir-up",b=b.replace(" dir-down","")+" dir-up");c.className=b;c=d.tBodies[0];b=[].slice.call(c.cloneNode(!0).rows,
0);var h=" dir-up"==a;b.sort(function(a,b){a=a.cells[e].innerText;b=b.cells[e].innerText;if(h){var c=a;a=b;b=c}return isNaN(a-b)?a.localeCompare(b):a-b});var f=c.cloneNode();for(a=0;a<b.length;a++)f.appendChild(b[a]);d.replaceChild(f,c)}}})})();
</script>

<script>
/* table stuff */
function tr_check(el){

    let input = el.find('input');
    input.prop('checked', 'checked');
    input.focus();
    input.trigger('select');
    el.parent().find('.checked').removeClass('checked');
    el.addClass('checked');

}

var results = $('#results');
results.on('click', 'tr', function(){
    tr_check($(this));
})


/* clean / remove table */
results.on('click', 'a', function(e){
    e.preventDefault(); e.stopPropagation();

    let t = $(this),
        tr = t.parents('tr'),
        rel = tr.attr('rel'), // db filename
        prevText = t.text();

    t.text('WAIT..')
    .addClass('button-secondary')
    .removeClass('button-outline');

    if( t.is('.remove') ){
        //alert('remove');
        var c = confirm('Sure? This will remove all data.');

        if( c ){
            ajax.get('system/remove_db.php?id=' + rel, 'DB removed', function(data){
                console.log(data);
                tr.next('tr').trigger('click'); // select next in line since this will get removed
                tr.remove();
                t.text(prevText)
                .removeClass('button-secondary')
                .addClass('button-outline'); // reset
            });
        }
        else {
            t.text(prevText)
            .removeClass('button-secondary')
            .addClass('button-outline'); // reset
        }
    }

    // not remove..
    else {
        ajax.get('system/clean_db.php?id=' + rel, 'DB was cleaned', function(data){
            t.parent().find('i').text(data);
            t.text(prevText)
            .removeClass('button-secondary')
            .addClass('button-outline'); // reset
        });
    }

})

/* auto resize textarea */
function autoSizeTextarea( self ){
    self.setAttribute('style','height: 0px; transition:none'); // reset
    self.style.height = (self.scrollHeight) + 'px';
    self.setAttribute('style', 'height:' + (self.scrollHeight + 30) + 'px');
}

/* show strat params */
$('#cards').on('click', '.show_popover', function(){
    var t = $(this),
        next = t.next('.popover');

    next.toggleClass('hidden');
    autoSizeTextarea(next.find('textarea')[0]);
})


/* filter table */
function filterTable( self, trs ){
    let val = self.value.toLowerCase();
    trs.each(function(){
        this.innerText.toLowerCase().indexOf(val) > -1 ? this.classList.remove('hidden') : this.classList.add('hidden');
    })
}

var trs = $('#results').find('tbody tr');
$('#filter').on('keyup', function(){
    filterTable(this, trs);
})

/* table open */
var tbl = $('#tbl');
tbl.on('click', 'button', function(){

    let rel = './' + this.getAttribute('rel');
    var hasPop = $('#popover');
    var popStr = "<div id='popover' class='popover more-data hidden'><i class='close' onclick='this.parentNode.classList.toggle(\"hidden\")'>&times;</i><div class='inner'>Loading...</div></div>";
    if( !hasPop.length ) {
        $(document.querySelector('body')).append(popStr); hasPop = $('#popover');
    }

    hasPop.find('div').text('Loading...');
    hasPop.toggleClass('hidden');

    ajax.get(rel, false, function(data){
        hasPop.find('div').html(data);
    });
})

/* autosize strategy average params */
let strat_avg = document.getElementById('strat_avg');
if(strat_avg) autoSizeTextarea(strat_avg);
</script>
</body>
</html>
