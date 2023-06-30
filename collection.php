<?php
/**
 * Plugin name: Collection
 * Plugin URI: https://github.com/crstauf/Collection
 * Description: Manage collections of anything in WordPress.
 * Author: Caleb Stauffer
 * Author URI: https://develop.calebstauffer.com
 * Version: 3.0
 */

defined( 'WPINC' ) || die();

/**
 * Collection.
 *
 * @property-read null|string $key
 * @property-read null|DateTime $refreshed
 * @property-read null|DateTime $expiration
 * @property-read null|callable $callback
 * @property-read array<mixed> $items
 * @property-read string $source
 * @property-read bool $debugging
 * @property-read int $life
 */
class Collection implements ArrayAccess, Countable, Iterator {

	/**
	 * @var Collection[]
	 */
	protected static $collections = array();

	/**
	 * @var null|string
	 */
	protected $key = null;

	/**
	 * @var null|DateTime
	 */
	protected $refreshed = null;

	/**
	 * @var null|DateTime
	 */
	protected $expiration = null;

	/**
	 * @var null|callable
	 */
	protected $callback = null;

	/**
	 * @var array<mixed>
	 */
	protected $items = null;

	/**
	 * @var string
	 */
	protected $source = 'runtime';

	/**
	 * @var bool
	 */
	protected $debugging = false;

	/**
	 * @var int
	 */
	protected $life = -1;


	/*
	 *  ######  ########    ###    ######## ####  ######
	 * ##    ##    ##      ## ##      ##     ##  ##    ##
	 * ##          ##     ##   ##     ##     ##  ##
	 *  ######     ##    ##     ##    ##     ##  ##
	 *       ##    ##    #########    ##     ##  ##
	 * ##    ##    ##    ##     ##    ##     ##  ##    ##
	 *  ######     ##    ##     ##    ##    ####  ######
	 */

	/**
	 * Register Collection.
	 *
	 * @param string $key
	 * @param null|array<mixed> $callback
	 * @param int $life
	 * @param bool $debug
	 * @uses $this->from_cache()
	 * @return Collection
	 */
	public static function register( string $key, $callback = null, int $life = -1, bool $debug = false ) : self {
		if ( ! is_callable( $callback ) ) {
			trigger_error( sprintf( 'Callback for Collection %s is not callable.', $key ), E_USER_WARNING );

			return new self;
		}

		if ( array_key_exists( $key, static::$collections ) ) {
			trigger_error( sprintf( 'Collection %s already registered.', $key ), E_USER_NOTICE );

			return static::get( $key );
		}

		$instance            = new self;
		$instance->key       = $key;
		$instance->callback  = $callback;
		$instance->life      = $life;
		$instance->debugging = $debug;

		$instance->log( sprintf( 'Collection::register( %s )', $key ) );
		$instance->hooks();

		static::$collections[ $key ] = $instance;

		do_action( 'collection_registered', $key, $instance );

		return static::$collections[ $key ];
	}

	/**
	 * Get registered Collections.
	 *
	 * @return array<string, Collection>
	 */
	public static function registered() : array {
		return static::$collections;
	}

	/**
	 * Get registered Collection.
	 *
	 * @param string $key
	 * @return Collection
	 */
	public static function get( string $key ) : self {
		if ( empty( $key ) || ! array_key_exists( $key, static::$collections ) ) {
			return new self;
		}

		$instance = static::$collections[ $key ];
		$instance->log( sprintf( 'Collection::get( %s )', $key ) );

		return $instance;
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
	 * @param null|array<mixed>|callable $items
	 */
	public function __construct( $items = null ) {
		if ( is_array( $items ) ) {
			$this->items = $items;
		}

		if ( is_callable( $items ) ) {
			$this->items    = array();
			$this->callback = $items;
		}

		if ( ! is_null( $this->items ) && ! is_array( $items ) ) {
			$this->items = array();
		}

		do_action( 'collection_constructed', $this );
	}

	/**
	 * Getter.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __get( string $key ) {
		if ( 'items' === $key ) {
			return $this->items();
		}

		return $this->$key;
	}

	public function __isset( string $key ) {
		return ! empty( $this->$key );
	}


	/*
	########  ######## ########  ##     ##  ######
	##     ## ##       ##     ## ##     ## ##    ##
	##     ## ##       ##     ## ##     ## ##
	##     ## ######   ########  ##     ## ##   ####
	##     ## ##       ##     ## ##     ## ##    ##
	##     ## ##       ##     ## ##     ## ##    ##
	########  ######## ########   #######   ######
	*/

	/**
	 * @return $this
	 */
	public function debug() : self {
		$this->debugging = true;

		return $this;
	}

	/**
	 * @param mixed $entry
	 * @return void
	 */
	protected function log( $entry ) : void {
		if ( ! $this->debugging ) {
			return;
		}

		do_action( 'qm/debug', $entry );
	}

	/**
	 * @param string $id
	 * @return void
	 */
	protected function start( string $id ) : void {
		if ( ! $this->debugging ) {
			return;
		}

		do_action( 'qm/start', $id );
	}

	/**
	 * @param string $id
	 * @param string $label
	 * @return void
	 */
	protected function lap( string $id, string $label ) : void {
		if ( ! $this->debugging ) {
			return;
		}

		do_action( 'qm/lap', $id, $label );
	}

	/**
	 * @param string $id
	 * @return void
	 */
	protected function stop( string $id ) : void {
		if ( ! $this->debugging ) {
			return;
		}

		do_action( 'qm/stop', $id );
	}


	/*
	#### ##    ##  ######  ########    ###    ##    ##  ######  ########
	 ##  ###   ## ##    ##    ##      ## ##   ###   ## ##    ## ##
	 ##  ####  ## ##          ##     ##   ##  ####  ## ##       ##
	 ##  ## ## ##  ######     ##    ##     ## ## ## ## ##       ######
	 ##  ##  ####       ##    ##    ######### ##  #### ##       ##
	 ##  ##   ### ##    ##    ##    ##     ## ##   ### ##    ## ##
	#### ##    ##  ######     ##    ##     ## ##    ##  ######  ########
	*/

	public function hooks() : void {
		$key = $this->key;

		if ( ! is_string( $key ) ) {
			return;
		}

		$hook = sprintf( 'Collection[ %s ]', $key );

		add_action( $hook . '->refresh()', static function () use ( $key ) : void {
			Collection::get( $key )->refresh();
		} );

		add_action( $hook . '->expire()', static function () use ( $key ) : void {
			Collection::get( $key )->expire();
		} );

		add_filter( $hook . '->has_expired()', static function ( bool $has_expired ) use ( $key ) : bool {
			return Collection::get( $key )->has_expired();
		} );

		add_filter( $hook . '->items()', static function ( array $items ) use ( $key ) : array {
			return Collection::get( $key )->items();
		} );
	}

	/**
	 * @return array<mixed>
	 */
	public function items() : array {
		$id = sprintf( 'Collection[ %s ]->items()', $this->key );

		$this->log( $id );
		$this->start( $id );

		$this->from_cache();
		$this->lap( $id, 'from_cache()' );

		if ( $this->has_expired() ) {
			$this->expire();
			$this->lap( $id, 'expire()' );
		}

		if ( empty( $this->refreshed ) ) {
			$this->refresh();
			$this->lap( $id, 'refresh()' );
		}

		if ( ! is_array( $this->items ) ) {
			trigger_error( sprintf( 'Data in Collection %s is not an array.', $this->key ), E_USER_NOTICE );
			$this->items = array();
		}

		$this->stop( $id );

		return $this->items;
	}

	/**
	 * Check if item in Collection.
	 *
	 * @param mixed $item
	 * @uses $this->items()
	 * @return bool
	 */
	public function contains( $item ) {
		$items = $this->items();

		if ( in_array( $item, $items ) ) {
			return true;
		}

		foreach ( $items as $i ) {
			if ( $i !== $item ) {
				continue;
			}

			return true;
		}

		return false;
	}

	/**
	 * Check Collection has item at specified key.
	 *
	 * @param string|int $key
	 * @uses $this->items()
	 * @return bool
	 */
	public function has( $key ) {
		if ( ! is_string( $key ) && ! is_int( $key ) ) {
			return false;
		}

		return isset( $this->items()[ $key ] );
	}

	/**
	 * @uses $this->callback()
	 * @uses $this->maybe_set_cache()
	 * @return self
	 */
	public function refresh() : self {
		$this->log( sprintf( 'Collection[ %s ]->refresh()', $this->key ) );

		$result = $this->callback();

		if ( false === $result ) {
			do_action( 'collection_not_refreshed', $this->key, $this );

			return $this;
		}

		$refreshed = date_create();

		if ( empty( $refreshed ) ) {
			$refreshed = null;
		}

		$this->items     = $result;
		$this->source    = 'runtime';
		$this->refreshed = $refreshed;

		if ( ! empty( $this->refreshed ) && ! empty( $this->life ) && $this->life > 0 ) {
			$expiration       = clone $this->refreshed;
			$interval         = new DateInterval( 'PT' . $this->life . 'S' );
			$this->expiration = $expiration->add( $interval );
		}

		$this->maybe_set_cache();

		do_action( 'collection_refreshed', $this->key, $this );

		return $this;
	}

	/**
	 * @return string
	 */
	public function cache_key() : string {
		return sprintf( 'collection__%s', $this->key );
	}

	/**
	 * $life === 0: does not expire
	 * $life === -1: no caching
	 *
	 * @return void
	 */
	protected function maybe_set_cache() : void {
		if ( -1 === $this->life ) {
			return;
		}

		$this->log( sprintf( 'Collection[ %s ]->maybe_set_cache()', $this->key ) );

		$value = array(
			'refreshed'  => $this->refreshed,
			'expiration' => $this->expiration,
			'items'      => $this->items,
		);

		if ( 0 === $this->life ) {
			$value['source'] = 'option';
			$result          = update_option( $this->cache_key(), $value, false );
		}

		if ( ! empty( $this->expiration ) ) {
			$value['source'] = 'transient';
			$result          = set_transient( $this->cache_key(), $value, $this->life );
		}

		if ( empty( $result ) ) {
			trigger_error( sprintf( 'Collection %s failed to set cache.', $this->key ), E_USER_WARNING );

			do_action( 'collection_not_cached', $this->key, $this );

			return;
		}

		do_action( 'collection_cached', $this->key, $this );
	}

	/**
	 * @uses $this->from_option()
	 * @uses $this->from_transient()
	 * @return void
	 */
	protected function from_cache() : void {
		$this->from_option();
		$this->from_transient();
	}

	/**
	 * @uses $this->cache_key()
	 * @return void
	 */
	protected function from_option() : void {
		$cache_key = $this->cache_key();
		$cache     = get_option( $cache_key );

		if ( empty( $cache ) ) {
			return;
		}

		$this->log( sprintf( 'Collection[ %s ]->from_option()', $this->key ) );

		if ( 0 !== $this->life ) {
			delete_option( $cache_key );
			return;
		}

		$this->refreshed = $cache['refreshed'];
		$this->items     = $cache['items'];
		$this->source    = 'option';
	}

	/**
	 * @uses $this->cache_key()
	 * @return void
	 */
	protected function from_transient() : void {
		$cache_key = $this->cache_key();
		$cache     = get_transient( $cache_key );

		if ( empty( $cache ) ) {
			return;
		}

		$this->log( sprintf( 'Collection[ %s ]->from_transient()', $this->key ) );

		if ( $this->life < 1 ) {
			delete_transient( $cache_key );
			return;
		}

		$this->refreshed  = $cache['refreshed'];
		$this->expiration = $cache['expiration'];
		$this->items      = $cache['items'];
		$this->source     = 'transient';
	}

	/**
	 * @return bool
	 */
	public function has_expired() : bool {
		if ( empty( $this->life ) ) {
			return false;
		}

		if ( empty( $this->expiration ) ) {
			return false;
		}

		if ( date_create() < $this->expiration ) {
			return false;
		}

		do_action( 'collection_has_expired', $this->key, $this );

		return true;
	}

	/**
	 * @return self
	 */
	public function expire() : self {
		$this->log( sprintf( 'Collection[ %s ]->expire()', $this->key ) );

		$expiration = date_create();

		if ( empty( $expiration ) ) {
			$expiration = null;
		}

		$this->refreshed  = null;
		$this->expiration = $expiration;
		$this->items      = array();

		delete_option( $this->cache_key() );
		delete_transient( $this->cache_key() );

		do_action( 'collection_expired', $this->key, $this );

		return $this;
	}

	/**
	 * @return false|array<mixed>
	 */
	protected function callback() {
		$this->log( sprintf( 'Collection[ %s ]->callback()', $this->key ) );

		$returned = array();

		if ( ! is_callable( $this->callback ) ) {
			trigger_error( sprintf( 'Callback for Collection %s is not available.', $this->key ), E_USER_WARNING );

			return false;
		}

		$returned = call_user_func( $this->callback );

		if ( ! is_array( $returned ) ) {
			trigger_error( sprintf( 'Callback for Collection %s did not return an array.', $this->key ), E_USER_NOTICE );

			return false;
		}

		return $returned;
	}

	protected function access_items() : void {
		if ( ! is_null( $this->items ) ) {
			return;
		}

		$this->items();

		if ( is_array( $this->items ) ) {
			return;
		}

		$this->items = array();
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

	public function offsetExists( $offset ) : bool {
		$this->access_items();
		return isset( $this->items[ $offset ] );
	}

	public function offsetGet( $offset ) : mixed {
		$this->access_items();
		return $this->items[ $offset ];
	}

	public function offsetSet( $offset, $value ) : void {
	}

	public function offsetUnset( $offset ) : void {
	}


	/*
	 ######   #######  ##     ## ##    ## ########    ###    ########  ##       ########
	##    ## ##     ## ##     ## ###   ##    ##      ## ##   ##     ## ##       ##
	##       ##     ## ##     ## ####  ##    ##     ##   ##  ##     ## ##       ##
	##       ##     ## ##     ## ## ## ##    ##    ##     ## ########  ##       ######
	##       ##     ## ##     ## ##  ####    ##    ######### ##     ## ##       ##
	##    ## ##     ## ##     ## ##   ###    ##    ##     ## ##     ## ##       ##
	 ######   #######   #######  ##    ##    ##    ##     ## ########  ######## ########
	*/

	public function count() : int {
		$this->access_items();
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

	public function rewind() : void {
		$this->access_items();
		reset( $this->items );
	}

	public function current() : mixed {
		$this->access_items();
		return current( $this->items );
	}

	public function key() : mixed {
		$this->access_items();
		return key( $this->items );
	}

	public function next() : void {
		$this->access_items();
		next( $this->items );
	}

	public function valid() : bool {
		$this->access_items();
		return null !== key( $this->items );
	}

}

add_action( 'collection:register', static function ( string $key, $callback = null, int $life = -1, bool $debug = false ) : void {
	Collection::register( $key, $callback, $life, $debug );
}, 10, 4 );

add_action( 'collection:refresh', static function ( string $key ) : void {
	Collection::get( $key )->refresh();
} );

add_action( 'collection:expire', static function ( string $key ) : void {
	Collection::get( $key )->expire();
} );

add_filter( 'collection:expired', static function ( bool $expired, string $key ) : bool {
	return Collection::get( $key )->has_expired();
}, 10, 2 );

add_filter( 'collection:items', static function ( array $items, string $key ) : array {
	return Collection::get( $key )->items();
}, 10, 2 );


/*
##     ## ######## ##       ########  ######## ########   ######
##     ## ##       ##       ##     ## ##       ##     ## ##    ##
##     ## ##       ##       ##     ## ##       ##     ## ##
######### ######   ##       ########  ######   ########   ######
##     ## ##       ##       ##        ##       ##   ##         ##
##     ## ##       ##       ##        ##       ##    ##  ##    ##
##     ## ######## ######## ##        ######## ##     ##  ######
*/

if ( ! function_exists( 'register_collection' ) ) {

	/**
	 * Register Collection.
	 *
	 * @param string $key
	 * @param mixed $callback
	 * @param int $life
	 * @param bool $debug
	 * @uses Collection::register()
	 * @return Collection
	 */
	function register_collection( $key, $callback = null, int $life = -1, bool $debug = false ) : Collection {
		return Collection::register( $key, $callback, $life, $debug );
	}

}

if ( ! function_exists( 'get_collection' ) ) {

	/**
	 * Get Collection.
	 *
	 * @param string $key
	 * @uses Collection::get()
	 * @return Collection
	 */
	function get_collection( string $key ) {
		return Collection::get( $key );
	}

}

do_action( 'collections_available' );