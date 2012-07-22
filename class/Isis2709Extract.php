<?php
/**
 * Clase para extracción de los datos de un Iso2709 exportado por ISIS en un array
 *
 * @author Carlos Sosa <carlitin at gmail dot com>
 */
class Isis2709Extract {
    // Head Const
    const Record_Length = 0;
//    const Record_Status = 1;
//    const Document_Type = 2;
//    const Bibliographic_Level = 3;
//    const Hierarchical_Level = 4;
//    const Indicator_Length = 5;
    const Subfield_Identifier_Length = 6;
    const Base_Address = 7;
//    const Encoding_Level = 8;
//    const Record_Update = 9;
    const Directory_1map = 10;
    const Directory_2map = 11;
//    const Directory_3map = 12;
//    const Undefined_Charset = 13;
//    const Undefined_19 = 14;
//    const Undefined_23 = 15;     
    
    // Directory Const
    const DIRECTORY_FIELD_LABEL = 0;
    const DIRECTORY_FIELD_LENGTH = 1;
    const DIRECTORY_FIELD_ADDRESS = 3;

    private $data;
    private $head;
    private $directory;
    private $array;
    
    
    /**
     *
     * @param string $data Iso 2709 Se asume que es un Iso de Isis valido
     * @param boolean $block80 Dividido en lineas de 80 caracteres, por defecto Isis lo exporta asi.
     */
    public function __construct ( $data, $block80 = true) {        
        $data = utf8_decode($data);
        if ( $block80) $this->data = implode("", explode("\r\n", $data));
                  else $this->data = $data;
                  
        $this->loadHeader();   
        $this->loadDirectory();
        $this->fieldsExtract();
        print_r($this->array);
    }
        
    /**
     *Analisis del Iso 
     */
    private function loadHeader ()
    {
        $trozo = substr( $this->data, 0, 24);
        $this->head = array();
        $this->head[] = intval( substr( $trozo, 0, 5));
//        $this->head[] = substr( $trozo, 5, 1);
//        $this->head[] = substr( $trozo, 6, 1);
//        $this->head[] = substr( $trozo, 7, 1);
//        $this->head[] = substr( $trozo, 8, 1);
//        $this->head[] = substr( $trozo, 10, 1);
        $this->head[self::Subfield_Identifier_Length] = ( intval(substr( $trozo, 11, 1)) == 0) ? 3 : ( intval(substr( $trozo, 11, 1)) == 0);
        $this->head[self::Base_Address] = intval(substr( $trozo, 12, 5));
//        $this->head[] = substr( $trozo, 17, 1);
//        $this->head[] = substr( $trozo, 18, 1);
        $this->head[self::Directory_1map] = intval( substr( $trozo, 20, 1));
        $this->head[self::Directory_2map] = intval( substr( $trozo, 21, 1));
//        $this->head[] = intval( substr( $trozo, 22, 1));
//        $this->head[] = substr( $trozo, 9, 1);
//        $this->head[] = substr( $trozo, 19, 1);
//        $this->head[] = substr( $trozo, 23, 1);
    }
    
    /**
     * Analisis del Directorio. 
     */
    private function loadDirectory ()
    {
        $trozo = substr( $this->data, 24, ($this->head[self::Base_Address]-25));
        $len = strlen($trozo);
        $item_len = $this->head[self::Subfield_Identifier_Length] + $this->head[self::Directory_1map] + $this->head[self::Directory_2map];
        $items_num = $len/$item_len;
        $first_item = 0;
        for ( $i=0; $i < $items_num; $i++)
        {
            $item = substr( $trozo, $i*$item_len, $item_len);
            $this->directory[$i] = array ( self::DIRECTORY_FIELD_LABEL => substr($item, 0, $this->head[self::Subfield_Identifier_Length]),
                                         self::DIRECTORY_FIELD_LENGTH => intval( substr($item, $this->head[self::Subfield_Identifier_Length], $this->head[self::Directory_1map])),   
                                         self::DIRECTORY_FIELD_ADDRESS => substr($item, ($this->head[self::Directory_1map] + $this->head[self::Subfield_Identifier_Length]), $this->head[self::Directory_2map]) );
            
            if ( $i == 0)
            {
                $first_item = $this->directory[$i][self::DIRECTORY_FIELD_ADDRESS];
            }
            
            $this->directory[$i][self::DIRECTORY_FIELD_ADDRESS] = $this->directory[$i][self::DIRECTORY_FIELD_ADDRESS] - $first_item;
        }
    }
    
    /**
     * Extraccion de los campos.
     */
    private function fieldsExtract() {
        $trozo = substr($this->data, $this->head[self::Base_Address]);
        foreach ( $this->directory as $field)
        {
            $this->array[$field[self::DIRECTORY_FIELD_LABEL]] = $this->__subFieldsExtract( rtrim( substr( $trozo, 
                                                                                                    $field[self::DIRECTORY_FIELD_ADDRESS], 
                                                                                                    $field[self::DIRECTORY_FIELD_LENGTH]
                                                                                                   ), '#')
                                                                                          );
        }
    }
    
    private function __subFieldsExtract ( $f)
    {                
        if ( preg_match( '/(^(\s|\d|\*)+\^|^\^)/', $f) )
        {    
            $subfield = array();
            $_sfs = explode("^", $f);
            foreach ( $_sfs as $k=>$v) {
                if ( $k != 0 ) $subfield[substr($v,0,1)] = substr($v, 1);
                else {
                    $subfield['i1'] = substr($v,0,1);
                    $subfield['i2'] = substr($v,1,1);
                }
            }
            return $subfield;
        } else return $f;
    }
}

$var = <<<ISOEOF
02110000000000925000450099900070000070000440000771000230005172000110007450000330
00855030027001182000058001455010023002032050013002269980002002392100036002412150
01700277225004100294701002300335702002300358711002300381712002300404721001100427
51000170043851200070045551300110046251400070047351500050048051600070048551700070
04925320007004993000005005063160007005113200005005183230005005233240005005283250
00500533327009600538950001900634950000900653950001800662950002700680328000500707
33600050071233700050071742300050072248200050072760000250073260100290075760200170
07866050029008036060030008326070015008626080017008776760019008949200002009139210
00200915922000200917923000200919924000200921925000200923931000900925932000200934
93300050093693400050094193500040094693600020095093700020095293900040095493800030
09580010006009610100024009670200009009911010026010001020015010268010020010418300
00901061928002801070929004101098930004501139#**^aAP# 1^aRodr�guez^bClaudio^c^d^f
1934-^g^p^3^4^9#**^a^b^c^d^e^f^g^h^3^4#  ^a^f^3^4#1*^a^b^h^i^k^l^m^n^q^v^x^y^z^2
^3#1 ^a^b^j^d^e^f^h^i^k^l^m^n#1 ^aDesde mis poemas^b^c^d^e^fClaudio Rodr�guez^g^
h^i^v^z#* ^a^b^e^k^m^x^y^z^2^3#  ^a^b^d^f^g#l#  ^aMadrid^b^cC�tedra^d1994^e^f^g^
h#  ^a260 p.^c^d^e#1 ^aLetras hisp�nicas^d^e^f^h^i^v175^x^z# 1^a^b^c^d^f^g^p^3^4
^9# 1^a^b^c^d^f^g^p^3^4^9#**^a^b^c^d^e^f^g^h^3^4#**^a^b^c^d^e^f^g^h^3^4#  ^a^f^3
^4#1 ^a^e^h^i^j^n^z#**^a^e#**^a^e^h^i#**^a^e#**^a#**^a^e#**^a^e#**^a^z#  ^a#  ^a
^5#  ^a#  ^a#  ^a#  ^a#  ^aContiene: Don de la ebriedad -- Conjuros -- Alianza y
 condena -- El Vuelo de la celebraci�n#Don de la ebriedad#Conjuros#Alianza y con
dena#El Vuelo de la celebraci�n#  ^a#  ^a#  ^a# 1^1# 1^1# *^a^b^c^d^f^t^x^y^z^2^
3# *^a^b^c^d^e^f^g^h^x^y^z^2^3#  ^a^f^x^y^z^2^3#  ^a^h^i^k^l^m^n^q^x^y^z^2^3# *^
aPOESIA ESPA�OLA^x^y^z^2^3#  ^a^x^y^z^2^3#  ^a^x^y^z^2^3^5#  ^a861.6^v15^zspa#c#
a#m# # #i#20071127#d#1994#    #km #y#0#spa#ba#IJA-1#  ^a84-376-0388-9^b^d^z#  ^a
^b^z#* ^aspa^b^c^d^e^f^g^h^i^j#  ^aES^bMadrid# *^aCU^bBNJM^c^gNC-#  ^aBNJM#^aF.G
.^b^c^d^e^f^g08-119^hE#^a1 ej.^cDonativo^d^e^gr�stica^h^o643878#^aig/acl-ene08^b
20071127^cec^d20080127^e^f^g##
ISOEOF;

$x = new Isis2709Extract($var);
?>
