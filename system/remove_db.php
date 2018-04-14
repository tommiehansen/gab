<?php
/* delete database */
require_once 'conf.php';
require_once $conf->dirs->system . 'functions.php';

# kill if bad id
if( !_G('id') ) die('E.');
$id = _G('id');
if( !$id && !contains('.db') ) die('E.');

$file = $id;

# get / check db type
$dbc = $conf->db;
$dbc->host == 'sqlite' ? $isMySQL = false : $isMySQL = true;

/* MySQL */
if( $isMySQL )
{
    # remove .db extension
    $file = str_replace('.db','', $file);
    prp( $file );

    $con = "mysql:host=".$dbc->host.";charset=utf8mb4";
    $db = new PDO($con, $dbc->user, $dbc->pass) or die("Error connecting to MySQL");

    $sql = "
        DROP DATABASE `$file`
    ";

    $db->query($sql);
    $db=null;
    exit;
}
/* SQLite */
else
{
    # replace odd chars
    $file = str_replace('%','', $file);
    $file = str_replace("'",'', $file);
    $file = str_replace('<','', $file);
    $file = str_replace('&','', $file);
    $file = str_replace(';','', $file);

    # set src
    $result_dir = $conf->dirs->results;
    $db_src = $result_dir . $file;

    # not allowed to delete dirs
    if( is_dir( $db_src) ) exit;

    # remove
    unlink($db_src);
    $db=null;
    exit;
}
