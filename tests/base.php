<?php

/**
 * @todo add test for accessible properties
 */
abstract class Collection_Test_Base extends WP_UnitTestCase {

	const COLLECTION_KEY_PREFIX = '_phpunit_';


	/*
	 ######  ########    ###    ######## ####  ######
	##    ##    ##      ## ##      ##     ##  ##    ##
	##          ##     ##   ##     ##     ##  ##
	 ######     ##    ##     ##    ##     ##  ##
	      ##    ##    #########    ##     ##  ##
	##    ##    ##    ##     ##    ##     ##  ##    ##
	 ######     ##    ##     ##    ##    ####  ######
	*/

	static function collection_callback() {
		$items = range( 1, 5 );
		$items['rand'] = mt_rand( 5, 9999 );
		return $items;
	}

	protected static function key( $suffix ) {
		return static::COLLECTION_KEY_PREFIX . $suffix;
	}

	protected function register_collection( $key_suffix ) {
		$key = static::key( $key_suffix );
		register_collection( $key, array( __CLASS__, 'collection_callback' ) );
		return $key;
	}

	protected function get_collection( $key ) {
		return get_collection( $key );
	}


	/*
	 ######  ##          ###     ######   ######
	##    ## ##         ## ##   ##    ## ##    ##
	##       ##        ##   ##  ##       ##
	##       ##       ##     ##  ######   ######
	##       ##       #########       ##       ##
	##    ## ##       ##     ## ##    ## ##    ##
	 ######  ######## ##     ##  ######   ######
	*/

	function test_callables() {
		foreach ( array(
			'format_key',
			'is_registered',
			'register',
			'get',
		) as $static_method_name )
			$this->assertIsCallable( array( Collection::class, $static_method_name ) );

		$key = $this->register_collection( __FUNCTION__ );
		$collection = $this->get_collection( $key );

		foreach ( array(
			'has',
			'contains',
			'refresh',
			'count',
			'rewind',
			'current',
			'key',
			'next',
			'valid',
		) as $method_name )
			$this->assertIsCallable( array( $collection, $method_name ) );
	}

	function test_properties() {
		$key = $this->register_collection( __FUNCTION__ );
		$collection = $this->get_collection( $key );

		$this->assertNotEmpty( $collection->items );
		$this->assertEmpty( $collection->empty );
	}

	function test_source() {
		$key = $this->register_collection( __FUNCTION__ );
		$this->assertEquals( 'runtime', $this->get_collection( $key )->source );
	}

	function test_key() {
		$key = $this->register_collection( __FUNCTION__ );
		$this->assertEquals( $key, $this->get_collection( $key )->key );
	}

	function test_created() {
		$key = $this->register_collection( __FUNCTION__ );
		$collection = $this->get_collection( $key );

		$this->assertInstanceOf( 'DateTime', $collection->created );
		$this->assertLessThanOrEqual( date_create( 'now', new DateTimeZone( 'UTC' ) ), $collection->created );
	}

	function test_array_access() {
		$key = $this->register_collection( __FUNCTION__ );
		$collection = $this->get_collection( $key );

		# Test offsetGet().
		$this->assertEquals( 3, $collection[2] );

		# Test offsetExists().
		$this->assertTrue( isset( $collection[2] ) );

		# Test offsetSet().
		$collection[2] = 10;
		$this->assertNotEquals( 10, $collection[2] );
		$this->assertEquals(     3, $collection[2] );

		# Test offsetUnset().
		unset( $collection[2] );
		$this->assertNotEmpty(  $collection[2] );
		$this->assertEquals( 3, $collection[2] );
		$this->assertTrue( $collection->has( 2 ) );
	}

	function test_countable() {
		$key = $this->register_collection( __FUNCTION__ );
		$collection = $this->get_collection( $key );

		$this->assertEquals( count( static::collection_callback() ), count( $collection ) );
		$this->assertEquals( count( static::collection_callback() ), $collection->count() );
	}

	function test_iterable() {
		$key = $this->register_collection( __FUNCTION__ );
		$collection = $this->get_collection( $key );

		foreach ( $collection as $i => $v )
			if ( 'rand' !== $i )
				$this->assertEquals( static::collection_callback()[$i], $v );

		$collection->rewind();
		$this->assertEquals( 1, $collection->current() );
		$this->assertEquals( 0, $collection->key() );

		$collection->next();
		$this->assertEquals( 2, $collection->current() );
		$this->assertEquals( 1, $collection->key() );

		$collection->next();
		$this->assertEquals( 3, $collection->current() );
		$this->assertEquals( 2, $collection->key() );

		$collection->rewind();
		$this->assertEquals( 1, $collection->current() );
		$this->assertEquals( 0, $collection->key() );

		$this->assertNull( null );
	}


	/*
	########  ########  ######   ####  ######  ######## ########     ###    ######## ####  #######  ##    ##
	##     ## ##       ##    ##   ##  ##    ##    ##    ##     ##   ## ##      ##     ##  ##     ## ###   ##
	##     ## ##       ##         ##  ##          ##    ##     ##  ##   ##     ##     ##  ##     ## ####  ##
	########  ######   ##   ####  ##   ######     ##    ########  ##     ##    ##     ##  ##     ## ## ## ##
	##   ##   ##       ##    ##   ##        ##    ##    ##   ##   #########    ##     ##  ##     ## ##  ####
	##    ##  ##       ##    ##   ##  ##    ##    ##    ##    ##  ##     ##    ##     ##  ##     ## ##   ###
	##     ## ########  ######   ####  ######     ##    ##     ## ##     ##    ##    ####  #######  ##    ##
	*/

	function test_is_registered() {
		$key = $this->register_collection( __FUNCTION__ );

		$this->assertTrue( Collection::is_registered( $key ) );
		$this->assertTrue( ( bool ) did_action( 'collection_registered' ) );
		$this->assertTrue( ( bool ) did_action( 'collection:' . $key . '/registered' ) );
	}

	function test_duplicate_registrations_notice() {
		$this->register_collection( __FUNCTION__ );
		$this->expectException( 'PHPUnit_Framework_Error_Notice' );
		$this->register_collection( __FUNCTION__ );
	}

	function test_duplicate_registrations_return() {
		$key = static::key( __FUNCTION__ );
		@register_collection( $key, array( __CLASS__, 'collection_callback' ) );
		$this->assertNull( @register_collection( $key, array( __CLASS__, 'collection_callback' ) ) );
	}


	/*
	 ######   ######## ########
	##    ##  ##          ##
	##        ##          ##
	##   #### ######      ##
	##    ##  ##          ##
	##    ##  ##          ##
	 ######   ########    ##
	*/

	function test_get() {
		$key = $this->register_collection( __FUNCTION__ );
		$this->assertInstanceOf( Collection::class, $this->get_collection( $key ) );

		$this->assertTrue( ( bool ) did_action( 'collection_constructed' ) );
		$this->assertTrue( ( bool ) did_action( 'collection:' . $key . '/constructed' ) );
	}

	function test_get_from_cache() {
		$key = $this->register_collection( __FUNCTION__ );
		get_collection( $key );
		$this->assertInstanceOf( Collection::class, get_collection( $key ) );
	}


	/*
	 ######     ###    ##       ##       ########     ###     ######  ##    ##
	##    ##   ## ##   ##       ##       ##     ##   ## ##   ##    ## ##   ##
	##        ##   ##  ##       ##       ##     ##  ##   ##  ##       ##  ##
	##       ##     ## ##       ##       ########  ##     ## ##       #####
	##       ######### ##       ##       ##     ## ######### ##       ##  ##
	##    ## ##     ## ##       ##       ##     ## ##     ## ##    ## ##   ##
	 ######  ##     ## ######## ######## ########  ##     ##  ######  ##    ##
	*/

	function test_callable_callback() {
		$key = $this->register_collection( __FUNCTION__ );

		$this->get_collection( $key );
		$this->assertNull( null );
	}

	function test_empty_callback() {
		$key = $this->register_collection( __FUNCTION__ );

		add_filter( 'collection:' . $key . '/callback', '__return_false' );

		$collection = $this->get_collection( $key );
		$this->assertIsArray( $collection->items );
		$this->assertEmpty(   $collection->items );

		remove_filter( 'collection:' . $key . '/callback', '__return_false' );
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

	function test_set_items() {
		$key = $this->register_collection( __FUNCTION__ );
		$this->get_collection( $key );

		$this->assertTrue( ( bool ) did_action( 'collection_curated' ) );
		$this->assertTrue( ( bool ) did_action( 'collection:' . $key . '/curated' ) );
	}

	function test_items() {
		$key = $this->register_collection( __FUNCTION__ );
		$collection = $this->get_collection( $key );

		$this->assertObjectHasAttribute( 'items', $collection );
		$this->assertNotEmpty( $collection->items );
		$this->assertIsArray(  $collection->items );

		$from_callback = static::collection_callback();
		$from_collection = $collection->items;

		unset(
			  $from_callback['rand'],
			$from_collection['rand']
		);

		$this->assertEquals( $from_callback, $from_collection );
	}

	function test_get_item() {
		$key = $this->register_collection( __FUNCTION__ );
		$collection = $this->get_collection( $key );

		$this->assertEquals( 1, $collection->get_item( 0 ) );
	}

	function test_has() {
		$key = $this->register_collection( __FUNCTION__ );
		$collection = $this->get_collection( $key );

		$this->assertTrue( $collection->has( 0 ) );
		$this->assertTrue( $collection->has( 1 ) );
		$this->assertTrue( $collection->has( 2 ) );
		$this->assertTrue( $collection->has( 3 ) );
		$this->assertTrue( $collection->has( 4 ) );
		$this->assertTrue( $collection->has( 'rand' ) );

		$this->assertFalse( $collection->has( 'nothing' ) );
		$this->assertFalse( $collection->has( 99999 ) );
	}

	function test_contains() {
		$key = $this->register_collection( __FUNCTION__ );
		$collection = $this->get_collection( $key );

		$this->assertTrue( $collection->contains( 1 ) );
		$this->assertTrue( $collection->contains( 2 ) );
		$this->assertTrue( $collection->contains( 3 ) );
		$this->assertTrue( $collection->contains( 4 ) );
		$this->assertTrue( $collection->contains( 5 ) );
	}

	function test_refresh() {
		$key = $this->register_collection( __FUNCTION__ );
		$collection = $this->get_collection( $key );

		$before_refresh = $collection->items;
		$collection->refresh();

		$this->assertTrue( ( bool ) did_action( 'collection_refreshed' ) );
		$this->assertTrue( ( bool ) did_action( 'collection:' . $key . '/refreshed' ) );

		$this->assertNotEquals( $before_refresh['rand'], $collection['rand'] );
	}

}
