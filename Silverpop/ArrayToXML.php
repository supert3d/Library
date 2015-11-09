<?php
// https://github.com/simpleweb/SilverpopPHP/blob/master/src/Silverpop/Util/ArrayToXML.php
// Ported from PHP4 to 5 - <tony_collings@conair.com> Nov 2015

class ArrayToXML {
	
	public $_data;
	public $_name = array();
	public $_rep  = array();
	public $_parser = 0;
	public $_ignore, $_err, $_errline, $_replace, $_attribs, $_parent;
	public $_level = 0;
	
	public function __construct( &$data, $replace = array(), $attribs = array() ) {
		$this->_attribs = $attribs;
		$this->_replace = $replace;
		$this->_data = $this->_processArray( $data );
		
	}
	
	public function getXML() {
		return $this->_data;
	}
	
	public function _processArray( &$array, $level = 0, $parent = '' ) {
		//ksort($array);
		$return = '';
		foreach ( (array) $array as $name => $value ) {
			$tlevel = $level;
			$isarray = false;
			$attrs = '';
			
			if ( is_array( $value ) && ( sizeof( $value ) > 0 ) && array_key_exists( 0, $value ) ) {
				$tlevel = $level - 1;
				$isarray = true;
			}
			elseif ( ! is_int( $name ) ) {
				if ( ! isset( $this->_rep[$name] ) )
					$this->_rep[$name] = 0;
				$this->_rep[$name]++;
			}
			else {
				$name = $parent;
				if ( ! isset( $this->_rep[$name] ) )
					$this->_rep[$name] = 0;
				$this->_rep[$name]++;
			}
			
			if ( ! isset( $this->_rep[$name] ) )
				$this->_rep[$name] = 0;
			
			if ( isset( $this->_attribs[$tlevel][$name][$this->_rep[$name] - 1] ) && is_array( $this->_attribs[$tlevel][$name][$this->_rep[$name] - 1] ) ) {
				foreach ( (array) $this->_attribs[$tlevel][$name][$this->_rep[$name] - 1] as $aname => $avalue ) {
					unset( $value[$aname] );
					$attrs .= " $aname=\"$avalue\"";
				}
			}
			if ( isset($this->_replace[$name]) && $this->_replace[$name] )
				$name = $this->_replace[$name];
			
			is_array( $value ) ? $output = $this->_processArray( $value, $tlevel + 1, $name ) : $output = htmlspecialchars( $value );
			
			$isarray ? $return .= $output : $return .= "<$name$attrs>$output</$name>\n";
		}
		return $return;
	}
}

