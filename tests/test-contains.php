<?php

class Collection_Test_Contains extends Collection_UnitTestCase {

	protected function _test( Collection $collection ) {
		$this->assertTrue( $collection->contains( 1 ) );
		$this->assertTrue( $collection->contains( 2 ) );
		$this->assertTrue( $collection->contains( 3 ) );
		$this->assertTrue( $collection->contains( 4 ) );
		$this->assertTrue( $collection->contains( 5 ) );
		$this->assertTrue( $collection->contains( 'bar' ) );

		$this->assertFalse( $collection->contains( 'foo'  ) );
		$this->assertFalse( $collection->contains( 'rand' ) );
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
