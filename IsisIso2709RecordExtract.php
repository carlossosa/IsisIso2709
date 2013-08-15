<?php
/**
 * Clase para extracción de los datos de un Iso2709 exportado por ISIS en un Array.
 * 
 * Esta clase es un extracto simplificado de la herramienta creada por Serhij Dubyk <serhijdubyk at gmail at com).
 * Proporcionando solo la capacidad de leer el contenido de un ISO2709 exportado por ISIS en PHP y la posibilidad de conversión a casi cualquier
 * formato conocido.
 *
 * @author Carlos Sosa <carlitin at gmail dot com>
 * @license IsisIso2709RecordExtract is free software; you can redistribute it and/or modify it under the
 *          terms of the GNU General Public License as published by the Free Software
 *          Foundation; either version 2 of the License, or (at your option) any later
 *          version.
 * @package IsisIso2709
 * @version 0.2
 */
class IsisIso2709RecordExtract implements \ArrayAccess, \Countable,  \Iterator {
    /** Head Const */
    const Record_Length = 0;
    const Subfield_Identifier_Length = 1;
    const Base_Address = 2;
    const Directory_1map = 3;
    const Directory_2map = 4;
    
    /** Directory Const */
    const DIRECTORY_FIELD_LABEL = 0;
    const DIRECTORY_FIELD_LENGTH = 1;
    const DIRECTORY_FIELD_ADDRESS = 2;
    
    /** ISIS CHARS */
    const ISIS_SUBFIELD_DELIMITER = '^';
    const ISIS_REPETIBLE = '%';
    const ISIS_FIELD_DELIMITER = '#';
    
    /** PROPIOS */
    const RECORDEXTRACT_SUBFIELD_IND1 = 'i1';
    const RECORDEXTRACT_SUBFIELD_IND2 = 'i2';

    /** DATA */
    private $data;
    private $head;
    private $directory;
    private $fields;
    private $array;
    
    /** Iterator */
    private $it_pos;
    private $it_fake_pos;
        
    /**
     * @param string $data Iso 2709 Se asume que es un Iso de Isis valido
     * @param boolean $block80 Dividido en lineas de 80 caracteres, por defecto Isis lo exporta asi.
     */
    public function __construct ( $data, $block80 = true) {        

        if ( $block80) $this->data = trim(implode("", explode("\r\n", $data)));
                  else $this->data = trim($data);
                  
        $this->loadHeader();   
        $this->loadDirectory();
        $this->fieldsExtract();
        $this->free();
        $this->loadKeys();
        
        $this->it_pos = 0;
        $this->it_fake_pos = $this->fields[$this->it_pos];
    }
    
    private function loadKeys ()
    {
        $this->fields = array_keys($this->array);
    }
    
    /**
     * Libera de memoria el RAW recibido en el constructor,
     * solo se mantiene en memoria el Array con los datos Parseados. 
     */
    private function free ()
    {
        unset($this->data, $this->head, $this->directory);
    }


    /**
     * Analisis de la cabacera del ISO 2709
     */
    private function loadHeader ()
    {
        // Agarro los primeros 24 Caracteres partenecientes a la cabacera
        $trozo = substr( $this->data, 0, 24);
        
        $this->head = array (   self::Record_Length                 => intval( substr( $trozo, 0, 5)),
                                self::Subfield_Identifier_Length    => ( intval(substr( $trozo, 11, 1)) == 0) ? //Buscar si ISIS coloco el tamano del identificador de registro
                                                                                                                    3 //Generalmente no sucede pero en usamos 3 ya que en UNIMARC los registros son identificados por 3 digitos
                                                                                                              : //Si tenemos la suerte que ISIS coloco el tamano pues usamos el que nos da.
                                                                                                                    intval( substr( $trozo, 11, 1)),
                                self::Base_Address                  => intval(substr( $trozo, 12, 5)), //Posición donde termina la cabecera de registro y empiezan los registros.
                                self::Directory_1map                => intval( substr( $trozo, 20, 1)), 
                                self::Directory_2map                => intval( substr( $trozo, 21, 1))
                            );
        unset($trozo);
    }
    
    /**
     * Analisis del Direcotorio del Iso2709 para la extracción de los 
     * datos de lso Registros almacenados.
     */
    private function loadDirectory ()
    {
        //Agarro el trozo correspondiente al Directorio de los Registros
        $trozo = substr( $this->data, 24, ($this->head[self::Base_Address]-25));        
        $len = strlen($trozo);
        //Tamano de cada elmento del Registro
        $item_len = $this->head[self::Subfield_Identifier_Length] + 
                    $this->head[            self::Directory_1map] + 
                    $this->head[            self::Directory_2map];
        //Cantidad de elementos
        $items_num = $len/$item_len;
        
        $first_item = 0;
        for ( $i=0; $i < $items_num; $i++)
            {
                //Saco el elemento
                $item = substr( $trozo, $i*$item_len, $item_len);
                //Voy agregando los datos de cada registros al directorio        
                $this->directory[$i] = array ( self::DIRECTORY_FIELD_LABEL => substr(                                           $item, 
                                                                                                                                    0, 
                                                                                        $this->head[self::Subfield_Identifier_Length])
                                                ,
                                              self::DIRECTORY_FIELD_LENGTH => intval( substr        ($item, 
                                                                                                $this->head[self::Subfield_Identifier_Length], 
                                                                                                           $this->head[self::Directory_1map]))
                                                ,   
                                              self::DIRECTORY_FIELD_ADDRESS => substr(                                             $item, 
                                                                                                    ($this->head[self::Directory_1map] + 
                                                                                            $this->head[self::Subfield_Identifier_Length]
                                                                                                    ), 
                                                                                                        $this->head[self::Directory_2map]) 
                                                );

                //Correccion de la posicion de cada registro
                if ( $i == 0){ $first_item = $this->directory[$i][self::DIRECTORY_FIELD_ADDRESS]; }
                $this->directory[$i][self::DIRECTORY_FIELD_ADDRESS] = $this->directory[$i][self::DIRECTORY_FIELD_ADDRESS] - $first_item;
            }
        //Libero recursos
        unset( $trozo, 
                $len, 
                $item_len, 
                $items_num, 
                $first_item, 
                $item);
    }
    
    /**
     * Extraccion de los campos.
     */
    private function fieldsExtract() {
        $trozo  = substr($this->data, $this->head[self::Base_Address]);
        $_sF    = NULL;
        foreach ( $this->directory as $field)
        {
            //Detectar duplicidad de campos
            if ( isset($this->array[$field[self::DIRECTORY_FIELD_LABEL]]))
            {
                unset($_sF);
                //Saco el Array() de los Subfields
                $_sF = $this->__subFieldsExtract( rtrim( substr( $trozo, 
                                                            $field[self::DIRECTORY_FIELD_ADDRESS], 
                                                            $field[self::DIRECTORY_FIELD_LENGTH]
                                                        ), self::ISIS_FIELD_DELIMITER)
                                                    );
                
                if ( is_array($_sF) && is_array( $this->array[$field[self::DIRECTORY_FIELD_LABEL]] ))
                    //Los campos repetidos los agrego al Array() pricipal separando el campo agregado por % que en ISIS se emple como separado de campo repetibles
                    foreach ( $_sF as $_R => $_V) 
                        //Recorro los subcampos
                        if ( !in_array( $_R, array( self::RECORDEXTRACT_SUBFIELD_IND1, self::RECORDEXTRACT_SUBFIELD_IND2) ) ) //Los identificadores de registros no son repetibles asi que se omiten
                                //Si el subcampo no exite solo lo asigno
                                if ( !isset($this->array[$field[self::DIRECTORY_FIELD_LABEL]][$_R]) ) 
                                    $this->array[$field[self::DIRECTORY_FIELD_LABEL]][$_R] = $_V;
                                else 
                                    //Caso contrario le agrego el contenido del subcampo secundario
                                    $this->array[$field[self::DIRECTORY_FIELD_LABEL]][$_R] .= self::ISIS_REPETIBLE . $_V;
                else
                    //Pudiera ocurrir el extrano caso de que el secundario si tuviera subcampo aunque el primero no, 
                    //algo realmente raro pero caso que ocurriera me quedo solo con el campo primario
                    if ( !is_array($_sF) && !is_array( $this->array[$field[self::DIRECTORY_FIELD_LABEL]] ))
                        $this->array[$field[self::DIRECTORY_FIELD_LABEL]] .= self::ISIS_REPETIBLE . $_sF;                    
            } else {            
                $this->array[$field[self::DIRECTORY_FIELD_LABEL]] = $this->__subFieldsExtract( rtrim( substr( $trozo, 
                                                                                                        $field[self::DIRECTORY_FIELD_ADDRESS], 
                                                                                                        $field[self::DIRECTORY_FIELD_LENGTH]
                                                                                                    ), self::ISIS_FIELD_DELIMITER)
                                                                                              );
            }
        }
        unset($trozo,
                $_sF);
    }
    
    /**
     * Prepara contenido del campo o subcampos
     * 
     * @param string $f
     * @return mixed SubField 
     */
    private function __subFieldsExtract ( $f)
    {                
        //Detectar si es un subcampo buscando 
        //UPDATE: Mejora detectando campos con subcampos con problemas
        //EJ : m *^a^b^c^d^f^t^x^y^z^2^3*
        /*
         * 
           m *^a^b^c^d^f^t^x^y^z^2^3
	   ,^aF.G.^b^c^d^e^f^g99-454^hC.Pa.
           x**^a^d^e^f^h^i^v^x^z
	   b**^a
	   +**^a^b^c^d^e^f^g^h^3^4
	   0-674-03307-8**^a^b^d^z
	   b**^a^b
	   **^aDirectorio Turístico de Cuba^b^c^d^e1996^f^g^...
	   x**^a1 ej.^b61/99 BN^cBR^d^e^grústica^h^i^j^k^l^mP...
	   x**^a^b^c^d^e^f^g^h^3^4
	   m**^a84-7882-232-1
	   x**^aColección de autores cubanos^e^d^e^f^h^iHisto...
         */
        /**
         * 
         $m = array();
         preg_match_all('#\^(([a-zA-Z0-9])[^\^]+)#i',"x**^a1 ej.^b61/99 BN^cBR^d^e^grústica^h^i^j^k^l", $m);
         var_dump($m);

array(3) {
  [0] =>
  array(4) {
    [0] =>
    string(7) "^a1 ej."
    [1] =>
    string(10) "^b61/99 BN"
    [2] =>
    string(4) "^cBR"
    [3] =>
    string(10) "^grústica"
  }
  [1] =>
  array(4) {
    [0] =>
    string(6) "a1 ej."
    [1] =>
    string(9) "b61/99 BN"
    [2] =>
    string(3) "cBR"
    [3] =>
    string(9) "grústica"
  }
  [2] =>
  array(4) {
    [0] =>
    string(1) "a"
    [1] =>
    string(1) "b"
    [2] =>
    string(1) "c"
    [3] =>
    string(1) "g"
  }
}
---
array(2) {
  [0] =>
  array(4) {
    [0] =>
    string(7) "^a1 ej."
    [1] =>
    string(10) "^b61/99 BN"
    [2] =>
    string(4) "^cBR"
    [3] =>
    string(10) "^grústica"
  }
  [1] =>
  array(4) {
    [0] =>
    string(6) "a1 ej."
    [1] =>
    string(9) "b61/99 BN"
    [2] =>
    string(3) "cBR"
    [3] =>
    string(9) "grústica"
  }
}

         */
        //if ( preg_match( '#(^(\s|\d|\*)+\^|^\^)#', $f) )
        if (strpos($f, self::ISIS_SUBFIELD_DELIMITER) !== false )
        {    
            $matchs = array();
            
            preg_match_all('#\^([a-zA-Z0-9][^\^]+)#i', $f, $matchs);
            
            if ( count($matchs) != 2) 
                return null;
            
            $subfield = array();
            
            if ( strpos($f, self::ISIS_SUBFIELD_DELIMITER) == 1) {
                $subfield[self::RECORDEXTRACT_SUBFIELD_IND1] = substr($f,0,1);
                $subfield[self::RECORDEXTRACT_SUBFIELD_IND2] = substr($f,1,1);
            }
            
            //$_sfs = explode(self::ISIS_SUBFIELD_DELIMITER, $f);
            foreach ( $matchs[1] as $k=>$v) {
                $subfield[strtolower(substr($v,0,1))] = $this->IsisDecode( trim(substr($v, 1)));                
            }
            return $subfield;
        } else return $this->IsisDecode (trim($f));        
    }
    
    /**
     * Traduce los caracteres de Isis
     * @param string $var
     * @return string 
     */
    private function IsisDecode ( $var)
    {
        $new = NULL;
        for ( $i=0; $i<strlen( $var); $i++)
                $new .= ( $this->__decode_letter($var[$i]));        
        return $new;
    }

    /**
     * @todo Completar el codigo de caracteres problematico
     * @param char $l
     * @return char 
     */
    private function __decode_letter( $l)
    {        
        if ( preg_match("#^[a-zA-Z0-9\^\[\]\{\}@_\'\#\$\%\&\*\(\)\~\`\"\+\=\:\.\?\<\>\/-]$#", $l ) )
            return $l;
        //Mas eficiente que usar un Array, ocupa más codigo pero tiene ahorro en recursos cuando se trata analizas grandes cantidades de ISOS
        switch (ord($l)) {                                                         
            //A
            case 131: return 'â'; case 132: return 'ä'; case 142: return 'Ä';
            case 160: return 'á'; case 181: return 'Á'; case 182: return 'Â';
            case 183: return 'À';                
            //E
            case 130: return 'é'; case 136: return 'ê'; case 137: return 'ë';
            case 138: return 'è'; case 144: return 'É'; case 212: return 'È';                
            //I
            case 139: return 'ï'; case 140: return 'î'; case 141: return 'ì';
            case 161: return 'í'; case 214: return 'Í'; case 215: return 'Î';
            case 216: return 'Ï';            
            //O    
            case 147: return 'ô'; case 148: return 'ö'; case 149: return 'ò';                        
            case 224: return 'Ó'; case 226: return 'Ô'; case 227: return 'Ò'; 
            case 162: return 'ó';                
            //U
            case 150: return 'û'; case 151: return 'ù'; case 152: return 'ù';                
            case 154: return 'Ü'; case 233: return 'Ú'; case 234: return 'Û';    
            case 235: return 'Ù'; case 163: return 'ú'; case 129: return 'ü';                
            //RESTO    
            case 128: return 'Ç'; case 135: return 'ç'; case 164: return 'ñ';
            case 165: return 'Ñ'; case 169: return '®'; case 194: return '¿';    
	    case 173: return '¡'; case 168: return '¿';  
            //Def    
            default:                 
                return ' ';
        }
    }
    
    /** IMPLEMENTS */
    /** 
     * Countable     *
     * @return int
     */
    public function count() 
     {
        return count($this->array);
     }
     
    /**
      * Interface Iterator 
      */
     function rewind() {
        $this->it_pos = 0;
        $this->it_fake_pos = $this->fields[$this->it_pos];
    }

    function current() {       
        return $this[$this->key()];
    }

    function key() {        
        return $this->it_fake_pos;
    }

    function next() {   
        if ( $this->it_pos < count($this) )
        {
 	    ++$this->it_pos;
            if ( $this->valid())
                $this->it_fake_pos = $this->fields[$this->it_pos];
        }
    }

    function valid() {      
        return ( $this->it_pos < count($this) );
    }
    
    /**
     * ArrayAccess 
     */
    public function offsetSet($offset,  $value) {                           
                throw new ErrorException('Arreglo de solo lectura.');
    }
    
    public function offsetExists($offset) {
       return ( in_array($offset, $this->fields) );
    }
    
    public function offsetUnset($offset) {       
                throw new ErrorException('Arreglo de solo lectura.');
    }
    
    public function offsetGet($offset) {
        if ( $this->offsetExists($offset) )
            return $this->array[$offset];
        else 
            throw new ErrorException('Campo no válido.');
    }            
}
