<?php

require_once 'test-base.php';

class Collection_Expiration_Test extends Collection_Base_Test {

	const COLLECTION_KEY_PREFIX = '_phpunit_expiration_';
	const LIFE = 1;

	protected static function register_collection( $key_suffix ) {
		$key = static::key( $key_suffix );
		register_collection( $key, array( __CLASS__, 'collection_callback' ), static::LIFE );
		return $key;
	}

	protected static function get_transient( $key ) {
		$transient_key = Collection::transient_key( $key );
		return get_transient( $transient_key );
	}

	function test_transient() {
		$key = static::register_collection( __FUNCTION__ );
		get_collection( $key );
		$transient = static::get_transient( $key );

		$this->assertNotEmpty( $transient );
		$this->assertInstanceOf( Collection::class, $transient );
	}

	function test_source() {
		$key = static::register_collection( __FUNCTION__ );
		get_collection( $key );
		$transient = static::get_transient( $key );

		$this->assertEquals( 'transient', $transient->source );
	}

	function test_set_expiration() {
		$key = static::register_collection( __FUNCTION__ );
		$collection = get_collection( $key );

		$this->assertInstanceOf( DateTime::class, $collection->expiration );
		$this->assertGreaterThanOrEqual( date_create( 'now', new DateTimeZone( 'UTC' ) ), $collection->expiration );

		$interval = new DateInterval( 'PT' . static::LIFE . 'S' );
		$this->assertEquals( $collection->expiration->getTimestamp(), $collection->created->add( $interval )->getTimestamp() );
	}

	/**
	 * @todo check after "refresh"
	 */
	function test_expiration() {
		$key = static::register_collection( __FUNCTION__ );
		$collection = get_collection( $key );

		sleep( static::LIFE + 1 );

		$this->assertFalse( static::get_transient( $key ) );
	}

}
