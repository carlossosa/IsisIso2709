<?php
/**
 * Clase para extracción de los datos de un Iso2709 exportado por ISIS en un Array.
 * 
 * Esta clase es un extracto simplificado de la herramienta creada por Serhij Dubyk <serhijdubyk at gmail at com).
 *
 * @author Carlos Sosa <carlitin at gmail dot com>
 * @license IsisIso2709RecordExtract is free software; you can redistribute it and/or modify it under the
 *          terms of the GNU General Public License as published by the Free Software
 *          Foundation; either version 2 of the License, or (at your option) any later
 *          version.
 * @package IsisIso2709
 */
class IsisIso2709RecordExtract implements ArrayAccess, Countable,  Iterator {
    // Head Const
    const Record_Length = 0;
    const Subfield_Identifier_Length = 1;
    const Base_Address = 2;
    const Directory_1map = 3;
    const Directory_2map = 4;
    
    // Directory Const
    const DIRECTORY_FIELD_LABEL = 0;
    const DIRECTORY_FIELD_LENGTH = 1;
    const DIRECTORY_FIELD_ADDRESS = 2;

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
        $this->__free();
        $this->fields = array_keys($this->array);
        
        $this->it_pos = 0;
        $this->it_fake_pos = $this->fields[$this->it_pos];
    }
    
    private function __free ()
    {
        unset($this->data, $this->head, $this->directory);
    }


    /**
     * Analisis del Iso2709
     */
    private function loadHeader ()
    {
        $trozo = substr( $this->data, 0, 24);
        $this->head = array();
        $this->head[] = intval( substr( $trozo, 0, 5));
        $this->head[self::Subfield_Identifier_Length] = ( intval(substr( $trozo, 11, 1)) == 0) ? 3 : ( intval(substr( $trozo, 11, 1)) == 0);
        $this->head[self::Base_Address] = intval(substr( $trozo, 12, 5));
        $this->head[self::Directory_1map] = intval( substr( $trozo, 20, 1));
        $this->head[self::Directory_2map] = intval( substr( $trozo, 21, 1));
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
        $first_item = 0;        for ( $i=0; $i < $items_num; $i++)
        {
            $item = substr( $trozo, $i*$item_len, $item_len);
            $this->directory[$i] = array ( self::DIRECTORY_FIELD_LABEL => substr($item, 0, $this->head[self::Subfield_Identifier_Length]),
                                         self::DIRECTORY_FIELD_LENGTH => intval( substr($item, $this->head[self::Subfield_Identifier_Length], $this->head[self::Directory_1map])),   
                                         self::DIRECTORY_FIELD_ADDRESS => substr($item, ($this->head[self::Directory_1map] + $this->head[self::Subfield_Identifier_Length]), $this->head[self::Directory_2map]) );
            
            if ( $i == 0){ $first_item = $this->directory[$i][self::DIRECTORY_FIELD_ADDRESS]; }
            
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
    
    /**
     * Prepara contenido del campo o subcampos
     * 
     * @param string $f
     * @return mixed SubField 
     */
    private function __subFieldsExtract ( $f)
    {                
        if ( preg_match( '/(^(\s|\d|\*)+\^|^\^)/', $f) )
        {    
            $subfield = array();
            $_sfs = explode("^", $f);
            foreach ( $_sfs as $k=>$v) {
                if ( $k != 0 ) $subfield[substr($v,0,1)] = $this->IsisDecode( substr($v, 1));
                else {
                    $subfield['i1'] = substr($v,0,1);
                    $subfield['i2'] = substr($v,1,1);
                }
            }
            return $subfield;
        } else return $this->IsisDecode ($f);
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
            //Def    
            default: return $l;
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
        return $this[$this->it_fake_pos];
    }

    function key() {        
        return $this->it_fake_pos;
    }

    function next() {   
        if ( $this->it_pos < count($this)-1 )
        {
            ++$this->it_pos;
            $this->it_fake_pos = $this->fields[$this->it_pos];
        }
    }

    function valid() {      
        return ( $this->it_pos < count($this)-1 );
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
