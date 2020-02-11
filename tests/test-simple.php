<?php

require_once 'base.php';

class Collection_Simple_Test extends Collection_Test_Base {

	function test_get_unregistered_notice() {
		$this->expectException( 'PHPUnit_Framework_Error_Notice' );
		$this->get_collection( static::key( __FUNCTION__ ) );
	}

	function test_get_unregistered() {
		$collection = @$this->get_collection( static::key( __FUNCTION__ ) );
		$this->assertInstanceOf( Collection::class, $collection );
		$this->assertEmpty( $collection->items );
	}

	function test_uncallable_callback_exception() {
		$key = static::key( __FUNCTION__ );
		register_collection( $key, 'does_not_exist' );
		$this->expectException( 'PHPUnit_Framework_Error_Notice' );
		$this->get_collection( $key );
	}

	function test_uncallable_callback() {
		$key = static::key( __FUNCTION__ );
		@register_collection( $key, 'does_not_exist' );
		$collection = @$this->get_collection( $key );

		$this->assertInstanceOf( Collection::class, $collection );
		$this->assertIsArray( $collection->items );
		$this->assertEmpty(   $collection->items );
	}

}
