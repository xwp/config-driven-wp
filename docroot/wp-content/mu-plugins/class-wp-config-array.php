<?php
/**
 * Plugin Name: class WP_Config_Array
 * Description: Extension of the SPL ArrayObject to facilitate working with multi-dimensional associative arrays, especially for traversing and merging.
 * Author: Weston Ruter, X-Team
 * Author URI: http://x-team.com/wordpress/
 * Version: 1.1
 * License: GPLv2+
 */

class WP_Config_Array extends ArrayObject {

	/**
	 * Load the config from file and let it merge on top of the base configs it defines. Base configs get sorted by key.
	 * Remember to never use TEMPLATEPATH and STYLESHEETPATH!
	 * @todo If one of the base_configs itself has a base config, it will not currently get recursively parsed
	 * @param string $theme_config_file Location of PHP file which returns a config array
	 * @param string $base_config_key The key for the array item containing the configs to merge on top of
	 * @return WP_Config_Array
	 */
	static function load_config( $theme_config_file, $base_config_key = 'base_configs' ) {
		$config = new self( require( $theme_config_file ) );

		// Allow a config to make note of other configs that it extends
		if ( $base_config_key ) {
			$base_config_files = array_filter( $config->get( $base_config_key, array() ) );
			ksort( $base_config_files );
			foreach ( $base_config_files as $priority => $base_config_file ) {
				$base_config = new WP_Config_Array( require( $base_config_file ) );
				$base_config->extend( $config->getArrayCopy() );
				$config = $base_config;
			}
		}

		do_action( 'theme_config_loaded', $config );
		return $config;
	}

	function __get( $name ) {
		$value = $this[$name];
		if ( self::is_assoc_array( $value ) ) {
			$value = new self( $value );
		}
		return $value;
	}

	function __set( $name, $value ) {
		$this[$name] = $value;
	}

	function offsetSet($offset, $value) {
		if ( is_int( $offset ) || is_null( $offset ) ) {
			throw new Exception( sprintf( 'Illegal numeric offset "%s" for associative array', var_export($offset, true) ) );
		}
		return parent::offsetSet( $offset, $value );
	}

	function append( $value ) {
		throw new Exception( sprintf( 'Illegal method %s for associative array', __CLASS__ ) );
	}

	/**
	 * Extend the instance array with another array(s)
	 * @param {array} Variable list of arrays to merge
	 * ...
	 */
	function extend( array $array /*...*/) {
		$arrays = func_get_args();
		array_unshift( $arrays, $this->getArrayCopy() );
		$this->exchangeArray( call_user_func_array(array( __CLASS__, 'recursive_array_merge_assoc' ), $arrays) );
	}

	/**
	 * Return true if the property is truthy (true, etc)
	 * @param $name
	 *
	 * @return bool
	 */
	function defined( $name ) {
		$none = new stdClass();
		$value = $this->get($name, $none);
		if ($value === $none) {
			return false;
		}
		return self::is_truthy( $value );
	}

	/**
	 * Convenience shortcut for obtaining a config value, supply a path to the
	 * configuration desired (separated by '/') and optionally provide a default.
	 * If the default is an associative array and the config points to an
	 * associative array (and it most likely should), then the result will be
	 * merged on top of the default array via recursive_array_merge_assoc
	 *
	 * @param string $name Path to the configuration setting desired
	 * @param mixed $default What to return of the value does not exist
	 *
	 * @return mixed|null|void
	 * @throws Exception
	 */
	function get( $name, $default = null ) {
		$name_parts = explode( '/', $name );
		$value = $default;
		$array = $this->getArrayCopy();
		while ( ! empty( $name_parts ) ) {
			$name_part = array_shift( $name_parts );
			if ( ! is_array( $array ) || ! array_key_exists( $name_part, $array ) ) {
				break;
			}
			if ( empty( $name_parts ) ) {
				$value = $array[$name_part];
			}
			else {
				$array = &$array[$name_part];
			}
		}

		// Merge the config array on top of the default array if they are both associative
		if ( self::is_assoc_array( $default ) && self::is_assoc_array( $array ) ) {
			$value = self::recursive_array_merge_assoc($default, $value);
		}
		if ( function_exists('apply_filters') ) {
			$filter_name = sprintf( 'wp_config_array_%s', $name );
			$value = apply_filters( $filter_name, $value );
		}
		return $value;
	}

	/**
	 * Test to see if a value is an associative array
	 * @param mixed $value
	 * @return bool
	 */
	static function is_assoc_array( $value ) {
		if ( ! is_array($value) ) {
			return false;
		}
		$has_index_key = in_array( true, array_map('is_int', array_keys($value)) );
		return !$has_index_key;
	}

	/**
	 * Merge two associative arrays recursively
	 * @return mixed
	 * @throws Exception
	 */
	static function recursive_array_merge_assoc( /*...*/ ){
		if (func_num_args() < 2) {
			throw new Exception('recursive_array_merge_assoc requires at least two args');
		}
		$arrays = func_get_args();
		if ( in_array(false, array_map( array( __CLASS__, 'is_assoc_array' ), $arrays )) ) {
			throw new Exception( 'recursive_array_merge_assoc must be passed associative arrays (no numeric indexes)' );
		}
		return array_reduce( $arrays, array( __CLASS__, '_recursive_array_merge_assoc_two' ) );
	}

	/**
	 * Merge two associative arrays recursively
	 * @param array $a assoc array
	 * @param array $b assoc array
	 *
	 * @return array
	 * @todo Once PHP 5.3 is adopted, supply array() as $initial arg for array_reduce() and then change params $a and $b to array types
	 */
	static protected function _recursive_array_merge_assoc_two( $a, $b ) {
		if ( is_null($a) ) { // needed for array_reduce in PHP 5.2
			return $b;
		}

		$merged = array();
		$all_keys = array_merge( array_keys( $a ), array_keys( $b ) );
		foreach ( $all_keys as $key ) {
			$value = null;

			// If key only exists in a (is not in b), then we pass it along
			if ( ! array_key_exists( $key, $b ) ) {
				assert( array_key_exists( $key, $a ) );
				$value = $a[$key];
			}
			// If key only exists in b (is not in a), then it is passed along
			else if ( ! array_key_exists( $key, $a ) ) {
				assert( array_key_exists( $key, $b ) );
				$value = $b[$key];
			}
			// ** At this point we know that they key is in both a and b **
			// If either is not an associative array, then we automatically chose b
			else if ( ! self::is_assoc_array( $a[$key] ) || ! self::is_assoc_array( $b[$key] ) ) {
				// @todo if they are both arrays, should we array_merge?
				$value = $b[$key];
			}
			// Both a and b's value are associative arrays and need to be merged
			else {
				$value = self::recursive_array_merge_assoc( $a[$key], $b[$key] );
			}

			// If the value is null, then that means the b array wants to delete
			// what is in a, so only merge if it is not null
			if ( ! is_null( $value ) ) {
				$merged[$key] = $value;
			}
		}
		return $merged;
	}

	/**
	 * Remove false and null from an array
	 * @param array $array
	 *
	 * @return array
	 */
	function filter_truthy(){
		return array_filter( $this->getArrayCopy(), array( __CLASS__, 'is_truthy' ) );
	}

	/**
	 * Return true if value is not null and it is not false
	 *
	 * @param mixed $value
	 * @return bool
	 */
	static function is_truthy( $value ) {
		return ! is_null( $value ) && $value !== false;
	}
}
