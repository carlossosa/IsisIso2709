<?php
/**
 * Class IsisIso2709Records se encarga de ir rastreando los registros en un archivo ISO exportado
 * por CDS/Isis. Dando la posibilidad de acceder a los mismo como si de un Array se tratase.
 *
 * @author Carlos Sosa 
 * @license IsisIso2709Records is free software; you can redistribute it and/or modify it under the
 *          terms of the GNU General Public License as published by the Free Software
 *          Foundation; either version 2 of the License, or (at your option) any later
 *          version.
 * @package IsisIso2709
 */
class IsisIso2709Records implements ArrayAccess, Countable,  Iterator {
    /**
     * RECORD_SEPARATOR : separador de registros en CDS/Isis 
     */
    const RECORD_SEPARATOR = '##';
    /**
     * @ignore 
     */
    const RECORD_FIELD_SEPARATOR = '#';
    
    /**
     * Recurso con el Fichero abierto.
     * @var Resource 
     */
    private $resource;
    /**
     * Micro indice con las ubicaciones de los Registros en el Iso
     * @var type 
     */
    private $directory;
    
    /**
     * Iterartor contador
     * @var type 
     */
    private $it_pos;
    
    /**
     * 
     * @param string $_path URI al archivo ISO
     * @throws ErrorException Al ocurrir un error al intentar abrir el fichero.
     */
    public function __construct( $_path) {
        if ( file_exists($_path) || is_readable($_path)) //Check de accesibilidad al archivo.
        {
          $this->resource = fopen($_path, 'r'); // Abro el archivo
          if ( !is_resource($this->resource))
              throw new ErrorException('Error al abrir el archivo.');
        } else            
            throw new ErrorException('Error al intentar acceder al archivo.'); // Lanzada un Excepcion de acceso
        
        // Incializo el Indice
        $this->directory = array();
        $this->preScan();   
                        
        // Iterator        
        $this->it_pos = 0;
    }

    /**
     * Crea el indice con las ubicaciones de los registros en el Iso 
     */
    private function preScan ()
    {
        $pos = 0;
        while ( !feof($this->resource))  
        {
            if ( !isset($this->directory[$pos][0]) )
            {
                $this->directory[$pos][0] = 0;
            }
            
            $line = fgets($this->resource);
            
            if (preg_match('/'.self::RECORD_SEPARATOR.'/', $line))
            {
                $pos++;
                $this->directory[$pos][0] = ftell($this->resource);
                $this->directory[$pos-1][1] = $this->directory[$pos][0] - $this->directory[$pos-1][0];
                continue;
            }                        
        }
        
        if ( !isset($this->directory[$pos][1]) ) unset($this->directory[$pos]);        
        fseek($this->resource, 0);
    }
    
    /** IMPLEMENTS */
    /** 
     * Countable     *
     * @return int
     */
    public function count() 
     {
        return count($this->directory);
     }
     
    /**
      * Interface Iterator 
      */
     function rewind() {
        $this->it_pos = 0;
    }

    function current() {       
        return $this[$this->it_pos];
    }

    function key() {        
        return $this->it_pos;
    }

    function next() {   
        if ( $this->it_pos < count($this) )
        {
            ++$this->it_pos;            
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
       return ( $offset < count($this) );
    }
    
    public function offsetUnset($offset) {       
                throw new ErrorException('Arreglo de solo lectura.');
    }
    
    public function offsetGet($offset) {
        if ( $this->offsetExists($offset) )
        {
            if ( ($offset-1) == $this->it_pos )
            {
                return new IsisIso2709RecordExtract(stream_get_line($this->resource, $this->directory[$this->it_pos][1]));
            } else {
                fseek($this->resource, $this->directory[$this->it_pos][0]);
                return new IsisIso2709RecordExtract(stream_get_line($this->resource, $this->directory[$this->it_pos][1]));
            }
        }
        else 
            throw new ErrorException('Campo no válido.');
    }
}