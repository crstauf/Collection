<?php

class Collection_Test_Contains extends Collection_UnitTestCase {

	protected function _test( Collection $collection ) {
		$this->assertTrue( $collection->contains( static::range()[0] ) );
		$this->assertTrue( $collection->contains( static::range()[1] ) );
		$this->assertTrue( $collection->contains( static::range()[2] ) );
		$this->assertTrue( $collection->contains( 'bar' ) );

		$this->assertFalse( $collection->contains(  9999  ) );
		$this->assertFalse( $collection->contains(  'foo' ) );
		$this->assertFalse( $collection->contains( 'rand' ) );

		# Test strictness.
		$this->assertFalse( $collection->contains( false ) );
		$this->assertFalse( $collection->contains(  true ) );
		$this->assertFalse( $collection->contains( ( string ) static::range()[0] ) );
		$this->assertTrue(  $collection->contains( ( string ) static::range()[0], false ) );
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
