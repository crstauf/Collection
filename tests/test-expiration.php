<?php

require_once 'base.php';

class Collection_Expiration_Test extends Collection_Test_Base {

	const COLLECTION_KEY_PREFIX = '_phpunit_expiration_';
	const LIFE = 1;


	/*
	##     ## ######## ##       ########  ######## ########   ######
	##     ## ##       ##       ##     ## ##       ##     ## ##    ##
	##     ## ##       ##       ##     ## ##       ##     ## ##
	######### ######   ##       ########  ######   ########   ######
	##     ## ##       ##       ##        ##       ##   ##         ##
	##     ## ##       ##       ##        ##       ##    ##  ##    ##
	##     ## ######## ######## ##        ######## ##     ##  ######
	*/

	protected function register_collection( $key_suffix ) {
		$key = self::key( $key_suffix );
		register_collection( $key, array( __CLASS__, 'collection_callback' ), self::LIFE );
		return $key;
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
		$key = '_transient_' . Collection::transient_key( $key );
		return get_option( $key );
	}


	/*
	######## ########  ######  ########  ######
	   ##    ##       ##    ##    ##    ##    ##
	   ##    ##       ##          ##    ##
	   ##    ######    ######     ##     ######
	   ##    ##             ##    ##          ##
	   ##    ##       ##    ##    ##    ##    ##
	   ##    ########  ######     ##     ######
	*/

	function test_get_from_transient() {
		$key = $this->register_collection( __FUNCTION__ );
		$transient = $this->get_transient( $key );

		$this->assertNotEmpty( $transient );
		$this->assertInstanceOf( Collection::class, $transient );
		$this->assertEquals( 'transient', $transient->source );
		$this->assertEquals( static::collection_callback()[2], $transient[2] );
	}

	function test_transient() {
		$key = $this->register_collection( __FUNCTION__ );
		$this->get_collection( $key );
		$transient = $this->_get_transient( $key );

		$this->assertNotEmpty( $transient );
		$this->assertInstanceOf( Collection::class, $transient );
	}

	function test_source() {
		$key = $this->register_collection( __FUNCTION__ );
		$transient = $this->get_transient( $key );

		$this->assertEquals( 'transient', $transient->source );
	}

	function test_set_expiration() {
		$key = $this->register_collection( __FUNCTION__ );
		$collection = $this->get_collection( $key );

		$this->assertInstanceOf( DateTime::class, $collection->expiration );
		$this->assertGreaterThanOrEqual( date_create( 'now', new DateTimeZone( 'UTC' ) ), $collection->expiration );

		$interval = new DateInterval( 'PT' . self::LIFE . 'S' );
		$this->assertEquals( $collection->expiration->getTimestamp(), $collection->created->add( $interval )->getTimestamp() );
	}

	function test_expires() {
		$key = $this->register_collection( __FUNCTION__ );
		$this->get_collection( $key );

		sleep( self::LIFE + 1 );

		$this->assertFalse( $this->_get_transient( $key ) );
	}

	function test_expiration_restored() {
		$key = $this->register_collection( __FUNCTION__ );
		$collection = $this->get_collection( $key );

		sleep( self::LIFE + 1 );

		$this->assertFalse( $this->_get_transient( $key ) );

		$collection->refresh();

		# Use get_option() instead due to weirdness.
		$transient = $this->_get_option( $key );

		$this->assertNotFalse( $transient );
		$this->assertInstanceOf( Collection::class, $transient );
	}

	function test_track_constructs() {
		$key = $this->register_collection( __FUNCTION__ );
		get_collection( $key );

		delete_transient( Collection::transient_key( $key ) );
		wp_cache_delete( $key, Collection::class );

		$this->expectException( 'PHPUnit_Framework_Error_Notice' );
		get_collection( $key );
	}

}
