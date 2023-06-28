<?php
/**
 * Plugin name: Collection
 * Plugin URI: https://github.com/crstauf/Collection
 * Description: Manage collections of anything in WordPress.
 * Author: Caleb Stauffer
 * Author URI: https://develop.calebstauffer.com
 * Version: 3.0
 */

/**
 * Collection.
 */
class Collection implements ArrayAccess, Countable, Iterator {

	protected $created    = null;
	protected $expiration = null;
	protected $callback   = null;
	protected $items      = array();
	protected $source     = 'runtime';


	/*
	 ######  ########    ###    ######## ####  ######
	##    ##    ##      ## ##      ##     ##  ##    ##
	##          ##     ##   ##     ##     ##  ##
	 ######     ##    ##     ##    ##     ##  ##
	      ##    ##    #########    ##     ##  ##
	##    ##    ##    ##     ##    ##     ##  ##    ##
	 ######     ##    ##     ##    ##    ####  ######
	*/

	/**
	 * Register Collection.
	 *
	 * @param mixed $key
	 * @param mixed $callback
	 * @param int $life
	 * @uses static::format_key()
	 * @uses static::is_registered()
	 */
	public static function register( $key, $callback = null, int $life = -1 ) {

	}

	/**
	 * Get registered Collection.
	 */
	public static function get( $key ) {

	}


	/*
	##     ##    ###     ######   ####  ######
	###   ###   ## ##   ##    ##   ##  ##    ##
	#### ####  ##   ##  ##         ##  ##
	## ### ## ##     ## ##   ####  ##  ##
	##     ## ######### ##    ##   ##  ##
	##     ## ##     ## ##    ##   ##  ##    ##
	##     ## ##     ##  ######   ####  ######
	*/

	/**
	 * Construct.
	 *
	 * @param array $items
	 */
	public function __construct( $items ) {

	}


	/*
	   ###    ########  ########     ###    ##    ##    ###     ######   ######  ########  ######   ######
	  ## ##   ##     ## ##     ##   ## ##    ##  ##    ## ##   ##    ## ##    ## ##       ##    ## ##    ##
	 ##   ##  ##     ## ##     ##  ##   ##    ####    ##   ##  ##       ##       ##       ##       ##
	##     ## ########  ########  ##     ##    ##    ##     ## ##       ##       ######    ######   ######
	######### ##   ##   ##   ##   #########    ##    ######### ##       ##       ##             ##       ##
	##     ## ##    ##  ##    ##  ##     ##    ##    ##     ## ##    ## ##    ## ##       ##    ## ##    ##
	##     ## ##     ## ##     ## ##     ##    ##    ##     ##  ######   ######  ########  ######   ######
	*/

	public function offsetExists( $offset ) {
		return isset( $this->items[ $offset ] );
	}

	public function offsetGet( $offset ) {
		return $this->items[ $offset ];
	}

	public function offsetSet( $offset, $value ) {}
	public function offsetUnset( $offset ) {}


	/*
	 ######   #######  ##     ## ##    ## ########    ###    ########  ##       ########
	##    ## ##     ## ##     ## ###   ##    ##      ## ##   ##     ## ##       ##
	##       ##     ## ##     ## ####  ##    ##     ##   ##  ##     ## ##       ##
	##       ##     ## ##     ## ## ## ##    ##    ##     ## ########  ##       ######
	##       ##     ## ##     ## ##  ####    ##    ######### ##     ## ##       ##
	##    ## ##     ## ##     ## ##   ###    ##    ##     ## ##     ## ##       ##
	 ######   #######   #######  ##    ##    ##    ##     ## ########  ######## ########
	*/

	public function count() {
		return count( $this->items );
	}


	/*
	#### ######## ######## ########     ###    ########  ##       ########
	 ##     ##    ##       ##     ##   ## ##   ##     ## ##       ##
	 ##     ##    ##       ##     ##  ##   ##  ##     ## ##       ##
	 ##     ##    ######   ########  ##     ## ########  ##       ######
	 ##     ##    ##       ##   ##   ######### ##     ## ##       ##
	 ##     ##    ##       ##    ##  ##     ## ##     ## ##       ##
	####    ##    ######## ##     ## ##     ## ########  ######## ########
	*/

	public function rewind() {
		reset( $this->items );
	}

	public function current() {
		return current( $this->items );
	}

	public function key() {
		return key( $this->items );
	}

	public function next() {
		next( $this->items );
	}

	public function valid() {
		return null !== key( $this->items );
	}

}


/*
##     ## ######## ##       ########  ######## ########   ######
##     ## ##       ##       ##     ## ##       ##     ## ##    ##
##     ## ##       ##       ##     ## ##       ##     ## ##
######### ######   ##       ########  ######   ########   ######
##     ## ##       ##       ##        ##       ##   ##         ##
##     ## ##       ##       ##        ##       ##    ##  ##    ##
##     ## ######## ######## ##        ######## ##     ##  ######
*/

if ( !function_exists( 'register_collection' ) ) {

	/**
	 * Register Collection.
	 *
	 * @param mixed $key
	 * @param mixed $callback
	 * @param int $life
	 * @uses Collection::register()
	 */
	function register_collection( $key, $callback = null, int $life = -1 ) {
		Collection::register( $key, $callback, $life );
	}

}

if ( !function_exists( 'get_collection' ) ) {

	/**
	 * Get Collection.
	 *
	 * @param mixed $key
	 * @uses Collection::get()
	 * @return Collection
	 */
	function get_collection( $key ) {
		return Collection::get( $key );
	}

}

do_action( 'collections_available' );
