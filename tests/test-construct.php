<?php

class Collection_Test_Construct extends Collection_UnitTestCase {

	function test_construct_actions() {
		$key = $this->register_collection( __METHOD__, static::LIFE );

		$constructed_callback = function( $collection ) use( $key ) {
			$cached = get_collection( $key );

			$this->assertInstanceOf( Collection::class, $collection );
			$this->assertEquals( $cached->created,      $collection->created );
			$this->assertEquals( $cached->items,        $collection->items );
		};

		add_action( 'collection_constructed', $constructed_callback );
		add_action( 'collection:' . $key . '/constructed', $constructed_callback );

		$this->get_runtime( $key );

		remove_action( 'collection_constructed', $constructed_callback );
		remove_action( 'collection:' . $key . '/constructed', $constructed_callback );

		$this->assertEquals( 1, did_action( 'collection_constructed' ) );
		$this->assertEquals( 1, did_action( 'collection:' . $key . '/constructed' ) );
	}

	function test_track_constructs() {
		$key = $this->register_collection( __METHOD__ );
		$this->get_runtime( $key );

		wp_cache_delete( $key, Collection::class );

		$this->expectException( 'PHPUnit_Framework_Error_Notice' );
		$this->get_runtime( $key );
	}

}
