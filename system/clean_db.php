<?php
/* clear cache with .cache extension */

require_once 'functions.php';

if( _G('id') )
{
    require_once 'conf.php';

    $file = _G('id');
    $result_dir = $conf->dirs->results;
    $db_src = $result_dir . $file;


    # connect
    $db = new PDO('sqlite:' .  $db_src) or die('Error @ db');
    $db->beginTransaction();

        // delete blobs
        $truncate = "
        DELETE FROM blobs
        WHERE id NOT IN (SELECT id FROM results ORDER BY strategy_profit DESC LIMIT 500);
        ";

        $db->query($truncate);

        // delete from results
        $truncate = "
            DELETE FROM results
            WHERE id NOT IN (SELECT id FROM results ORDER BY strategy_profit DESC LIMIT 500);
        ";

        $db->query($truncate);


    $db->commit();

    // vacuum
    $db->exec('VACUUM;');

    # get new filesize
    clearstatcache();
    $fs = filesize($db_src);
    $fs_mb = sprintf("%4.2f MB", $fs/1048576);
    echo $fs_mb;
}

else {
    echo 'E';
    die();
}
