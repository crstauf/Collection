<?php
/**
 * Plugin name: Collection
 * Plugin URI: https://gist.github.com/crstauf/7f25b9be2eaea72952fee770432ba27e
 * Description: Manage collections of anything in WordPress.
 * Author: Caleb Stauffer
 * Author URI: develop.calebstauffer.com
 * Version: 1.0
 */

/**
 * Collection.
 */
class Collection implements ArrayAccess, Countable, Iterator {

	/**
	 * @var string[] Registered Collection keys.
	 */
	protected static $registered = array();

	/**
	 * @var string $key
	 * @var null|DateTime $created
	 * @var null|DateTime $expiration
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

	/**
	 * Get transient name.
	 *
	 * @param mixed $key
	 * @return string
	 */
	static function transient_name( $key ) {
		return 'collection__' . sanitize_title_with_dashes( ( string ) $key );
	}

	/**
	 * Register Collection.
	 *
	 * @param mixed $key
	 * @param callback $callback Callback to generate items.
	 * @param int $life
	 */
	static function register( $key, callable $callback = null, int $life = -1 ) {
		$key = ( string ) $key;

		# Check if already registered.
		if ( in_array( $key, static::$registered ) ) {
			trigger_error( sprintf( 'Collection <code>%s</code> already registered.', $key ) );
			return;
		}

		# Store settings.
		static::$registered[$key] = array(
			'callback' => $callback,
			    'life' => $life,
		);

		if (
			defined( 'WP_DEBUG' )
			&& WP_DEBUG
		)
			static::check_duplicate( $key );

		do_action( 'collection_registered', $key );
		do_action( 'collection:' . $key . '/registered' );
	}

	/**
	 * Get Collection from database.
	 *
	 * @param mixed $key
	 * @uses $this::transient_name()
	 * @uses get_transient()
	 * @return Collection
	 */
	static function get( $key ) {
		# Filter key.
		$key = ( string ) apply_filters( 'collection/get/key', ( string ) $key );

		# Check if key is not registered.
		if ( !array_key_exists( $key, static::$registered ) ) {
			trigger_error( sprintf( 'Collection <code>%s</code> is not registered.', $key ) );

			# Return empty Collection.
			return new self( $key );
		}

		# Check object cache.
		$stored = static::get_from_object_cache( $key );

		# Check transients.
		if ( is_null( $stored ) )
			$stored = static::get_from_transient( $key );

		# Get registered settings.
		$registered = static::$registered[$key];

		# If no results in cache, create new Collection from settings.
		if ( is_null( $stored ) ) {
			$new = new self( $key, $registered['callback'], $registered['life'] );
			$new->maybe_log_access();
			return $new;
		}

		# Check callback is correct.
		if ( $stored->callback !== $registered['callback'] ) {
			$stored->set_callback( $registered['callback'] );
			$stored->refresh();
		}

		do_action( 'collection:' . $key . '/loaded', $stored );

		$stored->maybe_log_access();
		return $stored;
	}

	/**
	 * Check if Collector is duplicate of another.
	 *
	 * @param int|string $key
	 */
	static function check_duplicate( $key ) {
		$callback = static::$registered[$key]['callback'];
		$callbacks = array();

		foreach ( static::$registered as $_key => $array )
			if ( $key !== $_key )
				$callbacks[$_key] = $array['callback'];

		$result = array_search( $callback, $callbacks );

		if (
			false !== $result
			&& call_user_func( $callback ) == call_user_func( $callbacks[$result] )
		)
			trigger_error( sprintf( 'The Collector <code>%s</code> uses the same callback as <code>%s</code>.', $key, $result ) );
	}

	/**
	 * Check object cache for Collection.
	 *
	 * @param string $key
	 * @return null|Collection
	 */
	protected static function get_from_object_cache( string $key ) {
		# Check object cache.
		$in_cache = false;
		$cached = wp_cache_get( $key, __CLASS__, false, $in_cache );

		# If not in cache, return null.
		if ( !$in_cache )
			return null;

		$cached->source = 'object_cache';

		return $cached;
	}

	/**
	 * Check transients for Collection.
	 *
	 * @param string $key
	 * @return null|Collection
	 */
	protected static function get_from_transient( string $key ) {
		$transient = get_transient( static::transient_name( $key ) );

		# If no transient found, return null.
		if ( empty( $transient ) )
			return null;

		$transient->source = 'transient';

		# Store in cache.
		wp_cache_add( $key, $transient, __CLASS__ );

		return $transient;
	}

	/**
	 * Construct.
	 *
	 * @param mixed $key
	 * @param callback $callback
	 * @param int $life
	 *
	 * @uses $this::_get_items()
	 * @uses $this::set_expiration()
	 * @uses $this::save()
	 */
	protected function __construct( $key, callable $callback = null, int $life = -1 ) {
		# Set key.
		$this->key = ( string ) $key;

		# Set callback.
		$this->set_callback( $callback );

		# Set items.
		$this->_get_items();

		# If items, maybe set expiration and maybe save.
		if ( !empty( $this->items ) ) {

			# Set expiration time.
			if ( 1 < $life )
				$this->set_expiration( $life );

			# Save to database.
			if ( -1 < $life )
				$this->save();
		}

		# Store in cache.
		wp_cache_add( $this->key, $this, __CLASS__ );

		do_action( 'collection:' . $this->key . '/constructed', $this );
	}

	/**
	 * Sleeper.
	 *
	 * @return array
	 */
	function __sleep() {
		return array(
			'key',
			'created',
			'expiration',
			'callback',
			'items',
		);
	}

	/**
	 * Log access.
	 *
	 * @todo add source of call
	 */
	function maybe_log_access() {
		if (
			!defined( 'WP_DEBUG' )
			|| !WP_DEBUG
		)
			return;

		$this->access_log[] = microtime( true );
		wp_cache_set( $this->key, $this, __CLASS__ );
	}

	/**
	 * Set callback function to get items from.
	 *
	 * @param callable $callback
	 */
	protected function set_callback( callable $callback ) {
		# Filter callback.
		$this->callback = apply_filters( 'collection:' . $this->key . '/callback', $callback );

		# If no callback set, use empty array.
		if ( empty( $this->callback ) )
			$this->callback = '__return_empty_array';

		# Check callback is callable.
		if ( is_callable( $this->callback ) )
			return;

		trigger_error( sprintf( 'Cannot create Collection <code>%s</code>: callback does not exist.', $this->key ) );
		$this->callback = '__return_empty_array';
	}

	/**
	 * Check if item in Collection.
	 *
	 * @param mixed $item
	 * @return bool
	 */
	function contains( $item ) {
		return in_array( $item, $this->get_items() );
	}

	/**
	 * Check Collection has item at specified key.
	 *
	 * @param mixed $key
	 * @uses $this::get_items()
	 * @return mixed
	 */
	function has( $key ) {
		return isset( $this->get_items()[$key] );
	}

	/**
	 * Get item at specified key.
	 *
	 * @param mixed $key
	 * @uses $this::has()
	 * @uses $this::get_items()
	 * @return null|mixed
	 */
	function get_item( $key ) {
		if ( !$this->has( $key ) )
			return null;

		return $this->get_items()[$key];
	}

	/**
	 * Get items.
	 *
	 * @return array
	 */
	function get_items() {
		return ( array ) apply_filters( 'collection:' . $this->key . '/items', $this->items );
	}

	/**
	 * Get items from callback and set.
	 */
	protected function _get_items() {
		static $calls = 0;

		if ( !is_callable( $this->callback ) ) {
			trigger_error( sprintf( 'Cannot get items for Collection <code>%s</code>: callback does not exist.', $this->key ) );
			return;
		}

		# Start timer: getting items.
		do_action( 'qm/start', 'collection:' . $this->key . '/_items/' . ++$calls );

		# Get items from callback.
		$items = ( array ) call_user_func( $this->callback );

		# Time lap: getting items.
		do_action( 'qm/lap', 'collection:' . $this->key . '/_items/' . $calls, 'callback' );

		# Filter items.
		$this->items = ( array ) apply_filters( 'collection:' . $this->key . '/_items', $items );

		# Stop timer: getting items,
		do_action( 'qm/lap',  'collection:' . $this->key . '/_items/' . $calls, 'filtered' );
		do_action( 'qm/stop', 'collection:' . $this->key . '/_items/' . $calls );

		# Set created time.
		$this->created = date_create();

		do_action( 'collection:' . $this->key . '/curated', $this, $calls );
	}

	/**
	 * Set expiration.
	 *
	 * @param int $life
	 */
	protected function set_expiration( int $life ) {
		$expiration = clone $this->created;
		$_life = new DateInterval( 'PT' . $life . 'S' );
		$this->expiration = $expiration->add( $_life );
	}

	/**
	 * Save to database.
	 *
	 * @uses $this::transient_name()
	 */
	protected function save() {
		$life = 0;

		if ( !empty( $this->expiration ) )
			$life = $this->expiration->format( 'U' ) - time();

		set_transient( static::transient_name( $this->key ), $this, $life );

		do_action( 'collection:'. $this->key . '/saved', $this );
	}

	/**
	 * Refresh Collection items from callback.
	 *
	 * @uses $this::_get_items()
	 * @uses $this::save()
	 */
	function refresh() {
		static $calls = 0;

		# Get items from callback.
		$this->_get_items();

		# Re-save to database.
		$this->save();

		do_action( 'collection:' . $this->key . '/refreshed', $this, ++$calls );
	}

	/**
	 * Clear Collection from database.
	 *
	 * @uses $this::transient_name()
	 */
	function clear() {
		delete_transient( static::transient_name( $this->key ) );
		do_action( 'collection:' . $this->key . '/cleared', $this );
	}

	/**
	 * ArrayAccess functions.
	 */
	function offsetExists( $offset ) {
		return isset( $this->items[$offset] );
	}

	function offsetGet( $offset ) {
		return $this->items[$offset];
	}

	function offsetSet( $offset, $value ) {}
	function offsetUnset( $offset ) {}

	/**
	 * Countable functions.
	 */
	function count() {
		return count( $this->items );
	}

	/**
	 * Iterable functions.
	 */
	function rewind() {
		reset( $this->items );
	}

	function current() {
		return current( $this->items );
	}

	function key() {
		return key( $this->items );
	}

	function next() {
		return next( $this->items );
	}

	function valid() {
		$key = key( $this->items );
		return (
			    null !== $key
			&& false !== $key
		);
	}

}

if ( !function_exists( 'register_collection' ) ) {

	/**
	 * Register Collection.
	 *
	 * @param mixed $key
	 * @param callback $callback
	 * @param int $life
	 * @uses Collection::register()
	 */
	function register_collection( $key, callable $callback, int $life = -1 ) {
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

?>
