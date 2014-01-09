<?php

class sqlite_wpdb {

	var $version = null;
	var $db = null;
	var $result = null;
	var $error = null;

	function sqwpdb($file, $version=3) { 
		return $this->__construct($file, $version); 
	}

	function __construct( $file ) {
		if ( !file_exists($file) )
			return false;
				$this->db = new SQLite3( $file, SQLITE3_OPEN_READONLY );
				if ( !$this->db )
					return false;
				$this->query( "PRAGMA read_uncommitted = 1" );
				return $this;
	}

	function close() { 
		if ( !$this->db ) 
			return false;
		return @$this->db->close(); 
	}

	function escape($string) {
		return str_replace("'", "''", $string);
	}

	function query($query) {
		if ( $this->result = $this->db->query( $query ) )
			return $this->result;
		$this->error = $this->db->lastErrorMsg();
		return false;
	}

	function array_to_object($array) {
		if ( ! is_array($array) )
			return $array;

		$object = new stdClass();
		foreach ( $array as $idx => $val ) {
			$object->$idx = $val;
		}
		return $object;
	}

	function get_results($query) {
		if ( !$this->query($query) )
			return false;
		$rval = array();
		while ( $row = $this->result->fetchArray() ) {
			$rval[] = (object)$row;
		}
		return $rval;
	}

	function get_row($query) {
		if ( ! $results = $this->get_results($query) )
			return false;
		return array_shift($results);
	}

	function get_var($query) {
		return $this->get_val($query);
	}

	function get_val($query) {
		if ( !$row = $this->get_row($query) )
			return false;
		$row = get_object_vars($row);
		if ( !count($row) )
			return false;
		return array_shift($row);
	}

	function get_col($query) {
		if ( !$results = $this->get_results($query) )
			return false;
		$column = array();
		foreach ( $results as $row ) {
			$row = get_object_vars($row);
			if ( !count($row) )
				continue;
			$column[] = array_shift($row);
		}
		return $column;
	}

}
