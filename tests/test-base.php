<?php

class Collection_Base_Test extends WP_UnitTestCase {

	const COLLECTION_KEY_PREFIX = '_phpunit_test';
	protected static $collection = null;

	static function collection_callback() {
		return range( 1, 5 );
	}

	protected static function key( $suffix ) {
		return static::COLLECTION_KEY_PREFIX . '__' . $suffix;
	}

	protected static function register_collection( $key_suffix ) {
		$key = static::key( $key_suffix );
		register_collection( $key, array( __CLASS__, 'collection_callback' ) );
		return $key;
	}

	function test_callables() {
		foreach ( array(
			'format_key',
			'is_registered',
			'register',
			'get',
		) as $static_method_name )
			$this->assertIsCallable( array( Collection::class, $static_method_name ) );

		$key = static::register_collection( __FUNCTION__ );
		$collection = get_collection( $key );

		foreach ( array(
			'get_items',
		) as $method_name )
			$this->assertIsCallable( array( $collection, $method_name ) );
	}

	function test_is_registered() {
		$key = static::register_collection( __FUNCTION__ );

		$this->assertTrue( Collection::is_registered( $key ) );
		$this->assertTrue( ( bool ) did_action( 'collection_registered' ) );
		$this->assertTrue( ( bool ) did_action( 'collection:' . $key . '/registered' ) );
	}

	function test_duplicate_registrations_notice() {
		static::register_collection( __FUNCTION__ );
		$this->expectException( 'PHPUnit_Framework_Error_Notice' );
		static::register_collection( __FUNCTION__ );
	}

	function test_get_unregistered_notice() {
		$this->expectException( 'PHPUnit_Framework_Error_Notice' );
		get_collection( static::key( __FUNCTION__ ) );
	}

	function test_get_unregistered() {
		$collection = @get_collection( static::key( __FUNCTION__ ) );
		$this->assertInstanceOf( Collection::class, $collection );
		$this->assertEmpty( $collection->get_items() );
	}

	function test_get() {
		$key = static::register_collection( __FUNCTION__ );
		$this->assertInstanceOf( Collection::class, get_collection( $key ) );

		$this->assertTrue( ( bool ) did_action( 'collection_constructed' ) );
		$this->assertTrue( ( bool ) did_action( 'collection:' . $key . '/constructed' ) );
	}

	function test_callable_callback() {
		$key = static::register_collection( __FUNCTION__ );

		get_collection( $key );
		$this->assertNull( null );
	}

	function test_uncallable_callback_exception() {
		$key = static::key( __FUNCTION__ );
		register_collection( $key, 'does_not_exist' );
		$this->expectException( 'PHPUnit_Framework_Error_Notice' );
		get_collection( $key );
	}

	function test_uncallable_callback() {
		$key = static::key( __FUNCTION__ );
		@register_collection( $key, 'does_not_exist' );
		$collection = @get_collection( $key );

		$this->assertInstanceOf( Collection::class, $collection );
		$this->assertIsArray( $collection->get_items() );
		$this->assertEmpty( $collection->get_items() );
	}

	function test_set_items() {
		$key = static::register_collection( __FUNCTION__ );
		$collection = get_collection( $key );

		$this->assertTrue( ( bool ) did_action( 'collection_curated' ) );
		$this->assertTrue( ( bool ) did_action( 'collection:' . $key . '/curated' ) );
	}

	function test_get_items() {
		$key = static::register_collection( __FUNCTION__ );
		$collection = get_collection( $key );

		$this->assertNotEmpty( $collection->get_items() );
		$this->assertIsArray(  $collection->get_items() );
		$this->assertEquals(   $collection->get_items(), static::collection_callback() );
	}

}
