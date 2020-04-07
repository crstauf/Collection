<?php

/**
 * @group debugging
 */
class Collection_Test_Debugging extends Collection_UnitTestCase {

	static function collection_callback_no_rand() {
		$items = parent::collection_callback();
		unset( $items['rand'] );

		return $items;
	}

	/**
	 * @runInSeparateProcess
	 * @group access_log
	 */
	function test_access_log() {
		define( 'LOG_COLLECTION_ACCESS', true );

		$key = $this->register_collection( __METHOD__, 5 );
		$runtime = $this->get_runtime( $key );
		$count = 0;

		$this->assertEmpty( $runtime->access_log );

		$runtime->items;
		$this->assertEquals( ++$count, count( $runtime->access_log ), 'Access by `Collection->access_log` was not logged.' );
		$this->assertEquals( 'string', gettype( current( $runtime->access_log ) ) );

		$runtime->get_item( 0 );
		$this->assertEquals( ++$count, count( $runtime->access_log ), 'Access by `Collection->get_item()` was not logged.' );

		$runtime->get_proper_items();
		$this->assertEquals( ++$count, count( $runtime->access_log ), 'Access by `Collection->get_proper_items()` was not logged.' );

		foreach ( $runtime as $v ) {}
		$this->assertEquals( ++$count, count( $runtime->access_log ), 'Access by `foreach( Collection => $v )` was not logged.' );

		$runtime[0];
		$this->assertEquals( ++$count, count( $runtime->access_log ), 'Access by `Collection[0]` was not logged.' );

		$log_times = array_keys( $runtime->access_log );
		$this->assertEquals( 'string', gettype( $log_times[0] ) );
		$this->assertEquals( ( float ) $log_times[0], $log_times[0] );

		$cached = $this->get_cached( $key );
		$this->assertEmpty( $cached->access_log );

		$transient = $this->get_transient( $key );
		$this->assertEmpty( $transient->access_log );
	}

	/**
	 * @group access_log
	 */
	function test_not_logging_access() {
		$key = $this->register_collection( __METHOD__, 5 );
		$runtime = $this->get_runtime( $key );

		$runtime->get_item( 0 );
		$runtime->get_proper_items();
		foreach ( $runtime as $v ) {}
		$runtime[0];

		$this->assertEmpty( $runtime->access_log );
	}

	/**
	 * @runInSeparateProcess
	 * @group check_duplicates
	 */
	function test_check_duplicates() {
		define( 'CHECK_COLLECTION_DUPLICATES', true );

		$keys = array( $this->register_collection( __METHOD__ ) );
		$runtimes = array( $this->get_runtime( $keys[0] ) );

		$keys[] = uniqid( $keys[0] );
		register_collection( end( $keys ), function() use( $runtimes ) { return $runtimes[0]->items; } );

		$runtimes[] = @$this->get_runtime( end( $keys ) );
		$this->assertEquals( $runtimes[0]->items, end( $runtimes )->items );
		$this->assertEquals( 1, did_action( 'collection/found_duplicate' ) );
		$this->assertEquals( 1, did_action( 'collection:' . end( $keys ) . '/found_duplicate' ) );

		# This test is to ensure no error if not duplicate.
		$keys[] = $this->register_collection( uniqid( $keys[0] ) );
		$runtimes[] = $this->get_runtime( end( $keys ) );
		$this->assertNull( null );

		$keys[] = uniqid( $keys[0] );
		register_collection( end( $keys ), function() use( $runtimes ) { return $runtimes[0]->items; } );

		$this->expectException( 'PHPUnit_Framework_Error_Notice' );
		$runtimes[] = $this->get_runtime( end( $keys ) );
	}

	/**
	 * @group check_duplicates
	 */
	function test_not_checking_duplicates() {
		$key = static::key( __METHOD__ );
		register_collection( $key, array( __CLASS__, 'collection_callback_no_rand' ) );
		$runtime = $this->get_runtime( $key );

		$key2 = $key . '2';
		register_collection( $key2, array( __CLASS__, 'collection_callback_no_rand' ) );
		$runtime2 = $this->get_runtime( $key );

		$this->assertEquals( $runtime->items, $runtime2->items );
	}

}