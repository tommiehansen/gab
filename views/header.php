<?php
    if( !$page ){
        $page = '';
        $page_title = '';
    }

    $page_title = 'GAB: ' . $page_title;
    $asset_version = date('ymd');

    // get day/night
    $now = date('G');
    $now > 7 && $now < 20 ? $bodyClass = 'day' : $bodyClass = 'night';
?>

<!doctype html>
<html lang="en-us">
<head>
	<title><?= $page_title ?></title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=1">
	<link href="<?php echo $conf->urls->assets ?>css/styles.css?v=<?php echo $asset_version; ?>" rel="stylesheet">
</head>
<body class="<?php echo $bodyClass; ?>">
<?php include 'nav.php' ?>
