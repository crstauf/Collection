<?php

class Collection_Test_Transient extends Collection_UnitTestCase {

	protected function _test( $transient ) {
		$this->assertNotEmpty( $transient );
		$this->assertInstanceOf( Collection::class, $transient );
	}

	function test_get_from_transient() {
		$key = $this->register_collection( __METHOD__, static::LIFE );
		$this->_test( $this->get_transient( $key ) );
	}

	function test_get_from_transient_direct() {
		$key = $this->register_collection( __METHOD__, static::LIFE );
		$this->get_runtime( $key );
		$this->_test( $this->_get_transient( $key ) );
	}

}
