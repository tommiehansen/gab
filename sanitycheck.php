<head>
<title>GAB: Sanity check</title>
<style>
    body { font-family: monospace, mono-space; padding: 2%; line-height: 1.4; font-size: 14px; }
    i { color: red; font-style: normal; }
    b { color: #3c3; }
    i b { color: red; }
    h3 { margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd; }
    h1 + h3 { margin-top: 0; }
</style>
</head>
<h1>Sanity check</h1>
<?php
/*
    SANITY CHECK
    Check stuff
*/


/* set large defaults for PHP */
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit','512M');
set_time_limit(900);

/* functions */
function prp($str){ echo '<pre>'; print_r($str); echo '</pre>'; }
function show_errors($arr){
    foreach( $arr as $msg ){
        echo "<i><b>FAIL</b> $msg</i>";
    }
}

function show_ok(){ echo "<b>OK</b>"; }



/* Extensions test */
$errors = false;
$required = [
    'PDO',
    'pdo_sqlite',
    'curl',
    'mbstring',
    'fileinfo',
    'json',
];

$extensions = get_loaded_extensions();

foreach($required as $v ){
    if( in_array($v, $extensions) ){

    }
    else {
        $hasError = true;
        $errors[] = "PHP extension <b>$v</b> was not loaded";
    }
}

echo "<h3>PHP extensions</h3>";
if( $errors ){
    show_errors($errors);
}
else {
    show_ok();
}



/* Try creating a dir */
echo "<h3>Directory creation test</h3>";
$dir = '_testdir';
mkdir($dir, 0755, true);

if(is_dir($dir)){
    show_ok();
    rmdir($dir);
}
else {
    echo "<i><b>FAIL</b> Could not create directory, some error occured or your user might not have permissions.</i>";
}


/* php version */
echo "<h3>PHP version</h3>";
$errors = false;
if (version_compare(phpversion(), '7.0', '<')) {
    $version = phpversion();
    $errors[] = 'Your php version is old and this might cause problems, the version your running is ' . $version;
}

if( $errors ){
    show_errors($errors);
}
else {
    show_ok();
    echo ' You are running: ' . explode('+', phpversion())[0];
}



/* PHP memory test */
echo "<h3>PHP memory</h3>";
ini_set('memory_limit','512M');
$mem = ini_get('memory_limit');
$errors = false;
if( $mem < 256 ){
    $errors[] = 'Your memory limit is quite low and could not be set via ini_set(). Your memory limit is at ' . $mem;
}

if( $errors ){ show_errors($errors); }
else {
    show_ok();
    echo " Your PHP has $mem available";
}




/* TIME LIMIT TEST */
echo "<h3>PHP max execution time</h3>";
set_time_limit(900); // 15 minutes
$mem = ini_get('max_execution_time');
$errors = false;
if( $mem < 900 ){
    $errors[] = 'Your max execution time for PHP is too low and could not be set. Your max execution time is <b>' . $mem . '</b>';
}

if( $errors ){ show_errors($errors); }
else {
    show_ok();
    echo " Your max execution time is $mem seconds (15 minutes)";
}




/* SQLite test */
echo "<h3>SQLite create database test</h3>";
$errors = false;
$file = 'test_sqlite.db';
$dir = "sqlite:" . $file;
try {
    $db	= new PDO($dir);
    unlink($file);

} catch (\Exception $e) {
    $errors[] = ucfirst($e->getmessage());
}

if( $errors ) { show_errors($errors); }
else { show_ok(); }




/* SQLite insert test */
echo "<h3>SQLite insert test</h3>";
$errors = false;
$file = 'test_sqlite.db';
$dir = "sqlite:" . $file;
try {
    $db	= new PDO($dir);

    $db->beginTransaction();

        # create runs table
        $sql = "
        CREATE TABLE IF NOT EXISTS runs (
        	id TEXT PRIMARY KEY UNIQUE,
        	success TEXT
        )";

        $db->query($sql);

        # insert stuff
        $sql = "
        	INSERT INTO runs (id, success) VALUES (?, ?)
        ";
        $q = $db->prepare($sql);
        $q->execute(['1', 'true']);

    $db->commit();

    unlink($file);

} catch (\Exception $e) {
    $errors[] = ucfirst($e->getmessage());
}

if( $errors ) {
    show_errors($errors);
}
else {
    show_ok();
}



/* dump php info */
echo "<h3>PHP info</h3>";
phpinfo();
