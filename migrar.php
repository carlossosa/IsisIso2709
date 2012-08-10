#! /usr/bin/php
<?php
/**
 * Multiple file ISOS Migrate from CDS/ISIS
 * 
 * Este Script fue probado con algo mas de 60000 Registros, que podian contener hasta unos 747 Campos y Subcampos,
 * tomando un tiempo de +/- 15Minutos para construir todas las sentencias SQL;
 */
@ini_set('auto_detect_line_endings', true);
include 'IsisIso2709RecordExtract.php';
include 'IsisIso2709Records.php';

//Linea de INSERT SQL
$SQL = "INSERT INTO bncjm ( `id#CAMPOS#` ) VALUES ( 'NULL#VALUES#' );";

//Archivos
$files = array('53258-56555.ISO', '56608.ISO', '56556-56607.ISO', 'BNJM1.ISO');
$count = 0;
foreach ($files as $file) {
    $x = new IsisIso2709Records($file);
    foreach ($x as $idr => $j) {//TODO LOS REGISTROS
        $campos = "";
        $values = "";
        //$k Identificador de Campos
        //$v Valor del Campo
        foreach ($j as $k => $v) { //TODOS LOS CAMPOS
            $arr = array();
            if (is_array($v)) {
                //$sC identificador de Subcampo
                //$cC Contenido del Subcampo                
                foreach ($v as $sC => $cC) { //TODOS LOS SUBCAMPOS
                    //Omito los identificadore i1 y i2
                    if (    !in_array(trim('v' . intval($k) . strtolower($sC)), array('v' . intval($k) . 'i1', 'v' . intval($k) . 'i2')) &&
                            !in_array(trim('v' . intval($k) . strtolower($sC)), array_keys($arr))
                    ) {
                        if (strlen(trim($cC)) > 0)
                            $arr['v' . intval($k) . $sC] = mysql_escape_string($cC);                        
                    }
                }
            } else {
                if ( !in_array(trim('v' . intval($k)), array_keys($arr)))
                    $arr['v' . intval($k)] = mysql_escape_string($v);
                if (intval($k) == 1)
                    $arr['mfn'] = mysql_escape_string($v);
            }
            if (count($arr) > 0) {
                $campos .= "` , `" . implode("` , `", array_keys($arr));
                $values .= "' , '" . implode("' , '", $arr);
            }
        }
        //Construir la Sentencia de INSERT del SQL
        $temp = $salida = "";
        $temp = preg_replace('/#CAMPOS#/', $campos, $SQL);
        $salida = preg_replace('/#VALUES#/', $values, $temp) . "\r\n";
        //Almacenar la sentencia en el archivo de salida
        file_put_contents('salida.sql', $salida, FILE_APPEND);
        echo "[" . $file . "] => " . $idr . " @ " . $count++ . "\r\n";
    }
}

?>
