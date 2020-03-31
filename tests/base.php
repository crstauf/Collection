<?php

/**
 * @coversNothing
 */
abstract class Collection_UnitTestCase extends WP_UnitTestCase {

	/**
	 * @var string Prefix for Collection keys.
	 */
	const COLLECTION_KEY_PREFIX = '_phpunit_';

	/**
	 * @var int
	 */
	const LIFE = 1;

	/**
	 * Generate range of numbers.
	 *
	 * @return int[]
	 */
	protected static function range() {
		return range( 1, 10, 2 );
	}

	/**
	 * Collection data callback.
	 *
	 * @uses static::range()
	 * @return array
	 */
	static function collection_callback() {
		$items = static::range();
		$items['foo'] = 'bar';
		$items['rand'] = mt_rand( 5, 9998 );

		return $items;
	}

	/**
	 * Get key for Collection.
	 *
	 * @param string|int $suffix
	 * @return string
	 */
	protected static function key( $suffix ) {
		return static::COLLECTION_KEY_PREFIX . $suffix;
	}

	/**
	 * Register a Collection, and return the key.
	 *
	 * @param string|int $key_suffix
	 * @param int $life
	 * @uses static::key()
	 * @uses register_collection()
	 * @return string
	 */
	protected function register_collection( $key_suffix, int $life = -1 ) {
		$key = static::key( $key_suffix );
		register_collection( $key, array( get_class( $this ), 'collection_callback' ), $life );
		return $key;
	}

	protected function get_runtime( $key ) {
		return get_collection( $key );
	}

	protected function get_cached( $key ) {
		get_collection( $key );
		return get_collection( $key );
	}

	protected function get_transient( $key ) {
		get_collection( $key );
		wp_cache_delete( $key, Collection::class );
		return get_collection( $key );
	}

	protected function _get_transient( $key ) {
		return get_transient( Collection::transient_key( $key ) );
	}

	protected function _get_option( $key ) {
		return get_option( '_transient_' . Collection::transient_key( $key ) );
	}

}
