<?php

require_once 'test-expiration.php';

class Collection_Transient_Test extends Collection_Expiration_Test {

	const COLLECTION_KEY_PREFIX = '_phpunit_transient_';

	protected function get_collection( $key ) {
		get_collection( $key );
		return $this->get_transient( $key );
	}

	function test_get_unregistered() {
		$this->assertNull( null );
	}

	function test_uncallable_callback() {
		$this->assertNull( null );
	}

}
