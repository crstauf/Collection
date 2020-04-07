<?php
/**
 * Plugin name: Collection
 * Plugin URI: https://github.com/crstauf/Collection
 * Description: Manage collections of anything in WordPress.
 * Author: Caleb Stauffer
 * Author URI: https://develop.calebstauffer.com
 * Version: 2.0
 */

defined( 'LOG_COLLECTION_ACCESS' ) || define( 'LOG_COLLECTION_ACCESS', false );

/**
 * Collection.
 *
 * @todo add duplication detection
 */
class Collection implements ArrayAccess, Countable, Iterator {

	/**
	 * @var array Registered Collection keys.
	 */
	protected static $registered = array();

	/**
	 * @var string $key
	 * @var null|DateTime $created
	 * @var null|callback $callback
	 * @var array $items
	 * @var string $source
	 * @var array $access_log
	 */
	protected $key = '';
	protected $created = null;
	protected $expiration = null;
	protected $callback = null;
	protected $items = array();
	protected $source = 'runtime';
	protected $access_log = array();


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
	 * Transient key.
	 *
	 * @param string $key
	 * @return string
	 */
	static function transient_key( $key ) {
		return Collection::class . '_' . $key;
	}

	/**
	 * Format Collection key.
	 *
	 * @param string|int $key
	 * @param null|string $context
	 * @return string
	 */
	static function format_key( $key, $context = null ) {
		$key = ( string ) apply_filters( 'collection/key', $key );

		if ( !empty( $context ) )
			$key = ( string ) apply_filters( 'collection/key/' . $context, $key );

		return $key;
	}

	/**
	 * Check if registered.
	 *
	 * @param string $key
	 * @return bool
	 */
	static function is_registered( string $key ) {
		return isset( static::$registered[$key] );
	}

	/**
	 * Register Collection.
	 *
	 * @param mixed $key
	 * @param mixed $callback
	 * @param int $life
	 * @uses static::format_key()
	 * @uses static::is_registered()
	 */
	static function register( $key, $callback = null, int $life = -1 ) {
		$key = static::format_key( $key, 'register' );

		# Check if already registered.
		if ( static::is_registered( $key ) ) {
			trigger_error( sprintf( 'Collection <code>%s</code> is already registered.', $key ) );
			return;
		}

		# Store config.
		static::$registered[$key] = array(
			'callback' => $callback,
			    'life' => $life,
		);

		# Do actions.
		do_action( 'collection_registered', $key );
		do_action( 'collection:' . $key . '/registered' );
	}

	/**
	 * Get Collection.
	 *
	 * @param mixed $key
	 * @uses static::format_key()
	 * @uses static::get_from_cache()
	 * @uses static::get_from_transient()
	 * @uses static::is_registered()
	 * @return self
	 */
	static function get( $key ) {
		$key = static::format_key( $key, 'get' );

		# Get from cache.
		$cache = static::get_from_cache( $key );

		if ( !empty( $cache ) )
			return $cache;

		# Get from transient.
		$transient = static::get_from_transient( $key );

		if ( !empty( $transient ) )
			return $transient;

		# Check if not registered.
		if ( !static::is_registered( $key ) ) {
			trigger_error( sprintf( 'Collection <code>%s</code> is not registered.', $key ) );

			# Return empty Collection.
			return new self( $key, '__return_empty_array' );
		}

		# Get registered settings.
		$registered = static::$registered[$key];

		return new self( $key, $registered['callback'], $registered['life'] );
	}

	/**
	 * Get Collection from cache.
	 *
	 * @param string $key
	 * @uses wp_cache_get()
	 * @return self|false
	 */
	protected static function get_from_cache( $key ) {
		$found = false;
		$cached = wp_cache_get( $key, Collection::class, true, $found );

		if ( !$found )
			return false;

		return $cached;
	}

	/**
	 * Get Collection from transient.
	 *
	 * @param string $key
	 * @uses static::transient_key()
	 * @return self|false
	 */
	protected static function get_from_transient( $key ) {
		$transient = get_transient( static::transient_key( $key ) );

		return !empty( $transient )
			? $transient
			: false;
	}

	/**
	 * Count number of constructs.
	 *
	 * @param string $key
	 */
	protected static function track_constructs( string $key ) {
		static $calls = array();

		if ( !isset( $calls[$key] ) )
			$calls[$key] = 0;

		$calls[$key]++;

		if ( 1 !== $calls[$key] )
			trigger_error( sprintf( 'Collection <code>%s</code> has been constructed %d times; should only be once per page load.', $key, $calls[$key] ) );
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
	 * @param mixed $key
	 * @param callback|array $items
	 * @param int $life
	 *
	 * @uses static::format_key()
	 * @uses static::track_constructs()
	 * @uses $this->set_callback()
	 * @uses $this->set_items()
	 * @uses $this->maybe_set_expiration()
	 * @uses $this->save()
	 */
	function __construct( $key, $callback = null, int $life = -1 ) {
		$this->key = static::format_key( $key, 'construct' );

		# Track number times construct is called.
		static::track_constructs( $this->key );

		# Set callback.
		$this->set_callback( $callback );

		# Get and set items.
		$this->set_items();

		# Set expiration.
		$this->maybe_set_expiration( $life );

		# Save.
		$this->save();

		# Do actions.
		do_action( 'collection_constructed', $this );
		do_action( 'collection:' . $this->key . '/constructed', $this );
	}

	/**
	 * Getter.
	 *
	 * @param string $key
	 * @uses $this->maybe_log_access()
	 * @return mixed
	 */
	function __get( $key ) {
		if ( !in_array( $key, array(
			'key',
			'items',
			'source',
			'created',
			'access_log',
			'expiration',
		) ) )
			return;

		if ( 'items' === $key )
			$this->maybe_log_access();

		return $this->$key;
	}

	/**
	 * Sleeper.
	 *
	 * @return array
	 */
	function __sleep() {
		return array(
			'key',
			'items',
			'source',
			'created',
			'callback',
			'expiration',
		);
	}


	/*
	 ######  ######## ######## ######## ######## ########   ######
	##    ## ##          ##       ##    ##       ##     ## ##    ##
	##       ##          ##       ##    ##       ##     ## ##
	 ######  ######      ##       ##    ######   ########   ######
	      ## ##          ##       ##    ##       ##   ##         ##
	##    ## ##          ##       ##    ##       ##    ##  ##    ##
	 ######  ########    ##       ##    ######## ##     ##  ######
	*/

	/**
	 * Set callback.
	 *
	 * @param mixed $callback
	 */
	protected function set_callback( $callback ) {
		# Filter callback.
		$this->callback = apply_filters( 'collection:' . $this->key . '/callback', $callback );

		# If no callback set, use empty array.
		if ( empty( $this->callback ) )
			$this->callback = '__return_empty_array';

		# Check callback is callable.
		if ( is_callable( $this->callback ) )
			return;

		trigger_error( sprintf( 'Callback for Collection <code>%s</code> is not callable.', $this->key ) );
		$this->callback = '__return_empty_array';
	}

	/**
	 * Set items.
	 */
	protected function set_items() {
		static $calls = array();

		if ( !isset( $calls[$this->key] ) )
			$calls[$this->key] = 0;

		$calls[$this->key]++;

		# Start timer: getting items.
		do_action( 'qm/start', 'collection:' . $this->key . '/set_items' );

		# Get items from callback.
		$items = call_user_func( $this->callback );

		# Time lap: getting items.
		do_action( 'qm/lap', 'collection:' . $this->key . '/set_items', 'from callback' );

		# Filter internal items.
		$this->items = ( array ) apply_filters( 'collection:' . $this->key . '/_items', $items );

		# Stop timer: getting items,
		do_action( 'qm/lap',  'collection:' . $this->key . '/set_items', 'from filter' );
		do_action( 'qm/stop', 'collection:' . $this->key . '/set_items' );

		# Set created time.
		$this->created = date_create( 'now', new DateTimeZone( 'UTC' ) );

		do_action( 'collection_curated', $this->key, $this, $calls[$this->key] );
		do_action( 'collection:' . $this->key . '/curated', $this, $calls[$this->key] );
	}

	/**
	 * Maybe set expiration.
	 *
	 * @param int $life
	 */
	protected function maybe_set_expiration( int $life ) {

		# If life is less than one second, don't set expiration.
		if ( $life < 1 )
			return;

		$now = date_create( 'now', new DateTimeZone( 'UTC' ) );
		$interval = new DateInterval( 'PT' . $life . 'S' );
		$this->expiration = $now->add( $interval );
	}

	/**
	 * Save.
	 *
	 * @uses $this->save_to_cache()
	 * @uses $this->save_to_transient()
	 */
	protected function save() {
		$this->save_to_cache();

		if ( is_null( $this->expiration ) )
			return;

		$this->save_to_transient();
	}

	/**
	 * Save to WP cache.
	 *
	 * @uses wp_cache_add()
	 */
	protected function save_to_cache() {
		$cache = clone $this;
		$cache->source = 'object_cache';

		$cached = wp_cache_add( $this->key, $cache, Collection::class, -1 );
	}

	/**
	 * Save to transient.
	 *
	 * @uses static::transient_key()
	 */
	protected function save_to_transient() {
		$now = date_create( 'now', new DateTimeZone( 'UTC' ) );
		$life = $this->expiration->getTimestamp() - $now->getTimestamp();

		$transient = clone $this;
		$transient->source = 'transient';

		$set = set_transient( static::transient_key( $this->key ), $transient, $life );
	}

	/**
	 * Maybe log access.
	 *
	 * Log access if `LOG_COLLECTION_ACCESS` constant is true.
	 *
	 * @uses $this->log_access()
	 */
	protected function maybe_log_access() {
		if ( !LOG_COLLECTION_ACCESS )
			return;

		$this->log_access();
	}

	/**
	 * Log stack trace.
	 */
	protected function log_access() {
		$log = apply_filters( 'collection/access_log', wp_debug_backtrace_summary( Collection::class ), $this );
		$log = apply_filters( 'collection:' . $this->key . '/access_log', $log );
		$this->access_log[( string ) microtime( true )] = $log;
	}


	/*
	#### ######## ######## ##     ##  ######
	 ##     ##    ##       ###   ### ##    ##
	 ##     ##    ##       #### #### ##
	 ##     ##    ######   ## ### ##  ######
	 ##     ##    ##       ##     ##       ##
	 ##     ##    ##       ##     ## ##    ##
	####    ##    ######## ##     ##  ######
	*/

	/**
	 * Get filtered (proper) items.
	 *
	 * @uses $this->maybe_log_access()
	 * @return array
	 */
	function get_proper_items() {
		$this->maybe_log_access();
		return ( array ) apply_filters( 'collection:' . $this->key . '/proper_items', $this->items );
	}

	/**
	 * Get item at specified key.
	 *
	 * @param mixed $key
	 * @uses $this->maybe_log_access()
	 * @return mixed
	 */
	function get_item( $key ) {
		$this->maybe_log_access();
		return $this->items[$key];
	}

	/**
	 * Check item at specified key.
	 *
	 * @param mixed $key
	 * @return bool
	 */
	function has( $key ) {
		return isset( $this->items[$key] );
	}

	/**
	 * Check if value in Collection.
	 *
	 * @param mixed $value
	 * @param bool $strict
	 * @return bool
	 */
	function contains( $value, bool $strict = true ) {
		return in_array( $value, $this->items, $strict );
	}

	/**
	 * Refresh items.
	 *
	 * @uses $this->set_items()
	 * @return $this
	 */
	function refresh() {
		$this->set_items();
		$this->save();

		do_action( 'collection_refreshed', $this );
		do_action( 'collection:' . $this->key . '/refreshed', $this );

		return $this;
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

	function offsetExists( $offset ) {
		return isset( $this->items[$offset] );
	}

	/**
	 * @uses $this->maybe_log_access()
	 */
	function offsetGet( $offset ) {
		$this->maybe_log_access();
		return $this->items[$offset];
	}

	function offsetSet( $offset, $value ) {}
	function offsetUnset( $offset ) {}


	/*
	 ######   #######  ##     ## ##    ## ########    ###    ########  ##       ########
	##    ## ##     ## ##     ## ###   ##    ##      ## ##   ##     ## ##       ##
	##       ##     ## ##     ## ####  ##    ##     ##   ##  ##     ## ##       ##
	##       ##     ## ##     ## ## ## ##    ##    ##     ## ########  ##       ######
	##       ##     ## ##     ## ##  ####    ##    ######### ##     ## ##       ##
	##    ## ##     ## ##     ## ##   ###    ##    ##     ## ##     ## ##       ##
	 ######   #######   #######  ##    ##    ##    ##     ## ########  ######## ########
	*/

	function count() {
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

	function rewind() {
		$this->maybe_log_access();
		reset( $this->items );
	}

	function current() {
		return current( $this->items );
	}

	function key() {
		return key( $this->items );
	}

	function next() {
		next( $this->items );
	}

	function valid() {
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
