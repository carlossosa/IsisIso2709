<?php
/**
 * Class IsisIso2709Records se encarga de ir rastreando los registros en un archivo ISO exportado
 * por CDS/Isis.
 *
 * @author Carlos Sosa 
 */
class IsisIso2709Records implements ArrayAccess, Countable,  Iterator {
    const RECORD_SEPARATOR = '##';
    const RECORD_FIELD_SEPARATOR = '#';
    
    private $resource;
    private $directory;
    
    private $it_pos;
    
    public function __construct( $_path) {
        if ( file_exists($_path) || is_readable($_path))
        {
          $this->resource = fopen($_path, 'r');
        } else            
            throw new ErrorException('Error al intentar acceder al archivo.');        
        
        $this->directory = array();
        $this->preScan();   
                        
        fseek($this->resource, 0);
        $this->it_pos = 0;
    }

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
                return new Isis2709Extract(stream_get_line($this->resource, $this->directory[$this->it_pos][1]));
            } else {
                fseek($this->resource, $this->directory[$this->it_pos][0]);
                return new Isis2709Extract(stream_get_line($this->resource, $this->directory[$this->it_pos][1]));
            }
        }
        else 
            throw new ErrorException('Campo no válido.');
    }
}