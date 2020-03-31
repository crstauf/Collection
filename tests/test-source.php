<?php

class Collection_Test_Source {

	function test_runtime_source() {
		$key = $this->register_collection( __METHOD__ );
		$this->assertEquals( 'runtime', $this->get_collection( $key )->source );
	}

	function test_cached() {
		$key = $this->register_collection( __METHOD__ );
		$this->assertEquals( 'object_cache', $this->get_cached( $key )->source );
	}

	function test_transient() {
		$key = $this->register_collection( __METHOD__, static::LIFE );
		$this->assertEquals( 'transient', $this->get_transient( $key )->source );
	}

}
