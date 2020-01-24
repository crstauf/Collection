<?php

require_once 'test-simple.php';

/**
 * @todo define check that Collection expired
 */
class Collection_Expiration_Test extends Collection_Simple_Test {

	const LIFE = 5;

	function setUp() {
		if ( Collection::class() !== 'Collection' )
			static::$classes[] = Collection::class();

		register_collection( static::COLLECTION_KEY, array( __CLASS__, 'collection_callback' ), static::LIFE );
		static::$collection = get_collection( static::COLLECTION_KEY );
	}

	function test_in_transients() {
		$transient = get_transient( Collection::transient_name( static::COLLECTION_KEY ) );
		$this->assertInstanceOf( Collection::class(), $transient );
	}

}
