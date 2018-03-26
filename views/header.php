<?php
    if( !$page ){
        $page = '';
        $page_title = '';
    }

    $page_title = 'GAB: ' . $page_title;
?>

<!doctype html>
<html lang="en-us">
<head>
	<title><?= $page_title ?></title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=1">
	<link href="<?php echo $conf->urls->assets ?>css/styles.css" rel="stylesheet">
</head>
<body>
<?php include 'nav.php' ?>
