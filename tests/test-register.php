<?php

class Collection_Test_Register extends Collection_UnitTestCase {

	function test_actions() {
		$key = static::key( __METHOD__ );

		$registered_callback = function( $_key ) use( $key ) {
			$this->assertEquals( $key, $_key );
			$this->assertTrue( Collection::is_registered(  $key ) );
			$this->assertTrue( Collection::is_registered( $_key ) );
		};

		add_action( 'collection_registered', $registered_callback );

		# Use the already generated key.
		register_collection( $key );

		remove_action( 'collection_registered', $registered_callback );

		$this->assertEquals( 1, did_action( 'collection_registered' ) );
		$this->assertEquals( 1, did_action( 'collection:' . $key . '/registered' ) );
	}

	/**
	 * Test Collection registration.
	 */
	function test_is_registered() {
		$key = $this->register_collection( __METHOD__ );
		$this->assertTrue( Collection::is_registered( $key ) );
	}

	/**
	 * Test duplicate registration triggers error.
	 */
	function test_duplicate_registrations_notice() {
		$this->register_collection( __METHOD__ );
		$this->expectException( 'PHPUnit_Framework_Error_Notice' );
		$this->register_collection( __METHOD__ );
	}

	/**
	 * Test return value of duplicate registration.
	 */
	function test_duplicate_registrations_return() {
		$key = static::key( __METHOD__ );
		@register_collection( $key, array( __CLASS__, 'collection_callback' ) );
		$this->assertNull( @register_collection( $key, array( __CLASS__, 'collection_callback' ) ) );
	}

	function test_get_unregistered_notice() {
		$this->expectException( 'PHPUnit_Framework_Error_Notice' );
		$this->get_runtime( static::key( __METHOD__ ) );
	}

	function test_get_unregistered() {
		$runtime = @$this->get_runtime( static::key( __METHOD__ ) );
		$this->assertInstanceOf( Collection::class, $runtime );
		$this->assertEmpty( $runtime->items );
	}

}

?>
