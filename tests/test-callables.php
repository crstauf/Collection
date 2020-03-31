<?php

class Collection_Test_Callables extends Collection_UnitTestCase {

	protected function _test( Collection $collection ) {
		foreach ( array(
			'has',
			'contains',
			'refresh',
			'count',
			'rewind',
			'current',
			'key',
			'next',
			'valid',
		) as $method_name )
			$this->assertIsCallable( array( $collection, $method_name ) );
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
