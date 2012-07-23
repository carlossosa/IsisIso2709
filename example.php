#! /usr/bin/php
<?php
@ini_set('auto_detect_line_endings', true);
include 'Isis2709Extract.php';
include 'IsisIso2709Records.php';

$x = new IsisIso2709Records( 'sample.iso');

foreach (  $x as $v)
    print_r( $v);

?>
