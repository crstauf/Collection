<?php

class Collection_Test_Expiration extends Collection_UnitTestCase {

	protected function _test_set_expiration( Collection $collection ) {
		$this->assertInstanceOf( DateTime::class, $collection->expiration );
		$this->assertGreaterThan( date_create( 'now', new DateTimeZone( 'UTC' ) ), $collection->expiration );

		$interval = new DateInterval( 'PT' . static::LIFE . 'S' );
		$this->assertEquals( $collection->expiration->getTimestamp(), $collection->created->add( $interval )->getTimestamp() );
	}

	function test_runtime_set_expiration() {
		$key = $this->register_collection( __METHOD__, static::LIFE );
		$this->_test_set_expiration( $this->get_runtime( $key ) );
	}

	function test_cached_set_expiration() {
		$key = $this->register_collection( __METHOD__, static::LIFE );
		$this->_test_set_expiration( $this->get_cached( $key ) );
	}

	function test_transient_set_expiration() {
		$key = $this->register_collection( __METHOD__, static::LIFE );
		$this->_test_set_expiration( $this->get_transient( $key ) );
	}

	/**
	 * @group does_sleep
	 */
	function test_expires() {
		$key = $this->register_collection( __METHOD__, static::LIFE );
		$this->get_runtime( $key );

		sleep( static::LIFE + 1 );

		$this->assertFalse( $this->_get_transient( $key ) );
	}

	/**
	 * @group does_sleep
	 */
	function test_restored_after_refresh() {
		$key = $this->register_collection( __METHOD__, static::LIFE );
		$runtime = $this->get_runtime( $key );

		sleep( static::LIFE + 1 );

		$this->assertFalse( $this->_get_transient( $key ) );

		$runtime->refresh();

		# Use get_option() instead of get_transient() due to
		# WordPress caching behavior.
		$option = $this->_get_option( $key );

		$this->assertNotFalse( $option );
		$this->assertInstanceOf( Collection::class, $option );
	}

}
