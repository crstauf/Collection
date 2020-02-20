<?php

class Collection_Test_Items extends Collection_UnitTestCase {

	function test_actions() {
		$key = $this->register_collection( __METHOD__ );

		$curated_callback = function( ...$args ) use( $key ) {
			$calls      = array_pop( $args );
			$collection = array_pop( $args );
			$_key       = array_pop( $args );

			if ( !is_null( $_key ) )
				$this->assertEquals( $key, $_key );

			$this->assertEquals( 1, $calls );
		};

		add_action( 'collection_curated', $curated_callback, 10, 3 );
		add_action( 'collection:' . $key . '/curated', $curated_callback, 10, 2 );

		$this->get_runtime( $key );

		remove_action( 'collection_curated', $curated_callback, 10, 3 );
		remove_action( 'collection:' . $key . '/curated', $curated_callback, 10, 2 );

		$this->assertEquals( 1, did_action( 'collection_curated' ) );
		$this->assertEquals( 1, did_action( 'collection:' . $key . '/curated' ) );
	}


	protected function _test_items( Collection $collection ) {
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

	function test_runtime_items() {
		$key = $this->register_collection( __METHOD__ );
		$this->_test_items( $this->get_runtime( $key ) );
	}

	function test_cached_items() {
		$key = $this->register_collection( __METHOD__ );
		$this->_test_items( $this->get_cached( $key ) );
	}

	function test_transient_items() {
		$key = $this->register_collection( __METHOD__, static::LIFE );
		$this->_test_items( $this->get_transient( $key ) );
	}


	protected function _test_get_item( Collection $collection ) {
		$this->assertEquals( static::collection_callback()[0],     $collection->get_item( 0 ) );
		$this->assertEquals( static::collection_callback()['foo'], $collection->get_item( 'foo' ) );
	}

	function test_runtime_get_item() {
		$key = $this->register_collection( __METHOD__ );
		$this->_test_get_item( $this->get_runtime( $key ) );
	}

	function test_cached_get_item() {
		$key = $this->register_collection( __METHOD__ );
		$this->_test_get_item( $this->get_cached( $key ) );
	}

	function test_transient_get_item() {
		$key = $this->register_collection( __METHOD__, static::LIFE );
		$this->_test_get_item( $this->get_transient( $key ) );
	}

}
