<?php
/* clear cache with .cache extension */

require_once 'functions.php';

# kill if bad id
if( !_G('id') ) die('E.');

require_once 'conf.php';

# get name
$file = _G('id');

# get / check db type
$dbc = $conf->db;
$dbc->host == 'sqlite' ? $isMySQL = false : $isMySQL = true;

/* MySQL */
if( $isMySQL )
{
    $file = str_replace('.db','', $file); // remove .db extension

    $con = "mysql:host=".$dbc->host.";dbname=$file;charset=utf8mb4";
    $db = new PDO($con, $dbc->user, $dbc->pass) or die("Error connecting to MySQL");

    # truncate
    $truncate = "
        DELETE FROM blobs

        WHERE id NOT IN (
        	SELECT id FROM (
        		SELECT id FROM results
        		ORDER BY strategy_profit DESC
        		LIMIT 500
        	) foo
        )
        AND id NOT IN (
        	SELECT id FROM (
        		SELECT id FROM results
        		ORDER BY sharpe DESC
        		LIMIT 500
        	) foo
        );

        DELETE FROM results

        WHERE id NOT IN (
        	SELECT id FROM (
        		SELECT id FROM results
        		ORDER BY strategy_profit DESC
        		LIMIT 500
        	) foo
        )
        AND id NOT IN (
        	SELECT id FROM (
        		SELECT id FROM results
        		ORDER BY sharpe DESC
        		LIMIT 500
        	) foo
        );

        OPTIMIZE TABLE blobs;
        OPTIMIZE TABLE results;
        OPTIMIZE TABLE runs;
    ";

    $db->query($truncate);

    # get new file size
    $db_size = "
        SELECT
            a.table_schema as name,
            SUM(round(((a.data_length + a.index_length) / 1024 / 1024)))  AS 'size_mb',
            b.last_update as last_change

        FROM information_schema.tables a

        LEFT JOIN (
            SELECT
                last_update,
                database_name
            FROM mysql.innodb_table_stats
            GROUP BY database_name
        ) as b

        ON a.table_schema = b.database_name

        WHERE a.engine = 'InnoDB'
        AND  a.table_schema = '$file'

        GROUP BY a.table_schema
        ORDER BY b.last_update DESC
    ";

    $db_size = $db->query($db_size);

    $db_size = $db_size->fetchAll(PDO::FETCH_ASSOC)[0];
    $fs_mb = $db_size['size_mb'] . ' MB';

} // $isMySQL

/* SQLite */
else {

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

} // else

# output
echo $fs_mb;
