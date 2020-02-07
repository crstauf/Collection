<?php

/**
 * @todo add test for accessible properties
 */
class Collection_Base_Test extends WP_UnitTestCase {

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

	protected static function register_collection( $key_suffix ) {
		$key = static::key( $key_suffix );
		register_collection( $key, array( __CLASS__, 'collection_callback' ) );
		return $key;
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

	/**
	 * @see Collection::format_key()
	 * @see Collection::is_registered()
	 * @see Collection::register()
	 * @see Collection::get()
	 * @see Collection->get_items()
	 * @see Collection->has()
	 * @see Collection->contains()
	 * @see Collection->refresh()
	 */
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

	function test_source() {
		$key = static::register_collection( __FUNCTION__ );
		$this->assertEquals( 'runtime', get_collection( $key )->source );
	}

	function test_key() {
		$key = static::register_collection( __FUNCTION__ );
		$this->assertEquals( $key, get_collection( $key )->key );
	}

	function test_created() {
		$key = static::register_collection( __FUNCTION__ );
		$collection = get_collection( $key );

		$this->assertInstanceOf( 'DateTime', $collection->created );
		$this->assertLessThanOrEqual( date_create( 'now', new DateTimeZone( 'UTC' ) ), $collection->created );
	}

	/**
	 * @see Collection->offsetGet()
	 * @see Collection->offsetExists()
	 * @see Collection->offsetSet()
	 * @see Collection->offsetUnset()
	 */
	function test_array_access() {
		$key = static::register_collection( __FUNCTION__ );
		$collection = get_collection( $key );

		# Test offsetGet().
		$this->assertEquals( 3, $collection[2] );

		# Test offsetExists().
		$this->assertTrue( isset( $collection[2] ) );

		# Test offsetSet().
		$collection[2] = 10;
		$this->assertEquals( 3, $collection[2] );

		# Test offsetUnset().
		unset( $collection[2] );
		$this->assertEquals( 3, $collection[2] );
	}

	/**
	 * @see Collection->count()
	 */
	function test_countable() {
		$key = static::register_collection( __FUNCTION__ );
		$this->assertEquals( count( static::collection_callback() ), count( get_collection( $key ) ) );
	}

	/**
	 * @see Collection->rewind()
	 * @see Collection->current()
	 * @see Collection->key()
	 * @see Collection->next()
	 */
	function test_iterable() {
		$key = static::register_collection( __FUNCTION__ );
		$collection = get_collection( $key );

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

	/**
	 * @see Collection::register()
	 * @uses Collection::is_registered()
	 */
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
		$this->assertEmpty( $collection->items );
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
		$key = static::register_collection( __FUNCTION__ );
		$this->assertInstanceOf( Collection::class, get_collection( $key ) );

		$this->assertTrue( ( bool ) did_action( 'collection_constructed' ) );
		$this->assertTrue( ( bool ) did_action( 'collection:' . $key . '/constructed' ) );
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

	/**
	 * @uses Collection->get_items()
	 */
	function test_uncallable_callback() {
		$key = static::key( __FUNCTION__ );
		@register_collection( $key, 'does_not_exist' );
		$collection = @get_collection( $key );

		$this->assertInstanceOf( Collection::class, $collection );
		$this->assertIsArray( $collection->items );
		$this->assertEmpty(   $collection->items );
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
	 * @see Collection->set_items()
	 */
	function test_set_items() {
		$key = static::register_collection( __FUNCTION__ );
		get_collection( $key );

		$this->assertTrue( ( bool ) did_action( 'collection_curated' ) );
		$this->assertTrue( ( bool ) did_action( 'collection:' . $key . '/curated' ) );
	}

	function test_items() {
		$key = static::register_collection( __FUNCTION__ );
		$collection = get_collection( $key );

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

	/**
	 * @see Collection->has()
	 */
	function test_has() {
		$key = static::register_collection( __FUNCTION__ );
		$collection = get_collection( $key );

		$this->assertTrue( $collection->has( 0 ) );
		$this->assertTrue( $collection->has( 1 ) );
		$this->assertTrue( $collection->has( 2 ) );
		$this->assertTrue( $collection->has( 3 ) );
		$this->assertTrue( $collection->has( 4 ) );
		$this->assertTrue( $collection->has( 'rand' ) );
	}

	/**
	 * @see Collection->contains()
	 */
	function test_contains() {
		$key = static::register_collection( __FUNCTION__ );
		$collection = get_collection( $key );

		$this->assertTrue( $collection->contains( 1 ) );
		$this->assertTrue( $collection->contains( 2 ) );
		$this->assertTrue( $collection->contains( 3 ) );
		$this->assertTrue( $collection->contains( 4 ) );
		$this->assertTrue( $collection->contains( 5 ) );
	}

	/**
	 * @see Collection->refresh()
	 */
	function test_refresh() {
		$key = static::register_collection( __FUNCTION__ );
		$collection = get_collection( $key );

		$before_refresh = $collection->items;
		$collection->refresh();

		$this->assertTrue( ( bool ) did_action( 'collection_refreshed' ) );
		$this->assertTrue( ( bool ) did_action( 'collection:' . $key . '/refreshed' ) );

		$this->assertNotEquals( $before_refresh['rand'], $collection['rand'] );
	}

}
