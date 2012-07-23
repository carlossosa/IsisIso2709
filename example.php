<?php
@ini_set('auto_detect_line_endings', true);
include 'Isis2709Extract.php';

$f = fopen('docs/56556-56607.ISO', 'r');
$var = "";

for ( $i=0; $i<1000; $i++) $var .= fgets ($f);

$x = new Isis2709Extract($var);
print_r($x);

?>
