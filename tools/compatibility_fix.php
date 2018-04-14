<?php
    /*
        Compatibility fix
        -
        Let's users fix anything that becomes new
        or is some sort of breaking change from
        previous version(s).
    */


    /*
        # new db-format: poloniex$XRP$USDT$160102--180218$RSI_BULL_BEAR_ADX
        # old db-format: poloniex__BTC__USDT__2017-11-01--2018-02-01__RSI_BULL_BEAR_ADX.db
    */

    require_once '../system/conf.php';
    require_once $conf->dirs->system . 'functions.php';

    #prp($conf);
?>

<!doctype html>
<html lang="en">
<head>
    <title>GAB: Compatibility fix</title>
    <link rel="stylesheet" href="tools.css">
</head>
<body>
    <section>
        <h1>_comatibility_fix</h1>
        <p>Fix stuff</p>
    </section>


    <section>
    <hr>
    <h2>convert old format db > new </h2>
    <p>converts old __ style db's</p>
<?php

    $files = @listfiles( $conf->dirs->results );
    if( !is_array($files) ){
        echo "<h3>Nothing happend</h3><p>Tried fixing old databases, but you had none to fix or these could not be found.</p>";
        exit;
    }

    $fileFix = [];
    foreach( $files as $file )
    {
        if( contains('.db', $file) ) $fileFix[] = $file;
    }

    $files = $fileFix;

    $count = 0;
    $newFiles = [];
    $sep = "$";
    foreach( $files as $file )
    {
        if( !contains('__', $file) || contains('$', $file) ){ continue; } // check for old and new format
        $count++;

        $q = explode('__', $file);
        $dates = explode('--',$q[3]);
        $from = date('ymd', strtotime($dates[0]));
        $to = date('ymd', strtotime($dates[1]));

        $newFiles[] = $q[0] .$sep . $q[1] . $sep . $q[2] . $sep . $from . '--' . $to . $sep . $q[4];

    }

    // check if there were files
    if( $count == 0 ){
        echo "<h3>Nothing to do</h3><p>Could not find files with the old format so did nothing.</p>";
        exit;
    }

    // rename the files
    $res_dir = $conf->dirs->results;
    foreach( $newFiles as $key => $new )
    {
        $old = $files[$key];
        if( rename( $res_dir.$old, $res_dir.$new ) )
        {
            if( $key == 0 ){ echo "<h3>Renamed file(s)</h3>"; }
            echo "<p>>> $old<br><i class='cyan'>>> $new</i></p>";
        }
        else {
            echo "<p>Could not rename $old to $new</p>";
        }
    }

?>

</body>
</html>
