<?php

class Collection_Test_Refresh extends Collection_UnitTestCase {

	protected function _test_items( Collection $collection ) {
		$before_refresh = clone $collection;

		$collection->refresh();

		$this->assertNotEquals( $before_refresh->created, $collection->created );
		$this->assertNotEquals( $before_refresh['rand'], $collection['rand'] );
	}

	protected function _test_actions( Collection $collection ) {
		$key = $collection->key;

		$refreshed_callback = function( $after_refresh ) use( $collection ) {
			$this->assertInstanceOf( Collection::class, $collection );
			$this->assertEquals( $collection->key, $after_refresh->key );
		};

		$curated_callback = function( ...$args ) {
			$calls = array_pop( $args );
			$this->assertEquals( 3, $calls );
		};

		$this->assertEquals( 2, did_action( 'collection_curated' ) );
		$this->assertEquals( 1, did_action( 'collection_refreshed' ) );
		$this->assertEquals( 2, did_action( 'collection:' . $key . '/curated' ) );
		$this->assertEquals( 1, did_action( 'collection:' . $key . '/refreshed' ) );

		add_action( 'collection_curated',   $curated_callback, 10, 3 );
		add_action( 'collection_refreshed', $refreshed_callback );
		add_action( 'collection:' . $key . '/curated',   $curated_callback, 10, 2 );
		add_action( 'collection:' . $key . '/refreshed', $refreshed_callback );

		$collection->refresh();

		remove_action( 'collection_curated',   $curated_callback, 10, 3 );
		remove_action( 'collection_refreshed', $refreshed_callback );
		remove_action( 'collection:' . $key . '/curated',   $curated_callback, 10, 2 );
		remove_action( 'collection:' . $key . '/refreshed', $refreshed_callback );

		$this->assertEquals( 3, did_action( 'collection_curated' ) );
		$this->assertEquals( 2, did_action( 'collection_refreshed' ) );
		$this->assertEquals( 3, did_action( 'collection:' . $key . '/curated' ) );
		$this->assertEquals( 2, did_action( 'collection:' . $key . '/refreshed' ) );

	}

	function test_runtime() {
		$key = $this->register_collection( __METHOD__ );
		$runtime = $this->get_runtime( $key );

		$this->_test_items(   $runtime );
		$this->_test_actions( $runtime );
	}

	function test_cached() {
		$key = $this->register_collection( __METHOD__ );
		$cached = $this->get_cached( $key );

		$this->_test_items(   $cached );
		$this->_test_actions( $cached );
	}

	function test_transient() {
		$key = $this->register_collection( __METHOD__, static::LIFE );
		$transient = $this->get_transient( $key );

		$this->_test_items(   $transient );
		$this->_test_actions( $transient );
	}

}
