<?php

class Collection_Test_Key extends Collection_UnitTestCase {

	protected function _test( $key, Collection $collection ) {
		$this->assertEquals( $key, $collection->key );
	}

	function test_runtime() {
		$key = $this->register_collection( __METHOD__ );
		$this->_test( $key, $this->get_runtime( $key ) );
	}

	function test_cached() {
		$key = $this->register_collection( __METHOD__ );
		$this->_test( $key, $this->get_cached( $key ) );
	}

	function test_transient() {
		$key = $this->register_collection( __METHOD__, static::LIFE );
		$this->_test( $key, $this->get_transient( $key ) );
	}

}
