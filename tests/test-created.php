<?php

class Collection_Test_Created extends Collection_UnitTestCase {

	protected function _test( Collection $collection ) {
		$this->assertInstanceOf( 'DateTime', $collection->created );
		$this->assertLessThan( date_create( 'now', new DateTimeZone( 'UTC' ) ), $collection->created );
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

	function test_equal() {
		$key = $this->register_collection( __METHOD__, static::LIFE );

		$runtime   = $this->get_runtime( $key );
		$cached    = $this->get_cached( $key );
		$transient = $this->get_transient( $key );

		$this->assertEquals( $runtime->created,    $cached->created );
		$this->assertEquals( $runtime->created, $transient->created );
		$this->assertEquals(  $cached->created, $transient->created );
	}

}
