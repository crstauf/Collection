<?php

require_once 'test-simple.php';

class Collection_Expiration_Test extends Collection_Simple_Test {

	const LIFE = 3;
	protected static $time = null;

	static function collection_callback() {
		$array = parent::collection_callback();
		$time = time();

		if ( is_null( static::$time ) )
			static::$time = $time;

		$array['time'] = $time;

		return $array;
	}

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

	/**
	 * @todo fix
	 */
	function test_expiration() {
		sleep( static::LIFE + 1 );
		$collection = get_collection( static::COLLECTION_KEY );
		$this->assertTrue( $collection['time'] !== static::$time );
	}

	protected function get_items() {
		$items = parent::get_items();

		unset(
			$items[0]['time'],
			$items[1]['time']
		);

		return $items;
	}

}
