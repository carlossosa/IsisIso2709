#! /usr/bin/php
<?php
ini_set('auto_detect_line_endings', true);

function ReyMatador($fichero){
    $registro = simplexml_load_string($fichero);
    $query = "INSERT IN TO bncjm SET";
       $query.= " v1 = '".$registro->control."'";
       $v1 = $registro->control;
       foreach($registro->field as $campo):
            if ($campo['tag']=='010') { $campo['tag']='10'; }
            if ($campo['tag']=='020') { $campo['tag']='20'; }
            $first="v".$campo['tag'];
                foreach($campo->subfield as $subcampo):
               $query.= ", ".$first.$subcampo['code']." = '".$subcampo[0]."'";
                endforeach;
      endforeach;
    echo $query;
    //return mysql_query($query, $dbstr);
  }

/**
 * Objeto para recorrer un Xml desde Isis generado por Migration_tools de Koha.
 * Se aconseja establecer ini_set('auto_detect_line_endings', true);
 */
class IsisXml {
    private $path;
    private $flujo;
    private $regcount;

    /**
     * Constructora
     * @param string $path URI del Xml
     */
    public function __construct() {
        if (func_num_args() > 0 )
        {
            $this->setPath( func_get_arg(0));
        }

        $this->regcount = 0;
    }

    public function setPath ( $path)
    {
        $this->path = $path;
    }

    public function open()
    {
        if ( $this->path != "" && file_exists($this->path))
        {
            try {
                $this->flujo = fopen($this->path, "r");
            } catch (ErrorException $exc) {
                echo "Error al intentar abrir el archivo.";
            }
        } else
            throw new ErrorException('No se ha definido ningun archivo para abrir.');
    }

    public function getRegistro()
    {
        $trozo = false;
        while ( !feof($this->flujo))
        {
            $line = utf8_encode(trim(fgets($this->flujo)));
                if (preg_match('/<record number="(\d)+">/', $line))
                {
                    $trozo = $line."\r\n";
                } elseif (preg_match('/<\/record>/', $line)) {
                    $trozo .= $line."\r\n";
                    break;
                } else {
                    if ( $trozo !== false)
                        $trozo .= $line."\r\n";
                }
        }
        return $trozo;
    }
}

$x = new IsisXml('56556-56607.ISO.xml');
$x->open();
//
//while ( $trozo = $x->getRegistro())
//{
//    echo "\r\n------------------------------------------------------------------------------------\r\n";
//    echo $trozo;
//    echo "\r\n------------------------------------------------------------------------------------\r\n";
//}

$var = <<<EOF
<record number="52">
<leader>021020000000008890004500</leader>
<control tag="001">IJA-52</control>
<field tag="010" i1=" " i2=" ">
<subfield code="a">84-86770-76-9</subfield>
</field>
<field tag="020" i1=" " i2=" ">
</field>
<field tag="101" i1="*" i2=" ">
<subfield code="a">spa</subfield>
</field>
<field tag="102" i1=" " i2=" ">
<subfield code="a">ES</subfield>
<subfield code="b">Valladolid</subfield>
</field>
<field tag="200" i1="1" i2=" ">
<subfield code="a">Objetos perdidos</subfield>
<subfield code="e">antologÂ¡a de cuentos</subfield>
<subfield code="f">JosÂ JimÂnez Lozano</subfield>
<subfield code="g">introd. y sel. Francisco Javier Higuero</subfield>
</field>
<field tag="205" i1=" " i2=" ">
<subfield code="a">1. ed.</subfield>
</field>
<field tag="210" i1=" " i2=" ">
<subfield code="a">Valladolid</subfield>
<subfield code="c">Ambito</subfield>
</field>
<field tag="210">
<subfield code="c">Ayuntamiento de Valladolid. FundaciÂ¢n Municipal de Cultura</subfield>
<subfield code="d">1993</subfield>
</field>
<field tag="215" i1=" " i2=" ">
<subfield code="a">147 p.</subfield>
</field>
<field tag="225" i1="1" i2=" ">
<subfield code="a">Serie Letras</subfield>
</field>
<field tag="300" i1=" " i2=" ">
</field>
<field tag="316" i1=" " i2=" ">
</field>
<field tag="320" i1=" " i2=" ">
</field>
<field tag="323" i1=" " i2=" ">
</field>
<field tag="324" i1=" " i2=" ">
</field>
<field tag="325" i1=" " i2=" ">
</field>
<field tag="327" i1=" " i2=" ">
</field>
<field tag="328" i1=" " i2=" ">
</field>
<field tag="336" i1=" " i2=" ">
</field>
<field tag="337" i1=" " i2=" ">
</field>
<field tag="423" i1=" " i2="1">
</field>
<field tag="482" i1=" " i2="1">
</field>
<field tag="500" i1="1" i2="*">
</field>
<field tag="501" i1="*" i2=" ">
</field>
<field tag="503" i1="1" i2=" ">
</field>
<field tag="510" i1="1" i2=" ">
</field>
<field tag="512" i1="*" i2="*">
</field>
<field tag="513" i1="*" i2="*">
</field>
<field tag="514" i1="*" i2="*">
</field>
<field tag="515" i1="*" i2="*">
</field>
<field tag="516" i1="*" i2="*">
</field>
<field tag="517" i1="*" i2="*">
</field>
<field tag="532" i1="*" i2="*">
</field>
<field tag="600" i1=" " i2="*">
</field>
<field tag="601" i1=" " i2="*">
</field>
<field tag="602" i1=" " i2=" ">
</field>
<field tag="605" i1=" " i2=" ">
</field>
<field tag="606" i1=" " i2="*">
<subfield code="a">CUENTO ESPAÂ¥OL</subfield>
</field>
<field tag="607" i1=" " i2=" ">
</field>
<field tag="608" i1=" " i2=" ">
</field>
<field tag="676" i1=" " i2=" ">
<subfield code="a">863.6</subfield>
<subfield code="v">15</subfield>
<subfield code="z">spa</subfield>
</field>
<field tag="700" i1=" " i2="1">
<subfield code="a">JimÂnez Lozano</subfield>
<subfield code="b">JosÂ</subfield>
<subfield code="f">1930-</subfield>
</field>
<field tag="701" i1=" " i2="1">
</field>
<field tag="702" i1=" " i2="1">
<subfield code="a">Higuero</subfield>
<subfield code="b">Francisco Javier</subfield>
</field>
<field tag="710" i1="*" i2="*">
</field>
<field tag="711" i1="*" i2="*">
</field>
<field tag="712" i1="*" i2="*">
</field>
<field tag="720" i1=" " i2=" ">
</field>
<field tag="721" i1=" " i2=" ">
</field>
<field tag="801" i1=" " i2="*">
<subfield code="a">CU</subfield>
<subfield code="b">BNJM</subfield>
<subfield code="g">NC-</subfield>
</field>
<field tag="830" i1=" " i2=" ">
<subfield code="a">BNJM</subfield>
</field>
<field tag="920">
</field>
<field tag="921">
</field>
<field tag="922">
</field>
<field tag="923">
</field>
<field tag="924">
</field>
<field tag="925">
</field>
<field tag="928">
<subfield code="a">F.G.</subfield>
<subfield code="g">08-219</subfield>
<subfield code="h">E</subfield>
</field>
<field tag="929">
<subfield code="a">1 ej.</subfield>
<subfield code="c">Donativo</subfield>
<subfield code="g">rÂ£stica</subfield>
<subfield code="o">644032</subfield>
</field>
<field tag="930">
<subfield code="a">ig/acl-ene08</subfield>
<subfield code="b">20080121</subfield>
<subfield code="c">ec</subfield>
<subfield code="d">20080121</subfield>
</field>
<field tag="931">
</field>
<field tag="932">
</field>
<field tag="933">
</field>
<field tag="934">
</field>
<field tag="935">
</field>
<field tag="936">
</field>
<field tag="937">
</field>
<field tag="938">
</field>
<field tag="939">
</field>
<field tag="998">
</field>
<field tag="999" i1="*" i2="*">
<subfield code="a">AP</subfield>
</field>
</record>
EOF;

echo ReyMatador($var);

?>