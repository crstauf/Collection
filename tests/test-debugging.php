<?php

class Collection_Test_Debugging extends Collection_UnitTestCase {

	function test_access_log() {
		$key = $this->register_collection( __METHOD__, 5 );
		$runtime = $this->get_runtime( $key );

		$count = 0;
		$this->assertEmpty( $runtime->access_log );

		$runtime->items;
		$count++;

		$this->assertNotEmpty( $runtime->access_log );
		$this->assertEquals( $count, count( $runtime->access_log ) );
		$this->assertEquals( 'string', gettype( current( $runtime->access_log ) ) );

		$runtime[0];
		$count++;

		$this->assertEquals( $count, count( $runtime->access_log ) );

		$runtime->get_item( 0 );
		$count++;

		$this->assertEquals( $count, count( $runtime->access_log ) );

		$runtime->get_proper_items();
		$count++;

		$this->assertEquals( $count, count( $runtime->access_log ) );

		foreach ( $runtime as $k => $v ) {}
		$count++;

		$this->assertEquals( $count, count( $runtime->access_log ) );

		$log_times = array_keys( $runtime->access_log );
		$this->assertEquals( 'string', gettype( $log_times[0] ) );
		$this->assertEquals( ( float ) $log_times[0], $log_times[0] );

		$cached = $this->get_cached( $key );
		$this->assertEmpty( $cached->access_log );

		$transient = $this->get_transient( $key );
		$this->assertEmpty( $transient->access_log );
	}

}