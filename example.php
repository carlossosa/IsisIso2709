#! /usr/bin/php
<?php
@ini_set('auto_detect_line_endings', true);
include 'IsisIso2709RecordExtract.php';
include 'IsisIso2709Records.php';

$x = new IsisIso2709Records( 'sample.iso');

foreach (  $x as $j)
foreach (  $j as $k => $v)
{
    if (is_array($v)){
        echo $k;
        print_r($v);
    } else {
        echo $k." => ".$v."\r\n";
    }
}

?>
