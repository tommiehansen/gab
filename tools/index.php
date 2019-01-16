<?php
    require_once '../system/conf.php';
    require_once $conf->dirs->system . 'functions.php';

    $links = [
        'Possibility check' => 'possibility_check.php|Check number of possibilities something gives you.',
        'Compatibility Fix' => 'compatibility_fix.php|New version? Try fixing old data or old just old crap',
        'Sanity Check' => 'sanitycheck.php|Debug/check your server configuration and find out if there are problems',
        'Rapid Dataset Importer' => '#|Quickly download datasets for Gekko',
        'Database Converter' => '#|Convert databases from SQLite > MySQL',
    ];
?>
<!doctype html>
<html lang="en">
<head>
    <title>GAB: Tools</title>
    <link rel="stylesheet" href="tools.css">
</head>
<body>
    <section>
    <h1>_tools</h1>
    <p>Select a tool to run</p>
    <div class="grid">
        <?php
        $chunks = array_chunk($links, 2, true);
        $i = 0;
        foreach( $chunks as $chunk ){
            echo "<div class='grid--row'>";
            $tpl = "
                <a class='box grid--item' href='_link_' tabindex='_index_'>
                    <i class='pink'>_name_</i>
                    <u class='yellow'>_desc_</u>
                </a>
            ";

            $out = '';
            foreach( $chunk as $name => $link )
            {
                $i++;
                $cur = explode('|', $link);
                $href = $cur[0];
                $desc = $cur[1];

                $str = str_replace('_link_', $href, $tpl);
                $str = str_replace('_name_', $name, $str);
                $str = str_replace('_desc_', $desc, $str);
                $str = str_replace('_index_', $i, $str);
                $out .= $str;
            }
            echo $out;
            echo "</div>";

            } // foreach $chunks
        ?>

    </div>
    </section>


    <section class="donate">
        <h2>_donate</h2>
        <p>
            btc: <i>15cZUi7VvmCQJLbPXYYWChHF3JpsBaYDtH</i><br>
            eth: <i>0xe03c5eb9DF93360e3Bcfcd13012B7DeebbED6923</i>
        </p>
    </section>




<script src="<?php echo $conf->urls->assets ?>jquery-3.3.1.min.js"></script>
<script>
    $(document).on('click', '.box', function(e){

        if( this.getAttribute('href')  == '#' )
        {
            e.preventDefault();
            alert("Not available, developer too busy\nMaybe consider donating?");
            this.blur();
            this.classList.add('off');
            $('.donate').addClass('on');
        }
    })
</script>
</body>
</html>
