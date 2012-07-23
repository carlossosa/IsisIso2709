#! /usr/bin/php
<?php
@ini_set('auto_detect_line_endings', true);
include 'Isis2709Extract.php';

$f = fopen('sample.iso', 'r');
$var = "";
for ( $i=0; $i<27; $i++) $var .= fgets ($f);

$x = new Isis2709Extract($var);

echo count($x);
foreach ( $x as $k => $v )
{
    echo $k." => ".$v."\r\n";
}
print_r($x);
?>
