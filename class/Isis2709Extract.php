<?php
/**
 * Clase para extracción de los datos de un Iso2709 exportado por ISIS en un Array.
 * 
 * Esta clase es un extracto simplificado de la herramienta creada por Serhij Dubyk <serhijdubyk at gmail at com).
 *
 * @author Carlos Sosa <carlitin at gmail dot com>
 * @license Isis2709Extract is free software; you can redistribute it and/or modify it under the
 *          terms of the GNU General Public License as published by the Free Software
 *          Foundation; either version 2 of the License, or (at your option) any later
 *          version.
 * @todo Arreglar la conversion de caracteres.
 */
class Isis2709Extract implements ArrayAccess, Countable,  Iterator {
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
        
    /**
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
        
        $this->fields = array_keys($this->array);
        
        $this->it_pos = 0;
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
                if ( $k != 0 ) $subfield[substr($v,0,1)] = substr($v, 1);
                else {
                    $subfield['i1'] = substr($v,0,1);
                    $subfield['i2'] = substr($v,1,1);
                }
            }
            return $subfield;
        } else return $f;
    }
    
    /** IMPLEMENTS */
    /** 
     * Countable     *
     * @return int
     */
    public function count() 
     {
        count($this->array);
     }
     
    /**
      * Interface Iterator 
      */
     function rewind() {
        $this->it_pos = 0;
    }

    function current() {       
        return $this[$this->fields[$this->it_pos]];
    }

    function key() {        
        return $this->it_pos;
    }

    function next() {   
        if ( $this->it_pos < count($this) )
            ++$this->it_pos;
    }

    function valid() {      
        return ( $this->key() < count($this) );
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
