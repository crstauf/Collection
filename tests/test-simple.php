<?php

class Collection_Simple_Test extends WP_UnitTestCase {

	const COLLECTION_KEY = '_phpunit_test';
	protected static $classes = array( 'Collection' );
	protected static $collection;

	static function collection_callback() {
		return array(
			5723,
			'73' => 73,
			'year' => date( 'Y' ),
			'random' => rand( 1, 9999 ),
		);
	}

	function setUp() {
		if ( Collection::class() !== 'Collection' )
			static::$classes[] = Collection::class();

		register_collection( static::COLLECTION_KEY, array( __CLASS__, 'collection_callback' ) );
		static::$collection = get_collection( static::COLLECTION_KEY );
	}

	function test_instance() {
		$this->assertInstanceOf( Collection::class(), static::$collection );
	}

	function test_did_actions() {
		$this->assertTrue( ( bool ) did_action( 'collection_registered' ) );
		$this->assertTrue( ( bool ) did_action( 'collection:' . static::COLLECTION_KEY . '/registered' ) );
	}

	function test_callables() {
		foreach ( array(
			'maybe_log_access',
			'contains',
			'has',
			'get_item',
			'get_items',
			'refresh',
		) as $method_name )
			$this->assertIsCallable( array( static::$collection, $method_name ) );
	}

	function test_in_cache() {
		$found = false;
		$cache = wp_cache_get( static::COLLECTION_KEY, Collection::class, false, $found );

		$this->assertTrue( $found );
		$this->assertInstanceOf( Collection::class(), $cache );
	}

	function test_in_transients() {
		$transient = get_transient( Collection::transient_name( static::COLLECTION_KEY ) );
		$this->assertEmpty( $transient );
	}

	function test_contains() {
		$this->assertTrue( static::$collection->contains( 5723 ) );
		$this->assertTrue( static::$collection->contains( 73 ) );
		$this->assertTrue( static::$collection->contains( date( 'Y' ) ) );

		$this->assertFalse( static::$collection->contains( 5723 + 1 ) );
		$this->assertFalse( static::$collection->contains( 73 + 1 ) );
		$this->assertFalse( static::$collection->contains( date( 'Y' ) + 1 ) );
	}

	function test_has() {
		$this->assertTrue( static::$collection->has( 0 ) );
		$this->assertTrue( static::$collection->has( '73' ) );
		$this->assertTrue( static::$collection->has( 'year' ) );

		$this->assertFalse( static::$collection->has( 0 + 1 ) );
		$this->assertFalse( static::$collection->has( '73' . '1' ) );
		$this->assertFalse( static::$collection->has( 'year' . 's' ) );
	}

	protected function get_items() {
		$from_collection = static::$collection->get_items();
		$from_callback   = static::collection_callback();

		unset(
			$from_collection['random'],
			$from_callback['random']
		);

		return array( $from_collection, $from_callback );
	}

	function test_items() {
		list( $from_collection, $from_callback ) = $this->get_items();

		$this->assertEquals( $from_collection, $from_callback );

		$this->assertEquals( static::$collection->get_item( 0 ),      static::collection_callback()[0] );
		$this->assertEquals( static::$collection->get_item( '73' ),   static::collection_callback()['73'] );
		$this->assertEquals( static::$collection->get_item( 'year' ), static::collection_callback()['year'] );
	}

	function test_refresh() {
		$before_refresh = static::$collection->get_item( 'random' );
		static::$collection->refresh();
		$this->assertFalse( $before_refresh === static::$collection->get_item( 'random' ) );
	}

	function test_arrayaccess() {
		$this->assertEquals( static::$collection[0], static::collection_callback()[0] );
		$this->assertTrue( isset( static::$collection[0] ) );
	}

	function test_countable() {
		$this->assertEquals( count( static::$collection ), count( static::collection_callback() ) );
	}

	function test_iterable() {
		$this->assertIsIterable( static::$collection );
	}

}
