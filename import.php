<?php
//Capaz de Procesar 60000 sentencias indivuales
//en algo menor de 30 segundos
$count = 0;
$con = mysql_connect('localhost','root','');
mysql_select_db('bnjm_snbp');
mysql_query("SET NAMES 'utf8'");
@ini_set('auto_detect_line_endings', true);
$r = fopen('salida.sql', 'r');
// @todo: Reemplazar feof por stream_get_line, que emplea almenos un 50% menos de tiempo para leer del archivo
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
