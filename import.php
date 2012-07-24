<?php

$count = 0;
$con = mysql_connect('localhost','root','x34lae2010');
mysql_select_db('bnjm_snbp');
mysql_query("SET NAMES 'utf8'");
@ini_set('auto_detect_line_endings', true);
$r = fopen('salida.sql', 'r');
while ( !feof($r))
{
    $count++;
    $query = fgets($r);
    mysql_query($query);
    if ( mysql_errno() != 0)
    {
        $e = "[".$count."] => Error : ".mysql_error();
        file_put_contents('error.log', $e."\r\n\t[SQL] => ".$query );
        echo $e."\r\n";
    } else {
        $e = "[".$count."] => OK : Query procesada correctamente";
    }
}
?>
