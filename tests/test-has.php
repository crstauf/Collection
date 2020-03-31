<?php

class Collection_Test_Has extends Collection_UnitTestCase {

	protected function _test( Collection $collection ) {
		$this->assertTrue( $collection->has( 0 ) );
		$this->assertTrue( $collection->has( 1 ) );
		$this->assertTrue( $collection->has( 2 ) );
		$this->assertTrue( $collection->has( 'foo' ) );
		$this->assertTrue( $collection->has( 3 ) );
		$this->assertTrue( $collection->has( 4 ) );
		$this->assertTrue( $collection->has( 'rand' ) );

		$this->assertFalse( $collection->has( '___nothing' ) );
		$this->assertFalse( $collection->has( 99999 ) );
	}

	function test_runtime() {
		$key = $this->register_collection( __METHOD__ );
		$this->_test( $this->get_runtime( $key ) );
	}

	function test_cached() {
		$key = $this->register_collection( __METHOD__ );
		$this->_test( $this->get_cached( $key ) );
	}

	function test_transient() {
		$key = $this->register_collection( __METHOD__, static::LIFE );
		$this->_test( $this->get_transient( $key ) );
	}

}
