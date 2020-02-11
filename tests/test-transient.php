<?php

require_once 'test-expiration.php';

class Collection_Transient_Test extends Collection_Test_Base {

	const COLLECTION_KEY_PREFIX = '_phpunit_transient_';
	const LIFE = 1;

	protected function register_collection( $key_suffix ) {
		$key = self::key( $key_suffix );
		register_collection( $key, array( __CLASS__, 'collection_callback' ), self::LIFE );
		return $key;
	}

	protected function get_collection( $key ) {
		get_collection( $key );
		return $this->get_transient( $key );
	}

	protected function get_transient( $key ) {
		$transient_key = Collection::transient_key( $key );
		return get_transient( $transient_key );
	}

	function test_source() {
		$key = $this->register_collection( __FUNCTION__ );
		$transient = $this->get_collection( $key );

		$this->assertEquals( 'transient', $transient->source );
	}

}
