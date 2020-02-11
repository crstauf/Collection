<?php

require_once 'base.php';

class Collection_Expiration_Test extends Collection_Test_Base {

	const COLLECTION_KEY_PREFIX = '_phpunit_expiration_';
	const LIFE = 1;

	protected function register_collection( $key_suffix ) {
		$key = self::key( $key_suffix );
		register_collection( $key, array( __CLASS__, 'collection_callback' ), self::LIFE );
		return $key;
	}

	protected function get_transient( $key ) {
		$transient_key = Collection::transient_key( $key );
		return get_transient( $transient_key );
	}

	function test_transient() {
		$key = $this->register_collection( __FUNCTION__ );
		$this->get_collection( $key );
		$transient = $this->get_transient( $key );

		$this->assertNotEmpty( $transient );
		$this->assertInstanceOf( Collection::class, $transient );
	}

	function test_source() {
		$key = $this->register_collection( __FUNCTION__ );
		$collection = $this->get_collection( $key );
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

	/**
	 * @todo check after "refresh"
	 */
	function test_expiration() {
		$key = $this->register_collection( __FUNCTION__ );
		$collection = $this->get_collection( $key );

		sleep( self::LIFE + 1 );

		$this->assertFalse( $this->get_transient( $key ) );
	}

}
